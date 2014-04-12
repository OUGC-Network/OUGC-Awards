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

// Plugin information.
$l['ougc_awards'] = 'OUGC Awards';
$l['ougc_awards_d'] = 'This plugin will allow you to give awards to users and show them in posts and profile.';

// Awards management page.
$l['ougc_awards_acp_nav'] = 'Manage Awards';
$l['ougc_awards_acp_permissions'] = 'Can manage awards?';

// Tabs.
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
$l['ougc_awards_tab_users_d'] = 'View users that currently has a defined award.';
$l['ougc_awards_tab_edit_user'] = 'Edit User Award';
$l['ougc_awards_tab_edit_user_d'] = 'Edit this user\'s award data.';
$l['ougc_awards_tab_delete'] = 'Delete';
$l['ougc_awards_tab_cache'] = 'Rebuilt Cache';

// Form lang
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
$l['ougc_awards_form_image_d'] = 'Image of this award.';
$l['ougc_awards_form_visible'] = 'Visible';
$l['ougc_awards_form_hidden'] = 'Hidden';
$l['ougc_awards_form_visible_d'] = 'Does this award is visible at all?';
$l['ougc_awards_form_pm'] = 'PM';
$l['ougc_awards_form_pm_d'] = 'Write the content of the PM to send to users when awarded. BBCode Allowed<br />
	{1} = Username<br/>
	{2} = Award name<br/>
	{3} = Reason<br/>
	{4} = Image<br/>';
$l['ougc_awards_form_type'] = 'Type';
$l['ougc_awards_form_type_d'] = 'Choose if show only in posts, profile or both.';
$l['ougc_awards_form_type_0'] = 'Both';
$l['ougc_awards_form_type_1'] = 'Profile';
$l['ougc_awards_form_type_2'] = 'Posts';
$l['ougc_awards_button_submit'] = 'Submit';

// Error / success message
$l['ougc_awards_error_add'] = 'There was a error while creating a new award';
$l['ougc_awards_success_add'] = 'The award was created successfully.';
$l['ougc_awards_error_edit'] = 'There was a error editing the award/user.';
$l['ougc_awards_success_edit'] = 'The award/user was edited successfully.';
$l['ougc_awards_error_invaliduser'] = 'The selected user is invalid.';
$l['ougc_awards_error_give'] = 'The selected user already has this award.';
$l['ougc_awards_error_notgive'] = 'The selected user already has not this award.';
$l['ougc_awards_error_giveperm'] = 'You don\'t have permission to edit the selected user.';
$l['ougc_awards_success_give'] = 'User awarded successfully.';
$l['ougc_awards_error_revoke'] = 'The selected user doesn\'t exist or it doesn\'t have this award.';
$l['ougc_awards_success_revoke'] = 'Awards was revoked from the selected user successfully.';
$l['ougc_awards_error_delete'] = 'There was a error deleting the award.';
$l['ougc_awards_success_delete'] = 'The award was deleted successfully.';
$l['ougc_awards_success_cache'] = 'The cache was rebuild successfully.';

// View all
$l['ougc_awards_view_image'] = 'Image';
$l['ougc_awards_view_actions'] = 'Options';
$l['ougc_awards_view_empty'] = 'There are currently no awards to show.';

// Users action
$l['ougc_awards_users_date'] = 'Date';
$l['ougc_awards_users_timestamp'] = 'Time Stamp';
$l['ougc_awards_users_timestamp_d'] = 'Modify time stamp.';
$l['ougc_awards_users_time'] = '{1} <i>at</i> {2}';
$l['ougc_awards_users_empty'] = 'This award currently has no users.';

// Settings
$l['ougc_awards_s_power'] = 'Activate Plugin ';
$l['ougc_awards_s_power_d'] = 'Turn this on/off without losing any data.';
$l['ougc_awards_s_postbit'] = 'Maximum Awards in Posts';
$l['ougc_awards_s_postbit_d'] = 'Enter a maximum number of awards to be shown at posts. 0 = none, -1 = unlimited.';
$l['ougc_awards_s_profile'] = 'Maximum Awards in Profile';
$l['ougc_awards_s_profile_d'] = 'Enter a maximum number of awards to be shown at profile. 0 = none, -1 = unlimited.';
$l['ougc_awards_s_hidemcp'] = 'Show Hidden Awards in ModCP';
$l['ougc_awards_s_hidemcp_d'] = 'Choose [YES] to show hidden awards in the ModCP (this settings doesn\'t affect other awards areas).';
$l['ougc_awards_s_moderators'] = 'Awards Extra Moderators';
$l['ougc_awards_s_moderators_d'] = 'Insert a comma separated list of groups that can moderate awards from the ModCP.';
$l['ougc_awards_s_pmuser'] = 'PM UserID';
$l['ougc_awards_s_pmuser_d'] = 'Choose the PM author. Leave empty to disable.<br />
 -1 = MyBB Engine.<br />
 -2 = Current user';
$l['settings_ougc_awards_perpage'] = 'Items Per Page';
$l['settings_ougc_awards_perpage_desc'] = 'Maximun number of items to show per page in the ModCP queue list.';
$l['ougc_awards_s_checkperm'] = 'Check Permissions';
$l['ougc_awards_s_checkperm_d'] = 'Check if current user has permission to edit end user before giving/revoking awards.';
$l['ougc_awards_s_enablemod'] = 'Enable Moderation Panel';
$l['ougc_awards_s_enablemod_d'] = 'Enable moderators to give or revoke awards from the moderation panel.';
$l['ougc_awards_s_orderby'] = 'Order By';
$l['ougc_awards_s_orderby_d'] = 'Order awards being show by which method.';

// PluginLibrary
$l['ougc_awards_plreq'] = 'This plugin requires <a href="{1}">PluginLibrary</a> version {2} or later to be uploaded to your forum.';
$l['ougc_awards_plold'] = 'This plugin requires PluginLibrary version {1} or later, whereas your current version is {2}. Please do update <a href="{3}">PluginLibrary</a>.';
