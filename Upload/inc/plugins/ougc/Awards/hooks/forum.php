<?php

/***************************************************************************
 *
 *    ougc Awards plugin (/inc/plugins/ougc/Awards/hooks/admin.php)
 *    Author: Omar Gonzalez
 *    Copyright: © 2012 Omar Gonzalez
 *
 *    Website: https://ougc.network
 *
 *    Manage a powerful awards system for your community.
 *
 ***************************************************************************
 ****************************************************************************
 * This program is protected software: you can make use of it under
 * the terms of the OUGC Network EULA as detailed by the included
 * "EULA.TXT" file.
 *
 * This program is distributed with the expectation that it will be
 * useful, but WITH LIMITED WARRANTY; with a limited warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * OUGC Network EULA included in the "EULA.TXT" file for more details.
 *
 * You should have received a copy of the OUGC Network EULA along with
 * the package which includes this file.  If not, see
 * <https://ougc.network/eula.txt>.
 ****************************************************************************/

declare(strict_types=1);

namespace ougc\Awards\Hooks\Forum;

use MyBB;

use MybbStuff_MyAlerts_AlertFormatterManager;
use OUGC_Awards_MyAlerts_Formatter;

use postParser;

use function ougc\Awards\Core\awardGetIcon;
use function ougc\Awards\Core\awardGetUser;
use function ougc\Awards\Core\awardsCacheGet;
use function ougc\Awards\Core\awardsGetCache;
use function ougc\Awards\Core\categoryGetCache;
use function ougc\Awards\Core\cacheUpdate;
use function ougc\Awards\Core\isModerator;
use function ougc\Awards\Core\myAlertsInitiate;
use function ougc\Awards\Core\parseMessage;
use function ougc\Awards\Core\parseUserAwards;
use function ougc\Awards\Core\presetGet;
use function ougc\Awards\Core\presetUpdate;
use function ougc\Awards\Core\urlHandlerBuild;
use function ougc\Awards\Core\loadLanguage;
use function ougc\Awards\Core\getTemplate;
use function ougc\Awards\Core\urlHandlerGet;
use function ougc\Awards\Core\getSetting;

use const ougc\Awards\Core\AWARD_TEMPLATE_TYPE_CLASS;
use const ougc\Awards\Core\GRANT_STATUS_POSTS;
use const ougc\Awards\Core\GRANT_STATUS_VISIBLE;
use const ougc\Awards\Core\REQUEST_STATUS_PENDING;
use const TIME_NOW;

function global_start05(): bool
{
    myAlertsInitiate();

    global $templatelist;

    if (isset($templatelist)) {
        $templatelist .= ',';
    } else {
        $templatelist = '';
    }

    $templatelist .= ',';

    return true;
}

function global_intermediate(): bool
{
    global $mybb, $db, $lang, $templates, $ougcAwardsMenu, $ougcAwardsGlobalNotificationRequests, $ougcAwardsViewAll, $ougcAwardsJavaScript, $ougcAwardsCSS;

    loadLanguage();

    $currentUserID = (int)$mybb->user['uid'];

    $ougcAwardsJavaScript = eval(getTemplate('js'));

    $ougcAwardsCSS = eval(getTemplate('css'));

    $ougcAwardsMenu = eval(getTemplate('global_menu'));

    $ougcAwardsGlobalNotificationRequests = $ougcAwardsViewAll = '';

    if ($currentUserID) {
        $ougcAwardsViewAll = eval(getTemplate('viewAll'));
    }

    $cacheData = awardsCacheGet();

    if ($cacheData['time'] > (TIME_NOW - (60 * 5))) {
        cacheUpdate();
    }

    if (!$mybb->user['uid']) {
        return false;
    }

    $isOwner = !empty($mybb->user['ougc_awards_owner']);

    if (!isModerator() && !$isOwner) {
        return false;
    }

    cacheUpdate();

    $awardsCache = $cacheData['awards'] ?? [];

    $awardRequestsCache = $cacheData['requests'] ?? [];

    $pendingRequestCount = empty($awardRequestsCache['pending']) ? 0 : (int)$awardRequestsCache['pending'];

    if (!isModerator() && $pendingRequestCount) {
        if ($awardIDs = array_keys($awardsCache)) {
            $query = $db->simple_select(
                'ougc_awards_owners',
                'aid',
                "uid='{$currentUserID}' AND aid IN ('" . implode("','", $awardIDs) . "')"
            );

            $awardIDs = [];

            while ($awardID = $db->fetch_field($query, 'aid')) {
                $awardIDs[] = (int)$awardID;
            }

            if ($awardIDs) {
                $statusPending = REQUEST_STATUS_PENDING;

                $query = $db->simple_select(
                    'ougc_awards_requests',
                    'COUNT(rid) AS pending',
                    "status='{$statusPending}' AND aid IN ('" . implode("','", $awardIDs) . "')"
                );

                $pendingRequestCount = (int)$db->fetch_field($query, 'pending');
            }
        }
    }

    if ($pendingRequestCount < 1) {
        return false;
    }

    $messageContent = $lang->sprintf(
        $pendingRequestCount > 1 ? $lang->ougcAwardsGlobalNotificationRequestsPlural : $lang->ougcAwardsGlobalNotificationRequests,
        $mybb->settings['bburl'],
        urlHandlerGet(),
        my_number_format($pendingRequestCount)
    );

    $ougcAwardsGlobalNotificationRequests = eval(getTemplate('globalNotification'));

    return true;
}

function fetch_wol_activity_end(array &$activityArguments): array
{
    if ($activityArguments['activity'] === 'unknown' && my_strpos(
            $activityArguments['location'],
            'awards.php'
        ) !== false) {
        $activityArguments['activity'] = 'ougc_awards';
    }

    return $activityArguments;
}

function build_friendly_wol_location_end(array &$locationArguments): array
{
    if ($locationArguments['user_activity']['activity'] === 'ougc_awards') {
        global $mybb, $lang;

        loadLanguage();

        $locationArguments['location_name'] = $lang->sprintf(
            $lang->ougcAwardsWhoIsOnlineViewing,
            $mybb->settings['bburl']
        );
    }

    return $locationArguments;
}

function xmlhttp05(): bool
{
    myAlertsInitiate();

    return true;
}

function xmlhttp(): bool
{
    global $mybb, $lang;

    if ($mybb->get_input('action') === 'awardPresets') {
        loadLanguage();

        $mybb->input['ajax'] = 1;

        if (!is_member(getSetting('allowedGroupsPresets'))) {
            error_no_permission();
        }

        $presetID = $mybb->get_input('presetID', MyBB::INPUT_INT);

        $currentUserID = (int)$mybb->user['uid'];

        if ($presetID && !($currentPresetData = presetGet([
                "pid='{$presetID}'",
                "uid='{$currentUserID}'"
            ], '*', ['limit' => 1]
            ))) {
            error_no_permission();
        }

        if (!empty($lang->settings['charset'])) {
            $charset = $lang->settings['charset'];
        } else {
            $charset = 'UTF-8';
        }

        header("Content-type: application/json; charset={$charset}");

        $responseData = [];

        if ($mybb->request_method === 'post') {
            if (!empty($currentPresetData)) {
                $hiddenAwards = json_decode($mybb->get_input('hiddenAwards'), true);

                $hiddenAwards = empty($hiddenAwards) ? '' : my_serialize(array_map('intval', $hiddenAwards));

                $visibleAwards = json_decode($mybb->get_input('visibleAwards'), true);

                $visibleAwards = empty($visibleAwards) ? '' : my_serialize(array_map('intval', $visibleAwards));

                if (presetUpdate([
                    'hidden' => $hiddenAwards,
                    'visible' => $visibleAwards,
                ], $presetID)) {
                    $responseData = ['success' => $lang->ougcAwardsControlPanelPresetsSuccess];
                } else {
                    $responseData = ['error' => $lang->ougcAwardsControlPanelPresetsError];
                }
            }
        }

        echo json_encode($responseData);

        exit;
    }

    return true;
}

function postbit_prev(array &$postData): array
{
    return postbit($postData);
}

function postbit_pm(array &$postData): array
{
    return postbit($postData);
}

function postbit_announcement(array &$postData): array
{
    return postbit($postData);
}

function postbit(array &$postData): array
{
    global $mybb, $lang;

    $maximumAwardsInPost = (int)getSetting('showInPosts');

    $awardsCache = awardsCacheGet()['awards'];

    $postData['ougc_awards'] = $postData['ougc_awards_preset'] = $postData['ougc_awards_view_all'] = '';

    $postUserID = (int)$postData['uid'];

    if (getSetting('showInPostsPresets') && is_member(getSetting('allowedGroupsPresets'), $postData)) {
        global $db;

        static $presetsCache = null;

        static $presetsAwardsCache = null;

        if ($presetsCache === null) {
            $presetsCache = $presetsAwardsCache = $presetIDs = [];

            $tablesObjects = [
                'ougc_awards a',
                'ougc_awards_users ag ON (ag.aid=a.aid)',
                'users u ON (u.uid=ag.uid)'
            ];

            $whereClauses = ["ag.visible='1'"];

            if (isset($GLOBALS['pids'])) {
                $tablesObjects[] = 'posts p ON (p.uid=ag.uid)';

                $whereClauses[] = "p.{$GLOBALS['pids']}";
            }

            $query = $db->simple_select(
                implode(" LEFT JOIN {$db->table_prefix}", $tablesObjects),
                'ag.gid, ag.uid, ag.oid, ag.aid, ag.rid, ag.reason, ag.date, u.ougc_awards_preset',
                implode(' AND ', $whereClauses),
                [
                    'order_by' => 'ag.disporder, ag.date',
                    'order_dir' => 'desc'
                ]
            );

            while ($grantData = $db->fetch_array($query)) {
                if (!empty($grantData['ougc_awards_preset'])) {
                    $presetIDs[(int)$grantData['ougc_awards_preset']] = 1;
                }

                if (!empty($grantData['gid'])) {
                    $presetsAwardsCache[(int)$grantData['uid']][(int)$grantData['gid']] = $grantData;
                }
            }

            if ($presetIDs) {
                $presetIDs = implode("','", array_keys($presetIDs));

                $query = $db->simple_select(
                    'ougc_awards_presets',
                    'uid, visible, name',
                    "pid IN ('{$presetIDs}')"
                );

                while ($presetData = $db->fetch_array($query)) {
                    $presetsCache[(int)$presetData['uid']] = $presetData;
                }
            }
        }

        if (isset($presetsCache[$postUserID])) {
            $presetData = $presetsCache[$postUserID];

            if (!empty($presetData['visible'])) {
                $presetName = htmlspecialchars_uni($presetData['name']);

                $visibleAwards = array_filter((array)my_unserialize($presetData['visible']));

                $count = 0;

                $awardsList = '';

                foreach ($visibleAwards as $grantID) {
                    $grantData = $presetsAwardsCache[$postUserID][$grantID];

                    if (empty($grantData['gid'])) {
                        continue;
                    }

                    $awardID = (int)$grantData['aid'];

                    if (empty($awardsCache[$awardID])) {
                        continue;
                    }

                    ++$count;

                    $awardName = htmlspecialchars_uni($awardsCache[$awardID]['name']);

                    $awardDescription = htmlspecialchars_uni($awardsCache[$awardID]['description']);

                    $userName = format_name(
                        htmlspecialchars_uni($postData['username']),
                        $postData['usergroup'],
                        $postData['displaygroup']
                    );

                    $grantReason = $grantData['reason'];

                    parseMessage($grantReason);

                    $awardImage = $awardClass = awardGetIcon($awardID);

                    $grantDate = my_date('normal', $grantData['date']);

                    $awardsList .= eval(
                    getTemplate(
                        $awardsCache[$awardID]['template'] === AWARD_TEMPLATE_TYPE_CLASS ? 'awardImageClass' : 'awardImage'
                    )
                    );

                    if ($count >= getSetting('showInPostsPresets')) {
                        break;
                    }
                }

                if ($awardsList) {
                    $postData['ougc_awards_preset'] = eval(getTemplate('postBitPreset'));
                }
            }
        }
    }

    if ($maximumAwardsInPost < 1) {
        return $postData;
    }

    static $postAwardsCache = null;

    if (!isset($postAwardsCache)) {
        $awardsCategoriesCache = awardsCacheGet()['categories'];

        global $db;

        $categoriesIDs = implode("','", array_keys($awardsCategoriesCache));

        $tablesObjects = [
            'ougc_awards a',
            'ougc_awards_users ag ON (ag.aid=a.aid)'
        ];

        $whereClauses = [
            "a.visible='1'",
            "a.type!='1'",
            "a.cid IN ('{$categoriesIDs}')",
            'ag.visible=1',
        ];

        if (isset($GLOBALS['pids'])) {
            $tablesObjects[] = 'posts p ON (p.uid=ag.uid)';

            $whereClauses[] = "p.{$GLOBALS['pids']}";
            // how to limit by uid here?
            // -- '.('LIMIT '.$maximumAwardsInPost)
        } else {
            $whereClauses[] = "ag.uid='{$postUserID}'";
        }

        $query = $db->simple_select(
            implode(" LEFT JOIN {$db->table_prefix}", $tablesObjects),
            'ag.gid, ag.uid, ag.oid, ag.aid, ag.rid, ag.reason, ag.date',
            implode(' AND ', $whereClauses),
            [
                'order_by' => 'ag.disporder, ag.date',
                'order_dir' => 'desc',
            ]
        );

        $postAwardsCache = [];

        while ($grantData = $db->fetch_array($query)) {
            $postAwardsCache[(int)$grantData['uid']][(int)$grantData['gid']] = $grantData;
        }
    }

    if (empty($postAwardsCache[$postUserID])) {
        return $postData;
    }

    loadLanguage();

    $count = 0;

    $total = count($postAwardsCache[$postUserID]);

    $awardsList = '';

    foreach ($postAwardsCache[$postUserID] as $grantData) {
        if ($count >= $maximumAwardsInPost) {
            break;
        }

        $grantID = (int)$grantData['gid'];

        $awardID = (int)$grantData['aid'];

        //$awardData = array_merge(awardGet($awardID), $grantData);

        $awardName = htmlspecialchars_uni($awardsCache[$awardID]['name']);

        $awardDescription = htmlspecialchars_uni($awardsCache[$awardID]['description']);

        $userName = format_name(
            htmlspecialchars_uni($postData['username']),
            $postData['usergroup'],
            $postData['displaygroup']
        );

        $grantReason = $grantData['reason'];

        parseMessage($grantReason);

        $grantDate = my_date('normal', $grantData['date']);

        $awardImage = $awardClass = awardGetIcon($awardID);

        $awardImage = eval(
        getTemplate(
            $awardsCache[$awardID]['template'] === AWARD_TEMPLATE_TYPE_CLASS ? 'awardImageClass' : 'awardImage'
        )
        );

        $awardUrl = urlHandlerBuild(['action' => 'viewUsers', 'awardID' => $awardID]);

        $postData['ougc_awards'] .= eval(getTemplate('awardWrapper'));

        ++$count;
    }

    if ($total > $maximumAwardsInPost) {
        $postData['ougc_awards_view_all'] = eval(getTemplate('postBitViewAll'));
    }

    //$postData['user_details'] = str_replace('<!--OUGC_AWARDS-->', $postData['ougc_awards'], $postData['user_details']);

    return $postData;
}

function member_profile_end(): bool
{
    global $mybb, $memprofile, $parser;
    global $db, $lang, $theme;

    $memprofile['ougc_awards'] = $memprofile['ougc_awards_preset'] = $memprofile['ougc_awards_view_all'] = '';

    $maximumAwardsInProfile = (int)getSetting('showInProfile');

    if ($maximumAwardsInProfile < 1) {
        $maximumAwardsInProfile = 0;
    }

    $profileUserID = (int)$memprofile['uid'];

    $maximumAwardsInProfilePresets = 0;

    if (is_member(getSetting('allowedGroupsPresets'), $memprofile)) {
        $maximumAwardsInProfilePresets = (int)getSetting('showInProfilePresets');

        if ($maximumAwardsInProfilePresets < 1) {
            $maximumAwardsInProfilePresets = 0;
        }
    }

    if ($maximumAwardsInProfilePresets) {
        $presetID = (int)(get_user($profileUserID)['ougc_awards_preset'] ?? 0);

        $presetData = presetGet(["pid='{$presetID}'", "uid='{$profileUserID}'"], '*', ['limit' => 1]);

        if (empty($presetData['visible'])) {
            $maximumAwardsInProfilePresets = 0;
        }
    }

    if (!$maximumAwardsInProfile && !$maximumAwardsInProfilePresets) {
        return false;
    }

    loadLanguage();

    //urlHandlerSet(get_profile_link($profileUserID));

    $categoryIDs = $awardIDs = [];

    $categoriesCache = categoryGetCache();

    foreach ($categoriesCache as $categoryID => $categoryData) {
        $categoryIDs[$categoryID] = $categoryID;
    }

    $awardsCache = awardsGetCache();

    foreach ($awardsCache as $awardID => $awardData) {
        if (!empty($categoryIDs[$awardData['cid']]) && (int)$awardData['type'] !== GRANT_STATUS_POSTS) {
            $awardIDs[$awardID] = $awardID;
        }
    }

    $categoryIDs = implode("','", $categoryIDs);

    $awardIDs = implode("','", $awardIDs);

    $grantStatusVisible = GRANT_STATUS_VISIBLE;

    $whereClauses = [
        "aid IN ('{$awardIDs}')",
        "uid='{$profileUserID}'",
        "visible='{$grantStatusVisible}'",
    ];

    $totalGrantedCount = awardGetUser($whereClauses, 'COUNT(gid) AS totalGranted', ['limit' => 1]);

    if (empty($totalGrantedCount['totalGranted'])) {
        $totalGrantedCount = 0;
    } else {
        $totalGrantedCount = (int)$totalGrantedCount['totalGranted'];
    }

    $startPage = 0;

    $currentPage = 1;

    $paginationMenu = '';

    if ($maximumAwardsInProfile && $totalGrantedCount) {
        $currentPage = $mybb->get_input('view') == 'awards' ? $mybb->get_input('page', MyBB::INPUT_INT) : 1;

        if ($currentPage > 0) {
            $startPage = ($currentPage - 1) * $maximumAwardsInProfile;

            if ($currentPage > ceil($totalGrantedCount / $maximumAwardsInProfile)) {
                $startPage = 0;

                $currentPage = 1;
            }
        }

        $paginationMenu = (string)multipage(
            $totalGrantedCount,
            $maximumAwardsInProfile,
            $currentPage,
            "javascript: ougcAwards.ViewAwards('{$profileUserID}', '{page}');"
        //urlHandlerBuild(['view' => 'awards'])
        );

        if ($paginationMenu) {
            $paginationMenu = eval(getTemplate('profilePagination'));
        }
    }

    $queryOptions = [
        'order_by' => 'disporder, date',
        'order_dir' => 'desc'
    ];

    $queryOptions['limit'] = $maximumAwardsInProfile;

    $queryOptions['limit_start'] = $startPage;

    $grantCacheData = awardGetUser(
        $whereClauses,
        '*',
        $queryOptions
    );

    //$_awards = [];

    $grantedList = $presetList = '';

    if (!$totalGrantedCount) {
        if ($maximumAwardsInProfile) {
            $grantedList = eval(getTemplate('profileEmpty'));
        }
    } else {
        $threadIDs = array_filter(array_map('intval', array_column($grantCacheData, 'thread')));

        if ($threadIDs) {
            $threadIDs = implode("','", $threadIDs);

            $dbQuery = $db->simple_select(
                'threads',
                'tid, subject, prefix',
                "visible>0  AND closed NOT LIKE 'moved|%' AND tid IN ('{$threadIDs}')"
            );

            while ($threadData = $db->fetch_array($dbQuery)) {
                $threadsCache[(int)$threadData['tid']] = $threadData;
            }
        }

        if ($maximumAwardsInProfile) {
            parseUserAwards($grantedList, $grantCacheData);
        }

        if ($maximumAwardsInProfilePresets) {
            $presetAwards = array_filter(
                (array)my_unserialize($presetData['visible'])
            );

            require_once MYBB_ROOT . 'inc/class_parser.php';

            is_object($parser) || $parser = new postParser();

            $presetAwards = implode("','", $presetAwards);

            $grantPresetsCacheData = awardGetUser(
                array_merge($whereClauses, ["gid IN ('{$presetAwards}')"]),
                '*',
                [
                    'order_by' => 'disporder, date',
                    'order_dir' => 'desc'
                ]
            );

            parseUserAwards($presetList, $grantPresetsCacheData, 'profilePresetsRow');
        }
    }

    if ($maximumAwardsInProfile) {
        $lang->ougcAwardsProfileTitle = $lang->sprintf(
            $lang->ougcAwardsProfileTitle,
            htmlspecialchars_uni($memprofile['username'])
        );

        $memprofile['ougc_awards'] = eval(getTemplate('profile'));
    }

    if ($presetList) {
        $alternativeBackground = alt_trow(true);

        $presetName = $presetData['name'] = htmlspecialchars_uni($presetData['name']);

        $memprofile['ougc_awards_preset'] = eval(getTemplate('profilePresets'));
    }

    if ($mybb->get_input('ajax', MyBB::INPUT_INT)) {
        if (!empty($lang->settings['charset'])) {
            $charset = $lang->settings['charset'];
        } else {
            $charset = 'UTF-8';
        }

        header("Content-type: application/json; charset={$charset}");

        echo json_encode(['content' => $memprofile['ougc_awards']]);

        exit;
    }

    if ($totalGrantedCount > $maximumAwardsInProfile) {
        $memprofile['ougc_awards_view_all'] = eval(getTemplate('profileViewAll'));
    }

    return true;
}

function stats_end(): bool
{
    global $db, $lang, $ougc_awards_most, $ougcAwardsStatsLast, $theme;

    $ougc_awards_most = $ougcAwardsStatsLast = $userList = '';

    if (!getSetting('enablestatspage')) {
        return false;
    }

    loadLanguage();

    $statsCache = awardsCacheGet();

    if (empty($statsCache['top'])) {
        $userList = eval(getTemplate('stats_empty'));
    } else {
        $usersCache = [];

        $query = $db->simple_select(
            'users',
            'uid, username, usergroup, displaygroup',
            "uid IN ('" . implode("','", array_keys($statsCache['top'])) . "')"
        );

        while ($userData = $db->fetch_array($query)) {
            $usersCache[(int)$userData['uid']] = $userData;
        }

        $alternativeBackground = alt_trow(true);

        $statOrder = 0;

        foreach ($statsCache['top'] as $userID => $total) {
            ++$statOrder;

            $usernameFormatted = format_name(
                htmlspecialchars_uni($usersCache[$userID]['username']),
                $usersCache[$userID]['usergroup'],
                $usersCache[$userID]['displaygroup']
            );

            $profileLink = build_profile_link($usersCache[$userID]['username'], $userID);

            $profileLinkFormatted = build_profile_link(
                $usernameFormatted,
                $userID
            );

            $message = $total;

            $grantDate = '';

            $userList .= eval(getTemplate('statsUserRow'));

            $alternativeBackground = alt_trow();
        }
    }

    $title = $lang->ougcAwardsStatsMostTitle;

    $ougc_awards_most = eval(getTemplate('stats'));

    $userList = '';

    if (empty($statsCache['last'])) {
        $userList = eval(getTemplate('stats_empty'));
    } else {
        $usersCache = [];

        $query = $db->simple_select(
            'users',
            'uid, username, usergroup, displaygroup',
            "uid IN ('" . implode("','", array_values($statsCache['last'])) . "')"
        );

        while ($userData = $db->fetch_array($query)) {
            $usersCache[(int)$userData['uid']] = $userData;
        }

        $alternativeBackground = alt_trow(true);

        $statOrder = 0;

        foreach ($statsCache['last'] as $grantDate => $userID) {
            ++$statOrder;

            $usernameFormatted = format_name(
                htmlspecialchars_uni($usersCache[$userID]['username']),
                $usersCache[$userID]['usergroup'],
                $usersCache[$userID]['displaygroup']
            );

            $profileLink = build_profile_link($usersCache[$userID]['username'], $userID);

            $profileLinkFormatted = build_profile_link(
                $usernameFormatted,
                $userID
            );

            $grantDate = my_date('relative', $grantDate);

            $userList .= eval(getTemplate('statsUserRow'));

            $alternativeBackground = alt_trow();
        }
    }

    $title = $lang->ougc_awards_stats_last;

    $ougcAwardsStatsLast = eval(getTemplate('stats'));

    return true;
}

function myalerts_register_client_alert_formatters(): bool
{
    if (
        class_exists('MybbStuff_MyAlerts_Formatter_AbstractFormatter') &&
        class_exists('MybbStuff_MyAlerts_AlertFormatterManager') &&
        !class_exists('OUGC_Awards_MyAlerts_Formatter')
    ) {
        global $mybb, $lang;

        $formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::getInstance();

        if (!$formatterManager) {
            $formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::createInstance($mybb, $lang);
        }

        if ($formatterManager) {
            $formatterManager->registerFormatter(new OUGC_Awards_MyAlerts_Formatter($mybb, $lang, 'ougc_awards'));
        }
    }

    return true;
}