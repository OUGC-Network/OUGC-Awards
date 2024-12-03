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

use function ougc\Awards\Core\awardGetIcon;
use function ougc\Awards\Core\awardGetUser;
use function ougc\Awards\Core\awardsCacheGet;
use function ougc\Awards\Core\awardsGetCache;
use function ougc\Awards\Core\categoryGetCache;
use function ougc\Awards\Core\cacheUpdate;
use function ougc\Awards\Core\grantUpdate;
use function ougc\Awards\Core\myAlertsInitiate;
use function ougc\Awards\Core\parseMessage;
use function ougc\Awards\Core\parseUserAwards;
use function ougc\Awards\Core\presetGet;
use function ougc\Awards\Core\presetUpdate;
use function ougc\Awards\Core\urlHandlerBuild;
use function ougc\Awards\Core\loadLanguage;
use function ougc\Awards\Core\getTemplate;
use function ougc\Awards\Core\urlHandlerSet;
use function ougc\Awards\Core\getSetting;

use const ougc\Awards\Core\AWARD_TEMPLATE_TYPE_CLASS;
use const ougc\Awards\Core\GRANT_STATUS_POSTS;
use const ougc\Awards\Core\GRANT_STATUS_VISIBLE;
use const TIME_NOW;

function global_start(): bool
{
    myAlertsInitiate();

    global $cache, $templatelist;

    if (isset($templatelist)) {
        $templatelist .= ',';
    } else {
        $templatelist = '';
    }

    $templatelist .= 'ougcawards_js,ougcawards_css, ougcawards_global_menu,ougcawards_global_notification,ougcawards_welcomeblock,ougcawards_award_image,ougcawards_award_image_class,';

    $awards = awardsCacheGet();
    foreach ($awards['awards'] as $aid => $award) {
        if ($award['template'] == 2) {
            $templatelist .= 'ougcawards_award_image' . $aid . ',ougcawards_award_image_cat' . $award['cid'] . ',ougcawards_award_image_class' . $aid . ',ougcawards_award_image_class' . $aid . ',';
        }
    }
    unset($awards, $award);

    switch (constant('THIS_SCRIPT')) {
        case 'showthread.php':
        case 'newreply.php':
        case 'newthread.php':
        case 'editpost.php':
        case 'private.php':
        case 'announcements.php':
            $templatelist .= 'ougcawards_postbit, ougcawards_stats_user_viewall, ougcawards_postbit_preset_award, ougcawards_postbit_preset';
            break;
        case 'member.php':
            global $mybb;

            if ((string)$mybb->input['action'] == 'profile') {
                $templatelist .= 'ougcawards_profile_row, ougcawards_profile_row_category, ougcawards_profile, ougcawards_profile_multipage, multipage_prevpage, multipage_page, multipage_page_current, multipage_nextpage, multipage, ougcawards_profile_preset_row, ougcawards_profile_preset';
            }
            break;
        case 'usercp.php':
        case 'modcp.php':
            break;
        case 'stats.php':
            $templatelist .= 'ougcawards_stats_user_viewall, ougcawards_stats_user, ougcawards_stats';
            break;
    }

    return true;
}

function global_intermediate10(): bool
{
    global $mybb;

    $cacheData = awardsCacheGet();

    if ($cacheData['time'] > (TIME_NOW - (60 * 5))) {
        cacheUpdate();
    }

    global $ougcAwardsGlobalNotificationRequests;

    $ougcAwardsGlobalNotificationRequests = '';

    if (!empty($cacheData['requests']['pending'])) {
        global $lang;

        loadLanguage();

        $messageContent = $lang->sprintf(
            $lang->ougcAwardsGlobalNotificationsRequests,
            my_number_format($cacheData['requests']['pending'])
        );

        $ougcAwardsGlobalNotificationRequests = eval(getTemplate('globalNotification'));
    }

    return false;
}

function global_intermediate(): bool
{
    global $mybb, $db, $awards, $lang, $templates, $ougc_awards_menu, $ougc_awards_requests, $ougc_awards_welcomeblock, $ougc_awards_js, $ougc_awards_css;

    loadLanguage();

    $userID = (int)$mybb->user['uid'];

    $ougc_awards_js = eval(getTemplate('js'));

    $ougc_awards_css = eval(getTemplate('css'));

    $ougc_awards_menu = eval(getTemplate('global_menu'));

    $ougc_awards_requests = $ougc_awards_welcomeblock = '';

    if ($userID) {
        $ougc_awards_welcomeblock = eval(getTemplate('welcomeBlock'));
    }

    // TODO administratos should be able to manage requests from the ACP
    if (!$mybb->user['uid']) {
        return false;
    }

    $ismod = ($mybb->usergroup['canmodcp'] && getSetting('modcp') && is_member(getSetting('modgroups')));
    $isuser = ($mybb->usergroup['canusercp'] && $mybb->user['ougc_awards_owner']);

    if (!$ismod && !$isuser) {
        return false;
    }

    $_cache = awardsCacheGet();
    $pending = empty($_cache['requests']['pending']) ? 0 : (int)$_cache['requests']['pending'];

    $script = 'modcp.php';

    if (!$ismod && $isuser && $pending) {
        if ($aids = array_keys($_cache['awards'])) {
            $query = $db->simple_select(
                'ougc_awards_owners',
                'aid',
                "uid='1' AND aid IN ('" . implode("','", $aids) . "')"
            );

            $aids = [];

            while ($aids[] = (int)$db->fetch_field($query, 'aid')) {
            }

            if ($aids = array_filter($aids)) {
                $query = $db->simple_select(
                    'ougc_awards_requests',
                    'COUNT(rid) AS pending',
                    "status='1' AND aid IN ('" . implode("','", $aids) . "')"
                );
                $pending = (int)$db->fetch_field($query, 'pending');

                $script = 'usercp.php';
            }
        }
    }

    if ($pending < 1 || true) {
        return false;
    }

    $message = $lang->sprintf($lang->ougc_awards_page_pending_requests_moderator, $mybb->settings['bburl'], $script);
    if ($pending > 1) {
        $message = $lang->sprintf(
            $lang->ougc_awards_page_pending_requests_moderator_plural,
            $mybb->settings['bburl'],
            $script,
            my_number_format($pending)
        );
    }

    $ougc_awards_requests = eval(getTemplate('global_notification'));

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

        $locationArguments['location_name'] = $lang->sprintf($lang->ougc_awards_wol, $mybb->settings['bburl']);
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

        if ($presetID) {
            if (!($currentPresetData = presetGet(["pid='{$presetID}'", "uid='{$currentUserID}'"], '*', ['limit' => 1]
            ))) {
                error_no_permission();
            }
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
    global $mybb, $memprofile, $templates, $parser;
    global $db, $lang, $theme, $templates, $awards, $bg_color;

    $memprofile['ougc_awards'] = $memprofile['ougc_awards_preset'] = '';

    $queryLimit = (int)getSetting('profile');

    if ($queryLimit < 1 && $queryLimit !== -1) {
        $queryLimit = 0;
    }

    $userID = (int)$memprofile['uid'];

    $userData = get_user($userID);

    $queryLimitPresets = 0;

    if (is_member(getSetting('allowedGroupsPresets'), $memprofile)) {
        $queryLimitPresets = (int)getSetting('presets_profile');

        if ($queryLimitPresets < 1 && $queryLimitPresets !== -1) {
            $queryLimitPresets = 0;
        }
    }

    if ($queryLimitPresets) {
        $presetID = (int)$userData['ougc_awards_preset'];

        $presetData = presetGet(["pid='{$presetID}'"]);

        if (empty($presetData['visible']) || (int)$presetData['uid'] !== $userID) {
            $queryLimitPresets = 0;
        }
    }

    if (!$queryLimit && !$queryLimitPresets) {
        return false;
    }

    loadLanguage();

    urlHandlerSet(get_profile_link($userID));

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
        "uid='{$userID}'",
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

    if ($queryLimit && $totalGrantedCount && $queryLimit !== -1) {
        $currentPage = $mybb->get_input('view') == 'awards' ? $mybb->get_input('page', MyBB::INPUT_INT) : 1;

        if ($currentPage > 0) {
            $startPage = ($currentPage - 1) * $queryLimit;

            if ($currentPage > ceil($totalGrantedCount / $queryLimit)) {
                $startPage = 0;

                $currentPage = 1;
            }
        }

        $paginationMenu = (string)multipage(
            $totalGrantedCount,
            $queryLimit,
            $currentPage,
            "javascript: ougcAwards.ViewAwards('{$userID}', '{page}');"
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

    if ($queryLimit !== -1) {
        $queryOptions['limit'] = $queryLimit;

        $queryOptions['limit_start'] = $startPage;
    }

    $grantCacheData = awardGetUser(
        $whereClauses,
        '*',
        $queryOptions
    );

    //$_awards = [];

    $grantedList = $presetList = '';

    if (!$totalGrantedCount) {
        if ($queryLimit) {
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

        if ($queryLimit) {
            parseUserAwards($grantedList, $grantCacheData);
        }

        $presetAwards = array_filter(
            (array)my_unserialize($presetData['visible'])
        );

        if ($queryLimitPresets) {
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

    if ($queryLimit) {
        $lang->ougcAwardsProfileTitle = $lang->sprintf(
            $lang->ougcAwardsProfileTitle,
            htmlspecialchars_uni($userData['username'])
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

    return true;
}

function modcp_start10(): bool
{
    global $mybb;

    $pageUrl = urlHandlerBuild();

    $userID = (int)$mybb->user['uid'];

    return false;
}

function usercp_menu_built(): bool
{
    //_dump(123);
    global $mybb;

    if (!is_member(getSetting('groups'))) {
        return false;
    }

    global $lang, $templates, $usercpnav;

    loadLanguage();

    $pageUrl = urlHandlerBuild(['action' => getSetting('pageAction')]);

    $navigationItem = eval(getTemplate('menu'));

    $usercpnav = str_replace('<!--OUGC_AWARDS-->', $navigationItem, $usercpnav);

    return true;
}

function usercp_start(): bool
{
    return modcp_start();
}

function modcp_start(): bool
{
    global $mybb, $modcp_nav, $templates, $lang, $plugins, $usercpnav, $headerinclude, $header, $theme, $footer, $db, $gobutton;
    loadLanguage();

    $currentUserID = (int)$mybb->user['uid'];

    $isModerationPanel = $plugins->current_hook === 'modcp_start';

    if ($isModerationPanel) {
        urlHandlerSet('modcp.php');

        urlHandlerSet(urlHandlerBuild(['action' => getSetting('pageAction')]));

        $formUrl = urlHandlerBuild();

        $permission = (getSetting('modcp') && (is_member(getSetting('modgroups'))));

        if ($permission) {
            loadLanguage();

            $awards_nav = eval(getTemplate('modcp_nav'));
            $modcp_nav = str_replace('<!--OUGC_AWARDS-->', $awards_nav, $modcp_nav);
        }

        if (!$mybb->get_input('action')) {
            //$mybb->input['action'] = '';
        }
    } else {
        urlHandlerSet('usercp.php');

        urlHandlerSet(urlHandlerBuild(['action' => getSetting('pageAction')]));

        $formUrl = urlHandlerBuild();

        $awards_nav = eval(getTemplate('controlPanelNavigation'));
        $usercpnav = str_replace('<!--OUGC_AWARDS-->', $awards_nav, $usercpnav);
        $modcp_nav = &$usercpnav;

        if (!$mybb->get_input('action')) {
            //$mybb->input['action'] = 'sort';
        }
    }

    if ($mybb->get_input('action') !== getSetting('pageAction')) {
        return false;
    }

    if ($isModerationPanel) {
        $permission || error_no_permission();

        add_breadcrumb($lang->nav_modcp, 'modcp.php');
    } else {
        $query = $db->simple_select('ougc_awards_owners', 'aid', "uid='{$currentUserID}'");
        while ($owner_aids[] = (int)$db->fetch_field($query, 'aid')) {
        }
        $owner_aids = array_filter($owner_aids);

        add_breadcrumb($lang->nav_usercp, 'usercp.php');
    }

    add_breadcrumb($lang->ougc_awards_usercp_nav, urlHandlerBuild());

    if (!$isModerationPanel && $mybb->get_input('action') != 'sort') {
        if ($mybb->get_input('action') == 'viewPresets') {
            add_breadcrumb($lang->ougc_awards_presets_title, urlHandlerBuild(['action' => 'viewPresets']));
        } elseif ($mybb->get_input('action') != 'sort') {
            add_breadcrumb($lang->ougc_awards_modcp_nav, urlHandlerBuild(['action' => 'default']));
        }
    }

    $error = [];
    $button = $errors = $paginationMenu = $content = '';

    $_cache = awardsCacheGet();

    $where_cids = $where_aids = [];
    foreach ($_cache['categories'] as $cid => $category) {
        $where_cids[] = (int)$cid;
    }

    $where_cids = implode("','", $where_cids);

    foreach ($_cache['awards'] as $aid => $award) {
        if (isset($_cache['categories'][$award['cid']])) {
            $where_aids[] = (int)$aid;
        }
    }

    $where_aids = implode("','", $isModerationPanel ? $where_aids : $owner_aids);

    if ($mybb->get_input('page', MyBB::INPUT_INT) > 0) {
        $startPage = ($mybb->get_input('page', MyBB::INPUT_INT) - 1) * getSetting('perpage');
    } else {
        $startPage = 0;
        $mybb->input['page'] = 1;
    }

    $_awards = [];

    if (!$isModerationPanel && $mybb->get_input('action') == 'sort') {
        $categories = $cids = [];
        $awardlist = '';

        $catscount = count($_cache['categories']);

        if ($mybb->request_method === 'post') {
            $updates = [];

            $disporder = $mybb->get_input('disporder', MyBB::INPUT_ARRAY);
            foreach ($disporder as $key => $value) {
                $updates[(int)$key] = ['disporder' => (int)$value, 'visible' => 0];
            }

            $visible = $mybb->get_input('visible', MyBB::INPUT_ARRAY);
            foreach ($visible as $key => $value) {
                $updates[(int)$key]['visible'] = 1;
            }

            if (!empty($updates)) {
                foreach ($updates as $gid => $data) {
                    grantUpdate($data, $gid);
                }
            }
        }

        $query = $db->simple_select(
            'ougc_awards_categories',
            '*',
            "visible='1'",
            ['limit_start' => $startPage, 'limit' => (int)getSetting('perpage'), 'order_by' => 'disporder']
        );
        if ($db->num_rows($query)) {
            while ($category = $db->fetch_array($query)) {
                $cids[] = (int)$category['cid'];
                $categories[(int)$category['cid']] = $category;
            }

            $paginationMenu = (string)multipage(
                $catscount,
                getSetting('perpage'),
                $mybb->get_input('page', MyBB::INPUT_INT),
                urlHandlerBuild()
            );
        }

        // Query our data.
        $query = $db->query(
            '
			SELECT u.*, u.disporder as user_disporder, u.visible as user_visible, a.*, ou.uid as ouid, ou.username as ousername, ou.usergroup as ousergroup, ou.displaygroup as odisplaygroup
			FROM ' . $db->table_prefix . 'ougc_awards_users u
			LEFT JOIN ' . $db->table_prefix . 'ougc_awards a ON (u.aid=a.aid)
			LEFT JOIN ' . $db->table_prefix . "users ou ON (u.oid=ou.uid)
			WHERE u.uid='" . (int)$currentUserID . "' AND a.visible='1' AND a.cid IN ('" . implode(
                "','",
                array_values($cids)
            ) . "')
			ORDER BY u.disporder, u.date desc"
        );

        // Output our awards.
        if ($db->num_rows($query)) {
            while ($award = $db->fetch_array($query)) {
                $_awards[(int)$award['cid']][] = $award;
            }
        }

        $awardlist = '';
        if (!empty($categories)) {
            foreach ($categories as $cid => $category) {
                if (empty($category['visible'])) {
                    continue;
                }

                $awardlist = '';

                $category['name'] = htmlspecialchars_uni($category['name']);
                $category['description'] = htmlspecialchars_uni($category['description']);

                $trow = alt_trow(1);

                if (empty($_awards[(int)$category['cid']])) {
                    $awardlist = eval(getTemplate('usercp_sort_empty'));
                } else {
                    $alternativeBackground = alt_trow(true);

                    foreach ($_awards[(int)$category['cid']] as $cid => $grantData) {
                        if (empty($grantData['visible'])) {
                            continue;
                        }

                        $awardID = (int)$grantData['aid'];

                        $grantID = (int)$grantData['gid'];

                        $requestID = (int)$grantData['rid'];

                        $taskID = (int)$grantData['tid'];

                        $awardName = htmlspecialchars_uni($grantData['name']);

                        $awardDescription = htmlspecialchars_uni($grantData['description']);

                        $userName = $userNameFormatted = $userProfileLink = '';

                        if (!empty($usersCache[$grantData['uid']])) {
                            $userData = $usersCache[$grantData['uid']];

                            $userName = htmlspecialchars_uni($userData['username']);

                            $userNameFormatted = format_name(
                                $userName,
                                $userData['usergroup'],
                                $userData['displaygroup']
                            );

                            $userProfileLink = build_profile_link($userNameFormatted, $userData['uid']);
                        }

                        $grantReason = $grantData['reason'];

                        parseMessage($grantReason);

                        $awardImage = $awardClass = awardGetIcon($awardID);

                        $awardImage = eval(
                        getTemplate(
                            $grantData['template'] === AWARD_TEMPLATE_TYPE_CLASS ? 'awardImageClass' : 'awardImage'
                        )
                        );

                        $awardUrl = urlHandlerBuild(['action' => 'viewUsers', 'awardID' => $awardID]);

                        $awardImage = eval(getTemplate('awardWrapper', false));

                        $displayOrder = (int)$grantData['user_disporder'];

                        $visibleStatus = (int)$grantData['user_visible'];

                        $checked = $grantData['visible'] ? ' checked="checked"' : '';

                        $grantDate = $lang->sprintf(
                            $lang->ougcAwardsDate,
                            my_date($mybb->settings['dateformat'], $grantData['date']),
                            my_date($mybb->settings['timeformat'], $grantData['date'])
                        );

                        $awardlist .= eval(getTemplate('usercp_sort_award'));

                        $alternativeBackground = alt_trow();
                    }
                }

                if ($awardlist) {
                    $pageContents .= eval(getTemplate('usercp_sort'));
                }
            }
        }

        $pageContents || $pageContents = eval(getTemplate('page_empty'));

        $formUrl = urlHandlerBuild(['action' => 'default']);
        $message = $lang->ougc_awards_modcp_nav;
        $button .= eval(getTemplate('modcp_list_button'));

        if (is_member(getSetting('allowedGroupsPresets'))) {
            $formUrl = urlHandlerBuild(['action' => 'viewPresets']);

            $message = $lang->ougc_awards_presets_button;

            $button .= eval(getTemplate('modcp_list_button'));
        }
    }

    if (!empty($errorMessages)) {
        $errorMessages = inline_error($errorMessages);
    } else {
        $errorMessages = '';
    }

    $pageContents = eval(getTemplate('controlPanel'));

    output_page($pageContents);

    exit;
}

function stats_end(): bool
{
    global $awards, $db, $templates, $lang, $ougc_awards_most, $ougc_awards_last, $theme, $mybb;

    $ougc_awards_most = $ougc_awards_last = $userlist = '';
    $place = 0;

    if (!getSetting('enablestatspage')) {
        return false;
    }

    loadLanguage();

    $stats = awardsCacheGet();

    if (empty($stats['top'])) {
        $userlist = eval(getTemplate('stats_empty'));
    } else {
        $_users = [];

        $query = $db->simple_select(
            'users',
            'uid, username, usergroup, displaygroup',
            "uid IN ('" . implode("','", array_keys($stats['top'])) . "')"
        );
        while ($user = $db->fetch_array($query)) {
            $_users[(int)$user['uid']] = $user;
        }

        $trow = alt_trow(true);
        foreach ($stats['top'] as $uid => $total) {
            ++$place;
            $username = htmlspecialchars_uni($_users[$uid]['username']);
            $username_formatted = format_name(
                $_users[$uid]['username'],
                $_users[$uid]['usergroup'],
                $_users[$uid]['displaygroup']
            );
            $profilelink = build_profile_link($_users[$uid]['username'], $uid);
            $profilelink_formatted = build_profile_link(
                format_name($_users[$uid]['username'], $_users[$uid]['usergroup'], $_users[$uid]['displaygroup']),
                $uid
            );

            $message = $total;
            $field = eval(getTemplate('stats_user_viewall'));

            $userlist .= eval(getTemplate('stats_user'));
            $trow = alt_trow();
        }
    }

    $title = $lang->ougc_awards_stats_most;

    $ougc_awards_most = eval(getTemplate('stats'));

    $userlist = '';
    $place = 0;

    if (empty($stats['last'])) {
        $userlist = eval(getTemplate('stats_empty'));
    } else {
        $_users = [];

        $query = $db->simple_select(
            'users',
            'uid, username, usergroup, displaygroup',
            "uid IN ('" . implode("','", array_values($stats['last'])) . "')"
        );
        while ($user = $db->fetch_array($query)) {
            $_users[(int)$user['uid']] = $user;
        }

        $trow = alt_trow(true);
        foreach ($stats['last'] as $date => $uid) {
            ++$place;
            $username = htmlspecialchars_uni($_users[$uid]['username']);
            $username_formatted = format_name(
                $_users[$uid]['username'],
                $_users[$uid]['usergroup'],
                $_users[$uid]['displaygroup']
            );
            $profilelink = build_profile_link($_users[$uid]['username'], $uid);
            $profilelink_formatted = build_profile_link(
                format_name($_users[$uid]['username'], $_users[$uid]['usergroup'], $_users[$uid]['displaygroup']),
                $uid
            );

            $field = my_date('relative', $date);

            $userlist .= eval(getTemplate('stats_user'));
            $trow = alt_trow();
        }
    }

    $title = $lang->ougc_awards_stats_last;

    $ougc_awards_last = eval(getTemplate('stats'));

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