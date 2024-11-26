<?php

/***************************************************************************
 *
 *    OUGC Awards plugin (/inc/anguages/english/admin/user_ougc_awards.lang.php)
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
$l['ougcAwards'] = 'OUGC Awards';
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
$l['setting_ougc_awards_enablestatspage_desc'] = 'Do you want to enable the top and last granted users in the stats page?';
$l['setting_ougc_awards_myalerts'] = 'MyAlerts Integration';
$l['setting_ougc_awards_myalerts_desc'] = 'Do you want to send an alert to users when receiving an award';
$l['setting_ougc_awards_sort_visible_default'] = 'Visible as Default Sort Status';
$l['setting_ougc_awards_sort_visible_default_desc'] = 'Select the visible status of awards when granting awards to users. If set to <code>No</code>, users will need to set their awards as visible in the sorting page from withing the UserCP.';
$l['setting_ougc_awards_presets_groups'] = 'Presets Allowed Groups';
$l['setting_ougc_awards_presets_groups_desc'] = 'Select which groups are allowed to use and create presets.';
$l['setting_ougc_awards_presets_maximum'] = 'Maximum Presets';
$l['setting_ougc_awards_presets_maximum_desc'] = 'Select the maximum amount of presets can create.';
$l['setting_ougc_awards_presets_post'] = 'Maximum Presets in Posts';
$l['setting_ougc_awards_presets_post_desc'] = 'Type the maximum preset awards to display in posts.';
$l['setting_ougc_awards_presets_profile'] = 'Maximum Preset Awards in Profiles';
$l['setting_ougc_awards_presets_profile_desc'] = 'Type the maximum preset awards to display in profiles.';

// ACP Module: Tabs
$l['ougc_awards_acp_nav'] = 'Manage Awards';

$l['ougcAwardsTaskRan'] = 'The awards task ran successfully.';

$l['ougcAwardsPluginLibrary'] = 'This plugin requires <a href="{1}">PluginLibrary</a> version {2} or later to be uploaded to your forum.';
