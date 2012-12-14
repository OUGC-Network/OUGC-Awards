<?php

/***************************************************************************
 *
 *   OUGC Awards plugin (/awards_update.php)
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

// Boring stuff..
define("IN_MYBB", 1);
require_once "./global.php";

// Only administratos can run this file.
if($mybb->usergroup['cancp'] != 1)
{
	error_no_permission();
}

// We need out plugin file.
require_once MYBB_ROOT.'inc/plugins/ougc_awards/plugin.php';


global $lang, $db;
ougc_awards_lang_load();

// Modify our users column.
$db->modify_column('ougc_awards', 'users', "text NOT NULL DEFAULT ''");

// Inser our new settings
$set1 = $db->fetch_field($db->simple_select('settings', 'sid', 'name="ougc_awards_multipage"'), 'sid');
$set2 = $db->fetch_field($db->simple_select('settings', 'sid', 'name="ougc_awards_pmuser" AND disporder!="8"'), 'sid');
$set3 = $db->fetch_field($db->simple_select('settings', 'sid', 'name="ougc_awards_pmuserid"'), 'sid');
$setgroup = $db->fetch_field($db->simple_select('settinggroups', 'gid', 'name="ougc_awards"'), 'gid');

if(!$set1 && $setgroup)
{
	$db->insert_query('settings',
		array(
			'name'			=>	$db->escape_string('ougc_awards_multipage'),
			'title'			=>	$db->escape_string("Enable Multipage"),
			'description'	=>	$db->escape_string("Choose whether to show or no to use a multipage for profiles."),
			'optionscode'	=>	'yesno',
			'value'			=>	0,
			'disporder'		=>	7,
			'gid'			=>	intval($setgroup)
		)
	);
}

if(!$set3 && $setgroup)
{
	$db->insert_query('settings',
		array(
			'name'			=>	$db->escape_string('ougc_awards_pmuserid'),
			'title'			=>	$db->escape_string("PM UserID"),
			'description'	=>	$db->escape_string("Choose the PM author. -1 = MyBB Engine. (Only works if above is set to [NO])"),
			'optionscode'	=>	'text',
			'value'			=>	-1,
			'disporder'		=>	9,
			'gid'			=>	intval($setgroup)
		)
	);
}

if($set2)
{
	$db->update_query('settings', array('disporder' => '8'), "sid='{$set2}'");
}
rebuild_settings();

// Modify some templates.
require_once MYBB_ROOT.'/inc/adminfunctions_templates.php';
find_replace_templatesets('ougc_awards_page_view_row', '#'.preg_quote('{$user[\'username\']}').'#', '{$gived[\'username\']}', 0);
find_replace_templatesets('member_profile_ougc_awards', '#'.preg_quote('</table>').'#', '{$multipage}', 0);

// Delete one template.
$db->delete_query('templates', "title IN('ougc_awards_image') AND sid='-2'");

// Now we need to refresh the cache.
ougc_awards_update_cache();

echo "Done";
?>