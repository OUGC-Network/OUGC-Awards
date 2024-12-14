<?php

/***************************************************************************
 *
 *    ougc Awards plugin (/inc/anguages/english/admin/user_ougc_awards.lang.php)
 *    Author: Omar Gonzalez
 *    Copyright: © 2012 Omar Gonzalez
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

// Plugin API
$l['ougcAwards'] = 'ougc Awards';
$l['ougcAwardsDescription'] = 'Manage a powerful awards system for your community.';

// Plugin API
$l['setting_group_ougc_awards'] = 'Awards';
$l['setting_group_ougc_awards_desc'] = 'Manage a powerful awards system for your community.';

// Importer
$l['ougc_awards_import_title'] = 'Import Awards';
$l['ougcAwardsImportDescription'] = '<br />&nbsp;&nbsp;&nbsp;<a href="./index.php?module=config-plugins&amp;ougc_awards_import=myawards">Import From MyAwards</a>
<br />&nbsp;&nbsp;&nbsp;<a href="./index.php?module=config-plugins&amp;ougc_awards_import=nickawards">Import From Nickman\'s Awards System</a>';
$l['ougc_awards_import_confirm_myawards'] = 'Are you sure you want to import awards from MyAwards?';
$l['ougc_awards_import_confirm_nickawards'] = 'Are you sure you want to import awards from Nickman\'s award system?';
$l['ougc_awards_import_end'] = 'Awards Imported Successfully.';
$l['ougc_awards_import_error'] = 'There was an error trying to import the selected awards. The "{1}" table doesn\'t seems to exists.';

// Settings
$l['setting_ougc_awards_showInPosts'] = 'Maximum Awards in Posts';
$l['setting_ougc_awards_showInPosts_desc'] = 'Maximum number of awards to be shown in posts.';
$l['setting_ougc_awards_showInPostsPresets'] = 'Maximum Presets in Posts';
$l['setting_ougc_awards_showInPostsPresets_desc'] = 'Type the maximum preset awards to display in posts.';
$l['setting_ougc_awards_showInProfile'] = 'Maximum Awards in Profile';
$l['setting_ougc_awards_showInProfile_desc'] = 'Maximum number of awards to be shown in profiles.';
$l['setting_ougc_awards_showInProfilePresets'] = 'Maximum Preset Awards in Profiles';
$l['setting_ougc_awards_showInProfilePresets_desc'] = 'Type the maximum preset awards to display in profiles.';
$l['setting_ougc_awards_perPage'] = 'Items Per Page';
$l['setting_ougc_awards_perPage_desc'] = 'Maximum number of items to show per page or within listings.';
$l['setting_ougc_awards_groupsView'] = 'View Groups';
$l['setting_ougc_awards_groupsView_desc'] = 'Allowed groups to view the awards page.';
$l['setting_ougc_awards_groupsPresets'] = 'Presets Allowed Groups';
$l['setting_ougc_awards_groupsPresets_desc'] = 'Select which groups are allowed to use and create presets.';
$l['setting_ougc_awards_presetsMaximum'] = 'Maximum Presets';
$l['setting_ougc_awards_presetsMaximum_desc'] = 'Select the maximum amount of presets can create.';
$l['setting_ougc_awards_groupsModerators'] = 'Moderator Groups';
$l['setting_ougc_awards_groupsModerators_desc'] = 'Allowed groups to manage awards.';
$l['setting_ougc_awards_statsEnabled'] = 'Enable Stats';
$l['setting_ougc_awards_statsEnabled_desc'] = 'Do you want to enable the top and last granted users in the stats page?';
$l['setting_ougc_awards_statsLatestGrants'] = 'latest Grants in Stats';
$l['setting_ougc_awards_statsLatestGrants_desc'] = 'Type the maximum number of latest grants to show in the stats page.';
$l['setting_ougc_awards_notificationPrivateMessage'] = 'Send PM';
$l['setting_ougc_awards_notificationPrivateMessage_desc'] = 'Do you want to send an PM to users when receiving an award?';
$l['setting_ougc_awards_grantDefaultVisibleStatus'] = 'Grant Default Visible Status';
$l['setting_ougc_awards_grantDefaultVisibleStatus_desc'] = 'Select the visible status of awards when granting awards to users. If set to <code>No</code>, users will need to set their awards as visible in the sorting page from withing the UserCP.';
$l['setting_ougc_awards_uploadPath'] = 'Uploads Path';
$l['setting_ougc_awards_uploadPath_desc'] = 'Type the path where the awards images will be uploaded.';
$l['setting_ougc_awards_uploadDimensions'] = 'Uploads Dimensions';
$l['setting_ougc_awards_uploadDimensions_desc'] = 'Type the maximum dimensions for the awards images. Default <code>32|32</code>.';
$l['setting_ougc_awards_uploadSize'] = 'Uploads Size';
$l['setting_ougc_awards_uploadSize_desc'] = 'Type the maximum size in bytes for the awards images. Default <code>50</code>.';

// ACP Module: Tabs
$l['ougc_awards_acp_nav'] = 'Manage Awards';

$l['ougcAwardsTaskRan'] = 'The awards task ran successfully.';

$l['ougcAwardsPluginLibrary'] = 'This plugin requires <a href="{1}">PluginLibrary</a> version {2} or later to be uploaded to your forum.';
