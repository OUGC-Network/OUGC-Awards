<?php

/***************************************************************************
 *
 *	OUGC Awards plugin (/inc/plugins/ougc_awards.php)
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

// Die if IN_MYBB is not defined, for security reasons.
defined('IN_MYBB') or die('Direct initialization of this file is not allowed.');

// Run/Add Hooks
if(defined('IN_ADMINCP'))
{
	// Add menu to ACP
	$plugins->add_hook('admin_user_menu', create_function('&$args', 'global $lang, $ougc_awards;	$ougc_awards->lang_load();	$args[] = array(\'id\' => \'ougc_awards\', \'title\' => $lang->ougc_awards_acp_nav, \'link\' => \'index.php?module=user-ougc_awards\');'));

	// Add our action handler to config module
	$plugins->add_hook('admin_user_action_handler', create_function('&$args', '$args[\'ougc_awards\'] = array(\'active\' => \'ougc_awards\', \'file\' => \'ougc_awards.php\');'));

	// Insert our plugin into the admin permissions page
	$plugins->add_hook('admin_user_permissions', create_function('&$args', 'global $lang, $ougc_awards;	$ougc_awards->lang_load();	$args[\'ougc_awards\'] = $lang->ougc_awards_acp_permissions;'));// Insert our menu at users section.
}
else
{
	global $templatelist;

	if(isset($templatelist))
	{
		$templatelist .= ',';
	}
	else
	{
		$templatelist = '';
	}

	switch(THIS_SCRIPT)
	{
		case 'showthread.php':
		case 'newreply.php':
		case 'newthread.php':
		case 'editpost.php':
			$plugins->add_hook('postbit_prev', 'ougc_awards_postbit');
			$plugins->add_hook('postbit', 'ougc_awards_postbit');
			$templatelist .= 'ougcawards_postbit';
			break;
		case 'member.php':
			global $mybb;

			if($mybb->input['action'] == 'profile')
			{
				$plugins->add_hook('member_profile_end', 'ougc_awards_profile');
				$templatelist .= 'ougcawards_profile_row, ougcawards_profile, ougcharol_select_image, ougcharol_select_profile';
			}
			break;
		case 'modcp.php':
			global $mybb;

			$plugins->add_hook('modcp_start', 'ougc_awards_modcp');
			$templatelist .= 'ougcawards_modcp_nav';
			if($mybb->input['action'] == 'awards')
			{
				$templatelist .= ', ougcawards_modcp_list_award, ougcawards_modcp_list, ougcawards_modcp, ougcawards_modcp_manage_reason, ougcawards_modcp_manage';
			}
			break;
		case 'private.php':
			$plugins->add_hook('postbit_pm', 'ougc_awards_postbit');
			$templatelist .= 'ougcawards_postbit';
			break;
		case 'announcements.php':
			$plugins->add_hook('postbit_announcement', 'ougc_awards_postbit');
			$templatelist .= 'ougcawards_postbit';
			break;
	}
}

// PLUGINLIBRARY
defined('PLUGINLIBRARY') or define('PLUGINLIBRARY', MYBB_ROOT.'inc/plugins/pluginlibrary.php');

// Plugin API
function ougc_awards_info()
{
	global $lang, $awards;
	$awards->lang_load();

	return array(
		'name'			=> 'OUGC Awards',
		'description'	=> $lang->setting_group_ougc_awards_desc,
		'website'		=> 'http://mods.mybb.com/view/ougc-awards',
		'author'		=> 'Omar G.',
		'authorsite'	=> 'http://omarg.me',
		'version'		=> '1.0.7',
		'versioncode'	=> 1070,
		'compatibility'	=> '16*',
		'guid'			=> '8172205c3142e4295ed5ed3a7e8f40d6',
		'myalerts'		=> 105,
		'pl'			=> array(
			'version'	=> 12,
			'url'		=> 'http://mods.mybb.com/view/pluginlibrary'
		)
	);
}

// _activate() routine
function ougc_awards_activate()
{
	global $PL, $lang, $cache, $awards;
	$awards->lang_load();
	ougc_awards_deactivate();

	// Add settings group
	$PL->settings('ougc_awards', $lang->setting_group_ougc_awards, $lang->setting_group_ougc_awards_desc, array(
		'postbit'	=> array(
		   'title'			=> $lang->setting_ougc_awards_postbit,
		   'description'	=> $lang->setting_ougc_awards_postbit_desc,
		   'optionscode'	=> 'text',
			'value'			=>	4,
		),
		'profile'	=> array(
		   'title'			=> $lang->setting_ougc_awards_profile,
		   'description'	=> $lang->setting_ougc_awards_profile_desc,
		   'optionscode'	=> 'text',
			'value'			=>	4,
		),
		'modcp'	=> array(
		   'title'			=> $lang->setting_ougc_awards_modcp,
		   'description'	=> $lang->setting_ougc_awards_modcp_desc,
		   'optionscode'	=> 'yesno',
			'value'			=>	1,
		),
		'moderators'	=> array(
		   'title'			=> $lang->setting_ougc_awards_moderators,
		   'description'	=> $lang->setting_ougc_awards_moderators_desc,
		   'optionscode'	=> 'text',
			'value'			=>	'',
		),
		'perpage'	=> array(
		   'title'			=> $lang->setting_ougc_awards_perpage,
		   'description'	=> $lang->setting_ougc_awards_perpage_desc,
		   'optionscode'	=> 'text',
			'value'			=>	20,
		),
		'sendpm'			=> array(
		   'title'			=> $lang->setting_ougc_awards_sendpm,
		   'description'	=> $lang->setting_ougc_awards_sendpm_desc,
		   'optionscode'	=> 'yesno',
		   'value'			=> 1
		),
		'myalerts'	=> array(
		   'title'			=> $lang->setting_ougc_awards_myalerts,
		   'description'	=> $lang->setting_ougc_awards_myalerts_desc,
		   'optionscode'	=> 'yesno',
			'value'			=>	0,
		)
	));

	// Add template group
	$PL->templates('ougcawards', $lang->ougc_awards, array(
		'modcp_manage'					=> '<form action="modcp.php" method="post">
<input type="hidden" name="action" value="awards" />
<input type="hidden" name="manage" value="{$mybb->input[\'manage\']}" />
<input type="hidden" name="aid" value="{$mybb->input[\'aid\']}" />
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
	<tr>
		<td class="thead" colspan="2">
			<strong>{$lang->ougc_awards_modcp_title_give}</strong>
		</td>
	</tr>
	<tr>
		<td class="trow1" width="25%"><strong>{$lang->ougc_awards_modcp_username}:</strong></td>
		<td class="trow1" width="75%"><input type="text" class="textbox" name="username" id="username" value="{$mybb->input[\'username\']}" size="25" /></td>
	</tr>
	{$reason}
</table>
<br />
<div align="center">
	<input type="submit" class="button" value="{$lang->ougc_awards_modcp_give}" />
</div>
</form>
<script type="text/javascript" src="{$mybb->settings[\'bburl\']}/jscripts/autocomplete.js?ver=1400"></script>
<script type="text/javascript">
<!--
	if(use_xmlhttprequest == "1")
	{
		new autoComplete("username", "xmlhttp.php?action=get_users", {valueSpan: "username"});
	}
// -->
</script>',
		'modcp_nav'						=> '<tr><td class="trow1 smalltext"><a href="modcp.php?action=awards" class="modcp_nav_item" style="background: url(\'images/ougc_awards/icon.png\') no-repeat left center;">{$lang->ougc_awards_modcp_nav}</a></td></tr>',
		'modcp'							=> '<html>
	<head>
		<title>{$mybb->settings[\'bbname\']} - {$lang->ougc_awards_modcp_nav}</title>
		{$headerinclude}
	</head>
	<body>
		{$header}
		<table width="100%" border="0" align="center">
			<tr>
				{$modcp_nav}
				<td valign="top">
				{$errors}
				{$content}
				</td>
			</tr>
		</table>
		{$footer}
	</body>
</html>',
		'modcp_list_empty'	=> '<tr>
	<td class="trow1" colspan="4" align="center">
		{$lang->ougc_awards_modcp_list_empty}
	</td>
</tr>',
		'modcp_list'	=> '<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
	<tr>
		<td class="thead" colspan="4">
<a href="{$mybb->settings[\'bburl\']}/modcp.php?action=awards&amp;manage=update_cache" style="float:right;" class="smalltext">({$lang->ougc_awards_modcp_cache})</a>
			<strong>{$lang->ougc_awards_modcp_nav}</strong>
		</td>
	</tr>
	<tr>
		<td class="tcat smalltext" colspan="4">
			<strong>{$lang->ougc_awards_modcp_list_desc}</strong>
		</td>
	</tr>
	{$awards}
</table>
<span class="smalltext">{$lang->ougc_awards_modcp_list_note}</span>',
		'modcp_list_award'	=> '<tr>
	<td class="trow1" align="center" width="1%"><a href="{$mybb->settings[\'bburl\']}/awards.php?view={$award[\'aid\']}" title="{$award[\'name\']}"><img src="{$award[\'image\']}" alt="{$award[\'name\']}" /></a></td>
	<td class="trow1" width="15%">{$award[\'name\']}</td>
	<td class="trow1">{$award[\'description\']}</td>
	<td class="trow1" align="center" width="15%">[<a href="{$mybb->settings[\'bburl\']}/modcp.php?action=awards&amp;manage=give&amp;aid={$award[\'aid\']}">{$lang->ougc_awards_modcp_give}</a>&nbsp;|&nbsp;<a href="{$mybb->settings[\'bburl\']}/modcp.php?action=awards&amp;manage=revoke&amp;aid={$award[\'aid\']}">{$lang->ougc_awards_modcp_revoke}</a>]</td>
</tr>',
		'modcp_manage_reason'	=> '<tr>
	<td class="trow2" width="25%"><strong>{$lang->ougc_awards_modcp_reason}:</strong></td>
	<td class="trow2" width="75%"><textarea type="text" class="textarea" name="reason" id="reason" rows="4" cols="40">{$mybb->input[\'reason\']}</textarea></td>
</tr>',
		'postbit'	=> '{$br}<a href="{$mybb->settings[\'bburl\']}/awards.php?view={$award[\'aid\']}" title="{$award[\'name\']}"><img src="{$award[\'image\']}" alt="{$award[\'name\']}" /></a>',
		'profile'	=> '<br />
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="2"><a style="float:right;" href="{$mybb->settings[\'bburl\']}/awards.php?user={$memprofile[\'uid\']}">{$lang->ougc_awards_profile_viewall}</a><strong>{$lang->ougc_awards_profile_title}</strong></td>
</tr>
{$awards}
</table>
{$multipage}',
		'profile_row'	=> '<tr>
	<td class="tcat" rowspan="2" width="1">
		<a href="{$mybb->settings[\'bburl\']}/awards.php?view={$award[\'aid\']}" title="{$award[\'name\']}"><img src="{$award[\'image\']}" alt="{$award[\'name\']}" /></a>
	</td>
	<td class="{$trow} smalltext" >
		<span style="float:right;">{$award[\'date\']}</span> {$award[\'name\']}
	</td>
</tr>
<tr>
	<td class="{$trow}" >
		{$award[\'reason\']}
	</td>
</tr>',
		'profile_row_empty'	=> '<tr>
	<td class="trow1" colspan="2">
		{$lang->ougc_awards_profile_empty}
	</td>
</tr>',
		'page'	=> '<html>
	<head>
		<title>{$mybb->settings[\'bbname\']} - {$lang->ougc_awards_page_title}</title>
		{$headerinclude}
	</head>
	<body>
		{$header}
		{$content}
		{$footer}
	</body>
</html>',
		'page_list'	=> '<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
	<tr>
		<td class="thead" colspan="4">
			<strong>{$lang->ougc_awards_page_title}</strong>
		</td>
	</tr>
	<tr>
		<td class="tcat smalltext" align="center" width="1%"><strong>{$lang->ougc_awards_page_list_award}</strong></td>
		<td class="tcat smalltext" width="15%"><strong>{$lang->ougc_awards_page_list_name}</strong></td>
		<td class="tcat smalltext"><strong>{$lang->ougc_awards_page_list_description}</strong></td>
	</tr>
	{$award_list}
</table>',
		'page_list_award'	=> '<tr>
	<td class="{$trow}" align="center"><a href="{$mybb->settings[\'bburl\']}/awards.php?view={$award[\'aid\']}" title="{$award[\'name\']}"><img src="{$award[\'image\']}" alt="{$award[\'name\']}" /></a></td>
	<td class="{$trow}"><a href="{$mybb->settings[\'bburl\']}/awards.php?view={$award[\'aid\']}" title="{$award[\'name\']}">{$award[\'name\']}</a></td>
	<td class="{$trow}">{$award[\'description\']}</td>
</tr>',
		'page_list_empty'	=> '<tr>
	<td class="trow1" colspan="4" align="center">
		{$lang->ougc_awards_page_list_empty}
	</td>
</tr>',
		'page_user'	=> '<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
	<tr>
		<td class="thead" colspan="4">
			<strong>{$user[\'username\']}</strong>
		</td>
	</tr>
	<tr>
		<td class="tcat smalltext" align="center" width="1%"><strong>{$lang->ougc_awards_page_list_award}</strong></td>
		<td class="tcat smalltext"><strong>{$lang->ougc_awards_modcp_reason}</strong></td>
		<td class="tcat smalltext" align="center" width="20%"><strong>{$lang->ougc_awards_page_view_date}</strong></td>
	</tr>
	{$awards_list}
</table>',
		'page_user_award'	=> '<tr>
	<td class="{$trow}" align="center"><a href="{$mybb->settings[\'bburl\']}/awards.php?view={$award[\'aid\']}" title="{$award[\'name\']}"><img src="{$award[\'image\']}" alt="{$award[\'name\']}" /></a></td>
	<td class="{$trow}">{$award[\'reason\']}</td>
	<td class="{$trow}" align="center">{$award[\'date\']}</td>
</tr>',
		'page_user_empty'	=> '<tr>
	<td class="trow1" colspan="3" align="center">{$lang->ougc_awards_page_list_empty}</td>
</tr>',
		'page_view'	=> '<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
	<tr>
		<td class="thead" colspan="4">
			<strong>{$award[\'name\']}</strong>
		</td>
	</tr>
	<tr>
		<td class="tcat smalltext" width="15%"><strong>{$lang->ougc_awards_modcp_username}</strong></td>
		<td class="tcat smalltext"><strong>{$lang->ougc_awards_modcp_reason}</strong></td>
		<td class="tcat smalltext" align="center" width="20%"><strong>{$lang->ougc_awards_page_view_date}</strong></td>
	</tr>
	{$users_list}
</table>',
		'page_view_empty'	=> '<tr>
	<td class="trow1" colspan="3" align="center">{$lang->ougc_awards_page_view_empty}</td>
</tr>',
		'page_view_row'	=> '<tr>
	<td class="{$trow}">{$gived[\'username\']}</td>
	<td class="{$trow}">{$gived[\'reason\']}</td>
	<td class="{$trow}" align="center">{$gived[\'date\']}</td>
</tr>',
	));

	// Modify templates
	require_once MYBB_ROOT.'/inc/adminfunctions_templates.php';
	find_replace_templatesets('postbit', '#'.preg_quote('{$post[\'user_details\']}').'#', '{$post[\'user_details\']}{$post[\'ougc_awards\']}');
	find_replace_templatesets('postbit_classic', '#'.preg_quote('{$post[\'user_details\']}').'#', '{$post[\'user_details\']}{$post[\'ougc_awards\']}');
	find_replace_templatesets('member_profile', '#'.preg_quote('{$signature}').'#', '{$signature}{$memprofile[\'ougc_awards\']}');
	find_replace_templatesets('modcp_nav', '#'.preg_quote('mcp_nav_modlogs}</a></td></tr>').'#', 'mcp_nav_modlogs}</a></td></tr><!--OUGC_AWARDS-->');

	// Update administrator permissions
	change_admin_permission('tools', 'ougc_awards');

	// Insert/update version into cache
	$plugins = $cache->read('ougc_plugins');
	if(!$plugins)
	{
		$plugins = array();
	}

	$info = ougc_awards_info();

	if(!isset($plugins['awards']))
	{
		$plugins['awards'] = $info['versioncode'];
	}

	/*~*~* RUN UPDATES START *~*~*/
	if($plugins['awards'] <= 1100)
	{
		global $db, $cache;

		// Modify table colunms
		if($db->field_exists('users', 'ougc_awards'))
		{
			$db->drop_column('ougc_awards', 'users');
		}
		$db->modify_column('ougc_awards', 'pm', 'text NOT NULL');
		$db->modify_column('ougc_awards_users', 'reason', 'text NOT NULL');

		if(!$db->index_exists('ougc_awards_users', 'uidaid'))
		{
			$db->write_query('ALTER TABLE '.TABLE_PREFIX.'ougc_awards_users ADD UNIQUE KEY uidaid (uid,aid)');
		}
		if(!$db->index_exists('ougc_awards_users', 'aiduid'))
		{
			$db->write_query('CREATE INDEX aiduid ON '.TABLE_PREFIX.'ougc_awards_users (aid,uid)');
		}

		$db->modify_column('ougc_awards', 'aid', 'int UNSIGNED NOT NULL AUTO_INCREMENT');
		$db->modify_column('ougc_awards_users', 'gid', 'int UNSIGNED NOT NULL AUTO_INCREMENT');
		$db->modify_column('ougc_awards_users', 'uid', "int NOT NULL DEFAULT '0'");
		$db->modify_column('ougc_awards_users', 'aid', "int NOT NULL DEFAULT '0'");

		// Delete old template group
		$db->delete_query('templategroups', 'prefix=\'ougc_awards\'');

		// Delete the cache.
		$db->delete_query('datacache', 'title=\'ougc_awards\'');
		if(is_object($cache->handler))
		{
			$cache->handler->delete('ougc_awards');
		}
	}

	if($plugins['awards'] <= 1000)
	{
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
	}
	/*~*~* RUN UPDATES END *~*~*/

	$plugins['awards'] = $info['versioncode'];
	$cache->update('ougc_plugins', $plugins);
}

// _deactivate() routine
function ougc_awards_deactivate()
{
	ougc_awards_pl_check();

	// Revert template edits
	require_once MYBB_ROOT.'/inc/adminfunctions_templates.php';
	find_replace_templatesets('postbit', '#'.preg_quote('{$post[\'ougc_awards\']}').'#', '', 0);
	find_replace_templatesets('postbit_classic', '#'.preg_quote('{$post[\'ougc_awards\']}').'#', '', 0);
	find_replace_templatesets('member_profile', '#'.preg_quote('{$memprofile[\'ougc_awards\']}').'#', '', 0);
	find_replace_templatesets('modcp_nav', '#'.preg_quote('<!--OUGC_AWARDS-->').'#', '', 0);

	// Update administrator permissions
	change_admin_permission('tools', 'ougc_awards', 0);
}

// _install() routine
function ougc_awards_install()
{
	global $db;

	// Create our table(s)
	$collation = $db->build_create_table_collation();
	$db->write_query("CREATE TABLE `".TABLE_PREFIX."ougc_awards` (
			`aid` int UNSIGNED NOT NULL AUTO_INCREMENT,
			`name` varchar(100) NOT NULL DEFAULT '',
			`description` varchar(255) NOT NULL DEFAULT '',
			`image` varchar(255) NOT NULL DEFAULT '',
			`visible` smallint(1) NOT NULL DEFAULT '1',
			`pm` text NOT NULL,
			`type` smallint(1) NOT NULL DEFAULT '0',
			PRIMARY KEY (`aid`)
		) ENGINE=MyISAM{$collation};"
	);
	$db->write_query("CREATE TABLE `".TABLE_PREFIX."ougc_awards_users` (
			`gid` int UNSIGNED NOT NULL AUTO_INCREMENT,
			`uid` int NOT NULL DEFAULT '0',
			`aid` int NOT NULL DEFAULT '0',
			`reason` text NOT NULL,
			`date` int(10) NOT NULL DEFAULT '0',
			PRIMARY KEY (`gid`),
			UNIQUE KEY uidaid (uid,aid),
			INDEX aiduid (aid,uid)
		) ENGINE=MyISAM{$collation};"
	);

	// Add DB entries
	if(!$db->field_exists('ougc_awards', 'users'))
	{
		$db->add_column('users', 'ougc_awards', 'text NOT NULL');
	}

	if($db->table_exists('alert_settings') && $db->table_exists('alert_setting_values'))
	{
		$query = $db->simple_select('alert_settings', 'id', 'code=\'ougc_awards\'');

		if(!($id = (int)$db->fetch_field($query, 'id')))
		{
			$id = (int)$db->insert_query('alert_settings', array('code' => 'ougc_awards'));
	
			// Only update the first time
			$db->delete_query('alert_setting_values', 'setting_id=\''.$id.'\'');

			$query = $db->simple_select('users', 'uid');
			while($uid = (int)$db->fetch_field($query, 'uid'))
			{
				$settings[] = array(
					'user_id'		=> $uid,
					'setting_id'	=> $id,
					'value'			=> 1
				);
			}

			if(!empty($settings))
			{
				$db->insert_query_multiple('alert_setting_values', $settings);
			}
		}
	}
}

// _is_installed() routine
function ougc_awards_is_installed()
{
	global $db;

	return $db->table_exists('ougc_awards');
}

// _uninstall() routine
function ougc_awards_uninstall()
{
	global $db, $PL, $cache;
	ougc_awards_pl_check();

	// Drop DB entries
	$db->drop_table('ougc_awards');
	$db->drop_table('ougc_awards_users');
	if($db->field_exists('ougc_awards', 'users'))
	{
		$db->drop_column('users', 'ougc_awards');
	}

	$PL->settings_delete('ougc_awards');
	$PL->templates_delete('ougc_awards');

	// Delete version from cache
	$plugins = (array)$cache->read('ougc_plugins');

	if(isset($plugins['awards']))
	{
		unset($plugins['awards']);
	}

	if(!empty($plugins))
	{
		$cache->update('ougc_plugins', $plugins);
	}
	else
	{
		$PL->cache_delete('ougc_plugins');
	}

	// Remove administrator permissions
	change_admin_permission('tools', 'ougc_awards', -1);
}

// PluginLibrary dependency check & load
function ougc_awards_pl_check()
{
	global $lang, $awards;
	$awards->lang_load();
	$info = ougc_awards_info();

	if(!file_exists(PLUGINLIBRARY))
	{
		flash_message($lang->sprintf($lang->ougc_awards_pl_required, $info['pl']['url'], $info['pl']['version']), 'error');
		admin_redirect('index.php?module=config-plugins');
		exit;
	}

	global $PL;

	$PL or require_once PLUGINLIBRARY;

	if($PL->version < $info['pl']['version'])
	{
		flash_message($lang->sprintf($lang->ougc_awards_pl_old, $info['pl']['url'], $info['pl']['version'], $PL->version), 'error');
		admin_redirect('index.php?module=config-plugins');
		exit;
	}
}









































// ModCP Part
function ougc_awards_modcp()
{
	global $mybb, $modcp_nav, $templates, $lang, $ougc_awards;

	$permission = (bool)($mybb->settings['ougc_awards_modcp'] && ($mybb->usergroup['cancp'] || $this->check_groups($mybb->settings['ougc_awards_moderators'], false)));

	if($permission)
	{
		$ougc_awards->lang_load();

		eval('$awards_nav = "'.$templates->get('ougcawards_modcp_nav').'";');
		$modcp_nav = str_replace('<!--OUGC_AWARDS-->', $awards_nav, $modcp_nav);
	}

	if($mybb->input['action'] != 'awards')
	{
		return;
	}

	$permission or error_no_permission();

	$ougc_awards->lang_load();

	global $headerinclude, $header, $theme, $footer, $db;

	add_breadcrumb($lang->ougc_awards_modcp_nav, $ougc_awards->build_url());
	$error = array();
	$errors = '';

	// We can give awards from the ModCP
	if($mybb->input['manage'] == 'give')
	{
		if(!($award = $ougc_awards->get_award($mybb->input['aid'])))
		{
			error($lang->ougc_awards_error_wrongaward);
		}

		add_breadcrumb(strip_tags($award['name']));
		add_breadcrumb($lang->ougc_awards_modcp_give);

		if((bool)$mybb->settings['ougc_awards_hidemcp'] && !(int)$award['visible'])
		{
			$award['visible'] = 1;
		}

		if(!$award['visible'])
		{
			error($lang->ougc_awards_error_wrongaward);
		}

		if($mybb->request_method == 'post')
		{
			if(!($user = $ougc_awards->get_user_by_username($mybb->input['username'])))
			{
				$errors = inline_error($lang->ougc_awards_error_invaliduser);
			}
			elseif($ougc_awards->get_gived_award($award['aid'], $user['uid']))
			{
				$errors = inline_error($lang->ougc_awards_error_give);
			}
			elseif(!$ougc_awards->can_edit_user($user['uid']))
			{
				$errors = inline_error($lang->ougc_awards_error_giveperm);
			}
			else
			{
				$ougc_awards->give_award($award, $user, $mybb->input['reason']);
				$ougc_awards->log_action();
				$ougc_awards->redirect($lang->ougc_awards_redirect_gived);
			}
		}

		$lang->ougc_awards_modcp_title_give = $lang->sprintf($lang->ougc_awards_modcp_title_give, $ougc_awards->get_award_info('name', $award['aid'], $award['name']));

		eval('$reason = "'.$templates->get('ougcawards_modcp_manage_reason').'";');
		eval('$content = "'.$templates->get('ougcawards_modcp_manage').'";');
		eval('$page = "'.$templates->get('ougcawards_modcp').'";');
		output_page($page);
		exit;
	}
	// We can revoke awards from the ModCP
	elseif($mybb->input['manage'] == 'revoke')
	{
		if(!($award = $ougc_awards->get_award($mybb->input['aid'])))
		{
			error($lang->ougc_awards_error_wrongaward);
		}

		add_breadcrumb(strip_tags($award['name']));
		add_breadcrumb($lang->ougc_awards_modcp_revoke);

		if((bool)$mybb->settings['ougc_awards_hidemcp'] && !(int)$award['visible'])
		{
			$award['visible'] = 1;
		}

		if(!$award['visible'])
		{
			error($lang->ougc_awards_error_wrongaward);
		}

		if($mybb->request_method == 'post')
		{
			if(!($user = $ougc_awards->get_user_by_username($mybb->input['username'])))
			{
				$errors = inline_error($lang->ougc_awards_error_invaliduser);
			}
			elseif(!$ougc_awards->get_gived_award($award['aid'], $user['uid']))
			{
				$errors = inline_error($lang->ougc_awards_error_notgive);
			}
			elseif(!$ougc_awards->can_edit_user($user['uid']))
			{
				$errors = inline_error($lang->ougc_awards_error_giveperm);
			}
			else
			{
				$ougc_awards->revoke_award($award['aid'], $user['uid']);
				$ougc_awards->log_action();
				$ougc_awards->redirect($lang->ougc_awards_redirect_revoked);
			}
		}

		$lang->ougc_awards_modcp_title_give = $lang->sprintf($lang->ougc_awards_modcp_title_give, $ougc_awards->get_award_info('name', $award['aid'], $award['name']));

		$lang->ougc_awards_modcp_give = $lang->ougc_awards_modcp_revoke;
		eval('$content = "'.$templates->get('ougcawards_modcp_manage').'";');
		eval('$page = "'.$templates->get('ougcawards_modcp').'";');
		output_page($page);
		exit;
	}
	else
	{
		$limit = 20;
		$mybb->input['page'] = (int)$mybb->input['page'];
		if($mybb->input['page'] && $mybb->input['page'] > 0)
		{
			$start = ($mybb->input['page'] - 1)*$limit;
		}
		else
		{
			$start = 0;
			$mybb->input['page'] = 1;
		}

		$where = '';
		if(!(bool)$mybb->usergroup['cancp'] && !(bool)$mybb->settings['ougc_awards_hidemcp'])
		{
			$where = 'visible=\'1\'';
		}

		$awards = $multipage = '';
		$query = $db->simple_select('ougc_awards', '*', $where, array('limit_start' => $start, 'limit' => $limit));
		if(!$db->num_rows($query))
		{
			eval('$awards = "'.$templates->get('ougcawards_modcp_list_empty').'";');
		}
		else
		{
			while($award = $db->fetch_array($query))
			{
				$trow = alt_trow();

				$award['aid'] = (int)$award['aid'];
				$award['image'] = $ougc_awards->get_award_icon($award['aid']);
				if($name = $ougc_awards->get_award_info('name', $award['aid']))
				{
					$award['name'] = $name;
				}
				if($description = $ougc_awards->get_award_info('description', $award['aid']))
				{
					$award['description'] = $description;
				}

				eval('$awards .= "'.$templates->get('ougcawards_modcp_list_award').'";');
			}

			$query = $db->simple_select('ougc_awards', 'COUNT(aid) AS awards', $where);
			$awardscount = (int)$db->fetch_field($query, 'awards');

			
			$multipage = multipage($awardscount, $limit, $mybb->input['page'], $ougc_awards->build_url());
			isset($multipage) or $multipage = '';
		}

		eval('$content = "'.$templates->get('ougcawards_modcp_list').'".$multipage;');
		eval('$page = "'.$templates->get('ougcawards_modcp').'";');
		output_page($page);
		exit;
	}
}

// Show awards in profile function.
function ougc_awards_profile()
{
	global $mybb, $memprofile, $ougc_awards, $templates;

	$memprofile['ougc_awards'] = '';
	$max_profile = (int)$mybb->settings['ougc_awards_profile'];
	if(($max_profile > 0 || $max_profile == -1) && my_strpos($templates->cache['member_profile'], '{$memprofile[\'ougc_awards\']}'))
	{
		global $db, $lang, $theme;

		$limit = '';
		if($max_profile != -1)
		{
			$limit = ' LIMIT '.$max_profile;
		}

		$memprofile['uid'] = (int)$memprofile['uid'];
		// Query our data.
		if(!(bool)$mybb->settings['ougc_awards_multipage'])
		{
			// Get awards
			$query = $db->query('
				SELECT u.*, a.*
				FROM '.TABLE_PREFIX.'ougc_awards_users u
				LEFT JOIN '.TABLE_PREFIX.'ougc_awards a ON (u.aid=a.aid)
				WHERE u.uid=\''.$memprofile['uid'].'\' AND a.visible=\'1\'
				ORDER BY u.date desc'.$limit
			);
		}
		else
		{
			// First we need to figure out the total amount of awards.
			$query = $db->query('
				SELECT COUNT(u.aid) AS total_awards
				FROM '.TABLE_PREFIX.'ougc_awards_users u
				LEFT JOIN '.TABLE_PREFIX.'ougc_awards a ON (u.aid=a.aid)
				WHERE u.uid=\''.$memprofile['uid'].'\' AND a.visible=\'1\'
				ORDER BY u.date desc
			');
			$awardscount = (int)$db->fetch_field($query, 'total_awards');

			// Now we get the awards.
			$multipage = '';
			if((bool)$mybb->settings['ougc_awards_multipage'])
			{
				if($max_profile == -1)
				{
					$max_profile = 10;
				}
				$page = (int)$mybb->input['page'];
				if($page > 0)
				{
					$limit_start = ($page-1)*$max_profile;
					$pages = ceil($awardscount/$max_profile);
					if($page > $pages)
					{
						$limit_start = 0;
						$page = 1;
					}
				}
				else
				{
					$page = 1;
					$limit_start = 0;
				}
				$limit = ' LIMIT '.$limit_start.', '.$max_profile;
				$link = get_profile_link($memprofile['uid']);
				$multipage = multipage($awardscount, $max_profile, $page, $link.(!my_strpos($link, '?') ? '?' : '&amp;').'awards');
			}
			$query = $db->query('
				SELECT u.*, a.*
				FROM '.TABLE_PREFIX.'ougc_awards_users u
				LEFT JOIN '.TABLE_PREFIX.'ougc_awards a ON (u.aid=a.aid)
				WHERE u.uid=\''.$memprofile['uid'].'\' AND a.visible=\'1\'
				ORDER BY u.date desc'.$limit
			);
		}

		// Output ouw awards.
		$awards = '';
		while($award = $db->fetch_array($query))
		{
			$trow = alt_trow();

			if($name = $ougc_awards->get_award_info('name', $award['aid']))
			{
				$award['name'] = $name;
			}
			if($description = $ougc_awards->get_award_info('description', $award['aid']))
			{
				$award['description'] = $description;
			}
			if($reason = $ougc_awards->get_award_info('reason', $award['aid'], $award['gid']))
			{
				$award['reason'] = $reason;
			}

			if(empty($award['reason']))
			{
				$award['reason'] = $lang->ougc_awards_pm_noreason;
			}

			$ougc_awards->parse_text($award['reason']);

			$award['image'] = $ougc_awards->get_award_icon($award['aid']);

			$award['date'] = $lang->sprintf($lang->ougc_awards_profile_tine, my_date($mybb->settings['dateformat'], $award['date']), my_date($mybb->settings['timeformat'], $award['date']));

			eval('$awards .= "'.$templates->get('ougcawards_profile_row').'";');
		}

		// User has no awards.
		if(!$awards)
		{
			eval('$awards = "'.$templates->get('ougcawards_profile_row_empty').'";');
		}

		$lang->ougc_awards_profile_title = $lang->sprintf($lang->ougc_awards_profile_title, htmlspecialchars_uni($memprofile['username']));

		eval('$memprofile[\'ougc_awards\'] = "'.$templates->get('ougcawards_profile').'";');
	}
}

// Show awards in profile function.
function ougc_awards_postbit(&$post)
{
	global $settings, $plugins, $mybb;

	$post['ougc_awards'] = '';
	$max_postbit = (int)$settings['ougc_awards_postbit'];

	if($max_postbit < 1 && $max_postbit != -1)
	{
		return;
	}

	static $ougc_awards_cache = null;

	// First we need to get our data
	if(THIS_SCRIPT == 'showthread.php' && isset($GLOBALS['pids']) && !isset($ougc_awards_cache))
	{
		global $db;
		$ougc_awards_cache = array();

		$pids = array_filter(array_unique(array_map('intval', explode('\'', $GLOBALS['pids']))));
		$query = $db->query('
			SELECT a.aid, a.name, a.image, p.uid
			FROM '.TABLE_PREFIX.'ougc_awards a
			JOIN '.TABLE_PREFIX.'ougc_awards_users ag ON (ag.aid=a.aid)
			JOIN '.TABLE_PREFIX.'posts p ON (p.uid=ag.uid)
			WHERE p.pid IN (\''.implode('\',\'', $pids).'\') AND a.visible=\'1\' AND a.type!=\'1\'
			ORDER BY ag.date desc'
		);
		// how to limit by uid here?
		// -- '.($max_postbit == -1 ? '' : 'LIMIT '.$max_postbit)

		while($data = $db->fetch_array($query))
		{
			$ougc_awards_cache[$data['uid']][$data['aid']] = $data;
		}
	}
	elseif(!isset($ougc_awards_cache))
	{
		global $db;
		$ougc_awards_cache = array();

		$query = $db->query('
			SELECT a.aid, a.name, a.image, ag.uid
			FROM '.TABLE_PREFIX.'ougc_awards a
			JOIN '.TABLE_PREFIX.'ougc_awards_users ag ON (ag.aid=a.aid)
			WHERE ag.uid=\''.(int)($plugins->current_hook == 'postbit_prev' ? (isset($post['uid']) ? $post['uid'] : $mybb->user['uid']): $post['uid']).'\' AND a.visible=\'1\' AND a.type!=\'1\'
			ORDER BY ag.date desc
			'.($max_postbit == -1 ? '' : 'LIMIT '.$max_postbit)
		);
	
		while($data = $db->fetch_array($query))
		{
			$ougc_awards_cache[$data['uid']][$data['aid']] = $data;
		}
	}

	// User has no awards
	if(empty($ougc_awards_cache[$post['uid']]))
	{
		return;
	}
	$awards = $ougc_awards_cache[$post['uid']];

	global $templates, $ougc_awards;

	$count = 0;

	// Format the awards
	foreach($awards as $award)
	{
		$award['aid'] = (int)$award['aid'];
		if($name = $ougc_awards->get_award_info('name', $award['aid']))
		{
			$award['name'] = $name;
		}
		$award['name_ori'] = $award['name'];
		$award['name'] = strip_tags($award['name_ori']);

		$award['image'] = $ougc_awards->get_award_icon($award['aid']);

		if($max_postbit == -1 || $count < $max_postbit)
		{
			$count++;
			$br = '';
			if($count == 1)
			{
				$br = '<br />'; // We insert a break if it is the first award.
			}

			eval('$new_award = "'.$templates->get('ougcawards_postbit', 1, 0).'";');
			$post['ougc_awards'] .= trim($new_award);
		}
	}

	$post['user_details'] = str_replace('<!--OUGC_AWARDS-->', $post['ougc_awards'], $post['user_details']);
}

class OUGC_Awards
{
	// Define our url
	protected $url = '';

	// Cache
	private $cache = array('awards' => array(), 'images' => array());

	// AID which has just been updated/inserted/deleted
	public $aid = 0;

	// UID which has just been updated/inserted/deleted
	public $uid = 0;

	// Parser options
	public $parser_options = array(
		'allow_html'		=> 0,
		'allow_smilies'		=> 1,
		'allow_mycode'		=> 1,
		'filter_badwords'	=> 1,
		'shorten_urls'		=> 1
	);

	// Award data
	public $award_data = array();

	// Construct the data (?)
	function __construct()
	{
		global $mybb;

		$plugins = $mybb->cache->read('plugins');

		// Is plugin active?
		$this->is_active = isset($plugins['active']['ougc_awards']);

		if(defined('IN_ADMINCP'))
		{
			$this->set_url(null, 'index.php?module=user-ougc_awards');
		}
		else
		{
			$this->set_url(null, 'modcp.php?action=awards');
		}
	}

	// Load lang files from our plugin directory, not from mybb default.
	function lang_load($datahandler=false, $force=false)
	{
		global $lang;

		// Check if already loaded
		if(!$force && isset($lang->ougc_awards))
		{
			return;
		}

		$language_bu = $lang->language;
		$lang->load((defined('IN_ADMINCP') ? 'user_' : '').'ougc_awards', $datahandler);
		$lang->load('ougc_awards_extra_vals', true, true);
		$lang->language = $language_bu;
	}

	// Modify acp url
	function set_url($params, $url=false)
	{
		if($url !== false)
		{
			$this->url = $url;
		}

		if(is_array($params) && !empty($params))
		{
			global $PL;
			$PL or require_once PLUGINLIBRARY;

			$this->url = $PL->url_append($this->url, $params);
		}
	}

	// Build an url parameter
	function build_url($params=array())
	{
		global $PL;
		$PL or require_once PLUGINLIBRARY;

		if(!is_array($params))
		{
			$params = explode('=', $params);
			if(isset($params[0]) && isset($params[1]))
			{
				$params = array($params[0] => $params[1]);
			}
			else
			{
				$params = array();
			}
		}

		return $PL->url_append($this->url, $params);
	}

	// Get the rate icon
	function get_award_icon($aid)
	{
		if(!isset($this->cache['images'][$aid]))
		{
			global $settings;
			$award = $this->get_award($aid);

			// The image is suppose to be external.
			if(my_strpos($award['image'], 'ttp:/') || my_strpos($award['image'], 'ttps:/')) 
			{
				$this->cache['images'][$aid] = $award['image'];
			}
			// The image is suppose to be internal inside our images folder.
			elseif(!my_strpos($award['image'], '/') && !empty($award['image']) && file_exists(MYBB_ROOT.'/images/ougc_awards/'.$award['image'])) 
			{
				$this->cache['images'][$aid] = $settings['bburl'].'/images/ougc_awards/'.htmlspecialchars_uni($award['image']);
			}
			// Image is suppose to be internal.
			elseif(!empty($award['image']) && file_exists(MYBB_ROOT.'/'.$award['image']))
			{
				$this->cache['images'][$aid] = $settings['bburl'].'/'.htmlspecialchars_uni($award['image']);
			}
			// Default image.
			else
			{
				$this->cache['images'][$aid] = $settings['bburl'].'/images/ougc_awards/default.png';
			}
		}

		return $this->cache['images'][$aid];
	}

	// Set data award
	function set_award_data($aid=false)
	{
		if($aid !== false && (int)$aid > 0 )
		{
			$award = $this->get_award($aid);

			$this->award_data = array(
				'name'			=> $award['name'],
				'description'	=> $award['description'],
				'image'			=> $award['image'],
				'visible'		=> (int)$award['visible'],
				'pm'			=> $award['pm'],
				'type'			=> (int)$award['type'],
			);
		}
		else
		{
			$this->award_data = array(
				'name' 			=> '',
				'description' 	=> '',
				'image' 		=> '',
				'visible' 		=> 1,
				'pm' 			=> '',
				'type'		 	=> 0,
			);
		}

		global $mybb;

		if($mybb->request_method == 'post')
		{
			foreach((array)$mybb->input as $key => $value)
			{
				if(isset($this->award_data[$key]))
				{
					$this->award_data[$key] = $value;
				}
			}
		}
	}

	// Get a award from the DB
	function get_award($aid)
	{
		if(!isset($this->cache['awards'][$aid]))
		{
			global $db;
			$this->cache['awards'][$aid] = false;

			$query = $db->simple_select('ougc_awards', '*', 'aid=\''.(int)$aid.'\'');
			$award = $db->fetch_array($query);
			if(isset($award['aid']))
			{
				$this->cache['awards'][$aid] = $award;
			}
		}

		return $this->cache['awards'][$aid];
	}

	// Validate award data
	function validate_award()
	{
		global $mybb;

		$valid = true;

		if(!$this->award_data['name'] || isset($foo{100}))
		{
			$this->validation_errors[] = 'Invalid name';
			$valid = false;
		}

		if(isset($this->award_data['description']{255}))
		{
			$this->validation_errors[] = 'Invalid description';
			$valid = false;
		}

		if(isset($this->award_data['image']{255}))
		{
			$this->validation_errors[] = 'Invalid image';
			$valid = false;
		}

		return $valid;
	}

	// Insert a new rate to the DB
	function insert_award($data, $aid=null, $update=false)
	{
		global $db;

		$clean_data = array();
		if(isset($data['name']))
		{
			$clean_data['name'] = $db->escape_string($data['name']);
		}
		if(isset($data['description']))
		{
			$clean_data['description'] = $db->escape_string($data['description']);
		}
		if(isset($data['image']))
		{
			$clean_data['image'] = $db->escape_string($data['image']);
		}
		if(isset($data['pm']))
		{
			$clean_data['pm'] = $db->escape_string($data['pm']);
		}
		if(isset($data['visible']))
		{
			$clean_data['visible'] = (int)$data['visible'];
		}
		if(isset($data['type']))
		{
			$clean_data['type'] = (int)$data['type'];
		}

		if($update && $clean_data)
		{
			$this->aid = (int)$aid;
			$db->update_query('ougc_awards', $clean_data, 'aid=\''.$this->aid.'\'');
		}
		elseif($clean_data)
		{
			$this->aid = (int)$db->insert_query('ougc_awards', $clean_data);
		}
	}

	// Update espesific rate
	function update_award($data, $aid)
	{
		$this->insert_award($data, $aid, true);
	}

	// Redirect admin help function
	function admin_redirect($message='', $error=false)
	{
		if($message)
		{
			flash_message($message, ($error ? 'error' : 'success'));
		}

		admin_redirect($this->build_url());
		exit;
	}

	// Redirect admin help function
	function redirect($message='', $title='')
	{
		redirect($this->build_url(), $message, $title);
		exit;
	}

	// Log admin action
	function log_action()
	{
		$func = 'log_moderator_action';
		$data = array('fid' => '', 'tid' => '');
		if(defined('IN_ADMINCP'))
		{
			$func = 'log_admin_action';
			$data = array();
		}

		if($this->aid)
		{
			$data['aid'] = $this->aid;
		}
		if($this->uid)
		{
			$data['uid'] = $this->uid;
		}
		if($this->gid)
		{
			$data['gid'] = $this->gid;
		}

		$func($data);
	}
	// Get user by username
	function get_user_by_username($username)
	{
		global $db;

		$query = $db->simple_select('users', 'uid, username', 'LOWER(username)=\''.$db->escape_string(my_strtolower($username)).'\'', array('limit' => 1));

		if($user = $db->fetch_array($query))
		{
			return array('uid' => (int)$user['uid'], 'username' => $user['username']);
		}

		// Lets assume that admin inserted a uid..
		$query = $db->simple_select('users', 'uid, username', 'uid=\''.(int)$username.'\'', array('limit' => 1));

		if($user = $db->fetch_array($query))
		{
			return array('uid' => (int)$user['uid'], 'username' => $user['username']);
		}

		return false;
	}

	// Check if this user already has an award.
	function get_gived_award($aid, $uid)
	{
		global $db;

		$query = $db->simple_select('ougc_awards_users', '*', 'uid=\''.(int)$uid.'\' AND aid=\''.(int)$aid.'\'');

		if($gived = $db->fetch_array($query))
		{
			return $gived;
		}

		return false;
	}

	// Give an award.
	function give_award($award, $user, $reason)
	{
		global $db, $plugins;

		$args = array(
			'award'		=> &$award,
			'user'		=> &$user,
			'reason'	=> &$reason
		);

		$plugins->run_hooks('ougc_awards_give_award', $args);

		$this->aid = $award['aid'];
		$this->uid = $award['uid'];

		// Insert our gived award.
		$insert_data = array(
			'aid'		=> (int)$award['aid'],
			'uid'		=> (int)$user['uid'],
			'reason'	=> $db->escape_string(trim($reason)),
			'date'		=> TIME_NOW
		);

		$db->insert_query('ougc_awards_users', $insert_data);

		if($message = $this->get_award_info('pm', $award['aid']))
		{
			$award['pm'] = $message;
		}
		if($name = $this->get_award_info('name', $award['aid']))
		{
			$award['name'] = $name;
		}

		global $lang;
		$this->lang_load();

		$this->send_pm(array(
			'subject'		=> $lang->sprintf($lang->ougc_awards_pm_title, strip_tags($award['name'])),
			'message'		=> $lang->sprintf($award['pm'], $user['username'], $award['name'], (!empty($reason) ? $reason : $lang->ougc_awards_pm_noreason), $this->get_award_icon($award['aid']), $mybb->settings['bbname']),
			'touid'			=> $this->approval['uid']
		), -1, true);
	}

	// I liked as I did the pm thing, so what about award name, description, and reasons?
	function get_award_info($type, $aid, $gid=0)
	{
		global $lang;
		$this->lang_load();
		$this->lang_load(true, true);
		$aid = (int)$aid;

		if($type == 'pm')
		{
			if(!empty($lang->ougc_awards_award_pm_all))
			{
				return $lang->ougc_awards_award_pm_all;
			}
		}

		if($type == 'reason')
		{
			$lang_val = 'ougc_awards_award_'.$type.'_gived_'.(int)$gid;
			if(!empty($lang->$lang_val))
			{
				return $lang->$lang_val;
			}
			$lang_val = 'ougc_awards_award_'.$type.'_'.(int)$aid;
			if(!empty($lang->$lang_val))
			{
				return $lang->$lang_val;
			}
		}
		else
		{
			$lang_val = 'ougc_awards_award_'.$type.'_'.$aid;

			if(!empty($lang->$lang_val))
			{
				return $lang->$lang_val;
			}
		}

		return false;
	}

	// Revoke an award.
	function revoke_award($aid, $uid)
	{
		global $db, $plugins;
		$this->aid = (int)$aid;
		$this->uid = (int)$uid;

		$args = array(
			'aid'		=> &$this->aid,
			'uid'		=> &$this->uid
		);

		$plugins->run_hooks('ougc_awards_revoke_award', $args);

		// If user has two of the same award, it will delete it now too (this plugin doesn't support multiple of the same award anyways).
		$db->delete_query('ougc_awards_users', 'aid=\''.$this->aid.'\' AND uid=\''.$this->uid.'\'');
	}

	// Completely removes an award data from the DB
	function delete_award($aid)
	{
		global $db;
		$this->aid = (int)$aid;

		$query = $db->simple_select('ougc_awards_users', 'uid', 'aid=\''.$this->aid.'\'');
		while($uid = $db->fetch_field($query, 'uid'))
		{
			$this->revoke_award($this->aid, $uid);
		}

		$db->delete_query('ougc_awards', 'aid=\''.$this->aid.'\'');
	}

	// Update a awarded user data
	function update_gived($gid, $data)
	{
		global $db, $plugins;
		$this->gid = (int)$gid;

		if($this->gid < 1 || !is_array($data))
		{
			return;
		}

		$clean_data = array();
		if(isset($data['date']))
		{
			$clean_data['date'] = (int)$data['date'];
		}
		if(isset($data['reason']))
		{
			$clean_data['reason'] = $db->escape_string($data['reason']);
		}

		$args = array(
			'gid'			=> &$this->gid,
			'data'			=> &$data,
			'clean_data'	=> &$clean_data,
		);

		$plugins->run_hooks('ougc_awards_update_gived', $args);

		if($clean_data)
		{

			$db->update_query('ougc_awards_users', $clean_data, 'gid=\''.$this->gid.'\'');
		}
	}

	// Parse data with the mybb parser (for reasons).
	function parse_text(&$message)
	{
		global $parser;
		if(!is_object($parser))
		{
			require_once MYBB_ROOT.'inc/class_parser.php';
			$parser = new postParser;
		}

		return $parser->parse_message(htmlspecialchars_uni($message), $this->parser_options);
	}

	// This will check current user's groups.
	function check_groups($groups, $empty=true)
	{
		if(empty($groups) && $empty)
		{
			return true;
		}

		global $PL;
		$PL or require_once PLUGINLIBRARY;

		return (bool)$PL->is_member($groups);
	}

	// Check if user can "moderate" another user, meant to limit what users can do from the ModCP page
	function can_edit_user($uid)
	{
		global $mybb;

		if(true)
		{
			$uid = (int)$uid;

			if(is_super_admin($mybb->user['uid']))
			{
				return true;
			}

			if(is_super_admin($uid))
			{
				return false;
			}

			$user_perms = user_permissions($uid);
			if($user_perms['cancp'] && !$mybb->usergroup['cancp'])
			{
				return false;
			}

			if(!defined('IN_ADMINCP'))
			{
				if($uid == $mybb->user['uid'])
				{
					return false;
				}

				if(!is_moderator(false, null, $uid))
				{
					return true;
				}

				if($user_perms['issupermod'] && !$mybb->usergroup['issupermod'])
				{
					return false;
				}
				
			}
		}

		return true;
	}

	// Send a Private Message to a user  (Copied from MyBB 1.7)
	function send_pm($pm, $fromid=0, $admin_override=false, $tids)
	{
		global $mybb;

		if(!$mybb->settings['ougc_awards_sendpm'] || !$mybb->settings['enablepms'] || !is_array($pm))
		{
			return false;
		}

		if (!$pm['subject'] ||!$pm['message'] || (!$pm['receivepms'] && !$admin_override))
		{
			return false;
		}

		global $lang, $db, $session;
		$lang->load('messages');

		require_once MYBB_ROOT."inc/datahandlers/pm.php";

		$pmhandler = new PMDataHandler();

		// Build our final PM array
		$pm = array(
			'subject'		=> $pm['subject'],
			'message'		=> $pm['message'],
			'icon'			=> -1,
			'fromid'		=> ($fromid == 0 ? (int)$mybb->user['uid'] : ($fromid < 0 ? 0 : $fromid)),
			'toid'			=> array($pm['touid']),
			'bccid'			=> array(),
			'do'			=> '',
			'pmid'			=> '',
			'saveasdraft'	=> 0,
			'options'	=> array(
				'signature'			=> 0,
				'disablesmilies'	=> 0,
				'savecopy'			=> 0,
				'readreceipt'		=> 0
			)
		);

		if(isset($mybb->session))
		{
			$pm['ipaddress'] = $mybb->session->packedip;
		}

		// Admin override
		$pmhandler->admin_override = (int)$admin_override;

		$pmhandler->set_data($pm);

		if($pmhandler->validate_pm())
		{
			$pmhandler->insert_pm();
			return true;
		}

		return false;
	}
}
$GLOBALS['ougc_awards'] = new OUGC_Awards;