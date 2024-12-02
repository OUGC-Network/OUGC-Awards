<?php

/***************************************************************************
 *
 *    OUGC Awards plugin (/inc/plugins/ougc/Awards/hooks/admin.php)
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

use function ougc\Awards\Core\awardGet;
use function ougc\Awards\Core\awardGetIcon;
use function ougc\Awards\Core\awardGetInfo;
use function ougc\Awards\Core\awardGetUser;
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

use const ougc\Awards\Core\GRANT_STATUS_POSTS;
use const ougc\Awards\Core\GRANT_STATUS_VISIBLE;
use const ougc\Awards\Core\INFORMATION_TYPE_DESCRIPTION;
use const ougc\Awards\Core\INFORMATION_TYPE_NAME;
use const ougc\Awards\Core\INFORMATION_TYPE_REASON;
use const ougc\Awards\Core\INFORMATION_TYPE_TEMPLATE;
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

    $awards = $cache->read('ougc_awards');
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

    $cacheData = $mybb->cache->read('ougc_awards');

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

    if ($mybb->settings['ougc_awards_welcomeblock']) {
        $ougc_awards_welcomeblock = eval(getTemplate('welcomeBlock'));
    }

    // TODO administratos should be able to manage requests from the ACP
    if (!$mybb->user['uid']) {
        return false;
    }

    $ismod = ($mybb->usergroup['canmodcp'] && $mybb->settings['ougc_awards_modcp'] && ($mybb->settings['ougc_awards_modgroups'] == -1 || is_member(
                $mybb->settings['ougc_awards_modgroups']
            )));
    $isuser = ($mybb->usergroup['canusercp'] && $mybb->user['ougc_awards_owner']);

    if (!$ismod && !$isuser) {
        return false;
    }

    $_cache = $mybb->cache->read('ougc_awards');
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

        if (!is_member($mybb->settings['ougc_awards_presets_groups'])) {
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
    global $plugins, $mybb, $templates, $awards, $lang, $db;

    $awards_cache = $mybb->cache->read('ougc_awards');

    $postData['ougc_awards'] = $postData['ougc_awards_preset'] = '';

    $postData['uid'] = (int)$postData['uid'];

    if (getSetting('presets_post') && is_member($mybb->settings['ougc_awards_presets_groups'], $postData)) {
        static $ougc_awards_presets_cache = null;

        static $ougc_awards_presets_awards_cache = null;

        if ($ougc_awards_presets_cache === null) {
            $ougc_awards_presets_cache = $ougc_awards_presets_awards_cache = $preset_ids = [];

            $select = " LEFT JOIN {$db->table_prefix}users u ON (u.uid=ag.uid)";

            $where = " ag.uid='{$postData['uid']}'";

            if (isset($GLOBALS['pids'])) {
                $pids = implode("','", array_filter(array_unique(array_map('intval', explode("'", $GLOBALS['pids'])))));

                $select .= " LEFT JOIN {$db->table_prefix}posts p ON (p.uid=ag.uid)";

                $where = "p.pid IN ('{$pids}')";
            }

            $query = $db->simple_select(
                "ougc_awards a LEFT JOIN {$db->table_prefix}ougc_awards_users ag ON (ag.aid=a.aid){$select}",
                'ag.*, u.ougc_awards_preset',
                "ag.visible='1' AND {$where}",
                [
                    'order_by' => 'ag.disporder, ag.date',
                    'order_dir' => 'desc'
                ]
            );

            $preset_ids = [];

            while ($data = $db->fetch_array($query)) {
                $preset_ids[(int)$data['ougc_awards_preset']] = (int)$data['ougc_awards_preset'];

                $ougc_awards_presets_awards_cache[$data['uid']][$data['gid']] = $data;
            }

            if ($preset_ids) {
                $preset_ids = implode("','", $preset_ids);

                $query = $db->simple_select(
                    'ougc_awards_presets',
                    '*',
                    "pid IN ('{$preset_ids}')"
                );

                while ($preset = $db->fetch_array($query)) {
                    $ougc_awards_presets_cache[$preset['uid']] = $preset;
                }
            }
        }

        if (isset($ougc_awards_presets_cache[$postData['uid']])) {
            $preset = $ougc_awards_presets_cache[$postData['uid']];

            if (!empty($preset['visible'])) {
                $preset['name'] = htmlspecialchars_uni($preset['name']);

                $visible_awards = array_filter((array)my_unserialize($preset['visible']));

                $conunt = 0;

                foreach ($visible_awards as $position => $gid) {
                    $grantID = (int)$award['gid'];

                    $requestID = (int)$award['rid'];

                    $taskID = (int)$award['tid'];

                    $award = $ougc_awards_presets_awards_cache[$postData['uid']][$gid];

                    if (empty($award['gid'])) {
                        continue;
                    }

                    ++$count;

                    $awardID = (int)$award['aid'];

                    if ($name = awardGetInfo(
                        INFORMATION_TYPE_NAME,
                        $awardID
                    )) {
                        $award['name'] = $name;
                    }
                    if ($description = awardGetInfo(
                        INFORMATION_TYPE_DESCRIPTION,
                        $awardID
                    )) {
                        $award['description'] = $description;
                    }

                    if (!($grantReason = awardGetInfo(
                        INFORMATION_TYPE_REASON,
                        $awardID,
                        $grantID,
                        $requestID,
                        $taskID
                    ))) {
                        if (!($grantReason = $award['reason'])) {
                            $grantReason = $lang->na;
                        }
                    }

                    $grantReason = $award['reason'] = htmlspecialchars_uni($grantReason);

                    $award['ousername'] = format_name(
                        htmlspecialchars_uni($award['ousername']),
                        $award['ousergroup'],
                        $award['odisplaygroup']
                    );

                    parseMessage($award['reason']);

                    $award['image'] = awardGetIcon($awardID);

                    $award['disporder'] = (int)$award['user_disporder'];

                    $award['visible'] = (int)$award['user_visible'];

                    $award['date'] = $lang->sprintf(
                        $lang->ougcAwardsDate,
                        my_date($mybb->settings['dateformat'], $award['date']),
                        my_date($mybb->settings['timeformat'], $award['date'])
                    );

                    $award['fimage'] = eval(
                    getTemplate(
                        awardGetInfo(INFORMATION_TYPE_TEMPLATE, $awardID)
                    )
                    );

                    $visibleawards .= eval(getTemplate('postbit_preset_award'));

                    if (getSetting('presets_post') > 0 && $count == getSetting('presets_post')) {
                        break;
                    }
                }

                if ($visibleawards) {
                    $postData['ougc_awards_preset'] = eval(getTemplate('postbit_preset'));
                }
            }
        }
    }

    $max_per_line = (int)$mybb->settings['ougc_awards_postbit_maxperline'];

    if (getSetting('postbit') < 1 && getSetting('postbit') != -1) {
        return $postData;
    }

    static $ougc_awards_cache = null;

    if (!isset($ougc_awards_cache)) {
        global $db;
        $cids = [];

        foreach ($awards_cache['categories'] as $cid => $category) {
            $cids[] = (int)$cid;
        }

        $whereclause = "AND a.visible='1' AND a.type!='1' AND a.cid IN ('" . implode("','", array_values($cids)) . "')";

        // First we need to get our data
        if (THIS_SCRIPT == 'showthread.php' && isset($GLOBALS['pids'])) {
            $ougc_awards_cache = [];

            $pids = array_filter(array_unique(array_map('intval', explode('\'', $GLOBALS['pids']))));
            $query = $db->query(
                '
				SELECT ag.*
				FROM ' . $db->table_prefix . 'ougc_awards a
				JOIN ' . $db->table_prefix . 'ougc_awards_users ag ON (ag.aid=a.aid)
				JOIN ' . $db->table_prefix . 'posts p ON (p.uid=ag.uid)
				WHERE ag.visible=1 AND p.pid IN (\'' . implode('\',\'', $pids) . '\') ' . $whereclause . '
				ORDER BY ag.disporder, ag.date desc'
            );
            // how to limit by uid here?
            // -- '.(getSetting('postbit') == -1 ? '' : 'LIMIT '.getSetting('postbit'))

            while ($data = $db->fetch_array($query)) {
                $ougc_awards_cache[$data['uid']][$data['gid']] = $data;
            }
        } else {
            global $db;
            $ougc_awards_cache = [];

            $query = $db->query(
                '
				SELECT ag.*
				FROM ' . $db->table_prefix . 'ougc_awards a
				JOIN ' . $db->table_prefix . 'ougc_awards_users ag ON (ag.aid=a.aid)
				WHERE ag.visible=1 AND ag.uid=\'' . (int)$postData['uid'] . '\' ' . $whereclause . '
				ORDER BY ag.disporder, ag.date desc
				' . (getSetting('postbit') == -1 ? '' : 'LIMIT ' . getSetting('postbit'))
            );

            while ($data = $db->fetch_array($query)) {
                $ougc_awards_cache[$data['uid']][$data['gid']] = $data;
            }
        }
    }

    // User has no awards
    if (empty($ougc_awards_cache[$postData['uid']])) {
        return $postData;
    }

    $awardlist = &$ougc_awards_cache[$postData['uid']];

    loadLanguage();

    $count = $countbr = 0;

    $viewall = '';

    $total = count($awardlist);

    foreach ($awardlist as $awardData) {
        $grantID = (int)$awardData['gid'];

        $requestID = (int)$awardData['rid'];

        $taskID = (int)$awardData['tid'];

        $awardID = (int)$awardData['aid'];

        $awardData = array_merge(awardGet($awardID), $awardData);

        if ($name = awardGetInfo(
            INFORMATION_TYPE_NAME,
            $awardID
        )) {
            $awardData['name'] = $name;
        }

        $awardData['name_ori'] = $awardData['name'];

        $awardData['name'] = strip_tags($awardData['name_ori']);

        $awardData['image'] = awardGetIcon($awardID);

        if (getSetting('postbit') == -1 || $count < getSetting('postbit')) {
            ++$count;
            $br = '';

            if ($max_per_line === 1 || $count === 1 || $countbr === $max_per_line) {
                $countbr = 0;
                $br = '<br class="ougc_awards_postbit_maxperline" />';
            }

            if (getSetting('postbit') != -1 && $count == getSetting('postbit') && $total != $count) {
                $uid = $postData['uid'];
                $message = $lang->ougcAwardsStatsViewAll;
                $viewall = eval(getTemplate('stats_user_viewall'));
            }

            if (!($grantReason = awardGetInfo(
                INFORMATION_TYPE_REASON,
                $awardID,
                $grantID,
                $requestID,
                $taskID
            ))) {
                if (!($grantReason = $awardData['reason'])) {
                    $grantReason = $lang->na;
                }
            }

            $grantReason = $awardData['reason'] = htmlspecialchars_uni($grantReason);

            parseMessage($awardData['reason']);

            $awardImage = $imageClass = awardGetIcon($awardID);

            $awardName = htmlspecialchars_uni($awardData['name']);

            $awardImage = eval(getTemplate(awardGetInfo(INFORMATION_TYPE_TEMPLATE, $awardID), false));

            $new_award = eval(getTemplate('postbit', true, false));

            $postData['ougc_awards'] .= trim($new_award);

            ++$countbr;
        }
    }

    $postData['user_details'] = str_replace('<!--OUGC_AWARDS-->', $postData['ougc_awards'], $postData['user_details']);

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

    if (is_member(getSetting('presets_groups'), $memprofile)) {
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

        $permission = ($mybb->settings['ougc_awards_modcp'] && ($mybb->settings['ougc_awards_modgroups'] == -1 || ($mybb->settings['ougc_awards_modgroups'] && is_member(
                        $mybb->settings['ougc_awards_modgroups']
                    ))));

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

    $_cache = $mybb->cache->read('ougc_awards');

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

                        if (!($awardName = awardGetInfo(INFORMATION_TYPE_NAME, $awardID))) {
                            $awardName = $grantData['name'];
                        }

                        if (!($awardDescription = awardGetInfo(INFORMATION_TYPE_DESCRIPTION, $awardID))) {
                            $awardDescription = $grantData['description'];
                        }

                        if (!($grantReason = awardGetInfo(
                            INFORMATION_TYPE_REASON,
                            $awardID,
                            $grantID,
                            $requestID,
                            $taskID
                        ))) {
                            if (!($grantReason = $grantData['reason'])) {
                                $grantReason = $lang->na;
                            }
                        }

                        $grantReason = $grantData['reason'] = htmlspecialchars_uni($grantReason);

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

                        parseMessage($grantData['reason']);

                        $awardImage = $imageClass = awardGetIcon($awardID);

                        $displayOrder = (int)$grantData['user_disporder'];

                        $visibleStatus = (int)$grantData['user_visible'];

                        $checked = $grantData['visible'] ? ' checked="checked"' : '';

                        $grantDate = $lang->sprintf(
                            $lang->ougcAwardsDate,
                            my_date($mybb->settings['dateformat'], $grantData['date']),
                            my_date($mybb->settings['timeformat'], $grantData['date'])
                        );

                        $awardImage = eval(getTemplate(awardGetInfo(INFORMATION_TYPE_TEMPLATE, $awardID)));

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

        if (is_member($mybb->settings['ougc_awards_presets_groups'])) {
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

    if (!$mybb->settings['ougc_awards_enablestatspage']) {
        return false;
    }

    loadLanguage();

    $stats = $mybb->cache->read('ougc_awards');

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

function myalerts_register_client_alert_formatters(MybbStuff_MyAlerts_AlertFormatterManager &$hookArguments
): MybbStuff_MyAlerts_AlertFormatterManager {
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

    return $hookArguments;
}