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
	$plugins->add_hook('admin_user_menu', create_function('&$args', 'global $lang, $awards;	$awards->lang_load();	$args[] = array(\'id\' => \'ougc_awards\', \'title\' => $lang->ougc_awards_acp_nav, \'link\' => \'index.php?module=user-ougc_awards\');'));
	$plugins->add_hook('admin_user_action_handler', create_function('&$args', '$args[\'ougc_awards\'] = array(\'active\' => \'ougc_awards\', \'file\' => \'ougc_awards.php\');'));
	$plugins->add_hook('admin_user_permissions', create_function('&$args', 'global $lang, $awards;	$awards->lang_load();	$args[\'ougc_awards\'] = $lang->ougc_awards_acp_permissions;'));

	// Language support
	$plugins->add_hook('admin_config_settings_start', array('OUGC_Awards', 'lang_load'));
	$plugins->add_hook('admin_style_templates_set', array('OUGC_Awards', 'lang_load'));
	$plugins->add_hook('admin_config_settings_change', 'ougc_awards_settings_change');
	$plugins->add_hook('admin_config_plugins_begin', array('OUGC_Awards', 'run_importer'));
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
		case 'private.php':
		case 'announcements.php':
			$plugins->add_hook('postbit_prev', 'ougc_awards_postbit');
			$plugins->add_hook('postbit', 'ougc_awards_postbit');
			$plugins->add_hook('postbit_pm', 'ougc_awards_postbit');
			$plugins->add_hook('postbit_announcement', 'ougc_awards_postbit');
			$templatelist .= 'ougcawards_postbit';
			break;
		case 'member.php':
			global $mybb;

			if($mybb->input['action'] == 'profile')
			{
				$plugins->add_hook('member_profile_end', 'ougc_awards_profile');
				$templatelist .= 'ougcawards_profile_row, ougcawards_profile, ougcawards_profile_multipage, multipage_prevpage, multipage_page, multipage_page_current, multipage_nextpage, multipage';
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
		'description'	=> $lang->setting_group_ougc_awards_desc.(ougc_awards_is_installed() ? $lang->ougc_awards_import_desc : ''),
		'website'		=> 'http://mods.mybb.com/view/ougc-awards',
		'author'		=> 'Omar G.',
		'authorsite'	=> 'http://omarg.me',
		'version'		=> '1.8.3',
		'versioncode'	=> 1803,
		'compatibility'	=> '18*',
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
		'modgroups'	=> array(
		   'title'			=> $lang->setting_ougc_awards_modgroups,
		   'description'	=> $lang->setting_ougc_awards_modgroups_desc,
		   'optionscode'	=> 'groupselect',
			'value'			=>	'3,4,6',
		),
		'pagegroups'	=> array(
		   'title'			=> $lang->setting_ougc_awards_pagegroups,
		   'description'	=> $lang->setting_ougc_awards_pagegroups_desc,
		   'optionscode'	=> 'groupselect',
			'value'			=>	'2,3,4,5,6',
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
		/*'myalerts'	=> array(
		   'title'			=> $lang->setting_ougc_awards_myalerts,
		   'description'	=> $lang->setting_ougc_awards_myalerts_desc,
		   'optionscode'	=> 'yesno',
			'value'			=>	0,
		)*/
	));

	// Add template group
	$PL->templates('ougcawards', '<lang:setting_group_ougc_awards>', array(
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
<link rel="stylesheet" href="{$mybb->asset_url}/jscripts/select2/select2.css">
<script type="text/javascript" src="{$mybb->asset_url}/jscripts/select2/select2.min.js"></script>
<script type="text/javascript">
<!--
if(use_xmlhttprequest == "1")
{
	MyBB.select2();
	$("#username").select2({
		placeholder: "{$lang->search_user}",
		minimumInputLength: 3,
		maximumSelectionSize: 3,
		multiple: false,
		ajax: { // instead of writing the function to execute the request we use Select2\'s convenient helper
			url: "xmlhttp.php?action=get_users",
			dataType: \'json\',
			data: function (term, page) {
				return {
					query: term, // search term
				};
			},
			results: function (data, page) { // parse the results into the format expected by Select2.
				// since we are using custom formatting functions we do not need to alter remote JSON data
				return {results: data};
			}
		},
		initSelection: function(element, callback) {
			var value = $(element).val();
			if (value !== "") {
				callback({
					id: value,
					text: value
				});
			}
		},
       // Allow the user entered text to be selected as well
       createSearchChoice:function(term, data) {
			if ( $(data).filter( function() {
				return this.text.localeCompare(term)===0;
			}).length===0) {
				return {id:term, text:term};
			}
		},
	});
}
// -->
</script>',
		'modcp_nav'						=> '<tr><td class="trow1 smalltext"><a href="modcp.php?action=awards" class="modcp_nav_item" style="background: url(\'images/modcp/awards.png\') no-repeat left center;">{$lang->ougc_awards_modcp_nav}</a></td></tr>',
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
	{$awardlist}
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
		'profile'	=> '<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder tfixed">
	<tr>
		<td class="thead"><strong>{$lang->ougc_awards_profile_title}</strong></td>
	</tr>
	{$awardlist}
	{$multipage}
</table>
<br />',
		'profile_row'	=> '<tr>
	<td class="{$trow}">
		<span class="float_right smalltext">{$award[\'date\']}</span> {$award[\'name\']}
	</td>
</tr><tr>
	<td class="{$trow}" style="vertical-align: middle;">
		<a href="{$mybb->settings[\'bburl\']}/awards.php?view={$award[\'aid\']}" title="{$award[\'name\']}"><img src="{$award[\'image\']}" alt="{$award[\'name\']}" /></a> {$award[\'reason\']}
	</td>
</tr>',
		'profile_row_empty'	=> '<tr>
	<td class="trow1">
		{$lang->ougc_awards_profile_empty}
	</td>
</tr>',
		'page'		=> '<html>
	<head>
		<title>{$mybb->settings[\'bbname\']} - {$lang->ougc_awards_page_title}</title>
		{$headerinclude}
	</head>
	<body>
		{$header}
		{$content}
		{$multipage}
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
	find_replace_templatesets('modcp_nav', '#'.preg_quote('{$modcp_nav_users}').'#', '{$modcp_nav_users}<!--OUGC_AWARDS-->');

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
	if($plugins['awards'] <= 1800)
	{
		global $db;

		$tmpls = array(
			'modcp_ougc_awards'						=> 'ougcawards_modcp',
			'modcp_ougc_awards_manage'				=> 'ougcawards_modcp_manage',
			'modcp_ougc_awards_nav'					=> 'ougcawards_modcp_nav',
			'modcp_ougc_awards_list'				=> 'ougcawards_modcp_list',
			'modcp_ougc_awards_list_empty'			=> 'ougcawards_modcp_list_empty',
			'modcp_ougc_awards_list_award'			=> 'ougcawards_modcp_list_award',
			'modcp_ougc_awards_manage_reason'		=> 'ougcawards_modcp_manage_reason',
			'postbit_ougc_awards'					=> 'ougcawards_postbit',
			'member_profile_ougc_awards_row_empty'	=> 'ougcawards_profile_row_empty',
			'member_profile_ougc_awards_row'		=> 'ougcawards_profile_row',
			'member_profile_ougc_awards'			=> 'ougcawards_profile',
			'ougc_awards_page'						=> 'ougcawards_page',
			'ougc_awards_page_list'					=> 'ougcawards_page_list',
			'ougc_awards_page_list_award'			=> 'ougcawards_page_list_award',
			'ougc_awards_page_list_empty'			=> 'ougcawards_page_list_empty',
			'ougc_awards_page_user'					=> 'ougcawards_page_user',
			'ougc_awards_page_user_award'			=> 'ougcawards_page_user_award',
			'ougc_awards_page_user_empty'			=> 'ougcawards_page_user_empty',
			'ougc_awards_page_view'					=> 'ougcawards_page_view',
			'ougc_awards_page_view_empty'			=> 'ougcawards_page_view_empty',
			'ougc_awards_page_view_row'				=> 'ougcawards_page_view_row',
		);

		// Try to update old templates
		$query = $db->simple_select('templates', '*', $where);
		while($tmpl = $db->fetch_array($query))
		{
			check_template($tmpl['template']) or $tmplcache[$tmpl['title']] = $tmpl;
		}

		foreach($tmpls as $oldtitle => $newtitle)
		{
			$db->update_query('templates', 'title=\''.$db->escape_string($oldtitle).'\' AND sid=\'-2\'', array(
				'title'		=> $db->escape_string($newtitle),
				'version'	=> 1,
				'dateline'	=> TIME_NOW
			));
		}

		// Rebuild templates
		static $done = false;
		if(!$done)
		{
			$done = true;
			$funct = __FUNCTION__;
			$funct();
		}

		// Delete old templates if not updated
		$tmpls['ougc_awards_image'] = '';
		$db->delete_query('templates', 'title IN(\''.implode('\', \'', array_keys(array_map(array($db, 'escape_string'), $tmpls))).'\') AND sid=\'-2\'');

		// Modify table colunms
		$db->modify_column('ougc_awards', 'aid', 'int UNSIGNED NOT NULL AUTO_INCREMENT');
		$db->modify_column('ougc_awards', 'pm', 'text NOT NULL');

		!$db->field_exists('users', 'ougc_awards') or $db->drop_column('ougc_awards', 'users');

		if(!$db->field_exists('disporder', 'ougc_awards'))
		{
			$db->add_column('ougc_awards', 'disporder', 'smallint(5) NOT NULL DEFAULT \'0\'');
		}

		$db->modify_column('ougc_awards_users', 'gid', 'int UNSIGNED NOT NULL AUTO_INCREMENT');
		$db->modify_column('ougc_awards_users', 'uid', 'int NOT NULL DEFAULT \'0\'');
		$db->modify_column('ougc_awards_users', 'aid', 'int NOT NULL DEFAULT \'0\'');
		$db->modify_column('ougc_awards_users', 'reason', 'text NOT NULL');

		if(!$db->index_exists('ougc_awards_users', 'uidaid'))
		{
			$db->write_query('ALTER TABLE '.TABLE_PREFIX.'ougc_awards_users ADD UNIQUE KEY uidaid (uid,aid)');
		}
		if(!$db->index_exists('ougc_awards_users', 'aiduid'))
		{
			$db->write_query('CREATE INDEX aiduid ON '.TABLE_PREFIX.'ougc_awards_users (aid,uid)');
		}


		// Delete old template group
		$db->delete_query('templategroups', 'prefix=\'ougc_awards\'');

		// Now we need to refresh the cache.
		$awards->update_cache();
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
			`disporder` smallint(5) NOT NULL DEFAULT '0',
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
	$db->field_exists('ougc_awards', 'users') or $db->add_column('users', 'ougc_awards', 'text NOT NULL');

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

	!$db->field_exists('ougc_awards', 'users') or $db->drop_column('users', 'ougc_awards');

	$PL->settings_delete('ougc_awards');
	$PL->templates_delete('ougcawards');

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

// Language support for settings
function ougc_awards_settings_change()
{
	global $db, $mybb;

	$query = $db->simple_select('settinggroups', 'name', 'gid=\''.(int)$mybb->input['gid'].'\'');
	$groupname = $db->fetch_field($query, 'name');
	if($groupname == 'ougc_awards')
	{
		global $plugins, $awards;
		$awards->lang_load();
	}
}

// ModCP Part
function ougc_awards_modcp()
{
	global $mybb, $modcp_nav, $templates, $lang, $awards;

	$permission = (bool)($mybb->settings['ougc_awards_modcp'] && ($mybb->settings['ougc_awards_modgroups'] == -1 || ($mybb->settings['ougc_awards_modgroups'] && $awards->check_groups($mybb->settings['ougc_awards_modgroups'], false))));

	if($permission)
	{
		$awards->lang_load();

		eval('$awards_nav = "'.$templates->get('ougcawards_modcp_nav').'";');
		$modcp_nav = str_replace('<!--OUGC_AWARDS-->', $awards_nav, $modcp_nav);
	}

	if($mybb->input['action'] != 'awards')
	{
		return;
	}

	$permission or error_no_permission();

	$awards->lang_load();

	global $headerinclude, $header, $theme, $footer, $db;

	add_breadcrumb($lang->ougc_awards_modcp_nav, $awards->build_url());
	$error = array();
	$errors = '';

	// We can give awards from the ModCP
	if($mybb->input['manage'] == 'give')
	{
		if(!($award = $awards->get_award($mybb->input['aid'])))
		{
			error($lang->ougc_awards_error_wrongaward);
		}

		add_breadcrumb(strip_tags($award['name']));
		add_breadcrumb($lang->ougc_awards_modcp_give);

		if(!$award['visible'])
		{
			error($lang->ougc_awards_error_wrongaward);
		}

		if($mybb->request_method == 'post')
		{
			if(!($user = $awards->get_user_by_username($mybb->input['username'])))
			{
				$errors = inline_error($lang->ougc_awards_error_invaliduser);
			}
			elseif($awards->get_gived_award($award['aid'], $user['uid']))
			{
				$errors = inline_error($lang->ougc_awards_error_give);
			}
			elseif(!$awards->can_edit_user($user['uid']))
			{
				$errors = inline_error($lang->ougc_awards_error_giveperm);
			}
			else
			{
				$awards->give_award($award, $user, $mybb->input['reason']);
				$awards->log_action();
				$awards->redirect($lang->ougc_awards_redirect_gived);
			}
		}

		$lang->ougc_awards_modcp_title_give = $lang->sprintf($lang->ougc_awards_modcp_title_give, $awards->get_award_info('name', $award['aid'], $award['name']));

		eval('$reason = "'.$templates->get('ougcawards_modcp_manage_reason').'";');
		eval('$content = "'.$templates->get('ougcawards_modcp_manage').'";');
		eval('$page = "'.$templates->get('ougcawards_modcp').'";');
		output_page($page);
		exit;
	}
	// We can revoke awards from the ModCP
	elseif($mybb->input['manage'] == 'revoke')
	{
		if(!($award = $awards->get_award($mybb->input['aid'])))
		{
			error($lang->ougc_awards_error_wrongaward);
		}

		add_breadcrumb(strip_tags($award['name']));
		add_breadcrumb($lang->ougc_awards_modcp_revoke);

		if(!$award['visible'])
		{
			error($lang->ougc_awards_error_wrongaward);
		}

		if($mybb->request_method == 'post')
		{
			if(!($user = $awards->get_user_by_username($mybb->input['username'])))
			{
				$errors = inline_error($lang->ougc_awards_error_invaliduser);
			}
			elseif(!$awards->get_gived_award($award['aid'], $user['uid']))
			{
				$errors = inline_error($lang->ougc_awards_error_notgive);
			}
			elseif(!$awards->can_edit_user($user['uid']))
			{
				$errors = inline_error($lang->ougc_awards_error_giveperm);
			}
			else
			{
				$awards->revoke_award($award['aid'], $user['uid']);
				$awards->log_action();
				$awards->redirect($lang->ougc_awards_redirect_revoked);
			}
		}

		$lang->ougc_awards_modcp_title_give = $lang->sprintf($lang->ougc_awards_modcp_title_give, $awards->get_award_info('name', $award['aid'], $award['name']));

		$lang->ougc_awards_modcp_give = $lang->ougc_awards_modcp_revoke;
		eval('$content = "'.$templates->get('ougcawards_modcp_manage').'";');
		eval('$page = "'.$templates->get('ougcawards_modcp').'";');
		output_page($page);
		exit;
	}
	else
	{
		$limit = (int)$mybb->settings['ougc_awards_perpage'];
		$limit = $limit > 100 ? 100 : ($limit < 1 ? 1 : $limit);

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

		$awardlist = $multipage = '';
		$query = $db->simple_select('ougc_awards', '*', 'visible=\'1\'', array('limit_start' => $start, 'limit' => $limit));
		if(!$db->num_rows($query))
		{
			eval('$awardlist = "'.$templates->get('ougcawards_modcp_list_empty').'";');
		}
		else
		{
			while($award = $db->fetch_array($query))
			{
				$trow = alt_trow();

				$award['aid'] = (int)$award['aid'];
				$award['image'] = $awards->get_award_icon($award['aid']);
				if($name = $awards->get_award_info('name', $award['aid']))
				{
					$award['name'] = $name;
				}
				if($description = $awards->get_award_info('description', $award['aid']))
				{
					$award['description'] = $description;
				}

				eval('$awardlist .= "'.$templates->get('ougcawards_modcp_list_award').'";');
			}

			$query = $db->simple_select('ougc_awards', 'COUNT(aid) AS awards', $where);
			$awardscount = (int)$db->fetch_field($query, 'awards');

			
			$multipage = multipage($awardscount, $limit, $mybb->input['page'], $awards->build_url());
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
	global $mybb, $memprofile, $templates;

	$memprofile['ougc_awards'] = '';

	$limit = (int)$mybb->settings['ougc_awards_profile'];
	$limit = $limit > 100 ? 100 : ($limit < 1 && $limit != -1 ? 1 : $limit);

	if(($limit < 0 && $limit != -1) || my_strpos($templates->cache['member_profile'], '{$memprofile[\'ougc_awards\']}') === false)
	{
		return;
	}

	global $db, $lang, $theme, $templates, $awards;
	$awards->lang_load();

	$awards->set_url(null, get_profile_link($memprofile['uid']));

	// Query our data.
	if($limit == -1)
	{
		// Get awards
		$query = $db->query('
			SELECT u.*, a.*
			FROM '.TABLE_PREFIX.'ougc_awards_users u
			LEFT JOIN '.TABLE_PREFIX.'ougc_awards a ON (u.aid=a.aid)
			WHERE u.uid=\''.(int)$memprofile['uid'].'\' AND a.visible=\'1\'
			ORDER BY u.date desc'
		);
	}
	else
	{
		// First we need to figure out the total amount of awards.
		$query = $db->query('
			SELECT COUNT(au.aid) AS awards
			FROM '.TABLE_PREFIX.'ougc_awards_users au
			LEFT JOIN '.TABLE_PREFIX.'ougc_awards a ON (au.aid=a.aid)
			WHERE au.uid=\''.(int)$memprofile['uid'].'\' AND a.visible=\'1\'
			ORDER BY au.date desc
		');
		$awardscount = (int)$db->fetch_field($query, 'awards');

		$page = (string)$mybb->input['view'] == 'awards' ? (int)$mybb->input['page'] : 0;
		if($page > 0)
		{
			$start = ($page - 1)*$limit;
			if($page > ceil($awardscount/$limit))
			{
				$start = 0;
				$page = 1;
			}
		}
		else
		{
			$start = 0;
			$page = 1;
		}
		// We want to keep $mybb->input['view'] intact for other plugins, ;)

		$multipage = (string)multipage($awardscount, $limit, $page, $awards->build_url('view=awards'));
		eval('$multipage = "'.$templates->get('ougcawards_profile_multipage').'";');

		$query = $db->query('
			SELECT au.*, a.*
			FROM '.TABLE_PREFIX.'ougc_awards_users au
			LEFT JOIN '.TABLE_PREFIX.'ougc_awards a ON (au.aid=a.aid)
			WHERE au.uid=\''.(int)$memprofile['uid'].'\' AND a.visible=\'1\'
			ORDER BY au.date desc
			LIMIT '.$start.', '.$limit
		);
	}

	// Output our awards.
	if(!$db->num_rows($query))
	{
		eval('$awardlist = "'.$templates->get('ougcawards_profile_row_empty').'";');
	}
	else
	{
		$awardlist = '';
		while($award = $db->fetch_array($query))
		{
			$trow = alt_trow();

			if($name = $awards->get_award_info('name', $award['aid']))
			{
				$award['name'] = $name;
			}
			if($description = $awards->get_award_info('description', $award['aid']))
			{
				$award['description'] = $description;
			}
			if($reason = $awards->get_award_info('reason', $award['aid'], $award['gid']))
			{
				$award['reason'] = $reason;
			}

			if(empty($award['reason']))
			{
				$award['reason'] = $lang->ougc_awards_pm_noreason;
			}

			$awards->parse_text($award['reason']);

			$award['image'] = $awards->get_award_icon($award['aid']);

			$award['date'] = $lang->sprintf($lang->ougc_awards_profile_tine, my_date($mybb->settings['dateformat'], $award['date']), my_date($mybb->settings['timeformat'], $award['date']));

			eval('$awardlist .= "'.$templates->get('ougcawards_profile_row').'";');
		}
	}

	$lang->ougc_awards_profile_title = $lang->sprintf($lang->ougc_awards_profile_title, htmlspecialchars_uni($memprofile['username']));

	eval('$memprofile[\'ougc_awards\'] = "'.$templates->get('ougcawards_profile').'";');
}

// Show awards in profile function.
function ougc_awards_postbit(&$post)
{
	global $settings, $plugins, $mybb;

	$post['ougc_awards'] = '';

	$spl = explode('/', $settings['ougc_awards_postbit']);
	$max_postbit = (int)$spl[0];
	$spl = (int)$spl[1];

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
	$awardlist = &$ougc_awards_cache[$post['uid']];

	global $templates, $awards;

	$count = 0;

	// Format the awards
	foreach($awardlist as $award)
	{
		$award['aid'] = (int)$award['aid'];
		if($name = $awards->get_award_info('name', $award['aid']))
		{
			$award['name'] = $name;
		}
		$award['name_ori'] = $award['name'];
		$award['name'] = strip_tags($award['name_ori']);

		$award['image'] = $awards->get_award_icon($award['aid']);

		if($max_postbit == -1 || $count < $max_postbit)
		{
			$count++;
			$br = '';
			if($count == 1 || ($spl && !($count % $spl == 0)))
			{
				$br = '<br />'; // We insert a break if it is the first award.
			}

			eval('$new_award = "'.$templates->get('ougcawards_postbit', 1, 0).'";');
			$post['ougc_awards'] .= trim($new_award);
		}
	}

	$post['user_details'] = str_replace('<!--OUGC_AWARDS-->', $post['ougc_awards'], $post['user_details']);
}

// Cache manager helper.
function update_ougc_awards()
{
	global $awards;

	$awards->update_cache();
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

	// The language we are using.
	private $language;

	// Build the class
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

		if(!empty($settings['ougc_awards_myalerts']))
		{
			$settings['myalerts_alert_ougc_awards'] = 1;
		}
	}

	// Loads language strings
	function lang_load($extra=false)
	{
		global $lang;

		if(isset($lang->setting_group_ougc_awards))
		{
			return;
		}

		$lang->load((defined('IN_ADMINCP') ? 'user_' : '').'ougc_awards');

		// MyAlerts, ugly bitch
		if(isset($lang->ougc_awards_myalerts_setting))
		{
			$lang->myalerts_setting_ougc_awards = $lang->ougc_awards_myalerts_setting;
		}

		if($extra)
		{
			isset($this->language) or $this->language = $lang->language;
			$lang->load('ougc_awards_extra_vals', true, true);
			$lang->language = $this->language; // Bug in MyBB 1.6.12 and lower (probably 1.6.13 too).
		}
	}

	// $PL->is_member(); helper
	function is_member($gids, $user=false)
	{
		global $PL;
		$PL or require_once PLUGINLIBRARY;

		return (bool)$PL->is_member((string)$gids, $user);
	}

	// Update the cache
	function update_cache()
	{
		global $db, $cache;

		$d = array();
		$query = $db->simple_select('ougc_awards', 'aid, name, description, image, pm, type', 'visible=\'1\'', array('order_by' => 'disporder'));
		while($award = $db->fetch_array($query))
		{
			$d[$award['aid']] = array(
				'name'	=> $award['name'],
				'image'	=> $award['image'],
				'type'	=> (int)$award['type'],
			);
		}
		#_dump(1, $d);

		$cache->update('ougc_awards', $d);

		return true;
	}

	// Clean input
	function clean_ints($val, $implode=false)
	{
		if(!is_array($val))
		{
			$val = (array)explode(',', $val);
		}

		foreach($val as $k => &$v)
		{
			$v = (int)$v;
		}

		$val = array_filter($val);

		if($implode)
		{
			$val = (string)implode(',', $val);
		}

		return $val;
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
			global $cache, $settings, $theme;
			$awards = (array)$cache->read('ougc_awards');
			$award = $awards[$aid];

			$award or $award = $this->get_award($aid);

			$replaces = array(
				'{bburl}'	=> $settings['bburl'],
				'{homeurl}'	=> $settings['homeurl'],
				'{imgdir}'	=> $theme['imgdir']
			);

			$this->cache['images'][$aid] = str_replace(array_keys($replaces), array_values($replaces), $award['image']);
		}

		return $this->cache['images'][$aid];
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

	// Insert a new rate to the DB
	function insert_award($data, $aid=null, $update=false)
	{
		global $db;

		$cleandata = array();

		!isset($data['name']) or $cleandata['name'] = $db->escape_string($data['name']);
		!isset($data['description']) or $cleandata['description'] = $db->escape_string($data['description']);
		!isset($data['image']) or $cleandata['image'] = $db->escape_string($data['image']);
		!isset($data['disporder']) or $cleandata['disporder'] = (int)$data['disporder'];
		!isset($data['pm']) or $cleandata['pm'] = $db->escape_string($data['pm']);
		!isset($data['visible']) or $cleandata['visible'] = (int)$data['visible'];
		!isset($data['type']) or $cleandata['type'] = (int)$data['type'];

		if($update)
		{
			$this->aid = (int)$aid;
			$db->update_query('ougc_awards', $cleandata, 'aid=\''.$this->aid.'\'');
		}
		else
		{
			$this->aid = (int)$db->insert_query('ougc_awards', $cleandata);
		}

		return true;
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

		$where = 'LOWER(username)=\''.$db->escape_string(my_strtolower($username)).'\'';
		if(my_strpos($username, 'uid:') === 0)
		{
			$uid = explode('uid:', $username);
			$where = 'uid=\''.(int)$uid[1].'\'';
		}

		$query = $db->simple_select('users', 'uid, username', $where, array('limit' => 1));
		if($user = $db->fetch_array($query))
		{
			return array('uid' => (int)$user['uid'], 'username' => (string)$user['username']);
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
			'date'		=> isset($award['TIME_NOW']) ? (int)$award['TIME_NOW'] : TIME_NOW
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
		$this->lang_load(true);

		$this->send_pm(array(
			'subject'		=> $lang->sprintf($lang->ougc_awards_pm_title, strip_tags($award['name'])),
			'message'		=> $lang->sprintf($award['pm'], $user['username'], $award['name'], (!empty($reason) ? $reason : $lang->ougc_awards_pm_noreason), $this->get_award_icon($award['aid']), $mybb->settings['bbname']),
			'touid'			=> $user['uid']
		), -1, true);
	}

	// I liked as I did the pm thing, so what about award name, description, and reasons?
	function get_award_info($type, $aid, $gid=0)
	{
		global $lang;
		$this->lang_load(true);
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

		$cleandata = array();
		!isset($data['date']) or $cleandata['date'] = (int)$data['date'];
		!isset($data['reason']) or $cleandata['reason'] = $db->escape_string($data['reason']);

		$args = array(
			'gid'			=> &$this->gid,
			'data'			=> &$data,
			'clean_data'		=> &$cleandata,
		);

		$plugins->run_hooks('ougc_awards_update_gived', $args);

		$db->update_query('ougc_awards_users', $cleandata, 'gid=\''.$this->gid.'\'');

		return true;
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
		$uid = (int)$uid;

		if(is_super_admin($mybb->user['uid']))
		{
			return true;
		}

		if(!is_super_admin($uid))
		{
			return true;
		}

		if($mybb->usergroup['cancp'])
		{
			return true;
		}

		$userperms = user_permissions($uid);
		if(!$userperms['cancp'])
		{
			return true;
		}

		if(!defined('IN_ADMINCP'))
		{
			if($mybb->usergroup['issupermod'])
			{
				return true;
			}

			if(!$userperms['issupermod'])
			{
				return true;
			}

			if($mybb->user['ismoderator'])
			{
				return true;
			}

			if(!is_moderator(0, '', $uid))
			{
				return true;
			}

			if($mybb->user['uid'] != $uid)
			{
				return true;
			}
		}

		return false;
	}

	// Send a Private Message to a user  (Copied from MyBB 1.7)
	function send_pm($pm, $fromid=0, $admin_override=false)
	{
		global $mybb;

		if(!$mybb->settings['ougc_awards_sendpm'] || !$mybb->settings['enablepms'] || !is_array($pm))
		{
			return false;
		}

		if(!$pm['subject'] || !$pm['message'] || (!$pm['receivepms'] && !$admin_override))
		{
			return false;
		}

		global $lang, $db, $session;
		$lang->load((defined('IN_ADMINCP') ? '../' : '').'messages');

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

	// Importer
	function run_importer()
	{
		global $mybb;

		if(!($type = $mybb->get_input('ougc_awards_import')))
		{
			return;
		}

		switch($type)
		{
			#case 'mybbcentral';
			default;
				$tables = array('awards' => 'myawards', 'users' => 'myawards_users');
				$keys = array('name' => 'awname', 'description' => 'awdescr', 'image' => 'awimg', 'original_id' => 'awid', 'uid' => 'awuid', 'reason' => 'awreason', 'TIME_NOW' => 'awutime');
				$img_prefix = '{bburl}/uploads/awards/';
				$lang_var = 'ougc_awards_import_confirm_mybbcentral';
				break;
		}

		global $lang, $awards, $page;
		$awards->lang_load();

		if($mybb->request_method == 'post')
		{
			if(!verify_post_check($mybb->input['my_post_key']))
			{
				flash_message($lang->invalid_post_verify_key2, 'error');
				admin_redirect("index.php?module=config-plugins");
			}

			if(isset($mybb->input['no']))
			{
				return true;
			}

			global $db;

			$query = $db->simple_select('ougc_awards', 'MAX(disporder) AS max_disporder');
			$disporder = (int)$db->fetch_field($query, 'max_disporder');

			$query = $db->simple_select($tables['awards']);
			while($award = $db->fetch_array($query))
			{
				$insert_award = array(
					'name'			=> $award[$keys['name']],
					'description'	=> $award[$keys['description']],
					'image'			=> $img_prefix.$award[$keys['image']],
					'disporder'		=> ++$disporder,
					'pm'			=> ''
				);

				$awards->insert_award($insert_award);

				$insert_award['aid'] = $awards->aid;
				$insert_award[$keys['original_id']] = $award[$keys['original_id']];

				$cache_awards[$award[$keys['original_id']]] = $insert_award;
			}

			$mybb->settings['ougc_awards_sendpm'] = $mybb->settings['enablepms'] = false;

			$query = $db->simple_select($tables['users']);
			while($award = $db->fetch_array($query))
			{
				$insert_award = array(
					'aid'			=> $cache_awards[$award[$keys['original_id']]]['aid'],
					'uid'			=> $award[$keys['uid']],
					'reason'		=> $award[$keys['reason']],
					'TIME_NOW'		=> $award[$keys['TIME_NOW']]
				);

				$awards->give_award($insert_award, array('uid' => $insert_award['uid']), $insert_award['reason']);
			}

			flash_message($lang->ougc_awards_import_end, 'success');
			admin_redirect('index.php?module=config-plugins');
		}

		$page->output_confirm_action("index.php?module=config-plugins&ougc_awards_import={$type}", $lang->{$lang_var}, $lang->ougc_awards_import_title);
	}
}
$GLOBALS['awards'] = new OUGC_Awards;