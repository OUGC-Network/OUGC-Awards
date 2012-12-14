<?php

/***************************************************************************
 *
 *   OUGC Awards plugin (/inc/plugins/ougc_awards/plugin.php)
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

// Necessary plugin information for the ACP plugin manager.
function ougc_awards_plugin_info()
{
	global $lang;
	ougc_awards_lang_load();

	return array(
		'name'			=> "OUGC Awards",
		'description'	=> $lang->ougc_awards_plugin_d,
		'website'		=> 'http://mods.mybb.com/view/ougc-awards',
		'author'		=> 'Omar Gonzalez',
		'authorsite'	=> 'http://community.mybb.com/user-25096.html',
		'version'		=> '1.1',
		'compatibility'	=> '16*',
		'guid'			=> '8172205c3142e4295ed5ed3a7e8f40d6'
	);
}

// Activate the plugin.
function ougc_awards_plugin_activate()
{
	global $lang, $db;
	ougc_awards_lang_load();

	// Run deactivate function.
	ougc_awards_plugin_deactivate();

	// Insert new templates.
	ougc_awards_add_template('modcp_ougc_awards_manage', '<form action="modcp.php" method="post">
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
</script>', 1607);
	ougc_awards_add_template('modcp_ougc_awards_nav', '<tr><td class="trow1 smalltext"><a href="modcp.php?action=awards" class="modcp_nav_item" style="background: url(\'images/ougc_awards/icon.png\') no-repeat left center;">{$lang->ougc_awards_modcp_nav}</a></td></tr>', 1607);
	ougc_awards_add_template('modcp_ougc_awards', '<html>
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
</html>', 1607);
	ougc_awards_add_template('modcp_ougc_awards_list_empty', '<tr>
	<td class="trow1" colspan="4" align="center">
		{$lang->ougc_awards_modcp_list_empty}
	</td>
</tr>', 1607);
	ougc_awards_add_template('modcp_ougc_awards_list', '<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
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
<span class="smalltext">{$lang->ougc_awards_modcp_list_note}</span>', 1607);
	ougc_awards_add_template('modcp_ougc_awards_list_award', '<tr>
	<td class="trow1" align="center" width="1%"><a href="{$mybb->settings[\'bburl\']}/awards.php?view={$award[\'aid\']}" title="{$award[\'name\']}"><img src="{$award[\'image\']}" alt="{$award[\'name\']}" /></a></td>
	<td class="trow1" width="15%">{$award[\'name\']}</td>
	<td class="trow1">{$award[\'description\']}</td>
	<td class="trow1" align="center" width="15%">[<a href="{$mybb->settings[\'bburl\']}/modcp.php?action=awards&amp;manage=give&amp;aid={$award[\'aid\']}">{$lang->ougc_awards_modcp_give}</a>&nbsp;|&nbsp;<a href="{$mybb->settings[\'bburl\']}/modcp.php?action=awards&amp;manage=revoke&amp;aid={$award[\'aid\']}">{$lang->ougc_awards_modcp_revoke}</a>]</td>
</tr>', 1607);
	ougc_awards_add_template('modcp_ougc_awards_manage_reason', '<tr>
	<td class="trow2" width="25%"><strong>{$lang->ougc_awards_modcp_reason}:</strong></td>
	<td class="trow2" width="75%"><textarea type="text" class="textarea" name="reason" id="reason" rows="4" cols="40">{$mybb->input[\'reason\']}</textarea></td>
</tr>', 1607);
	ougc_awards_add_template('postbit_ougc_awards', '{$br}<a href="{$mybb->settings[\'bburl\']}/awards.php?view={$award[\'aid\']}" title="{$award[\'name\']}"><img src="{$award[\'image\']}" alt="{$award[\'name\']}" /></a>', 1607);
	ougc_awards_add_template('member_profile_ougc_awards', '<br />
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="2"><a style="float:right;" href="{$mybb->settings[\'bburl\']}/awards.php?user={$memprofile[\'uid\']}">{$lang->ougc_awards_profile_viewall}</a><strong>{$lang->ougc_awards_profile_title}</strong></td>
</tr>
{$awards}
</table>
{$multipage}', 1607);
	ougc_awards_add_template('member_profile_ougc_awards_row', '<tr>
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
</tr>', 1607);
	ougc_awards_add_template('member_profile_ougc_awards_row_empty', '<tr>
	<td class="trow1" colspan="2">
		{$lang->ougc_awards_profile_empty}
	</td>
</tr>', 1607);
	ougc_awards_add_template('ougc_awards_page', '<html>
	<head>
		<title>{$mybb->settings[\'bbname\']} - {$lang->ougc_awards_page_title}</title>
		{$headerinclude}
	</head>
	<body>
		{$header}
		{$content}
		{$footer}
	</body>
</html>', 1607);
	ougc_awards_add_template('ougc_awards_page_list', '<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
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
</table>', 1607);
	ougc_awards_add_template('ougc_awards_page_list_award', '<tr>
	<td class="{$trow}" align="center"><a href="{$mybb->settings[\'bburl\']}/awards.php?view={$award[\'aid\']}" title="{$award[\'name\']}"><img src="{$award[\'image\']}" alt="{$award[\'name\']}" /></a></td>
	<td class="{$trow}"><a href="{$mybb->settings[\'bburl\']}/awards.php?view={$award[\'aid\']}" title="{$award[\'name\']}">{$award[\'name\']}</a></td>
	<td class="{$trow}">{$award[\'description\']}</td>
</tr>', 1607);
	ougc_awards_add_template('ougc_awards_page_list_empty', '<tr>
	<td class="trow1" colspan="4" align="center">
		{$lang->ougc_awards_page_list_empty}
	</td>
</tr>', 1607);
	ougc_awards_add_template('ougc_awards_page_user', '<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
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
</table>', 1607);
	ougc_awards_add_template('ougc_awards_page_user_award', '<tr>
	<td class="{$trow}" align="center"><a href="{$mybb->settings[\'bburl\']}/awards.php?view={$award[\'aid\']}" title="{$award[\'name\']}"><img src="{$award[\'image\']}" alt="{$award[\'name\']}" /></a></td>
	<td class="{$trow}">{$award[\'reason\']}</td>
	<td class="{$trow}" align="center">{$award[\'date\']}</td>
</tr>', 1607);
	ougc_awards_add_template('ougc_awards_page_user_empty', '<tr>
	<td class="trow1" colspan="3" align="center">{$lang->ougc_awards_page_list_empty}</td>
</tr>', 1607);
	ougc_awards_add_template('ougc_awards_page_view', '<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
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
</table>', 1607);
	ougc_awards_add_template('ougc_awards_page_view_empty', '<tr>
	<td class="trow1" colspan="3" align="center">{$lang->ougc_awards_page_view_empty}</td>
</tr>', 1607);
	ougc_awards_add_template('ougc_awards_page_view_row', '<tr>
	<td class="{$trow}">{$gived[\'username\']}</td>
	<td class="{$trow}">{$gived[\'reason\']}</td>
	<td class="{$trow}" align="center">{$gived[\'date\']}</td>
</tr>', 1607);

	// Modify some templates.
	require_once MYBB_ROOT.'/inc/adminfunctions_templates.php';
	find_replace_templatesets('postbit', '#'.preg_quote('{$post[\'user_details\']}').'#', '{$post[\'user_details\']}{$post[\'ougc_awards\']}');
	find_replace_templatesets('postbit_classic', '#'.preg_quote('{$post[\'user_details\']}').'#', '{$post[\'user_details\']}{$post[\'ougc_awards\']}');
	find_replace_templatesets('member_profile', '#'.preg_quote('{$signature}').'#', '{$signature}{$memprofile[\'ougc_awards\']}');
	find_replace_templatesets('modcp_nav', '#'.preg_quote('mcp_nav_modlogs}</a></td></tr>').'#', 'mcp_nav_modlogs}</a></td></tr><!--OUGC_AWARDS-->');

	// Add our setting group.
	$gid = $db->insert_query('settinggroups', 
		array(
			'name'			=> 'ougc_awards',
			'title'			=> $db->escape_string($lang->ougc_awards_settinggroup),
			'description'	=> $db->escape_string($lang->ougc_awards_settinggroup_d),
			'disporder'		=> 9,
			'isdefault'		=> 'no'
		)
	);
	ougc_awards_add_setting('power', 'onoff', 1, 1, $gid);
	ougc_awards_add_setting('postbit', 'text', 4, 2, $gid);
	ougc_awards_add_setting('profile', 'text', 4, 4, $gid);
	ougc_awards_add_setting('hidemcp', 'yesno', 1, 5, $gid);
	ougc_awards_add_setting('moderators', 'text', '', 6, $gid);
	ougc_awards_add_setting('multipage', 'yesno', 0, 7, $gid);
	ougc_awards_add_setting('pmuser', 'yesno', 0, 8, $gid);
	ougc_awards_add_setting('pmuserid', 'text', '-1', 9, $gid);
	rebuild_settings();
}

// Deactivate the plugin.
function ougc_awards_plugin_deactivate()
{
	global $db;

	// Delete any old templates.
	$db->delete_query('templates', "title IN('modcp_ougc_awards', 'modcp_ougc_awards_manage', 'modcp_ougc_awards_nav', 'modcp_ougc_awards_list', 'modcp_ougc_awards_list_empty', 'modcp_ougc_awards_list_award', 'modcp_ougc_awards_manage_reason', 'postbit_ougc_awards', 'member_profile_ougc_awards_row_empty', 'member_profile_ougc_awards_row', 'member_profile_ougc_awards', 'ougc_awards_page', 'ougc_awards_page_list', 'ougc_awards_page_list_award', 'ougc_awards_page_list_empty', 'ougc_awards_page_user', 'ougc_awards_page_user_award', 'ougc_awards_page_user_empty', 'ougc_awards_page_view', 'ougc_awards_page_view_empty', 'ougc_awards_page_view_row') AND sid='-2'");

	// Remove added variables.
	require_once MYBB_ROOT.'/inc/adminfunctions_templates.php';
	find_replace_templatesets('postbit', '#'.preg_quote('{$post[\'ougc_awards\']}').'#', '', 0);
	find_replace_templatesets('postbit_classic', '#'.preg_quote('{$post[\'ougc_awards\']}').'#', '', 0);
	find_replace_templatesets('member_profile', '#'.preg_quote('{$memprofile[\'ougc_awards\']}').'#', '', 0);
	find_replace_templatesets('modcp_nav', '#'.preg_quote('<!--OUGC_AWARDS-->').'#', '', 0);

	// Delete setting group.
	$q = $db->simple_select('settinggroups', 'gid', 'name="ougc_awards"');
	$gid = $db->fetch_field($q, 'gid');
	if($gid)
	{
		$db->delete_query('settings', "gid='{$gid}'");
		$db->delete_query('settinggroups', "gid='{$gid}'");
		rebuild_settings();
	}
}

// Install the plugin.
function ougc_awards_plugin_install()
{
	global $db;

	// Run uninstall function
	ougc_awards_plugin_uninstall();

	$collation = $db->build_create_table_collation();
	// Create our tables if none exists.
	if(!$db->table_exists('ougc_awards'))
	{
		$db->write_query("CREATE TABLE `".TABLE_PREFIX."ougc_awards` (
				`aid` bigint(30) UNSIGNED NOT NULL AUTO_INCREMENT,
				`name` varchar(100) NOT NULL DEFAULT '',
				`description` varchar(255) NOT NULL DEFAULT '',
				`image` varchar(255) NOT NULL DEFAULT '',
				`visible` smallint(1) NOT NULL DEFAULT '1',
				`users` text NOT NULL DEFAULT '',
				`pm` varchar(255) NOT NULL DEFAULT '',
				`type` smallint(1) NOT NULL DEFAULT '0',
				PRIMARY KEY (`aid`)
			) ENGINE=MyISAM{$collation};"
		);
	}
	if(!$db->table_exists('ougc_awards_users'))
	{
		$db->write_query("CREATE TABLE `".TABLE_PREFIX."ougc_awards_users` (
				`gid` bigint(30) UNSIGNED NOT NULL AUTO_INCREMENT,
				`uid` bigint(30) NOT NULL DEFAULT '0',
				`aid` bigint(30) NOT NULL DEFAULT '0',
				`reason` varchar(255) NOT NULL DEFAULT '',
				`date` int(10) NOT NULL DEFAULT '0',
				PRIMARY KEY (`gid`)
			) ENGINE=MyISAM{$collation};"
		);
	}

	// Insert template group.
	$db->insert_query("templategroups",
		array(
			'prefix' => 'ougc_awards',
			'title' => 'OUGC Awards'
		)
	);
}

// Is this plugin installed?
function ougc_awards_plugin_is_installed()
{
	global $db;
	if($db->table_exists('ougc_awards') && $db->table_exists('ougc_awards_users'))
	{
		return true;
	}
	return false;
}

// Uninstall the plugin.
function ougc_awards_plugin_uninstall()
{
	global $db;

	// Delete our tables if none exists.
	if($db->table_exists('ougc_awards'))
	{
		$db->drop_table('ougc_awards');
	}
	if($db->table_exists('ougc_awards_users'))
	{
		$db->drop_table('ougc_awards_users');
	}

	// Insert template group.
	$db->delete_query("templategroups", "prefix='ougc_awards'");

	// Delete the cache.
	$db->delete_query('datacache', "title='ougc_awards'");
}

//\\ ACP SECTION //\\
// Insert our menu at users section.
function ougc_awards_menu(&$sub_menu)
{
	global $lang;
	ougc_awards_lang_load();

	$sub_menu[] = array('id' => 'ougc_awards', 'title' => $lang->ougc_awards_acp_nav, 'link' => 'index.php?module=user-ougc_awards');
}

// Insert our action handler in users section awards page.
function ougc_awards_action_handler(&$action)
{
	$action['ougc_awards'] = array('active' => 'ougc_awards', 'file' => 'ougc_awards');
}

// Insert our plugin into the admin permissions page.
function ougc_awards_admin_permissions(&$admin_permissions)
{
  	global $lang;

	$admin_permissions['ougc_awards'] = $lang->ougc_awards_acp_permissions;
}

// Actual ACP page.
function ougc_awards_admin_load()
{
	global $run_module, $action_file;
	
	if($run_module == 'user' && $action_file == 'ougc_awards')
	{
		global $mybb, $db, $lang, $page;

		$page->add_breadcrumb_item($lang->ougc_awards_acp_nav, 'index.php?module=user-ougc_awards');
		$page->output_header($lang->ougc_awards_acp_nav);
		$mybb->input['aid'] = intval($mybb->input['aid']);
		$mybb->input['uid'] = intval($mybb->input['uid']);

		if(!$mybb->input['action'] || in_array($mybb->input['action'], array('awards', 'add', 'edit', 'give', 'revoke', 'users', 'user')))
		{
			$sub_tabs['ougc_awards_view'] = array(
				'title'			=> $lang->ougc_awards_tab_view,
				'link'			=> 'index.php?module=user-ougc_awards',
				'description'	=> $lang->ougc_awards_tab_view_d
			);
			$sub_tabs['ougc_awards_add'] = array(
				'title'			=> $lang->ougc_awards_tab_add,
				'link'			=> 'index.php?module=user-ougc_awards&amp;action=add',
				'description'	=> $lang->ougc_awards_tab_add_d
			);
			if($mybb->input['action'] == 'edit')
			{
				$sub_tabs['ougc_awards_edit'] = array(
					'title'			=> $lang->ougc_awards_tab_edit,
					'link'			=> 'index.php?module=user-ougc_awards&amp;action=edit&amp;aid='.$mybb->input['aid'],
					'description'	=> $lang->ougc_awards_tab_edit_d
				);
			}
			elseif($mybb->input['action'] == 'give')
			{
				$sub_tabs['ougc_awards_give'] = array(
					'title'			=> $lang->ougc_awards_tab_give,
					'link'			=> 'index.php?module=user-ougc_awards&amp;action=give&amp;aid='.$mybb->input['aid'],
					'description'	=> $lang->ougc_awards_tab_give_d
				);
			}
			elseif($mybb->input['action'] == 'revoke')
			{
				$sub_tabs['ougc_awards_revoke'] = array(
					'title'			=> $lang->ougc_awards_tab_revoke,
					'link'			=> 'index.php?module=user-ougc_awards&amp;action=revoke&amp;aid='.$mybb->input['aid'],
					'description'	=> $lang->ougc_awards_tab_revoke_d
				);
			}
			elseif($mybb->input['action'] == 'users')
			{
				$sub_tabs['ougc_awards_users'] = array(
					'title'			=> $lang->ougc_awards_tab_users,
					'link'			=> 'index.php?module=user-ougc_awards&amp;action=users&amp;aid='.$mybb->input['aid'],
					'description'	=> $lang->ougc_awards_tab_users_d
				);
			}
			elseif($mybb->input['action'] == 'user')
			{
				$sub_tabs['ougc_awards_edit_user'] = array(
					'title'			=> $lang->ougc_awards_tab_edit_user,
					'link'			=> 'index.php?module=user-ougc_awards&amp;action=user&amp;aid='.$mybb->input['aid'].'&amp;uid='.$mybb->input['uid'],
					'description'	=> $lang->ougc_awards_tab_edit_user_d
				);
			}
			$sub_tabs['ougc_awards_cache'] = array(
				'title'			=> $lang->ougc_awards_tab_cache,
				'link'			=> 'index.php?module=user-ougc_awards&amp;action=rebuilt_cache',
				'description'	=> $lang->ougc_awards_tab_cache_d
			);
		}

		if(!$mybb->input['action'] || $mybb->input['action'] == 'awards')
		{
			$page->output_nav_tabs($sub_tabs, 'ougc_awards_view');

			$table = new Table;
			$table->construct_header($lang->ougc_awards_view_image, array('width' => '1%'));
			$table->construct_header($lang->ougc_awards_form_name, array('width' => '19%'));
			$table->construct_header($lang->ougc_awards_form_desc, array('width' => '45%'));
			$table->construct_header($lang->ougc_awards_form_visible, array('width' => '10%', 'class' => 'align_center'));
			$table->construct_header($lang->ougc_awards_view_actions, array('width' => '15%', 'class' => 'align_center'));

			$query = $db->simple_select('ougc_awards');
			if($db->num_rows($query) < 1)
			{
				$table->construct_cell('<div align="center">'.$lang->ougc_awards_view_empty.'</div>', array('colspan' => 6));
				$table->construct_row();
			}
			else
			{
				while($award = $db->fetch_array($query))
				{
					$table->construct_cell('<img src="'.ougc_awards_get_icon($award['image']).'" />', array('class' => 'align_center'));
					$table->construct_cell(htmlspecialchars_uni($award['name']));
					$table->construct_cell(htmlspecialchars_uni($award['description']));
					$table->construct_cell('<img src="../inc/plugins/ougc_awards/bullet_'.(!$award['visible'] ? 'red' : 'green').'.png" alt="" title="'.(!$award['visible'] ? $lang->ougc_awards_form_hidden : $lang->ougc_awards_form_visible).'" />', array('class' => 'align_center'));

					$popup = new PopupMenu("award_{$award['aid']}", $lang->options);
					$popup->add_item($lang->ougc_awards_tab_give, "index.php?module=user-ougc_awards&amp;action=give&amp;aid={$award['aid']}");
					$popup->add_item($lang->ougc_awards_tab_revoke, "index.php?module=user-ougc_awards&amp;action=revoke&amp;aid={$award['aid']}");
					$popup->add_item($lang->ougc_awards_tab_users, "index.php?module=user-ougc_awards&amp;action=users&amp;aid={$award['aid']}");
					$popup->add_item($lang->ougc_awards_tab_edit, "index.php?module=user-ougc_awards&amp;action=edit&amp;aid={$award['aid']}");
					$popup->add_item($lang->ougc_awards_tab_delete, "index.php?module=user-ougc_awards&amp;action=delete&amp;aid={$award['aid']}");
					$table->construct_cell($popup->fetch(), array('class' => 'align_center'));

					$table->construct_row();
				}
			}
			$db->free_result($query);
			$table->output($lang->ougc_awards_tab_view_d);
		}
		elseif($mybb->input['action'] == 'add')
		{
			if($mybb->request_method == 'post')
			{
				if($mybb->input['name'] == '')
				{
					flash_message($lang->ougc_awards_error_add, 'error');
					admin_redirect("index.php?module=user-ougc_awards&amp;action=add");
				}
				$insert_data = array(
					'name'			=>	$mybb->input['name'],
					'description'	=>	$mybb->input['description'],
					'image'			=>	$mybb->input['image'],
					'visible'		=>	$mybb->input['visible'],
					'pm'			=>	$mybb->input['pm'],
					'type'			=>	$mybb->input['type']
				);
				log_admin_action($lang->ougc_awards_form_name.': '.$insert_data['name']);
				ougc_awards_add_award($insert_data);
				flash_message($lang->ougc_awards_success_add, 'success');
				admin_redirect("index.php?module=user-ougc_awards");
			}

			$page->output_nav_tabs($sub_tabs, 'ougc_awards_add');
			$form = new Form("index.php?module=user-ougc_awards&amp;action=add", "post");
			$form_container = new FormContainer($lang->ougc_awards_form_add);

			$form_container->output_row($lang->ougc_awards_form_name." <em>*</em>", $lang->ougc_awards_form_name_d, $form->generate_text_box('name'));
			$form_container->output_row($lang->ougc_awards_form_desc, $lang->ougc_awards_form_desc_d, $form->generate_text_box('description'));
			$form_container->output_row($lang->ougc_awards_form_image, $lang->ougc_awards_form_image_d, $form->generate_text_box('image'));
			$form_container->output_row($lang->ougc_awards_form_visible, $lang->ougc_awards_form_visible_d, $form->generate_yes_no_radio('visible'));
			$form_container->output_row($lang->ougc_awards_form_pm, $lang->ougc_awards_form_pm_d, $form->generate_text_area('pm', '', array('rows' => 8, 'style' => 'width:80%;')));
			$types = array(
				0 => $lang->ougc_awards_form_type_0,
				1 => $lang->ougc_awards_form_type_1,
				2 => $lang->ougc_awards_form_type_2
			);
			$form_container->output_row($lang->ougc_awards_form_type, $lang->ougc_awards_form_type_d, $form->generate_select_box('type', $types));

			$form_container->end();
			$buttons = array();
			$buttons[] = $form->generate_submit_button($lang->ougc_awards_button_submit);
			$buttons[] = $form->generate_reset_button($lang->ougc_awards_button_reset);
			$form->output_submit_wrapper($buttons);
			$form->end();
		}
		elseif($mybb->input['action'] == 'edit')
		{
			if(!($award = ougc_awards_get_award($mybb->input['aid'])))
			{
				flash_message($lang->ougc_awards_error_edit, 'error');
				admin_redirect("index.php?module=user-ougc_awards");
			}
			if($mybb->request_method == 'post')
			{
				if($mybb->input['name'] == '')
				{
					flash_message($lang->ougc_awards_error_edit, 'error');
					admin_redirect("index.php?module=user-ougc_awards&amp;action=edit&amp;aid={$award['aid']}");
				}
				$update_data = array(
					'name'			=>	$mybb->input['name'],
					'description'	=>	$mybb->input['description'],
					'image'			=>	$mybb->input['image'],
					'visible'		=>	$mybb->input['visible'],
					'pm'			=>	$mybb->input['pm'],
					'type'			=>	$mybb->input['type']
				);
				log_admin_action($lang->ougc_awards_form_name.': '.$update_data['name'], $award['aid']);
				ougc_awards_update_award($award['aid'], $update_data);
				ougc_awards_update_cache();
				flash_message($lang->ougc_awards_success_edit, 'success');
				admin_redirect("index.php?module=user-ougc_awards");
			}

			$page->output_nav_tabs($sub_tabs, 'ougc_awards_edit');
			$form = new Form("index.php?module=user-ougc_awards&amp;action=edit&amp;aid={$award['aid']}", "post");
			$form_container = new FormContainer($lang->ougc_awards_tab_edit_d);

			$form_container->output_row($lang->ougc_awards_form_name." <em>*</em>", $lang->ougc_awards_form_name_d, $form->generate_text_box('name', $award['name']));
			$form_container->output_row($lang->ougc_awards_form_desc, $lang->ougc_awards_form_desc_d, $form->generate_text_box('description', $award['description']));
			$form_container->output_row($lang->ougc_awards_form_image, $lang->ougc_awards_form_image_d, $form->generate_text_box('image', htmlspecialchars_uni($award['image'])));
			$form_container->output_row($lang->ougc_awards_form_visible, $lang->ougc_awards_form_visible_d, $form->generate_yes_no_radio('visible', intval($award['visible'])));
			$form_container->output_row($lang->ougc_awards_form_pm, $lang->ougc_awards_form_pm_d, $form->generate_text_area('pm', htmlspecialchars_uni($award['pm']), array('rows' => 8, 'style' => 'width:80%;')));
			$types = array(
				0 => $lang->ougc_awards_form_type_0,
				1 => $lang->ougc_awards_form_type_1,
				2 => $lang->ougc_awards_form_type_2
			);
			$form_container->output_row($lang->ougc_awards_form_type, $lang->ougc_awards_form_type_d, $form->generate_select_box('type', $types, array('selected' => $award['type'])));


			$form_container->end();
			$buttons = array();
			$buttons[] = $form->generate_submit_button($lang->ougc_awards_button_submit);
			$buttons[] = $form->generate_reset_button($lang->ougc_awards_button_reset);
			$form->output_submit_wrapper($buttons);
			$form->end();
		}
		elseif($mybb->input['action'] == 'delete')
		{
			if(!($award = ougc_awards_get_award($mybb->input['aid'])) || ($mybb->request_method == 'post' && $mybb->input['my_post_key'] != $mybb->post_code) || $mybb->input['no'])
			{
				if(!$mybb->input['no'])
				{
					flash_message($lang->ougc_awards_error_delete, 'error');
				}
				admin_redirect("index.php?module=user-ougc_awards");
			}
			if($mybb->request_method == 'post')
			{
				log_admin_action($lang->ougc_awards_form_name.': '.$award['name'], $award['aid']);
				ougc_awards_delete_award($award['aid']);
				flash_message($lang->ougc_awards_success_delete, 'success');
				admin_redirect("index.php?module=user-ougc_awards");
			}
			$form = new Form("index.php?module=user-ougc_awards&amp;action=delete&amp;aid={$award['aid']}&amp;my_post_key={$mybb->post_code}", 'post');
			echo("
				<div class=\"confirm_action\">\n
				<p>{$lang->ougc_awards_delete_confirm}</p><br />\n
				<p class=\"buttons\">
				{$form->generate_submit_button($lang->yes, array('class' => 'button_yes'))}
				{$form->generate_submit_button($lang->no, array("name" => "no", 'class' => 'button_no'))}
				</p>\n
				</div>
			");
			$form->end();
		}
		elseif($mybb->input['action'] == 'give')
		{
			if(!($award = ougc_awards_get_award($mybb->input['aid'])))
			{
				flash_message($lang->ougc_awards_error_give, 'error');
				admin_redirect("index.php?module=user-ougc_awards");
			}
			if($mybb->request_method == 'post')
			{
				if((!($user = ougc_awards_get_user($mybb->input['username']))))
				{
					flash_message($lang->ougc_awards_error_give, 'error');
					admin_redirect("index.php?module=user-ougc_awards&amp;action=give&amp;aid={$award['aid']}");
				}
				if(($gived = ougc_awards_get_gived_award($award['users'], $user['uid'])))
				{
					flash_message($lang->ougc_awards_error_give, 'error');
					admin_redirect("index.php?module=user-ougc_awards&amp;action=give&amp;aid={$award['aid']}");
				}
				log_admin_action($lang->ougc_awards_form_username.': '.$mybb->input['username'], $lang->ougc_awards_form_award.': '.$award['name'].'('.$award['aid'].')');
				ougc_awards_give_award($award, $user['uid'], $mybb->input['reason']);
				flash_message($lang->ougc_awards_success_give, 'success');
				admin_redirect("index.php?module=user-ougc_awards");
			}
			$page->output_nav_tabs($sub_tabs, 'ougc_awards_give');
			$form = new Form("index.php?module=user-ougc_awards&amp;action=give&amp;aid={$award['aid']}", "post");
			$form_container = new FormContainer($lang->ougc_awards_tab_give_d);

			$form_container->output_row($lang->ougc_awards_form_username." <em>*</em>", $lang->ougc_awards_form_username_d, $form->generate_text_box('username'));
			$form_container->output_row($lang->ougc_awards_form_reason, $lang->ougc_awards_form_reason_d, $form->generate_text_area('reason', '', array('rows' => 8, 'style' => 'width:80%;')));

			$form_container->end();
			$buttons = array();
			$buttons[] = $form->generate_submit_button($lang->ougc_awards_button_submit);
			$buttons[] = $form->generate_reset_button($lang->ougc_awards_button_reset);
			$form->output_submit_wrapper($buttons);
			$form->end();
		}	
		elseif($mybb->input['action'] == 'revoke')
		{
			if(!($award = ougc_awards_get_award($mybb->input['aid'])))
			{
				flash_message($lang->ougc_awards_error_revoke, 'error');
				admin_redirect("index.php?module=user-ougc_awards");
			}
			if($mybb->request_method == 'post')
			{
				if(!($user = ougc_awards_get_user($mybb->input['username'])))
				{
					flash_message($lang->ougc_awards_error_revoke, 'error');
					admin_redirect("index.php?module=user-ougc_awards&amp;action=revoke&amp;aid={$mybb->input['aid']}");
				}
				if(!($gived = ougc_awards_get_gived_award($award['users'], $user['uid'])))
				{
					flash_message($lang->ougc_awards_error_revoke, 'error');
					admin_redirect("index.php?module=user-ougc_awards&amp;action=revoke&amp;aid={$mybb->input['aid']}");
				}
				log_admin_action($lang->ougc_awards_form_username.': '.$mybb->input['username'], $lang->ougc_awards_form_award.': '.$award['name'].'('.$award['aid'].')');
				ougc_awards_revoke_award($award, $user['uid']);
				flash_message($lang->ougc_awards_success_revoke, 'success');
				admin_redirect("index.php?module=user-ougc_awards");
			}
			$page->output_nav_tabs($sub_tabs, 'ougc_awards_revoke');
			$form = new Form("index.php?module=user-ougc_awards&amp;action=revoke&amp;aid={$mybb->input['aid']}", "post");
			$form_container = new FormContainer($lang->ougc_awards_tab_revoke_d);

			$form_container->output_row($lang->ougc_awards_form_username." <em>*</em>", $lang->ougc_awards_form_username_d, $form->generate_text_box('username'));

			$form_container->end();
			$buttons = array();
			$buttons[] = $form->generate_submit_button($lang->ougc_awards_button_submit);
			$buttons[] = $form->generate_reset_button($lang->ougc_awards_button_reset);
			$form->output_submit_wrapper($buttons);
			$form->end();
		}
		elseif($mybb->input['action'] == 'users')
		{
			$page->output_nav_tabs($sub_tabs, 'ougc_awards_users');

			$table = new Table;
			$table->construct_header($lang->ougc_awards_form_username, array('width' => '15%'));
			$table->construct_header($lang->ougc_awards_form_reason, array('width' => '45%'));
			$table->construct_header($lang->ougc_awards_users_date, array('width' => '25%', 'class' => 'align_center'));
			$table->construct_header($lang->ougc_awards_view_actions, array('width' => '15%', 'class' => 'align_center'));

			$limit = 20;
			$mybb->input['page'] = intval($mybb->input['page']);
			if($mybb->input['page'] && $mybb->input['page'] > 0)
			{
				$start = ($mybb->input['page'] - 1)*$limit;
			}
			else
			{
				$start = 0;
				$mybb->input['page'] = 1;
			}
			
			$q = $db->simple_select('ougc_awards_users', '*', "aid='{$mybb->input['aid']}'", array('order_by' => 'date', 'order_dir' => 'desc', 'limit_start' => $start, 'limit' => $limit));
			$q2 = $db->simple_select('ougc_awards_users', 'COUNT(uid) AS users', "aid='{$mybb->input['aid']}'");
			$num_results = $db->fetch_field($q2, "users");
			$db->free_result($q2);
			if($db->num_rows($q) < 1)
			{
				$table->construct_cell('<div align="center">'.$lang->ougc_awards_users_empty.'</div>', array('colspan' => 6));
				$table->construct_row();
			}
			else
			{
				while($gived = $db->fetch_array($q))
				{
					$user = get_user($gived['uid']);
					$user['username'] = format_name(htmlspecialchars_uni($user['username']), $user['usergroup'], $user['displaygroup']);
					$table->construct_cell("<a href=\"index.php?module=user-users&action=edit&uid={$user['uid']}\">{$user['username']}</a>");
					$table->construct_cell(htmlspecialchars_uni($gived['reason']));
					$table->construct_cell($lang->sprintf($lang->ougc_awards_users_time, my_date($mybb->settings['dateformat'], intval($gived['date'])), my_date($mybb->settings['timeformat'], intval($gived['date']))), array('class' => 'align_center'));
					$table->construct_cell("<a href=\"index.php?module=user-ougc_awards&amp;action=user&amp;aid={$gived['aid']}&amp;uid={$user['uid']}\">{$lang->ougc_awards_tab_edit}</a>", array('class' => 'align_center'));

					$table->construct_row();
				}
			}
			$db->free_result($q);
			$table->output($lang->ougc_awards_tab_users_d);
			echo draw_admin_pagination($mybb->input['page'], $limit, $num_results, $view['url']."index.php?module=user-ougc_awards&amp;action=users&amp;aid=1");
		}
		elseif($mybb->input['action'] == 'user')
		{
			if(!($award = ougc_awards_get_award($mybb->input['aid'])))
			{
				flash_message($lang->ougc_awards_error_edit, 'error');
				admin_redirect("index.php?module=user-ougc_awards");
			}
			if(!($gived = ougc_awards_get_gived_award($award['users'], $mybb->input['uid'])))
			{
				flash_message($lang->ougc_awards_error_edit, 'error');
				admin_redirect("index.php?module=user-ougc_awards&amp;action=users&amp;aid={$mybb->input['aid']}");
			}
			$gived = ougc_awards_get_awarded($award['aid'], $mybb->input['uid']);
			if($mybb->request_method == 'post')
			{
				$data = array(
					'aid' => $mybb->input['awards_selector'],
					'date' => $mybb->input['date'],
					'reason' => $mybb->input['reason']
				);
				log_admin_action($lang->ougc_awards_form_award.': '.$award['name'].'('.$award['aid'].')', 'UID: '.$mybb->input['uid']);
				ougc_awards_update_user($gived['gid'], $mybb->input['uid'], $data);
				flash_message($lang->ougc_awards_success_edit, 'success');
				admin_redirect("index.php?module=user-ougc_awards&amp;action=users&aid={$mybb->input['aid']}");
			}

			$page->output_nav_tabs($sub_tabs, 'ougc_awards_edit_user');
			$form = new Form("index.php?module=user-ougc_awards&amp;action=user&aid={$mybb->input['aid']}&uid={$mybb->input['uid']}", "post");
			$form_container = new FormContainer($lang->ougc_awards_tab_edit_user_d);

			$form_container->output_row($lang->ougc_awards_form_award, $lang->ougc_awards_form_award_d, ougc_awards_build_selector($award['aid']));
			$form_container->output_row($lang->ougc_awards_form_reason, $lang->ougc_awards_form_reason_d, $form->generate_text_area('reason', htmlspecialchars_uni($gived['reason']), array('rows' => 8, 'style' => 'width:80%;')));
			$form_container->output_row($lang->ougc_awards_users_timestamp, $lang->ougc_awards_users_timestamp_d, $form->generate_text_box('date', intval($gived['date'])));

			$form_container->end();
			$buttons = array();
			$buttons[] = $form->generate_submit_button($lang->ougc_awards_button_submit);
			$buttons[] = $form->generate_reset_button($lang->ougc_awards_button_reset);
			$form->output_submit_wrapper($buttons);
			$form->end();
		}
		elseif($mybb->input['action'] == 'rebuilt_cache')
		{
			log_admin_action();
			ougc_awards_update_cache();
			flash_message($lang->ougc_awards_success_cache, 'success');
			admin_redirect("index.php?module=user-ougc_awards");
		}
		$page->output_footer();
	}
}


//\\ FORUM SECTION //\\
// Show awards in profile function.
function ougc_awards_postbit(&$post)
{
	global $mybb;

	$post['ougc_awards'] = '';
	$max_postbit = intval($mybb->settings['ougc_awards_postbit']);
	if($mybb->settings['ougc_awards_power'] == 1 && ($max_postbit > 0 || $max_postbit == -1))
	{
		global $cache, $templates;

		$awards = $cache->read('ougc_awards'); // Get all awards.
		if(is_array($awards))
		{
			$count = 0;
			foreach($awards as $award)
			{
				$award['users'] = explode(',', $award['users']);
				// Check if this user UID match with the list of each awards already in the cache.
				// Only visible awards and awards that are suppose to be shown should be in the cache.
				if(in_array($post['uid'], $award['users']))
				{
					$award['aid'] = intval($award['aid']);
					$award['name'] = ougc_awards_get_award_info('name', $award['aid'], $award['name']);
					$award['image'] = ougc_awards_get_icon($award['image']);
					if($max_postbit == -1 || $count < $max_postbit)
					{
						$count++;
						$br = '';
						if($count == 1)
						{
							$br = '<br />'; // We insert a break if it is the first award.
						}
						eval("\$post['ougc_awards'] .= \"".$templates->get("postbit_ougc_awards")."\";");
					}
				}
			}
		}
	}
}

// Show awards in profiles function
function ougc_awards_profile()
{
	global $mybb, $memprofile;

	$memprofile['ougc_awards'] = '';
	$max_profile = intval($mybb->settings['ougc_awards_profile']);
	if($mybb->settings['ougc_awards_power'] == 1 && ($max_profile > 0 || $max_profile == -1))
	{
		global $db, $lang, $templates, $theme;
		ougc_awards_lang_load();

		// Query our data.
		// First we need to  figure out the total amount of awards.
		$query = $db->query("
			SELECT u.aid, a.aid
			FROM ".TABLE_PREFIX."ougc_awards_users u
			LEFT JOIN ".TABLE_PREFIX."ougc_awards a ON (u.aid=a.aid) AND a.visible='1'
			WHERE u.uid='".intval($memprofile['uid'])."'
			ORDER BY u.date desc
		");
		$awardscount = $db->num_rows($query);

		// Now we get the awards.
		$limit = '';
		$multipage = '';
		if($mybb->settings['ougc_awards_multipage'] == 1)
		{
			if($max_profile == -1)
			{
				$max_profile = 10;
			}
			$page = intval($mybb->input['page']);
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
			$limit .= " LIMIT {$limit_start},{$max_profile}";
			$link = get_profile_link($memprofile['uid']);
			$link .= (strpos($link, "?") === false ? '?' : '&amp;').'awards';
			$multipage = multipage($awardscount, $max_profile, $page, $link);
		}
		elseif($max_profile != -1)
		{
			$limit .= " LIMIT {$max_profile}";
		}
		$query = $db->query("
			SELECT u.*, a.*
			FROM ".TABLE_PREFIX."ougc_awards_users u
			LEFT JOIN ".TABLE_PREFIX."ougc_awards a ON (u.aid=a.aid) AND a.visible='1'
			WHERE u.uid='".intval($memprofile['uid'])."'
			ORDER BY u.date desc{$limit}
		");

		// Output ouw awards.
		$awards = '';
		while($award = $db->fetch_array($query))
		{
			$trow = alt_trow();
			$award['name'] = ougc_awards_get_award_info('name', $award['aid'], $award['name']);
			$award['description'] = ougc_awards_get_award_info('desc', $award['aid'], $award['description']);
			$award['image'] = ougc_awards_get_icon($award['image']);
			$award['reason'] = ougc_awards_get_award_info('reason', $award['aid'], $award['reason'], $award['gid']);
			$award['date'] = $lang->sprintf($lang->ougc_awards_profile_tine, my_date($mybb->settings['dateformat'], $award['date']), my_date($mybb->settings['timeformat'], $award['date']));
			eval("\$awards .= \"".$templates->get("member_profile_ougc_awards_row")."\";");
		}
		$db->free_result($query);

		// User has no awards.
		if(!$awards)
		{
			eval("\$awards = \"".$templates->get("member_profile_ougc_awards_row_empty")."\";");
		}
		$lang->ougc_awards_profile_title = $lang->sprintf($lang->ougc_awards_profile_title, htmlspecialchars_uni($memprofile['username']));
		eval("\$memprofile['ougc_awards'] = \"".$templates->get("member_profile_ougc_awards")."\";");
	}
}

// ModCP Part
function ougc_awards_modcp()
{
	global $mybb, $modcp_nav, $templates, $lang;
	ougc_awards_lang_load();

	$permission = (($mybb->usergroup['cancp'] == 1 || ougc_check_groups($mybb->settings['ougc_awards_moderators'], false)) && $mybb->settings['ougc_awards_power'] == 1 ? true : false);
	if($permission)
	{
		eval("\$awards_nav = \"".$templates->get("modcp_ougc_awards_nav")."\";");
		$modcp_nav = str_replace('<!--OUGC_AWARDS-->', $awards_nav, $modcp_nav);
	}
	else
	{
		$modcp_nav = str_replace('<!--OUGC_AWARDS-->', '', $modcp_nav);
	}
	if($mybb->input['action'] == 'awards' && $permission)
	{
		global $headerinclude, $header, $errors, $theme, $cache, $footer;

		add_breadcrumb($lang->ougc_awards_modcp_nav, 'modcp.php?action=awards');
		$error = array();
		// Clean the cache from the ModCP
		if($mybb->input['manage'] == 'update_cache')
		{
			log_moderator_action(null, $lang->ougc_awards_modcp_cache);
			ougc_awards_update_cache();
			redirect('modcp.php?action=awards', $lang->ougc_awards_redirect_cache);
		}
		// We can give awards from the ModCP
		elseif($mybb->input['manage'] == 'give')
		{
			if(!($award = ougc_awards_get_award($mybb->input['aid'])))
			{
				error($lang->ougc_awards_error_wrongaward);
			}
			if($mybb->settings['ougc_awards_hidemcp'] == 1 && $award['visible'] != 1)
			{
				$award['visible'] = 1;
			}
			if($award['visible'] != 1)
			{
				error($lang->ougc_awards_error_wrongaward);
			}
			if($mybb->request_method == 'post')
			{
				if(!($user = ougc_awards_get_user($mybb->input['username'])))
				{
					$error[] = $lang->ougc_awards_error_wronguser;
				}
				if(($gived = ougc_awards_get_gived_award($award['users'], $user['uid'])))
				{
					$error[] = $lang->ougc_awards_error_duplicated;
				}
				if($error)
				{
					$errors = inline_error($error);
				}
				else
				{
					// Everythig is suppose to be alright. Insert our award.
					$log = array(
						'award' => $award['name'],
						'awardid' => $award['aid'],
						'user' => $mybb->input['username']
					);
					log_moderator_action($log, $lang->ougc_awards_redirect_gived);
					ougc_awards_give_award($award, $user['uid'], $mybb->input['reason']);
					redirect('modcp.php?action=awards', $lang->ougc_awards_redirect_gived);
				}
			}
			add_breadcrumb($lang->ougc_awards_modcp_give);
			$lang->ougc_awards_modcp_title_give = $lang->sprintf($lang->ougc_awards_modcp_title_give, ougc_awards_get_award_info('name', $award['aid'], $award['name']));
			eval("\$reason = \"".$templates->get("modcp_ougc_awards_manage_reason")."\";");
			eval("\$content = \"".$templates->get("modcp_ougc_awards_manage")."\";");
			eval("\$page = \"".$templates->get("modcp_ougc_awards")."\";");
			output_page($page);
			exit;
		}
		// We can revoke awards from the ModCP
		elseif($mybb->input['manage'] == 'revoke')
		{
			if(!($award = ougc_awards_get_award($mybb->input['aid'])))
			{
				error($lang->ougc_awards_error_wrongaward);
			}
			if($mybb->settings['ougc_awards_hidemcp'] == 1 && $award['visible'] != 1)
			{
				$award['visible'] = 1;
			}
			if($award['visible'] != 1)
			{
				error($lang->ougc_awards_error_wrongaward);
			}
			if($mybb->request_method == 'post')
			{
				if(!($user = ougc_awards_get_user($mybb->input['username'])))
				{
					$error[] = $lang->ougc_awards_error_wronguser;
				}
				if(!($gived = ougc_awards_get_gived_award($award['users'], $user['uid'])))
				{
					$error[] = $lang->ougc_awards_error_nowarded;
				}
				if($error)
				{
					$errors = inline_error($error);
				}
				else
				{
					// Everythig is suppose to be alright. Revoke our award.
					$log = array(
						'award' => $award['name'],
						'awardid' => $award['aid'],
						'user' => $mybb->input['username']
					);
					log_moderator_action($log, $lang->ougc_awards_redirect_revoked);
					ougc_awards_revoke_award(array('aid' => $award['aid'], 'users' => $award['users']), $user['uid']);
					redirect('modcp.php?action=awards', $lang->ougc_awards_redirect_revoked);
				}
			}
			add_breadcrumb($lang->ougc_awards_modcp_revoke);
			$lang->ougc_awards_modcp_title_give = $lang->sprintf($lang->ougc_awards_modcp_title_give, ougc_awards_get_award_info('name', $award['aid'], $award['name']));
			$lang->ougc_awards_modcp_give = $lang->ougc_awards_modcp_revoke;
			eval("\$content = \"".$templates->get("modcp_ougc_awards_manage")."\";");
			eval("\$page = \"".$templates->get("modcp_ougc_awards")."\";");
			output_page($page);
			exit;
		}
		/*elseif($mybb->input['manage'] == 'edit')
		{
		 // TODO: Write this part.
					$log = array(
						'awardid' => $award['aid'],
						'uid' => $award['uid']
					);
					log_moderator_action($log, $lang->ougc_awards_redirect_revoked);
		}*/
		else
		{
			global $db;
			$where = '';
			if($mybb->settings['ougc_awards_hidemcp'] != 1)
			{
				$where .= "visible='1'";
			}
			$query = $db->simple_select('ougc_awards', 'image, name, description, aid', "{$where}");
			$awards = '';
			while($award = $db->fetch_array($query))
			{
				$trow = alt_trow();

				$award['aid'] = intval($award['aid']);
				$award['image'] = ougc_awards_get_icon($award['image']);
				$award['name'] = ougc_awards_get_award_info('name', $award['aid'], $award['name']);
				$award['description'] = ougc_awards_get_award_info('desc', $award['aid'], $award['description']);

				eval("\$awards .= \"".$templates->get("modcp_ougc_awards_list_award")."\";");
			}
			$db->free_result($query);

			if(!$awards)
			{
				eval("\$awards = \"".$templates->get("modcp_ougc_awards_list_empty")."\";");
			}

			eval("\$content = \"".$templates->get("modcp_ougc_awards_list")."\";");
			eval("\$page = \"".$templates->get("modcp_ougc_awards")."\";");
			output_page($page);
			exit;
		}
	}
	elseif($mybb->input['action'] == 'awards')
	{
		error_no_permission();
	}
}


//\\ SOME FUNCTIONS //\\
// Get the award from cache or DB.
function ougc_awards_get_award($aid)
{
	global $db;

	$aid = intval($aid);
	$query = $db->simple_select('ougc_awards', '*', "aid='{$aid}'");
	$award = $db->fetch_array($query);
	$db->free_result($query);

	if($award['aid'])
	{
		return $award;
	}
	return false;
}

// Get UID by username (we just need the uid).
function ougc_awards_get_user($username)
{
	global $db;

	$q = $db->simple_select('users', 'uid', "username='{$db->escape_string($username)}'");
	$user = $db->fetch_array($q);
	$db->free_result($q);

	if($user['uid'] && intval($user['uid']) > 0)
	{
		return $user;
	}
	return false;
}

// Check if this user already has an award.
function ougc_awards_get_gived_award($users, $uid)
{
	$users = explode(',', $users);
	if(!is_array($users))
	{
		$users = array();
	}
	foreach($users as $user)
	{
		if($user == $uid)
		{
			return true;
		}
	}
	return false;
}

// Give an award.
function ougc_awards_give_award($award, $uid, $reason)
{
	global $db;

	// Insert our gived award.
	$db->insert_query('ougc_awards_users',
		array(
			'aid'			=>	intval($award['aid']),
			'uid'			=>	intval($uid),
			'reason'		=>	$db->escape_string($reason),
			'date'			=>	TIME_NOW
		)
	);

	// Send our PM.
	$pm_data = array(
		'aid'		=>	$award['aid'],
		'visible'	=>	$award['visible'],
		'pm'		=>	$award['pm'],
		'name'		=>	ougc_awards_get_award_info('name', $award['aid'], $award['name']),
		'image'		=>	$award['image']
	);
	ougc_awards_send_pm($pm_data, $uid, $reason);

	// We need to update the cache.
	ougc_awards_update_cache();
}

// Revoke an award.
function ougc_awards_revoke_award($award=array(), $uid)
{
	global $db;

	// If user has two of the same award, it will delete it now too (this plugin doesn't support multiple of the same award anyways).
	$db->delete_query('ougc_awards_users', "aid='".intval($award['aid'])."' AND uid='".intval($uid)."'");
	ougc_awards_update_cache();
}

// Update the defined award.
function ougc_awards_update_award($aid, $data=array())
{
	global $db;

	$aid = intval($aid);
	if($data['name'])
	{
		$data['name'] = $db->escape_string(unhtmlentities($data['name']));
	}
	if($data['description'])
	{
		$data['description'] = $db->escape_string(unhtmlentities($data['description']));
	}
	if($data['image'])
	{
		$data['image'] = $db->escape_string(unhtmlentities($data['image']));
	}
	if($data['visible'])
	{
		$data['visible'] = (intval($data['visible']) == 1 ? 1 : 0);
	}
	if($data['pm'])
	{
		$data['pm'] = $db->escape_string(unhtmlentities($data['pm']));
	}
	if($data['users'])
	{
		$data['users'] = $db->escape_string($data['users']);
	}
	if($data['type'])
	{
		$data['type'] = intval($data['type']);
	}
	$db->update_query('ougc_awards', $data, "aid='{$aid}'");
}

// Update a awarded user data
function ougc_awards_update_user($gid=0, $uid=0, $data=array())
{
	global $db;
	$gid = intval($gid);
	$uid = intval($uid);

	if(!$gid || !$uid || !is_array($data))
	{
		return false;
	}
	if($data['aid'])
	{
		// Check if award exist.
		if(($award = ougc_awards_get_award($data['aid'])))
		{
			// Check is this user already has the new award..
			if(!($gived = ougc_awards_get_gived_award($award['users'], $uid)) && $uid)
			{
				// Change it.
				$data['aid'] = intval($award['aid']);
			}
			else
			{
				unset($data['aid']);
			}
		}
		else
		{
			unset($data['aid']);
		}
	}
	if($data['date'])
	{
		$data['date'] = intval($data['date']);
	}
	if($data['reason'])
	{
		$data['reason'] = $db->escape_string(unhtmlentities($data['reason']));
	}
	$db->update_query('ougc_awards_users', $data, "gid='{$gid}'");
	ougc_awards_update_cache();
}

// Send a PM when award is given.
function ougc_awards_send_pm($pm_data=array(), $uid, $reason='')
{
	global $mybb, $lang, $cache;
	ougc_awards_lang_load(true);

	// Check if send this award.
	if(!$pm_data['aid'] || $pm_data['visible'] != 1 || $mybb->settings['enablepms'] != 1)
	{
		return false;
	}

	// Get the award PM content.
	$pm_data['pm'] = ougc_awards_get_award_info('pm', $pm_data['aid'], $pm_data['pm']);
	if(empty($pm_data['pm']))
	{
		return false;
	}

	// We are ready to send it.
	require_once MYBB_ROOT."inc/datahandlers/pm.php";
	$pmhandler = new PMDataHandler();
	$touid = array();
	$touid[] = intval($uid);

	// Figure out if to use current connected user as PM sender.
	$uid = intval($mybb->settings['ougc_awards_pmuserid']);
	if($mybb->settings['ougc_awards_pmuser'] == 1 && $mybb->user['uid'])
	{
		$uid = $mybb->user['uid'];
	}
	elseif($uid < 1)
	{
		$uid = -1;
	}

	$pm_data['username'] = htmlspecialchars_uni($mybb->input['username']);
	$pm_data['name'] = ougc_awards_get_award_info('name', $pm_data['aid'], $pm_data['name']);
	$reason = (!empty($reason) ? htmlspecialchars_uni($reason) : $lang->ougc_awards_pm_noreason); // TODO: Maybe modify this to search for lang variable too.
	$pm_data['image'] = ougc_awards_get_icon($pm_data['image']);
	$pm = array(
		'subject'	=>	$lang->sprintf($lang->ougc_awards_pm_title, $pm_data['name']),
		'message'	=>	$lang->sprintf($pm_data['pm'], $pm_data['username'], $pm_data['name'], $reason, $pm_data['image'], htmlspecialchars_uni($mybb->settings['bbname'])),
		'icon'		=>	-1,
		'fromid'	=>	intval($uid),
		'toid'		=>	$touid
	);
	$pmhandler->admin_override = true;
	$pmhandler->set_data($pm);

	if(!$pmhandler->validate_pm())
	{
		$pmhandler->is_validated = true;
		$pmhandler->errors = array();
	}
	$pminfo = $pmhandler->insert_pm();
}

// Parse data with the mybb parser (for reasons).
function ougc_awards_parse_text($message)
{
	global $parser;
	if(!is_object($parser))
	{
		require_once MYBB_ROOT.'inc/class_parser.php';
		$parser = new postParser;
	}
	$parser_options = array(
		'allow_html'		=> 0,
		'allow_smilies'		=> 1,
		'allow_mycode'		=> 1,
		'filter_badwords'	=> 1,
		'shorten_urls'		=> 1
	);
	$message = $parser->parse_message($message, $parser_options);
	return $message;
}

// Load lang files from our plugin directory, not from mybb default.
function ougc_awards_lang_load($datahandler=false)
{
	global $lang, $mybb;

	$lang->set_path(MYBB_ROOT."inc/plugins/ougc_awards/languages");

	// We need to load it like a datahandler because well, it actualy is used to handle data >_>? (for sending PM)
	if($datahandler == false)
	{
		$lang->load('ougc_awards');
	}
	else
	{
		$lang->load('ougc_awards', true);
	}

	// Load extra lang files if they exists, if no, not a big deal...
	if((defined('IN_ADMINCP') && $mybb->input['module'] == 'user-ougc_awards') || (!defined('IN_ADMINCP') && $mybb->input['action'] == 'awards'))
	{
		// If we use $isdatahandler=true to call our optional lang file, it will trow some errors, better use more to to evoid so.
		//$lang->load('ougc_awards_extra_vals', true, true);
		$lang_file = $lang->path.'/'.str_replace('/admin', '', $lang->language).'/ougc_awards_extra_vals.lang.php';
		if(file_exists($lang_file))
		{
			require_once $lang_file;
			if(is_array($l))
			{
				foreach($l as $key => $val)
				{
					if((empty($lang->$key) || $lang->$key != $val) && !in_array($key, array('language', 'path', 'settings')))
					{
						$lang->$key = $val;
					}
				}
			}
		}
	}

	$lang->set_path(MYBB_ROOT."inc/languages");
}

// I liked as I did the pm thing, so what about award name, description, and reasons?
function ougc_awards_get_award_info($type, $aid, $text, $gid=0)
{
	global $lang;
	ougc_awards_lang_load();

	$aid = intval($aid);
	$gid = intval($gid);

	// Lets figure out our lang variable first.
	if($type == 'reason')
	{
		$lang_val = "ougc_awards_award_reason_gived_{$gid}";
		if(!$lang->$lang_val || empty($lang->$lang_val))
		{
			$lang_val = "ougc_awards_award_reason_{$aid}";
		}
		if((!$lang->$lang_val || empty($lang->$lang_val)) && empty($text))
		{
			$lang_val = "ougc_awards_pm_noreason";
		}
	}
	else
	{
		$lang_val = "ougc_awards_award_{$type}_{$aid}";
	}

	// If lang variable exists and it is not empty, use it instead of provided text.
	if($lang->$lang_val && !empty($lang->$lang_val))
	{
		$text = $lang->$lang_val;
	}
	if($type == 'pm' && $lang->ougc_awards_award_pm_all && !empty($lang->ougc_awards_award_pm_all))
	{
		$text = $lang->ougc_awards_award_pm_all;
	}
	$text = htmlspecialchars_uni($text);
	if($type == 'reason')
	{
		$text = ougc_awards_parse_text($text);
	}
	return $text;
}

// Update the awards cache.
function ougc_awards_update_cache()
{
	global $db, $cache;

	// First we need to update all users by award.
	$q1 = $db->simple_select('ougc_awards', 'aid');

	while($award = $db->fetch_array($q1))
	{
		$q2 = $db->simple_select('ougc_awards_users', 'uid', "aid='{$award['aid']}'");
		$users = array();
		while($user = $db->fetch_array($q2))
		{
			$users[] = intval($user['uid']);
		}
		$db->free_result($q2);
		$update = implode(',', $users);
		ougc_awards_update_award($award['aid'], array('users' => $update));
	}
	$db->free_result($q1);
	unset($update);

	// Now lets update all awards cache.
	$q3 = $db->simple_select('ougc_awards', 'aid, name, image, users', "visible='1' AND type!='2'");

	$update = array();
	while($award = $db->fetch_array($q3))
	{
		$update[$award['aid']] = $award;
	}
	$db->free_result($q3);
	$cache->update('ougc_awards', $update);
}

// Delete a award.
function ougc_awards_delete_award($aid)
{
	global $db;

	$aid = intval($aid);

	$db->delete_query('ougc_awards', "aid='{$aid}'");
	$db->delete_query('ougc_awards_users', "aid='{$aid}'");
	ougc_awards_update_cache();
}

// Add a award.
function ougc_awards_add_award($data)
{
	global $db;
	if(!is_array($data))
	{
		$data = array();
	}
	if($data['name'])
	{
		$data['name'] = $db->escape_string($data['name']);
	}
	if($data['description'])
	{
		$data['description'] = $db->escape_string($data['description']);
	}
	if($data['image'])
	{
		$data['image'] = $db->escape_string($data['image']);
	}
	if($data['visible'])
	{
		$data['visible'] = intval($data['visible']);
	}
	if($data['pm'])
	{
		$data['pm'] = $db->escape_string($data['pm']);
	}
	if($data['type'] == 1 || $data['type'] == 2)
	{
		$data['type'] = intval($data['type']);
	}
	$db->insert_query('ougc_awards', $data);
	ougc_awards_update_cache();
}

// Get awarded user data from db.
function ougc_awards_get_awarded($aid, $uid)
{
	global $db;

	$q = $db->simple_select('ougc_awards_users', '*', "aid='".intval($aid)."' AND uid='".intval($uid)."'");
	$data = $db->fetch_array($q);
	$db->free_result($q);
	if($data['gid'])
	{
		return $data;
	}
	return false;
}

// Get a select box with all cached awards.
function ougc_awards_build_selector($aid=0)
{
	global $cache;

	$awards = $cache->read('ougc_awards');
	if(!is_array($awards))
	{
		$awards = array();
	}
	$select_box = "<select name=\"awards_selector\">";
	foreach($awards as $award)
	{
		$selected = '';
		if($award['aid'] == $aid)
		{
			$selected = ' selected="selected"';
		}
		$name = $award['name'];
		$award['name'] = htmlspecialchars_uni($award['name']);
		if(!defined('IN_ADMINCP'))
		{
			$award['name'] = ougc_awards_get_award_info('name', $award['aid'], $name);
		}
		$select_box .= "<option value=\"{$award['aid']}\"{$selected}>".htmlspecialchars_uni($award['name'])."</option>";
	}
	$select_box .= "</select>";
	return $select_box;
}

// This will check current user's groups.
if(!function_exists('ougc_check_groups'))
{
	function ougc_check_groups($groups, $empty=true)
	{
		global $mybb;
		if(empty($groups) && $empty == true)
		{
			return true;
		}
		if(!empty($mybb->user['additionalgroups']))
		{
			$usergroups = explode(',', $mybb->user['additionalgroups']);
		}
		if(!is_array($usergroups))
		{
			$usergroups = array();
		}
		$usergroups[] = $mybb->user['usergroup'];
		$groups = explode(',', $groups);
		foreach($usergroups as $gid)
		{
			if(in_array($gid, $groups))
			{
				return true;
			}
		}
		return false;
	}
}

// Return the image depending in the award image value.
function ougc_awards_get_icon($img)
{
	global $mybb;

	// Default image.
	$image = $mybb->settings['bburl'].'/images/ougc_awards/default.png';

	// The image is suppose to be external.
	if(my_strpos($img, "ttp:/")) 
	{
		$image = $img;
	}

	// The image is suppose to be internal inside our images folder.
	if(!my_strpos($img, "/") && !empty($img) && file_exists(MYBB_ROOT.'/images/ougc_awards/'.$img)) 
	{
		$image = $mybb->settings['bburl'].'/images/ougc_awards/'.htmlspecialchars_uni($img);
	}

	// Image is suppose to be internal.
	if(!empty($img) && file_exists(MYBB_ROOT.'/'.$img))
	{
		$image = $mybb->settings['bburl'].'/'.htmlspecialchars_uni($img);
	}
	return $image;
}

// Save us time when inserting our settings.
function ougc_awards_add_setting($name, $type, $value, $order, $gid)
{
	global $lang, $db;
	ougc_awards_lang_load();
	
	$lang_val = 'ougc_awards_s_'.$name;
	$lang_val_d = $lang_val.'_d';

	$db->insert_query('settings',
		array(
			'name'			=>	$db->escape_string('ougc_awards_'.$name),
			'title'			=>	$db->escape_string($lang->$lang_val),
			'description'	=>	$db->escape_string($lang->$lang_val_d),
			'optionscode'	=>	$db->escape_string($type),
			'value'			=>	$db->escape_string($value),
			'disporder'		=>	intval($order),
			'gid'			=>	intval($gid)
		)
	);
}

// Save us time when inserting our templates.
function ougc_awards_add_template($name, $content, $version)
{
	global $db;

	$db->insert_query('templates', 
		array(
			'title'		=>	$db->escape_string($name),
	//		'title'		=>	$db->escape_string('ougc_awards_'.$name),
			'template'	=>	$db->escape_string($content),
			'version'	=>	intval($version),
			'dateline'	=>	TIME_NOW,
			'sid'		=>	-2
		)
	);
}
?>