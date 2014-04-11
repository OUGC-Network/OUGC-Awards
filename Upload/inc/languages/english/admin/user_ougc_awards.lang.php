<?php

/***************************************************************************
 *
 *   OUGC Awards plugin (/inc/plugins/ougc_awards/languages/english/ougc_awards_extra_vals.lang.php)
 *	 Author: Omar Gonzalez
 *   Copyright: © 2012 Omar Gonzalez
 *   
 *   Website: http://community.mybb.com/user-25096.html
 *
 *   Extend your forum with a powerful awards system.
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
$l['ougc_awards_plugin'] = 'OUGC Awards';
$l['ougc_awards_plugin_d'] = 'This plugin will allow you to give awards to users and show them in posts and profile.';

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
$l['ougc_awards_settinggroup'] = 'OUGC Awards';
$l['ougc_awards_settinggroup_d'] = 'Configure your awards system.';
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
$l['ougc_awards_s_pmuser'] = 'Current User as PM Author';
$l['ougc_awards_s_pmuser_d'] = 'Choose if use current online user as the PM author.';
$l['ougc_awards_s_pmuserid'] = 'PM UserID';
$l['ougc_awards_s_pmuserid_d'] = 'Choose the PM author. -1 = MyBB Engine. (Only works if above is set to [NO])';
$l['ougc_awards_s_multipage'] = 'Enable Multipage';
$l['ougc_awards_s_multipage_d'] = 'Choose whether to show or no to use a multipage for profiles.';