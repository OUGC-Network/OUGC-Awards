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
	$plugins->add_hook('admin_config_settings_start', create_function('', 'global $awards; $awards->lang_load();'));
	$plugins->add_hook('admin_style_templates_set', create_function('', 'global $awards; $awards->lang_load();'));
	$plugins->add_hook('admin_config_settings_change', 'ougc_awards_settings_change');
	$plugins->add_hook('admin_config_plugins_begin', create_function('', 'global $awards; $awards->run_importer();'));
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

	$templatelist .= 'ougcawards_global_menu,ougcawards_global_notification,';

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

			if((string)$mybb->input['action'] == 'profile')
			{
				$plugins->add_hook('member_profile_end', 'ougc_awards_profile');
				$templatelist .= 'ougcawards_profile_row, ougcawards_profile_row_category, ougcawards_profile, ougcawards_profile_multipage, multipage_prevpage, multipage_page, multipage_page_current, multipage_nextpage, multipage';
			}
			break;
		case 'modcp.php':
			global $mybb;

			$plugins->add_hook('modcp_start', 'ougc_awards_modcp');
			$templatelist .= 'ougcawards_modcp_nav';
			if((string)$mybb->input['action'] == 'awards')
			{
				$templatelist .= ', ougcawards_modcp_list_award, ougcawards_modcp_list, ougcawards_modcp, ougcawards_modcp_manage_reason, ougcawards_modcp_manage, ougcawards_modcp_manage_username';
			}
			break;
	}

	$plugins->add_hook('stats_start', 'ougc_awards_stats_start');
	$plugins->add_hook('stats_end', 'ougc_awards_stats_end');
	$plugins->add_hook('global_intermediate', 'ougc_awards_global_intermediate');
	$plugins->add_hook('datahandler_user_insert', 'ougc_awards_datahandler_user_insert');
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
		'version'		=> '1.8.7',
		'versioncode'	=> 1807,
		'compatibility'	=> '16*,18*',
		'myalerts'		=> 105,
		'pl'			=> array(
			'version'	=> 12,
			'url'		=> 'https://community.mybb.com/mods.php?action=view&pid=573'
		)
	);
}

// _activate() routine
function ougc_awards_activate()
{
	global $PL, $lang, $mybb, $awards, $db;
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
		   'optionscode'	=> ($mybb->version_code >= 1800 ? 'groupselect' : 'text'),
			'value'			=>	'3,4,6',
		),
		'pagegroups'	=> array(
		   'title'			=> $lang->setting_ougc_awards_pagegroups,
		   'description'	=> $lang->setting_ougc_awards_pagegroups_desc,
		   'optionscode'	=> ($mybb->version_code >= 1800 ? 'groupselect' : 'text'),
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
		'enablestatspage'	=> array(
		   'title'			=> $lang->setting_ougc_awards_enablestatspage,
		   'description'	=> $lang->setting_ougc_awards_enablestatspage_desc,
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
		'global_menu'			=> '<li><a href="{$mybb->settings[\'bburl\']}/awards.php" class="portal">{$lang->ougc_awards_global_menu}</a></li>',
		'js'				=> '<script type="text/javascript" src="{$mybb->asset_url}/jscripts/ougc_awards.js"></script>',
		'ougcawards_global_menu'			=> '<div class="pm_alert">
	<div>{$message}</div>
</div>
<br />',
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
	{$username}
	{$reason}
	{$gived_list}
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
		'modcp_manage_gived_list'		=> '<tr>
	<td class="trow2" width="25%"><strong>{$lang->ougc_awards_modcp_gived}:</strong></td>
	<td class="trow2" width="75%">{$select}{$username}</td>
</tr>',
		'modcp_manage_username'		=> '<tr>
	<td class="trow1" width="25%"><strong>{$lang->ougc_awards_modcp_username}:</strong></td>
	<td class="trow1" width="75%"><input type="text" class="textbox" name="username" id="username" value="{$mybb->input[\'username\']}" size="25" /></td>
</tr>',
		'modcp_manage_username_hidden'		=> '<input type="hidden" name="username" value="{$mybb->input[\'username\']}" />',
		'profile_multipage'		=> '{$multipage}',
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
		'profile'	=> ($mybb->version_code >= 1800 ? '' : '<br />
').'<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder tfixed">
	<tr>
		<td class="thead"><strong>{$lang->ougc_awards_profile_title}</strong></td>
	</tr>
	{$awardlist}
	{$multipage}
</table>'.($mybb->version_code >= 1800 ? '
<br />' : ''),
		'profile_row'	=> '<tr>
	<td class="{$trow}">
		<span class="float_right smalltext">{$award[\'date\']}</span> {$award[\'name\']}
	</td>
</tr><tr>
	<td class="{$trow}" style="vertical-align: middle;">
		<a href="{$mybb->settings[\'bburl\']}/awards.php?view={$award[\'aid\']}" title="{$award[\'name\']}"><img src="{$award[\'image\']}" alt="{$award[\'name\']}" /></a> {$award[\'reason\']}
	</td>
</tr>',
		'profile_row_category'	=> '<tr>
	<td class="tcat">
		{$category[\'name\']}
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
		{$jscriptfile}
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
		<td class="thead" colspan="{$colspan_thead}">
			<strong>{$category[\'name\']}</strong>
		</td>
	</tr>
	<tr>
		<td class="tcat smalltext" align="center" width="1%"><strong>{$lang->ougc_awards_page_list_award}</strong></td>
		<td class="tcat smalltext" width="15%"><strong>{$lang->ougc_awards_page_list_name}</strong></td>
		<td class="tcat smalltext"><strong>{$lang->ougc_awards_page_list_description}</strong></td>
		{$request}
	</tr>
	{$award_list}
</table>
<br />',
		'page_list_request'	=> '<td class="tcat smalltext" width="15%"><strong>{$lang->ougc_awards_page_list_request}</strong></td>',
		'page_list_award'	=> '<tr>
	<td class="{$trow}" align="center"><a href="{$mybb->settings[\'bburl\']}/awards.php?view={$award[\'aid\']}" title="{$award[\'name\']}"><img src="{$award[\'image\']}" alt="{$award[\'name\']}" /></a></td>
	<td class="{$trow}"><a href="{$mybb->settings[\'bburl\']}/awards.php?view={$award[\'aid\']}" title="{$award[\'name\']}">{$award[\'name\']}</a></td>
	<td class="{$trow}" colspan="{$colspan_trow}">{$award[\'description\']}</td>
	{$award_request}
</tr>',
		'page_list_award_request'	=> '<td class="{$trow} postbit_buttons post_management_buttons" align="center"><a href="javascript:OUGC_Plugins.RequestAward(\'{$award[\'aid\']}\');" title="{$lang->ougc_awards_page_list_request}" class="postbit_report"><span>{$lang->ougc_awards_page_list_request}</span></a></td>',
		'page_list_empty'	=> '<tr>
	<td class="trow1" colspan="{$colspan_thead}" align="center">
		{$lang->ougc_awards_page_list_empty}
	</td>
</tr>',
		'page_view'	=> '{$pending_requests}
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
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
</table>
{$request_button}',
		'page_view_empty'	=> '<tr>
	<td class="trow1" colspan="3" align="center">{$lang->ougc_awards_page_view_empty}</td>
</tr>',
		'page_view_row'	=> '<tr>
	<td class="{$trow}">{$gived[\'username\']}</td>
	<td class="{$trow}">{$gived[\'reason\']}</td>
	<td class="{$trow}" align="center">{$gived[\'date\']}</td>
</tr>',
		'page_request_error'	=> '<tr>
		<td class="{$trow}">{$error}</td>
</tr>',
		'page_request'	=> '<div class="modal">
	{$modal}
</div>',
		'page_request_modal'	=> '<div style="overflow-y: auto; max-height: 400px;" class="modal_{$award[\'aid\']}">
		<form action="{$mybb->settings[\'bburl\']}/awards.php" method="post" onsubmit="javascript: return Report.submitRequest(\'{$mybb->user[\'uid\']}\', \'{$award[\'aid\']}\');" class="requestdata_{$award[\'aid\']}">
		<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
		<input type="hidden" name="action" value="request" />
		<input type="hidden" name="aid" value="{$award[\'aid\']}" />
		<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
			<tr>
				<td class="thead"><strong>{$lang->ougc_awards_request_title}</strong></td>
			</tr>
			<tr>
				<td class="tcat">{$lang->ougc_awards_request_desc}</td>
			</tr>
			{$content}
			<tr>
				<td class="tfoot" align="center">{$button}</td>
			</tr>
		</table>
	</form>
</div>',
		'page_request_form'	=> '<tr>
	<td class="{$trow}"><strong>{$lang->ougc_awards_request_name}</strong></td>
</tr>
<tr>
		<td class="{$trow}" align="center"><a href="{$mybb->settings[\'bburl\']}/awards.php?view={$award[\'aid\']}" title="{$award[\'name\']}"><img src="{$award[\'image\']}" alt="{$award[\'name\']}" /></a><br />{$award[\'name\']}</td>
</tr>
<tr>
	<td class="{$trow}"><strong>{$lang->ougc_awards_request_message}</strong></td>
</tr>
<tr>
		<td class="{$trow}"><input type="text" class="textbox" name="message" size="40" maxlength="250" /></td>
</tr>',
		'page_request_form_button'	=> '<input type="submit" class="button" value="{$lang->ougc_awards_request_button}" tabindex="2" accesskey="s">',
		'page_view_request'	=> '<br class="clear" />
<div class="float_right"><a href="javascript:OUGC_Plugins.RequestAward(\'{$award[\'aid\']}\');" class="button new_thread_button"><span>{$lang->ougc_awards_page_list_request}</span></a></div>
<br class="clear" />',
		'stats'	=> '<br />
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
	<tr>
		<td class="thead" colspan="3"><strong>{$title}</strong></td>
	</tr>
	<tr>
		<td class="tcat" width="1%">&nbsp;</td>
		<td class="tcat" width="89%">{$lang->ougc_awards_stats_username}</td>
		<td class="tcat" width="10%" align="center">{$lang->ougc_awards_stats_total}</td>
	</tr>
	{$userlist}
</table>',
		'stats_empty'	=> '<tr>
	<td class="trow1" colspan="3"><em>{$lang->ougc_awards_stats_empty}</em></td>
</tr>',
		'stats_user'	=> '<tr>
	<td class="{$trow}" width="1%">{$place}</td>
	<td class="{$trow}" width="89%">{$profilelink_formatted}</td>
	<td class="{$trow}" width="10%" align="center">{$field}</td>
</tr>',
		'stats_user_viewall'	=> '<a href="javascript:OUGC_Plugins.ViewAll(\'{$uid}\');" title="{$lang->ougc_awards_stats_viewall}">{$total}</a>',
		''	=> '',
		''	=> '',
		''	=> '',
		''	=> '',
		''	=> '',
		''	=> '',
		''	=> '',
		''	=> '',
	));

	// Modify templates
	require_once MYBB_ROOT.'/inc/adminfunctions_templates.php';
	find_replace_templatesets('postbit', '#'.preg_quote('{$post[\'user_details\']}').'#', '{$post[\'user_details\']}{$post[\'ougc_awards\']}');
	find_replace_templatesets('postbit_classic', '#'.preg_quote('{$post[\'user_details\']}').'#', '{$post[\'user_details\']}{$post[\'ougc_awards\']}');
	find_replace_templatesets('member_profile', '#'.preg_quote('{$signature}').'#', '{$signature}{$memprofile[\'ougc_awards\']}');
	find_replace_templatesets('modcp_nav', '#'.preg_quote('{$modcp_nav_users}').'#', '{$modcp_nav_users}<!--OUGC_AWARDS-->');
	find_replace_templatesets('modcp_nav', '#'.preg_quote('mcp_nav_editprofile}</a></td></tr>').'#', 'mcp_nav_editprofile}</a></td></tr><!--OUGC_AWARDS-->'); // 1.6
	find_replace_templatesets('header', '#'.preg_quote('{$pm_notice}').'#', '{$pm_notice}{$ougc_awards_requests}');
	find_replace_templatesets('header', '#'.preg_quote('{$menu_portal}').'#', '{$menu_portal}{$ougc_awards_menu}');
	find_replace_templatesets('stats', '#'.preg_quote('{$footer}').'#', '{$ougc_awards_most}{$ougc_awards_last}{$footer}');
	find_replace_templatesets('stats', '#'.preg_quote('{$headerinclude}').'#', '{$headerinclude}{$ougc_awards_js}');

	// Update administrator permissions
	change_admin_permission('tools', 'ougc_awards');

	// Insert/update version into cache
	$plugins = $mybb->cache->read('ougc_plugins');
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
	// TODO here we check that each table exist, if it exists check each field then, if it does not then create it
	$awards->_db_verify_tables();
	$awards->_db_verify_columns();

	if($plugins['awards'] <= 1807)
	{
	}

	if($plugins['awards'] <= 1803)
	{
		$query = $db->simple_select('ougc_awards', 'aid');
		$numawards = $db->num_rows($query);

		if($numawards)
		{
			$awards->insert_category(array(
				'name'			=> 'Default',
				'description'	=> 'Default category created after an update.'
			));

			$db->update_query('ougc_awards', array('cid' => $awards->cid));
		}
	}
	if($plugins['awards'] <= 1800)
	{
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

		// Delete old template group
		$db->delete_query('templategroups', 'prefix=\'ougc_awards\'');

		$awards->_db_verify_indexes();
	}
	if($plugins['awards'] <= 1803)
	{
	}

	$awards->update_cache();
	/*~*~* RUN UPDATES END *~*~*/

	$plugins['awards'] = $info['versioncode'];
	$mybb->cache->update('ougc_plugins', $plugins);
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
	find_replace_templatesets('header', '#'.preg_quote('{$ougc_awards_requests}').'#', '', 0);
	find_replace_templatesets('header', '#'.preg_quote('{$ougc_awards_menu}').'#', '', 0);
	find_replace_templatesets('stats', '#'.preg_quote('{$ougc_awards_most}{$ougc_awards_last}').'#', '', 0);
	find_replace_templatesets('stats', '#'.preg_quote('{$ougc_awards_js}').'#', '', 0);

	// Update administrator permissions
	change_admin_permission('tools', 'ougc_awards', 0);
}

// _install() routine
function ougc_awards_install()
{
	global $db, $awards;

	// Create our table(s)
	$awards->_db_verify_tables();
	$awards->_db_verify_columns();
	$awards->_db_verify_indexes();

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
	global $db, $awards;

	foreach($awards->_db_tables() as $name => $table)
	{
		$installed = $db->table_exists('ougc_awards');
		break;
	}

	return $installed;
}

// _uninstall() routine
function ougc_awards_uninstall()
{
	global $db, $PL, $cache, $awards;
	ougc_awards_pl_check();

	// Drop DB entries
	foreach($awards->_db_tables() as $name => $table)
	{
		$db->drop_table($name);
	}
	foreach($awards->_db_columns() as $table => $columns)
	{
		foreach($columns as $name => $definition)
		{
			!$db->field_exists($name, $table) or $db->drop_column($table, $name);
		}
	}

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
	global $db, $mybb, $awards;

	$query = $db->simple_select('settinggroups', 'name', 'gid=\''.$awards->get_input('gid', 1).'\'');
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

	if($awards->get_input('action') != 'awards')
	{
		return;
	}

	$permission or error_no_permission();

	$awards->lang_load();

	global $headerinclude, $header, $theme, $footer, $db;

	add_breadcrumb($lang->ougc_awards_modcp_nav, $awards->build_url());
	$error = array();
	$errors = '';

	$mybb->input['aid'] = $awards->get_input('aid', 1);
	$mybb->input['username'] = $awards->get_input('username');
	$mybb->input['reason'] = $awards->get_input('reason');

	$_cache = $mybb->cache->read('ougc_awards');

	$where_cids = array();
	foreach($_cache['categories'] as $cid => $category)
	{
		$where_cids[] = (int)$cid;
	}

	$where_cids = implode("','", $where_cids);

	// We can give awards from the ModCP
	if($awards->get_input('manage') == 'give')
	{
		if(!($award = $awards->get_award($awards->get_input('aid', 1))))
		{
			error($lang->ougc_awards_error_wrongaward);
		}

		if(!($category = $awards->get_category($award['cid'])))
		{
			error($lang->ougc_awards_error_invalidcategory);
		}

		add_breadcrumb(strip_tags($award['name']));
		add_breadcrumb($lang->ougc_awards_modcp_give);

		if(!$award['visible'])
		{
			error($lang->ougc_awards_error_wrongaward);
		}

		if(!$category['visible'])
		{
			error($lang->ougc_awards_error_invalidcategory);
		}

		if($mybb->request_method == 'post')
		{
			$users = array();
			if(my_strpos($awards->get_input('username'), 'multiple:') === false)
			{
				$user = $awards->get_user_by_username($awards->get_input('username'));
				if(!$user)
				{
					$errors = inline_error($lang->ougc_awards_error_invaliduser);
				}
				else
				{
					$users[] = $user;
				}
			}
			else
			{
				$usernames = explode('multiple:', $awards->get_input('username'));
				foreach(explode(',', $usernames[1]) as $username)
				{
					$user = $awards->get_user_by_username($username);
					if(!$user)
					{
						$errors = inline_error($lang->ougc_awards_error_invaliduser);
						break;
					}
					$users[] = $user;
				}
			}
			unset($user, $usernames, $username);




			/*elseif($awards->get_gived_award($award['aid'], $user['uid']))
			{
				$errors = inline_error($lang->ougc_awards_error_give);
			}*/
			foreach($users as $user)
			{
				if(!$awards->can_edit_user($user['uid']))
				{
					$errors = inline_error($lang->ougc_awards_error_giveperm);
					break;
				}
			}

			if(empty($errors))
			{
				foreach($users as $user)
				{
					$awards->give_award($award, $user, $awards->get_input('reason'));
					$awards->log_action();
				}
				$awards->redirect($lang->ougc_awards_redirect_gived);
			}
		}

		$lang->ougc_awards_modcp_title_give = $lang->sprintf($lang->ougc_awards_modcp_title_give, $awards->get_award_info('name', $award['aid'], $award['name']));

		$gived_list = '';
		eval('$username = "'.$templates->get('ougcawards_modcp_manage_username').'";');
		eval('$reason = "'.$templates->get('ougcawards_modcp_manage_reason').'";');
		eval('$content = "'.$templates->get('ougcawards_modcp_manage').'";');
		eval('$page = "'.$templates->get('ougcawards_modcp').'";');
		output_page($page);
		exit;
	}
	// We can revoke awards from the ModCP
	elseif($awards->get_input('manage') == 'revoke')
	{
		if(!($award = $awards->get_award($awards->get_input('aid', 1))))
		{
			error($lang->ougc_awards_error_wrongaward);
		}

		if(!($category = $awards->get_category($award['cid'])))
		{
			error($lang->ougc_awards_error_invalidcategory);
		}

		add_breadcrumb(strip_tags($award['name']));
		add_breadcrumb($lang->ougc_awards_modcp_revoke);

		if(!$award['visible'])
		{
			error($lang->ougc_awards_error_wrongaward);
		}

		if(!$category['visible'])
		{
			error($lang->ougc_awards_error_invalidcategory);
		}

		$show_gived_list = false;

		if($mybb->request_method == 'post')
		{
			if(!($user = $awards->get_user_by_username($awards->get_input('username'))))
			{
				$errors = inline_error($lang->ougc_awards_error_invaliduser);
			}
			elseif(!($gived = $awards->get_gived_award($award['aid'], $user['uid'])))
			{
				$errors = inline_error($lang->ougc_awards_error_notgive);
			}
			elseif(!$awards->can_edit_user($user['uid']))
			{
				$errors = inline_error($lang->ougc_awards_error_giveperm);
			}
			elseif(!$awards->get_input('gid'))
			{
				$show_gived_list = true;
			}
			elseif(!($gived = $awards->get_gived_award(null, null, $awards->get_input('gid', 1))))
			{
				$errors = inline_error($lang->ougc_awards_error_notgive);
			}
			else
			{
				$awards->revoke_award($gived['gid']);
				$awards->log_action();
				$awards->redirect($lang->ougc_awards_redirect_revoked);
			}
		}

		$lang->ougc_awards_modcp_title_give = $lang->sprintf($lang->ougc_awards_modcp_title_give, $awards->get_award_info('name', $award['aid'], $award['name']));

		$reason = $gived_list = '';
		if($show_gived_list)
		{
			$select = $awards->generate_gived_select($award['aid'], $user['uid'], $awards->get_input('gid', 1));
			eval('$username = "'.$templates->get('ougcawards_modcp_manage_username_hidden').'";');
			eval('$gived_list = "'.$templates->get('ougcawards_modcp_manage_gived_list').'";');
		}
		else
		{
			eval('$username = "'.$templates->get('ougcawards_modcp_manage_username').'";');
		}

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

		if($awards->get_input('page', 1) > 0)
		{
			$start = ($awards->get_input('page', 1) - 1)*$limit;
		}
		else
		{
			$start = 0;
			$mybb->input['page'] = 1;
		}

		$awardlist = $multipage = '';
		$query = $db->simple_select('ougc_awards', '*', "visible='1' AND cid IN ('{$where_cids}')", array('limit_start' => $start, 'limit' => $limit));
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

			
			$multipage = multipage($awardscount, $limit, $awards->get_input('page', 1), $awards->build_url());
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

	$categories = $cids = array();

	$query = $db->simple_select('ougc_awards_categories', '*', "visible='1'", array('order_by' => 'disporder'));
	while($category = $db->fetch_array($query))
	{
		$cids[] = (int)$category['cid'];
		$categories[] = $category;
	}

	$whereclause = "u.uid='".(int)$memprofile['uid']."' AND a.visible='1' AND a.type!='2' AND a.cid IN ('".implode("','", array_values($cids))."')";

	// Query our data.
	if($limit == -1)
	{
		// Get awards
		$query = $db->query('
			SELECT u.*, a.*
			FROM '.TABLE_PREFIX.'ougc_awards_users u
			LEFT JOIN '.TABLE_PREFIX.'ougc_awards a ON (u.aid=a.aid)
			WHERE '.$whereclause.'
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
			WHERE a'.$whereclause.'
			ORDER BY au.date desc
		');
		$awardscount = (int)$db->fetch_field($query, 'awards');

		$page = $awards->get_input('view') == 'awards' ? $awards->get_input('page', 1) : 0;
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
			WHERE a'.$whereclause.'
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
		while($award = $db->fetch_array($query))
		{
			$_awards[(int)$award['cid']][] = $award;
		}

		$awardlist = '';
		if(!empty($categories))
		{
			foreach($categories as $disporder => $category)
			{
				if(!empty($_awards[(int)$category['cid']]))
				{
					$category['name'] = htmlspecialchars_uni($category['name']);
					$category['description'] = htmlspecialchars_uni($category['description']);

					eval('$awardlist .= "'.$templates->get('ougcawards_profile_row_category').'";');

					$trow = alt_trow(1);
					foreach($_awards[(int)$category['cid']] as $cid => $award)
					{
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
						$trow = alt_trow();
					}
				}
			}
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

	if(!isset($ougc_awards_cache))
	{
		global $db;
		$cids = array();

		$query = $db->simple_select('ougc_awards_categories', '*', "visible='1'", array('order_by' => 'disporder'));
		while($category = $db->fetch_array($query))
		{
			$cids[] = (int)$category['cid'];
		}

		$whereclause = "AND a.visible='1' AND a.type!='1' AND a.cid IN ('".implode("','", array_values($cids))."')";
	}

	// First we need to get our data
	if(THIS_SCRIPT == 'showthread.php' && isset($GLOBALS['pids']) && !isset($ougc_awards_cache))
	{
		$ougc_awards_cache = array();

		$pids = array_filter(array_unique(array_map('intval', explode('\'', $GLOBALS['pids']))));
		$query = $db->query('
			SELECT a.aid, a.name, a.image, ag.uid, ag.gid, ag.reason
			FROM '.TABLE_PREFIX.'ougc_awards a
			JOIN '.TABLE_PREFIX.'ougc_awards_users ag ON (ag.aid=a.aid)
			JOIN '.TABLE_PREFIX.'posts p ON (p.uid=ag.uid)
			WHERE p.pid IN (\''.implode('\',\'', $pids).'\') '.$whereclause.'
			ORDER BY ag.date desc'
		);
		// how to limit by uid here?
		// -- '.($max_postbit == -1 ? '' : 'LIMIT '.$max_postbit)

		while($data = $db->fetch_array($query))
		{
			$ougc_awards_cache[$data['uid']][$data['gid']] = $data;
		}
	}
	elseif(!isset($ougc_awards_cache))
	{
		global $db;
		$ougc_awards_cache = array();

		$query = $db->query('
			SELECT a.aid, a.name, a.image, ag.uid, ag.gid, ag.reason
			FROM '.TABLE_PREFIX.'ougc_awards a
			JOIN '.TABLE_PREFIX.'ougc_awards_users ag ON (ag.aid=a.aid)
			WHERE ag.uid=\''.(int)$post['uid'].'\' '.$whereclause.'
			ORDER BY ag.date desc
			'.($max_postbit == -1 ? '' : 'LIMIT '.$max_postbit)
		);
	
		while($data = $db->fetch_array($query))
		{
			$ougc_awards_cache[$data['uid']][$data['gid']] = $data;
		}
	}

	// User has no awards
	if(empty($ougc_awards_cache[$post['uid']]))
	{
		return;
	}
	$awardlist = &$ougc_awards_cache[$post['uid']];

	global $templates, $awards, $lang;
	$awards->lang_load();

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

			if($reason = $awards->get_award_info('reason', $award['aid'], $award['gid']))
			{
				$award['reason'] = $reason;
			}

			if(empty($award['reason']))
			{
				$award['reason'] = $lang->ougc_awards_pm_noreason;
			}

			$awards->parse_text($award['reason']);

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

// Global requests notification
function ougc_awards_global_intermediate()
{
	global $mybb, $awards, $lang, $templates, $ougc_awards_menu, $ougc_awards_requests;
	$awards->lang_load();

	$ougc_awards_menu = eval($templates->render('ougcawards_global_menu'));

	$ougc_awards_requests = '';

	// TODO administratos should be able to manage requests from the ACP
	if(!($mybb->user['uid'] && $mybb->usergroup['canmodcp'] && $mybb->settings['ougc_awards_modcp'] && $awards->is_member($mybb->settings['ougc_awards_modgroups'])))
	{
		//return;
	}

	global $PL;
	$PL or require_once PLUGINLIBRARY;

	$_cache = $PL->cache_read('ougc_awards');
	$pending = (int)$_cache['requests']['pending'];

	if($pending < 1)
	{
		return;
	}

	$message = $lang->sprintf($lang->ougc_awards_page_pending_requests_moderator, $mybb->settings['bburl']);
	if($pending > 1)
	{
		$message = $lang->sprintf($lang->ougc_awards_page_pending_requests_moderator_plural, $mybb->settings['bburl'], my_number_format($pending));
	}

	$ougc_awards_requests = eval($templates->render('ougcawards_global_notification'));
}

// Stats page
function ougc_awards_stats_start()
{
	global $templates, $mybb, $ougc_awards_js;

	$ougc_awards_js = eval($templates->render('ougcawards_js'));
}

function ougc_awards_stats_end()
{
	global $awards, $db, $templates, $lang, $ougc_awards_most, $ougc_awards_last, $theme, $mybb;

	$ougc_awards_most = $ougc_awards_last = $userlist = '';
	$place = 0;

	if(!$mybb->settings['ougc_awards_enablestatspage'])
	{
		return;
	}

	$awards->lang_load();

	$stats = $mybb->cache->read('ougc_awards');

	if(empty($stats['top']))
	{
		$userlist = eval($templates->render('ougcawards_stats_empty'));
	}
	else
	{
		$_users = array();

		$query = $db->simple_select('users', 'uid, username, usergroup, displaygroup', "uid IN ('".implode("','", array_values($stats['top']))."')");
		while($user = $db->fetch_array($query))
		{
			$_users[(int)$user['uid']] = $user;
		}

		$trow = alt_trow(true);
		foreach($stats['top'] as $total => $uid)
		{
			++$place;
			$username = htmlspecialchars_uni($_users[$uid]['username']);
			$username_formatted = format_name($_users[$uid]['username'], $_users[$uid]['usergroup'], $_users[$uid]['displaygroup']);
			$profilelink = build_profile_link($_users[$uid]['username'], $uid);
			$profilelink_formatted = build_profile_link(format_name($_users[$uid]['username'], $_users[$uid]['usergroup'], $_users[$uid]['displaygroup']), $uid);

			$field = eval($templates->render('ougcawards_stats_user_viewall'));

			$userlist .= eval($templates->render('ougcawards_stats_user'));
			$trow = alt_trow();
		}
	}

	$title = $lang->ougc_awards_stats_most;

	$ougc_awards_most = eval($templates->render('ougcawards_stats'));

	$userlist = '';
	$place = 0;

	if(empty($stats['last']))
	{
		$userlist = eval($templates->render('ougcawards_stats_empty'));
	}
	else
	{
		$_users = array();

		$query = $db->simple_select('users', 'uid, username, usergroup, displaygroup', "uid IN ('".implode("','", array_values($stats['last']))."')");
		while($user = $db->fetch_array($query))
		{
			$_users[(int)$user['uid']] = $user;
		}

		$trow = alt_trow(true);
		foreach($stats['last'] as $date => $uid)
		{
			++$place;
			$username = htmlspecialchars_uni($_users[$uid]['username']);
			$username_formatted = format_name($_users[$uid]['username'], $_users[$uid]['usergroup'], $_users[$uid]['displaygroup']);
			$profilelink = build_profile_link($_users[$uid]['username'], $uid);
			$profilelink_formatted = build_profile_link(format_name($_users[$uid]['username'], $_users[$uid]['usergroup'], $_users[$uid]['displaygroup']), $uid);

			$field = my_date('relative', $date);

			$userlist .= eval($templates->render('ougcawards_stats_user'));
			$trow = alt_trow();
		}
	}

	$title = $lang->ougc_awards_stats_last;

	$ougc_awards_last = eval($templates->render('ougcawards_stats'));
}

// Fix whatever
function ougc_awards_datahandler_user_insert(&$dh)
{
	$dh->user_insert_data['ougc_awards'] = '';
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

	// List of tables
	function _db_tables()
	{
		$tables = array(
			'ougc_awards'				=> array(
				'aid'			=> "int UNSIGNED NOT NULL AUTO_INCREMENT",
				'cid'			=> "int UNSIGNED NOT NULL DEFAULT '0'",
				'name'			=> "varchar(100) NOT NULL DEFAULT ''",
				'description'	=> "varchar(255) NOT NULL DEFAULT ''",
				'image'			=> "varchar(255) NOT NULL DEFAULT ''",
				'disporder'		=> "smallint(5) NOT NULL DEFAULT '0'",
				'allowrequests'	=> "tinyint(1) NOT NULL DEFAULT '1'",
				'visible'		=> "smallint(1) NOT NULL DEFAULT '1'",
				'pm'			=> "text NOT NULL",
				'type'			=> "smallint(1) NOT NULL DEFAULT '0'",
				'prymary_key'	=> "aid"
			),
			'ougc_awards_users'			=> array(
				'gid'			=> "int UNSIGNED NOT NULL AUTO_INCREMENT",
				'uid'			=> "int UNSIGNED NOT NULL DEFAULT '0'",
				'aid'			=> "int UNSIGNED NOT NULL DEFAULT '0'",
				'rid'			=> "int UNSIGNED NOT NULL DEFAULT '0'",
				'tid'			=> "int UNSIGNED NOT NULL DEFAULT '0'",
				'thread'		=> "int UNSIGNED NOT NULL DEFAULT '0'",
				'reason'		=> "text NOT NULL",
				'date'			=> "int(10) NOT NULL DEFAULT '0'",
				'prymary_key'	=> "gid"
			),
			'ougc_awards_categories'	=> array(
				'cid'			=> "int UNSIGNED NOT NULL AUTO_INCREMENT",
				'name'			=> "varchar(100) NOT NULL DEFAULT ''",
				'description'	=> "varchar(255) NOT NULL DEFAULT ''",
				'disporder'		=> "smallint NOT NULL DEFAULT '0'",
				'allowrequests'	=> "tinyint(1) NOT NULL DEFAULT '1'",
				'visible'		=> "tinyint(1) NOT NULL DEFAULT '1'",
				'prymary_key'	=> "cid"
			),
			'ougc_awards_requests'		=> array(
				'rid'			=> "int UNSIGNED NOT NULL AUTO_INCREMENT",
				'aid'			=> "int UNSIGNED NOT NULL DEFAULT '0'",
				'uid'			=> "int UNSIGNED NOT NULL DEFAULT '0'",
				'message'		=> "text NOT NULL",
				'status'		=> "smallint(1) NOT NULL DEFAULT '1'",
				'prymary_key'	=> "rid"
			),
			'ougc_awards_tasks'			=> array(
				'tid'					=> "int UNSIGNED NOT NULL AUTO_INCREMENT",
				'name'					=> "varchar(100) NOT NULL DEFAULT ''",
				'description'			=> "varchar(255) NOT NULL DEFAULT ''",
				'disporder'				=> "smallint(5) NOT NULL DEFAULT '0'",
				'active'				=> "smallint(1) NOT NULL DEFAULT '1'",
				'logging'				=> "smallint(1) NOT NULL DEFAULT '1'",
				'requirements'			=> "varchar(200) NOT NULL DEFAULT ''",
				'usergroups'			=> "text NOT NULL",
				'give'					=> "text NOT NULL",
				'reason'				=> "text NOT NULL",
				'revoke'				=> "text NOT NULL",
				'posts'					=> "int UNSIGNED NOT NULL DEFAULT '0'",
				'poststype'				=> "char(2) NOT NULL DEFAULT ''",
				'threads'				=> "int UNSIGNED NOT NULL DEFAULT '0'",
				'threadstype'			=> "char(2) NOT NULL DEFAULT ''",
				'fposts'				=> "int UNSIGNED NOT NULL DEFAULT '0'",
				'fpoststype'			=> "char(2) NOT NULL DEFAULT ''",
				'fpostsforums'			=> "text NOT NULL",
				'fthreads'				=> "int UNSIGNED NOT NULL DEFAULT '0'",
				'fthreadstype'			=> "char(2) NOT NULL DEFAULT ''",
				'fthreadsforums'		=> "text NOT NULL",
				'registered'			=> "int UNSIGNED NOT NULL DEFAULT '0'",
				'registeredtype'		=> "varchar(20) NOT NULL DEFAULT ''",
				'online'				=> "int UNSIGNED NOT NULL DEFAULT '0'",
				'onlinetype'			=> "varchar(20) NOT NULL DEFAULT ''",
				'reputation'			=> "int NOT NULL DEFAULT '0'",
				'reputationtype'		=> "char(2) NOT NULL DEFAULT ''",
				'referrals'				=> "int UNSIGNED NOT NULL DEFAULT '0'",
				'referralstype'			=> "char(2) NOT NULL DEFAULT ''",
				'warnings'				=> "int UNSIGNED NOT NULL DEFAULT '0'",
				'warningstype'			=> "char(2) NOT NULL DEFAULT ''",
				'newpoints'				=> "int NOT NULL DEFAULT '0'",
				'newpointstype'			=> "char(2) NOT NULL DEFAULT ''",
				'profilefields'			=> "text NOT NULL",
				'mydownloads'			=> "int UNSIGNED NOT NULL DEFAULT '0'",
				'mydownloadstype'		=> "char(2) NOT NULL DEFAULT ''",
				'myarcadechampions'		=> "int UNSIGNED NOT NULL DEFAULT '0'",
				'myarcadechampionstype'	=> "char(2) NOT NULL DEFAULT ''",
				'myarcadescores'		=> "int UNSIGNED NOT NULL DEFAULT '0'",
				'myarcadescorestype'	=> "char(2) NOT NULL DEFAULT ''",
				'ougc_customrep_r'		=> "int UNSIGNED NOT NULL DEFAULT '0'",
				'ougc_customreptype_r'	=> "char(2) NOT NULL DEFAULT ''",
				'ougc_customrepids_r'	=> "text NOT NULL",
				'ougc_customrep_g'		=> "int UNSIGNED NOT NULL DEFAULT '0'",
				'ougc_customreptype_g'	=> "char(2) NOT NULL DEFAULT ''",
				'ougc_customrepids_g'	=> "text NOT NULL",
				'prymary_key'	=> "tid"
			)
		);

		return $tables;
	}

	// List of columns
	function _db_columns()
	{
		$tables = array(
			'users'			=> array(
				'ougc_awards' => 'text NOT NULL'
			),
		);

		return $tables;
	}

	// Verify DB tables
	function _db_verify_tables()
	{
		global $db;

		$collation = $db->build_create_table_collation();
		foreach($this->_db_tables() as $table => $fields)
		{
			if($db->table_exists($table))
			{
				foreach($fields as $field => $definition)
				{
					if($field == 'prymary_key')
					{
						continue;
					}

					if($db->field_exists($field, $table))
					{
						$db->modify_column($table, "`{$field}`", $definition);
					}
					else
					{
						$db->add_column($table, $field, $definition);
					}
				}
			}
			else
			{
				$query = "CREATE TABLE IF NOT EXISTS `".TABLE_PREFIX."{$table}` (";
				foreach($fields as $field => $definition)
				{
					if($field == 'prymary_key')
					{
						$query .= "PRIMARY KEY (`{$definition}`)";
					}
					else
					{
						$query .= "`{$field}` {$definition},";
					}
				}
				$query .= ") ENGINE=MyISAM{$collation};";
				$db->write_query($query);
			}
		}
	}

	// Verify DB columns
	function _db_verify_columns()
	{
		global $db;

		foreach($this->_db_columns() as $table => $columns)
		{
			foreach($columns as $field => $definition)
			{
				if($db->field_exists($field, $table))
				{
					$db->modify_column($table, "`{$field}`", $definition);
				}
				else
				{
					$db->add_column($table, $field, $definition);
				}
			}
		}
	}

	// Verify DB indexes
	function _db_verify_indexes()
	{
		global $db;

		if(!$db->index_exists('ougc_awards_users', 'uidaid'))
		{
			$db->write_query('ALTER TABLE '.TABLE_PREFIX.'ougc_awards_users ADD UNIQUE KEY uidaid (uid,aid)');
		}
		if(!$db->index_exists('ougc_awards_users', 'aiduid'))
		{
			$db->write_query('CREATE INDEX aiduid ON '.TABLE_PREFIX.'ougc_awards_users (aid,uid)');
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
		global $db, $mybb;

		$mybb->settings['statslimit'] = (int)$mybb->settings['statslimit'];

		$_cache= array(
			'time'			=> TIME_NOW,
			'awards'		=> array(),
			'categories'	=> array(),
			'requests'		=> array(),
			'top'			=> array(),
			'last'			=> array()
		);

		$query = $db->simple_select('ougc_awards_categories', 'cid, name, allowrequests', "visible='1'", array('order_by' => 'disporder'));
		while($category = $db->fetch_array($query))
		{
			$_cache['categories'][(int)$category['cid']] = array(
				'name'			=> (string)$category['name'],
				'allowrequests'	=> (int)$category['allowrequests']
			);
		}

		$query = $db->simple_select('ougc_awards', 'aid, cid, name, image, allowrequests, type', "visible='1' AND cid IN ('".implode("','", array_keys($_cache['categories']))."')", array('order_by' => 'disporder'));
		while($award = $db->fetch_array($query))
		{
			$_cache['awards'][(int)$award['aid']] = array(
				'cid'			=> (int)$award['cid'],
				'name'			=> (string)$award['name'],
				'image'			=> (string)$award['image'],
				'allowrequests'	=> (int)$award['allowrequests'],
				'type'			=> (int)$award['type']
			);
		}

		$query = $db->simple_select('ougc_awards_requests', 'COUNT(rid) AS pending', "status='1'");
		$pending = $db->fetch_field($query, 'pending');

		$_cache['requests'] = array(
			'pending'	=> (int)$pending
		);

		// Stats
		$where = "aid IN ('".implode("','", array_keys($_cache['awards']))."')";
		$query = $db->query("
			SELECT u.uid, a.awards
			FROM ".TABLE_PREFIX."users u
			LEFT JOIN (SELECT uid, COUNT(aid) AS awards FROM ".TABLE_PREFIX."ougc_awards_users WHERE {$where} GROUP BY uid) a ON (u.uid=a.uid)
			WHERE a.awards!=''
			ORDER BY a.awards DESC
			LIMIT 0, {$mybb->settings['statslimit']}
		;");
		while($user = $db->fetch_array($query))
		{
			$_cache['top'][(int)$user['awards']] = (int)$user['uid'];
		}

		$query = $db->simple_select('ougc_awards_users', 'uid, date', $where, array('order_by' => 'date', 'order_dir' => 'desc', 'limit' => 10));
		while($user = $db->fetch_array($query))
		{
			$_cache['last'][(int)$user['date']] = (int)$user['uid'];
		}

		$mybb->cache->update('ougc_awards', $_cache);

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

		if(!empty($params))
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

	// Get an award from the DB
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

	// Get a task from the DB
	function get_task($tid)
	{
		if(!isset($this->cache['tasks'][$tid]))
		{
			global $db;
			$this->cache['tasks'][$tid] = false;

			$query = $db->simple_select('ougc_awards_tasks', '*', 'tid=\''.(int)$tid.'\'');
			$task = $db->fetch_array($query);
			if(isset($task['tid']))
			{
				$this->cache['tasks'][$tid] = $task;
			}
		}

		return $this->cache['tasks'][$tid];
	}

	// Get a request from the DB
	function get_request($uid, $aid)
	{
		if(!isset($this->cache['requests'][$uid][$aid]))
		{
			global $db;
			$this->cache['requests'][$uid][$aid] = false;

			$uid = (int)$uid;
			$aid = (int)$aid;

			$query = $db->simple_select('ougc_awards_requests', '*', "uid='{$uid}' AND aid='{$aid}'");
			$request = $db->fetch_array($query);
			if(isset($request['rid']))
			{
				$this->cache['requests'][$uid][$aid] = $request;
			}
		}

		return $this->cache['requests'][$uid][$aid];
	}

	// Get a category from the DB
	function get_category($cid)
	{
		if(!isset($this->cache['categories'][$cid]))
		{
			global $db;
			$this->cache['categories'][$cid] = false;

			$query = $db->simple_select('ougc_awards_categories', '*', 'cid=\''.(int)$cid.'\'');
			$award = $db->fetch_array($query);
			if(isset($award['cid']))
			{
				$this->cache['categories'][$cid] = $award;
			}
		}

		return $this->cache['categories'][$cid];
	}

	// Insert a new award to the DB
	function insert_award($data, $aid=null, $update=false)
	{
		global $db;

		$cleandata = array();

		!isset($data['name']) or $cleandata['name'] = $db->escape_string($data['name']);
		!isset($data['cid']) or $cleandata['cid'] = (int)$data['cid'];
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

	// Update espesific award
	function update_award($data, $aid)
	{
		$this->insert_award($data, $aid, true);
	}

	// Insert a new request to the DB
	function insert_request($data, $rid=null, $update=false)
	{
		global $db;

		$cleandata = array();

		!isset($data['uid']) or $cleandata['uid'] = (int)$data['uid'];
		!isset($data['aid']) or $cleandata['aid'] = (int)$data['aid'];
		!isset($data['message']) or $cleandata['message'] = $db->escape_string($data['message']);
		!isset($data['status']) or $cleandata['status'] = (int)$data['status'];

		if($update)
		{
			$this->rid = (int)$rid;
			$db->update_query('ougc_awards_requests', $cleandata, 'rid=\''.$this->rid.'\'');
		}
		else
		{
			$this->rid = (int)$db->insert_query('ougc_awards_requests', $cleandata);
		}

		return true;
	}

	// Update espesific request
	function update_request($data, $rid)
	{
		$this->insert_request($data, $aid, true);
	}

	// Insert a new category to the DB
	function insert_category($data, $cid=null, $update=false)
	{
		global $db;

		$cleandata = array();

		!isset($data['name']) or $cleandata['name'] = $db->escape_string($data['name']);
		!isset($data['description']) or $cleandata['description'] = $db->escape_string($data['description']);
		!isset($data['disporder']) or $cleandata['disporder'] = (int)$data['disporder'];
		!isset($data['visible']) or $cleandata['visible'] = (int)$data['visible'];

		if($update)
		{
			$this->cid = (int)$cid;
			$db->update_query('ougc_awards_categories', $cleandata, 'cid=\''.$this->cid.'\'');
		}
		else
		{
			$this->cid = (int)$db->insert_query('ougc_awards_categories', $cleandata);
		}

		return true;
	}

	// Update espesific category
	function update_category($data, $cid)
	{
		$this->insert_category($data, $cid, true);
	}

	// Insert a new task to the DB
	function insert_task($data, $tid=null, $update=false)
	{
		global $db;

		$cleandata = array();

		foreach(array('name', 'description', 'reason') as $k)
		{
			!isset($data[$k]) or $cleandata[$k] = $db->escape_string($data[$k]);
		}

		foreach(array('active', 'logging', 'disporder', 'posts', 'threads', 'fposts', 'fpostsforums', 'fthreads', 'fthreadsforums', 'registered', 'online', 'reputation', 'referrals', 'warnings', 'newpoints', 'mydownloads', 'myarcadechampions', 'myarcadescores', 'ougc_customrep_r', 'ougc_customrep_g', 'ougc_customrepids_r', 'ougc_customrepids_g') as $k)
		{
			!isset($data[$k]) or $cleandata[$k] = (int)$data[$k];
		}

		foreach(array('poststype', 'threadstype', 'fpoststype', 'fthreadstype', 'reputationtype', 'referralstype', 'warningstype', 'newpointstype', 'mydownloadstype', 'myarcadechampionstype', 'myarcadescorestype', 'ougc_customreptype_r', 'ougc_customreptype_g') as $k)
		{
			in_array($data[$k], array('>', '>=', '=', '<=', '<')) or $data[$k] = '=';

			!isset($data[$k]) or $cleandata[$k] = $db->escape_string($data[$k]);
		}

		foreach(array('registeredtype', 'onlinetype') as $k)
		{
			in_array($data[$k], array('hours', 'days', 'weeks', 'months', 'years')) or $data[$k] = '=';

			!isset($data[$k]) or $cleandata[$k] = $db->escape_string($data[$k]);
		}

		foreach(array('usergroups', 'give', 'revoke', 'profilefields') as $k)
		{
			is_array($data[$k]) or $data[$k] = array($data[$k]);

			$data[$k] = implode(',', array_filter(array_unique(array_map('intval', $data[$k]))));
			
			!isset($data[$k]) or $cleandata[$k] = $db->escape_string($data[$k]);
		}

		!isset($data['requirements']) or $cleandata['requirements'] = $db->escape_string(implode(',', array_filter(array_unique((array)$data['requirements']))));

		if($update)
		{
			$this->tid = (int)$tid;
			$db->update_query('ougc_awards_tasks', $cleandata, "tid='{$this->tid}'");
		}
		else
		{
			$this->tid = (int)$db->insert_query('ougc_awards_tasks', $cleandata);
		}

		return true;
	}

	// Update espesific task
	function update_task($data, $tid)
	{
		$this->insert_task($data, $tid, true);
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
		if($this->cid)
		{
			$data['cid'] = $this->cid;
		}
		if($this->rid)
		{
			$data['rid'] = $this->rid;
		}
		if($this->tid)
		{
			$data['tid'] = $this->tid;
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
	function get_gived_award($aid, $uid, $gid=0)
	{
		global $db;

		$aid = (int)$aid;
		$uid = (int)$uid;
		$where = "aid='{$aid}' AND uid='{$uid}'";
		if(!$aid && !$uid && $gid)
		{
			$gid = (int)$gid;
			$where = "gid='{$gid}'";
		}

		$query = $db->simple_select('ougc_awards_users', '*', $where);

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
	function revoke_award($gid)
	{
		global $db, $plugins;

		$this->gid = (int)$gid;

		$plugins->run_hooks('ougc_awards_revoke_award', $this);

		$db->delete_query('ougc_awards_users', "gid='{$this->gid}'");
	}

	// Completely removes an award data from the DB
	function delete_award($aid)
	{
		global $db;
		$this->aid = (int)$aid;

		$query = $db->simple_select('ougc_awards_users', 'gid', 'aid=\''.$this->aid.'\'');
		while($gid = $db->fetch_field($query, 'gid'))
		{
			$this->revoke_award($gid);
		}

		$db->delete_query('ougc_awards', 'aid=\''.$this->aid.'\'');
	}

	// Completely removes an category data from the DB
	function delete_category($cid)
	{
		global $db;
		$this->cid = (int)$cid;

		$query = $db->simple_select('ougc_awards', 'aid', 'cid=\''.$this->cid.'\'');
		while($aid = $db->fetch_field($query, 'aid'))
		{
			$this->delete_award($aid);
		}

		$db->delete_query('ougc_awards_categories', 'cid=\''.$this->cid.'\'');
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
		global $plugins;

		$awards = &$this;

		if(!($type = $awards->get_input('ougc_awards_import')))
		{
			return;
		}

		switch($type)
		{
			case 'nickawards';
				$tables = array('awards' => 'awards', 'users' => 'awards_given');
				$keys = array('name' => 'name', 'description' => '', 'image' => 'image', 'original_id' => 'id', 'original_id_u' => 'award_id', 'uid' => 'to_uid', 'reason' => 'reason', 'TIME_NOW' => 'date_given');
				$img_prefix = '{bburl}/uploads/awards/';
				$lang_var = 'ougc_awards_import_confirm_mybbcentral';
				break;
			default;
				$tables = array('awards' => 'myawards', 'users' => 'myawards_users');
				$keys = array('name' => 'awname', 'description' => 'awdescr', 'image' => 'awimg', 'original_id' => 'awid', 'original_id_u' => 'awid', 'uid' => 'awuid', 'reason' => 'awreason', 'TIME_NOW' => 'awutime');
				$img_prefix = '{bburl}/uploads/awards/';
				$lang_var = 'ougc_awards_import_confirm_mybbcentral';
				break;
		}

		$args = array(
			'this'			=> &$this,
			'tables'		=> &$tables,
			'keys'			=> &$keys,
			'img_prefix'	=> &$img_prefix,
			'lang_var'		=> &$lang_var,
			'keys'			=> &$this
		);

		$plugins->run_hooks('ougc_awards_importer_start', $args);

		global $lang, $mybb, $page;
		$awards->lang_load();

		if($mybb->request_method == 'post')
		{
			if(!verify_post_check($awards->get_input('my_post_key')))
			{
				flash_message($lang->invalid_post_verify_key2, 'error');
				admin_redirect("index.php?module=config-plugins");
			}

			if(isset($mybb->input['no']))
			{
				return true;
			}

			global $db;

			$awards->insert_category(array(
				'name'			=> 'Imported Awards',
				'description'	=> 'Automatic category created after an import.'
			));

			$query = $db->simple_select('ougc_awards', 'MAX(disporder) AS max_disporder');
			$disporder = (int)$db->fetch_field($query, 'max_disporder');

			$query = $db->simple_select($tables['awards']);
			while($award = $db->fetch_array($query))
			{
				$insert_award = array(
					'cid'			=> $awards->cid,
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

			$plugins->run_hooks('ougc_awards_importer_end', $args);

			flash_message($lang->ougc_awards_import_end, 'success');
			admin_redirect('index.php?module=config-plugins');
		}

		$page->output_confirm_action("index.php?module=config-plugins&ougc_awards_import={$type}", $lang->{$lang_var}, $lang->ougc_awards_import_title);
	}

	/**
	 * Checks the input data type before usage.
	 *
	 * @param string $name Variable name ($mybb->input)
	 * @param int $type The type of the variable to get. Should be one of MyBB::INPUT_INT, MyBB::INPUT_ARRAY or MyBB::INPUT_STRING.
	 *
	 * @return mixed Checked data
	 */
	function get_input($name, $type=0)
	{
		global $mybb;

		switch($type)
		{
			case 2:
				if(!isset($mybb->input[$name]) || !is_array($mybb->input[$name]))
				{
					return array();
				}
				return $mybb->input[$name];
			case 1:
				if(!isset($mybb->input[$name]) || !is_numeric($mybb->input[$name]))
				{
					return 0;
				}
				return (int)$mybb->input[$name];
			case 3:
				if(!isset($mybb->input[$name]) || !is_numeric($mybb->input[$name]))
				{
					return 0.0;
				}
				return (float)$mybb->input[$name];
			case 4:
				if(!isset($mybb->input[$name]) || !is_scalar($mybb->input[$name]))
				{
					return false;
				}
				return (bool)$mybb->input[$name];
			default:
				if(!isset($mybb->input[$name]) || !is_scalar($mybb->input[$name]))
				{
					return '';
				}
				return $mybb->input[$name];
		}
	}
	
	function generate_awards_select($name, $selected=array(), $options)
	{
		global $db, $mybb;

		$select = "<select name=\"{$name}\"";

		!isset($options['multiple']) or $select .= " multiple=\"multiple\"";

		$select .= ">";

		is_array($selected) or $selected = array($selected);

		$query = $db->simple_select('ougc_awards', '*', '', array('order_by' => 'disporder'));
		while($award = $db->fetch_array($query))
		{
			$s = '';
			if(in_array($award['aid'], $selected))
			{
				$s = 'selected="selected"';
			}

			$select .= "<option value=\"{$award['aid']}\"{$s}>{$award['name']}</option>";
		}

		$select .= "</select>";

		return $select;
	}
	
	function generate_profilefields_select($name, $selected=array(), $options)
	{
		global $db, $mybb;

		$select = "<select name=\"{$name}\"";

		!isset($options['multiple']) or $select .= " multiple=\"multiple\"";
		!isset($options['id']) or $select .= " id=\"id\"";

		$select .= ">";

		is_array($selected) or $selected = array($selected);

		$query = $db->simple_select('profilefields', '*', '', array('order_by' => 'disporder'));
		while($profilefield = $db->fetch_array($query))
		{
			$s = '';
			if(in_array($profilefield['fid'], $selected))
			{
				$s = 'selected="selected"';
			}

			$select .= "<option value=\"{$profilefield['fid']}\"{$s}>{$profilefield['name']}</option>";
		}

		$select .= "</select>";

		return $select;
	}
	
	function generate_gived_select($aid, $uid, $input)
	{
		global $db, $mybb;

		$select = "<select name=\"gid\">\n";

		$aid = (int)$aid;
		$uid = (int)$uid;
		$input = (int)$input;

		$query = $db->simple_select('ougc_awards_users', '*', "aid='{$aid}' AND uid='{$uid}'");
		while($gived = $db->fetch_array($query))
		{
			$selected = '';
			if($gived['gid'] == $input)
			{
				$selected = 'selected="selected"';
			}

			if($mybb->version_code >= 1800)
			{
				$date = my_date('relative', $gived['date']);
			}
			else
			{
				$date = my_date($mybb->settings['dateformat'], $gived['date']).' - '.my_date($mybb->settings['timeformat'], $gived['date']);
			}

			if($reason = $this->get_award_info('reason', $gived['aid'], $gived['gid']))
			{
				$gived['reason'] = $reason;
			}

			$select .= "<option value=\"{$gived['gid']}\"{$selected}>".$date.' ('.htmlspecialchars_uni($gived['reason']).")</option>";
		}

		$select .= "</select>";

		return $select;
	}
	
	function generate_category_select($aid, $input)
	{
		global $db, $mybb;

		$select = "<select name=\"cid\">\n";

		$aid = (int)$aid;
		$input = (int)$input;

		$query = $db->simple_select('ougc_awards_categories', '*', '', array('order_by' => 'disporder'));
		while($category = $db->fetch_array($query))
		{
			$selected = '';
			if($category['cid'] == $input)
			{
				$selected = 'selected="selected"';
			}

			$select .= "<option value=\"{$category['cid']}\"{$selected}>{$category['name']}</option>";
		}

		$select .= "</select>";

		return $select;
	}
	
	function generate_ougc_custom_reputation_select($name, $selected=0)
	{
		global $db, $mybb;

		$select = "<select name=\"{$name}\"";

		!isset($options['multiple']) or $select .= " multiple=\"multiple\"";

		$select .= ">";

		$selected = (int)$selected;

		$query = $db->simple_select('ougc_customrep', '*', '', array('order_by' => 'disporder'));
		while($rep = $db->fetch_array($query))
		{
			$s = '';
			if($rep['rid'] == $selected)
			{
				$s = 'selected="selected"';
			}

			$select .= "<option value=\"{$rep['rid']}\"{$s}>{$rep['name']}</option>";
		}

		$select .= "</select>";

		return $select;
	}
}
$GLOBALS['awards'] = new OUGC_Awards;