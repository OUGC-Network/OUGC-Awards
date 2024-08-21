<?php

/***************************************************************************
 *
 *    OUGC Awards plugin (/inc/anguages/english/ougc_awards.lang.php)
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

$l = [
    'ougcAwards' => 'Awards',

    'ougcAwardsPageNavigation' => 'Awards',
    'ougcAwardsNoReason' => 'No reason specified.',
    'ougcAwardsDate' => '{1} <i>at</i> {2}',
    //$l['ougc_awards_pm_noreason_request_accepted' => 'Your request for this award was accepted.';

    /*
    'ougcAwardsEmpty' => 'There are currently no awards to display.',
    'ougcAwardsAward' => 'Award',
    'ougcAwardsName' => 'Name',
    'ougcAwardsDescription' => 'Description',
    'ougcAwardsRequest' => 'Request',


    'ougcAwardsViewUsername' => 'Username',
    'ougcAwardsViewReason' => 'Reason',
    'ougcAwardsViewThread' => 'Thread',
    'ougcAwardsViewDate' => 'Date',
    'ougcAwardsViewButtonRequest' => 'Request',


    'ougcAwardsControlPanelGrant' => 'Grant Award',
    'ougcAwardsControlPanelGrantTitle' => 'Grant Award',

    'ougcAwardsControlPanelRevoke' => 'Revoke Award',
    'ougcAwardsControlPanelRevokeTitle' => 'Revoke Award',

    */
    'ougcAwardsControlPanelNavigation' => 'Awards',
    'ougcAwardsControlPanelButtonNewCategory' => 'New Category',

    'ougcAwardsControlPanelNewCategoryTitle' => 'New Category',
    'ougcAwardsControlPanelNewCategoryTableTitle' => 'New Category',
    'ougcAwardsControlPanelNewCategoryTableDescription' => 'Use the form below to create a new category.',
    'ougcAwardsControlPanelNewCategoryName' => 'Name',
    'ougcAwardsControlPanelNewCategoryNameDescription' => 'Select a short name for this category.',
    'ougcAwardsControlPanelNewCategoryDescription' => 'Description',
    'ougcAwardsControlPanelNewCategoryDescriptionDescription' => 'Select a short description for this category.',
    'ougcAwardsControlPanelNewCategoryAllowRequests' => 'Allow User Requests',
    'ougcAwardsControlPanelNewCategoryAllowRequestsDescription' => 'Allow users to request this category.',
    'ougcAwardsControlPanelNewCategoryEnabled' => 'Enabled',
    'ougcAwardsControlPanelNewCategoryEnabledDescription' => 'Enable this category.',
    'ougcAwardsControlPanelNewCategoryDisplayOrder' => 'Display Order',
    'ougcAwardsControlPanelNewCategoryDisplayOrderDescription' => 'Select the display order for this category.',
    'ougcAwardsControlPanelNewCategoryButton' => 'Create Category',

    'ougcAwardsControlPanelEditCategoryTitle' => 'Edit Category',
    'ougcAwardsControlPanelEditCategoryTableTitle' => 'Edit Category',
    'ougcAwardsControlPanelEditCategoryTableDescription' => 'Use the form below to update the selected category.',
    'ougcAwardsControlPanelEditCategoryButton' => 'Update Category',

    'ougcAwardsControlPanelTitle' => 'Awards',
    'ougcAwardsControlPanelAward' => 'Award',
    'ougcAwardsControlPanelDescription' => 'Description',
    'ougcAwardsControlPanelEmpty' => 'There are currently no awards to display.',
    'ougcAwardsControlPanelAwardEditCategory' => 'Edit',
    'ougcAwardsControlPanelEnabled' => 'Enabled',
    'ougcAwardsControlPanelDisplayOrder' => 'Display Order',
    'ougcAwardsControlPanelOptions' => 'Options',
    'ougcAwardsControlPanelRequest' => 'Request',
    'ougcAwardsControlPanelViewUsers' => 'View Users',
    'ougcAwardsControlPanelViewOwners' => 'View Owners',
    'ougcAwardsControlPanelViewRequests' => 'View Requests',
    'ougcAwardsControlPanelEditAward' => 'Edit Award',
    'ougcAwardsControlPanelDeleteAward' => 'Delete Award',
    'ougcAwardsControlPanelRequestAward' => 'Request Award',
    'ougcAwardsControlPanelButtonUpdate' => 'Update Award',
    'ougcAwardsControlPanelButtonNewAward' => 'New Award',

    'ougcAwardsControlPanelNewAwardTitle' => 'New Award',
    'ougcAwardsControlPanelNewAwardTableTitle' => 'New Award',
    'ougcAwardsControlPanelNewAwardTableDescription' => 'Use the form below to create a new award.',
    'ougcAwardsControlPanelNewAwardName' => 'Name',
    'ougcAwardsControlPanelNewAwardNameDescription' => 'Select a short name for this award.',
    'ougcAwardsControlPanelNewAwardDescription' => 'Description',
    'ougcAwardsControlPanelNewAwardDescriptionDescription' => 'Select a short description for this award.',
    'ougcAwardsControlPanelNewAwardCategory' => 'Category',
    'ougcAwardsControlPanelNewAwardCategoryDescription' => 'Select the category for this award.',
    'ougcAwardsControlPanelNewAwardImageType' => 'Image / Class',
    'ougcAwardsControlPanelNewAwardImageTypeDescription' => 'Enter the image path or class name for this award below. The following variables are available.<br /><pre style="color: darkgreen;">
{bburl} -> Forum URL
{homeurl} -> Home URL
{imgdir} -> Theme Directory URL
{aid} -> Award ID
{cid} -> Category ID
</pre>',
    'ougcAwardsControlPanelNewAwardTemplateType' => 'Template Type',
    'ougcAwardsControlPanelNewAwardTemplateTypeDescription' => 'Select what template type to use for this award.<br /><pre style="color: darkgreen;">
Image Template: Best for image (file) awards.
Class Template: Best for CSS (class) awards.
Custom Template (Advanced): A custom template for this award.
</pre>',
    'ougcAwardsControlPanelNewAwardTemplateTypeImage' => 'Image Template',
    'ougcAwardsControlPanelNewAwardTemplateTypeClass' => 'Class Template',
    'ougcAwardsControlPanelNewAwardTemplateTypeCustom' => 'Custom Template (Advanced)',
    'ougcAwardsControlPanelNewAwardAllowRequests' => 'Allow User Requests',
    'ougcAwardsControlPanelNewAwardAllowRequestsDescription' => 'Allow users to request this award.',
    'ougcAwardsControlPanelNewAwardPrivateMessage' => 'Private Message Content',
    'ougcAwardsControlPanelNewAwardPrivateMessageDescription' => 'If not empty, will send a Private Message whenever this award is granted.<br /><pre style="color: darkgreen;">
{1} = Username
{2} = Award name
{3} = Reason
{4} = Image
</pre>',
    'ougcAwardsControlPanelNewAwardDisplayType' => 'Display Type',
    'ougcAwardsControlPanelNewAwardDisplayTypeDescription' => 'Select if this award will be displayed in posts, profiles, or both.',
    'ougcAwardsControlPanelNewAwardDisplayTypeBoth' => 'Both',
    'ougcAwardsControlPanelNewAwardDisplayTypeProfile' => 'Profile',
    'ougcAwardsControlPanelNewAwardDisplayTypePosts' => 'Posts',
    'ougcAwardsControlPanelNewAwardButton' => 'Create Award',

    'ougcAwardsControlPanelEditAwardTitle' => 'Edit Award',
    'ougcAwardsControlPanelEditAwardTableTitle' => 'Edit Award',
    'ougcAwardsControlPanelEditAwardTableDescription' => 'Use the form below to update the selected award.',
    'ougcAwardsControlPanelEditAwardButton' => 'Update Award',

    'ougcAwardsControlPanelDeleteAwardTitle' => 'Delete Award',
    'ougcAwardsControlPanelDeleteAwardTableTitle' => 'Delete Award',
    'ougcAwardsControlPanelDeleteAwardTableDescription' => 'Are you sure you want to delete the selected award?',
    'ougcAwardsControlPanelDeleteAwardDetailTotalAwards' => 'Total award records',
    'ougcAwardsControlPanelDeleteAwardDetailTotalGranted' => 'Total award grant records',
    'ougcAwardsControlPanelDeleteAwardDetailTotalOwners' => 'Total owner records',
    'ougcAwardsControlPanelDeleteAwardDetailTotalRequests' => 'Total request records',
    'ougcAwardsControlPanelDeleteAwardButton' => 'Delete Award',

    'ougcAwardsControlPanelUsersEmpty' => 'There are currently no users to display.',

    'ougcAwardsControlPanelUsersTitle' => 'Award Users',
    'ougcAwardsControlPanelUsersTableTitle' => 'Award Users',
    'ougcAwardsControlPanelUsersTableUsername' => 'Username',
    'ougcAwardsControlPanelUsersTableReason' => 'Reason',
    'ougcAwardsControlPanelUsersTableThread' => 'Thread',
    'ougcAwardsControlPanelUsersTableDate' => 'Date',
    'ougcAwardsControlPanelUsersTableOptions' => 'Options',
    'ougcAwardsControlPanelUsersTableOptionsEditGrant' => 'Edit Grant',

    'ougcAwardsControlPanelOwnersEmpty' => 'There are currently no users to display.',

    'ougcAwardsControlPanelOwnersTitle' => 'Owners',
    'ougcAwardsControlPanelOwnersTableTitle' => 'Award Owners',
    'ougcAwardsControlPanelOwnersTableUsername' => 'Username',
    'ougcAwardsControlPanelOwnersTableDate' => 'Date',
    'ougcAwardsControlPanelOwnersTableOptions' => 'Options',
    'ougcAwardsControlPanelUsersTableOptionsDelete' => 'Revoke Owner',

    'ougcAwardsControlPanelOwnersForm' => 'Assign Owner',
    'ougcAwardsControlPanelOwnersFormDescription' => 'Use the form below to assign new owners to this award.',
    'ougcAwardsControlPanelOwnersFormUsernames' => 'Usernames',
    'ougcAwardsControlPanelOwnersFormButton' => 'Assign Owner',

    'ougcAwardsControlPanelGrantTable' => 'Grant Award',
    'ougcAwardsControlPanelGrantTableDescription' => 'Grant this award to users.',
    'ougcAwardsControlPanelGrantTableUsernames' => 'Usernames',
    'ougcAwardsControlPanelGrantTableReason' => 'Reason',
    'ougcAwardsControlPanelGrantTableThread' => 'Thread',
    'ougcAwardsControlPanelGrantButton' => 'Grant Award',

    'ougcAwardsControlPanelRevokeTable' => 'Revoke Award',
    'ougcAwardsControlPanelRevokeTableDescription' => 'Revoke this award from users.',
    'ougcAwardsControlPanelRevokeTableUsernames' => 'Usernames',
    'ougcAwardsControlPanelRevokeButton' => 'Revoke Award',

    'ougcAwardsControlPanelEditGrantTitle' => 'Edit Grant',
    'ougcAwardsControlPanelEditGrantTableTitle' => 'Edit Grant',
    'ougcAwardsControlPanelEditGrantTableDescription' => 'Use the form below to edit an existing award grant.',
    'ougcAwardsControlPanelEditGrantTableReason' => 'Grant Reason',
    'ougcAwardsControlPanelEditGrantTableReasonDescription' => 'Select a reason for granting this award.',
    'ougcAwardsControlPanelEditGrantTableThread' => 'Grant Thread',
    'ougcAwardsControlPanelEditGrantTableThreadDescription' => 'Please enter an thread to assign this granting to.',
    'ougcAwardsControlPanelEditGrantTableDate' => 'Grant Date',
    'ougcAwardsControlPanelEditGrantTableDateDescription' => 'Modify the grant time stamp.',
    'ougcAwardsControlPanelEditGrantButton' => 'Update Grant',

    'ougcAwardsControlPanelDeleteOwnersTitle' => 'Delete Owner',
    'ougcAwardsControlPanelDeleteOwnersDescription' => 'Are you sure you want to delete the selected owner?',
    'ougcAwardsControlPanelDeleteOwnersButton' => 'Delete Owner',

    'ougcAwardsControlPanelRequests' => 'Award Requests',
    'ougcAwardsControlPanelRequestsTableTitle' => 'Award Requests',


    'ougcAwardsControlPanelRequestsEmpty' => 'There are currently no requests to display.',
    'ougcAwardsControlPanelRequestsTableAward' => 'Award',
    'ougcAwardsControlPanelRequestsTableUsername' => 'Username',
    'ougcAwardsControlPanelRequestsTableMessage' => 'Message',
    'ougcAwardsControlPanelRequestsTableStatus' => 'Status',
    'ougcAwardsControlPanelRequestsTableOptions' => 'Options',

    'ougcAwardsControlPanelRequestsStatusPending' => 'Pending',
    'ougcAwardsControlPanelRequestsStatusRejected' => 'Rejected',
    'ougcAwardsControlPanelRequestsStatusAccepted' => 'Accepted',

    'ougcAwardsControlPanelPresetsTitle' => 'Award Presets',
    'ougcAwardsControlPanelPresetsTableTitle' => 'Award Presets',
    'ougcAwardsControlPanelPresetsTableDescription' => 'Use the form below to manage your award presets.',
    'ougcAwardsControlPanelPresetsButtonSelect' => 'Select Preset',
    'ougcAwardsControlPanelPresetsButtonDelete' => 'Delete Preset',
    'ougcAwardsControlPanelPresetsButtonSetDefault' => 'Set Default',
    'ougcAwardsControlPanelPresetsButtonCreate' => 'Create Preset',
    'ougcAwardsControlPanelPresetsTableHidden' => 'Hidden',
    'ougcAwardsControlPanelPresetsTableVisible' => 'Visible',
    'ougcAwardsControlPanelPresetsSuccess' => 'The preset has been updated.',
    'ougcAwardsControlPanelPresetsError' => 'There was an error updating the preset.',

    'ougcAwardsRequestTitle' => 'Request Award',
    'ougcAwardsRequestDescription' => 'Send a request for this award to a moderator.',
    'ougcAwardsRequestErrorNoPermission' => 'You do not have permission to view this page.',
    'ougcAwardsRequestMessage' => 'Request comment',
    'ougcAwardsRequestButtonSend' => 'Send Request',

    'ougcAwardsControlPanelTasksTitle' => 'Award Tasks',
    'ougcAwardsControlPanelTasksTableTitle' => 'Award Tasks',
    'ougcAwardsControlPanelTasksTableName' => 'Name',
    'ougcAwardsControlPanelTasksTableDescription' => 'Description',
    'ougcAwardsControlPanelTasksTableRequirements' => 'Requirements',
    'ougcAwardsControlPanelTasksTableEnabled' => 'Enabled',
    'ougcAwardsControlPanelTasksTableOptions' => 'Options',
    'ougcAwardsControlPanelTasksTableOptionsEdit' => 'Edit Task',
    'ougcAwardsControlPanelTasksTableOptionsDelete' => 'Delete Task',

    'ougcAwardsControlPanelButtonManageTasks' => 'Manage Tasks',
    'ougcAwardsControlPanelButtonNewTask' => 'New Task',

    'ougcAwardsControlPanelNewTaskTitle' => 'New Task',
    'ougcAwardsControlPanelNewTaskTableTitle' => 'New Task',
    'ougcAwardsControlPanelNewTaskTableDescription' => 'Use the form below to create a new award task.',
    'ougcAwardsControlPanelNewTaskName' => 'Name',
    'ougcAwardsControlPanelNewTaskNameDescription' => 'Select a short name for this award',
    'ougcAwardsControlPanelNewTaskDescription' => 'Description',
    'ougcAwardsControlPanelNewTaskDescriptionDescription' => 'Select a short description for this task.',
    'ougcAwardsControlPanelNewTaskEnabled' => 'Enabled',
    'ougcAwardsControlPanelNewTaskEnabledDescription' => 'Enable this task.',
    'ougcAwardsControlPanelNewTaskRequirements' => 'Criteria Requirements',
    'ougcAwardsControlPanelNewTaskRequirementsDescription' => 'Select the rules to execute this task on users.',
    'ougcAwardsControlPanelNewTaskRequirementsGroups' => 'User Groups',
    'ougcAwardsControlPanelNewTaskRequirementsPostCount' => 'Post Count',
    'ougcAwardsControlPanelNewTaskRequirementsThreadCount' => 'Thread Count',
    'ougcAwardsControlPanelNewTaskRequirementsForumPostCount' => 'Forum Post Count',
    'ougcAwardsControlPanelNewTaskRequirementsForumThreadCount' => 'Forum Thread Count',
    'ougcAwardsControlPanelNewTaskRequirementsTimeRegistered' => 'Time Registered',
    'ougcAwardsControlPanelNewTaskRequirementsTimeOnline' => 'Time Registered',
    'ougcAwardsControlPanelNewTaskRequirementsReputation' => 'Reputation',
    'ougcAwardsControlPanelNewTaskRequirementsReferrals' => 'Referrals',
    'ougcAwardsControlPanelNewTaskRequirementsWarningPoints' => 'Warning Points',
    //'ougcAwardsControlPanelNewTaskRequirementsNewpoints' => 'Newpoints Points',
    'ougcAwardsControlPanelNewTaskRequirementsPreviousAwards' => 'Previous Awards',
    'ougcAwardsControlPanelNewTaskRequirementsFilledProfileFields' => 'Filled Profile Fields',
    //'ougcAwardsControlPanelNewTaskRequirementsMyDownloads' => 'MyDownloads Files Count',
    //'ougcAwardsControlPanelNewTaskRequirementsMyArcadeChampions' => 'MyArcade Champions',
    //'ougcAwardsControlPanelNewTaskRequirementsMyArcadeScores' => 'MyArcade Scores',
    'ougcAwardsControlPanelNewTaskRequirementsCustomReputationReceived' => 'Custom Reputation Received',
    'ougcAwardsControlPanelNewTaskRequirementsCustomReputationGiven' => 'Custom Reputation Received',
    'ougcAwardsControlPanelNewTaskGrant' => 'Grant Award',
    'ougcAwardsControlPanelNewTaskGrantDescription' => 'Select the award(s) to grant to users.',
    'ougcAwardsControlPanelNewTaskReason' => 'Grant Reason',
    'ougcAwardsControlPanelNewTaskReasonDescription' => 'Select a reason for granting this award.',
    'ougcAwardsControlPanelNewTaskThread' => 'Grant Thread',
    'ougcAwardsControlPanelNewTaskThreadDescription' => 'Please enter a thread to assign to award grants.',
    'ougcAwardsControlPanelNewTaskMultiple' => 'Allow Multiple Awards',
    'ougcAwardsControlPanelNewTaskMultipleDescription' => 'Select whether this task should grant awards to users that already have it.',
    'ougcAwardsControlPanelNewTaskRevoke' => 'Revoke Award',
    'ougcAwardsControlPanelNewTaskRevokeDescription' => 'Select the award(s) to revoke from users.',
    'ougcAwardsControlPanelNewTaskDisplayOrder' => 'Display Order',
    'ougcAwardsControlPanelNewTaskDisplayOrderDescription' => 'Select the display order for this task.',
    'ougcAwardsControlPanelNewTaskButton' => 'Create Task',

    'ougcAwardsControlPanelEditTaskTitle' => 'Edit Tasks',
    'ougcAwardsControlPanelEditTaskTableTitle' => 'Edit Task',
    'ougcAwardsControlPanelEditTaskTableDescription' => 'Use the form below to update an award task.',
    'ougcAwardsControlPanelEditTaskButton' => 'Update Task',

    'ougcAwardsErrorInvalidCategory' => 'The selected category is invalid.',
    'ougcAwardsErrorInvalidAward' => 'The selected award does not exist.',
    'ougcAwardsErrorInvalidOwner' => 'The selected award owner does not exist.',
    'ougcAwardsErrorInvalidUsers' => 'Some selected users do not exist.',
    'ougcAwardsErrorDuplicatedOwner' => 'Some selected users are already assigned as owners for this award.',
    'ougcAwardsErrorPendingRequest' => 'You already have an open request for this award.',
    'ougcAwardsErrorInvalidGrant' => 'The selected award grant does not exist.',
    'ougcAwardsErrorInvalidGrantReason' => 'The selected grant reason is invalid.',
    'ougcAwardsErrorInvalidGrantDate' => 'The selected grant date is invalid.',
    'ougcAwardsErrorInvalidThread' => 'The selected thread does not exist.',
    'ougcAwardsErrorInvalidCategoryName' => 'The selected category name is invalid.',
    'ougcAwardsErrorInvalidCategoryDescription' => 'The selected category description is invalid.',
    'ougcAwardsErrorInvalidAwardName' => 'The selected award name is invalid.',
    'ougcAwardsErrorInvalidAwardDescription' => 'The selected award description is invalid.',
    'ougcAwardsErrorInvalidAwardImage' => 'The selected award image is invalid.',

    'ougcAwardsErrorInvalidTaskName' => 'The selected task name is invalid.',
    'ougcAwardsErrorInvalidTaskDescription' => 'The selected task description is invalid.',

    'ougcAwardsErrorNoUsersPermission' => 'You have no permission to edit the selected user.',

    'ougcAwardsRedirectCategoryCreated' => 'The category was created successfully.<br />You will now be redirected back.',
    'ougcAwardsRedirectCategoryUpdated' => 'The category was updated successfully.<br />You will now be redirected back.',
    'ougcAwardsRedirectAwardCreated' => 'The award was created successfully.<br />You will now be redirected back.',
    'ougcAwardsRedirectAwardUpdated' => 'The award was updated successfully.<br />You will now be redirected back.',
    'ougcAwardsRedirectAwardDeleted' => 'The award was deleted successfully.<br />You will now be redirected back.',
    'ougcAwardsRedirectOwnerAssigned' => 'The award owner was assigned successfully.<br />You will now be redirected back.',
    'ougcAwardsRedirectOwnerRevoked' => 'The award owner was revoked successfully.<br />You will now be redirected back.',
    'ougcAwardsRedirectGranted' => 'The award was granted successfully.<br />You will now be redirected back.',
    'ougcAwardsRedirectGrantedUpdated' => 'The award grant was updated successfully.<br />You will now be redirected back.',
    'ougcAwardsRedirectGrantRevoked' => 'The award grant was revoked successfully.<br />You will now be redirected back.',
    'ougcAwardsRedirectPresetCreated' => 'The award preset was created successfully.<br />You will now be redirected back.',
    'ougcAwardsRedirectPresetUpdated' => 'The award preset was updated successfully.<br />You will now be redirected back.',
    'ougcAwardsRedirectPresetDeleted' => 'The award preset was deleted successfully.<br />You will now be redirected back.',

    'ougcAwardsRedirectRequest' => 'The award request was sent successfully.',

    'ougcAwardsRedirectTaskCreated' => 'The task was created successfully.<br />You will now be redirected back.',
    'ougcAwardsRedirectTaskUpdated' => 'The task was updated successfully.<br />You will now be redirected back.',
];

/*
// Plugin information.
$l['ougc_awards' => 'OUGC Awards';

// ModCP
$l['ougc_awards_usercp_nav' => 'Awards';
$l['ougc_awards_modcp_list_desc' => 'Grant or revoke awards from this quick access panel.';

// Requests
$l['ougc_awards_modcp_requests_logs_nav' => 'Requests';
$l['ougc_awards_modcp_requests_list_accept' => 'Accept';
$l['ougc_awards_modcp_requests_list_reject' => 'Reject';

$l['ougc_awards_error_give' => 'The selected user already has this award.';
$l['ougcAwardsErrorRequestsNoneSelected' => 'No awards were selected.';
$l['ougc_awards_error_active' => 'The awards system is currently not active.';

$l['ougc_awards_modcp_list_empty' => 'Currently there are not awards to manage.';
$l['ougc_awards_modcp_cache' => 'Update Cache';

// Redirects
$l['ougc_awards_redirect_cache' => 'Cache updated successfully.';
$l['ougcAwardsRedirectRequestAccepted' => 'Award request accepted successfully.<br />You will now be redirected back.';
$l['ougcAwardsRedirectRequestRejected' => 'Award request rejected successfully.<br />You will now be redirected back.';

// Profile
$l['ougcAwardsProfileEmpty' => 'There are currently no awards to display.';
$l['ougcAwardsProfileTitle' => '{1}'s awards.";
$l['ougcAwardsProfilePresetsAwards' => 'awards";

// PMs
$l['ougcAwardsPrivateMessageTitle' => 'You have been granted the {1} award!';
$l['ougcAwardsPrivateMessageRequestRejectedTitle' => 'Your request for the {1} award was rejected.';
$l['ougcAwardsPrivateMessageRequestRejectedBody' => 'Hi {1}. This is an automatic message to inform you that your request for the {2} award was rejected.

Greetings.';

// View all
$l['ougcAwardsViewUser' => 'View User Awards';
$l['ougcAwardsViewUserTitle' => '{1}'s Awards";

$l['ougcAwardsWelcomeLinkText' => 'View My Awards';
$l['ougc_awards_welcomeblock_empty' => 'Your award list is currently empty.';

// Modal
$l['ougc_awards_request_name' => 'Award';

// Stats
$l['ougc_awards_stats_most' => 'Most Granted Users';
$l['ougc_awards_stats_last' => 'Latest Granted';
$l['ougc_awards_stats_empty' => 'There are currently no stats to display.';
$l['ougc_awards_stats_username' => 'Username';
$l['ougc_awards_stats_total' => 'Total';
$l['ougc_awards_stats_viewall' => 'View All';

// UserCP
$l['ougc_awards_usercp_list_visible' => 'Display';
$l['ougc_awards_usercp_list_disporder' => 'Display Order';
$l['ougc_awards_usercp_list_reason' => 'Reason';
$l['ougc_awards_usercp_list_from' => 'From User';

// Global
$l['ougc_awards_global_menu' => 'Awards';
$l['ougcAwardsPendingRequests' => 'You have {1} pending request(s) for this award.';
$l['ougc_awards_page_pending_requests_moderator' => '<a href="{1}/{2}?action=awards&amp;manage=requests"><strong>Moderator Notice:</strong> There is one award request pending for review.</a>';
$l['ougc_awards_page_pending_requests_moderator_plural' => '<a href="{1}/{2}?action=awards&amp;manage=requests"><strong>Moderator Notice:</strong> There are {3} award requests pending for review.</a>';

// WOL
$l['ougc_awards_wol' => 'Viewing <a href="{1}/awards.php">Awards Page</a>';

// MyAlerts
$l['ougc_awards_myalerts' => '{1}, you were granted the {3} award by {2}.';// {4} outputs the award formatted image
$l['myalerts_setting_ougc_awards' => 'Receive alerts related to awards?';

// Presets
$l['ougc_awards_presets_title' => 'Presets';
$l['ougc_awards_presets_desc' => 'Manage your award presets, which control how your awards are displayed.';
$l['ougc_awards_presets_setdefault' => 'Set as default';
$l['ougc_awards_presets_delete' => 'Delete';
$l['ougc_awards_presets_button' => 'View Presets';
$l['ougc_awards_presets_postbit' => 'awards';

$l['ougcAwardsTaskRan' => 'The awards task ran successfully.';*/