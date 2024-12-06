<?php

/***************************************************************************
 *
 *    ougc Awards plugin (/awards.php)
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
use function ougc\Awards\Core\awardGetUser;
use function ougc\Awards\Core\awardInsert;
use function ougc\Awards\Core\awardsCacheGet;
use function ougc\Awards\Core\awardsGetCache;
use function ougc\Awards\Core\awardUpdate;
use function ougc\Awards\Core\cacheUpdate;
use function ougc\Awards\Core\canManageUsers;
use function ougc\Awards\Core\canRequestAwards;
use function ougc\Awards\Core\canViewMainPage;
use function ougc\Awards\Core\categoryDelete;
use function ougc\Awards\Core\categoryGet;
use function ougc\Awards\Core\categoryGetCache;
use function ougc\Awards\Core\categoryInsert;
use function ougc\Awards\Core\categoryUpdate;
use function ougc\Awards\Core\generateSelectAwards;
use function ougc\Awards\Core\generateSelectCategory;
use function ougc\Awards\Core\generateSelectProfileFields;
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
use function ougc\Awards\Core\logGet;
use function ougc\Awards\Core\ownerCategoryDelete;
use function ougc\Awards\Core\ownerCategoryFind;
use function ougc\Awards\Core\ownerCategoryGetSingle;
use function ougc\Awards\Core\ownerCategoryGetUser;
use function ougc\Awards\Core\ownerCategoryInsert;
use function ougc\Awards\Core\ownerDelete;
use function ougc\Awards\Core\ownerFind;
use function ougc\Awards\Core\ownerGetSingle;
use function ougc\Awards\Core\ownerGetUser;
use function ougc\Awards\Core\ownerInsert;
use function ougc\Awards\Core\parseMessage;
use function ougc\Awards\Core\parseUserAwards;
use function ougc\Awards\Core\getUser;
use function ougc\Awards\Core\logAction;
use function ougc\Awards\Core\pluginIsInstalled;
use function ougc\Awards\Core\presetDelete;
use function ougc\Awards\Core\presetGet;
use function ougc\Awards\Core\presetInsert;
use function ougc\Awards\Core\requestApprove;
use function ougc\Awards\Core\requestGetPending;
use function ougc\Awards\Core\requestGetPendingTotal;
use function ougc\Awards\Core\requestInsert;
use function ougc\Awards\Core\requestReject;
use function ougc\Awards\Core\taskDelete;
use function ougc\Awards\Core\taskGet;
use function ougc\Awards\Core\taskInsert;
use function ougc\Awards\Core\taskUpdate;
use function ougc\Awards\Core\uploadAward;
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
use const ougc\Awards\Core\REQUEST_STATUS_ACCEPTED;
use const ougc\Awards\Core\REQUEST_STATUS_PENDING;
use const ougc\Awards\Core\REQUEST_STATUS_REJECTED;
use const ougc\Awards\Core\TASK_STATUS_ENABLED;

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

add_breadcrumb($lang->ougcAwardsPageNavigation, urlHandlerBuild());

$errorMessages = [];

$currentUserID = (int)$mybb->user['uid'];

$pageUrl = $formUrl = urlHandlerBuild();

$actionButtons = [];

$validActions = [
    'newCategory',
    'editCategory',
    'newAward',
    'editAward',
    'deleteAward',
    'viewUsers',
    'viewCategoryOwners',
    'viewOwners',
    'deleteCategoryOwner',
    'deleteOwner',
    'viewRequests',
    'editGrant',
    'viewAward',
    'requestAward',
];

$isCustomPage = false;

$plugins->run_hooks('ougc_awards_start');

$isCategoryOwner = false;

$isModerator = isModerator();

if (in_array($mybb->get_input('action'), $validActions)) {
    if (in_array($mybb->get_input('action'), ['deleteOwner'])) {
        if ($ownerData = ownerGetSingle(["oid='{$ownerID}'"])) {
            $awardID = (int)$ownerData['aid'];
        }
    }

    if (in_array($mybb->get_input('action'), ['editGrant'])) {
        if ($grantData = grantGetSingle(["gid='{$grantID}'"])) {
            $awardID = (int)$grantData['aid'];
        }
    }

    if (!in_array(
        $mybb->get_input('action'),
        ['newCategory', 'editCategory', 'newAward', 'viewCategoryOwners', 'deleteCategoryOwner']
    )) {
        if (!($awardData = awardGet($awardID)) || !isVisibleAward($awardID)) {
            error($lang->ougcAwardsErrorInvalidAward);
        }

        $categoryID = (int)$awardData['cid'];
    }

    if (ownerCategoryFind($categoryID, $currentUserID)) {
        $isCategoryOwner = true;
    }

    if (($isModerator || $isCategoryOwner) && $mybb->get_input('action') === 'deleteOwner') {
        if (empty($ownerData)) {
            error($lang->ougcAwardsErrorInvalidOwner);
        }

        $awardID = (int)$ownerData['aid'];
    }

    if (($isModerator || $isCategoryOwner) && $mybb->get_input('action') === 'editGrant') {
        if (!($grantData = grantGetSingle(["gid='{$grantID}'"]))) {
            error($lang->ougcAwardsErrorInvalidGrant);
        }

        $awardID = (int)$grantData['aid'];
    }

    if (!in_array($mybb->get_input('action'), ['newCategory', 'newAward'])) {
        if (!($categoryData = categoryGet($categoryID)) || !isVisibleCategory($categoryID)) {
            error($lang->ougcAwardsErrorInvalidCategory);
        }
    }

    if (!in_array($mybb->get_input('action'), ['newCategory', 'editCategory'])) {
        $awardData = awardGet($awardID);

        if (!empty($awardData)) {
            $awardName = htmlspecialchars_uni($awardData['name']);
        }
    }

    if (!empty($categoryData['name'])) {
        add_breadcrumb(
            $categoryData['name'],
            urlHandlerBuild(['action' => 'viewCategory', 'categoryID' => $categoryID])
        );
    }

    if (isset($awardName) && ($isModerator || $isCategoryOwner) && $mybb->get_input('action') === 'deleteOwner') {
        add_breadcrumb($awardName, urlHandlerBuild(['action' => 'deleteOwner', 'ownerID' => $ownerID]));
    } elseif (isset($awardName) && ($isModerator || $isCategoryOwner) && $mybb->get_input('action') === 'editGrant') {
        add_breadcrumb($awardName, urlHandlerBuild(['action' => 'editGrant', 'grantID' => $grantID]));
    } elseif (!empty($awardName)) {
        add_breadcrumb(
            $awardName,
            urlHandlerBuild(['action' => $mybb->get_input('action'), 'awardID' => $awardID])
        );
    }

    $isCustomPage = true;
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
    case 'viewCategoryOwners':
        add_breadcrumb($lang->ougcAwardsControlPanelCategoryOwnersTitle);
        break;
    case 'deleteCategoryOwner':
        add_breadcrumb($lang->ougcAwardsControlPanelDeleteOwnersTitle);
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

$requirementCriteria = [
    'usergroups' => [
        'languageVar' => 'ougcAwardsControlPanelNewTaskRequirementsGroups',
        'rowFunction' => function (
            string $selectName,
            array $selectedIDs
        ): string {
            global $cache;

            $selectName = 'usergroups[]';

            $selectOptions = '';

            foreach ($cache->read('usergroups') as $groupData) {
                $optionValue = (int)$groupData['gid'];

                $optionName = htmlspecialchars_uni($groupData['title']);

                $selectedElement = '';

                if (in_array($optionValue, $selectedIDs)) {
                    $selectedElement = ' selected="selected"';
                }

                $selectOptions .= eval(getTemplate('selectFieldOption'));
            }

            $multipleOption = 'multiple="multiple"';

            $onChange = '';

            $inputField = eval(getTemplate('selectField'));

            global $lang;
            global $inputData;

            $inputName = $selectName = 'additionalgroups';

            $inputLabel = $lang->ougcAwardsControlPanelNewTaskRequirementsAdditionalGroups;

            $inputTitle = $lang->ougcAwardsControlPanelNewTaskRequirementsAdditionalGroupsDescription;

            $checked = '';

            if (!empty($inputData['additionalgroups'])) {
                $checked = ' checked="checked"';
            }

            $typeSelect = eval(getTemplate('radioField'));

            return $inputField . $typeSelect;
        }
    ],
    'threads' => [
        'languageVar' => 'ougcAwardsControlPanelNewTaskRequirementsThreadCount',
        'rowFunction' => function (
            string $inputName,
            array $inputValue
        ): string {
            $inputType = 'number';

            $inputValue = (int)$inputValue[0];

            $inputField = eval(getTemplate('inputField'));

            global $lang;
            global $inputData;

            $selectName = 'threadstype';

            $selectOptions = '';

            foreach (
                [
                    '>' => $lang->ougcAwardsControlPanelGreaterThan,
                    '>=' => $lang->ougcAwardsControlPanelGreaterThanOrEqualTo,
                    '=' => $lang->ougcAwardsControlPanelEqualTo,
                    '<=' => $lang->ougcAwardsControlPanelLessThanOrEqualTo,
                    '<' => $lang->ougcAwardsControlPanelLessThan,
                ] as $optionValue => $optionName
            ) {
                $selectedElement = '';

                if ($optionValue === $inputData[$selectName]) {
                    $selectedElement = ' selected="selected"';
                }

                $selectOptions .= eval(getTemplate('selectFieldOption'));
            }

            $onChange = $multipleOption = '';

            $typeSelect = eval(getTemplate('selectField'));

            return $inputField . $typeSelect;
        }
    ],
    'posts' => [
        'languageVar' => 'ougcAwardsControlPanelNewTaskRequirementsPostCount',
        'rowFunction' => function (
            string $inputName,
            array $selectedIDs
        ): string {
            $inputType = 'number';

            $inputValue = (int)$selectedIDs[0];

            $inputField = eval(getTemplate('inputField'));

            global $lang;
            global $inputData;

            $selectName = "{$inputName}type";

            $selectOptions = '';

            foreach (
                [
                    '>' => $lang->ougcAwardsControlPanelGreaterThan,
                    '>=' => $lang->ougcAwardsControlPanelGreaterThanOrEqualTo,
                    '=' => $lang->ougcAwardsControlPanelEqualTo,
                    '<=' => $lang->ougcAwardsControlPanelLessThanOrEqualTo,
                    '<' => $lang->ougcAwardsControlPanelLessThan,
                ] as $optionValue => $optionName
            ) {
                $selectedElement = '';

                if ($optionValue === $inputData[$selectName]) {
                    $selectedElement = ' selected="selected"';
                }

                $selectOptions .= eval(getTemplate('selectFieldOption'));
            }

            $onChange = $multipleOption = '';

            $typeSelect = eval(getTemplate('selectField'));

            return $inputField . $typeSelect;
        }
    ],
    'fthreads' => [
        'languageVar' => 'ougcAwardsControlPanelNewTaskRequirementsForumThreadCount',
        'rowFunction' => function (
            string $inputName,
            array $inputValue
        ): string {
            $inputType = 'number';

            $inputValue = (int)$inputValue[0];

            $inputField = eval(getTemplate('inputField'));

            global $lang;
            global $inputData;

            $selectName = "{$inputName}type";

            $selectOptions = '';

            foreach (
                [
                    '>' => $lang->ougcAwardsControlPanelGreaterThan,
                    '>=' => $lang->ougcAwardsControlPanelGreaterThanOrEqualTo,
                    '=' => $lang->ougcAwardsControlPanelEqualTo,
                    '<=' => $lang->ougcAwardsControlPanelLessThanOrEqualTo,
                    '<' => $lang->ougcAwardsControlPanelLessThan,
                ] as $optionValue => $optionName
            ) {
                $selectedElement = '';

                if ($optionValue === $inputData[$selectName]) {
                    $selectedElement = ' selected="selected"';
                }

                $selectOptions .= eval(getTemplate('selectFieldOption'));
            }

            $onChange = $multipleOption = '';

            $typeSelect = eval(getTemplate('selectField'));

            $selectName = "{$inputName}forums";

            $selectOptions = '';

            foreach (
                cache_forums() as $forumData
            ) {
                $optionValue = (int)$forumData['fid'];

                $optionName = htmlspecialchars_uni($forumData['name']);

                $selectedElement = '';

                if ($optionValue === (int)$inputData[$selectName]) {
                    $selectedElement = ' selected="selected"';
                }

                $selectOptions .= eval(getTemplate('selectFieldOption'));
            }

            $onChange = $multipleOption = '';

            $forumSelect = eval(getTemplate('selectField'));

            return $inputField . $typeSelect . $forumSelect;
        }
    ],
    'fposts' => [
        'languageVar' => 'ougcAwardsControlPanelNewTaskRequirementsForumPostCount',
        'rowFunction' => function (
            string $inputName,
            array $inputValue
        ): string {
            $inputType = 'number';

            $inputValue = (int)$inputValue[0];

            $inputField = eval(getTemplate('inputField'));

            global $lang;
            global $inputData;

            $selectName = "{$inputName}type";

            $selectOptions = '';

            foreach (
                [
                    '>' => $lang->ougcAwardsControlPanelGreaterThan,
                    '>=' => $lang->ougcAwardsControlPanelGreaterThanOrEqualTo,
                    '=' => $lang->ougcAwardsControlPanelEqualTo,
                    '<=' => $lang->ougcAwardsControlPanelLessThanOrEqualTo,
                    '<' => $lang->ougcAwardsControlPanelLessThan,
                ] as $optionValue => $optionName
            ) {
                $selectedElement = '';

                if ($optionValue === $inputData[$selectName]) {
                    $selectedElement = ' selected="selected"';
                }

                $selectOptions .= eval(getTemplate('selectFieldOption'));
            }

            $onChange = $multipleOption = '';

            $typeSelect = eval(getTemplate('selectField'));

            $selectName = "{$inputName}forums";

            $selectOptions = '';

            foreach (
                cache_forums() as $forumData
            ) {
                $optionValue = (int)$forumData['fid'];

                $optionName = htmlspecialchars_uni($forumData['name']);

                $selectedElement = '';

                if ($optionValue === (int)$inputData[$selectName]) {
                    $selectedElement = ' selected="selected"';
                }

                $selectOptions .= eval(getTemplate('selectFieldOption'));
            }

            $onChange = $multipleOption = '';

            $forumSelect = eval(getTemplate('selectField'));

            return $inputField . $typeSelect . $forumSelect;
        }
    ],
    'registered' => [
        'languageVar' => 'ougcAwardsControlPanelNewTaskRequirementsTimeRegistered',
        'rowFunction' => function (
            string $inputName,
            array $inputValue
        ): string {
            $inputType = 'number';

            $inputValue = (int)$inputValue[0];

            $inputField = eval(getTemplate('inputField'));

            global $lang;
            global $inputData;

            $selectName = "{$inputName}type";

            $selectOptions = '';

            foreach (
                [
                    'hours' => $lang->ougcAwardsControlPanelHours,
                    'days' => $lang->ougcAwardsControlPanelDays,
                    'weeks' => $lang->ougcAwardsControlPanelWeeks,
                    'months' => $lang->ougcAwardsControlPanelMonths,
                    'years' => $lang->ougcAwardsControlPanelYears,
                ] as $optionValue => $optionName
            ) {
                $selectedElement = '';

                if ($optionValue === $inputData[$selectName]) {
                    $selectedElement = ' selected="selected"';
                }

                $selectOptions .= eval(getTemplate('selectFieldOption'));
            }

            $onChange = $multipleOption = '';

            $typeSelect = eval(getTemplate('selectField'));

            return $inputField . $typeSelect;
        }
    ],
    'online' => [
        'languageVar' => 'ougcAwardsControlPanelNewTaskRequirementsTimeOnline',
        'rowFunction' => function (
            string $inputName,
            array $inputValue
        ): string {
            $inputType = 'number';

            $inputValue = (int)$inputValue[0];

            $inputField = eval(getTemplate('inputField'));

            global $lang;
            global $inputData;

            $selectName = "{$inputName}type";

            $selectOptions = '';

            foreach (
                [
                    'hours' => $lang->ougcAwardsControlPanelHours,
                    'days' => $lang->ougcAwardsControlPanelDays,
                    'weeks' => $lang->ougcAwardsControlPanelWeeks,
                    'months' => $lang->ougcAwardsControlPanelMonths,
                    'years' => $lang->ougcAwardsControlPanelYears,
                ] as $optionValue => $optionName
            ) {
                $selectedElement = '';

                if ($optionValue === $inputData[$selectName]) {
                    $selectedElement = ' selected="selected"';
                }

                $selectOptions .= eval(getTemplate('selectFieldOption'));
            }

            $onChange = $multipleOption = '';

            $typeSelect = eval(getTemplate('selectField'));

            return $inputField . $typeSelect;
        }
    ],
    'reputation' => [
        'languageVar' => 'ougcAwardsControlPanelNewTaskRequirementsReputation',
        'rowFunction' => function (
            string $inputName,
            array $inputValue
        ): string {
            $inputType = 'number';

            $inputValue = (int)$inputValue[0];

            $inputField = eval(getTemplate('inputField'));

            global $lang;
            global $inputData;

            $selectName = "{$inputName}type";

            $selectOptions = '';

            foreach (
                [
                    '>' => $lang->ougcAwardsControlPanelGreaterThan,
                    '>=' => $lang->ougcAwardsControlPanelGreaterThanOrEqualTo,
                    '=' => $lang->ougcAwardsControlPanelEqualTo,
                    '<=' => $lang->ougcAwardsControlPanelLessThanOrEqualTo,
                    '<' => $lang->ougcAwardsControlPanelLessThan,
                ] as $optionValue => $optionName
            ) {
                $selectedElement = '';

                if ($optionValue === $inputData[$selectName]) {
                    $selectedElement = ' selected="selected"';
                }

                $selectOptions .= eval(getTemplate('selectFieldOption'));
            }

            $onChange = $multipleOption = '';

            $typeSelect = eval(getTemplate('selectField'));

            return $inputField . $typeSelect;
        }
    ],
    'referrals' => [
        'languageVar' => 'ougcAwardsControlPanelNewTaskRequirementsReferrals',
        'rowFunction' => function (
            string $inputName,
            array $inputValue
        ): string {
            $inputType = 'number';

            $inputValue = (int)$inputValue[0];

            $inputField = eval(getTemplate('inputField'));

            global $lang;
            global $inputData;

            $selectName = "{$inputName}type";

            $selectOptions = '';

            foreach (
                [
                    '>' => $lang->ougcAwardsControlPanelGreaterThan,
                    '>=' => $lang->ougcAwardsControlPanelGreaterThanOrEqualTo,
                    '=' => $lang->ougcAwardsControlPanelEqualTo,
                    '<=' => $lang->ougcAwardsControlPanelLessThanOrEqualTo,
                    '<' => $lang->ougcAwardsControlPanelLessThan,
                ] as $optionValue => $optionName
            ) {
                $selectedElement = '';

                if ($optionValue === $inputData[$selectName]) {
                    $selectedElement = ' selected="selected"';
                }

                $selectOptions .= eval(getTemplate('selectFieldOption'));
            }

            $onChange = $multipleOption = '';

            $typeSelect = eval(getTemplate('selectField'));

            return $inputField . $typeSelect;
        }
    ],
    'warnings' => [
        'languageVar' => 'ougcAwardsControlPanelNewTaskRequirementsWarningPoints',
        'rowFunction' => function (
            string $inputName,
            array $inputValue
        ): string {
            $inputType = 'number';

            $inputValue = (int)$inputValue[0];

            $inputField = eval(getTemplate('inputField'));

            global $lang;
            global $inputData;

            $selectName = "{$inputName}type";

            $selectOptions = '';

            foreach (
                [
                    '>' => $lang->ougcAwardsControlPanelGreaterThan,
                    '>=' => $lang->ougcAwardsControlPanelGreaterThanOrEqualTo,
                    '=' => $lang->ougcAwardsControlPanelEqualTo,
                    '<=' => $lang->ougcAwardsControlPanelLessThanOrEqualTo,
                    '<' => $lang->ougcAwardsControlPanelLessThan,
                ] as $optionValue => $optionName
            ) {
                $selectedElement = '';

                if ($optionValue === $inputData[$selectName]) {
                    $selectedElement = ' selected="selected"';
                }

                $selectOptions .= eval(getTemplate('selectFieldOption'));
            }

            $onChange = $multipleOption = '';

            $typeSelect = eval(getTemplate('selectField'));

            return $inputField . $typeSelect;
        }
    ],
    /*'newpoints' => [
        'languageVar' => 'ougcAwardsControlPanelNewTaskRequirementsNewpoints'
    ],*/
    'previousawards' => [
        'languageVar' => 'ougcAwardsControlPanelNewTaskRequirementsPreviousAwards',
        'rowFunction' => function (
            string $inputName,
            array $inputValue
        ): string {
            global $inputData;

            return generateSelectAwards(
                "{$inputName}[]",
                (array)$inputData[$inputName],
                ['multiple' => true]
            );
        }
    ],
    'profilefields' => [
        'languageVar' => 'ougcAwardsControlPanelNewTaskRequirementsFilledProfileFields',
        'rowFunction' => function (
            string $inputName,
            array $inputValue
        ): string {
            global $inputData;

            return generateSelectProfileFields(
                "{$inputName}[]",
                (array)$inputData[$inputName],
                ['multiple' => true, 'id' => $inputName]
            );
        }
    ],
    /*'mydownloads' => [
        'languageVar' => 'ougcAwardsControlPanelNewTaskRequirementsMyDownloads',
        'rowFunction' => function (
            string $inputName,
            array $inputValue
        ): string {
            $inputType = 'number';

            $inputValue = (int)$inputValue[0];

            return eval(getTemplate('inputField'));
        }
    ],
    'myarcadechampions' => [
        'languageVar' => 'ougcAwardsControlPanelNewTaskRequirementsMyArcadeChampions',
        'rowFunction' => function (
            string $inputName,
            array $inputValue
        ): string {
            $inputType = 'number';

            $inputValue = (int)$inputValue[0];

            return eval(getTemplate('inputField'));
        }
    ],
    'myarcadescores' => [
        'languageVar' => 'ougcAwardsControlPanelNewTaskRequirementsMyArcadeScores',
        'rowFunction' => function (
            string $inputName,
            array $inputValue
        ): string {
            $inputType = 'number';

            $inputValue = (int)$inputValue[0];

            return eval(getTemplate('inputField'));
        }
    ],
    'ougc_customrep_r' => [
        'languageVar' => 'ougcAwardsControlPanelNewTaskRequirementsCustomReputationReceived',
        'rowFunction' => function (
            string $inputName,
            array $inputValue
        ): string {
            $inputType = 'number';

            $inputValue = (int)$inputValue[0];

            return eval(getTemplate('inputField'));
        }
    ],
    'ougc_customrep_g' => [
        'languageVar' => 'ougcAwardsControlPanelNewTaskRequirementsCustomReputationGiven',
        'rowFunction' => function (
            string $inputName,
            array $inputValue
        ): string {
            $inputType = 'number';

            $inputValue = (int)$inputValue[0];

            return eval(getTemplate('inputField'));
        }
    ],*/
    'ruleScripts' => [
        'languageVar' => 'ougcAwardsControlPanelNewTaskRequirementsRuleScripts',
        'rowFunction' => function (
            string $inputName,
            array $inputValue
        ): string {
            global $lang;

            $inputValue = htmlspecialchars_uni((string)$inputValue[0]);

            $inputRows = 10;

            $inputPlaceholder = str_replace(
                '"',
                '&quot;',
                $lang->ougcAwardsControlPanelNewTaskRequirementsRuleScriptsDescriptionPlaceHolder
            );

            return eval(getTemplate('textAreaField'));
        }
    ],
];

$plugins->run_hooks('ougc_awards_intermediate');

if (in_array($mybb->get_input('action'), ['newCategory', 'editCategory'])) {
    if (!isModerator()) {
        error_no_permission();
    }

    $newCategoryPage = $mybb->get_input('action') === 'newCategory';

    $inputData = [];

    $plugins->run_hooks('ougc_awards_edit_category_start');

    foreach (['name', 'description', 'allowrequests', 'visible', 'disporder'] as $inputKey) {
        if ($mybb->request_method === 'post') {
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
                'name' => $db->escape_string($inputData['name']),
                'description' => $db->escape_string($inputData['description']),
                'disporder' => (int)$inputData['disporder'],
                'allowrequests' => (int)$inputData['allowrequests'],
                'visible' => (int)$inputData['visible'],
            ];

            $plugins->run_hooks('ougc_awards_edit_category_commit_start');

            if ($newCategoryPage) {
                categoryInsert($categoryData);
            } else {
                categoryUpdate($categoryData, $categoryID);
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
        if ($mybb->request_method === 'post') {
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

    $additionalRows = [];

    $rowBackground = alt_trow(true);

    $plugins->run_hooks('ougc_awards_edit_category_end');

    $additionalRows = implode(' ', $additionalRows);

    $pageContents = eval(getTemplate('controlPanelNewEditCategoryForm'));
} elseif ($mybb->get_input('action') === 'deleteCategory') {
    if (!isModerator()) {
        error_no_permission();
    }

    if ($mybb->request_method === 'post') {
        verify_post_check($mybb->get_input('my_post_key'));

        categoryDelete($categoryID);

        cacheUpdate();

        logAction();

        redirect(urlHandlerBuild(), $lang->ougcAwardsRedirectCategoryDeleted);
    }

    $categoryAwardsObjects = awardsGetCache(["cid='{$categoryID}'"], '*', ['order_by' => 'disporder']);

    $awardIDs = array_column($categoryAwardsObjects, 'aid');

    $awardsTotal = count($awardIDs);

    $awardIDs = implode("','", $awardIDs);

    $pendingRequestTotal = my_number_format(
        requestGetPendingTotal(
            ["aid IN ('{$awardIDs}')"]
        )
    );

    $grantedTotal = grantGetSingle(["aid IN ('{$awardIDs}')"], 'COUNT(*) AS grantedTotal');

    if (!empty($grantedTotal['grantedTotal'])) {
        $grantedTotal = my_number_format($grantedTotal['grantedTotal']);
    } else {
        $grantedTotal = 0;
    }

    $ownersCategoryTotal = ownerCategoryGetSingle(["categoryID='{$categoryID}'"], 'COUNT(*) AS ownersTotal');

    if (!empty($ownersCategoryTotal['ownersTotal'])) {
        $ownersCategoryTotal = my_number_format($ownersCategoryTotal['ownersTotal']);
    } else {
        $ownersCategoryTotal = 0;
    }

    $ownersTotal = ownerGetSingle(["aid IN ('{$awardIDs}')"], 'COUNT(*) AS ownersTotal');

    if (!empty($ownersTotal['ownersTotal'])) {
        $ownersTotal = my_number_format($ownersTotal['ownersTotal']);
    } else {
        $ownersTotal = 0;
    }

    $pageTitle = $lang->ougcAwardsControlPanelDeleteCategoryTitle;

    $confirmationTitle = $lang->ougcAwardsControlPanelDeleteCategoryTableTitle;

    $confirmationButtonText = $lang->ougcAwardsControlPanelDeleteCategoryButton;

    $confirmationContent = eval(getTemplate('controlPanelConfirmationDeleteCategory'));

    $pageContents = eval(getTemplate('controlPanelConfirmation'));
} elseif (in_array($mybb->get_input('action'), ['newAward', 'editAward'])) {
    if (!($isModerator || $isCategoryOwner)) {
        error_no_permission();
    }

    $newAwardPage = $mybb->get_input('action') === 'newAward';

    //$categoryID = $mybb->get_input('cid', MyBB::INPUT_INT);

    $inputData = [];

    $plugins->run_hooks('ougc_awards_edit_award_start');

    foreach (
        [
            'cid',
            'categoryID',
            'name',
            'description',
            'image',
            'template',
            'allowrequests',
            'pm',
            'type'
        ] as $inputKey
    ) {
        if ($mybb->request_method === 'post') {
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

        if (empty($errorMessages) && !empty($_FILES['award_file']['name'])) {
            $upload = uploadAward($_FILES['award_file'], $awardID);
        }

        if (empty($errorMessages)) {
            $awardData = [
                'name' => $db->escape_string($inputData['name']),
                'cid' => (int)$inputData['categoryID'],
                'description' => $db->escape_string($inputData['description']),
                'image' => $db->escape_string($inputData['image']),
                'template' => (int)$inputData['template'],
                'allowrequests' => (int)$inputData['allowrequests'],
                'pm' => $db->escape_string($inputData['pm']),
                'type' => (int)$inputData['type'],
            ];

            if (!empty($upload['fileName'])) {
                $awardData['award_file'] = $db->escape_string($upload['fileName']);
            }

            $plugins->run_hooks('ougc_awards_edit_award_commit_start');

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

    foreach (['name', 'description', 'image', 'template', 'allowrequests', 'pm', 'type'] as $inputKey) {
        if ($mybb->request_method === 'post') {
            $inputData[$inputKey] = htmlspecialchars_uni($mybb->get_input($inputKey));
        } elseif (isset($awardData[$inputKey])) {
            $inputData[$inputKey] = htmlspecialchars_uni($awardData[$inputKey]);
        } else {
            $inputData[$inputKey] = '';
        }
    }

    $fileUploadRow = '';

    if (!$newAwardPage) {
        $fileUploadedNote = '';

        if (!empty($awardData['award_file'])) {
            $fileUploadedNote = $lang->ougcAwardsControlPanelNewAwardImageFileNote;
        }

        $fileUploadRow = eval(getTemplate('controlPanelNewEditAwardFormUpload'));
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

    $additionalRows = [];

    $rowBackground = alt_trow(true);

    $plugins->run_hooks('ougc_awards_edit_award_end');

    $additionalRows = implode(' ', $additionalRows);

    $pageContents = eval(getTemplate('controlPanelNewEditAwardForm'));
} elseif ($mybb->get_input('action') === 'deleteAward') {
    if (!($isModerator || $isCategoryOwner)) {
        error_no_permission();
    }

    if ($mybb->request_method === 'post') {
        verify_post_check($mybb->get_input('my_post_key'));

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
} elseif ($mybb->get_input('action') === 'myAwards') {
    if ($mybb->request_method === 'post') {
        $displayOrders = $mybb->get_input('displayOrder', MyBB::INPUT_ARRAY);

        $visibleStatuses = $mybb->get_input('visibleStatus', MyBB::INPUT_ARRAY);

        $updateGrandIDs = array_unique(array_merge(array_keys($displayOrders), array_keys($visibleStatuses)));

        foreach ($updateGrandIDs as $grantID) {
            $updateData = ['disporder' => (int)$displayOrders[$grantID]];

            if (isset($visibleStatuses[$grantID])) {
                $updateData['visible'] = \ougc\Awards\Core\GRANT_STATUS_VISIBLE;
            } else {
                $updateData['visible'] = \ougc\Awards\Core\GRANT_STATUS_NOT_VISIBLE;
            }

            $plugins->run_hooks('ougc_awards_my_awards_update_end');

            grantUpdate($updateData, $grantID);
        }

        redirect(
            urlHandlerBuild(['action' => 'myAwards']),
            $lang->ougcAwardsRedirectMyAwardsUpdated
        );
    }

    $totalGrantedCount = awardGetUser(
        ["uid='{$currentUserID}'"],
        'COUNT(gid) AS totalGranted',
        ['limit' => 1]
    );

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

    $userIDs = $threadsCache = [];

    $grantCacheData = awardGetUser(
        ["uid='{$currentUserID}'"],
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
    }

    $paginationMenu = (string)multipage(
        $totalGrantedCount,
        (int)getSetting('perpage'),
        $mybb->get_input('page', MyBB::INPUT_INT),
        urlHandlerBuild(['action' => 'myAwards'])
    );

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

    $rowColumnSpan = 7;

    foreach ($grantCacheData as $grantData) {
        $grantID = (int)$grantData['gid'];

        $awardID = (int)$grantData['aid'];

        $awardData = awardGet($awardID);

        $awardName = htmlspecialchars_uni($awardData['name']);

        $awardImage = $awardClass = awardGetIcon($awardID);

        $awardImage = eval(
        getTemplate(
            $awardData['template'] === AWARD_TEMPLATE_TYPE_CLASS ? 'awardImageClass' : 'awardImage'
        )
        );

        $awardUrl = urlHandlerBuild(['action' => 'viewUsers', 'awardID' => $awardID]);

        $awardImage = eval(getTemplate('awardWrapper', false));

        $grantReason = $grantData['reason'];

        parseMessage($grantReason);

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

            $threadLink = eval(getTemplate('controlPanelMyAwardsRowLink'));
        }

        $grantDate = my_date('normal', $grantData['date']);

        $displayOrder = (int)$grantData['disporder'];

        $checkedElement = '';

        $visibleStatus = (int)$grantData['visible'];

        if ($visibleStatus) {
            $checkedElement = 'checked="checked"';
        }

        $rowColumnsExtra = [];

        $plugins->run_hooks('ougc_awards_my_awards_row_end');

        $rowColumnsExtra = implode(' ', $rowColumnsExtra);

        $grantedList .= eval(getTemplate('controlPanelMyAwardsRow'));

        $alternativeBackground = alt_trow();
    }

    $rowHeadColumnsExtra = [];

    $plugins->run_hooks('ougc_awards_my_awards_end');

    $rowHeadColumnsExtra = implode(' ', $rowHeadColumnsExtra);

    if (!$grantedList) {
        $grantedList = eval(getTemplate('controlPanelMyAwardsEmpty'));
    }

    $pageTitle = $lang->ougcAwardsControlPanelUsersTitle;

    $formUrl = urlHandlerBuild(['action' => 'myAwards', 'awardID' => $awardID]);

    $columnHeader = $grantForm = $revokeForm = '';

    if (is_member(getSetting('allowedGroupsPresets'))) {
        $buttonUrl = urlHandlerBuild(['action' => 'viewPresets']);

        $buttonText = $lang->ougcAwardsControlPanelButtonManagePresets;

        $actionButtons[] = eval(getTemplate('controlPanelButtons'));
    }

    $pageContents = eval(getTemplate('controlPanelMyAwards'));
} elseif ($mybb->get_input('action') === 'viewUsers') {
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
        }

        if (isset($mybb->input['revoke'])) {
            foreach ($usersCache as $userData) {
                if (!grantFind($awardID, (int)$userData['uid'])) {
                    $errorMessages[] = $lang->ougcAwardsErrorInvalidGrant;

                    break;
                }
            }

            if (empty($errorMessages)) {
                foreach ($usersCache as $userData) {
                    if ($grantData = grantFind($awardID, (int)$userData['uid'])) {
                        grantDelete((int)$grantData['gid']);

                        logAction();
                    }
                }

                redirect(
                    urlHandlerBuild(['action' => 'viewUsers', 'awardID' => $awardID]),
                    $lang->ougcAwardsRedirectGrantRevoked
                );
            }
        } elseif (isset($mybb->input['grant'])) {
            if (empty($errorMessages)) {
                foreach ($usersCache as $userData) {
                    grantInsert(
                        $awardID,
                        (int)$userData['uid'],
                        $mybb->get_input('reason')
                    );

                    logAction();
                }

                redirect(
                    urlHandlerBuild(['action' => 'viewUsers', 'awardID' => $awardID]),
                    $lang->ougcAwardsRedirectGranted
                );
            }
        }

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

            redirect(
                urlHandlerBuild(['action' => 'viewUsers', 'awardID' => $awardID]),
                $lang->ougcAwardsRedirectGranted
            );
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
            //$userIDs[] = (int)$grantData['thread'];
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

        $userName = $userNameFormatted = $userProfileLink = '';

        if (!empty($usersCache[$grantData['uid']])) {
            $userData = $usersCache[$grantData['uid']];

            $userName = htmlspecialchars_uni($userData['username']);

            $userNameFormatted = format_name($userName, $userData['usergroup'], $userData['displaygroup']);

            $userProfileLink = build_profile_link($userNameFormatted, $userData['uid']);
        }

        $grantReason = $grantData['reason'];

        parseMessage($grantReason);

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

        $grantDate = my_date('normal', $grantData['date']);

        $editUrl = urlHandlerBuild(['action' => 'editGrant', 'grantID' => $grantID]);

        $columnRow = '';

        if (($isModerator || ownerCategoryFind($categoryID, $currentUserID))) {
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

    $theadColumSpan = 4;

    $columnHeader = $grantForm = $revokeForm = '';

    if (($isModerator || $isCategoryOwner)) {
        ++$theadColumSpan;

        $inputUserName = htmlspecialchars_uni($mybb->get_input('username'));

        $inputReason = htmlspecialchars_uni($mybb->get_input('reason'));

        $inputThread = htmlspecialchars_uni($mybb->get_input('thread'));

        $columnHeader = eval(getTemplate('controlPanelUsersColumnOptions'));

        $grantForm = eval(getTemplate('controlPanelUsersFormGrant'));

        $revokeForm = eval(getTemplate('controlPanelUsersFormRevoke'));
    }

    $pageContents = eval(getTemplate('controlPanelUsers'));
} elseif ($mybb->get_input('action') === 'viewCategoryOwners') {
    if (!isModerator()) {
        error_no_permission();
    }

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

            if (ownerCategoryFind($categoryID, (int)$userData['uid'])) {
                $errorMessages[] = $lang->ougcAwardsErrorDuplicatedCategoryOwner;

                break;
            }
        }

        if (empty($errorMessages)) {
            foreach ($usersCache as $userData) {
                ownerCategoryInsert($categoryID, (int)$userData['uid']);

                logAction();
            }

            redirect(
                urlHandlerBuild(['action' => 'viewCategoryOwners', 'categoryID' => $categoryID]),
                $lang->ougcAwardsRedirectOwnerAssigned
            );
        }
    }

    $totalOwnersCount = ownerCategoryGetUser(["categoryID='{$categoryID}'"],
        'COUNT(ownerID) AS totalOwners',
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

    $ownersCacheData = ownerCategoryGetUser(["categoryID='{$categoryID}'"],
        '*',
        [
            'limit' => (int)getSetting('perpage'),
            'limit_start' => $startPage,
            'order_by' => 'ownerDate',
            'order_dir' => 'desc'
        ]
    );

    foreach ($ownersCacheData as $ownerData) {
        if (!empty($ownerData['userID'])) {
            $userIDs[] = (int)$ownerData['userID'];
        }
    }

    $paginationMenu = (string)multipage(
        $totalOwnersCount,
        (int)getSetting('perpage'),
        $mybb->get_input('page', MyBB::INPUT_INT),
        urlHandlerBuild(['action' => 'viewUsers', 'categoryID' => $categoryID])
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
        $ownerID = (int)$ownerData['ownerID'];

        $userID = (int)$ownerData['userID'];

        $userName = $userNameFormatted = $userProfileLink = '';

        if (!empty($usersCache[$userID])) {
            $userData = $usersCache[$userID];

            $userName = htmlspecialchars_uni($userData['username']);

            $userNameFormatted = format_name($userName, $userData['usergroup'], $userData['displaygroup']);

            $userProfileLink = build_profile_link($userNameFormatted, $userData['uid']);
        }

        $ownerDate = my_date('normal', $ownerData['ownerDate']);

        $deleteUrl = urlHandlerBuild(
            ['action' => 'deleteCategoryOwner', 'categoryID' => $categoryID, 'ownerID' => $ownerID]
        );

        $ownersList .= eval(getTemplate('controlPanelOwnersRow'));

        $alternativeBackground = alt_trow();
    }

    if (!$ownersList) {
        $ownersList = eval(getTemplate('controlPanelOwnersEmpty'));
    }

    $pageTitle = $lang->ougcAwardsControlPanelOwnersTitle;

    $formUrl = urlHandlerBuild(['action' => 'viewCategoryOwners', 'categoryID' => $categoryID]);

    $inputUserName = htmlspecialchars_uni($mybb->get_input('username'));

    $pageContents = eval(getTemplate('controlPanelCategoryOwners'));
} elseif ($mybb->get_input('action') === 'deleteCategoryOwner') {
    if (!isModerator()) {
        error_no_permission();
    }

    if ($mybb->request_method === 'post') {
        verify_post_check($mybb->get_input('my_post_key'));

        ownerCategoryDelete($ownerID);

        cacheUpdate();

        logAction();

        redirect(
            urlHandlerBuild(['action' => 'viewCategoryOwners', 'categoryID' => $categoryID]),
            $lang->ougcAwardsRedirectCategoryOwnerRevoked
        );
    }

    $pageTitle = $lang->ougcAwardsControlPanelDeleteCategoryOwnersTitle;

    $confirmationTitle = $lang->ougcAwardsControlPanelDeleteCategoryOwnersDescription;

    $confirmationButtonText = $lang->ougcAwardsControlPanelDeleteCategoryOwnersButton;

    $confirmationContent = eval(getTemplate('controlPanelConfirmationDeleteOwner'));

    $pageContents = eval(getTemplate('controlPanelConfirmation'));
} elseif ($mybb->get_input('action') === 'viewOwners') {
    if (!($isModerator || $isCategoryOwner)) {
        error_no_permission();
    }

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

        $ownerDate = my_date('normal', $ownerData['date']);

        $deleteUrl = urlHandlerBuild(['action' => 'deleteOwner', 'ownerID' => $ownerID]);

        $ownersList .= eval(getTemplate('controlPanelOwnersRow'));

        $alternativeBackground = alt_trow();
    }

    if (!$ownersList) {
        $ownersList = eval(getTemplate('controlPanelOwnersEmpty'));
    }

    $pageTitle = $lang->ougcAwardsControlPanelOwnersTitle;

    $formUrl = urlHandlerBuild(['action' => 'viewOwners', 'awardID' => $awardID]);

    $inputUserName = htmlspecialchars_uni($mybb->get_input('username'));

    $pageContents = eval(getTemplate('controlPanelOwners'));
} elseif ($mybb->get_input('action') === 'deleteOwner') {
    if (!($isModerator || $isCategoryOwner)) {
        error_no_permission();
    }

    if ($mybb->request_method === 'post') {
        verify_post_check($mybb->get_input('my_post_key'));

        ownerDelete($ownerID);

        cacheUpdate();

        logAction();

        redirect(urlHandlerBuild(['action' => 'viewOwners', 'awardID' => $awardID]),
            $lang->ougcAwardsRedirectOwnerRevoked);
    }

    $pageTitle = $lang->ougcAwardsControlPanelDeleteOwnersTitle;

    $confirmationTitle = $lang->ougcAwardsControlPanelDeleteOwnersTitle;

    $confirmationButtonText = $lang->ougcAwardsControlPanelDeleteOwnersButton;

    $confirmationContent = eval(getTemplate('controlPanelConfirmationDeleteOwner'));

    $pageContents = eval(getTemplate('controlPanelConfirmation'));
} elseif ($mybb->get_input('action') == 'viewRequests') {
    if (!($isModerator || $isCategoryOwner) && !$isOwner) {
        error_no_permission();
    }

    $statusPending = REQUEST_STATUS_PENDING;

    $whereClauses = [
        "aid='{$awardID}'",
        'status' => "status='{$statusPending}'"
    ];

    if (!($isModerator || $isCategoryOwner) && $isOwner) {
        $whereClauses['uid'] = "uid='{$currentUserID}'";
    }

    $filterOptions = $mybb->get_input('filterOptions', MyBB::INPUT_ARRAY);

    $filterOptionsSelected = [
        'statusOpen' => '',
        'statusAccepted' => '',
        'statusRejected' => '',
    ];

    if (isset($filterOptions['status'])) {
        $filterOptions['status'] = (int)$filterOptions['status'];

        switch ($filterOptions['status']) {
            case REQUEST_STATUS_ACCEPTED:
                $statusAccepted = REQUEST_STATUS_ACCEPTED;

                $filterOptionsSelected['statusAccepted'] = ' selected="selected"';
                $whereClauses['status'] = "status='{$statusAccepted}'";
                break;
            case REQUEST_STATUS_REJECTED:
                $statusRejected = REQUEST_STATUS_REJECTED;

                $filterOptionsSelected['statusRejected'] = ' selected="selected"';
                $whereClauses['status'] = "status='{$statusRejected}'";
                break;
        }
    }

    $selectedRequestIDs = $mybb->get_input('selected', MyBB::INPUT_ARRAY);

    if ($mybb->request_method === 'post') {
        if (!$selectedRequestIDs) {
            $errorMessages[] = $lang->ougcAwardsErrorRequestsNoneSelected;
        } else {
            $selectedRequestIDs = implode("','", array_map('intval', array_keys($selectedRequestIDs)));

            $pendingRequestsCache = requestGetPending(
                ["rid IN ('{$selectedRequestIDs}')", "status='{$statusPending}'"]
            );

            foreach ($pendingRequestsCache as $requestData) {
                if (!canManageUsers((int)$requestData['uid'])) {
                    $errorMessages[] = $lang->ougcAwardsErrorNoUsersPermission;

                    break;
                }
            }
        }

        if (empty($errorMessages)) {
            if (isset($pendingRequestsCache)) {
                foreach ($pendingRequestsCache as $requestData) {
                    if ((int)$requestData['status'] !== REQUEST_STATUS_PENDING) {
                        continue;
                    }

                    if (isset($mybb->input['accept'])) {
                        requestApprove((int)$requestData['rid']);
                    } else {
                        requestReject((int)$requestData['rid']);
                    }

                    logAction();
                }
            }

            cacheUpdate();

            if ($mybb->get_input('accept')) {
                redirect(
                    urlHandlerBuild(['action' => 'viewRequests', 'awardID' => $awardID]),
                    $lang->ougcAwardsRedirectRequestAccepted
                );
            } else {
                redirect(
                    urlHandlerBuild(['action' => 'viewRequests', 'awardID' => $awardID]),
                    $lang->ougcAwardsRedirectRequestRejected
                );
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

    $paginationMenu = '';

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
            $mybb->get_input('page', MyBB::INPUT_INT),
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

            $awardName = htmlspecialchars_uni($awardData['name']);

            $requestMessage = htmlspecialchars_uni($requestData['message']);

            $awardImage = $awardClass = awardGetIcon($awardID);

            $awardImage = eval(
            getTemplate(
                $awardData['template'] === AWARD_TEMPLATE_TYPE_CLASS ? 'awardImageClass' : 'awardImage'
            )
            );

            $awardUrl = urlHandlerBuild(['action' => 'viewUsers', 'awardID' => $awardID]);

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
                case REQUEST_STATUS_PENDING:
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
} elseif ($mybb->get_input('action') === 'editGrant') {
    if (!($isModerator || $isCategoryOwner)) {
        error_no_permission();
    }

    $inputData = [];

    foreach (['thread', 'reason', 'date'] as $inputKey) {
        if ($mybb->request_method === 'post') {
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
                'reason' => $db->escape_string($inputData['reason']),
                'date' => (int)$inputData['date']
            ];

            if (!empty($threadData['tid'])) {
                $updateData['thread'] = (int)$threadData['tid'];
            }

            grantUpdate($updateData, $grantID);

            cacheUpdate();

            logAction();

            redirect(urlHandlerBuild(['action' => 'viewUsers', 'awardID' => $awardID]),
                $lang->ougcAwardsRedirectGrantedUpdated);
        }
    }

    foreach (['thread', 'reason', 'date'] as $inputKey) {
        if ($mybb->request_method === 'post') {
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
    if (!is_member(getSetting('allowedGroupsPresets'))) {
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
        if ($mybb->get_input('newPreset', MyBB::INPUT_INT) === 1) {
            if (getSetting('presets_maximum') <= $totalPresets) {
                error_no_permission();
            }

            $presetID = presetInsert([
                'name' => $db->escape_string($mybb->get_input('presetName')),
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

            if ($presetID === $optionValue) {
                $selectedElement = ' selected="selected"';
            }

            $selectOptions .= eval(getTemplate('selectFieldOption'));
        }

        $selectName = 'presetID';

        $onChange = 'this.form.submit()';

        $multipleOption = '';

        $presetOptions = eval(getTemplate('selectField'));

        if ($presetID && $presetID != $mybb->user['ougc_awards_preset']) {
            $setDefaultButton = eval(getTemplate('controlPanelPresetsDefault'));
        }

        $presetsList = eval(getTemplate('controlPanelPresetsSelect'));
    }

    if (getSetting('presets_maximum') > $totalPresets) {
        $newPresetForm = eval(getTemplate('controlPanelPresetsForm'));
    }

    $presetRows = '';

    if ($presetID) {
        $grantStatusVisible = GRANT_STATUS_VISIBLE;

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

        $grantCacheData = awardGetUser([
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

        foreach ($grantCacheData as $grantData) {
            $grantID = (int)$grantData['gid'];

            $awardID = (int)$grantData['aid'];

            $awardData = awardGet($awardID);

            $awardName = htmlspecialchars_uni($awardData['name']);

            $awardImage = $awardClass = awardGetIcon($awardID);

            $awardImage = eval(
            getTemplate(
                $awardData['template'] === AWARD_TEMPLATE_TYPE_CLASS ? 'awardImageClass' : 'awardImage'
            )
            );

            if (isset($presetVisibleAwards[$grantID])) {
                $visibleAwards .= eval(getTemplate('controlPanelPresetsAward', false));
            } else {
                $hiddenAwards .= eval(getTemplate('controlPanelPresetsAward', false));
            }
        }

        $presetRows = eval(getTemplate('controlPanelPresetsRow'));
    }

    $pageTitle = $lang->ougcAwardsControlPanelPresetsTitle;

    $inputMessage = htmlspecialchars_uni($mybb->get_input('message'));

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

        if ($queryLimit < 1) {
            $queryLimit = 10;
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

        if ($queryLimit && $totalGrantedCount) {
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
                "javascript: ougcAwards.ViewAll('{$userID}', '{page}');"
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

        $queryOptions['limit'] = $queryLimit;

        $queryOptions['limit_start'] = $startPage;

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
    if (!canRequestAwards($awardID, $categoryID)) {
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
            'aid' => (int)$awardID,
            'message' => $db->escape_string($mybb->get_input('message'))
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

        $disabledElement = '';

        $buttonContent = eval(getTemplate('pageRequestButton'));

        $awardImage = $awardClass = awardGetIcon($awardID);

        $awardImage = eval(
        getTemplate(
            $awardData['template'] === AWARD_TEMPLATE_TYPE_CLASS ? 'awardImageClass' : 'awardImage'
        )
        );

        $awardUrl = urlHandlerBuild(['action' => 'viewUsers', 'awardID' => $awardID]);

        $awardImage = eval(getTemplate('awardWrapper', false));

        $formContents = eval(getTemplate('pageRequestForm'));
    }

    $modalContents = eval(getTemplate('pageRequest', false));

    echo $modalContents;

    exit;
} elseif (in_array($mybb->get_input('action'), ['newTask', 'editTask'])) {
    if (!isModerator()) {
        error_no_permission();
    }

    $newTaskPage = $mybb->get_input('action') === 'newTask';

    $taskData = taskGet(["tid='{$taskID}'"], '*', ['limit' => 1]);

    if (!$newTaskPage && empty($taskData['tid'])) {
        error($lang->ougcAwardsErrorInvalidTask);
    }

    $inputData = [];

    foreach (
        [
            'name',
            'description',
            'reason',
            'threadstype',
            'poststype',
            'fthreadstype',
            'fthreadsforums',
            'fpoststype',
            'fpostsforums',
            'registeredtype',
            'onlinetype',
            'reputationtype',
            'referralstype',
            'warningstype',
            //'newpointstype',
            //'mydownloadstype',
            //'myarcadechampionstype',
            //'myarcadescorestype',
            //'ougc_customreptype_r',
            //'ougc_customrepids_r',
            //'ougc_customreptype_g',
            //'ougc_customrepids_g',
            'ruleScripts',
        ] as $inputKey
    ) {
        if ($mybb->request_method === 'post') {
            $inputData[$inputKey] = $mybb->get_input($inputKey);
        } elseif (isset($taskData[$inputKey])) {
            $inputData[$inputKey] = $taskData[$inputKey];
        } else {
            $inputData[$inputKey] = '';
        }
    }

    foreach (
        [
            //'newpoints',
        ] as $inputKey
    ) {
        if ($mybb->request_method === 'post') {
            $inputData[$inputKey] = $mybb->get_input($inputKey, MyBB::INPUT_FLOAT);
        } elseif (isset($taskData[$inputKey])) {
            $inputData[$inputKey] = (float)$taskData[$inputKey];
        } else {
            $inputData[$inputKey] = 0;
        }
    }

    foreach (
        [
            'tid',
            'active',
            'logging',
            'thread',
            'allowmultiple',
            'disporder',
            'additionalgroups',
            'threads',
            'posts',
            'fthreads',
            'fposts',
            'registered',
            'online',
            'reputation',
            'referrals',
            'warnings',
            //'mydownloads',
            //'myarcadechampions',
            //'myarcadescores',
            //'ougc_customrep_r',
            //'ougc_customrep_g',
        ] as $inputKey
    ) {
        if ($mybb->request_method === 'post') {
            $inputData[$inputKey] = $mybb->get_input($inputKey, MyBB::INPUT_INT);
        } elseif (isset($taskData[$inputKey])) {
            $inputData[$inputKey] = (int)$taskData[$inputKey];
        } else {
            $inputData[$inputKey] = 0;
        }
    }

    foreach (
        [
            'requirements',
            'give',
            'revoke',
            'usergroups',
            'previousawards',
            'profilefields',
        ] as $inputKey
    ) {
        if ($mybb->request_method === 'post') {
            $inputData[$inputKey] = $mybb->get_input($inputKey, MyBB::INPUT_ARRAY);
        } elseif (isset($taskData[$inputKey])) {
            $inputData[$inputKey] = explode(',', $taskData[$inputKey]);
        } else {
            $inputData[$inputKey] = [];
        }
    }

    if ($mybb->request_method === 'post') {
        verify_post_check($mybb->get_input('my_post_key'));

        if (my_strlen($inputData['name']) > 100 || my_strlen($inputData['name']) < 1) {
            $errorMessages[] = $lang->ougcAwardsErrorInvalidTaskName;
        }

        if (my_strlen($inputData['description']) > 255 || my_strlen($inputData['description']) < 1) {
            $errorMessages[] = $lang->ougcAwardsErrorInvalidTaskDescription;
        }

        if (!empty($inputData['ruleScripts']) && !json_decode($inputData['ruleScripts'])) {
            $errorMessages[] = $lang->ougcAwardsErrorInvalidTaskScript;
        }

        if (empty($errorMessages)) {
            $insertData = [
                'name' => $inputData['name'],
                'description' => $inputData['description'],
                'active' => $inputData['active'],
                'requirements' => $inputData['requirements'],
                'give' => $inputData['give'],
                'reason' => $inputData['reason'],
                'thread' => $inputData['thread'],
                'allowmultiple' => $inputData['allowmultiple'],
                'revoke' => $inputData['revoke'],
                'disporder' => $inputData['disporder'],
                'usergroups' => $inputData['usergroups'],
                'additionalgroups' => $inputData['additionalgroups'],
                'threads' => $inputData['threads'],
                'threadstype' => $inputData['threadstype'],
                'posts' => $inputData['posts'],
                'poststype' => $inputData['poststype'],
                'fthreads' => $inputData['fthreads'],
                'fthreadstype' => $inputData['fthreadstype'],
                'fthreadsforums' => $inputData['fthreadsforums'],
                'fposts' => $inputData['fposts'],
                'fpoststype' => $inputData['fpoststype'],
                'fpostsforums' => $inputData['fpostsforums'],
                'registered' => $inputData['registered'],
                'registeredtype' => $inputData['registeredtype'],
                'online' => $inputData['online'],
                'onlinetype' => $inputData['onlinetype'],
                'reputation' => $inputData['reputation'],
                'reputationtype' => $inputData['reputationtype'],
                'referrals' => $inputData['referrals'],
                'referralstype' => $inputData['referralstype'],
                'warnings' => $inputData['warnings'],
                'warningstype' => $inputData['warningstype'],
                //'newpoints' => $inputData['newpoints'],
                //'newpointstype' => $inputData['newpointstype'],
                'previousawards' => $inputData['previousawards'],
                'profilefields' => $inputData['profilefields'],
                //'mydownloads' => $inputData['mydownloads'],
                //'mydownloadstype' => $inputData['mydownloadstype'],
                //'myarcadechampions' => $inputData['myarcadechampions'],
                //'myarcadechampionstype' => $inputData['myarcadechampionstype'],
                //'myarcadescores' => $inputData['myarcadescores'],
                //'myarcadescorestype' => $inputData['myarcadescorestype'],
                //'ougc_customrep_r' => $inputData['ougc_customrep_r'],
                //'ougc_customreptype_r' => $inputData['ougc_customreptype_r'],
                //'ougc_customrepids_r' => $inputData['ougc_customrepids_r'],
                //'ougc_customrep_g' => $inputData['ougc_customrep_g'],
                //'ougc_customreptype_g' => $inputData['ougc_customreptype_g'],
                //'ougc_customrepids_g' => $inputData['ougc_customrepids_g'],
                'ruleScripts' => $inputData['ruleScripts'],
            ];

            _dump($insertData['onlinetype']);
            if ($newTaskPage) {
                taskInsert($insertData);
            } else {
                taskUpdate($insertData, $taskID);
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

    /*
    foreach (
        [
            'name',
            'description',
            'reason',
            'threadstype',
            'poststype',
            'fthreadstype',
            'fthreadsforums',
            'fpoststype',
            'fpostsforums',
            'registeredtype',
            'onlinetype',
            'reputationtype',
            'referralstype',
            'warningstype',
            //'newpointstype',
            //'mydownloadstype',
            //'myarcadechampionstype',
            //'myarcadescorestype',
            //'ougc_customreptype_r',
            //'ougc_customrepids_r',
            //'ougc_customreptype_g',
            //'ougc_customrepids_g',
            'ruleScripts',
        ] as $inputKey
    ) {
        if ($mybb->request_method === 'post') {
            $inputData[$inputKey] = $mybb->get_input($inputKey);
        } elseif (isset($taskData[$inputKey])) {
            $inputData[$inputKey] = $taskData[$inputKey];
        } else {
            $inputData[$inputKey] = '';
        }
    }*/

    $selectedElementEnabledYes = $selectedElementEnabledNo = '';

    switch ($inputData['active']) {
        case TASK_STATUS_ENABLED:
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
        $requirementCriteria as $requirementKey => $requirementOption
    ) {
        $optionValue = $requirementKey;

        $optionName = $lang->{$requirementOption['languageVar']};

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

    $awardsGrantSelect = generateSelectAwards('give[]', (array)$inputData['give'], ['multiple' => true]);

    $selectedElementMultipleYes = $selectedElementMultipleNo = '';

    switch ($inputData['allowmultiple']) {
        case TASK_ALLOW_MULTIPLE:
            $selectedElementMultipleYes = 'checked="checked"';
            break;
        default:
            $selectedElementMultipleNo = 'checked="checked"';
            break;
    }

    $awardsRevokeSelect = generateSelectAwards('revoke[]', (array)$inputData['revoke'], ['multiple' => true]
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

    $requirementRows = '';

    foreach (
        $requirementCriteria as $requirementKey => $requirementOption
    ) {
        $optionName = $lang->{$requirementOption['languageVar']};

        $optionDescription = $lang->{"{$requirementOption['languageVar']}Description"};

        $selectedElement = '';

        if (isset($selectedRequirements[$requirementKey])) {
            $selectedElement = ' selected="selected"';
        }

        $inputRow = '';

        if (isset($requirementOption['rowFunction'])) {
            $inputRow = $requirementOption['rowFunction']($requirementKey, (array)$inputData[$requirementKey]);
        }

        $requirementRows .= eval(getTemplate('controlPanelNewEditTaskFormRequirementRow'));
    }

    $pageContents = eval(getTemplate('controlPanelNewEditTaskForm'));
} elseif ($mybb->get_input('action') === 'deleteTask') {
    if (!isModerator()) {
        error_no_permission();
    }

    $taskData = taskGet(["tid='{$taskID}'"], '*', ['limit' => 1]);

    if (empty($taskData['tid'])) {
        error($lang->ougcAwardsErrorInvalidTask);
    }

    if ($mybb->request_method === 'post') {
        verify_post_check($mybb->get_input('my_post_key'));

        taskDelete($taskID);

        cacheUpdate();

        logAction();

        redirect(urlHandlerBuild(['action' => 'manageTasks']), $lang->ougcAwardsRedirectTaskDeleted);
    }

    $pageTitle = $lang->ougcAwardsControlPanelDeleteAwardTitle;

    $confirmationTitle = $lang->ougcAwardsControlPanelDeleteTaskTableTitle;

    $confirmationButtonText = $lang->ougcAwardsControlPanelDeleteTaskTableButton;

    $confirmationContent = $lang->ougcAwardsControlPanelDeleteTaskTableDescription;

    $pageContents = eval(getTemplate('controlPanelConfirmation'));
} elseif ($mybb->get_input('action') === 'manageTasks') {
    if (!isModerator()) {
        error_no_permission();
    }

    $alternativeBackground = alt_trow(true);

    $taskRows = '';

    foreach (taskGet() as $taskData) {
        $taskID = (int)$taskData['tid'];

        $taskName = htmlspecialchars_uni($taskData['name']);

        $taskDescription = htmlspecialchars_uni($taskData['description']);

        $taskRequirements = array_flip(explode(',', $taskData['requirements']));

        foreach ($taskRequirements as $taskRequirementKey => &$taskRequirementValue) {
            if (isset($lang->{$requirementCriteria[$taskRequirementKey]['languageVar']})) {
                $taskRequirementValue = $lang->{$requirementCriteria[$taskRequirementKey]['languageVar']};
            }
        }

        $taskRequirements = implode($lang->comma, $taskRequirements);

        $checkedElement = $taskGrantAwards = $taskRevokeAwards = '';

        $taskGrantAwardIDs = implode("','", array_map('intval', explode(',', $taskData['give'])));

        foreach (awardsGetCache(["aid IN ('{$taskGrantAwardIDs}')"]) as $awardID => $awardData) {
            $awardName = htmlspecialchars_uni($awardData['name']);

            $awardImage = $awardClass = awardGetIcon($awardID);

            $awardImage = eval(
            getTemplate(
                $awardData['template'] === AWARD_TEMPLATE_TYPE_CLASS ? 'awardImageClass' : 'awardImage'
            )
            );

            $awardUrl = urlHandlerBuild(['action' => 'viewUsers', 'awardID' => $awardID]);

            $awardImage = eval(getTemplate('awardWrapper', false));

            $taskGrantAwards .= $awardImage;
        }


        $taskRevokeAwardIDs = implode("','", array_map('intval', explode(',', $taskData['revoke'])));

        foreach (awardsGetCache(["aid IN ('{$taskRevokeAwardIDs}')"]) as $awardID => $awardData) {
            $awardImage = $awardClass = awardGetIcon($awardID);

            $awardImage = eval(
            getTemplate(
                $awardData['template'] === AWARD_TEMPLATE_TYPE_CLASS ? 'awardImageClass' : 'awardImage'
            )
            );

            $awardUrl = urlHandlerBuild(['action' => 'viewUsers', 'awardID' => $awardID]);

            $awardImage = eval(getTemplate('awardWrapper', false));

            $taskRevokeAwards .= eval(getTemplate('awardWrapper', false));
        }

        if (!empty($taskData['active'])) {
            $checkedElement = 'checked="$checkedElement"';
        }

        $taskRevoke = htmlspecialchars_uni($taskData['revoke']);

        $taskStatus = (int)$taskData['active'];

        $viewLogsUrl = urlHandlerBuild(['action' => 'taskLogs', 'taskID' => $taskID]);

        $editUrl = urlHandlerBuild(['action' => 'editTask', 'taskID' => $taskID]);

        $deleteUrl = urlHandlerBuild(['action' => 'deleteTask', 'taskID' => $taskID]);

        $taskRows .= eval(getTemplate('controlPanelTasksRow'));

        $alternativeBackground = alt_trow();
    }

    $buttonUrl = urlHandlerBuild(['action' => 'newTask']);

    $buttonText = $lang->ougcAwardsControlPanelButtonNewTask;

    $actionButtons[] = eval(getTemplate('controlPanelButtons'));

    $pageTitle = $lang->ougcAwardsControlPanelTasksTitle;

    $pageContents = eval(getTemplate('controlPanelTasks'));
} elseif ($mybb->get_input('action') === 'taskLogs') {
    if (!isModerator()) {
        error_no_permission();
    }

    $taskData = taskGet(["tid='{$taskID}'"], '*', ['limit' => 1]);

    if (empty($taskData['tid'])) {
        error($lang->ougcAwardsErrorInvalidTask);
    }

    $alternativeBackground = alt_trow(true);

    $logsRows = '';

    foreach (logGet(["tid='{$taskID}'"]) as $logData) {
        $logID = (int)$logData['lid'];

        $userID = (int)$logData['uid'];

        $userData = get_user($userID);

        if (!empty($userData['uid'])) {
            $userName = htmlspecialchars_uni($userData['username']);

            $userName = format_name($userName, $userData['usergroup'], $userData['displaygroup']);

            $userName = build_profile_link($userName, $userData['uid']);
        } else {
            $userName = $lang->na;
        }

        $logGrantAwards = $logRevokeAwards = '';

        $logGrantAwardIDs = implode("','", array_map('intval', explode(',', $logData['gave'])));

        foreach (awardsGetCache(["aid IN ('{$logGrantAwardIDs}')"]) as $awardID => $awardData) {
            $awardName = htmlspecialchars_uni($awardData['name']);

            $awardImage = $awardClass = awardGetIcon($awardID);

            $awardImage = eval(
            getTemplate(
                $awardData['template'] === AWARD_TEMPLATE_TYPE_CLASS ? 'awardImageClass' : 'awardImage'
            )
            );

            $awardUrl = urlHandlerBuild(['action' => 'viewUsers', 'awardID' => $awardID]);

            $awardImage = eval(getTemplate('awardWrapper', false));

            $logGrantAwards .= $awardImage;
        }

        $taskRevokeAwardIDs = implode("','", array_map('intval', explode(',', $logData['revoked'])));

        foreach (awardsGetCache(["aid IN ('{$taskRevokeAwardIDs}')"]) as $awardID => $awardData) {
            $awardImage = $awardClass = awardGetIcon($awardID);

            $awardImage = eval(
            getTemplate(
                $awardData['template'] === AWARD_TEMPLATE_TYPE_CLASS ? 'awardImageClass' : 'awardImage'
            )
            );

            $awardUrl = urlHandlerBuild(['action' => 'viewUsers', 'awardID' => $awardID]);

            $awardImage = eval(getTemplate('awardWrapper', false));

            $logRevokeAwards .= $awardImage;
        }

        $logDate = my_date('relative', (int)$logData['date']);

        $logsRows .= eval(getTemplate('controlPanelLogsRow'));

        $alternativeBackground = alt_trow();
    }

    $pageTitle = $lang->ougcAwardsControlPanelLogsTitle;

    $pageContents = eval(getTemplate('controlPanelLogs'));
} elseif (!$isCustomPage) {
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

    $pageContents = '';

    $ownerObjects = ownerGetUser(["uid='{$currentUserID}'"]);

    $ownerAwardIDs = array_map('intval', array_column($ownerObjects, 'aid'));

    foreach (categoryGetCache([], '*', ['order_by' => 'disporder']) as $categoryData) {
        $categoryID = (int)$categoryData['cid'];

        if (!isVisibleCategory($categoryID)) {
            continue;
        }

        $moderationColumnRequest = $moderationColumnEnabled = $moderationColumnDisplayOrder = $moderationColumnOptions = '';

        $theadColumSpan = 3;

        $isCategoryOwner = ownerCategoryFind($categoryID, $currentUserID);

        if (($isModerator || $isCategoryOwner)) {
            $theadColumSpan += 2;

            $moderationColumnEnabled = eval(getTemplate('controlPanelListColumnEnabled'));

            $moderationColumnDisplayOrder = eval(getTemplate('controlPanelListColumnDisplayOrder'));
        }

        if (canRequestAwards(0, $categoryID)) {
            ++$theadColumSpan;

            $moderationColumnRequest = eval(getTemplate('controlPanelListColumnRequest'));
        }

        $categoryAwardsObjects = awardsGetCache(["cid='{$categoryID}'"], '*', ['order_by' => 'disporder']);

        $isAwardOwner = array_intersect(array_column($categoryAwardsObjects, 'aid'), $ownerAwardIDs);

        if (($isModerator || $isCategoryOwner) || $isAwardOwner) {
            ++$theadColumSpan;

            $moderationColumnOptions = eval(getTemplate('controlPanelListColumnOptions'));
        }

        $awardsList = '';

        $categoryName = $categoryData['name'];

        $categoryDescription = $categoryData['description'];

        $alternativeBackground = alt_trow(true);

        foreach ($categoryAwardsObjects as $awardID => $awardData) {
            if (!($isModerator || $isCategoryOwner) && !in_array($awardID, $ownerAwardIDs)) {
                //continue;
            }

            $descriptionColumSpan = 1;

            if (!($isModerator || $isCategoryOwner) && $isAwardOwner && !in_array($awardID, $isAwardOwner)) {
                ++$descriptionColumSpan;
            }

            //$colSpanRowCount = 2;

            if (canRequestAwards($awardID, $categoryID)) {
                //--$colSpanRowCount;
            }

            $awardName = htmlspecialchars_uni($awardData['name']);

            $awardDescription = htmlspecialchars_uni($awardData['description']);

            $awardUrl = urlHandlerBuild(['viewAward' => $awardID]);

            $awardImage = $awardClass = awardGetIcon($awardID);

            $awardImage = eval(
            getTemplate(
                $awardData['template'] === AWARD_TEMPLATE_TYPE_CLASS ? 'awardImageClass' : 'awardImage'
            )
            );

            $awardUrl = urlHandlerBuild(['action' => 'viewUsers', 'awardID' => $awardID]);

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

            $rowColumnEnabled = $rowColumnDisplayOrder = $rowColumnOptions = $rowColumnRequest = '';

            if (($isModerator || $isCategoryOwner)) {
                $rowColumnEnabled = eval(getTemplate('controlPanelListRowEnabled'));

                $rowColumnDisplayOrder = eval(getTemplate('controlPanelListRowDisplayOrder'));

                $rowColumnOptions = eval(getTemplate('controlPanelListRowOptions'));
            } elseif (in_array($awardID, $ownerAwardIDs)) {
                $rowColumnOptions = eval(getTemplate('controlPanelListRowOptions'));
            }

            $requestButton = '';

            if (canRequestAwards($awardID, $categoryID)) {
                $requestButton = eval(getTemplate('controlPanelListRowRequestButton'));

                $rowColumnRequest = eval(getTemplate('controlPanelListRowRequest'));
            } elseif ($moderationColumnRequest) {
                $requestButton = '-';

                $rowColumnRequest = eval(getTemplate('controlPanelListRowRequest'));
            }

            $rowColumnExtra = [];

            $plugins->run_hooks('ougc_awards_main_category_award_end');

            $rowColumnExtra = implode(' ', $rowColumnExtra);

            $awardsList .= eval(getTemplate('controlPanelListRow'));

            $alternativeBackground = alt_trow();
        }

        $moderationColumnExtra = [];

        $plugins->run_hooks('ougc_awards_main_category_end');

        if (!$awardsList) {
            $awardsList = eval(getTemplate('controlPanelListRowEmpty'));
        }

        $categoryLinks = $updateButton = '';

        if (($isModerator || $isCategoryOwner)) {
            $moderatorLinks = '';

            if ($isModerator) {
                $viewCategoryOwnersUrl = urlHandlerBuild(
                    ['action' => 'viewCategoryOwners', 'categoryID' => $categoryID]
                );

                $editCategoryUrl = urlHandlerBuild(['action' => 'editCategory', 'categoryID' => $categoryID]);

                $deleteCategoryUrl = urlHandlerBuild(['action' => 'deleteCategory', 'categoryID' => $categoryID]);

                $moderatorLinks = eval(getTemplate('controlPanelListCategoryLinksModerator'));
            }

            $newAwardUrl = urlHandlerBuild(['action' => 'newAward', 'categoryID' => $categoryID]);

            $categoryLinks = eval(getTemplate('controlPanelListCategoryLinks'));

            $updateButton = eval(getTemplate('controlPanelListButtonUpdateCategory'));
        }

        $moderationColumnExtra = implode(' ', $moderationColumnExtra);

        $pageContents .= eval(getTemplate('controlPanelList'));
    }

    if (!$pageContents) {
        $pageContents = eval(getTemplate('controlPanelEmpty'));
    }

    if (isModerator()) {
        $buttonUrl = urlHandlerBuild(['action' => 'newCategory']);

        $buttonText = $lang->ougcAwardsControlPanelButtonNewCategory;

        $actionButtons[] = eval(getTemplate('controlPanelButtons'));

        /*
                $buttonUrl = urlHandlerBuild(['action' => 'newAward']);

                $buttonText = $lang->ougcAwardsControlPanelButtonNewAward;

                $actionButtons[] = eval(getTemplate('controlPanelButtons'));
        */

        $buttonUrl = urlHandlerBuild(['action' => 'manageTasks']);

        $buttonText = $lang->ougcAwardsControlPanelButtonManageTasks;

        $actionButtons[] = eval(getTemplate('controlPanelButtons'));
    }

    $actionButtons[] =
        (function () use ($lang): string {
            $buttonUrl = urlHandlerBuild(['action' => 'myAwards']);

            $buttonText = $lang->ougcAwardsControlPanelButtonManageMyAwards;

            return eval(getTemplate('controlPanelButtons'));
        })();

    $pageTitle = $lang->ougcAwardsControlPanelTitle;
}

if (!empty($errorMessages)) {
    $errorMessages = inline_error($errorMessages);
} else {
    $errorMessages = '';
}

$actionButtons = implode(' ', $actionButtons);

$pageContents = eval(getTemplate('controlPanelContents'));

$pageContents = eval(getTemplate('controlPanel'));

$plugins->run_hooks('ougc_awards_end');

output_page($pageContents);

exit;