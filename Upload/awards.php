<?php

/***************************************************************************
 *
 *    OUGC Awards plugin (/awards.php)
 *    Author: Omar Gonzalez
 *    Copyright: © 2012-2020 Omar Gonzalez
 *
 *    Website: https://ougc.network
 *
 *    Adds a powerful awards system to you community.
 *
 ***************************************************************************
 ****************************************************************************
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 ****************************************************************************/

use function ougc\Awards\Core\awardGet;

use function ougc\Awards\Core\awardGetIcon;

use function ougc\Awards\Core\awardGetInfo;

use function ougc\Awards\Core\awardGetUser;

use function ougc\Awards\Core\awardsGetCache;

use function ougc\Awards\Core\cacheUpdate;

use function ougc\Awards\Core\canRequestAwards;

use function ougc\Awards\Core\canViewMainPage;

use function ougc\Awards\Core\categoryGet;

use function ougc\Awards\Core\categoryGetCache;

use function ougc\Awards\Core\parseUserAwards;

use function ougc\Awards\Core\getUser;

use function ougc\Awards\Core\logAction;

use function ougc\Awards\Core\parseMessage;

use function ougc\Awards\Core\pluginIsInstalled;

use function ougc\Awards\Core\requestGetPendingTotal;

use function ougc\Awards\Core\requestInsert;

use function ougc\Awards\Core\urlHandlerBuild;

use function ougc\Awards\Core\loadLanguage;

use function ougc\Awards\Core\urlHandlerSet;

use function ougc\Awards\Core\getTemplate;

use function ougc\Awards\Core\getSetting;

use const ougc\Awards\Core\GRANT_STATUS_POSTS;

use const ougc\Awards\Core\GRANT_STATUS_VISIBLE;

use const ougc\Awards\Core\INFORMATION_TYPE_DESCRIPTION;

use const ougc\Awards\Core\INFORMATION_TYPE_NAME;

use const ougc\Awards\Core\INFORMATION_TYPE_REASON;

use const ougc\Awards\Core\INFORMATION_TYPE_TEMPLATE;

use const ougc\Awards\Core\REQUEST_STATUS_PENDING;

const IN_MYBB = true;

define('THIS_SCRIPT', substr($_SERVER['SCRIPT_NAME'], -strpos(strrev($_SERVER['SCRIPT_NAME']), '/')));

$templatelist = 'ougcawards_page_list_award, ougcawards_page_list_award_request, ougcawards_page_list_request, ougcawards_page_list, ougcawards_page, ougcawards_page_list_empty, ougcawards_page_view_row, ougcawards_page_view, ougcawards_page_view_empty,ougcawards_page_empty,ougcawards_page_view_request,';

require_once './global.php';

require_once MYBB_ROOT . 'inc/class_parser.php';

global $parser, $lang, $mybb, $plugins, $db, $templates;

$awardID = $mybb->get_input('aid', MyBB::INPUT_INT);

$userID = (int)$mybb->user['uid'];

if (!pluginIsInstalled() || !canViewMainPage()) {
    error_no_permission();
}

is_object($parser) || $parser = new postParser();

loadLanguage();

urlHandlerSet(THIS_SCRIPT);

$plugins->run_hooks('ougcAwards_PageStart');

add_breadcrumb($lang->ougcAwards, urlHandlerBuild());

$paginationMenu = '';

if ($mybb->get_input('viewAward', MyBB::INPUT_INT)) {
    $awardID = $mybb->get_input('viewAward', MyBB::INPUT_INT);

    if (!($awardData = awardGet($awardID)) || empty($awardData['visible'])) {
        error($lang->ougcAwardsErrorInvalidAward);
    }

    if (!($categoryData = categoryGet($awardData['cid'])) || empty($categoryData['visible'])) {
        error($lang->ougcAwardsErrorInvalidCategory);
    }

    $pendingRequestTotal = requestGetPendingTotal(
        ["aid='{$awardID}'", "uid='{$userID}'"]
    );

    $pendingRequests = '';

    if ($pendingRequestTotal) {
        $messageContent = $lang->sprintf(
            $lang->ougcAwardsPendingRequests,
            my_number_format($pendingRequestTotal)
        );

        $pendingRequests = eval(getTemplate('globalNotification'));
    }

    $plugins->run_hooks('ougcAwards_ViewStart');

    if (!($awardName = awardGetInfo(INFORMATION_TYPE_NAME, $awardID))) {
        $awardName = $awardData['name'];
    }

    add_breadcrumb($categoryData['name']);

    add_breadcrumb($awardName);

    $totalGrantedCount = awardGetUser(["aid='{$awardID}'"],
        'COUNT(gid) AS totalGranted',
        ['limit' => 1]);

    if (empty($totalGrantedCount['totalGranted'])) {
        $totalGrantedCount = 0;
    } else {
        $totalGrantedCount = (int)$totalGrantedCount['totalGranted'];
    }

    if ($mybb->get_input('page', MyBB::INPUT_INT) > 0) {
        $startPage = ($mybb->get_input('page', MyBB::INPUT_INT) - 1) * (int)getSetting('perpage');

        $totalPages = ceil($totalGrantedCount / (int)getSetting('perpage'));

        if ($mybb->get_input('page', MyBB::INPUT_INT) > $totalPages) {
            $startPage = 0;

            $mybb->input['page'] = 1;
        }
    } else {
        $startPage = 0;

        $mybb->input['page'] = 1;
    }

    $userIDs = $threadIDs = $threadsCache = $usersCache = [];

    $grantCacheData = awardGetUser(["aid='{$awardID}'"],
        '*',
        [
            'limit' => (int)getSetting('perpage'),
            'limit_start' => $startPage,
            'order_by' => 'date',
            'order_dir' => 'desc'
        ]
    );

    foreach ($grantCacheData as $grantData) {
        if (!empty($grantData['uid'])) {
            $userIDs[] = (int)$grantData['uid'];
        }

        if (!empty($grantData['thread'])) {
            $threadIDs[] = (int)$grantData['thread'];
        }
    }

    $paginationMenu = (string)multipage(
        $totalGrantedCount,
        (int)getSetting('perpage'),
        $mybb->get_input('page', MyBB::INPUT_INT),
        urlHandlerBuild(['viewAward' => $awardID])
    );

    if ($paginationMenu) {
        $threadLink = eval(getTemplate('globalPagination'));
    }

    if ($userIDs) {
        $userIDs = implode("','", $userIDs);

        $dbQuery = $db->simple_select(
            'users',
            'uid, username, usergroup, displaygroup',
            "uid IN ('{$userIDs}')"
        );

        while ($userData = $db->fetch_array($dbQuery)) {
            $usersCache[(int)$userData['uid']] = $userData;
        }
    }

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

    $grantedList = '';

    $alternativeBackground = alt_trow(true);

    foreach ($grantCacheData as $grantData) {
        $grantID = (int)$grantData['gid'];

        $requestID = (int)$grantData['rid'];

        $taskID = (int)$grantData['tid'];

        if (!($grantReason = awardGetInfo(
            INFORMATION_TYPE_REASON,
            $awardID,
            $grantID,
            $requestID,
            $taskID
        ))) {
            if (!($grantReason = $grantData['reason'])) {
                $grantReason = $lang->ougcAwardsNoReason;
            }
        }

        $grantReason = $grantData['reason'] = htmlspecialchars_uni($grantReason);

        $userName = $userNameFormatted = $userProfileLink = '';

        if (!empty($usersCache[$grantData['uid']])) {
            $userData = $usersCache[$grantData['uid']];

            $userName = htmlspecialchars_uni($userData['username']);

            $userNameFormatted = format_name($userName, $userData['usergroup'], $userData['displaygroup']);

            $userProfileLink = build_profile_link($userNameFormatted, $userData['uid']);
        }

        $threadLink = '';

        if (!empty($threadsCache[$grantData['thread']])) {
            $threadData = $threadsCache[$grantData['thread']];

            $threadData['threadPrefix'] = $threadData['threadPrefixDisplay'] = '';

            if ($threadData['prefix']) {
                $prefixData = build_prefixes($threadData['prefix']);

                if (!empty($prefixData['prefix'])) {
                    $threadData['threadPrefix'] = $prefixData['prefix'] . '&nbsp;';

                    $threadData['threadPrefixDisplay'] = $prefixData['displaystyle'] . '&nbsp;';
                }
            }

            $threadSubject = htmlspecialchars_uni(
                $parser->parse_badwords($threadData['subject'])
            );

            $threadLink = get_thread_link($threadData['tid']);

            $threadLink = eval(getTemplate('pageViewRowLink'));
        }

        $grantDate = $lang->sprintf(
            $lang->ougcAwardsDate,
            my_date($mybb->settings['dateformat'], $grantData['date']),
            my_date($mybb->settings['timeformat'], $grantData['date'])
        );

        $grantedList .= eval(getTemplate('pageViewRow'));

        $alternativeBackground = alt_trow();
    }

    if (!$grantedList) {
        $grantedList = eval(getTemplate('pageViewEmpty'));
    }

    $requestButton = '';

    if (!empty($categoryData['allowrequests']) && !empty($awardData['allowrequests'])) {
        $requestButton = eval(getTemplate('pageViewButtonRequest'));
    }

    $plugins->run_hooks('ougcAwards_ViewEnd');

    $pageContents = eval(getTemplate('pageView'));
} elseif ($mybb->get_input('action') == 'viewUser') {
    $userID = $mybb->get_input('userID', MyBB::INPUT_INT);

    if (!($userData = getUser($userID))) {
        $errorMessage = $lang->ougcAwardsErrorInvalidUser;
    }

    $modalTitle = $lang->ougcAwardsViewUser;

    if (!empty($errorMessage)) {
        $grantedList = eval(getTemplate('viewUserError'));
    } else {
        $modalTitle = $lang->sprintf(
            $lang->ougcAwardsViewUserTitle,
            htmlspecialchars_uni($userData['username'])
        );

        $queryLimit = (int)getSetting('perpage');

        if ($queryLimit < 1 && $queryLimit !== -1) {
            $queryLimit = 0;
        }

        $categoryIDs = $awardIDs = [];

        $categoriesCache = categoryGetCache();

        foreach ($categoriesCache as $categoryID => $categoryData) {
            if (!empty($categoryData['visible'])) {
                $categoryIDs[$categoryID] = $categoryID;
            }
        }

        $awardsCache = awardsGetCache();

        foreach ($awardsCache as $awardID => $awardData) {
            if (!empty($awardData['visible']) && !empty($categoryIDs[$awardData['cid']]) && (int)$awardData['type'] !== GRANT_STATUS_POSTS) {
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

        $totalGrantedCount = awardGetUser($whereClauses, 'COUNT(gid) AS totalGranted', ['limit' => 1]
        );

        if (empty($totalGrantedCount['totalGranted'])) {
            $totalGrantedCount = 0;
        } else {
            $totalGrantedCount = (int)$totalGrantedCount['totalGranted'];
        }

        $startPage = 0;

        $currentPage = 1;

        if ($queryLimit && $totalGrantedCount && $queryLimit !== -1) {
            $currentPage = $mybb->get_input('page', MyBB::INPUT_INT);

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
                "javascript: OUGC_Plugins.ViewAll('{$userID}', '{page}');"
            //urlHandlerBuild(['view' => 'awards'])
            );

            if ($paginationMenu) {
                $paginationMenu = eval(getTemplate('globalPagination'));
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

        $grantedList = $presetList = '';

        if (!$totalGrantedCount) {
            if ($queryLimit) {
                $grantedList = eval(getTemplate('viewUserEmpty'));
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
                parseUserAwards($grantedList, $grantCacheData, 'viewUserRow');
            }
        }
    }

    $paginationMenu || $paginationMenu = '&nbsp;';

    $pageContents = eval(getTemplate('viewUser', false));

    echo $pageContents;

    exit;
} elseif ($mybb->get_input('action') == 'request') {
    $awardID = $mybb->get_input('awardID', MyBB::INPUT_INT);

    $errorMessage = false;

    if (!($awardData = awardGet($awardID)) || empty($awardData['visible'])) {
        $errorMessage = $lang->ougcAwardsErrorInvalidAward;
    }

    if (!canRequestAwards($awardID)) {
        $errorMessage = $lang->ougcAwardsErrorNoPermission;
    }

    $categoryID = (int)$awardData['cid'];

    if (!($categoryData = categoryGet($categoryID)) || empty($categoryData['visible'])) {
        $errorMessage = $lang->ougcAwardsErrorInvalidCategory;
    }

    $statusPending = REQUEST_STATUS_PENDING;

    $pendingRequestTotal = requestGetPendingTotal(
        ["aid='{$awardID}'", "uid='{$userID}'", "status='{$statusPending}'"]
    );

    if ($pendingRequestTotal) {
        $errorMessage = $lang->ougcAwardsErrorPendingRequest;
    }

    $buttonContent = '';

    if ($errorMessage !== false) {
        $disabledElement = 'disabled="disabled"';

        $buttonContent = eval(getTemplate('pageRequestButton'));

        $formContents = eval(getTemplate('pageRequestError'));
    } elseif ($mybb->request_method === 'post') {
        requestInsert([
            'uid' => $userID,
            'aid' => $awardID,
            'message' => $mybb->get_input('message')
        ]);

        logAction();

        cacheUpdate();

        if (!empty($lang->settings['charset'])) {
            $charset = $lang->settings['charset'];
        } else {
            $charset = 'UTF-8';
        }

        header("Content-type: application/json; charset={$charset}");

        $formContents = eval(getTemplate('pageRequestSuccess'));

        $modalContents = eval(getTemplate('pageRequest', false));

        echo json_encode(['modal' => $modalContents]);

        exit;
    } else {
        $awardName = htmlspecialchars_uni($awardData['name']);

        $awardDescription = htmlspecialchars_uni($awardData['description']);

        $awardImage = $imageClass = awardGetIcon($awardData['aid']);

        $disabledElement = '';

        $buttonContent = eval(getTemplate('pageRequestButton'));

        $awardImage = eval(getTemplate(awardGetInfo(INFORMATION_TYPE_TEMPLATE, $awardData['aid'])));

        $formContents = eval(getTemplate('pageRequestForm'));
    }

    $modalContents = eval(getTemplate('pageRequest', false));

    echo $modalContents;

    exit;
} else {
    $pageContents = '';

    foreach (categoryGetCache() as $categoryID => $categoryData) {
        if (empty($categoryData['visible'])) {
            continue;
        }

        $requestColumn = '';

        $colSpanCount = 3;

        if ($categoryData['allowrequests']) {
            $requestColumn = eval(getTemplate('pageListRequest'));

            ++$colSpanCount;
        }

        $awardsList = '';

        $alternativeBackground = alt_trow(true);

        $categoryName = $categoryData['name'];

        foreach (awardsGetCache(["cid='{$categoryID}'"]) as $awardID => $awardData) {
            if (empty($awardData['visible'])) {
                continue;
            }

            $requestColumnRow = '';

            $colSpanRowCount = 2;

            if ($categoryData['allowrequests'] && $awardData['allowrequests']) {
                $requestColumnRow = eval(getTemplate('pageListAwardRequest'));

                --$colSpanRowCount;
            }

            if (!($awardName = awardGetInfo(INFORMATION_TYPE_NAME, $awardID))) {
                $awardName = $awardData['name'];
            }

            $awardName = htmlspecialchars_uni($awardName);

            if (!($awardDescription = awardGetInfo(INFORMATION_TYPE_DESCRIPTION, $awardID))) {
                $awardDescription = $awardData['description'];
            }

            $awardDescription = htmlspecialchars_uni($awardDescription);

            $awardImage = $awardClass = awardGetIcon($awardID);

            $awardImage = eval(getTemplate(awardGetInfo(INFORMATION_TYPE_TEMPLATE, $awardID)));

            $awardsList .= eval(getTemplate('pageListAward'));

            $alternativeBackground = alt_trow();
        }

        if ($awardsList) {
            $pageContents .= eval(getTemplate('pageList'));
        }
    }

    if (!$pageContents) {
        $pageContents = eval(getTemplate('pageListEmpty'));
    }
}

$plugins->run_hooks('ougcAwards_PageEnd');

$pageContents = eval(getTemplate('page'));

output_page($pageContents);

exit;
