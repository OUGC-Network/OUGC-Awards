<?php

/***************************************************************************
 *
 *    OUGC Awards plugin (/awards.php)
 *    Author: Omar Gonzalez
 *    Copyright: © 2012-2020 Omar Gonzalez
 *
 *    Website: https://ougc.network
 *
 *    Manage a powerful awards system for your community.
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

use function ougc\Awards\Core\awardDelete;

use function ougc\Awards\Core\awardGet;

use function ougc\Awards\Core\awardGetIcon;

use function ougc\Awards\Core\awardGetInfo;

use function ougc\Awards\Core\awardGetUser;

use function ougc\Awards\Core\awardInsert;

use function ougc\Awards\Core\awardsGetCache;

use function ougc\Awards\Core\awardUpdate;

use function ougc\Awards\Core\cacheUpdate;

use function ougc\Awards\Core\canManageUsers;

use function ougc\Awards\Core\canRequestAwards;

use function ougc\Awards\Core\canViewMainPage;

use function ougc\Awards\Core\categoryGet;

use function ougc\Awards\Core\categoryGetCache;

use function ougc\Awards\Core\generateSelectCategory;

use function ougc\Awards\Core\getThreadByUrl;

use function ougc\Awards\Core\getUserByUserName;

use function ougc\Awards\Core\grantDelete;

use function ougc\Awards\Core\grantFind;

use function ougc\Awards\Core\grantGetSingle;

use function ougc\Awards\Core\grantInsert;

use function ougc\Awards\Core\grantUpdate;

use function ougc\Awards\Core\isModerator;

use function ougc\Awards\Core\isVisibleAward;

use function ougc\Awards\Core\isVisibleCategory;

use function ougc\Awards\Core\ownerDelete;

use function ougc\Awards\Core\ownerFind;

use function ougc\Awards\Core\ownerGetSingle;

use function ougc\Awards\Core\ownerGetUser;

use function ougc\Awards\Core\ownerInsert;

use function ougc\Awards\Core\parseUserAwards;

use function ougc\Awards\Core\getUser;

use function ougc\Awards\Core\logAction;

use function ougc\Awards\Core\parseMessage;

use function ougc\Awards\Core\pluginIsInstalled;

use function ougc\Awards\Core\presetDelete;

use function ougc\Awards\Core\presetGet;

use function ougc\Awards\Core\presetInsert;

use function ougc\Awards\Core\presetUpdate;

use function ougc\Awards\Core\requestApprove;

use function ougc\Awards\Core\requestGetPending;

use function ougc\Awards\Core\requestGetPendingTotal;

use function ougc\Awards\Core\requestInsert;

use function ougc\Awards\Core\requestReject;

use function ougc\Awards\Core\urlHandlerBuild;

use function ougc\Awards\Core\loadLanguage;

use function ougc\Awards\Core\urlHandlerSet;

use function ougc\Awards\Core\getTemplate;

use function ougc\Awards\Core\getSetting;

use const ougc\Awards\Core\AWARD_ALLOW_REQUESTS;

use const ougc\Awards\Core\AWARD_STATUS_DISABLED;

use const ougc\Awards\Core\AWARD_STATUS_ENABLED;

use const ougc\Awards\Core\AWARD_TEMPLATE_TYPE_CLASS;

use const ougc\Awards\Core\AWARD_TEMPLATE_TYPE_CUSTOM;

use const ougc\Awards\Core\GRANT_STATUS_POSTS;

use const ougc\Awards\Core\GRANT_STATUS_PROFILE;

use const ougc\Awards\Core\GRANT_STATUS_VISIBLE;

use const ougc\Awards\Core\INFORMATION_TYPE_DESCRIPTION;

use const ougc\Awards\Core\INFORMATION_TYPE_NAME;

use const ougc\Awards\Core\INFORMATION_TYPE_REASON;

use const ougc\Awards\Core\INFORMATION_TYPE_TEMPLATE;

use const ougc\Awards\Core\REQUEST_STATUS_ACCEPTED;

use const ougc\Awards\Core\REQUEST_STATUS_OPEN;

use const ougc\Awards\Core\REQUEST_STATUS_PENDING;

use const ougc\Awards\Core\REQUEST_STATUS_REJECTED;

const IN_MYBB = true;

define('THIS_SCRIPT', substr($_SERVER['SCRIPT_NAME'], -strpos(strrev($_SERVER['SCRIPT_NAME']), '/')));

$templatelist = '';

require_once './global.php';

require_once MYBB_ROOT . 'inc/class_parser.php';

global $parser, $lang, $mybb, $plugins, $db, $templates;

if (!pluginIsInstalled() || !canViewMainPage()) {
    error_no_permission();
}

is_object($parser) || $parser = new postParser();

loadLanguage();

urlHandlerSet(THIS_SCRIPT);

$awardID = $mybb->get_input('awardID', MyBB::INPUT_INT);

$categoryID = $mybb->get_input('categoryID', MyBB::INPUT_INT);

$ownerID = $mybb->get_input('ownerID', MyBB::INPUT_INT);

$grantID = $mybb->get_input('grantID', MyBB::INPUT_INT);

$presetID = $mybb->get_input('presetID', MyBB::INPUT_INT);

$taskID = $mybb->get_input('taskID', MyBB::INPUT_INT);

$currentUserID = (int)$mybb->user['uid'];

$isOwner = !empty($mybb->user['ougc_awards_owner']);

$plugins->run_hooks('ougcAwards_PageStart');

add_breadcrumb($lang->ougcAwardsPageNavigation, urlHandlerBuild());

$errorMessages = [];

$currentUserID = (int)$mybb->user['uid'];

switch ($mybb->get_input('action')) {
    case 'newCategory':
    case 'editCategory':
    case 'newAward':
    case 'editAward':
    case 'deleteAward':
    case 'viewUsers':
    case 'viewOwners':
    case 'deleteOwner':
    case 'viewRequests':
    case 'editGrant':
    case 'viewAward':
    case 'requestAward':
        if (isModerator() && $mybb->get_input('action') === 'deleteOwner') {
            if (!($ownerData = ownerGetSingle(["oid='{$ownerID}'"]))) {
                error($lang->ougcAwardsErrorInvalidOwner);
            }

            $awardID = (int)$ownerData['aid'];
        }

        if (isModerator() && $mybb->get_input('action') === 'editGrant') {
            if (!($grantData = grantGetSingle(["gid='{$grantID}'"]))) {
                error($lang->ougcAwardsErrorInvalidGrant);
            }

            $awardID = (int)$grantData['aid'];
        }

        if (!in_array($mybb->get_input('action'), ['newCategory', 'editCategory', 'newAward'], true)) {
            if (!($awardData = awardGet($awardID)) || !isVisibleAward($awardID)) {
                error($lang->ougcAwardsErrorInvalidAward);
            }

            $categoryID = (int)$awardData['cid'];
        }

        if (!in_array($mybb->get_input('action'), ['newCategory', 'newAward', 'editAward'], true)) {
            if (!($categoryData = categoryGet($categoryID)) || !isVisibleCategory($categoryID)) {
                error($lang->ougcAwardsErrorInvalidCategory);
            }
        }

        if (!in_array($mybb->get_input('action'), ['newCategory', 'editCategory'], true)) {
            $awardName = awardGetInfo(INFORMATION_TYPE_NAME, $awardID);
        }

        if (!empty($categoryData['name'])) {
            add_breadcrumb(
                $categoryData['name'],
                urlHandlerBuild(['action' => 'viewCategory', 'categoryID' => $categoryID])
            );
        }

        if (isModerator() && $mybb->get_input('action') === 'deleteOwner') {
            add_breadcrumb($awardName, urlHandlerBuild(['action' => 'deleteOwner', 'ownerID' => $ownerID]));
        } elseif (isModerator() && $mybb->get_input('action') === 'editGrant') {
            add_breadcrumb($awardName, urlHandlerBuild(['action' => 'editGrant', 'grantID' => $grantID]));
        } elseif (!empty($awardName)) {
            add_breadcrumb(
                $awardName,
                urlHandlerBuild(['action' => $mybb->get_input('action'), 'awardID' => $awardID])
            );
        }

        break;
}

switch ($mybb->get_input('action')) {
    case 'newAward':
        add_breadcrumb($lang->ougcAwardsControlPanelNewAwardTitle);
        break;
    case 'editAward':
        add_breadcrumb($lang->ougcAwardsControlPanelEditAwardTitle);
        break;
    case 'deleteAward':
        add_breadcrumb($lang->ougcAwardsControlPanelDeleteAwardTitle);
        break;
    case 'viewUsers':
        add_breadcrumb($lang->ougcAwardsControlPanelUsersTitle);
        break;
    case 'viewOwners':
        add_breadcrumb($lang->ougcAwardsControlPanelOwnersTitle);
        break;
    case 'deleteOwner':
        add_breadcrumb($lang->ougcAwardsControlPanelDeleteOwnersTitle);
        break;
    case 'viewRequests':
        add_breadcrumb($lang->ougcAwardsControlPanelRequests);
        break;
    case 'editGrant':
        add_breadcrumb($lang->ougcAwardsControlPanelEditGrantTitle);
        break;
    case 'viewPresets':
        add_breadcrumb($lang->ougcAwardsControlPanelPresetsTitle);
        break;
    case 'newCategory':
        add_breadcrumb($lang->ougcAwardsControlPanelNewCategoryTitle);
        break;
    case 'editCategory':
        add_breadcrumb($lang->ougcAwardsControlPanelEditCategoryTitle);
        break;
    case 'manageTasks':
        add_breadcrumb($lang->ougcAwardsControlPanelTasksTitle);
        break;
    case 'newTask':
        add_breadcrumb($lang->ougcAwardsControlPanelTasksTitle);

        add_breadcrumb($lang->ougcAwardsControlPanelNewTaskTitle);
        break;
    case 'editTask':
        add_breadcrumb($lang->ougcAwardsControlPanelTasksTitle);

        add_breadcrumb($lang->ougcAwardsControlPanelEditTaskTitle);
        break;
}

$pageUrl = urlHandlerBuild();

if (isModerator() && in_array($mybb->get_input('action'), ['newCategory', 'editCategory'], true)) {
    $newCategoryPage = $mybb->get_input('action') === 'newCategory';

    $inputData = [];

    foreach (['name', 'description', 'allowrequests', 'visible', 'disporder'] as $inputKey) {
        if (isset($mybb->input[$inputKey])) {
            $inputData[$inputKey] = $mybb->get_input($inputKey);
        } elseif (isset($categoryData[$inputKey])) {
            $inputData[$inputKey] = $categoryData[$inputKey];
        } else {
            $inputData[$inputKey] = '';
        }
    }

    if ($mybb->request_method === 'post') {
        verify_post_check($mybb->get_input('my_post_key'));

        if (my_strlen($inputData['name']) > 100) {
            $errorMessages[] = $lang->ougcAwardsErrorInvalidCategoryName;
        }

        if (my_strlen($inputData['description']) > 255) {
            $errorMessages[] = $lang->ougcAwardsErrorInvalidCategoryDescription;
        }

        if (empty($errorMessages)) {
            $categoryData = [
                'name' => $inputData['name'],
                'description' => $inputData['description'],
                'disporder' => $inputData['disporder'],
                'allowrequests' => $inputData['allowrequests'],
                'visible' => $inputData['visible'],
            ];

            if ($newCategoryPage) {
                \ougc\Awards\Core\categoryInsert($categoryData);
            } else {
                \ougc\Awards\Core\categoryUpdate($categoryData, $categoryID);
            }

            cacheUpdate();

            logAction();

            if ($newCategoryPage) {
                redirect(urlHandlerBuild(), $lang->ougcAwardsRedirectCategoryCreated);
            } else {
                redirect(urlHandlerBuild(), $lang->ougcAwardsRedirectCategoryUpdated);
            }
        }
    }

    foreach (['name', 'description', 'allowrequests', 'visible', 'disporder'] as $inputKey) {
        if (isset($mybb->input[$inputKey])) {
            $inputData[$inputKey] = htmlspecialchars_uni($mybb->get_input($inputKey));
        } elseif (isset($categoryData[$inputKey])) {
            $inputData[$inputKey] = htmlspecialchars_uni($categoryData[$inputKey]);
        } else {
            $inputData[$inputKey] = '';
        }
    }

    $selectedElementAllowRequestsYes = $selectedElementAllowRequestsNo = '';

    switch ($inputData['allowrequests']) {
        case AWARD_ALLOW_REQUESTS:
            $selectedElementAllowRequestsYes = 'checked="checked"';
            break;
        default:
            $selectedElementAllowRequestsNo = 'checked="checked"';
            break;
    }

    $selectedElementEnabledYes = $selectedElementEnabledNo = '';

    switch ($inputData['visible']) {
        case AWARD_STATUS_ENABLED:
            $selectedElementEnabledYes = 'checked="checked"';
            break;
        default:
            $selectedElementEnabledNo = 'checked="checked"';
            break;
    }

    $pageTitle = $lang->ougcAwardsControlPanelEditCategoryTitle;

    $tableTitle = $lang->ougcAwardsControlPanelEditCategoryTableTitle;

    $tableDescription = $lang->ougcAwardsControlPanelEditCategoryTableDescription;

    $buttonText = $lang->ougcAwardsControlPanelEditCategoryButton;

    if ($newCategoryPage) {
        $pageTitle = $lang->ougcAwardsControlPanelNewCategoryTitle;

        $tableTitle = $lang->ougcAwardsControlPanelNewCategoryTableTitle;

        $tableDescription = $lang->ougcAwardsControlPanelNewCategoryTableDescription;

        $buttonText = $lang->ougcAwardsControlPanelNewCategoryButton;
    }

    $pageContents = eval(getTemplate('controlPanelNewEditCategoryForm'));
} elseif (isModerator() && in_array($mybb->get_input('action'), ['newAward', 'editAward'], true)) {
    $newAwardPage = $mybb->get_input('action') === 'newAward';

    $categoryID = $mybb->get_input('cid', MyBB::INPUT_INT);

    $inputData = [];

    foreach (['cid', 'name', 'description', 'image', 'template', 'allowrequests', 'pm', 'type'] as $inputKey) {
        if (isset($mybb->input[$inputKey])) {
            $inputData[$inputKey] = $mybb->get_input($inputKey);
        } elseif (isset($awardData[$inputKey])) {
            $inputData[$inputKey] = $awardData[$inputKey];
        } else {
            $inputData[$inputKey] = '';
        }
    }

    if ($mybb->request_method === 'post') {
        verify_post_check($mybb->get_input('my_post_key'));

        if (my_strlen($inputData['name']) > 100) {
            $errorMessages[] = $lang->ougcAwardsErrorInvalidAwardName;
        }

        if (my_strlen($inputData['description']) > 255) {
            $errorMessages[] = $lang->ougcAwardsErrorInvalidAwardDescription;
        }

        if (my_strlen($inputData['image']) > 255) {
            $errorMessages[] = $lang->ougcAwardsErrorInvalidAwardImage;
        }

        if (!($categoryData = categoryGet($categoryID)) || !isVisibleCategory($categoryID)) {
            $errorMessages[] = $lang->ougcAwardsErrorInvalidCategory;
        }

        if (empty($errorMessages)) {
            $awardData = [
                'name' => $inputData['name'],
                'cid' => $inputData['cid'],
                'description' => $inputData['description'],
                'image' => $inputData['image'],
                'template' => $inputData['template'],
                'allowrequests' => $inputData['allowrequests'],
                'pm' => $inputData['pm'],
                'type' => $inputData['type'],
            ];

            if ($newAwardPage) {
                awardInsert($awardData);
            } else {
                awardUpdate($awardData, $awardID);
            }

            cacheUpdate();

            logAction();

            if ($newAwardPage) {
                redirect(urlHandlerBuild(), $lang->ougcAwardsRedirectAwardCreated);
            } else {
                redirect(urlHandlerBuild(), $lang->ougcAwardsRedirectAwardUpdated);
            }
        }
    }

    foreach (['cid', 'name', 'description', 'image', 'template', 'allowrequests', 'pm', 'type'] as $inputKey) {
        if (isset($mybb->input[$inputKey])) {
            $inputData[$inputKey] = htmlspecialchars_uni($mybb->get_input($inputKey));
        } elseif (isset($awardData[$inputKey])) {
            $inputData[$inputKey] = htmlspecialchars_uni($awardData[$inputKey]);
        } else {
            $inputData[$inputKey] = '';
        }
    }

    $categorySelect = generateSelectCategory($categoryID);

    $selectedElementTemplateImage = $selectedElementTemplateClass = $selectedElementTemplateCustom = '';

    switch ($inputData['template']) {
        case AWARD_TEMPLATE_TYPE_CLASS:
            $selectedElementTemplateClass = 'selected="selected"';
            break;
        case AWARD_TEMPLATE_TYPE_CUSTOM:
            $selectedElementTemplateCustom = 'selected="selected"';
            break;
        default:
            $selectedElementTemplateImage = 'selected="selected"';
            break;
    }

    $selectedElementAllowRequestsYes = $selectedElementAllowRequestsNo = '';

    switch ($inputData['allowrequests']) {
        case AWARD_ALLOW_REQUESTS:
            $selectedElementAllowRequestsYes = 'checked="checked"';
            break;
        default:
            $selectedElementAllowRequestsNo = 'checked="checked"';
            break;
    }

    $selectedElementTypeBoth = $selectedElementTypeProfile = $selectedElementTypeBothPosts = '';

    switch ($inputData['type']) {
        case GRANT_STATUS_POSTS:
            $selectedElementTypeBothPosts = 'selected="selected"';
            break;
        case GRANT_STATUS_PROFILE:
            $selectedElementTypeProfile = 'selected="selected"';
            break;
        default:
            $selectedElementTypeBoth = 'selected="selected"';
            break;
    }

    $pageTitle = $lang->ougcAwardsControlPanelEditAwardTitle;

    $tableTitle = $lang->ougcAwardsControlPanelEditAwardTableTitle;

    $tableDescription = $lang->ougcAwardsControlPanelEditAwardTableDescription;

    $buttonText = $lang->ougcAwardsControlPanelEditAwardButton;

    if ($newAwardPage) {
        $pageTitle = $lang->ougcAwardsControlPanelNewAwardTitle;

        $tableTitle = $lang->ougcAwardsControlPanelNewAwardTableTitle;

        $tableDescription = $lang->ougcAwardsControlPanelNewAwardTableDescription;

        $buttonText = $lang->ougcAwardsControlPanelNewAwardButton;
    }

    $pageContents = eval(getTemplate('controlPanelNewEditAwardForm'));
} elseif (isModerator() && $mybb->get_input('action') === 'deleteAward') {
    if ($mybb->request_method === 'post') {
        if (!verify_post_check($mybb->input['my_post_key'], true)) {
            redirectAdmin($lang->invalid_post_verify_key2, true);
        }

        awardDelete($awardID);

        cacheUpdate();

        logAction();

        redirect(urlHandlerBuild(), $lang->ougcAwardsRedirectAwardDeleted);
    }

    $pendingRequestTotal = my_number_format(
        requestGetPendingTotal(
            ["aid='{$awardID}'"]
        )
    );

    $grantedTotal = grantGetSingle(["aid='{$awardID}'"], 'COUNT(*) AS grantedTotal');

    if (!empty($grantedTotal['grantedTotal'])) {
        $grantedTotal = my_number_format($grantedTotal['grantedTotal']);
    } else {
        $grantedTotal = 0;
    }

    $ownersTotal = ownerGetSingle(["aid='{$awardID}'"], 'COUNT(*) AS ownersTotal');

    if (!empty($ownersTotal['ownersTotal'])) {
        $ownersTotal = my_number_format($ownersTotal['ownersTotal']);
    } else {
        $ownersTotal = 0;
    }

    $pageTitle = $lang->ougcAwardsControlPanelDeleteAwardTitle;

    $confirmationTitle = $lang->ougcAwardsControlPanelDeleteAwardTableTitle;

    $confirmationButtonText = $lang->ougcAwardsControlPanelDeleteAwardButton;

    $confirmationContent = eval(getTemplate('controlPanelConfirmationDeleteAward'));

    $pageContents = eval(getTemplate('controlPanelConfirmation'));
} elseif ($mybb->get_input('action') === 'viewUsers') {
    if ($mybb->request_method === 'post') {
        if (isset($mybb->input['revoke'])) {
            $userNames = explode(',', $mybb->get_input('username'));

            $usersCache = [];

            foreach ($userNames as $userName) {
                if ($userData = getUserByUserName($userName)) {
                    $usersCache[] = $userData;
                } else {
                    $errorMessages[] = $lang->ougcAwardsErrorInvalidUsers;

                    break;
                }
            }

            foreach ($usersCache as $userData) {
                if (!canManageUsers((int)$userData['uid'])) {
                    $errorMessages[] = $lang->ougcAwardsErrorNoUsersPermission;

                    break;
                }

                if (!grantFind($awardID, (int)$userData['uid'])) {
                    $errorMessages[] = $lang->ougcAwardsErrorInvalidGrant;

                    break;
                }
            }

            /*
            if (!$mybb->get_input('gid')) {
                $show_gived_list = true;
            }

            if (!($gived = grantFind($award['aid'], $user['uid']))) {
                $errors = inline_error($lang->ougcAwardsErrorInvalidGrant);
            } elseif (!($gived = grantGetSingle($mybb->get_input('gid', MyBB::INPUT_INT)))) {
                $errors = inline_error($lang->ougcAwardsErrorInvalidGrant);
            } else {
            */

            if (empty($errorMessages)) {
                foreach ($usersCache as $userData) {
                    if ($grantData = grantFind($awardID, (int)$userData['uid'])) {
                        grantDelete((int)$grantData['gid']);

                        logAction();
                    }
                }

                redirect(urlHandlerBuild(), $lang->ougcAwardsRedirectGrantRevoked);
            }
        } elseif (isset($mybb->input['grant'])) {
            $userNames = explode(',', $mybb->get_input('username'));

            $usersCache = [];

            foreach ($userNames as $userName) {
                if ($userData = getUserByUserName($userName)) {
                    $usersCache[] = $userData;
                } else {
                    $errorMessages[] = $lang->ougcAwardsErrorInvalidUsers;

                    break;
                }
            }

            foreach ($usersCache as $userData) {
                if (!canManageUsers((int)$userData['uid'])) {
                    $errorMessages[] = $lang->ougcAwardsErrorNoUsersPermission;

                    break;
                }

                if (!grantFind($awardID, (int)$userData['uid'])) {
                    $errorMessages[] = $lang->ougcAwardsErrorInvalidGrant;

                    break;
                }
            }

            /*
            if (!$mybb->get_input('gid')) {
                $show_gived_list = true;
            }

            if (!($gived = grantFind($award['aid'], $user['uid']))) {
                $errors = inline_error($lang->ougcAwardsErrorInvalidGrant);
            } elseif (!($gived = grantGetSingle($mybb->get_input('gid', MyBB::INPUT_INT)))) {
                $errors = inline_error($lang->ougcAwardsErrorInvalidGrant);
            } else {
            */

            if (empty($errorMessages)) {
                foreach ($usersCache as $userData) {
                    if ($grantData = grantFind($awardID, (int)$userData['uid'])) {
                        grantDelete((int)$grantData['gid']);

                        logAction();
                    }
                }

                redirect(urlHandlerBuild(), $lang->ougcAwardsRedirectGrantRevoked);
            }
        }
        $userNames = explode(',', $mybb->get_input('username'));

        $usersCache = [];

        foreach ($userNames as $userName) {
            if ($userData = getUserByUserName($userName)) {
                $usersCache[] = $userData;
            } else {
                $errorMessages[] = $lang->ougcAwardsErrorInvalidUsers;

                break;
            }
        }

        foreach ($usersCache as $userData) {
            if (!canManageUsers((int)$userData['uid'])) {
                $errorMessages[] = $lang->ougcAwardsErrorNoUsersPermission;

                break;
            }
        }

        if ($mybb->get_input('thread')) {
            if (!($threadData = getThreadByUrl($mybb->get_input('thread')))) {
                $errorMessages[] = $lang->ougcAwardsErrorInvalidThread;
            }
        }

        if (empty($errorMessages)) {
            foreach ($usersCache as $userData) {
                grantInsert(
                    $awardID,
                    (int)$userData['uid'],
                    $mybb->get_input('reason'),
                    !empty($threadData['tid']) ? (int)$threadData['tid'] : 0
                );

                logAction();
            }

            redirect(urlHandlerBuild(['action' => 'viewUsers', 'awardID' => $awardID]),
                $lang->ougcAwardsRedirectGranted);
        }
    }

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

    $userIDs = $threadsCache = $usersCache = [];

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
            $userIDs[] = (int)$grantData['thread'];
        }
    }

    $paginationMenu = (string)multipage(
        $totalGrantedCount,
        (int)getSetting('perpage'),
        $mybb->get_input('page', MyBB::INPUT_INT),
        urlHandlerBuild(['action' => 'viewUsers', 'awardID' => $awardID])
    );

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

    $grantedList = '';

    $alternativeBackground = alt_trow(true);

    foreach ($grantCacheData as $grantData) {
        $grantID = (int)$grantData['gid'];

        $requestID = (int)$grantData['rid'];

        $taskID = (int)$grantData['tid'];

        $grantReason = awardGetInfo(INFORMATION_TYPE_REASON, $awardID, $grantID, $requestID, $taskID);

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

            $threadLink = eval(getTemplate('controlPanelUsersRowLink'));
        }

        $grantDate = $lang->sprintf(
            $lang->ougcAwardsDate,
            my_date($mybb->settings['dateformat'], $grantData['date']),
            my_date($mybb->settings['timeformat'], $grantData['date'])
        );

        $editUrl = urlHandlerBuild(['action' => 'editGrant', 'grantID' => $grantID]);

        if (isModerator()) {
            $columnRow = eval(getTemplate('controlPanelUsersRowOptions'));
        }

        $grantedList .= eval(getTemplate('controlPanelUsersRow'));

        $alternativeBackground = alt_trow();
    }

    if (!$grantedList) {
        $grantedList = eval(getTemplate('controlPanelUsersEmpty'));
    }

    $pageTitle = $lang->ougcAwardsControlPanelUsersTitle;

    $formUrl = urlHandlerBuild(['action' => 'viewUsers', 'awardID' => $awardID]);

    $columSpan = 4;

    if (isModerator()) {
        ++$columSpan;

        $columnHeader = eval(getTemplate('controlPanelUsersColumnOptions'));

        $grantForm = eval(getTemplate('controlPanelUsersFormGrant'));

        $revokeForm = eval(getTemplate('controlPanelUsersFormRevoke'));
    }

    $pageContents = eval(getTemplate('controlPanelUsers'));
} elseif ($mybb->get_input('action') === 'viewOwners') {
    if ($mybb->request_method === 'post') {
        $userNames = explode(',', $mybb->get_input('username'));

        $usersCache = [];

        foreach ($userNames as $userName) {
            if ($userData = getUserByUserName($userName)) {
                $usersCache[] = $userData;
            } else {
                $errorMessages[] = $lang->ougcAwardsErrorInvalidUsers;

                break;
            }
        }

        foreach ($usersCache as $userData) {
            if (!canManageUsers((int)$userData['uid'])) {
                $errorMessages[] = $lang->ougcAwardsErrorNoUsersPermission;

                break;
            }

            if (ownerFind($awardID, (int)$userData['uid'])) {
                $errorMessages[] = $lang->ougcAwardsErrorDuplicatedOwner;

                break;
            }
        }

        if (empty($errorMessages)) {
            foreach ($usersCache as $userData) {
                ownerInsert($awardID, (int)$userData['uid']);

                logAction();
            }

            redirect(urlHandlerBuild(['action' => 'viewOwners', 'awardID' => $awardID]),
                $lang->ougcAwardsRedirectOwnerAssigned);
        }
    }

    $totalOwnersCount = ownerGetUser(["aid='{$awardID}'"],
        'COUNT(oid) AS totalOwners',
        ['limit' => 1]);

    if (empty($totalOwnersCount['totalOwners'])) {
        $totalOwnersCount = 0;
    } else {
        $totalOwnersCount = (int)$totalOwnersCount['totalOwners'];
    }

    if ($mybb->get_input('page', MyBB::INPUT_INT) > 0) {
        $startPage = ($mybb->get_input('page', MyBB::INPUT_INT) - 1) * (int)getSetting('perpage');

        $totalPages = ceil($totalOwnersCount / (int)getSetting('perpage'));

        if ($mybb->get_input('page', MyBB::INPUT_INT) > $totalPages) {
            $startPage = 0;

            $mybb->input['page'] = 1;
        }
    } else {
        $startPage = 0;

        $mybb->input['page'] = 1;
    }

    $userIDs = $usersCache = [];

    $ownersCacheData = ownerGetUser(["aid='{$awardID}'"],
        '*',
        [
            'limit' => (int)getSetting('perpage'),
            'limit_start' => $startPage,
            'order_by' => 'date',
            'order_dir' => 'desc'
        ]
    );

    foreach ($ownersCacheData as $ownerData) {
        if (!empty($ownerData['uid'])) {
            $userIDs[] = (int)$ownerData['uid'];
        }
    }

    $paginationMenu = (string)multipage(
        $totalOwnersCount,
        (int)getSetting('perpage'),
        $mybb->get_input('page', MyBB::INPUT_INT),
        urlHandlerBuild(['action' => 'viewUsers', 'awardID' => $awardID])
    );

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

    $ownersList = '';

    $alternativeBackground = alt_trow(true);

    foreach ($ownersCacheData as $ownerData) {
        $ownerID = (int)$ownerData['oid'];

        $userID = (int)$ownerData['uid'];

        $userName = $userNameFormatted = $userProfileLink = '';

        if (!empty($usersCache[$userID])) {
            $userData = $usersCache[$userID];

            $userName = htmlspecialchars_uni($userData['username']);

            $userNameFormatted = format_name($userName, $userData['usergroup'], $userData['displaygroup']);

            $userProfileLink = build_profile_link($userNameFormatted, $userData['uid']);
        }

        $ownerDate = $lang->sprintf(
            $lang->ougcAwardsDate,
            my_date($mybb->settings['dateformat'], $ownerData['date']),
            my_date($mybb->settings['timeformat'], $ownerData['date'])
        );

        $deleteUrl = urlHandlerBuild(['action' => 'deleteOwner', 'ownerID' => $ownerID]);

        $ownersList .= eval(getTemplate('controlPanelOwnersRow'));

        $alternativeBackground = alt_trow();
    }

    if (!$ownersList) {
        $ownersList = eval(getTemplate('controlPanelOwnersEmpty'));
    }

    $pageTitle = $lang->ougcAwardsControlPanelOwnersTitle;

    $formUrl = urlHandlerBuild(['action' => 'viewOwners', 'awardID' => $awardID]);

    $pageContents = eval(getTemplate('controlPanelOwners'));
} elseif (isModerator() && $mybb->get_input('action') === 'deleteOwner') {
    if ($mybb->request_method === 'post') {
        if (!verify_post_check($mybb->input['my_post_key'], true)) {
            redirectAdmin($lang->invalid_post_verify_key2, true);
        }

        ownerDelete($ownerID);

        cacheUpdate();

        logAction();

        redirect(urlHandlerBuild(['action' => 'viewOwners', 'awardID' => $awardID]),
            $lang->ougcAwardsRedirectOwnerRevoked);
    }

    $pageTitle = $lang->ougcAwardsControlPanelDeleteOwnersTitle;

    $formUrl = urlHandlerBuild();

    $confirmationTitle = $lang->ougcAwardsControlPanelDeleteOwnersTitle;

    $confirmationButtonText = $lang->ougcAwardsControlPanelDeleteOwnersButton;

    $confirmationContent = eval(getTemplate('controlPanelConfirmationDeleteOwner'));

    $pageContents = eval(getTemplate('controlPanelConfirmation'));
} elseif ($mybb->get_input('action') == 'viewRequests') {
    $requestStatusOpen = REQUEST_STATUS_REJECTED;

    $requestStatusOpen = REQUEST_STATUS_ACCEPTED;

    $requestStatusOpen = REQUEST_STATUS_ACCEPTED;

    $whereClauses = ["aid='{$awardID}'"];

    $filterOptions = $mybb->get_input('filterOptions', MyBB::INPUT_INT);

    if (isset($filterOptions['status'])) {
        $filterOptions['status'] = (int)$filterOptions['status'];

        switch ($filterOptions['status']) {
            case $requestStatusOpen:
            case $requestStatusOpen:
            case $requestStatusOpen:
                $whereClauses[] = "status='{$filterOptions['status']}'";
                break;
        }
    }

    $selectedRequestIDs = [];

    if ($mybb->request_method === 'post') {
        foreach ($mybb->get_input('selected', MyBB::INPUT_ARRAY) as $requestID => $v) {
            $selectedRequestIDs[(int)$requestID] = 1;
        }

        if (!$selectedRequestIDs) {
            $errorMessages[] = $lang->ougcAwardsErrorRequestsNoneSelected;
        } else {
            $selectedRequestIDs = implode("','", $selectedRequestIDs);

            $pendingRequestsCache = requestGetPending(
                ["rid IN ('{$selectedRequestIDs}')"]
            );

            foreach ($pendingRequestsCache as $requestData) {
                if (!canManageUsers((int)$requestData['uid'])) {
                    $errorMessages[] = $lang->ougcAwardsErrorNoUsersPermission;

                    break;
                }
            }
        }

        if (empty($errorMessages)) {
            foreach ($pendingRequestsCache as $requestData) {
                if ($mybb->get_input('accept')) {
                    requestApprove((int)$requestData['rid']);
                } else {
                    requestReject((int)$requestData['rid']);
                }

                logAction();
            }

            cacheUpdate();

            if ($mybb->get_input('accept')) {
                redirect(urlHandlerBuild(['action' => 'viewRequests']), $lang->ougcAwardsRedirectRequestAccepted);
            } else {
                redirect(urlHandlerBuild(['action' => 'viewRequests']), $lang->ougcAwardsRedirectRequestRejected);
            }
        }
    }

    $totalRequestsCount = requestGetPending(
        $whereClauses,
        'COUNT(rid) AS totalRequests',
        ['limit' => 1]
    );

    if (empty($totalRequestsCount['totalRequests'])) {
        $totalRequestsCount = 0;
    } else {
        $totalRequestsCount = (int)$totalRequestsCount['totalRequests'];
    }

    $requestsList = $buttons = '';

    if (!$totalRequestsCount) {
        $requestsList = eval(getTemplate('controlPanelRequestsEmpty'));
    } else {
        if ($mybb->get_input('page', MyBB::INPUT_INT) > 0) {
            $startPage = ($mybb->get_input('page', MyBB::INPUT_INT) - 1) * (int)getSetting('perpage');

            $totalPages = ceil($totalRequestsCount / (int)getSetting('perpage'));

            if ($mybb->get_input('page', MyBB::INPUT_INT) > $totalPages) {
                $startPage = 0;

                $mybb->input['page'] = 1;
            }
        } else {
            $startPage = 0;

            $mybb->input['page'] = 1;
        }

        $paginationMenu = (string)multipage(
            $totalRequestsCount,
            getSetting('perpage'),
            $mybb->get_input('page', 1),
            urlHandlerBuild(['action' => 'viewRequests'])
        );

        $pendingRequestsCache = requestGetPending(
            $whereClauses,
            '*',
            ['limit_start' => $startPage, 'limit' => (int)getSetting('perpage')]
        );

        $userIDs = [];

        foreach ($pendingRequestsCache as $grantData) {
            if (!empty($grantData['uid'])) {
                $userIDs[] = (int)$grantData['uid'];
            }
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

        $alternativeBackground = alt_trow(true);

        foreach ($pendingRequestsCache as $requestData) {
            $userID = (int)$requestData['uid'];

            $awardID = (int)$requestData['aid'];

            $awardData = awardGet($awardID);

            $requestID = (int)$requestData['rid'];

            if (!($awardName = awardGetInfo(INFORMATION_TYPE_NAME, $awardID))) {
                $awardName = $awardData['name'];
            }

            $awardName = htmlspecialchars_uni($awardName);

            $requestMessage = htmlspecialchars_uni($requestData['message']);

            $awardImage = $awardClass = awardGetIcon($awardID);

            $awardImage = eval(getTemplate(awardGetInfo(INFORMATION_TYPE_TEMPLATE, $awardID), false));

            $awardImage = eval(getTemplate('awardWrapper', false));

            $userName = $userNameFormatted = $userProfileLink = '';

            if (!empty($usersCache[$requestData['uid']])) {
                $userData = $usersCache[$requestData['uid']];

                $userName = htmlspecialchars_uni($userData['username']);

                $userNameFormatted = format_name($userName, $userData['usergroup'], $userData['displaygroup']);

                $userProfileLink = build_profile_link($userNameFormatted, $userData['uid']);
            }

            switch ((int)$requestData['status']) {
                case REQUEST_STATUS_REJECTED:
                    $requestStatus = $lang->ougcAwardsControlPanelRequestsStatusRejected;
                    break;
                case REQUEST_STATUS_ACCEPTED:
                    $requestStatus = $lang->ougcAwardsControlPanelRequestsStatusAccepted;
                    break;
                case REQUEST_STATUS_OPEN:
                    $requestStatus = $lang->ougcAwardsControlPanelRequestsStatusPending;
                    break;
            }

            $checkedElement = '';

            if (isset($selectedRequestIDs[$requestData['rid']])) {
                $checkedElement = 'checked="checked"';
            }

            $requestsList .= eval(getTemplate('controlPanelRequestsRow'));

            $alternativeBackground = alt_trow();
        }

        $buttons = eval(getTemplate('modcp_requests_buttons'));
    }

    $pageTitle = $lang->ougcAwardsControlPanelRequests;

    $formUrl = urlHandlerBuild(['action' => 'viewRequests']);

    $pageContents = eval(getTemplate('controlPanelRequests'));
} elseif (isModerator() && $mybb->get_input('action') === 'editGrant') {
    $inputData = [];

    foreach (['thread', 'reason', 'date'] as $inputKey) {
        if (isset($mybb->input[$inputKey])) {
            $inputData[$inputKey] = $mybb->get_input($inputKey);
        } elseif (isset($grantData[$inputKey])) {
            $inputData[$inputKey] = $grantData[$inputKey];
        } else {
            $inputData[$inputKey] = '';
        }
    }

    if (!empty($inputData['thread'])) {
        if (is_numeric($inputData['thread'])) {
            if (is_numeric($inputData['thread']) && !($threadData = get_thread($inputData['thread']))) {
                $errorMessages[] = $lang->ougcAwardsErrorInvalidThread;
            }
        } elseif (!($threadData = getThreadByUrl($inputData['thread']))) {
            $errorMessages[] = $lang->ougcAwardsErrorInvalidThread;
        }
    }

    if (!empty($threadData['tid'])) {
        $inputData['thread'] = get_thread_link($threadData['tid']);
    }

    if ($mybb->request_method === 'post') {
        verify_post_check($mybb->get_input('my_post_key'));

        if (my_strlen($inputData['reason']) > 255) {
            $errorMessages[] = $lang->ougcAwardsErrorInvalidGrantReason;
        }

        if (filter_var($inputData['date'], FILTER_VALIDATE_INT) === false) {
            $errorMessages[] = $lang->ougcAwardsErrorInvalidGrantDate;
        }

        if (empty($errorMessages)) {
            $updateData = [
                'reason' => $inputData['reason'],
                'date' => $inputData['date']
            ];

            if (!empty($threadData['tid'])) {
                $updateData['thread'] = $threadData['tid'];
            }

            grantUpdate($updateData, $grantID);

            cacheUpdate();

            logAction();

            redirect(urlHandlerBuild(['action' => 'viewUsers', 'awardID' => $awardID]),
                $lang->ougcAwardsRedirectGrantedUpdated);
        }
    }

    foreach (['thread', 'reason', 'date'] as $inputKey) {
        if (isset($mybb->input[$inputKey])) {
            $inputData[$inputKey] = htmlspecialchars_uni($mybb->get_input($inputKey));
        } elseif (isset($grantData[$inputKey])) {
            $inputData[$inputKey] = htmlspecialchars_uni($grantData[$inputKey]);
        } else {
            $inputData[$inputKey] = '';
        }
    }

    $pageTitle = $lang->ougcAwardsControlPanelEditGrantTitle;

    $pageContents = eval(getTemplate('controlPanelGrantEdit'));
} elseif ($mybb->get_input('action') === 'viewPresets') {
    if (!is_member(getSetting('presets_groups'))) {
        error_no_permission();
    }

    if ($presetID) {
        if (!($currentPresetData = presetGet(["pid='{$presetID}'", "uid='{$currentUserID}'"], '*', ['limit' => 1]))) {
            error_no_permission();
        }
    }

    $totalPresets = presetGet(["uid='{$currentUserID}'"], 'count(*) AS totalPresets', ['limit' => 1]);

    if (!empty($totalPresets['totalPresets'])) {
        $totalPresets = (int)$totalPresets['totalPresets'];
    } else {
        $totalPresets = 0;
    }

    if ($mybb->request_method === 'post') {
        if ($mybb->get_input('newPreset', \MyBB::INPUT_INT) === 1) {
            if (getSetting('presets_maximum') <= $totalPresets) {
                error_no_permission();
            }

            $presetID = presetInsert([
                'name' => $mybb->get_input('presetName'),
                'uid' => $currentUserID
            ]);

            redirect(urlHandlerBuild(['action' => 'viewPresets', 'presetID' => $presetID]),
                $lang->ougcAwardsRedirectPresetCreated);
        }

        if (isset($mybb->input['setDefault'])) {
            $db->update_query('users', [
                'ougc_awards_preset' => $presetID,
            ], "uid='{$currentUserID}'");

            redirect(urlHandlerBuild(['action' => 'viewPresets', 'presetID' => $presetID]),
                $lang->ougcAwardsRedirectPresetUpdated);
        }

        if (isset($mybb->input['deletePreset'])) {
            $db->update_query('users', [
                'ougc_awards_preset' => 0,
            ], "uid='{$currentUserID}'");

            presetDelete($presetID);

            redirect(urlHandlerBuild(['action' => 'viewPresets']),
                $lang->ougcAwardsRedirectPresetDeleted);
        }
    }

    $newPresetForm = $presetsList = $selectOptions = $setDefaultButton = '';

    if ($totalPresets) {
        foreach (presetGet(["uid='{$currentUserID}'"]) as $presetData) {
            $optionValue = (int)$presetData['pid'];

            $optionName = htmlspecialchars_uni($presetData['name']);

            $selectedElement = '';

            if ($presetID === (int)$presetData['pid']) {
                $selectedElement = ' selected="selected"';
            }

            $selectOptions .= eval(getTemplate('selectFieldOption'));
        }

        $selectName = 'presetID';

        $onChange = 'this.form.submit()';

        $presetOptions = eval(getTemplate('selectField'));

        if ($presetID && $presetID != $mybb->user['ougc_awards_preset']) {
            $setDefaultButton = eval(getTemplate('controlPanelPresetsDefault'));
        }

        $presetsList = eval(getTemplate('controlPanelPresetsSelect'));
    }

    if (getSetting('presets_maximum') > $totalPresets) {
        $newPresetForm = eval(getTemplate('controlPanelPresetsForm'));
    }

    if ($presetID) {
        $grantStatusVisible = \ougc\Awards\Core\GRANT_STATUS_VISIBLE;

        $categoriesIDs = $awardIDs = [];

        foreach (categoryGetCache() as $categoryData) {
            $categoryID = (int)$categoryData['cid'];

            if (isVisibleCategory($categoryID)) {
                $categoriesIDs[$categoryID] = $categoryID;
            }
        }

        foreach (awardsGetCache() as $awardID => $awardData) {
            if (isVisibleAward($awardID) && in_array((int)$awardData['cid'], $categoriesIDs)) {
                $awardIDs[$awardID] = $awardID;
            }
        }

        $awardIDs = implode("','", $awardIDs);

        $grantCacheData = \ougc\Awards\Core\awardGetUser([
            "uid='{$currentUserID}'",
            "visible='{$grantStatusVisible}'",
            "aid IN ('{$awardIDs}')",
        ],
            '*',
            [
                'order_by' => 'disporder, date',
                'order_dir' => 'desc'
            ]
        );

        $visibleAwards = $hiddenAwards = '';

        $presetVisibleAwards = [];

        if (!empty($currentPresetData['visible'])) {
            $presetVisibleAwards = array_flip(array_filter((array)my_unserialize($currentPresetData['visible'])));
        }

        foreach ($grantCacheData as $awardData) {
            $grantID = (int)$awardData['gid'];

            $awardID = (int)$awardData['aid'];

            if (!($awardName = awardGetInfo(INFORMATION_TYPE_NAME, $awardID))) {
                $awardName = $awardData['name'];
            }

            $awardName = htmlspecialchars_uni($awardName);

            $awardImage = $awardClass = awardGetIcon($awardID);

            $awardImage = eval(getTemplate(awardGetInfo(INFORMATION_TYPE_TEMPLATE, $awardID), false));

            if (isset($presetVisibleAwards[$grantID])) {
                $visibleAwards .= eval(getTemplate('controlPanelPresetsAward', false));
            } else {
                $hiddenAwards .= eval(getTemplate('controlPanelPresetsAward', false));
            }
        }

        $presetRows = eval(getTemplate('controlPanelPresetsRow'));
    }

    $pageTitle = $lang->ougcAwardsControlPanelPresetsTitle;

    $pageContents = eval(getTemplate('controlPanelPresets'));
} elseif ($mybb->get_input('action') === 'viewUser') {
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

        foreach (categoryGetCache() as $categoryData) {
            $categoryID = (int)$categoryData['cid'];

            if (isVisibleCategory($categoryID)) {
                $categoryIDs[$categoryID] = $categoryID;
            }
        }

        $awardsCache = awardsGetCache();

        foreach ($awardsCache as $awardID => $awardData) {
            if (isVisibleAward(
                    $awardID
                ) && !empty($categoryIDs[$awardData['cid']]) && (int)$awardData['type'] !== GRANT_STATUS_POSTS) {
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
} elseif ($mybb->get_input('action') === 'requestAward') {
    if (!canRequestAwards($awardID)) {
        $errorMessages[] = $lang->ougcAwardsRequestErrorNoPermission;
    }

    $categoryID = (int)$awardData['cid'];

    if (!($categoryData = categoryGet($categoryID)) || !isVisibleCategory($categoryID)) {
        $errorMessages[] = $lang->ougcAwardsErrorInvalidCategory;
    }

    $statusPending = REQUEST_STATUS_PENDING;

    $pendingRequestTotal = requestGetPendingTotal(
        ["aid='{$awardID}'", "uid='{$currentUserID}'", "status='{$statusPending}'"]
    );

    if ($pendingRequestTotal) {
        $errorMessages[] = $lang->ougcAwardsErrorPendingRequest;
    }

    $buttonContent = '';

    if ($errorMessages) {
        if (!empty($errorMessages)) {
            $errorMessages = inline_error($errorMessages);
        } else {
            $errorMessages = '';
        }

        $disabledElement = 'disabled="disabled"';

        $buttonContent = eval(getTemplate('pageequestButton'));

        $formContents = eval(getTemplate('pageRequestError'));
    } elseif ($mybb->request_method === 'post') {
        requestInsert([
            'uid' => $currentUserID,
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

        $formUrl = urlHandlerBuild();

        $modalContents = eval(getTemplate('pageRequest', false));

        echo json_encode(['modal' => $modalContents]);

        exit;
    } else {
        $awardName = htmlspecialchars_uni($awardData['name']);

        $awardDescription = htmlspecialchars_uni($awardData['description']);

        $awardImage = $imageClass = awardGetIcon($awardData['aid']);

        $disabledElement = '';

        $buttonContent = eval(getTemplate('pageRequestButton'));

        $awardImage = eval(getTemplate(awardGetInfo(INFORMATION_TYPE_TEMPLATE, $awardID), false));

        $awardImage = eval(getTemplate('awardWrapper', false));

        $formContents = eval(getTemplate('pageRequestForm'));
    }

    $formUrl = urlHandlerBuild();

    $modalContents = eval(getTemplate('pageRequest', false));

    echo $modalContents;

    exit;
} elseif (isModerator() && in_array($mybb->get_input('action'), ['newTask', 'editTask'], true)) {
    $newTaskPage = $mybb->get_input('action') === 'newTask';

    $taskData = \ougc\Awards\Core\taskGet(["tid='{$taskID}'"], '*', ['limit' => 1]);

    $inputData = [];

    foreach (
        [
            'name',
            'description',
            'reason',
            'disporder',
            'active',
            'reason',
            'thread',
            'allowmultiple',
        ] as $inputKey
    ) {
        if (isset($mybb->input[$inputKey])) {
            $inputData[$inputKey] = $mybb->get_input($inputKey);
        } elseif (isset($taskData[$inputKey])) {
            $inputData[$inputKey] = $taskData[$inputKey];
        } else {
            $inputData[$inputKey] = '';
        }
    }

    foreach (
        [
            'requirements',
            'give',
            'revoke',
        ] as $inputKey
    ) {
        if (isset($mybb->input[$inputKey])) {
            $inputData[$inputKey] = $mybb->get_input($inputKey, \MyBB::INPUT_ARRAY);
        } elseif (isset($taskData[$inputKey])) {
            $inputData[$inputKey] = explode(',', $taskData[$inputKey]);
        } else {
            $inputData[$inputKey] = [];
        }
    }

    if ($mybb->request_method === 'post') {
        verify_post_check($mybb->get_input('my_post_key'));

        if (my_strlen($inputData['name']) > 100) {
            $errorMessages[] = $lang->ougcAwardsErrorInvalidTaskName;
        }

        if (my_strlen($inputData['description']) > 255) {
            $errorMessages[] = $lang->ougcAwardsErrorInvalidTaskDescription;
        }

        if (empty($errorMessages)) {
            $insertData = [
                'name' => $inputData['name'],
                'description' => $inputData['description'],
                'reason' => $inputData['reason'],
                'disporder' => $inputData['disporder'],
                'active' => $inputData['active'],
                'requirements' => $inputData['requirements'],
                'give' => $inputData['give'],
            ];

            //_dump($inputData);

            if ($newTaskPage) {
                \ougc\Awards\Core\taskInsert($insertData);
            } else {
                \ougc\Awards\Core\taskUpdate($insertData, $taskID);
            }

            cacheUpdate();

            logAction();

            if ($newTaskPage) {
                redirect(urlHandlerBuild(['action' => 'manageTasks']), $lang->ougcAwardsRedirectTaskCreated);
            } else {
                redirect(urlHandlerBuild(['action' => 'manageTasks']), $lang->ougcAwardsRedirectTaskUpdated);
            }
        }
    }

    foreach (
        [
            'name',
            'description',
            'reason',
            'disporder',
            'active',
        ] as $inputKey
    ) {
        if (isset($mybb->input[$inputKey])) {
            $inputData[$inputKey] = htmlspecialchars_uni($mybb->get_input($inputKey));
        } elseif (isset($taskData[$inputKey])) {
            $inputData[$inputKey] = htmlspecialchars_uni($taskData[$inputKey]);
        } else {
            $inputData[$inputKey] = '';
        }
    }

    $selectedElementEnabledYes = $selectedElementEnabledNo = '';

    switch ($inputData['active']) {
        case \ougc\Awards\Core\TASK_STATUS_ENABLED:
            $selectedElementEnabledYes = 'checked="checked"';
            break;
        default:
            $selectedElementEnabledNo = 'checked="checked"';
            break;
    }

    $selectOptions = '';

    if (!is_array($inputData['requirements'])) {
        $inputData['requirements'] = explode(',', $inputData['requirements']);
    }

    $selectedRequirements = array_flip($inputData['requirements']);

    foreach (
        [
            'usergroups' => $lang->ougcAwardsControlPanelNewTaskRequirementsGroups,
            'posts' => $lang->ougcAwardsControlPanelNewTaskRequirementsPostCount,
            'threads' => $lang->ougcAwardsControlPanelNewTaskRequirementsThreadCount,
            'fposts' => $lang->ougcAwardsControlPanelNewTaskRequirementsForumPostCount,
            'fthreads' => $lang->ougcAwardsControlPanelNewTaskRequirementsForumThreadCount,
            'registered' => $lang->ougcAwardsControlPanelNewTaskRequirementsTimeRegistered,
            'online' => $lang->ougcAwardsControlPanelNewTaskRequirementsTimeOnline,
            'reputation' => $lang->ougcAwardsControlPanelNewTaskRequirementsReputation,
            'referrals' => $lang->ougcAwardsControlPanelNewTaskRequirementsReferrals,
            'warnings' => $lang->ougcAwardsControlPanelNewTaskRequirementsWarningPoints,
            //'newpoints' => $lang->ougcAwardsControlPanelNewTaskRequirementsNewpoints,
            'previousawards' => $lang->ougcAwardsControlPanelNewTaskRequirementsPreviousAwards,
            'profilefields' => $lang->ougcAwardsControlPanelNewTaskRequirementsFilledProfileFields,
            //'mydownloads' => $lang->ougcAwardsControlPanelNewTaskRequirementsMyDownloads,
            //'myarcadechampions'	=> $lang->ougcAwardsControlPanelNewTaskRequirementsMyArcadeChampions,
            //'myarcadescores' => $lang->ougcAwardsControlPanelNewTaskRequirementsMyArcadeScores,
            'ougc_customrep_r' => $lang->ougcAwardsControlPanelNewTaskRequirementsCustomReputationReceived,
            'ougc_customrep_g' => $lang->ougcAwardsControlPanelNewTaskRequirementsCustomReputationGiven
        ] as $requirementKey => $requirementText
    ) {
        $optionValue = $requirementKey;

        $optionName = $requirementText;

        $selectedElement = '';

        if (isset($selectedRequirements[$requirementKey])) {
            $selectedElement = ' selected="selected"';
        }

        $selectOptions .= eval(getTemplate('selectFieldOption'));
    }

    $selectName = 'requirements[]';

    $onChange = '';

    $multipleOption = 'multiple="multiple"';

    $requirementOptions = eval(getTemplate('selectField'));

    $awardsGrantSelect = \ougc\Awards\Core\generateSelectAwards('give[]', $inputData['give'], ['multiple' => true]);

    $selectedElementMultipleYes = $selectedElementMultipleNo = '';

    switch ($inputData['allowmultiple']) {
        case \ougc\Awards\Core\TASK_ALLOW_MULTIPLE:
            $selectedElementMultipleYes = 'checked="checked"';
            break;
        default:
            $selectedElementMultipleNo = 'checked="checked"';
            break;
    }

    $awardsRevokeSelect = \ougc\Awards\Core\generateSelectAwards('revoke[]', $inputData['revoke'], ['multiple' => true]
    );

    $pageTitle = $lang->ougcAwardsControlPanelEditTaskTitle;

    $tableTitle = $lang->ougcAwardsControlPanelEditTaskTableTitle;

    $tableDescription = $lang->ougcAwardsControlPanelEditTaskTableDescription;

    $buttonText = $lang->ougcAwardsControlPanelEditTaskButton;

    if ($newTaskPage) {
        $pageTitle = $lang->ougcAwardsControlPanelNewTaskTitle;

        $tableTitle = $lang->ougcAwardsControlPanelNewTaskTableTitle;

        $tableDescription = $lang->ougcAwardsControlPanelNewTaskTableDescription;

        $buttonText = $lang->ougcAwardsControlPanelNewTaskButton;
    }

    $pageContents = eval(getTemplate('controlPanelNewEditTaskForm'));
} elseif ($mybb->get_input('action') === 'manageTasks') {
    $alternativeBackground = alt_trow(true);

    $taskRows = '';

    $requirementCriteria = [
        'usergroups' => $lang->ougcAwardsControlPanelNewTaskRequirementsGroups,
        'posts' => $lang->ougcAwardsControlPanelNewTaskRequirementsPostCount,
        'threads' => $lang->ougcAwardsControlPanelNewTaskRequirementsThreadCount,
        'fposts' => $lang->ougcAwardsControlPanelNewTaskRequirementsForumPostCount,
        'fthreads' => $lang->ougcAwardsControlPanelNewTaskRequirementsForumThreadCount,
        'registered' => $lang->ougcAwardsControlPanelNewTaskRequirementsTimeRegistered,
        'online' => $lang->ougcAwardsControlPanelNewTaskRequirementsTimeOnline,
        'reputation' => $lang->ougcAwardsControlPanelNewTaskRequirementsReputation,
        'referrals' => $lang->ougcAwardsControlPanelNewTaskRequirementsReferrals,
        'warnings' => $lang->ougcAwardsControlPanelNewTaskRequirementsWarningPoints,
        //'newpoints' => $lang->ougcAwardsControlPanelNewTaskRequirementsNewpoints,
        'previousawards' => $lang->ougcAwardsControlPanelNewTaskRequirementsPreviousAwards,
        'profilefields' => $lang->ougcAwardsControlPanelNewTaskRequirementsFilledProfileFields,
        //'mydownloads' => $lang->ougcAwardsControlPanelNewTaskRequirementsMyDownloads,
        //'myarcadechampions' => $lang->ougcAwardsControlPanelNewTaskRequirementsMyArcadeChampions,
        //'myarcadescores' => $lang->ougcAwardsControlPanelNewTaskRequirementsMyArcadeScores,
        'ougc_customrep_r' => $lang->ougcAwardsControlPanelNewTaskRequirementsCustomReputationReceived,
        'ougc_customrep_g' => $lang->ougcAwardsControlPanelNewTaskRequirementsCustomReputationGiven,
    ];

    foreach (\ougc\Awards\Core\taskGet() as $taskData) {
        $taskID = (int)$taskData['tid'];

        $taskName = htmlspecialchars_uni($taskData['name']);

        $taskDescription = htmlspecialchars_uni($taskData['description']);

        $taskRequirements = array_flip(explode(',', $taskData['requirements']));

        foreach ($taskRequirements as $taskRequirementKey => &$taskRequirementValue) {
            $taskRequirementValue = $requirementCriteria[$taskRequirementKey];
        }

        $taskRequirements = implode($lang->comma, $taskRequirements);

        $checkedElement = $taskGrantAwards = $taskRevokeAwards = '';

        $taskGrantAwardIDs = implode("','", array_map('intval', explode(',', $taskData['give'])));

        foreach (awardsGetCache(["aid IN ('{$taskGrantAwardIDs}')"]) as $awardID => $awardData) {
            $awardImage = $awardClass = awardGetIcon($awardID);

            $awardImage = eval(getTemplate(awardGetInfo(INFORMATION_TYPE_TEMPLATE, $awardID), false));

            $taskGrantAwards .= eval(getTemplate('awardWrapper', false));
        }

        $taskRevokeAwardIDs = implode("','", array_map('intval', explode(',', $taskData['revoke'])));

        foreach (awardsGetCache(["aid IN ('{$taskRevokeAwardIDs}')"]) as $awardID => $awardData) {
            $awardImage = $awardClass = awardGetIcon($awardID);

            $awardImage = eval(getTemplate(awardGetInfo(INFORMATION_TYPE_TEMPLATE, $awardID), false));

            $taskRevokeAwards .= eval(getTemplate('awardWrapper', false));
        }

        if (!empty($taskData['active'])) {
            $checkedElement = 'checked="$checkedElement"';
        }

        $taskRevoke = htmlspecialchars_uni($taskData['revoke']);

        $taskStatus = (int)$taskData['active'];

        $editUrl = urlHandlerBuild(['action' => 'editTask', 'taskID' => $taskID]);

        $deleteUrl = urlHandlerBuild(['action' => 'deleteTask', 'taskID' => $taskID]);

        $taskRows .= eval(getTemplate('controlPanelTasksRow'));

        $alternativeBackground = alt_trow();
    }

    $buttonUrl = urlHandlerBuild(['action' => 'newTask']);

    $buttonText = $lang->ougcAwardsControlPanelButtonNewTask;

    $actionButtons = eval(getTemplate('controlPanelButtons'));

    $pageTitle = $lang->ougcAwardsControlPanelTasksTitle;

    $pageContents = eval(getTemplate('controlPanelTasks'));
} else {
    if ($mybb->request_method === 'post') {
        $categoryID = $mybb->get_input('categoryID', MyBB::INPUT_INT);

        $awardIDs = [0];

        foreach ($mybb->get_input('visible', MyBB::INPUT_ARRAY) as $awardID => $v) {
            $awardIDs[] = (int)$awardID;
        }

        foreach ($mybb->get_input('display', MyBB::INPUT_ARRAY) as $awardID => $displayOrder) {
            awardUpdate(['disporder' => (int)$displayOrder], (int)$awardID);
        }

        $awardIDs = implode("','", $awardIDs);

        $db->update_query(
            'ougc_awards',
            ['visible' => AWARD_STATUS_ENABLED],
            "cid='{$categoryID}' AND aid IN ('{$awardIDs}')"
        );

        $db->update_query(
            'ougc_awards',
            ['visible' => AWARD_STATUS_DISABLED],
            "cid='{$categoryID}' AND aid NOT IN ('{$awardIDs}')"
        );

        cacheUpdate();

        redirect(urlHandlerBuild(), $lang->ougcAwardsRedirectCategoryUpdated);
    }

    $pageContents = $moderationColumnRequest = $moderationColumnEnabled = $moderationColumnDisplayOrder = $moderationColumnOptions = '';

    $columSpan = 3;

    if (isModerator()) {
        $columSpan += 3;

        $moderationColumnEnabled = eval(getTemplate('controlPanelListColumnEnabled'));

        $moderationColumnDisplayOrder = eval(getTemplate('controlPanelListColumnDisplayOrder'));

        $moderationColumnOptions = eval(getTemplate('controlPanelListColumnOptions'));
    }

    $columOptionsRequest = false;

    foreach (categoryGetCache([], '*', ['order_by' => 'disporder']) as $categoryData) {
        $categoryID = (int)$categoryData['cid'];

        if (isModerator() || canRequestAwards(0, $categoryID)) {
            ++$columSpan;

            $moderationColumnRequest = eval(getTemplate('controlPanelListColumnRequest'));
        }

        $requestColumn = '';

        $awardsList = '';

        $categoryName = $categoryData['name'];

        $categoryDescription = $categoryData['description'];

        $ownerObjects = ownerGetUser(["uid='{$currentUserID}'"]);

        if (isset($ownerObjects['aid'])) {
        }

        $ownerAwardIDs = array_map('intval', array_column($ownerObjects, 'aid'));

        $alternativeBackground = alt_trow(true);

        foreach (
            awardsGetCache(["cid='{$categoryID}'"], '*', ['order_by' => 'disporder']
            ) as $awardID => $awardData
        ) {
            if (!isModerator() && !in_array($awardID, $ownerAwardIDs)) {
                continue;
            }

            //$requestColumnRow = '';

            //$colSpanRowCount = 2;

            if (canRequestAwards($awardID)) {
                //$requestColumnRow = eval(getTemplate('page_list_award_request'));

                //--$colSpanRowCount;
            }

            if (!($awardName = awardGetInfo(INFORMATION_TYPE_NAME, $awardID))) {
                $awardName = $awardData['name'];
            }

            $awardName = htmlspecialchars_uni($awardName);

            if (!($awardDescription = awardGetInfo(INFORMATION_TYPE_DESCRIPTION, $awardID))) {
                $awardDescription = $awardData['description'];
            }

            $awardDescription = htmlspecialchars_uni($awardDescription);

            $awardUrl = urlHandlerBuild(['viewAward' => $awardID]);

            $awardImage = $awardClass = awardGetIcon($awardID);

            $awardImage = eval(getTemplate(awardGetInfo(INFORMATION_TYPE_TEMPLATE, $awardID), false));

            $awardImage = eval(getTemplate('awardWrapper', false));

            $usersUrl = urlHandlerBuild(['action' => 'viewUsers', 'awardID' => $awardID]);

            $ownersUrl = urlHandlerBuild(['action' => 'viewOwners', 'awardID' => $awardID]);

            $editUrl = urlHandlerBuild(['action' => 'editAward', 'awardID' => $awardID]);

            $deleteUrl = urlHandlerBuild(['action' => 'deleteAward', 'awardID' => $awardID]);

            $displayOrder = (int)$awardData['disporder'];

            $visibleStatus = (int)$awardData['visible'];

            $checkedElement = '';

            if ($visibleStatus) {
                $checkedElement = 'checked="checked"';
            }

            $rowColumnEnabled = $rowColumnDisplayOrder = $rowColumnOptions = $rowColumnOptionsRequest = '';

            if (isModerator()) {
                $rowColumnEnabled = eval(getTemplate('controlPanelListRowEnabled'));

                $rowColumnDisplayOrder = eval(getTemplate('controlPanelListRowDisplayOrder'));

                $rowColumnOptions = eval(getTemplate('controlPanelListRowOptions'));
            }

            $requestButton = '';

            if (canRequestAwards($awardID)) {
                $requestButton = eval(getTemplate('controlPanelListRowRequestButton'));
            }

            if ($moderationColumnRequest) {
                $rowColumnRequest = eval(getTemplate('controlPanelListRowRequest'));
            }

            $awardsList .= eval(getTemplate('controlPanelListRow'));

            $alternativeBackground = alt_trow();
        }

        if (!$awardsList) {
            $awardsList = eval(getTemplate('controlPanelListRowEmpty'));
        }

        $plugins->run_hooks('ougc_awards_end');

        $editCategoryUrl = $updateButton = '';

        if (isModerator()) {
            $columSpan += 3;

            $editCategoryUrl = urlHandlerBuild(['action' => 'editCategory', 'categoryID' => $categoryID]);

            $editCategoryUrl = eval(getTemplate('controlPanelListButtonEditCategory'));

            $updateButton = eval(getTemplate('controlPanelListButtonUpdateCategory'));
        }

        $pageContents .= eval(getTemplate('controlPanelList'));
    }

    if (!$pageContents) {
        $pageContents = eval(getTemplate('controlPanelEmpty'));
    } elseif (isModerator()) {
        $buttonUrl = urlHandlerBuild(['action' => 'newCategory']);

        $buttonText = $lang->ougcAwardsControlPanelButtonNewCategory;

        $actionButtons = eval(getTemplate('controlPanelButtons'));

        $buttonUrl = urlHandlerBuild(['action' => 'newAward']);

        $buttonText = $lang->ougcAwardsControlPanelButtonNewAward;

        $actionButtons .= eval(getTemplate('controlPanelButtons'));

        $buttonUrl = urlHandlerBuild(['action' => 'manageTasks']);

        $buttonText = $lang->ougcAwardsControlPanelButtonManageTasks;

        $actionButtons .= eval(getTemplate('controlPanelButtons'));
    }

    $pageTitle = $lang->ougcAwardsControlPanelTitle;
}

if (!empty($errorMessages)) {
    $errorMessages = inline_error($errorMessages);
} else {
    $errorMessages = '';
}

$pageContents = eval(getTemplate('controlPanelContents'));

$pageContents = eval(getTemplate('controlPanel'));

$plugins->run_hooks('ougcAwards_PageEnd');

output_page($pageContents);

exit;