<?php

/***************************************************************************
 *
 *   OUGC Awards plugin (/inc/plugins/ougc_awards.php)
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

// Die if IN_MYBB is not defined, for security reasons.
if(!defined('IN_MYBB'))
{
	die('This file cannot be accessed directly.');
}

// Run the ACP hooks.
$plugin_path = MYBB_ROOT.'inc/plugins/ougc_awards/plugin.php';
if(defined('IN_ADMINCP'))
{
	$plugins->add_hook('admin_user_menu', 'ougc_awards_menu', 10, $plugin_path);
	$plugins->add_hook('admin_user_action_handler', 'ougc_awards_action_handler', 10, $plugin_path);
	$plugins->add_hook('admin_load', 'ougc_awards_admin_load', 10, $plugin_path);
	$plugins->add_hook('admin_user_permissions', 'ougc_awards_admin_permissions', 10, $plugin_path);
}
// Anything else.
else
{
	$plugins->add_hook('global_start', 'ougc_awards_cachetempl');
	$plugins->add_hook('postbit_prev', 'ougc_awards_postbit', 10, $plugin_path); // Preview post postbit
	$plugins->add_hook('postbit_pm', 'ougc_awards_postbit', 10, $plugin_path); // PMs postbit
	$plugins->add_hook('postbit_announcement', 'ougc_awards_postbit', 10, $plugin_path); // Annoucements postbit
	$plugins->add_hook('postbit', 'ougc_awards_postbit', 10, $plugin_path); // Normal postbit
	$plugins->add_hook('member_profile_end', 'ougc_awards_profile', 10, $plugin_path);
	$plugins->add_hook('modcp_start', 'ougc_awards_modcp', 10, $plugin_path);
}

// Necessary plugin information for the ACP plugin manager.
function ougc_awards_info()
{
	require_once MYBB_ROOT.'inc/plugins/ougc_awards/plugin.php';
	return ougc_awards_plugin_info();
}

// Activate the plugin.
function ougc_awards_activate()
{
	require_once MYBB_ROOT.'inc/plugins/ougc_awards/plugin.php';
	ougc_awards_plugin_activate();
}

// Deactivate the plugin.
function ougc_awards_deactivate()
{
	require_once MYBB_ROOT.'inc/plugins/ougc_awards/plugin.php';
	ougc_awards_plugin_deactivate();
}

// Install the plugin.
function ougc_awards_install()
{
	require_once MYBB_ROOT.'inc/plugins/ougc_awards/plugin.php';
	ougc_awards_plugin_install();
}

// Is the plugin installed?
function ougc_awards_is_installed()
{
	require_once MYBB_ROOT.'inc/plugins/ougc_awards/plugin.php';
	return ougc_awards_plugin_is_installed();
}

// Uninstall the plugin.
function ougc_awards_uninstall()
{
	require_once MYBB_ROOT.'inc/plugins/ougc_awards/plugin.php';
	ougc_awards_plugin_uninstall();
}


//\\ FORUM SECTION //\\
// Cache our templates if plugin is active.
function ougc_awards_cachetempl()
{
	global $mybb;
	if($mybb->settings['ougc_awards_power'] == 1)
	{
		global $templatelist;
		if(THIS_SCRIPT == 'showthread.php')
		{
			$templatelist .= ', ougc_awards_image, postbit_ougc_awards';
		}
		if(THIS_SCRIPT == 'member.php' && $mybb->input['action'] == 'profile')
		{
			$templatelist .= ', ougc_awards_image, member_profile_ougc_awards_row, member_profile_ougc_awards, member_profile_ougc_awards_row_empty';
		}
		if(THIS_SCRIPT == 'modcp.php' && $mybb->input['action'] == 'awards')
		{
			$templatelist .= ', modcp_ougc_awards_nav, ougc_awards_image, modcp_ougc_awards_list_award, modcp_ougc_awards_list, modcp_ougc_awards, modcp_ougc_awards_manage_reason, modcp_ougc_awards_manage, modcp_ougc_awards_list_empty';
		}
		elseif(THIS_SCRIPT == 'modcp.php')
		{
			$templatelist .= ', modcp_ougc_awards_nav';
		}
	}
}
?>