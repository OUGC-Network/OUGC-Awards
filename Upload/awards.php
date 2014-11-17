<?php

/***************************************************************************
 *
 *	OUGC Awards plugin (/awards.php)
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

// Boring stuff..
define('IN_MYBB', 1);
define('THIS_SCRIPT', substr($_SERVER['SCRIPT_NAME'], -strpos(strrev($_SERVER['SCRIPT_NAME']), '/')));
$templatelist = 'ougcawards_page_list_award, ougcawards_page_list, ougcawards_page, ougcawards_page_list_empty, ougcawards_page_view_row, ougcawards_page_view';
require_once './global.php';

// Load lang
$awards->lang_load();

// If plugin no active or user is guest then stop.
$awards->is_active or error($lang->ougc_awards_error_active);
$mybb->user['uid'] or error_no_permission();

if(!$mybb->settings['ougc_awards_pagegroups'] || ($mybb->settings['ougc_awards_pagegroups'] != -1 && !$awards->is_member($mybb->settings['ougc_awards_pagegroups'])))
{
	error_no_permission();
}

// Set url
$awards->set_url(null, THIS_SCRIPT);

$plugins->run_hooks('ougc_awards_start');

add_breadcrumb($lang->ougc_awards_page_title, $awards->build_url());

$limit = (int)$mybb->settings['ougc_awards_perpage'];
$limit = $limit > 100 ? 100 : ($limit < 1 ? 1 : $limit);

$users_list = $award_list = $multipage = '';

if(!empty($mybb->input['view']))
{
	$aid = (int)$mybb->input['view'];
	$award = $awards->get_award($aid);

	// This award doesn't exists or is not visible.
	if(!$award['aid'] || !$award['visible'])
	{
		error($lang->ougc_awards_error_wrongaward);
	}

	$plugins->run_hooks('ougc_awards_view_start');

	// Add breadcrumb
	if($name = $awards->get_award_info('name', $award['aid']))
	{
		$award['name'] = $name;
	}
	add_breadcrumb(strip_tags($award['name']));

	$query = $db->simple_select('ougc_awards_users', 'COUNT(gid) AS users', 'aid=\''.(int)$award['aid'].'\'');
	$userscount = $db->fetch_field($query, 'users');

	if($mybb->get_input('page', 1) > 0)
	{
		$start = ($mybb->get_input('page', 1)-1)*$limit;
		$pages = ceil($userscount/$limit);
		if($mybb->get_input('page', 1) > $pages)
		{
			$start = 0;
			$mybb->input['page'] = 1;
		}
	}
	else
	{
		$start = 0;
		$mybb->input['page'] = 1;
	}

	// Query our data.
	$query = $db->query('
		SELECT g.gid, g.uid, g.aid, g.reason, g.date, u.uid, u.username, u.usergroup, u.displaygroup 
		FROM '.TABLE_PREFIX.'ougc_awards_users g
		LEFT JOIN '.TABLE_PREFIX.'users u ON (g.uid=u.uid)
		WHERE g.aid=\''.(int)$award['aid'].'\'
		ORDER BY g.date desc
		LIMIT '.$start.', '.$limit.'
	');

	$multipage = (string)multipage($userscount, $limit, $mybb->input['page'], $awards->build_url('view='.$aid));

	while($gived = $db->fetch_array($query))
	{
		$trow = alt_trow();

		if($reason = $awards->get_award_info('reason', $award['aid'], $award['gid']))
		{
			$award['reason'] = $reason;
		}

		if(empty($award['reason']))
		{
			$award['reason'] = $lang->ougc_awards_pm_noreason;
		}

		$gived['username'] = htmlspecialchars_uni($gived['username']);
		$gived['username'] = format_name($gived['username'], $gived['usergroup'], $gived['displaygroup']);
		$gived['username'] = build_profile_link($gived['username'], $gived['uid']);
		$gived['date'] = $lang->sprintf($lang->ougc_awards_profile_tine, my_date($mybb->settings['dateformat'], $gived['date']), my_date($mybb->settings['timeformat'], $gived['date']));

		eval('$users_list .= "'.$templates->get('ougcawards_page_view_row').'";');
	}

	if(!$users_list)
	{
		eval('$users_list = "'.$templates->get('ougcawards_page_view_empty').'";');
	}

	$plugins->run_hooks('ougc_awards_view_end');

	eval('$content = "'.$templates->get('ougcawards_page_view').'";');
}
else
{
	$query = $db->simple_select('ougc_awards', 'COUNT(aid) AS awards', 'visible=\'1\'');
	$awardscount = $db->fetch_field($query, 'awards');

	if($mybb->get_input('page', 1) > 0)
	{
		$start = ($mybb->get_input('page', 1)-1)*$limit;
		$pages = ceil($awardscount/$limit);
		if($mybb->get_input('page', 1) > $pages)
		{
			$start = 0;
			$mybb->input['page'] = 1;
		}
	}
	else
	{
		$start = 0;
		$mybb->input['page'] = 1;
	}

	$query = $db->simple_select('ougc_awards', '*', 'visible=\'1\'', array('limit_start' => $start, 'limit' => $limit, 'order_by' => 'disporder'));

	$multipage = (string)multipage($awardscount, $limit, $mybb->input['page'], $awards->build_url());

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

		eval('$award_list .= "'.$templates->get('ougcawards_page_list_award').'";');
	}

	if(!$award_list)
	{
		eval('$award_list = "'.$templates->get('ougcawards_page_list_empty').'";');
	}

	$plugins->run_hooks('ougc_awards_end');

	eval('$content = "'.$templates->get('ougcawards_page_list').'";');
}
eval('$page = "'.$templates->get('ougcawards_page').'";');
output_page($page);
exit;