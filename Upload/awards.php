<?php

/***************************************************************************
 *
 *   OUGC Awards plugin (/inc/plugins/ougc_awards/languages/english/ougc_awards_extra_vals.lang.php)
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
define('IN_MYBB', 1);
define('THIS_SCRIPT', substr($_SERVER['SCRIPT_NAME'], -strpos(strrev($_SERVER['SCRIPT_NAME']), '/')));
$templatelist = 'ougcawards_page_list_award, ougcawards_page_list, ougcawards_page, ougcawards_page_user_award, ougcawards_page_user, ougcawards_page, ougcawards_page_view_row, ougcawards_page_view, ougcawards_page, ougcawards_page_user_empty';
require_once './global.php';

// Load lang
$ougc_awards->lang_load();

// If plugin no active or user is guest then stop.
$ougc_awards->is_active or $error_handler->error(MYBB_GENERAL, $lang->ougc_awards_error_active);
if(!$mybb->user['uid'])
{
	error_no_permission();
}

// Load plugin
require_once MYBB_ROOT.'inc/plugins/ougc_awards/plugin.php';
$ougc_awards = new OUGC_Awards;
$ougc_awards->lang_load();

// Run our start hook, may be somebody can use it? IDK.
$plugins->run_hooks('ougc_awards_start');

add_breadcrumb($lang->ougc_awards_page_title, THIS_SCRIPT);
// We are viewing a spesific award.
if($mybb->input['view'])
{
	$mybb->input['view'] = intval($mybb->input['view']);
	$query = $db->simple_select('ougc_awards', 'aid, name', "aid='{$mybb->input['view']}' AND visible='1'");
	$award = $db->fetch_array($query);
	$db->free_result($query);

	// This award doesn't exists or is not visible.
	if(!$award['aid'])
	{
		error($lang->ougc_awards_error_wrongaward);
	}

	// Add breadcrumb
	$award['name'] = ougc_awards_get_award_info('name', $award['aid'], $award['name']);
	add_breadcrumb($award['name'], THIS_SCRIPT."?view={$award['aid']}");

	// Query our data.
	$query = $db->query("
		SELECT g.gid, g.uid, g.aid, g.reason, g.date, u.uid, u.username, u.usergroup, u.displaygroup 
		FROM ".TABLE_PREFIX."ougc_awards_users g
		LEFT JOIN ".TABLE_PREFIX."users u ON (g.uid=u.uid)
		WHERE g.aid='".intval($award['aid'])."'
		ORDER BY g.date desc
	");

	$users_list = '';
	while($gived = $db->fetch_array($query))
	{
		$trow = alt_trow();

		$gived['username'] = htmlspecialchars_uni($gived['username']);
		$gived['username'] = format_name($gived['username'], $gived['usergroup'], $gived['displaygroup']);
		$gived['username'] = build_profile_link($gived['username'], $gived['uid']);
		$gived['reason'] = ougc_awards_get_award_info('reason', $gived['aid'], $gived['reason'], $gived['gid']);
		$gived['date'] = $lang->sprintf($lang->ougc_awards_profile_tine, my_date($mybb->settings['dateformat'], $gived['date']), my_date($mybb->settings['timeformat'], $gived['date']));

		eval("\$users_list .= \"".$templates->get("ougc_awards_page_view_row")."\";");
	}
	$db->free_result($query);

	if(!$users_list)
	{
		eval("\$users_list = \"".$templates->get("ougc_awards_page_view_empty")."\";");
	}

	eval("\$content = \"".$templates->get("ougc_awards_page_view")."\";");
	eval("\$page = \"".$templates->get("ougc_awards_page")."\";");
	output_page($page);
	exit;
}
// We are viewing a spesific user.
elseif($mybb->input['user'])
{
	// Does this user exists?
	$user = get_user(intval($mybb->input['user']));
	if(!$user['uid'])
	{
		error($lang->ougc_awards_error_wronguser);
	}

	$user['username'] = htmlspecialchars_uni($user['username']);
	add_breadcrumb($user['username']);

	// Query our data.
	$query = $db->query("
		SELECT u.gid, u.reason, u.date, a.aid, a.image 
		FROM ".TABLE_PREFIX."ougc_awards_users u
		LEFT JOIN ".TABLE_PREFIX."ougc_awards a ON (u.aid=a.aid)
		WHERE u.uid='".intval($user['uid'])."'
		ORDER BY u.date desc
	");

	$awards_list = '';
	while($award = $db->fetch_array($query))
	{
		$trow = alt_trow();

		$award['aid'] = intval($award['aid']);
		$award['reason'] = ougc_awards_get_award_info('reason', $award['aid'], $award['reason'], $award['gid']);
		$award['date'] = $lang->sprintf($lang->ougc_awards_profile_tine, my_date($mybb->settings['dateformat'], $award['date']), my_date($mybb->settings['timeformat'], $award['date']));
		$award['image'] = $ougc_awards->get_icon($award['image'], $award['aid']);
		eval("\$awards_list .= \"".$templates->get("ougc_awards_page_user_award")."\";");
	}
	$db->free_result($query);

	if(!$awards_list)
	{
		eval("\$awards_list = \"".$templates->get("ougc_awards_page_user_empty")."\";");
	}

	eval("\$content = \"".$templates->get("ougc_awards_page_user")."\";");
	eval("\$page = \"".$templates->get("ougc_awards_page")."\";");
	output_page($page);
	exit;
}
// Anything else lets see all awards..
else
{
	$awards_list = $ougc_awards->get_awards(false, 'aid, name, description, image', "visible='1'");
	$award_list = '';
	foreach($awards_list as $award)
	{
		$trow = alt_trow();
		$award['aid'] = intval($award['aid']);
		$award['name'] = $ougc_awards->get_award_info('name', $award['aid'], $award['name']);
		$award['description'] = $ougc_awards->get_award_info('desc', $award['aid'], $award['description']);
		$award['image'] = $ougc_awards->get_icon($award['image'], $award['aid']);
		eval("\$award_list .= \"".$templates->get("ougc_awards_page_list_award")."\";");
	}

	if(!$award_list)
	{
		eval('$award_list = "'.$templates->get('ougc_awards_page_list_empty').'";');
	}

	eval('$content = "'.$templates->get('ougc_awards_page_list').'";');
	eval('$page = "'.$templates->get('ougc_awards_page').'";');
	output_page($page);
}