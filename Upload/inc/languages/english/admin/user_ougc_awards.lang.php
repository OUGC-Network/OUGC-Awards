<?php

/***************************************************************************
 *
 *	OUGC Awards plugin (/inc/anguages/english/admin/user_ougc_awards.lang.php)
 *	Author: Omar Gonzalez
 *	Copyright: © 2012-2020 Omar Gonzalez
 *
 *	Website: https://ougc.network
 *
 *	Adds a powerful awards system to you community.
 *
 ***************************************************************************

****************************************************************************
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
****************************************************************************/

// Plugin API
$l['setting_group_ougc_awards'] = 'OUGC Awards';
$l['setting_group_ougc_awards_desc'] = 'Adds a powerful awards system to you community.';

// Importer
$l['ougc_awards_import_title'] = 'Import Awards';
$l['ougc_awards_import_desc'] = '<br />&nbsp;&nbsp;&nbsp;<a href="./index.php?module=config-plugins&amp;ougc_awards_import=myawards">Import From MyAwards</a>
<br />&nbsp;&nbsp;&nbsp;<a href="./index.php?module=config-plugins&amp;ougc_awards_import=nickawards">Import From Nickman\'s Awards System</a>';
$l['ougc_awards_import_confirm_myawards'] = 'Are you sure you want to import awards from MyAwards?';
$l['ougc_awards_import_confirm_nickawards'] = 'Are you sure you want to import awards from Nickman\'s award system?';
$l['ougc_awards_import_end'] = 'Awards Imported Successfully.';
$l['ougc_awards_import_error'] = 'There was an error trying to import the selected awards. The "{1}" table doesn\'t seems to exists.';

// Settings
$l['setting_ougc_awards_postbit'] = 'Maximum Awards in Posts';
$l['setting_ougc_awards_postbit_desc'] = 'Maximum number of awards to be shown in posts. -1 for unlimited. 0 to disable.';
$l['setting_ougc_awards_postbit_maxperline'] = 'Maximum Awards Per Line';
$l['setting_ougc_awards_postbit_maxperline_desc'] = 'Maximum number of awards to be shown in the same line in posts before adding a break tag. Leave 0 to disable.';
$l['setting_ougc_awards_profile'] = 'Maximum Awards in Profile';
$l['setting_ougc_awards_profile_desc'] = 'Maximum number of awards to be shown in profiles. -1 for unlimited. 0 to disable.';
$l['setting_ougc_awards_modcp'] = 'Enable ModCP Panel';
$l['setting_ougc_awards_modcp_desc'] = 'Allows moderators to manage awards from the moderator control panel.';
$l['setting_ougc_awards_modgroups'] = 'Moderator Groups';
$l['setting_ougc_awards_modgroups_desc'] = 'Allowed groups to moderate this feature.';
$l['setting_ougc_awards_pagegroups'] = 'Awards Page Allowed Groups';
$l['setting_ougc_awards_pagegroups_desc'] = 'Allowed groups to view the awards page.';
$l['setting_ougc_awards_perpage'] = 'Items Per Page';
$l['setting_ougc_awards_perpage_desc'] = 'Maximum number of items to show per page or within listings.';
$l['setting_ougc_awards_sendpm'] = 'Send PM';
$l['setting_ougc_awards_sendpm_desc'] = 'Do you want to send an PM to users when receiving an award?';
$l['setting_ougc_awards_welcomeblock'] = 'Display Welcome Block List';
$l['setting_ougc_awards_welcomeblock_desc'] = 'Enabling this feature, a full list of earned awards will be displayed inside the header user welcome block.';
$l['setting_ougc_awards_enablestatspage'] = 'Enable Stats';
$l['setting_ougc_awards_enablestatspage_desc'] = 'Do you want to enable the top and last awarded users in the stats page?';
$l['setting_ougc_awards_myalerts'] = 'MyAlerts Integration';
$l['setting_ougc_awards_myalerts_desc'] = 'Do you want to send an alert to users when receiving an award';
$l['setting_ougc_awards_presets_groups'] = 'Presets Allowed Groups';
$l['setting_ougc_awards_presets_groups_desc'] = 'Select which groups are allowed to use and create presets.';
$l['setting_ougc_awards_presets_maximum'] = 'Maximum Presets';
$l['setting_ougc_awards_presets_maximum_desc'] = 'Select the maximum amount of presets can create.';
$l['setting_ougc_awards_presets_post'] = 'Maximum Presets in Posts';
$l['setting_ougc_awards_presets_post_desc'] = 'Type the maximum preset awards to display in posts.';
$l['setting_ougc_awards_presets_profile'] = 'Maximum Preset Awards in Profiles';
$l['setting_ougc_awards_presets_profile_desc'] = 'Type the maximum preset awards to display in profiles.';

// Administrator Permissions
$l['ougc_awards_acp_permissions'] = 'Can manage awards?';

// ACP Module: Logs
$l['ougc_awards_logs_task'] = 'Task';
$l['ougc_awards_logs_user'] = 'User';
$l['ougc_awards_logs_received'] = 'Award Received';
$l['ougc_awards_logs_revoked'] = 'Award Revoked';
$l['ougc_awards_logs_date'] = 'Date';
$l['ougc_awards_logs_empty'] = 'There are currently no logs to show.';
$l['ougc_awards_logs_prune'] = 'Prune';
$l['ougc_awards_logs_none'] = 'None';

// ACP Module: Tabs
$l['ougc_awards_acp_nav'] = 'Manage Awards';
$l['ougc_awards_tab_view'] = 'View';
$l['ougc_awards_tab_view_d'] = 'View all current awards to manage in this category.';
$l['ougc_awards_tab_add'] = 'Add';
$l['ougc_awards_tab_add_d'] = 'Add a new award.';
$l['ougc_awards_tab_addc_desc'] = 'Add a new category.';
$l['ougc_awards_tab_edit'] = 'Edit';
$l['ougc_awards_tab_edit_d'] = 'Edit a existing award.';
$l['ougc_awards_tab_editc_desc'] = 'Edit an existing category.';
$l['ougc_awards_tab_editt_desc'] = 'Edit an existing task.';
$l['ougc_awards_tab_give'] = 'Give';
$l['ougc_awards_tab_give_d'] = 'Give award.';
$l['ougc_awards_tab_revoke'] = 'Revoke';
$l['ougc_awards_tab_revoke_d'] = 'Revoke award.';
$l['ougc_awards_tab_users'] = 'View Users';
$l['ougc_awards_tab_users_d'] = 'View users with this award.';
$l['ougc_awards_tab_owners'] = 'Manage Owners';
$l['ougc_awards_tab_owners_d'] = 'Manage the award owners.';
$l['ougc_awards_tab_owners_form'] = 'Add Owner';
$l['ougc_awards_tab_edit_user'] = 'Edit User Award';
$l['ougc_awards_tab_edit_user_d'] = 'Edit this user\'s award data.';
$l['ougc_awards_tab_delete'] = 'Delete';
$l['ougc_awards_tab_cache'] = 'Rebuilt Cache';
$l['ougc_awards_tab_categories'] = 'Categories';
$l['ougc_awards_tab_categories_desc'] = 'View all current categories to manage.';
$l['ougc_awards_tab_tasks'] = 'Tasks';
$l['ougc_awards_tab_tasks_desc'] = 'Create task to automatically give/revoke awards from users.';
$l['ougc_awards_tab_tasks_logs'] = 'Logs';
$l['ougc_awards_tab_tasks_logs_desc'] = 'View the tasks logs.';

// ACP Module: Form
$l['ougc_awards_form_add'] = 'Add New Item';
$l['ougc_awards_form_name'] = 'Name';
$l['ougc_awards_form_name_d'] = 'Insert a short name for this item.';
$l['ougc_awards_form_username'] = 'Username';
$l['ougc_awards_form_username_d'] = 'Insert the username of the user to give/revoke the selected award.';
$l['ougc_awards_form_owner_username_d'] = 'Insert the username of the user upon which you want to grant owner status over this award.';
$l['ougc_awards_form_reason'] = 'Reason';
$l['ougc_awards_form_reason_d'] = 'Insert a reason for giving this award';
$l['ougc_awards_form_thread'] = 'Thread';
$l['ougc_awards_form_thread_d'] = 'Please enter an thread to assign this granting to.';
$l['ougc_awards_form_multiple'] = 'Multiple Users';
$l['ougc_awards_form_multiple_desc'] = 'Select whether or not you are giving this award to multiple users at once.<br />
You need to separate each username with a comma without spaces inside the field above.<br />
The same reason will be used for all users.<br />
All usernames must be correct.';
$l['ougc_awards_form_gived'] = 'Select Given Award';
$l['ougc_awards_form_gived_desc'] = 'Please select the given award you want to revoke.';
$l['ougc_awards_form_category'] = 'Category';
$l['ougc_awards_form_category_desc'] = 'Please select the category this award belongs to.';
$l['ougc_awards_form_desc'] = 'Description';
$l['ougc_awards_form_desc_d'] = 'Write a short description for this item.';
$l['ougc_awards_form_image'] = 'Image / Class';
$l['ougc_awards_form_image_d'] = 'Enter the image path or class name for this award below. The following variables are available.<br/><span class="smalltext">&nbsp;&nbsp;{bburl} -> Forum URL<br />
&nbsp;&nbsp;{homeurl} -> Home URL<br />
&nbsp;&nbsp;{imgdir} -> Theme Directory URL<br />
&nbsp;&nbsp;{aid} -> Award ID<br />
&nbsp;&nbsp;{cid} -> Category ID
</span>';
$l['ougc_awards_form_template'] = 'Template Type';
$l['ougc_awards_form_template_d'] = 'Choose if display using an image template, class template, or custom template (advanced).';
$l['ougc_awards_form_template_0'] = 'Image Template';
$l['ougc_awards_form_template_1'] = 'Class Template';
$l['ougc_awards_form_template_2'] = 'Custom Template (Advanced)';
$l['ougc_awards_form_visible'] = 'Visible';
$l['ougc_awards_form_allowrequests'] = 'Allow Requests';
$l['ougc_awards_form_hidden'] = 'Hidden';
$l['ougc_awards_form_visible_d'] = 'Is this item visible?';
$l['ougc_awards_form_active'] = 'Active?';
$l['ougc_awards_form_active_desc'] = 'Is this task active?';
$l['ougc_awards_form_logging'] = 'Enable Logging';
$l['ougc_awards_form_logging_desc'] = 'Do you want to log actions from this task?';
$l['ougc_awards_form_allowrequests_desc'] = 'Do you want to allow users to request from this item?';
$l['ougc_awards_form_pm'] = 'PM';
$l['ougc_awards_form_pm_d'] = 'Write the content of the PM to send when awarded.<br />
	{1} = Username<br/>
	{2} = Award name<br/>
	{3} = Reason<br/>
	{4} = Image<br/>';
$l['ougc_awards_form_type'] = 'Type';
$l['ougc_awards_form_type_d'] = 'Choose if show only in posts, profile or both.';
$l['ougc_awards_form_order'] = 'Order';
$l['ougc_awards_form_order_d'] = 'Order on which this item will be processed.';
$l['ougc_awards_form_type_0'] = 'Both';
$l['ougc_awards_form_type_1'] = 'Profile';
$l['ougc_awards_form_type_2'] = 'Posts';
$l['ougc_awards_button_submit'] = 'Submit';
$l['ougc_awards_button_order'] = 'Update Order';

// ACP Module: Messages
$l['ougc_awards_error_add'] = 'There was a error while creating the new item.';
$l['ougc_awards_success_add'] = 'The item was created successfully.';
$l['ougc_awards_success_edit'] = 'The item was edited successfully.';
$l['ougc_awards_error_invalidcategory'] = 'The selected category is invalid.';
$l['ougc_awards_error_invalidaward'] = 'The selected award is invalid.';
$l['ougc_awards_error_invalidtask'] = 'The selected task is invalid.';
$l['ougc_awards_error_invaliduser'] = 'The selected user is invalid.';
$l['ougc_awards_error_invalidname'] = 'The inserted name is too short.';
$l['ougc_awards_error_invaliddesscription'] = 'The inserted description is too long.';
$l['ougc_awards_error_invalidimage'] = 'The inserted image is too long.';
$l['ougc_awards_error_give'] = 'The selected user already has the selected award.';
$l['ougc_awards_error_giveperm'] = 'You don\'t have permission to edit the selected user.';
$l['ougc_awards_success_give'] = 'The selected user was awarded successfully.';
$l['ougc_awards_success_owner_grant'] = 'The selected user was successfully granted owner status.';
$l['ougc_awards_success_owner_revoke'] = 'The selected owner status was successfully revoked.';
$l['ougc_awards_success_owner_duplicated'] = 'The selected user has owner status already granted.';
$l['ougc_awards_error_revoke'] = 'The selected user doesn\'t has the selected award.';
$l['ougc_awards_error_invalidthread'] = 'You entered an invalid thread value.';
$l['ougc_awards_error_invaliddate'] = 'You entered an invalid dateline value.';
$l['ougc_awards_success_revoke'] = 'The selected award was revoked successfully.';
$l['ougc_awards_success_delete'] = 'The item was deleted successfully.';
$l['ougc_awards_success_cache'] = 'The cache was rebuild successfully.';
$l['ougc_awards_success_prunelogs'] = 'The task logs were successfully pruned.';

// Owner revoke page
$l['ougc_awards_owner_revoke_title'] = 'Revoke Award Owner';
$l['ougc_awards_owner_revoke_desc'] = 'Are you sure you want to revoke the selected owner status?';

// Tasks
$l['ougc_awards_form_usergroups'] = 'User Groups';
$l['ougc_awards_form_usergroups_desc'] = 'Select which user group or user groups the user must be in for the task to run.';
$l['ougc_awards_form_additionalgroups'] = 'Secondary User Groups';
$l['ougc_awards_form_additionalgroups_desc'] = 'Select whether the task should check for additional user groups as well.';
$l['ougc_awards_form_give'] = 'Give Award';
$l['ougc_awards_form_give_desc'] = 'Select the award(s) to give to the user.';
$l['ougc_awards_form_allowmultiple'] = 'Allow Multiple Awards';
$l['ougc_awards_form_allowmultiple_desc'] = 'Select whether the task should give the award even if the user already has it.';
$l['ougc_awards_form_revoke'] = 'Revoke Award';
$l['ougc_awards_form_revoke_desc'] = 'Select the award(s) to be revoked from the user.';
$l['ougc_awards_form_requirements'] = 'Requirements';
$l['ougc_awards_form_requirements_desc'] = 'Select the rules to execute this task on users.';
$l['ougc_awards_form_requirements_post'] = 'Post Count';
$l['ougc_awards_form_requirements_threads'] = 'Thread Count';
$l['ougc_awards_form_requirements_fposts'] = 'Forum Post Count';
$l['ougc_awards_form_requirements_fposts_desc'] = 'Enter the number of posts required in the selected forum. Forum post count must be selected as a required value for this to be included. Select the type of comparison for posts.';
$l['ougc_awards_form_requirements_fthreads'] = 'Forum Thread Count';
$l['ougc_awards_form_requirements_fthreads_desc'] = 'Enter the number of threads required in the selected forum. Forum thread count must be selected as a required value for this to be included. Select the type of comparison for threads.';
$l['ougc_awards_form_requirements_registered'] = 'Time Registered';
$l['ougc_awards_form_requirements_online'] = 'Time Online';
$l['ougc_awards_form_requirements_reputation'] = 'Reputation';
$l['ougc_awards_form_requirements_referrals'] = 'Referrals';
$l['ougc_awards_form_requirements_warnings'] = 'Warning Points';
$l['ougc_awards_form_requirements_newpoints'] = 'Newpoints Points';
$l['ougc_awards_form_requirements_newpoints_desc'] = 'Enter the number of Newpoints points required. Newpoints points must be selected as a required value for this to be included. Select the type of comparison for Newpoints points.';
$l['ougc_awards_form_requirements_previousawards'] = 'Previous Awards';
$l['ougc_awards_form_requirements_previousawards_desc'] = 'Select the awards the user must have required to meet the criteria. Previous awards must be selected as a required value for this to be included.';
$l['ougc_awards_form_requirements_profilefields'] = 'Filled Profile Fields';
$l['ougc_awards_form_requirements_profilefields_desc'] = 'Select the filled profile fields required. Filled profile fields must be selected as a required value for this to be included.';
$l['ougc_awards_form_requirements_mydownloads'] = 'MyDownloads Files Count';
$l['ougc_awards_form_requirements_mydownloads_desc'] = 'Enter the number of MyDownloads files required. MyDownloads files count must be selected as a required value for this to be included. Select the type of comparison for MyDownloads files.';
$l['ougc_awards_form_requirements_myarcadechampions'] = 'MyArcade Championships Count';
$l['ougc_awards_form_requirements_myarcadechampions_desc'] = 'Enter the number of MyArcade championships required. MyArcade championships count must be selected as a required value for this to be included. Select the type of comparison for MyArcade championships.';
$l['ougc_awards_form_requirements_myarcadescores'] = 'MyArcade Score Count';
$l['ougc_awards_form_requirements_myarcadescores_desc'] = 'Enter the number of MyArcade Score required. MyArcade score count must be selected as a required value for this to be included. Select the type of comparison for MyArcade score.';
$l['ougc_awards_form_requirements_ougc_customrep_r'] = 'OUGC Custom Reputation Received';
$l['ougc_awards_form_requirements_ougc_customrep_r_desc'] = 'Enter the number of OUGC Custom Reputation received required for a selected reputation type. OUGC Custom Reputation received must be selected as a required value for this to be included. Select the type of comparison for OUGC Custom Reputation received.';
$l['ougc_awards_form_requirements_ougc_customrep_g'] = 'OUGC Custom Reputation Given';
$l['ougc_awards_form_requirements_ougc_customrep_g_desc'] = 'Enter the number of OUGC Custom Reputation given required for a selected reputation type. OUGC Custom Reputation given must be selected as a required value for this to be included. Select the type of comparison for OUGC Custom Reputation given.';

// ACP Module: Home
$l['ougc_awards_view_image'] = 'Image';
$l['ougc_awards_view_actions'] = 'Options';
$l['ougc_awards_view_empty'] = 'There are currently no items to show.';

// ACP Module: Users
$l['ougc_awards_users_date'] = 'Date';
$l['ougc_awards_users_timestamp'] = 'Time Stamp';
$l['ougc_awards_users_timestamp_d'] = 'Modify time stamp.';
$l['ougc_awards_users_time'] = '{1} <i>at</i> {2}';
$l['ougc_awards_users_empty'] = 'This award currently has no users.';

// PMs
$l['ougc_awards_pm_title'] = 'You have been given the {1} award!';
$l['ougc_awards_pm_noreason'] = 'There was no reason specified.';

// PluginLibrary
$l['ougc_awards_pl_required'] = 'This plugin requires <a href="{1}">PluginLibrary</a> version {2} or later to be uploaded to your forum.';
$l['ougc_awards_pl_old'] = 'This plugin requires <a href="{1}">PluginLibrary</a> version {2} or later, whereas your current version is {3}.';
