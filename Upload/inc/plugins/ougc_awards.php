<?php

/***************************************************************************
 *
 *	OUGC Awards plugin (/inc/plugins/ougc_awards.php)
 *	Author: Omar Gonzalez
 *	Copyright: © 2012-2020 Omar Gonzalez
 *
 *	Website: https://ougc.network
 *
 *	Adds a powerful awards system to you community.
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
	global $cache, $templatelist;

	if(isset($templatelist))
	{
		$templatelist .= ',';
	}
	else
	{
		$templatelist = '';
	}

	$templatelist .= 'ougcawards_js,ougcawards_css, ougcawards_global_menu,ougcawards_global_notification,ougcawards_welcomeblock,ougcawards_award_image,ougcawards_award_image_class,';

	$awards = $cache->read('ougc_awards');
	foreach($awards['awards'] as $aid => $award)
	{
		if($award['template'] == 2)
		{
			$templatelist .= 'ougcawards_award_image'.$aid.',ougcawards_award_image_cat'.$award['cid'].',ougcawards_award_image_class'.$aid.',ougcawards_award_image_class'.$aid.',';
		}
	}
	unset($awards, $award);

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
			$templatelist .= 'ougcawards_postbit, ougcawards_stats_user_viewall, ougcawards_postbit_preset_award, ougcawards_postbit_preset';
			break;
		case 'member.php':
			global $mybb;

			if((string)$mybb->input['action'] == 'profile')
			{
				$plugins->add_hook('member_profile_end', 'ougc_awards_profile');
				$templatelist .= 'ougcawards_profile_row, ougcawards_profile_row_category, ougcawards_profile, ougcawards_profile_multipage, multipage_prevpage, multipage_page, multipage_page_current, multipage_nextpage, multipage, ougcawards_profile_preset_row, ougcawards_profile_preset';
			}
			break;
		case 'usercp.php':
		case 'modcp.php':
			$plugins->add_hook('usercp_start', 'ougc_awards_modcp');
			$plugins->add_hook('modcp_start', 'ougc_awards_modcp');
			$templatelist .= 'ougcawards_page_empty,ougcawards_usercp_nav, ougcawards_modcp_requests_list_empty, ougcawards_modcp_list_button, ougcawards_modcp_requests_list, ougcawards_modcp, ougcawards_modcp_requests_list_item,ougcawards_modcp_manage_multiple, ougcawards_modcp_manage_username, ougcawards_modcp_manage_thread, ougcawards_modcp_manage_reason, ougcawards_modcp_manage, ougcawards_usercp_sort_award, ougcawards_usercp_sort_empty, ougcawards_usercp_sort, ougcawards_modcp_list_award, ougcawards_modcp_list, ougcawards_page_empty, ougcawards_modcp_requests_buttons, ougcawards_modcp_nav,ougcawards_modcp_nav, ougctooltip_js, ougcawards_usercp_presets_select_option, ougcawards_usercp_presets_select, ougcawards_usercp_presets_addform, ougcawards_usercp_presets_award, ougcawards_usercp_presets_form, ougcawards_usercp_presets, ougcawards_usercp_presets_form_js';
			break;
		case 'stats.php':
			$plugins->add_hook('stats_end', 'ougc_awards_stats_end');
			$templatelist .= 'ougcawards_stats_user_viewall, ougcawards_stats_user, ougcawards_stats';
			break;
	}

	$plugins->add_hook('global_start', 'ougc_awards_global_start');
	$plugins->add_hook('global_intermediate', 'ougc_awards_global_intermediate');
	$plugins->add_hook('fetch_wol_activity_end', 'ougc_awards_fetch_wol_activity_end');
	$plugins->add_hook('build_friendly_wol_location_end', 'ougc_awards_build_friendly_wol_location_end');
	$plugins->add_hook('xmlhttp', 'ougc_awards_xmlhttp');
}

$plugins->add_hook('datahandler_user_insert', 'ougc_awards_datahandler_user_insert');

// PLUGINLIBRARY
defined('PLUGINLIBRARY') or define('PLUGINLIBRARY', MYBB_ROOT.'inc/plugins/pluginlibrary.php');

define('OUGC_AWARDS_ROOT', MYBB_ROOT . 'inc/plugins/ougc_awards');

// Plugin API
function ougc_awards_info()
{
	global $lang, $awards;
	$awards->lang_load();

	return array(
		'name'			=> 'OUGC Awards',
		'description'	=> $lang->setting_group_ougc_awards_desc.($awards->allow_imports && ougc_awards_is_installed() ? $lang->ougc_awards_import_desc : ''),
		'website'		=> 'https://ougc.network',
		'author'		=> 'Omar G.',
		'authorsite'	=> 'https://ougc.network',
		'version'		=> '1.8.22',
		'versioncode'	=> 1822,
		'compatibility'	=> '18*',
		'myalerts'		=> '2.0.4',
		'codename'		=> 'ougc_awards',
		'newpoints'		=> '2.1.1',
		'pl'			=> array(
			'version'	=> 13,
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
		'postbit_maxperline'	=> array(
		   'title'			=> $lang->setting_ougc_awards_postbit_maxperline,
		   'description'	=> $lang->setting_ougc_awards_postbit_maxperline_desc,
		   'optionscode'	=> 'text',
			'value'			=>	0,
		),
		'profile'	=> array(
		   'title'			=> $lang->setting_ougc_awards_profile,
		   'description'	=> $lang->setting_ougc_awards_profile_desc,
		   'optionscode'	=> 'text',
			'value'			=>	4,
		),
		'perpage'	=> array(
		   'title'			=> $lang->setting_ougc_awards_perpage,
		   'description'	=> $lang->setting_ougc_awards_perpage_desc,
		   'optionscode'	=> 'text',
			'value'			=>	20,
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
			'value'			=>	-1,
		),
		'enablestatspage'	=> array(
		   'title'			=> $lang->setting_ougc_awards_enablestatspage,
		   'description'	=> $lang->setting_ougc_awards_enablestatspage_desc,
		   'optionscode'	=> 'yesno',
		   'value'			=> 1
		),
		'welcomeblock'		=> array(
		   'title'			=> $lang->setting_ougc_awards_welcomeblock,
		   'description'	=> $lang->setting_ougc_awards_welcomeblock_desc,
		   'optionscode'	=> 'yesno',
		   'value'			=> 1
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
		),
		'presets_groups'	=> array(
			'title'			=> $lang->setting_ougc_awards_presets_groups,
			'description'	=> $lang->setting_ougc_awards_presets_groups_desc,
			'optionscode'	=> 'groupselect',
			'value'			=>	-1,
		),
		'presets_maximum'	=> array(
			'title'			=> $lang->setting_ougc_awards_presets_maximum,
			'description'	=> $lang->setting_ougc_awards_presets_maximum_desc,
			'optionscode'	=> 'numeric',
			'value'			=>	5,
		),
		'presets_post'	=> array(
			'title'			=> $lang->setting_ougc_awards_presets_post,
			'description'	=> $lang->setting_ougc_awards_presets_post_desc,
			'optionscode'	=> 'numeric',
			'value'			=>	10,
		),
		'presets_profile'	=> array(
			'title'			=> $lang->setting_ougc_awards_presets_profile,
			'description'	=> $lang->setting_ougc_awards_presets_profile_desc,
			'optionscode'	=> 'numeric',
			'value'			=>	10,
		),
	));

	// Add template group
	$templates = array(
		'welcomeblock'	=> '<li><a href="javascript:OUGC_Plugins.ViewAll(\'{$mybb->user[\'uid\']}\', \'1\');">{$lang->ougc_awards_welcomeblock}</a></li>',
		'award_image' => '<a href="{$mybb->settings[\'bburl\']}/awards.php?view={$award[\'aid\']}" title="{$award[\'name\']}"><img src="{$award[\'image\']}" alt="{$award[\'name\']}" style="max-width: 32px;" /></a>',
		'award_image_class' => '<a href="{$mybb->settings[\'bburl\']}/awards.php?view={$award[\'aid\']}" title="{$award[\'name\']}"><i class="{$award[\'image\']} huge fitted link icon"></i></a>',
		'global_menu'			=> '<li><a href="{$mybb->settings[\'bburl\']}/awards.php" class="portal" style="background: url(\'images/modcp/awards.png\') no-repeat left center;">{$lang->ougc_awards_global_menu}</a></li>',
		'js'				=> '<script type="text/javascript" src="{$mybb->asset_url}/jscripts/ougc_awards.js"></script>',
		'css'				=> '<link href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" integrity="sha384-wvfXpqpZZVQGK6TAh5PVlGOfQNHSoD2xbE+QkPxCAFlNEevoEH3Sl0sibVcOQVnN" crossorigin="anonymous">',
		'modcp_manage'					=> '<form action="{$url}" method="post">
<input type="hidden" name="manage" value="{$mybb->input[\'manage\']}" />
<input type="hidden" name="aid" value="{$mybb->input[\'aid\']}" />
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
	<tr>
		<td class="thead" colspan="2">
			<strong>{$lang->ougc_awards_modcp_title_give}</strong>
		</td>
	</tr>
	<tr>
		<td class="tcat" colspan="2">
			<strong>{$lang->ougc_awards_modcp_title_give_desc}</strong>
		</td>
	</tr>
	{$username}
	{$reason}
	{$thread}
	{$gived_list}
	{$multiple}
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
		multiple: true,
		ajax: {
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
	<td class="trow1" width="25%"><strong>{$lang->ougc_awards_modcp_gived}:</strong></td>
	<td class="trow1" width="75%">{$select}{$username}</td>
</tr>',
		'modcp_manage_username'		=> '<tr>
	<td class="trow1" width="25%"><strong>{$lang->ougc_awards_modcp_username}:</strong></td>
	<td class="trow1" width="75%"><input type="text" class="textbox" name="username" id="username" value="{$mybb->input[\'username\']}" size="25" /></td>
</tr>',
		'modcp_manage_thread'		=> '<tr>
	<td class="trow1" width="25%"><strong>{$lang->ougc_awards_modcp_thread}:</strong></td>
	<td class="trow1" width="75%"><input type="text" class="textbox" name="thread" size="35" maxlength="250" value="{$mybb->input[\'thread\']}"></td>
</tr>',
		'modcp_manage_username_hidden'		=> '<input type="hidden" name="username" value="{$mybb->input[\'username\']}" />',
		
		
		
		
		'profile_multipage'		=> '<tr>
	<td class="tfoot">{$multipage}</td>
</tr>',
		'modcp_nav'		=> '<tr><td class="trow1 smalltext"><a href="{$url}" class="modcp_nav_item" style="background: url(\'images/modcp/awards.png\') no-repeat left center;">{$lang->ougc_awards_modcp_nav}</a></td></tr>',
		'modcp'		=> '<html>
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
				{$multipage}
				{$button}
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
			<strong>{$category[\'name\']}</strong>
		</td>
	</tr>
	<tr>
		<td class="tcat smalltext" colspan="4">
			<strong>{$category[\'description\']}</strong>
		</td>
	</tr>
	{$awardlist}
</table>
<br class="clear" />',
		'modcp_list_button'	=> '<a href="{$url}" class="button float_right">{$message}</a>',
		'modcp_list_award'	=> '<tr>
	<td class="trow1" align="center" width="1%">{$award[\'fimage\']}</td>
	<td class="trow1" width="15%">{$award[\'name\']}</td>
	<td class="trow1">{$award[\'description\']}</td>
	<td class="trow1" align="center" width="15%">[<a href="{$mybb->settings[\'bburl\']}/{$url}&amp;manage=give&amp;aid={$award[\'aid\']}">{$lang->ougc_awards_modcp_give}</a>&nbsp;|&nbsp;<a href="{$mybb->settings[\'bburl\']}/{$url}&amp;manage=revoke&amp;aid={$award[\'aid\']}">{$lang->ougc_awards_modcp_revoke}</a>]</td>
</tr>',
		'modcp_manage_reason'	=> '<tr>
	<td class="trow2" width="25%"><strong>{$lang->ougc_awards_modcp_reason}:</strong></td>
	<td class="trow2" width="75%"><textarea type="text" class="textarea" name="reason" id="reason" rows="4" cols="40">{$mybb->input[\'reason\']}</textarea></td>
</tr>',
		'postbit'	=> '{$br}{$award[\'fimage\']}{$viewall}',
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
		{$award[\'fimage\']} {$award[\'reason\']} {$threadlink}
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
		'page_list_request'	=> '<td class="tcat smalltext" align="center" width="15%"><strong>{$lang->ougc_awards_page_list_request}</strong></td>',
		'page_list_award'	=> '<tr>
	<td class="{$trow}" align="center">{$award[\'fimage\']}</td>
	<td class="{$trow}"><a href="{$mybb->settings[\'bburl\']}/awards.php?view={$award[\'aid\']}">{$award[\'name\']}</a></td>
	<td class="{$trow}" colspan="{$colspan_trow}">{$award[\'description\']}</td>
	{$award_request}
</tr>',
		'page_list_award_request'	=> '<td class="{$trow} postbit_buttons post_management_buttons" align="center"><a href="javascript: void(0);" onclick="return OUGC_Plugins.RequestAward(\'{$award[\'aid\']}\'); return false;" title="{$lang->ougc_awards_page_list_request}" class="postbit_report"><span>{$lang->ougc_awards_page_list_request}</span></a></td>',
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
		<td class="tcat smalltext" width="10%"><strong>{$lang->ougc_awards_modcp_username}</strong></td>
		<td class="tcat smalltext" width="40%"><strong>{$lang->ougc_awards_modcp_reason}</strong></td>
		<td class="tcat smalltext" width="40%"><strong>{$lang->ougc_awards_modcp_thread}</strong></td>
		<td class="tcat smalltext" align="center" width="10%"><strong>{$lang->ougc_awards_page_view_date}</strong></td>
	</tr>
	{$users_list}
</table>
{$request_button}',
		'page_view_empty'	=> '<tr>
	<td class="trow1" colspan="4" align="center">{$lang->ougc_awards_page_view_empty}</td>
</tr>',
		'page_view_row'	=> '<tr>
	<td class="{$trow}">{$gived[\'username\']}</td>
	<td class="{$trow}">{$gived[\'reason\']}</td>
	<td class="{$trow}">{$threadlink}</td>
	<td class="{$trow}" align="center">{$gived[\'date\']}</td>
</tr>',
		'page_request_error'	=> '<tr>
		<td class="{$trow}">{$error}</td>
</tr>',
		'page_request'	=> '<div class="modal">
	<div style="overflow-y: auto; max-height: 400px;" class="modal_{$award[\'aid\']}">
			<form action="{$mybb->settings[\'bburl\']}/awards.php" method="post" onsubmit="javascript: return OUGC_Plugins.DoRequestAward(\'{$award[\'aid\']}\');" class="request_form_{$award[\'aid\']}">
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
	</div>

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
		<td class="{$trow}" align="center">{$award[\'fimage\']}<br />{$award[\'name\']}</td>
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
		'stats_user_viewall'	=> '<a href="javascript:OUGC_Plugins.ViewAll(\'{$uid}\', \'1\');" title="{$lang->ougc_awards_stats_viewall}">{$message}</a>',
		'global_notification'	=> '<div class="pm_alert">
	{$message}
</div><br />',
		'modcp_manage_multiple'	=> '<tr>
	<td class="trow2" colspan="2">
		<strong><label><input type="checkbox" class="checkbox" name="multiple" id="multiple" value="1"{$multiple_checked} /> {$lang->ougc_awards_modcp_multiple}</label></strong><br />
		<span class="smalltext">{$lang->ougc_awards_modcp_multiple_note}</span>
	</td>
</tr>',
		'modcp_requests_buttons'	=> '<br /><div align="center"><input type="submit" class="button" name="accept" value="{$lang->ougc_awards_modcp_requests_list_accept}" /> <input type="submit" class="button" name="reject" value="{$lang->ougc_awards_modcp_requests_list_reject}" /></div>',
		'modcp_requests_list'	=> '<form action="{$mybb->settings[\'bburl\']}/{$url}&amp;manage=requests" method="post">
<input type="hidden" name="my_post_key" value="{$mybb->post_code}" />
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
	<tr>
		<td class="thead" colspan="5">
			<strong>{$lang->ougc_awards_modcp_requests_list_title}</strong>
		</td>
	</tr>
	<tr>
		<td class="tcat smalltext" colspan="5">
			<strong>{$lang->ougc_awards_modcp_requests_list_desc}</strong>
		</td>
	</tr>
	{$requestslist}
</table>
{$buttons}
</form>
<br class="clear" />',
		'modcp_requests_list_empty'	=> '<tr>
	<td class="trow1" colspan="5" align="center">
		{$lang->ougc_awards_modcp_requests_list_empty}
	</td>
</tr>',
		'modcp_requests_list_item'	=> '<tr>
	<td class="trow1" align="center" width="1%">{$request[\'fimage\']}</td>
	<td class="trow1" width="15%">{$profilelink_formatted}</td>
	<td class="trow1">{$request[\'message\']}</td>
	<td class="trow1" align="center">{$status}</td>
	<td class="trow1" align="center" width="1%"><input type="checkbox" class="checkbox" name="selected[{$request[\'rid\']}]" value="1"{$checked} /></td>
</tr>',
		'page_request_success'	=> '<tr>
		<td class="{$trow}">{$lang->ougc_awards_redirect_request}</td>
</tr>',
		'viewall_error'	=> '<tr>
	<td class="trow1">
		{$lang->ougc_awards_profile_empty}
	</td>
</tr>',
		'viewall_multipage'	=> '{$multipage}',
		'viewall_row_empty'	=> '<tr>
	<td class="trow1">
		{$lang->ougc_awards_profile_empty}
	</td>
</tr>',
		'viewall'	=> '<div class="modal">
	<div style="overflow-y: auto; max-height: 400px;" class="modal_{$award[\'aid\']}">
			<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
				<tr>
					<td class="thead"><strong>{$title}</strong></td>
				</tr>
				{$content}
				<tr>
					<td class="tfoot" align="center">{$multipage}</td>
				</tr>
			</table>
	</div>
</div>',
		'usercp_list'	=> '<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
	<tr>
		<td class="thead" colspan="4">
			<strong>{$category[\'name\']}</strong>
		</td>
	</tr>
	<tr>
		<td class="tcat smalltext" colspan="4">
			<strong>{$category[\'description\']}</strong>
		</td>
	</tr>
	{$awardlist}
</table>
<br class="clear" />',
		'usercp_nav'	=> '<tr><td class="trow1 smalltext"><a href="{$url}" class="usercp_nav_item" style="background: url(\'images/modcp/awards.png\') no-repeat left center;">{$lang->ougc_awards_usercp_nav}</a></td></tr>',
		'page_empty'	=> '<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
	<tr>
		<td class="thead">
			<strong>{$lang->ougc_awards_page_title}</strong>
		</td>
	</tr>
	<tr>
		<td class="trow1">
			{$lang->ougc_awards_page_list_empty}
		</td>
	</tr>
</table>',
		'usercp_sort'	=> '<form action="{$url}" method="post">
<input type="hidden" name="manage" value="{$mybb->input[\'manage\']}" />
	<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
	<tr>
		<td class="thead" colspan="7">
			<strong>{$category[\'name\']}</strong>
		</td>
	</tr>
	<tr>
		<td class="tcat" width="16%" colspan="2">{$lang->ougc_awards_page_list_name}</td>
		<td class="tcat" width="34%">{$lang->ougc_awards_usercp_list_reason}</td>
		<td class="tcat" width="15%" align="center">{$lang->ougc_awards_usercp_list_from}</td>
		<td class="tcat" width="15%" align="center">{$lang->ougc_awards_page_view_date}</td>
		<td class="tcat" width="10%" align="center">{$lang->ougc_awards_usercp_list_visible}</td>
		<td class="tcat" width="10%" align="center">{$lang->ougc_awards_usercp_list_disporder}</td>
	</tr>
	{$awardlist}
</table>
<br />
<div align="center">
	{$gobutton}
</div>
</form>
<br />',
		'usercp_sort_award'	=> '<tr>
	<td class="trow1" align="center" width="1%">{$award[\'fimage\']}</td>
	<td class="trow1" width="15%">{$award[\'name\']}</td>
	<td class="trow1" width="34%">{$award[\'reason\']}</td>
	<td class="trow1" width="15%" align="center">{$award[\'ousername\']}</td>
	<td class="trow1" width="15%" align="center">{$award[\'date\']}</td>
	<td class="trow1" width="10%" align="center"><input type="checkbox" name="visible[{$award[\'gid\']}]" value="{$award[\'visible\']}" class="checkbox" {$checked} /></td>
	<td class="trow1" width="10%" align="center"><input type="text" name="disporder[{$award[\'gid\']}]" value="{$award[\'disporder\']}" class="textbox" size="2" maxlength="4" /></td>
</tr>',
		'usercp_sort_empty'	=> '<tr>
	<td class="trow1" align="center" colspan="7">{$lang->ougc_awards_page_list_empty}</td>
</tr>',
		'profile_preset' => '<tr>
	<td class="{$bg_color}"><strong>{$preset[\'name\']} {$lang->ougc_awards_presets_profile}</strong></td>
	<td class="{$bg_color}">{$presetlist}</td>
</tr>',
		'profile_preset_row' => '{$award[\'fimage\']}',
		'usercp_presets' => '{$preset_content}
<input type="hidden" name="manage" value="{$mybb->input[\'manage\']}" />
	<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
	<tr>
		<td class="thead">
			<strong>{$lang->ougc_awards_presets_title}</strong>
		</td>
	</tr>
	<tr>
		<td class="tcat">{$lang->ougc_awards_presets_desc}</td>
	</tr>
	<tr>
		<td class="trow1">{$preset_list}{$add_form}</td>
	</tr>
	{$preset_form}
</table>
<br />',
		'usercp_presets_addform' => '<form method="get" action="{$mybb->settings[\'bburl\']}/{$url}&manage=presets" style="display: inline; float: right;">
	<input type="hidden" name="action" value="awards" />
	<input type="hidden" name="manage" value="presets" />
	<input type="hidden" name="do" value="add" />
	<label>
		{$lang->ougc_awards_presets_addpreset}:
	</label>
	<input name="name" type="text" class="textbox" />
	{$gobutton}
</form>',
		'usercp_presets_award' => '<span data-id="{$award[\'gid\']}" class="item"><img src="{$award[\'image\']}" alt="{$award[\'name\']}" style="max-width: 32px;" /></span>',
		'usercp_presets_form' => '',
		'usercp_presets_select' => '<form method="get" action="{$mybb->settings[\'bburl\']}/{$url}&manage=presets" style="display: inline;" id="preset_select">
	<input type="hidden" name="action" value="awards" />
	<input type="hidden" name="manage" value="presets" />
	<label>
		{$lang->ougc_awards_presets_select}:
	</label>
	<select name="pid" onchange="ougc_awards_change_preset();">
		{$preset_options}
	</select>
	{$gobutton}
	{$setdefault}
	<input type="submit" class="button" name="delete" value="{$lang->ougc_awards_presets_delete}" />
</form>
<script>
	function ougc_awards_change_preset()
	{
		form = $(\'#preset_select\');

		if(!form.length)
		{
			return false;
		}

		form.trigger(\'submit\');
	}
</script>',
		'usercp_presets_select_option' => '<option value="{$preset[\'pid\']}"{$selected}>{$preset[\'name\']}</option>',
		'usercp_presets_setdefault' => '<input type="submit" class="button" name="setdefault" value="{$lang->ougc_awards_presets_setdefault}" />',
	);

	// Add templates
    $templatesDirIterator = new \DirectoryIterator(OUGC_AWARDS_ROOT.'/templates');

	//$templates = [];

    foreach($templatesDirIterator as $template)
    {
		if(!$template->isFile())
		{
			continue;
		}

		$pathName = $template->getPathname();

        $pathInfo = pathinfo($pathName);

		if($pathInfo['extension'] === 'html')
		{
            $templates[$pathInfo['filename']] = file_get_contents($pathName);
		}
    }

	if($templates)
	{
		$PL->templates('ougcawards', 'OUGC Awards', $templates);
	}

	// Modify templates
	require_once MYBB_ROOT.'/inc/adminfunctions_templates.php';
	find_replace_templatesets('postbit', '#'.preg_quote('{$post[\'user_details\']}').'#', '{$post[\'user_details\']}{$post[\'ougc_awards\']}{$post[\'ougc_awards_preset\']}');
	find_replace_templatesets('postbit_classic', '#'.preg_quote('{$post[\'user_details\']}').'#', '{$post[\'user_details\']}{$post[\'ougc_awards\']}{$post[\'ougc_awards_preset\']}');
	find_replace_templatesets('member_profile', '#'.preg_quote('{$signature}').'#', '{$signature}{$memprofile[\'ougc_awards\']}');
	find_replace_templatesets('member_profile', '#'.preg_quote('{$warning_level}').'#', '{$warning_level}{$memprofile[\'ougc_awards_preset\']}');
	find_replace_templatesets('modcp_nav_users', '#'.preg_quote('{$nav_ipsearch}').'#', '{$nav_ipsearch}<!--OUGC_AWARDS-->');
	find_replace_templatesets('usercp_nav_misc', '#'.preg_quote('{$lang->ucp_nav_view_profile}</a></td></tr>').'#', '{$lang->ucp_nav_view_profile}</a></td></tr><!--OUGC_AWARDS-->');
	find_replace_templatesets('modcp_nav', '#'.preg_quote('mcp_nav_editprofile}</a></td></tr>').'#', 'mcp_nav_editprofile}</a></td></tr><!--OUGC_AWARDS-->'); // 1.6
	find_replace_templatesets('header', '#'.preg_quote('{$pm_notice}').'#', '{$pm_notice}{$ougc_awards_requests}');
	find_replace_templatesets('header', '#'.preg_quote('{$menu_portal}').'#', '{$menu_portal}{$ougc_awards_menu}');
	find_replace_templatesets('stats', '#'.preg_quote('{$footer}').'#', '{$ougc_awards_most}{$ougc_awards_last}{$footer}');
	find_replace_templatesets('headerinclude', '#'.preg_quote('{$stylesheets}').'#', '{$stylesheets}{$ougc_awards_js}{$ougc_awards_css}');
	find_replace_templatesets('header_welcomeblock_member', '#'.preg_quote('{$searchlink}').'#', '{$ougc_awards_welcomeblock}{$searchlink}');

	// MyAlerts
	if(class_exists('MybbStuff_MyAlerts_AlertTypeManager'))
	{
		$alertTypeManager or $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $mybb->cache);

		$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

		$alertType = new MybbStuff_MyAlerts_Entity_AlertType();

		$alertType->setCode('ougc_awards');
		$alertType->setEnabled(true);
		$alertType->setCanBeUserDisabled(true);

		$alertTypeManager->add($alertType);
	}

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
	$awards->_db_verify_indexes();

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
				'description'	=> 'Default category created after an update.',
				'disporder'		=> 1
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
	}
	if($plugins['awards'] <= 1803)
	{
	}

	$awards->update_task_file();
	$awards->update_cache();
	/*~*~* RUN UPDATES END *~*~*/

	$plugins['awards'] = $info['versioncode'];
	$mybb->cache->update('ougc_plugins', $plugins);
}

// _deactivate() routine
function ougc_awards_deactivate()
{
	global $awards, $db, $cache;
	ougc_awards_pl_check();

	// Revert template edits
	require_once MYBB_ROOT.'/inc/adminfunctions_templates.php';
	find_replace_templatesets('postbit', '#'.preg_quote('{$post[\'ougc_awards\']}').'#', '', 0);
	find_replace_templatesets('postbit_classic', '#'.preg_quote('{$post[\'ougc_awards\']}').'#', '', 0);
	find_replace_templatesets('postbit', '#'.preg_quote('{$post[\'{$post[\'ougc_awards_preset\']}\']}').'#', '', 0);
	find_replace_templatesets('postbit_classic', '#'.preg_quote('{$post[\'{$post[\'ougc_awards_preset\']}\']}').'#', '', 0);
	find_replace_templatesets('member_profile', '#'.preg_quote('{$memprofile[\'ougc_awards\']}').'#', '', 0);
	find_replace_templatesets('member_profile', '#'.preg_quote('{$memprofile[\'ougc_awards_preset\']}').'#', '', 0);
	find_replace_templatesets('modcp_nav', '#'.preg_quote('<!--OUGC_AWARDS-->').'#', '', 0);//-1.8.7
	find_replace_templatesets('modcp_nav_users', '#'.preg_quote('<!--OUGC_AWARDS-->').'#', '', 0);
	find_replace_templatesets('usercp_nav_misc', '#'.preg_quote('<!--OUGC_AWARDS-->').'#', '', 0);
	find_replace_templatesets('header', '#'.preg_quote('{$ougc_awards_requests}').'#', '', 0);
	find_replace_templatesets('header', '#'.preg_quote('{$ougc_awards_menu}').'#', '', 0);
	find_replace_templatesets('stats', '#'.preg_quote('{$ougc_awards_most}').'#', '', 0);
	find_replace_templatesets('stats', '#'.preg_quote('{$ougc_awards_last}').'#', '', 0);
	find_replace_templatesets('headerinclude', '#'.preg_quote('{$ougc_awards_js}').'#', '', 0);
	find_replace_templatesets('headerinclude', '#'.preg_quote('{$ougc_awards_css}').'#', '', 0);
	find_replace_templatesets('header_welcomeblock_member', '#'.preg_quote('{$ougc_awards_welcomeblock}').'#', '', 0);

	// MyAlerts
	if(class_exists('MybbStuff_MyAlerts_AlertTypeManager'))
	{
		$alertTypeManager or $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);

		$alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

		$alertTypeManager->deleteByCode('ougc_awards');
	}

	$awards->update_task_file(0);

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

	$awards->update_task_file();
}

// _is_installed() routine
function ougc_awards_is_installed()
{
	global $db, $awards;

	foreach($awards->_db_tables() as $name => $table)
	{
		$installed = $db->table_exists($name);
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

	$awards->update_task_file(-1);

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

// ModCP/UserCP modules
function ougc_awards_modcp()
{
	global $mybb, $modcp_nav, $templates, $lang, $awards, $plugins, $usercpnav, $headerinclude, $header, $theme, $footer, $db, $gobutton;
	$awards->lang_load();

	$mybb->user['uid'] = (int)$mybb->user['uid'];

	$modcp = $plugins->current_hook == 'modcp_start' ? true : false;

	$url = $awards->build_url();

	if($modcp)
	{
		$permission = (bool)($mybb->settings['ougc_awards_modcp'] && ($mybb->settings['ougc_awards_modgroups'] == -1 || ($mybb->settings['ougc_awards_modgroups'] && $awards->check_groups($mybb->settings['ougc_awards_modgroups'], false))));

		if($permission)
		{
			$awards->lang_load();

			eval('$awards_nav = "'.$templates->get('ougcawards_modcp_nav').'";');
			$modcp_nav = str_replace('<!--OUGC_AWARDS-->', $awards_nav, $modcp_nav);
		}

		if(!$awards->get_input('manage'))
		{
			 $mybb->input['manage'] = 'default';
		}
	}
	else
	{
		eval('$awards_nav = "'.$templates->get('ougcawards_usercp_nav').'";');
		$usercpnav = str_replace('<!--OUGC_AWARDS-->', $awards_nav, $usercpnav);
		$modcp_nav = &$usercpnav;

		if(!$awards->get_input('manage'))
		{
			 $mybb->input['manage'] = 'sort';
		}
	}

	if($awards->get_input('action') != 'awards')
	{
		return;
	}

	if($modcp)
	{
		$permission or error_no_permission();

		add_breadcrumb($lang->nav_modcp, 'modcp.php');
	}
	else
	{
		$query = $db->simple_select('ougc_awards_owners', 'aid', "uid='{$mybb->user['uid']}'");
		while($owner_aids[] = (int)$db->fetch_field($query, 'aid'));
		$owner_aids = array_filter($owner_aids);

		add_breadcrumb($lang->nav_usercp, 'usercp.php');
	}

	add_breadcrumb($lang->ougc_awards_usercp_nav, $awards->build_url());

	if(!$modcp && $awards->get_input('manage') != 'sort')
	{
		if($awards->get_input('manage') == 'presets')
		{
			add_breadcrumb($lang->ougc_awards_presets_title, $awards->build_url('manage=presets'));
		}
		elseif($awards->get_input('manage') != 'sort')
		{
			add_breadcrumb($lang->ougc_awards_modcp_nav, $awards->build_url('manage=default'));
		}
	}

	$error = array();
	$button = $errors = $multipage = $content = '';

	$mybb->input['aid'] = $awards->get_input('aid', 1);
	$mybb->input['username'] = $awards->get_input('username');
	$mybb->input['reason'] = $awards->get_input('reason');
	$mybb->input['manage'] = $awards->get_input('manage');

	$_cache = $mybb->cache->read('ougc_awards');

	$where_cids = $where_aids = array();
	foreach($_cache['categories'] as $cid => $category)
	{
		$where_cids[] = (int)$cid;
	}

	$where_cids = implode("','", $where_cids);

	foreach($_cache['awards'] as $aid => $award)
	{
		if(isset($_cache['categories'][$award['cid']]))
		{
			$where_aids[] = (int)$aid;
		}
	}

	$where_aids = implode("','", $modcp ? $where_aids : $owner_aids);

	if($awards->get_input('page', 1) > 0)
	{
		$start = ($awards->get_input('page', 1) - 1)*$awards->query_limit;
	}
	else
	{
		$start = 0;
		$mybb->input['page'] = 1;
	}

	// We can give awards from the ModCP
	if($awards->get_input('manage') == 'requests')
	{
		add_breadcrumb($lang->ougc_awards_modcp_requests_nav, $awards->build_url('manage=requests'));

		$status = "='1'";
		if($awards->get_input('view') == 'logs')
		{
			add_breadcrumb($lang->ougc_awards_modcp_requests_list_viewlogs, $logs_url);
			$status = "!='1'";
		}

		if($mybb->request_method == 'post')
		{
			$selected = $awards->get_input('selected', 2);
			foreach($selected as $key => $value)
			{
				$selected_list[(int)$key] = 1;
			}

			$request_cache = array();

			if(!$selected_list)
			{
				$errors = inline_error($lang->ougc_awards_error_noneselected);
			}

			if(empty($errors))
			{
				$query = $db->simple_select('ougc_awards_requests', '*', "status{$status} AND aid IN ('{$where_aids}') AND rid IN ('".implode("','", array_keys($selected_list))."')");
				while($request = $db->fetch_array($query))
				{
					if(!$awards->can_edit_user($request['uid']))
					{
						$errors = inline_error($lang->ougc_awards_error_giveperm);
						break;
					}

					$request_cache[] = $request;
				}
			}

			if(empty($errors))
			{
				$awards->set_url('manage=requests');
				foreach($request_cache as $request)
				{
					if($awards->get_input('accept'))
					{
						$awards->accept_request($request['rid']);
						$awards->log_action();
					}
					else
					{
						$awards->reject_request($request['rid']);
						$awards->log_action();
					}
				}

				$awards->update_cache();

				if($awards->get_input('accept'))
				{
					$awards->redirect($lang->ougc_awards_redirect_request_accepted);
				}
				else
				{
					$awards->redirect($lang->ougc_awards_redirect_request_rejected);
				}
			}
		}

		$requestslist = $buttons = '';
		$query = $db->simple_select('ougc_awards_requests', '*', "status{$status} AND aid IN ('{$where_aids}')", array('limit_start' => $start, 'limit' => $awards->query_limit));
		if(!$db->num_rows($query))
		{
			eval('$requestslist = "'.$templates->get('ougcawards_modcp_requests_list_empty').'";');
		}
		else
		{
			while($request = $db->fetch_array($query))
			{
				$requests[] = $request;
				$uids[] = (int)$request['uid'];
			}

			$query2 = $db->simple_select('ougc_awards', '*', "aid IN ('{$where_aids}')");
			while($award = $db->fetch_array($query2))
			{
				$awards->set_cache('awards', $award['aid'], $award);
			}

			$query3 = $db->simple_select('users', 'uid, username, usergroup, displaygroup', "uid IN ('".implode("','", $uids)."')");
			while($user = $db->fetch_array($query3))
			{
				$awards->set_cache('users', $user['uid'], $user);
			}

			$query = $db->simple_select('ougc_awards_requests', 'COUNT(rid) AS requests', "status{$status} AND aid IN ('{$where_aids}')");
			$requestscount = (int)$db->fetch_field($query, 'requests');

			$multipage = (string)multipage($requestscount, $awards->query_limit, $awards->get_input('page', 1), $awards->build_url(($awards->get_input('view') != 'logs' ? 'manage=requests' : array('manage' => 'requests', 'view' => 'logs'))));

			$trow = alt_trow(true);
			foreach($requests as $request)
			{
				$award = &$request;

				$request['aid'] = (int)$request['aid'];
				$request['message'] = htmlspecialchars_uni($request['message']);
				$request['rid'] = (int)$request['rid'];

				$request['image'] = $awards->get_award_icon($request['aid']);
				$request['fimage'] = eval($templates->render($awards->get_award_info('template', $request['aid'])));

				if($name = $awards->get_award_info('name', $request['aid']))
				{
					$request['name'] = $name;
				}
				else
				{
					$award = $awards->get_award($request['aid']);
					$request['name'] = htmlspecialchars_uni($award['name']);
				}

				$user = $awards->get_user($request['uid']);

				switch($request['status'])
				{
					case -1:
						$status = $lang->ougc_awards_modcp_requests_list_status_rejected;
						break;
					case 0;
						$status = $lang->ougc_awards_modcp_requests_list_status_accepted;
						break;
					default:
						$status = $lang->ougc_awards_modcp_requests_list_status_pending;
						break;
				}

				$checked = '';
				if(!empty($selected_list[$request['rid']]))
				{
					$checked = ' checked="checked"';
				}
				$awards->get_input('view') != 'logs' or $checked = ' disabled="disabled"';

				$username = htmlspecialchars_uni($user['username'], $user['uid']);
				$profilelink = build_profile_link($username, $user['uid']);
				$username_formatted = format_name($username, $user['uid'], $user['usergroup'], $user['displaygroup']);
				$profilelink_formatted = build_profile_link($username_formatted, $user['uid']);

				eval('$requestslist .= "'.$templates->get('ougcawards_modcp_requests_list_item').'";');
				$trow = alt_trow();
				unset($award);
			}

			$awards->get_input('view') == 'logs' or $buttons = eval($templates->render('ougcawards_modcp_requests_buttons'));
		}

		if($awards->get_input('view') != 'logs')
		{
			$url = $awards->build_url(array('manage' => 'requests', 'view' => 'logs'));
			$message = $lang->ougc_awards_modcp_requests_list_viewlogs;
			$button = eval($templates->render('ougcawards_modcp_list_button'));
			$url = $awards->build_url(array('manage' => 'requests'));
		}

		$content = eval($templates->render('ougcawards_modcp_requests_list'));
	}
	elseif($awards->get_input('manage') == 'give')
	{
		if(!($award = $awards->get_award($awards->get_input('aid', 1))))
		{
			error($lang->ougc_awards_error_wrongaward);
		}

		if(!($category = $awards->get_category($award['cid'])))
		{
			error($lang->ougc_awards_error_invalidcategory);
		}

		if(!$modcp && !($owner = $awards->get_owner($award['aid'], $mybb->user['uid'])))
		{
			error($lang->ougc_awards_error_wrongowner);
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
			if(!$awards->get_input('multiple', 1))
			{
				$user = $awards->get_user_by_username($awards->get_input('username'));
				if(!$user)
				{
					$error[] = $lang->ougc_awards_error_invaliduser;
				}
				else
				{
					$users[] = $user;
				}
			}
			else
			{
				foreach(explode(',', $awards->get_input('username')) as $username)
				{
					$user = $awards->get_user_by_username($username);
					if(!$user)
					{
						$error[] = $lang->ougc_awards_error_invaliduser;
						break;
					}
					$users[] = $user;
				}
			}
			unset($user, $usernames, $username);

			foreach($users as $user)
			{
				if(!$awards->can_edit_user($user['uid']))
				{
					$error[] = $lang->ougc_awards_error_giveperm;
					break;
				}
			}

			if($awards->get_input('thread'))
			{
				if(!($thread = $awards->get_thread_by_url($awards->get_input('thread'))))
				{
					$error[] = $lang->ougc_awards_error_invalidthread;
				}
			}

			if(empty($error))
			{
				foreach($users as $user)
				{
					$awards->give_award($award, $user, $awards->get_input('reason'), !empty($thread['tid']) ? $thread['tid'] : 0);
					$awards->log_action();
				}
				$awards->redirect($lang->ougc_awards_redirect_gived);
			}
			else
			{
				$errors = inline_error($error);
			}

			$multiple_checked = '';
			if($awards->get_input('multiple', 1))
			{
				$multiple_checked = ' checked="checked"';
			}
		}

		$lang->ougc_awards_modcp_title_give = $lang->sprintf($lang->ougc_awards_modcp_title_give, $awards->get_award_info('name', $award['aid']));

		$gived_list = '';
		eval('$multiple = "'.$templates->get('ougcawards_modcp_manage_multiple').'";');
		eval('$username = "'.$templates->get('ougcawards_modcp_manage_username').'";');
		eval('$reason = "'.$templates->get('ougcawards_modcp_manage_reason').'";');
		eval('$thread = "'.$templates->get('ougcawards_modcp_manage_thread').'";');
		eval('$content = "'.$templates->get('ougcawards_modcp_manage').'";');
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

		if(!$modcp && !($owner = $awards->get_owner($award['aid'], $mybb->user['uid'])))
		{
			error($lang->ougc_awards_error_wrongowner);
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

		$lang->ougc_awards_modcp_title_give = $lang->sprintf($lang->ougc_awards_modcp_title_give, $awards->get_award_info('name', $award['aid']));

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
	}
	elseif(!$modcp && $awards->get_input('manage') == 'sort')
	{
		$categories = $cids = array();
		$awardlist = '';

		$catscount = count($_cache['categories']);

		if($mybb->request_method == 'post')
		{
			$updates = array();

			$disporder = $awards->get_input('disporder', 2);
			foreach($disporder as $key => $value)
			{
				$updates[(int)$key] = array('disporder' => (int)$value, 'visible' => 0);
			}

			$visible = $awards->get_input('visible', 2);
			foreach($visible as $key => $value)
			{
				$updates[(int)$key]['visible'] = 1;
			}

			if(!empty($updates))
			{
				foreach($updates as $gid => $data)
				{
					$awards->update_gived($gid, $data);
				}
			}
		}

		$query = $db->simple_select('ougc_awards_categories', '*', "visible='1'", array('limit_start' => $start, 'limit' => $awards->query_limit, 'order_by' => 'disporder'));
		if($db->num_rows($query))
		{
			while($category = $db->fetch_array($query))
			{
				$cids[] = (int)$category['cid'];
				$categories[(int)$category['cid']] = $category;
			}

			$multipage = (string)multipage($catscount, $awards->query_limit, $awards->get_input('page', 1), $awards->build_url());
		}

		// Query our data.
		$query = $db->query("
			SELECT u.*, u.disporder as user_disporder, u.visible as user_visible, a.*, ou.uid as ouid, ou.username as ousername, ou.usergroup as ousergroup, ou.displaygroup as odisplaygroup
			FROM ".TABLE_PREFIX."ougc_awards_users u
			LEFT JOIN ".TABLE_PREFIX."ougc_awards a ON (u.aid=a.aid)
			LEFT JOIN ".TABLE_PREFIX."users ou ON (u.oid=ou.uid)
			WHERE u.uid='".(int)$mybb->user['uid']."' AND a.visible='1' AND a.cid IN ('".implode("','", array_values($cids))."')
			ORDER BY u.disporder, u.date desc"
		);

		// Output our awards.
		if($db->num_rows($query))
		{
			while($award = $db->fetch_array($query))
			{
				$_awards[(int)$award['cid']][] = $award;
			}
		}

		$awardlist = '';
		if(!empty($categories))
		{
			foreach($categories as $cid => $category)
			{
				$awardlist = '';

				$category['name'] = htmlspecialchars_uni($category['name']);
				$category['description'] = htmlspecialchars_uni($category['description']);

				$trow = alt_trow(1);

				if(empty($_awards[(int)$category['cid']]))
				{
					$awardlist = eval($templates->render('ougcawards_usercp_sort_empty'));
				}
				else
				{
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
						if($reason = $awards->get_award_info('reason', $award['aid'], $award['gid'], $award['rid'], $award['tid']))
						{
							$award['reason'] = $reason;
						}
						else
						{
							$award['reason'] = htmlspecialchars_uni($award['reason']);
						}

						if(empty($award['reason']))
						{
							$award['reason'] = $lang->ougc_awards_pm_noreason;
						}

						$award['ousername'] = format_name(htmlspecialchars_uni($award['ousername']), $award['ousergroup'], $award['odisplaygroup']);

						$awards->parse_text($award['reason']);

						$award['image'] = $awards->get_award_icon($award['aid']);

						$award['disporder'] = (int)$award['user_disporder'];

						$award['visible'] = (int)$award['user_visible'];
						
						$checked = $award['visible'] ? ' checked="checked"' : '';

						$award['date'] = $lang->sprintf($lang->ougc_awards_profile_tine, my_date($mybb->settings['dateformat'], $award['date']), my_date($mybb->settings['timeformat'], $award['date']));

						$award['fimage'] = eval($templates->render($awards->get_award_info('template', $award['aid'])));
						eval('$awardlist .= "'.$templates->get('ougcawards_usercp_sort_award').'";');
						$trow = alt_trow();
					}
				}

				eval('$content .= "'.$templates->get('ougcawards_usercp_sort').'";');
			}
		}

		$content or $content = eval($templates->render('ougcawards_page_empty'));

		$url = $awards->build_url('manage=default');
		$message = $lang->ougc_awards_modcp_nav;
		$button .= eval($templates->render('ougcawards_modcp_list_button'));

		if(is_member($mybb->settings['ougc_awards_presets_groups']))
		{
			$url = $awards->build_url('manage=presets');

			$message = $lang->ougc_awards_presets_button;

			$button .= eval($templates->render('ougcawards_modcp_list_button'));
		}
	}
	elseif(!$modcp && $awards->get_input('manage') == 'presets')
	{
		if(!is_member($mybb->settings['ougc_awards_presets_groups']))
		{
			error_no_permission();
		}

		$categories = $cids = array();
		$awardlist = '';

		$pid = $mybb->get_input('pid', MyBB::INPUT_INT);

		$preset = false;

		if($pid)
		{
			$preset = $awards->get_preset($pid);

			if(!$preset || $preset['uid'] != $mybb->user['uid'])
			{
				error_no_permission();
			}
		}

		$query = $db->simple_select('ougc_awards_presets', '*', "uid='{$mybb->user['uid']}'");

		$total_presets = $db->num_rows($query);

		if($mybb->request_method == 'post')
		{
			if($preset)
			{
				foreach(['hiddenawards', 'visibleawards'] as $key)
				{
					${$key} = array_map('intval', json_decode( $mybb->get_input($key) ) );
	
					if(empty(${$key}))
					{
						${$key} = '';
					}
					else
					{
						${$key} = my_serialize( ${$key} );
					}
				}

				$result = $awards->update_preset([
					'hidden' => $hiddenawards,
					'visible' => $visibleawards,
				], $pid);
	
				if(!$result)
				{
					echo json_encode(['error' => $lang->ougc_awards_presets_error_message]);
	
					exit;
				}

				echo json_encode(['success' => $lang->ougc_awards_presets_success_message]);
	
				exit;
			}
			else
			{
			}
		}
		else
		{
			if($mybb->get_input('do') == 'add')
			{
				if($mybb->settings['ougc_awards_presets_maximum'] >= $total_presets)
				{
					error_no_permission();
				}

				$pid = $awards->insert_preset([
					'name' => $mybb->get_input('name'),
					'uid' => $mybb->user['uid']
				]);

				redirect($awards->build_url(['manage' => 'presets', 'pid' => $pid]));
			}

			if($mybb->get_input('setdefault'))
			{
				$db->update_query('users', [
					'ougc_awards_preset' => $pid,
				], "uid='{$mybb->user['uid']}'");

				redirect($awards->build_url(['manage' => 'presets', 'pid' => $pid]));
			}

			if($mybb->get_input('delete'))
			{
				$db->update_query('users', [
					'ougc_awards_preset' => 0,
				], "uid='{$mybb->user['uid']}'");
	
				$awards->delete_preset($pid);

				redirect($awards->build_url(['manage' => 'presets']));
			}
		}

		$preset_form = $preset_list = $add_form = '';

		$presets_cache = [];

		if($total_presets)
		{
			while($presets_cache[] = $db->fetch_array($query));

			$presets_cache = array_filter($presets_cache);

			$setdefault = '';

			(function ($presets_cache) use (&$preset_options, $pid, $templates) {
				$preset_options = '';

				foreach($presets_cache as $preset)
				{
					$preset['name'] = htmlspecialchars_uni($preset['name']);
	
					$selected = '';
	
					if($pid == $preset['pid'])
					{
						$selected = ' selected="selected"';	
					}
	
					$preset_options .= eval($templates->render('ougcawards_usercp_presets_select_option'));
				}
			})($presets_cache);

			if($pid && $pid != $mybb->user['ougc_awards_preset'])
			{
				$setdefault = eval($templates->render('ougcawards_usercp_presets_setdefault'));
			}

			$preset_list = eval($templates->render('ougcawards_usercp_presets_select'));
		}

		$add_form = '';

		if($mybb->settings['ougc_awards_presets_maximum'] > $total_presets)
		{
			$add_form = eval($templates->render('ougcawards_usercp_presets_addform'));
		}

		if($pid)
		{
			$query = $db->simple_select(
				'ougc_awards_categories',
				'*',
				"visible='1'",
				array(
					'order_by' => 'disporder'
				)
			);

			if($db->num_rows($query))
			{
				while($category = $db->fetch_array($query))
				{
					$cids[] = (int)$category['cid'];
				}
			}
	
			// Query our data.
			$query = $db->query("
				SELECT u.*, u.disporder as user_disporder, u.visible as user_visible, a.*, ou.uid as ouid, ou.username as ousername, ou.usergroup as ousergroup, ou.displaygroup as odisplaygroup
				FROM {$db->table_prefix}ougc_awards_users u
				LEFT JOIN {$db->table_prefix}ougc_awards a ON (u.aid=a.aid)
				LEFT JOIN {$db->table_prefix}users ou ON (u.oid=ou.uid)
				WHERE u.uid='{$mybb->user['uid']}' AND a.visible='1' AND a.cid IN ('".implode("','", array_values($cids))."')
				ORDER BY u.disporder, u.date desc"
			);
	
			// Output our awards.
			if($db->num_rows($query))
			{
				while($award = $db->fetch_array($query))
				{
					$_awards[(int)$award['gid']] = $award;
				}
			}
	
			$list_awards = [];

			$visibleawards = $hiddenawards = '';

			$visible_awards = array_flip( array_filter( (array)my_unserialize($preset['visible']) ) );

			foreach($visible_awards as $gid => $position)
			{
				$list_awards[] = $_awards[$gid];

				unset($_awards[$gid]);
			}

			foreach($_awards as $gid => $award)
			{
				$list_awards[] = $award;

				unset($_awards[$gid]);
			}

			foreach($list_awards as $award)
			{
				if(empty($award['gid']))
				{
					continue;
				}

				if($name = $awards->get_award_info('name', $award['aid']))
				{
					$award['name'] = $name;
				}
				if($description = $awards->get_award_info('description', $award['aid']))
				{
					$award['description'] = $description;
				}
				if($reason = $awards->get_award_info('reason', $award['aid'], $award['gid'], $award['rid'], $award['tid']))
				{
					$award['reason'] = $reason;
				}
				else
				{
					$award['reason'] = htmlspecialchars_uni($award['reason']);
				}

				if(empty($award['reason']))
				{
					$award['reason'] = $lang->ougc_awards_pm_noreason;
				}

				$award['ousername'] = format_name(htmlspecialchars_uni($award['ousername']), $award['ousergroup'], $award['odisplaygroup']);

				$awards->parse_text($award['reason']);

				$award['image'] = $awards->get_award_icon($award['aid']);

				$award['disporder'] = (int)$award['user_disporder'];

				$award['visible'] = (int)$award['user_visible'];

				$award['date'] = $lang->sprintf($lang->ougc_awards_profile_tine, my_date($mybb->settings['dateformat'], $award['date']), my_date($mybb->settings['timeformat'], $award['date']));

				$award['fimage'] = eval($templates->render($awards->get_award_info('template', $award['aid'])));

				if(isset($visible_awards[$award['gid']]))
				{
					$visibleawards .= eval($templates->render('ougcawards_usercp_presets_award'));
				}
				else
				{
					$hiddenawards .= eval($templates->render('ougcawards_usercp_presets_award'));
				}

				unset($award);
			}

			$sortable_js = eval($templates->render('ougcawards_usercp_presets_form_js'));

			$preset_form = eval($templates->render('ougcawards_usercp_presets_form'));
		}
	
		$content = eval($templates->render('ougcawards_usercp_presets'));

		$url = $awards->build_url('manage=awards');

		$message = $lang->ougc_awards_modcp_nav;

		$button .= '';
	}
	elseif($awards->get_input('manage') == 'default')
	{
		$categories = $cids = array();
		$awardlist = '';

		$catscount = count($_cache['categories']);

		$query = $db->simple_select('ougc_awards_categories', '*', "visible='1'", array('limit_start' => $start, 'limit' => $awards->query_limit, 'order_by' => 'disporder'));
		if($db->num_rows($query))
		{
			while($category = $db->fetch_array($query))
			{
				$cids[] = (int)$category['cid'];
				$categories[(int)$category['cid']] = $category;
			}

			$multipage = (string)multipage($catscount, $awards->query_limit, $awards->get_input('page', 1), $awards->build_url($modcp ? '' : 'manage=default'));

			foreach($_cache['awards'] as $aid => $award)
			{
				if(!$modcp && !in_array($aid, array_values($owner_aids)))
				{
					continue;
				}

				$award['aid'] = (int)$aid;
				$cached_items[$award['cid']][] = $award;
			}
		}

		foreach($categories as $cid => $category)
		{
			$awardlist = '';

			$category['name'] = htmlspecialchars_uni($category['name']);
			$category['description'] = htmlspecialchars_uni($category['description']);

			if(empty($cached_items[(int)$cid]))
			{
				$awardlist = eval($templates->render('ougcawards_modcp_list_empty'));
			}
			else
			{
				foreach($cached_items[(int)$cid] as $cid => $award)
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

					$award['fimage'] = eval($templates->render($awards->get_award_info('template', $award['aid'])));
					eval('$awardlist .= "'.$templates->get('ougcawards_modcp_list_award').'";');
				}
			}

			eval('$content .= "'.$templates->get('ougcawards_modcp_list').'";');
		}

		$content or $content = eval($templates->render('ougcawards_page_empty'));

		$url = $awards->build_url('manage=requests');
		$message = $lang->ougc_awards_modcp_requests_list_title;
		$button = eval($templates->render('ougcawards_modcp_list_button'));
	}

	eval('$page = "'.$templates->get('ougcawards_modcp').'";');
	output_page($page);
	exit;
}

// Show awards in profile function.
function ougc_awards_profile()
{
	global $mybb, $memprofile, $templates, $parser;
	global $db, $lang, $theme, $templates, $awards, $bg_color;

	$memprofile['ougc_awards'] = $memprofile['ougc_awards_preset'] = '';

	$display_awards = !(
		($awards->query_limit_profile < 1 && $awards->query_limit_profile != -1) ||
		my_strpos($templates->cache['member_profile'], '{$memprofile[\'ougc_awards\']}') === false
	);

	$display_preset = !(
		($awards->query_limit_preset_profile < 1 && $awards->query_limit_preset_profile != -1) ||
		my_strpos($templates->cache['member_profile'], '{$memprofile[\'ougc_awards_preset\']}') === false ||
		!is_member($mybb->settings['ougc_awards_presets_groups'], $memprofile)
	);

	if($display_preset)
	{
		$preset = $awards->get_preset($mybb->user['ougc_awards_preset']);

		if(empty($preset['visible']) || $preset['uid'] != $mybb->user['uid'])
		{
			$display_preset = false;
		}
	}

	if(!$display_awards && !$display_preset)
	{
		return;
	}

	$awards->lang_load();

	$awards->set_url(null, get_profile_link($memprofile['uid']));

	$categories = $cids = $thread_cache = $tids = array();

	$query = $db->simple_select('ougc_awards_categories', '*', "visible='1'", array('order_by' => 'disporder'));
	while($category = $db->fetch_array($query))
	{
		$cids[] = (int)$category['cid'];
		$categories[] = $category;
	}

	$whereclause = "u.visible=1 AND u.uid='".(int)$memprofile['uid']."' AND a.visible='1' AND a.type!='2' AND a.cid IN ('".implode("','", array_values($cids))."')";

	// Query our data.
	// Get awards
	$query = $db->simple_select(
		"ougc_awards_users u LEFT JOIN {$db->table_prefix}ougc_awards a ON (u.aid=a.aid)",
		"u.*, a.*",
		$whereclause,
		[
			'order_by' => 'u.disporder, u.date',
			'order_dir' => 'desc'
		]
	);

	$awardscount = (int)$db->num_rows($query);

	$start = 0;

	$page = 1;

	if($display_awards)
	{
		$page = $awards->get_input('view') == 'awards' ? $awards->get_input('page', 1) : 0;

		if($page > 0)
		{
			$start = ($page - 1)*$awards->query_limit_profile;

			if($page > ceil($awardscount/$awards->query_limit_profile))
			{
				$start = 0;
				$page = 1;
			}
		}
		// We want to keep $mybb->input['view'] intact for other plugins, ;)

		$multipage = (string)multipage($awardscount, $awards->query_limit_profile, $page, $awards->build_url('view=awards'));

		if($multipage)
		{
			$multipage = eval($templates->render('ougcawards_profile_multipage'));
		}
	}

	$presetlist = '';

	// Output our awards.
	if(!$db->num_rows($query))
	{
		if($display_awards)
		{
			$awardlist = eval($templates->render('ougcawards_profile_row_empty'));
		}
	}
	else
	{
		while($_awards[] = $db->fetch_array($query));

		$_awards = array_filter($_awards);

		$tids = array_filter(array_map('intval', array_column($_awards, 'thread')));

		if($display_awards)
		{
			if($tids)
			{
				$query = $db->simple_select('threads', 'tid, subject, prefix', "visible>0  AND closed NOT LIKE 'moved|%' AND tid IN ('".implode("','", $tids)."')");
				while($thread = $db->fetch_array($query))
				{
					$thread_cache[$thread['tid']] = $thread;
				}
			}
	
			require_once MYBB_ROOT."inc/class_parser.php";
			is_object($parser) or $parser = new postParser;

			$awardlist = '';

			if(!empty($_awards))
			{
				$category['name'] = htmlspecialchars_uni($category['name']);
				$category['description'] = htmlspecialchars_uni($category['description']);
	
				//eval('$awardlist .= "'.$templates->get('ougcawards_profile_row_category').'";');
	
				$parsed_awards = 0;

				$trow = alt_trow(1);

				foreach((function ($_awards) use ($awards, $start) {
	
					if($awards->query_limit_profile > 0)
					{
						$limit = $awards->query_limit_profile;

						$count = 0;

						foreach($_awards as $key => $award)
						{
							if($start > 0)
							{
								--$start;

								unset($_awards[$key]);

								continue;
							}

							++$count;

							if($count > $limit)
							{
								unset($_awards[$key]);
							}
						}
					}
	
					return $_awards;
				})($_awards) as $award)
				{
					++$parsed_awards;

					if($name = $awards->get_award_info('name', $award['aid']))
					{
						$award['name'] = $name;
					}
					if($description = $awards->get_award_info('description', $award['aid']))
					{
						$award['description'] = $description;
					}
					if($reason = $awards->get_award_info('reason', $award['aid'], $award['gid'], $award['rid'], $award['tid']))
					{
						$award['reason'] = $reason;
					}
					else
					{
						$award['reason'] = htmlspecialchars_uni($award['reason']);
					}
	
					if(empty($award['reason']))
					{
						$award['reason'] = $lang->ougc_awards_pm_noreason;
					}
	
					$threadlink = '';
					if($award['thread'] && $thread_cache[$award['thread']])
					{
						$thread = $thread_cache[$award['thread']];
	
						$thread['threadprefix'] = $thread['displayprefix'] = '';
						if($thread['prefix'])
						{
							$threadprefix = build_prefixes($thread['prefix']);
	
							if(!empty($threadprefix['prefix']))
							{
								$thread['threadprefix'] = htmlspecialchars_uni($threadprefix['prefix']).'&nbsp;';
								$thread['displayprefix'] = $threadprefix['displaystyle'].'&nbsp;';
							}
						}
	
						$thread['subject'] = $parser->parse_badwords($thread['subject']);
	
						$threadlink = '<a href="'.$settings['bburl'].'/'.get_thread_link($thread['tid']).'">'.$thread['displayprefix'].htmlspecialchars_uni($thread['subject']).'</a>';
					}
	
					$awards->parse_text($award['reason']);
	
					$award['image'] = $awards->get_award_icon($award['aid']);
	
					$award['date'] = $lang->sprintf($lang->ougc_awards_profile_tine, my_date($mybb->settings['dateformat'], $award['date']), my_date($mybb->settings['timeformat'], $award['date']));
	
					$award['fimage'] = eval($templates->render($awards->get_award_info('template', $award['aid'])));
					eval('$awardlist .= "'.$templates->get('ougcawards_profile_row').'";');
					$trow = alt_trow();

					if($awards->query_limit_preset_profile && $parsed_awards == $awards->query_limit_profile)
					{
						break;
					}
				}
			}
		}

		if($display_preset)
		{
			(function (&$_awards) {
				$awards = [];

				foreach($_awards as $award)
				{
					$awards[$award['gid']] = $award;
				}

				$_awards = $awards;
			})($_awards);
	
			$preset_awards = array_filter((array)my_unserialize($preset['visible']));

			$presetlist = '';
	
			$parsed_awards = 0;

			foreach($preset_awards as $gid)
			{
				$award = $_awards[$gid];

				if(empty($award))
				{
					continue;
				}

				++$parsed_awards;

				if($name = $awards->get_award_info('name', $award['aid']))
				{
					$award['name'] = $name;
				}
				if($description = $awards->get_award_info('description', $award['aid']))
				{
					$award['description'] = $description;
				}
				if($reason = $awards->get_award_info('reason', $award['aid'], $award['gid'], $award['rid'], $award['tid']))
				{
					$award['reason'] = $reason;
				}
				else
				{
					$award['reason'] = htmlspecialchars_uni($award['reason']);
				}

				if(empty($award['reason']))
				{
					$award['reason'] = $lang->ougc_awards_pm_noreason;
				}

				$awards->parse_text($award['reason']);

				$award['image'] = $awards->get_award_icon($award['aid']);

				$award['date'] = $lang->sprintf($lang->ougc_awards_profile_tine, my_date($mybb->settings['dateformat'], $award['date']), my_date($mybb->settings['timeformat'], $award['date']));

				$award['fimage'] = eval($templates->render($awards->get_award_info('template', $award['aid'])));

				$presetlist .= eval($templates->render('ougcawards_profile_preset_row'));

				if($awards->query_limit_preset_profile != -1 && $parsed_awards == $awards->query_limit_preset_profile)
				{
					break;
				}
			}
		}
	}

	if($display_awards)
	{
		$lang->ougc_awards_profile_title = $lang->sprintf($lang->ougc_awards_profile_title, htmlspecialchars_uni($memprofile['username']));
	
		$memprofile['ougc_awards'] = eval($templates->render('ougcawards_profile'));
	}

	if($presetlist)
	{
		$bg_color = alt_trow();

		$preset['name'] = htmlspecialchars_uni($preset['name']);

		$memprofile['ougc_awards_preset'] = eval($templates->render('ougcawards_profile_preset'));
	}
}

// Show awards in profile function.
function ougc_awards_postbit(&$post)
{
	global $plugins, $mybb, $templates, $awards, $lang, $db;

	$awards_cache = $mybb->cache->read('ougc_awards');

	$post['ougc_awards'] = $post['ougc_awards_preset'] = '';

	$post['uid'] = (int)$post['uid'];

	if($awards->query_limit_preset_postbit && is_member($mybb->settings['ougc_awards_presets_groups'], $post))
	{
		static $ougc_awards_presets_cache = null;

		static $ougc_awards_presets_awards_cache = null;

		if($ougc_awards_presets_cache === null)
		{
			$ougc_awards_presets_cache = $ougc_awards_presets_awards_cache = $preset_ids = [];

			$select = " LEFT JOIN {$db->table_prefix}users u ON (u.uid=ag.uid)";
	
			$where = " ag.uid='{$post['uid']}'";
	
			if(isset($GLOBALS['pids']))
			{
				$pids = implode("','", array_filter(array_unique(array_map('intval', explode("'", $GLOBALS['pids'])))));
	
				$select .= " LEFT JOIN {$db->table_prefix}posts p ON (p.uid=ag.uid)";
	
				$where = "p.pid IN ('{$pids}')";
			}
	
			$query = $db->simple_select(
				"ougc_awards a LEFT JOIN {$db->table_prefix}ougc_awards_users ag ON (ag.aid=a.aid){$select}",
				'ag.*, u.ougc_awards_preset',
				"ag.visible='1' AND {$where}",
				[
					'order_by' => 'ag.disporder, ag.date',
					'order_dir' => 'desc'
				]
			);
	
			$preset_ids = [];

			while($data = $db->fetch_array($query))
			{
				$preset_ids[(int)$data['ougc_awards_preset']] = (int)$data['ougc_awards_preset'];

				$ougc_awards_presets_awards_cache[$data['uid']][$data['gid']] = $data;
			}

			if($preset_ids)
			{
				$preset_ids = implode("','", $preset_ids);
		
				$query = $db->simple_select(
					"ougc_awards_presets",
					'*',
					"pid IN ('{$preset_ids}')"
				);

				while($preset = $db->fetch_array($query))
				{
					$ougc_awards_presets_cache[$preset['uid']] = $preset;
				}
			}
		}

		if(isset($ougc_awards_presets_cache[$post['uid']]))
		{
			$preset = $ougc_awards_presets_cache[$post['uid']];

			if(!empty($preset['visible']))
			{
				$preset['name'] = htmlspecialchars_uni($preset['name']);

				$visible_awards = array_filter((array)my_unserialize($preset['visible']));

				$conunt = 0;

				foreach($visible_awards as $position => $gid)
				{
					$award = $ougc_awards_presets_awards_cache[$post['uid']][$gid];
	
					if(empty($award['gid']))
					{
						continue;
					}
	
					++$count;

					if($name = $awards->get_award_info('name', $award['aid']))
					{
						$award['name'] = $name;
					}
					if($description = $awards->get_award_info('description', $award['aid']))
					{
						$award['description'] = $description;
					}
					if($reason = $awards->get_award_info('reason', $award['aid'], $award['gid'], $award['rid'], $award['tid']))
					{
						$award['reason'] = $reason;
					}
					else
					{
						$award['reason'] = htmlspecialchars_uni($award['reason']);
					}
	
					if(empty($award['reason']))
					{
						$award['reason'] = $lang->ougc_awards_pm_noreason;
					}
	
					$award['ousername'] = format_name(htmlspecialchars_uni($award['ousername']), $award['ousergroup'], $award['odisplaygroup']);
	
					$awards->parse_text($award['reason']);
	
					$award['image'] = $awards->get_award_icon($award['aid']);
	
					$award['disporder'] = (int)$award['user_disporder'];
	
					$award['visible'] = (int)$award['user_visible'];
	
					$award['date'] = $lang->sprintf($lang->ougc_awards_profile_tine, my_date($mybb->settings['dateformat'], $award['date']), my_date($mybb->settings['timeformat'], $award['date']));
	
					$award['fimage'] = eval($templates->render($awards->get_award_info('template', $award['aid'])));
	
					$visibleawards .= eval($templates->render('ougcawards_postbit_preset_award'));

					if($awards->query_limit_preset_postbit > 0 && $count == $awards->query_limit_preset_postbit)
					{
						break;
					}
				}

				if($visibleawards)
				{
					$post['ougc_awards_preset'] = eval($templates->render('ougcawards_postbit_preset'));
				}
			}
		}
	}

	$max_per_line = (int)$mybb->settings['ougc_awards_postbit_maxperline'];

	if($awards->query_limit_postbit < 1 && $awards->query_limit_postbit != -1)
	{
		return;
	}

	static $ougc_awards_cache = null;

	if(!isset($ougc_awards_cache))
	{
		global $db;
		$cids = array();

		foreach($awards_cache['categories'] as $cid => $category)
		{
			$cids[] = (int)$cid;
		}

		$whereclause = "AND a.visible='1' AND a.type!='1' AND a.cid IN ('".implode("','", array_values($cids))."')";

		// First we need to get our data
		if(THIS_SCRIPT == 'showthread.php' && isset($GLOBALS['pids']))
		{
			$ougc_awards_cache = array();

			$pids = array_filter(array_unique(array_map('intval', explode('\'', $GLOBALS['pids']))));
			$query = $db->query('
				SELECT ag.*
				FROM '.TABLE_PREFIX.'ougc_awards a
				JOIN '.TABLE_PREFIX.'ougc_awards_users ag ON (ag.aid=a.aid)
				JOIN '.TABLE_PREFIX.'posts p ON (p.uid=ag.uid)
				WHERE ag.visible=1 AND p.pid IN (\''.implode('\',\'', $pids).'\') '.$whereclause.'
				ORDER BY ag.disporder, ag.date desc'
			);
			// how to limit by uid here?
			// -- '.($awards->query_limit_postbit == -1 ? '' : 'LIMIT '.$awards->query_limit_postbit)

			while($data = $db->fetch_array($query))
			{
				$ougc_awards_cache[$data['uid']][$data['gid']] = $data;
			}
		}
		else
		{
			global $db;
			$ougc_awards_cache = array();

			$query = $db->query('
				SELECT ag.*
				FROM '.TABLE_PREFIX.'ougc_awards a
				JOIN '.TABLE_PREFIX.'ougc_awards_users ag ON (ag.aid=a.aid)
				WHERE ag.visible=1 AND ag.uid=\''.(int)$post['uid'].'\' '.$whereclause.'
				ORDER BY ag.disporder, ag.date desc
				'.($awards->query_limit_postbit == -1 ? '' : 'LIMIT '.$awards->query_limit_postbit)
			);
		
			while($data = $db->fetch_array($query))
			{
				$ougc_awards_cache[$data['uid']][$data['gid']] = $data;
			}
		}
	}

	// User has no awards
	if(empty($ougc_awards_cache[$post['uid']]))
	{
		return;
	}

	$awardlist = &$ougc_awards_cache[$post['uid']];

	$awards->lang_load();

	$count = $countbr = 0;

	$viewall = '';

	$total = count($awardlist);

	// Format the awards
	foreach($awardlist as $award)
	{
		$award = array_merge($awards->get_award($award['aid']), $award);

		$award['aid'] = (int)$award['aid'];
		if($name = $awards->get_award_info('name', $award['aid']))
		{
			$award['name'] = $name;
		}
		$award['name_ori'] = $award['name'];
		$award['name'] = strip_tags($award['name_ori']);

		$award['image'] = $awards->get_award_icon($award['aid']);

		if($awards->query_limit_postbit == -1 || $count < $awards->query_limit_postbit)
		{
			++$count;
			$br = '';

			if($max_per_line === 1 || $count === 1 || $countbr === $max_per_line)
			{
				$countbr = 0;
				$br = '<br class="ougc_awards_postbit_maxperline" />';
			}

			if($awards->query_limit_postbit != -1 && $count == $awards->query_limit_postbit && $total != $count)
			{
				$uid = $post['uid'];
				$message = $lang->ougc_awards_stats_viewall;
				eval('$viewall = "'.$templates->get('ougcawards_stats_user_viewall').'";');
			}

			if($reason = $awards->get_award_info('reason', $award['aid'], $award['gid'], $award['rid'], $award['tid']))
			{
				$award['reason'] = $reason;
			}
			else
			{
				$award['reason'] = htmlspecialchars_uni($award['reason']);
			}

			if(empty($award['reason']))
			{
				$award['reason'] = $lang->ougc_awards_pm_noreason;
			}

			$awards->parse_text($award['reason']);

			$award['fimage'] = eval($templates->render($awards->get_award_info('template', $award['aid']), 1, 0));
			eval('$new_award = "'.$templates->get('ougcawards_postbit', 1, 0).'";');
			$post['ougc_awards'] .= trim($new_award);

			++$countbr;
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

// MyAlerts alert formatter
function ougc_awards_global_start()
{
	if(class_exists('MybbStuff_MyAlerts_AlertFormatterManager'))
	{
		global $mybb, $lang, $awards;

		$formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::getInstance();

		$formatterManager or $formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::createInstance($mybb, $lang);

		$formatterManager->registerFormatter(new OUGC_Awards_MyAlerts_Formatter($mybb, $lang, 'ougc_awards'));
	}
}

// Global requests notification
function ougc_awards_global_intermediate()
{
	global $mybb, $awards, $lang, $templates, $ougc_awards_menu, $ougc_awards_requests, $ougc_awards_welcomeblock, $ougc_awards_js, $ougc_awards_css;
	$awards->lang_load();
	
	$ougc_awards_js = eval($templates->render('ougcawards_js'));
	
	$ougc_awards_css = eval($templates->render('ougcawards_css'));

	$ougc_awards_menu = eval($templates->render('ougcawards_global_menu'));

	$ougc_awards_requests = $ougc_awards_welcomeblock = '';

	if($mybb->settings['ougc_awards_welcomeblock'])
	{
		eval('$ougc_awards_welcomeblock = "'.$templates->get('ougcawards_welcomeblock').'";');
	}

	// TODO administratos should be able to manage requests from the ACP
	if(!$mybb->user['uid'])
	{
		return;
	}

	$ismod = ($mybb->usergroup['canmodcp'] && $mybb->settings['ougc_awards_modcp'] && ($mybb->settings['ougc_awards_modgroups'] == -1 || $awards->is_member($mybb->settings['ougc_awards_modgroups'])));
	$isuser = ($mybb->usergroup['canusercp'] && $mybb->user['ougc_awards_owner']);

	if(!$ismod && !$isuser)
	{
		return;
	}

	global $PL, $db;
	$PL or require_once PLUGINLIBRARY;

	$_cache = $PL->cache_read('ougc_awards');
	$pending = (int)$_cache['requests']['pending'];

	$script = 'modcp.php';

	if(!$ismod && $isuser && $pending)
	{
		if($aids = array_keys($_cache['awards']))
		{
			$query = $db->simple_select('ougc_awards_owners', 'aid', "uid='1' AND aid IN ('".implode("','", $aids)."')");

			$aids = array();

			while($aids[] = (int)$db->fetch_field($query, 'aid'));

			if($aids = array_filter($aids))
			{
				$query = $db->simple_select('ougc_awards_requests', 'COUNT(rid) AS pending', "status='1' AND aid IN ('".implode("','", $aids)."')");
				$pending = (int)$db->fetch_field($query, 'pending');

				$script = 'usercp.php';
			}
		}
	}

	if($pending < 1)
	{
		return;
	}

	$message = $lang->sprintf($lang->ougc_awards_page_pending_requests_moderator, $mybb->settings['bburl'], $script);
	if($pending > 1)
	{
		$message = $lang->sprintf($lang->ougc_awards_page_pending_requests_moderator_plural, $mybb->settings['bburl'], $script, my_number_format($pending));
	}

	$ougc_awards_requests = eval($templates->render('ougcawards_global_notification'));
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

		$query = $db->simple_select('users', 'uid, username, usergroup, displaygroup', "uid IN ('".implode("','", array_keys($stats['top']))."')");
		while($user = $db->fetch_array($query))
		{
			$_users[(int)$user['uid']] = $user;
		}

		$trow = alt_trow(true);
		foreach($stats['top'] as $uid => $total)
		{
			++$place;
			$username = htmlspecialchars_uni($_users[$uid]['username']);
			$username_formatted = format_name($_users[$uid]['username'], $_users[$uid]['usergroup'], $_users[$uid]['displaygroup']);
			$profilelink = build_profile_link($_users[$uid]['username'], $uid);
			$profilelink_formatted = build_profile_link(format_name($_users[$uid]['username'], $_users[$uid]['usergroup'], $_users[$uid]['displaygroup']), $uid);

			$message = $total;
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

// WOL Support
function ougc_awards_fetch_wol_activity_end(&$args)
{
	if($args['activity'] != 'unknown')
	{
		return;
	}

	if(my_strpos($args['location'], 'awards.php') === false)
	{
		return;
	}

	$args['activity'] = 'ougc_awards';
}

function ougc_awards_build_friendly_wol_location_end(&$args)
{
	global $awards, $lang, $settings;
	$awards->lang_load();

	if($args['user_activity']['activity'] == 'ougc_awards')
	{
		$args['location_name'] = $lang->sprintf($lang->ougc_awards_wol, $settings['bburl']);
	}
}

function ougc_awards_xmlhttp(&$args)
{
	global $mybb, $awards, $lang;

	if($mybb->get_input('action') != 'ougc_awards')
	{
		return;
	}

	$awards->lang_load();

	if($mybb->get_input('manage') == 'presets')
	{
		$mybb->input['ajax'] = 1;

		if(!is_member($mybb->settings['ougc_awards_presets_groups']))
		{
			error_no_permission();
		}

		$pid = $mybb->get_input('pid', MyBB::INPUT_INT);
	
		$preset = false;
	
		if($pid)
		{
			$preset = $awards->get_preset($pid);
	
			if(!$preset || $preset['uid'] != $mybb->user['uid'])
			{
				error_no_permission();
			}
		}

		if($mybb->request_method == 'post')
		{
			if($preset)
			{
				foreach(['hiddenawards', 'visibleawards'] as $key)
				{
					${$key} = array_map('intval', json_decode( $mybb->get_input($key) ) );
	
					if(empty(${$key}))
					{
						${$key} = '';
					}
					else
					{
						${$key} = my_serialize( ${$key} );
					}
				}
	
				$result = $awards->update_preset([
					'hidden' => $hiddenawards,
					'visible' => $visibleawards,
				], $pid);
	
				if(!$result)
				{
					echo json_encode(['error' => $lang->ougc_awards_presets_error_message]);
	
					exit;
				}
	
				echo json_encode(['success' => $lang->ougc_awards_presets_success_message]);
	
				exit;
			}
		}
	
		exit(json_encode($mybb->input));
	}
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

	// Hard-coded setting to disable imports
	public $allow_imports = false;

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
			switch(THIS_SCRIPT)
			{
				case 'usercp.php':
					$this->set_url(null, 'usercp.php?action=awards');
					break;
				default:
					$this->set_url(null, 'modcp.php?action=awards');
					break;
			}
		}

		$this->query_limit = (int)$mybb->settings['ougc_awards_perpage'];
		$this->query_limit_profile = (int)$mybb->settings['ougc_awards_profile'];
		$this->query_limit_postbit = (int)$mybb->settings['ougc_awards_postbit'];
		$this->query_limit_preset_profile = (int)$mybb->settings['ougc_awards_presets_profile'];
		$this->query_limit_preset_postbit = (int)$mybb->settings['ougc_awards_presets_post'];

		$this->myalerts = $mybb->settings['ougc_awards_myalerts'] && $mybb->cache->cache['plugins']['active']['myalerts'];
	}

	// List of tables
	function _db_tables()
	{
		return array(
			'ougc_awards'				=> array(
				'aid'					=> "int UNSIGNED NOT NULL AUTO_INCREMENT",
				'cid'					=> "int UNSIGNED NOT NULL DEFAULT '0'",
				'name'					=> "varchar(100) NOT NULL DEFAULT ''",
				'description'			=> "varchar(255) NOT NULL DEFAULT ''",
				'image'					=> "varchar(255) NOT NULL DEFAULT ''",
				'template'				=> "smallint(1) NOT NULL DEFAULT '0'",
				'disporder'				=> "smallint(5) NOT NULL DEFAULT '0'",
				'allowrequests'			=> "tinyint(1) NOT NULL DEFAULT '1'",
				'visible'				=> "smallint(1) NOT NULL DEFAULT '1'",
				'pm'					=> "text NOT NULL",
				'type'					=> "smallint(1) NOT NULL DEFAULT '0'",
				'prymary_key'			=> "aid"
			),
			'ougc_awards_users'			=> array(
				'gid'					=> "int UNSIGNED NOT NULL AUTO_INCREMENT",
				'uid'					=> "int UNSIGNED NOT NULL DEFAULT '0'",
				'oid'					=> "int UNSIGNED NOT NULL DEFAULT '0'",
				'aid'					=> "int UNSIGNED NOT NULL DEFAULT '0'",
				'rid'					=> "int UNSIGNED NOT NULL DEFAULT '0'",
				'tid'					=> "int UNSIGNED NOT NULL DEFAULT '0'",
				'thread'				=> "int UNSIGNED NOT NULL DEFAULT '0'",
				'reason'				=> "text NOT NULL",
				'date'					=> "int(10) NOT NULL DEFAULT '0'",
				'disporder'				=> "smallint NOT NULL DEFAULT '0'",
				'visible'				=> "smallint(1) NOT NULL DEFAULT '1'",
				'prymary_key'			=> "gid"
			),
			'ougc_awards_owners'		=> array(
				'oid'					=> "int UNSIGNED NOT NULL AUTO_INCREMENT",
				'uid'					=> "int UNSIGNED NOT NULL DEFAULT '0'",
				'aid'					=> "int UNSIGNED NOT NULL DEFAULT '0'",
				'date'					=> "int(10) NOT NULL DEFAULT '0'",
				'prymary_key'			=> "oid"
			),
			'ougc_awards_categories'	=> array(
				'cid'					=> "int UNSIGNED NOT NULL AUTO_INCREMENT",
				'name'					=> "varchar(100) NOT NULL DEFAULT ''",
				'description'			=> "varchar(255) NOT NULL DEFAULT ''",
				'disporder'				=> "smallint NOT NULL DEFAULT '0'",
				'allowrequests'			=> "tinyint(1) NOT NULL DEFAULT '1'",
				'visible'				=> "tinyint(1) NOT NULL DEFAULT '1'",
				'prymary_key'			=> "cid"
			),
			'ougc_awards_requests'		=> array(
				'rid'					=> "int UNSIGNED NOT NULL AUTO_INCREMENT",
				'aid'					=> "int UNSIGNED NOT NULL DEFAULT '0'",
				'uid'					=> "int UNSIGNED NOT NULL DEFAULT '0'",
				'muid'					=> "int UNSIGNED NOT NULL DEFAULT '0'",
				'message'				=> "text NOT NULL",
				'status'				=> "smallint(1) NOT NULL DEFAULT '1'",
				'prymary_key'			=> "rid"
			),
			'ougc_awards_tasks'			=> array(
				'tid'					=> "int UNSIGNED NOT NULL AUTO_INCREMENT",
				'name'					=> "varchar(100) NOT NULL DEFAULT ''",
				'description'			=> "varchar(255) NOT NULL DEFAULT ''",
				'disporder'				=> "smallint(5) NOT NULL DEFAULT '0'",
				'active'				=> "smallint(1) NOT NULL DEFAULT '1'",
				'logging'				=> "smallint(1) NOT NULL DEFAULT '1'",
				'requirements'			=> "varchar(200) NOT NULL DEFAULT ''",
				'give'					=> "text NOT NULL",
				'reason'				=> "text NOT NULL",
				'thread'				=> "int UNSIGNED NOT NULL DEFAULT '0'",
				'allowmultiple'			=> "smallint(1) NOT NULL DEFAULT '0'",
				'revoke'				=> "text NOT NULL",
				'usergroups'			=> "text NOT NULL",
				'additionalgroups'		=> "smallint(1) NOT NULL DEFAULT '1'",
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
				'previousawards'		=> "text NOT NULL",
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
				'prymary_key'			=> "tid"
			),
			'ougc_awards_tasks_logs'	=> array(
				'lid'					=> "int UNSIGNED NOT NULL AUTO_INCREMENT",
				'tid'					=> "int UNSIGNED NOT NULL DEFAULT '0'",
				'uid'					=> "int UNSIGNED NOT NULL DEFAULT '0'",
				'gave'					=> "text NOT NULL",
				'revoked'				=> "text NOT NULL",
				'date'					=> "int(10) NOT NULL DEFAULT '0'",
				'prymary_key'			=> "lid"
			),
			'ougc_awards_presets'	=> array(
				'pid'					=> "int UNSIGNED NOT NULL AUTO_INCREMENT",
				'uid'					=> "int UNSIGNED NOT NULL DEFAULT '0'",
				'name'					=> "varchar(100) NOT NULL DEFAULT ''",
				'hidden'				=> "text NULL",
				'visible'				=> "text NULL",
				'prymary_key'			=> "pid"
			)
		);
	}

	// List of columns
	function _db_columns()
	{
		return array(
			'users'			=> array(
				'ougc_awards' => 'text NOT NULL',
				'ougc_awards_owner' => "tinyint(1) NOT NULL DEFAULT '0'",
				'ougc_awards_preset' => "int NOT NULL DEFAULT '0'"
			),
		);
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

		if($db->index_exists('ougc_awards_users', 'uidaid'))
		{
			//$db->write_query('ALTER TABLE '.TABLE_PREFIX.'ougc_awards_users ADD UNIQUE KEY uidaid (uid,aid)');
			$db->drop_index('ougc_awards_users', 'uidaid');
		}
		if($db->index_exists('ougc_awards_users', 'aiduid'))
		{
			//$db->write_query('CREATE INDEX aiduid ON '.TABLE_PREFIX.'ougc_awards_users (aid,uid)');
			$db->drop_index('ougc_awards_users', 'aiduid');
		}
	}

	// Install/update task file
	function update_task_file($action=1)
	{
		global $db, $lang;
		$this->lang_load();

		if($action == -1)
		{
			$db->delete_query('tasks', "file='ougc_awards'");

			return;
		}

		$query = $db->simple_select('tasks', '*', "file='ougc_awards'", array('limit' => 1));
		$task = $db->fetch_array($query);

		if($task)
		{
			$db->update_query('tasks', array('enabled' => $action), "file='ougc_awards'");
		}
		else
		{
			include_once MYBB_ROOT.'inc/functions_task.php';

			$_ = $db->escape_string('*');

			$new_task = array(
				'title'			=> $db->escape_string($lang->setting_group_ougc_awards),
				'description'	=> $db->escape_string($lang->setting_group_ougc_awards_desc),
				'file'			=> $db->escape_string('ougc_awards'),
				'minute'		=> 0,
				'hour'			=> $_,
				'day'			=> $_,
				'weekday'		=> $_,
				'month'			=> $_,
				'enabled'		=> 1,
				'logging'		=> 1
			);

			$new_task['nextrun'] = fetch_next_run($new_task);

			$db->insert_query('tasks', $new_task);
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

		$limit = (int)$mybb->settings['statslimit'];

		$_cache= array(
			'time'			=> TIME_NOW,
			'awards'		=> array(),
			'categories'	=> array(),
			'requests'		=> array(),
			'tasks'			=> array(),
			'top'			=> array(),
			'last'			=> array()
		);

		$query = $db->simple_select('ougc_awards_categories', 'cid, name, description, allowrequests', "visible='1'", array('order_by' => 'disporder'));
		while($category = $db->fetch_array($query))
		{
			$_cache['categories'][(int)$category['cid']] = array(
				'name'			=> (string)$category['name'],
				'description'	=> (string)$category['description'],
				'allowrequests'	=> (int)$category['allowrequests']
			);
		}

		if($cids = array_keys($_cache['categories']))
		{
			$wherecids = "cid IN ('".implode("','", $cids)."')";
			$query = $db->simple_select('ougc_awards', 'aid, cid, name, template, description, image, allowrequests, type', "visible='1' AND {$wherecids}", array('order_by' => 'disporder'));
			while($award = $db->fetch_array($query))
			{
				$_cache['awards'][(int)$award['aid']] = array(
					'cid'			=> (int)$award['cid'],
					'name'			=> (string)$award['name'],
					'template'		=> (int)$award['template'],
					'description'	=> (string)$award['description'],
					'image'			=> (string)$award['image'],
					'allowrequests'	=> (int)$award['allowrequests'],
					'type'			=> (int)$award['type']
				);
			}
		}

		if($aids = array_keys($_cache['awards']))
		{
			$where = "aid IN ('".implode("','", $aids)."')";

			$query = $db->simple_select('ougc_awards_requests', 'COUNT(rid) AS pending', "status='1' AND {$where}");
			$pending = $db->fetch_field($query, 'pending');

			$_cache['requests'] = array(
				'pending'	=> (int)$pending
			);

			// Stats
			$query = $db->query("
				SELECT u.uid, a.awards
				FROM ".TABLE_PREFIX."users u
				LEFT JOIN (
					SELECT ua.uid, COUNT(ua.aid) AS awards
					FROM ".TABLE_PREFIX."ougc_awards_users ua
					LEFT JOIN ".TABLE_PREFIX."ougc_awards aw ON (aw.aid=ua.aid)
					WHERE ua.{$where} AND aw.{$wherecids} 
					GROUP BY ua.uid
				) a ON (u.uid=a.uid)
				WHERE a.awards!=''
				ORDER BY a.awards DESC
				LIMIT 0, {$limit}
			;");
			while($user = $db->fetch_array($query))
			{
				$_cache['top'][(int)$user['uid']] = (int)$user['awards'];
			}

			$query = $db->simple_select('ougc_awards_users', 'uid, date', $where, array('order_by' => 'date', 'order_dir' => 'desc', 'limit' => $limit));
			while($user = $db->fetch_array($query))
			{
				$_cache['last'][(int)$user['date']] = (int)$user['uid'];
			}
		}

		$query = $db->simple_select('ougc_awards_tasks', 'tid, name, reason', '', array('order_by' => 'disporder'));
		while($task = $db->fetch_array($query))
		{
			$_cache['tasks'][(int)$task['tid']] = array(
				'name'		=> (string)$task['name'],
				'reason'	=> (string)$task['reason']
			);
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
				'{imgdir}'	=> $theme['imgdir'],
				'{aid}'	=> $aid,
				'{cid}'	=> $award['cid']
			);

			$this->cache['images'][$aid] = str_replace(array_keys($replaces), array_values($replaces), $award['image']);
		}

		return $this->cache['images'][$aid];
	}

	function set_cache($type, $id, $data)
	{
		$this->cache[$type][$id] = $data;
	}

	function get_user($uid)
	{
		if(!isset($this->cache['users'][$uid]))
		{
			global $db;
			$this->cache['users'][$uid] = false;

			$query = $db->simple_select('users', '*', 'uid=\''.(int)$uid.'\'');
			$user = $db->fetch_array($query);
			if(isset($user['uid']))
			{
				$this->cache['users'][$uid] = $user;
			}
		}

		return $this->cache['users'][$uid];
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
	function get_request($rid)
	{
		if(!isset($this->cache['requests'][$rid]))
		{
			global $db;
			$this->cache['requests'][$rid] = false;

			$rid = (int)$rid;

			$query = $db->simple_select('ougc_awards_requests', '*', "rid='{$rid}'");
			$request = $db->fetch_array($query);
			if(isset($request['rid']))
			{
				$this->cache['requests'][$rid] = $request;
			}
		}

		return $this->cache['requests'][$rid];
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
		!isset($data['template']) or $cleandata['template'] = (int)$data['template'];
		!isset($data['disporder']) or $cleandata['disporder'] = (int)$data['disporder'];
		!isset($data['allowrequests']) or $cleandata['allowrequests'] = (int)$data['allowrequests'];
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
		!isset($data['muid']) or $cleandata['muid'] = (int)$data['muid'];
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
		$this->insert_request($data, $rid, true);
	}

	// Insert a new category to the DB
	function insert_category($data, $cid=null, $update=false)
	{
		global $db;

		$cleandata = array();

		!isset($data['name']) or $cleandata['name'] = $db->escape_string($data['name']);
		!isset($data['description']) or $cleandata['description'] = $db->escape_string($data['description']);
		!isset($data['disporder']) or $cleandata['disporder'] = (int)$data['disporder'];
		!isset($data['allowrequests']) or $cleandata['allowrequests'] = (int)$data['allowrequests'];
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

		foreach(array('active', 'logging', 'thread', 'allowmultiple', 'additionalgroups', 'disporder', 'posts', 'threads', 'fposts', 'fpostsforums', 'fthreads', 'fthreadsforums', 'registered', 'online', 'reputation', 'referrals', 'warnings', 'newpoints', 'mydownloads', 'myarcadechampions', 'myarcadescores', 'ougc_customrep_r', 'ougc_customrep_g', 'ougc_customrepids_r', 'ougc_customrepids_g') as $k)
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

		foreach(array('usergroups', 'give', 'revoke', 'previousawards', 'profilefields') as $k)
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
	function give_award($award, $user, $reason, $thread=0, $task=0, $rid=0)
	{
		global $db, $plugins, $mybb;

		$thread = (int)$thread;
		$task = (int)$task;
		$rid = (int)$rid;

		$args = array(
			'award'		=> &$award,
			'user'		=> &$user,
			'reason'	=> &$reason
		);

		$plugins->run_hooks('ougc_awards_give_award', $args);

		$this->aid = $award['aid'];
		$this->uid = $user['uid'];

		// Insert our gived award.
		$insert_data = array(
			'aid'		=> (int)$award['aid'],
			'uid'		=> (int)$user['uid'],
			'oid'		=> (int)$mybb->user['uid'],
			'tid'		=> $task,
			'thread'	=> $thread,
			'rid'	=> $rid,
			'reason'	=> $db->escape_string(trim($reason)),
			'date'		=> isset($award['TIME_NOW']) ? (int)$award['TIME_NOW'] : TIME_NOW
		);

		$gid = $db->insert_query('ougc_awards_users', $insert_data);

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

		$this->send_alert();

		return $gid;
	}

	// Grant owner status.
	function insert_owner($award, $user)
	{
		global $db, $plugins;

		$args = array(
			'award'		=> &$award,
			'user'		=> &$user
		);

		$plugins->run_hooks('ougc_awards_insert_owner', $args);

		$this->aid = (int)$award['aid'];
		$this->uid = (int)$user['uid'];

		// Insert our gived award.
		$insert_data = array(
			'aid'		=> $this->aid,
			'uid'		=> $this->uid,
			'date'		=> isset($award['TIME_NOW']) ? (int)$award['TIME_NOW'] : TIME_NOW
		);

		$db->insert_query('ougc_awards_owners', $insert_data);

		$db->update_query('users', array('ougc_awards_owner' => 1), "uid='{$this->uid}'");

		return true;
	}

	// Grant owner status.
	function revoke_owner($oid)
	{
		global $db, $plugins;

		$this->oid = (int)$oid;

		$plugins->run_hooks('ougc_awards_revoke_owner', $this);

		$owner = $this->get_owner(null, null, $oid);

		$db->delete_query('ougc_awards_owners', "oid='{$this->oid}'");

		$this->rebuild_owners($owner['uid']);
	}

	// Rebuild the owner status
	function rebuild_owners($uid=0)
	{
		global $db;

		$uids = array();

		if(!$uids)
		{
			$query = $db->simple_select('ougc_awards_owners', 'uid');

			while($uids[] = (int)$db->fetch_field($query, 'uid'));

			$uids = array_filter($uids);

			//$db->update_query('users', array('ougc_awards_owner' => 0));
		}

		$db->update_query('users', array('ougc_awards_owner' => 1), "uid IN ('".implode("','", $uids)."')");
	}

	// Grant owner status.
	function get_owner($aid, $uid, $oid=0)
	{
		global $db;

		$aid = (int)$aid;
		$uid = (int)$uid;
		$where = "aid='{$aid}' AND uid='{$uid}'";
		if(!$aid && !$uid && $oid)
		{
			$oid = (int)$oid;
			$where = "oid='{$oid}'";
		}

		$query = $db->simple_select('ougc_awards_owners', '*', $where);

		if($granted = $db->fetch_array($query))
		{
			return $granted;
		}

		return false;
	}

	function accept_request($rid)
	{
		global $lang, $mybb;
		$this->lang_load();

		$request = $this->get_request($rid);
		$award = $this->get_award($request['aid']);
		$user = $this->get_user($request['uid']);

		$this->give_award($award, $user, null, 0, 0, $rid);

		$this->update_request(array('status' => 0, 'muid' => $mybb->user['uid']), $rid);
	}

	function reject_request($rid)
	{
		global $lang, $mybb;
		$this->lang_load(true);

		$request = $this->get_request($rid);
		$award = $this->get_award($request['aid']);
		$user = $this->get_user($request['uid']);

		$this->aid = $award['aid'];
		$this->uid = $user['uid'];

		$this->send_pm(array(
			'subject'		=> $lang->sprintf($lang->ougc_awards_pm_noreason_request_rejected_subject, strip_tags($award['name'])),
			'message'		=> $lang->sprintf($lang->ougc_awards_pm_noreason_request_rejected_message, $user['username'], strip_tags($award['name'])),
			'touid'			=> $user['uid']
		), -1, true);

		$this->send_alert('reject_request');

		$this->update_request(array('status' => -1, 'muid' => $mybb->user['uid']), $rid);
	}

	// I liked as I did the pm thing, so what about award name, description, and reasons?
	function get_award_info($type, $aid, $gid=0, $rid=0, $tid=0)
	{
		global $lang, $cache;
		$this->lang_load(true);
		$aid = (int)$aid;

		if($type == 'template')
		{
			static $template_cache = array();

			if(!isset($template_cache[$aid]))
			{
				$template_cache[$aid] = 'ougcawards_award_image';

				$award = $this->get_award($aid);

				switch($award['template'])
				{
					case 2;
						global $templates;

						if(isset($templates->cache['ougcawards_award_image_cat'.$award['cid']]))
						{
							$template_cache[$aid] = 'ougcawards_award_image_cat'.$award['cid'];
						}

						if(isset($templates->cache['ougcawards_award_image'.$aid]))
						{
							$template_cache[$aid] = 'ougcawards_award_image'.$aid;
						}
						break;
					case 1;
						$template_cache[$aid] = 'ougcawards_award_image_class';
						break;
				}
			}

			return $template_cache[$aid];
		}

		if($type == 'pm')
		{
			if(!empty($lang->ougc_awards_award_pm_all))
			{
				return $lang->ougc_awards_award_pm_all;
			}
		}

		if($type == 'reason')
		{
			if($gid)
			{
				$lang_val = 'ougc_awards_award_reason_gived_'.(int)$gid;
				if(!empty($lang->$lang_val))
				{
					return $lang->$lang_val;
				}
			}

			if($rid)
			{
				$lang_val = 'ougc_awards_pm_noreason_request_accepted';
				if(!empty($lang->$lang_val))
				{
					return $lang->$lang_val;
				}
			}

			if($tid)
			{
				$_cache = $cache->read('ougc_awards');

				if(isset($_cache['tasks'][$tid]))
				{
					$lang_val = 'ougc_awards_task_reason'.$tid;
					isset($lang->$lang_val) or $lang->$lang_val = (string)$_cache['tasks'][$tid]['reason'];

					if(!empty($lang->$lang_val))
					{
						return $lang->$lang_val;
					}
				}
			}

			$lang_val = 'ougc_awards_award_reason_'.(int)$aid;
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

			global $cache;

			$awards = $cache->read('ougc_awards');

			if(isset($awards['awards'][$aid][$type]))
			{
				return $awards['awards'][$aid][$type];
			}
			
			return false;
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

	// Completely removes an award data from the DB
	function delete_task($tid)
	{
		global $db;
		$this->tid = (int)$tid;

		$db->delete_query('ougc_awards_tasks', 'tid=\''.$this->tid.'\'');
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
		!isset($data['thread']) or $cleandata['thread'] = (int)$data['thread'];
		!isset($data['visible']) or $cleandata['visible'] = (int)$data['visible'];
		!isset($data['disporder']) or $cleandata['disporder'] = (int)$data['disporder'];

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

	// Insert an alert
	function send_alert($type='give_award')
	{
		global $lang, $mybb, $alertType, $db;
		$this->lang_load(true);

		if(!($this->myalerts && class_exists('MybbStuff_MyAlerts_AlertTypeManager')))
		{
			return false;
		}

		switch($type)
		{
			case 'reject_request':
				break;
			default:
				break;
		}

		$alertType = MybbStuff_MyAlerts_AlertTypeManager::getInstance()->getByCode('ougc_awards');

		if(!$alertType)
		{
			return false;
		}

		// Check if already alerted
		$query = $db->simple_select('alerts', 'id', "object_id='{$this->aid}' AND uid='{$this->uid}' AND unread=1 AND alert_type_id='{$alertType->getId()}'");

		if($db->fetch_field($query, 'id'))
		{
			return false;
		}

		if($alertType != null && $alertType->getEnabled())
		{
			$alert = new MybbStuff_MyAlerts_Entity_Alert($this->uid, $alertType, $this->aid);

			$alert->setExtraDetails(
				array(
					'type'       => $type
				)
			);

			MybbStuff_MyAlerts_AlertManager::getInstance()->addAlert($alert);
		}
	}

	// Get a tid from input
	// Most of this was taken from @Starpaul20's Move Post plugin (https://github.com/PaulBender/Move-Posts)
	function get_thread_by_url($threadurl)
	{
		global $db;

		// Google SEO URL support
		if($db->table_exists("google_seo"))
		{
			// Build regexp to match URL.
			$regexp = "{$mybb->settings['bburl']}/{$mybb->settings['google_seo_url_threads']}";

			if($regexp)
			{
				$regexp = preg_quote($regexp, '#');
				$regexp = str_replace('\\{\\$url\\}', '([^./]+)', $regexp);
				$regexp = str_replace('\\{url\\}', '([^./]+)', $regexp);
				$regexp = "#^{$regexp}$#u";
			}

			// Fetch the (presumably) Google SEO URL:
			$url = $threadurl;

			// $url can be either 'http://host/Thread-foobar' or just 'foobar'.

			// Kill anchors and parameters.
			$url = preg_replace('/^([^#?]*)[#?].*$/u', '\\1', $url);

			// Extract the name part of the URL.
			$url = preg_replace($regexp, '\\1', $url);

			// Unquote the URL.
			$url = urldecode($url);

			// If $url was 'http://host/Thread-foobar', it is just 'foobar' now.

			// Look up the ID for this item.
			$query = $db->simple_select("google_seo", "id", "idtype='4' AND url='".$db->escape_string($url)."'");
			$tid = $db->fetch_field($query, 'id');
		}

		// explode at # sign in a url (indicates a name reference) and reassign to the url
		$realurl = explode("#", $threadurl);
		$threadurl = $realurl[0];

		// Are we using an SEO URL?
		if(substr($threadurl, -4) == "html")
		{
			// Get thread to move tid the SEO way
			preg_match("#thread-([0-9]+)?#i", $threadurl, $threadmatch);
			preg_match("#post-([0-9]+)?#i", $threadurl, $postmatch);
			
			if($threadmatch[1])
			{
				$parameters['tid'] = $threadmatch[1];
			}
			
			if($postmatch[1])
			{
				$parameters['pid'] = $postmatch[1];
			}
		}
		else
		{
			// Get thread to move tid the normal way
			$splitloc = explode(".php", $threadurl);
			$temp = explode("&", my_substr($splitloc[1], 1));

			if(!empty($temp))
			{
				for($i = 0; $i < count($temp); $i++)
				{
					$temp2 = explode("=", $temp[$i], 2);
					$parameters[$temp2[0]] = $temp2[1];
				}
			}
			else
			{
				$temp2 = explode("=", $splitloc[1], 2);
				$parameters[$temp2[0]] = $temp2[1];
			}
		}

		if($parameters['pid'] && !$parameters['tid'])
		{
			$post = get_post($parameters['pid']);
			$tid = $post['tid'];
		}
		elseif($parameters['tid'])
		{
			$tid = $parameters['tid'];
		}

		return get_thread($tid);
	}

	// Importer
	function run_importer()
	{
		global $plugins;

		$awards = &$this;

		if(!$this->allow_imports || !($type = $awards->get_input('ougc_awards_import')))
		{
			return;
		}

		switch($type)
		{
			case 'nickawards';
				$name = 'Nickman\'s';
				$tables = array('awards' => 'awards', 'users' => 'awards_given');
				$keys = array('name' => 'name', 'description' => '', 'image' => 'image', 'original_id' => 'id', 'original_id_u' => 'award_id', 'uid' => 'to_uid', 'reason' => 'reason', 'TIME_NOW' => 'date_given');
				$img_prefix = '{bburl}/images/awards/';
				$lang_var = 'ougc_awards_import_confirm_nickawards';
				break;
			default;
				$name = 'MyAwards';
				$tables = array('awards' => 'myawards', 'users' => 'myawards_users');
				$keys = array('name' => 'awname', 'description' => 'awdescr', 'image' => 'awimg', 'original_id' => 'awid', 'original_id_u' => 'awid', 'uid' => 'awuid', 'reason' => 'awreason', 'TIME_NOW' => 'awutime');
				$img_prefix = '{bburl}/uploads/awards/';
				$lang_var = 'ougc_awards_import_confirm_myawards';
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

			if(!$db->table_exists($tables['awards']))
			{
				flash_message($lang->sprintf($lang->ougc_awards_import_error, $tables['awards']), 'error');
				admin_redirect("index.php?module=config-plugins");
			}

			$query = $db->simple_select('ougc_awards_categories', 'MAX(disporder) AS max_disporder');
			$disporder = (int)$db->fetch_field($query, 'max_disporder');

			$awards->insert_category(array(
				'name'			=> 'Imported '.$name.' Awards',
				'description'	=> 'Automatic category created after an import.',
				'allowrequests'	=> 0,
				'disporder'		=> ++$disporder
			));

			$disporder = 0;

			$query = $db->simple_select($tables['awards']);
			while($award = $db->fetch_array($query))
			{
				$insert_award = array(
					'cid'			=> $awards->cid,
					'name'			=> (string)$award[$keys['name']],
					'description'	=> (string)$award[$keys['description']],
					'image'			=> $img_prefix.$award[$keys['image']],
					'disporder'		=> isset($award[$keys['disporder']]) ? (int)$award[$keys['disporder']] : ++$disporder,
					'allowrequests'	=> 0,
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
					'aid'			=> $cache_awards[$award[$keys['original_id_u']]]['aid'],
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

			if($reason = $this->get_award_info('reason', $gived['aid'], $gived['gid'], $gived['rid'], $gived['tid']))
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

		if(!$db->table_exists('ougc_customrep'))
		{
			return false;
		}

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

	function insert_preset($data)
	{
		global $db;

		$pid = (int)$pid;

		$insert_data = [];

		if(isset($data['uid']))
		{
			$insert_data['uid'] = (int)$data['uid'];
		}

		if(isset($data['name']))
		{
			$insert_data['name'] = $db->escape_string($data['name']);
		}

		if(isset($data['hidden']))
		{
			$insert_data['hidden'] = $db->escape_string($data['hidden']);
		}

		if(isset($data['visible']))
		{
			$insert_data['visible'] = $db->escape_string($data['visible']);
		}

		return $db->insert_query('ougc_awards_presets', $insert_data);
	}

	function update_preset($data, $pid)
	{
		global $db;

		$pid = (int)$pid;

		$update_data = [];

		if(isset($data['uid']))
		{
			$update_data['uid'] = (int)$data['uid'];
		}

		if(isset($data['name']))
		{
			$update_data['name'] = $db->escape_string($data['name']);
		}

		if(isset($data['hidden']))
		{
			$update_data['hidden'] = $db->escape_string($data['hidden']);
		}

		if(isset($data['visible']))
		{
			$update_data['visible'] = $db->escape_string($data['visible']);
		}

		return $db->update_query('ougc_awards_presets', $update_data, "pid='{$pid}'");
	}

	function get_preset($pid)
	{
		global $db;

		$pid = (int)$pid;

		$query = $db->simple_select('ougc_awards_presets', '*',"pid='{$pid}'");

		return $db->fetch_array($query);
	}

	function delete_preset($pid)
	{
		global $db;

		$pid = (int)$pid;

		$db->delete_query('ougc_awards_presets', "pid='{$pid}'");

		return true;
	}
}

if(class_exists('MybbStuff_MyAlerts_Formatter_AbstractFormatter'))
{
	class OUGC_Awards_MyAlerts_Formatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter
	{
		public function init()
		{
			global $awards;
			$awards->lang_load();
		}

		/**
		 * Format an alert into it's output string to be used in both the main alerts listing page and the popup.
		 *
		 * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to format.
		 *
		 * @return string The formatted alert string.
		 */
		public function formatAlert(MybbStuff_MyAlerts_Entity_Alert $alert, array $outputAlert)
		{
			global $awards, $templates;

			$Details = $alert->toArray();
			$ExtraDetails = $alert->getExtraDetails();
			$award = $awards->get_award($Details['object_id']);

			if($name = $awards->get_award_info('name', $award['aid']))
			{
				$award['name'] = $name;
			}

			$award['image'] = $awards->get_award_icon($award['aid']);
			$award['fimage'] = eval($templates->render($awards->get_award_info('template', $award['aid'])));

			/*$FromUser = $alert->getFromUser();
			$FromUser['avatar'] = $award['image'];
			$alert->setFromUser($FromUser);*/

			return $this->lang->sprintf($this->lang->ougc_awards_myalerts, $outputAlert['username'], $outputAlert['from_user'], $award['name'], $award['fimage']);
		}

		/**
		 * Build a link to an alert's content so that the system can redirect to it.
		 *
		 * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to build the link for.
		 *
		 * @return string The built alert, preferably an absolute link.
		 */
		public function buildShowLink(MybbStuff_MyAlerts_Entity_Alert $alert)
		{
			global $settings;

			$Details = $alert->toArray();
			$ExtraDetails = $alert->getExtraDetails();

			return $settings['bburl'].'/awards.php?view='.(int)$Details['object_id'];
		}
	}
}

$GLOBALS['awards'] = new OUGC_Awards;
