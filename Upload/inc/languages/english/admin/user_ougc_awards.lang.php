<?php

/***************************************************************************
 *
 *	OUGC Awards plugin (/inc/anguages/english/admin/user_ougc_awards.lang.php)
 *	Author: Omar Gonzalez
 *	Copyright: © 2012-2014 Omar Gonzalez
 *
 *	Website: http://omarg.me
 *
 *	Extend your forum with a powerful awards system.
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
$l['setting_group_ougc_awards_desc'] = 'Extend your forum with a powerful awards system.';

// Importer
$l['ougc_awards_import_title'] = 'Import Awards';
$l['ougc_awards_import_desc'] = '<br />&nbsp;&nbsp;&nbsp;<a href="./index.php?module=config-plugins&amp;ougc_awards_import=mybbcentral">Import MyAwards by MyBB-Central</a>';
$l['ougc_awards_import_confirm_mybbcentral'] = 'Are you sure you want to import awards from MyAwards by MyBB-Central?';
$l['ougc_awards_import_end'] = 'Awards Imported Successfully.';

// Settings
$l['setting_ougc_awards_postbit'] = 'Maximum Awards in Posts';
$l['setting_ougc_awards_postbit_desc'] = 'Maximum number of awards to be shown in posts. -1 for unlimited.';
$l['setting_ougc_awards_profile'] = 'Maximum Awards in Profile';
$l['setting_ougc_awards_profile_desc'] = 'Maximum number of awards to be shown in profiles. -1 for unlimited.';
$l['setting_ougc_awards_modcp'] = 'Enable ModCP Panel';
$l['setting_ougc_awards_modcp_desc'] = 'Allows moderators to manage awards from the moderator control panel.';
$l['setting_ougc_awards_modgroups'] = 'Moderator Groups';
$l['setting_ougc_awards_modgroups_desc'] = 'Allowed groups to moderate this feature.';
$l['setting_ougc_awards_pagegroups'] = 'Awards Page Allowed Groups';
$l['setting_ougc_awards_pagegroups_desc'] = 'Allowed groups to view the awards page.';
$l['setting_ougc_awards_perpage'] = 'Items Per Page';
$l['setting_ougc_awards_perpage_desc'] = 'Maximum number of items to show per page in the ModCP queue list.';
$l['setting_ougc_awards_sendpm'] = 'Send PM';
$l['setting_ougc_awards_sendpm_desc'] = 'Do you want to send an PM to users when receiving an award?';
$l['setting_ougc_awards_myalerts'] = 'MyAlerts Integration';
$l['setting_ougc_awards_myalerts_desc'] = 'Do you want to send an alert to users when receiving an award';

// Administrator Permissions
$l['ougc_awards_acp_permissions'] = 'Can manage awards?';

// ACP Module: Tabs
$l['ougc_awards_acp_nav'] = 'Manage Awards';
$l['ougc_awards_tab_view'] = 'View';
$l['ougc_awards_tab_view_d'] = 'View all currently awards to manage.';
$l['ougc_awards_tab_add'] = 'Add';
$l['ougc_awards_tab_add_d'] = 'Add a new award to the award list.';
$l['ougc_awards_tab_edit'] = 'Edit';
$l['ougc_awards_tab_edit_d'] = 'Edit a existing award.';
$l['ougc_awards_tab_give'] = 'Give';
$l['ougc_awards_tab_give_d'] = 'Give award.';
$l['ougc_awards_tab_revoke'] = 'Revoke';
$l['ougc_awards_tab_revoke_d'] = 'Revoke award.';
$l['ougc_awards_tab_users'] = 'View Users';
$l['ougc_awards_tab_users_d'] = 'View users with this award.';
$l['ougc_awards_tab_edit_user'] = 'Edit User Award';
$l['ougc_awards_tab_edit_user_d'] = 'Edit this user\'s award data.';
$l['ougc_awards_tab_delete'] = 'Delete';
$l['ougc_awards_tab_cache'] = 'Rebuilt Cache';

// ACP Module: Form
$l['ougc_awards_form_add'] = 'Add New Award';
$l['ougc_awards_form_name'] = 'Name';
$l['ougc_awards_form_name_d'] = 'Insert a short name for this award.';
$l['ougc_awards_form_username'] = 'Username';
$l['ougc_awards_form_username_d'] = 'Insert the username of the user to give/revoke the selected award award.';
$l['ougc_awards_form_reason'] = 'Reason';
$l['ougc_awards_form_reason_d'] = 'Insert a reason for giving this award';
$l['ougc_awards_form_desc'] = 'Description';
$l['ougc_awards_form_desc_d'] = 'Write a short description for this award.';
$l['ougc_awards_form_image'] = 'Image';
$l['ougc_awards_form_image_d'] = 'Image of this award.<br/><span class="smalltext">&nbsp;&nbsp;{bburl} -> Forum URL<br />
&nbsp;&nbsp;{homeurl} -> Home URL<br />
&nbsp;&nbsp;{imgdir} -> Theme Directory URL
</span>';
$l['ougc_awards_form_visible'] = 'Visible';
$l['ougc_awards_form_hidden'] = 'Hidden';
$l['ougc_awards_form_visible_d'] = 'Is this award is visible?';
$l['ougc_awards_form_pm'] = 'PM';
$l['ougc_awards_form_pm_d'] = 'Write the content of the PM to send when awarded.<br />
	{1} = Username<br/>
	{2} = Award name<br/>
	{3} = Reason<br/>
	{4} = Image<br/>';
$l['ougc_awards_form_type'] = 'Type';
$l['ougc_awards_form_type_d'] = 'Choose if show only in posts, profile or both.';
$l['ougc_awards_form_order'] = 'Order';
$l['ougc_awards_form_order_d'] = 'Order on which this award will be processed.';
$l['ougc_awards_form_type_0'] = 'Both';
$l['ougc_awards_form_type_1'] = 'Profile';
$l['ougc_awards_form_type_2'] = 'Posts';
$l['ougc_awards_button_submit'] = 'Submit';
$l['ougc_awards_button_order'] = 'Update Order';

// ACP Module: Messages
$l['ougc_awards_error_add'] = 'There was a error while creating the new award';
$l['ougc_awards_success_add'] = 'The award was created successfully.';
$l['ougc_awards_success_edit'] = 'The award/user was edited successfully.';
$l['ougc_awards_error_invalidaward'] = 'The selected award is invalid.';
$l['ougc_awards_error_invaliduser'] = 'The selected user is invalid.';
$l['ougc_awards_error_invalidname'] = 'The inserted name is too short.';
$l['ougc_awards_error_invaliddesscription'] = 'The inserted description is too long.';
$l['ougc_awards_error_invalidimage'] = 'The inserted image is too long.';
$l['ougc_awards_error_give'] = 'The selected user already has the selected award.';
$l['ougc_awards_error_giveperm'] = 'You don\'t have permission to edit the selected user.';
$l['ougc_awards_success_give'] = 'The selected user was awarded successfully.';
$l['ougc_awards_error_revoke'] = 'The selected user doesn\'t has the selected award.';
$l['ougc_awards_success_revoke'] = 'The selected award was revoked successfully.';
$l['ougc_awards_success_delete'] = 'The award was deleted successfully.';
$l['ougc_awards_success_cache'] = 'The cache was rebuild successfully.';

// ACP Module: Home
$l['ougc_awards_view_image'] = 'Image';
$l['ougc_awards_view_actions'] = 'Options';
$l['ougc_awards_view_empty'] = 'There are currently no awards to show.';

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