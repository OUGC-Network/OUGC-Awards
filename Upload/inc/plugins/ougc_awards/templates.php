<?php
 
/*
 * OUGC Awards plugin
 * Author: Omar Gonzalez.
 * Copyright: © 2012 Omar Gonzalez, All Rights Reserved
 * 
 * Website: http://www.udezain.com.ar
 *
 * This plugin will extend your forum with a powerful Awards System.
 *
************************************************************

 *
 * This plugin is under uDezain free plugins license. In short:
 * ============================================================
 * 1.- You may edit whatever you want to fit your needs without premission.
 * 2.- You MUST NOT redistribute this or any modified version of this plugin by any means without the author written permission.
 * 3.- You MUST NOT remove any license comments in any file that comes with this plugin pack.
 *
 * By downloading / installing / using this plugin you accept these conditions and the full attached license.
 * If no license file was attached within this plugin pack, you can read it in the following places:
 * 	1.- http://www.udezain.com.ar/eula-free.txt
 * 	2.- http://www.udezain.com.ar/eula-free.php
************************************************************/

return array(
	'modcp_ougc_awards_manage' => array(
		'version' => 1607,
		'content' => '<form action="modcp.php" method="post">
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
	),
	'modcp_ougc_awards_nav' => array(
		'version' => 1607,
		'content' => '<tr><td class="trow1 smalltext"><a href="modcp.php?action=awards" class="modcp_nav_item" style="background: url(\'images/ougc_awards/icon.png\') no-repeat left center;">{$lang->ougc_awards_modcp_nav}</a></td></tr>',
	),
	'modcp_ougc_awards' => array(
		'version' => 1607,
		'content' => '<html>
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
	),
	'modcp_ougc_awards_list_empty' => array(
		'version' => 1607,
		'content' => '<tr>
		<td class="trow1" colspan="4" align="center">
			{$lang->ougc_awards_modcp_list_empty}
		</td>
	</tr>',
	),
	'modcp_ougc_awards_list' => array(
		'version' => 1608,
		'content' => '<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
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
		{$awards}
	</table>
	<span class="smalltext">{$lang->ougc_awards_modcp_list_note}</span>',
	),
	'modcp_ougc_awards_list_award' => array(
		'version' => 1607,
		'content' => '<tr>
		<td class="trow1" align="center" width="1%"><a href="{$mybb->settings[\'bburl\']}/awards.php?view={$award[\'aid\']}" title="{$award[\'name\']}"><img src="{$award[\'image\']}" alt="{$award[\'name\']}" /></a></td>
		<td class="trow1" width="15%">{$award[\'name\']}</td>
		<td class="trow1">{$award[\'description\']}</td>
		<td class="trow1" align="center" width="15%">[<a href="{$mybb->settings[\'bburl\']}/modcp.php?action=awards&amp;manage=give&amp;aid={$award[\'aid\']}">{$lang->ougc_awards_modcp_give}</a>&nbsp;|&nbsp;<a href="{$mybb->settings[\'bburl\']}/modcp.php?action=awards&amp;manage=revoke&amp;aid={$award[\'aid\']}">{$lang->ougc_awards_modcp_revoke}</a>]</td>
	</tr>',
	),
	'modcp_ougc_awards_manage_reason' => array(
		'version' => 1607,
		'content' => '<tr>
		<td class="trow2" width="25%"><strong>{$lang->ougc_awards_modcp_reason}:</strong></td>
		<td class="trow2" width="75%"><textarea type="text" class="textarea" name="reason" id="reason" rows="4" cols="40">{$mybb->input[\'reason\']}</textarea></td>
	</tr>',
	),
	'postbit_ougc_awards' => array(
		'version' => 1607,
		'content' => '{$br}<a href="{$mybb->settings[\'bburl\']}/awards.php?view={$award[\'aid\']}" title="{$award[\'name\']}"><img src="{$award[\'image\']}" alt="{$award[\'name\']}" /></a>',
	),
	'member_profile_ougc_awards' => array(
		'version' => 1607,
		'content' => '<br />
	<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
	<tr>
	<td class="thead" colspan="2"><a style="float:right;" href="{$mybb->settings[\'bburl\']}/awards.php?user={$memprofile[\'uid\']}">{$lang->ougc_awards_profile_viewall}</a><strong>{$lang->ougc_awards_profile_title}</strong></td>
	</tr>
	{$awards}
	</table>
	{$multipage}',
	),
	'member_profile_ougc_awards_row' => array(
		'version' => 1607,
		'content' => '<tr>
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
	),
	'member_profile_ougc_awards_row_empty' => array(
		'version' => 1607,
		'content' => '<tr>
		<td class="trow1" colspan="2">
			{$lang->ougc_awards_profile_empty}
		</td>
	</tr>',
	),
	'ougc_awards_page' => array(
		'version' => 1607,
		'content' => '<html>
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
	),
	'ougc_awards_page_list' => array(
		'version' => 1607,
		'content' => '<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
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
	),
	'ougc_awards_page_list_award' => array(
		'version' => 1607,
		'content' => '<tr>
		<td class="{$trow}" align="center"><a href="{$mybb->settings[\'bburl\']}/awards.php?view={$award[\'aid\']}" title="{$award[\'name\']}"><img src="{$award[\'image\']}" alt="{$award[\'name\']}" /></a></td>
		<td class="{$trow}"><a href="{$mybb->settings[\'bburl\']}/awards.php?view={$award[\'aid\']}" title="{$award[\'name\']}">{$award[\'name\']}</a></td>
		<td class="{$trow}">{$award[\'description\']}</td>
	</tr>',
	),
	'ougc_awards_page_list_empty' => array(
		'version' => 1607,
		'content' => '<tr>
		<td class="trow1" colspan="4" align="center">
			{$lang->ougc_awards_page_list_empty}
		</td>
	</tr>',
	),
	'ougc_awards_page_user' => array(
		'version' => 1607,
		'content' => '<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
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
	),
	'ougc_awards_page_user_award' => array(
		'version' => 1607,
		'content' => '<tr>
		<td class="{$trow}" align="center"><a href="{$mybb->settings[\'bburl\']}/awards.php?view={$award[\'aid\']}" title="{$award[\'name\']}"><img src="{$award[\'image\']}" alt="{$award[\'name\']}" /></a></td>
		<td class="{$trow}">{$award[\'reason\']}</td>
		<td class="{$trow}" align="center">{$award[\'date\']}</td>
	</tr>',
	),
	'ougc_awards_page_user_empty' => array(
		'version' => 1607,
		'content' => '<tr>
		<td class="trow1" colspan="3" align="center">{$lang->ougc_awards_page_list_empty}</td>
	</tr>',
	),
	'ougc_awards_page_view' => array(
		'version' => 1607,
		'content' => '<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
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
	),
	'ougc_awards_page_view_empty' => array(
		'version' => 1607,
		'content' => '<tr>
		<td class="trow1" colspan="3" align="center">{$lang->ougc_awards_page_view_empty}</td>
	</tr>',
	),
	'ougc_awards_page_view_row' => array(
		'version' => 1607,
		'content' => '<tr>
		<td class="{$trow}">{$gived[\'username\']}</td>
		<td class="{$trow}">{$gived[\'reason\']}</td>
		<td class="{$trow}" align="center">{$gived[\'date\']}</td>
	</tr>',
	)
);