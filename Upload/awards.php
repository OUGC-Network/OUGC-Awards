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
$templatelist = 'ougcawards_page_list_award, ougcawards_page_list_award_request, ougcawards_page_list_request, ougcawards_page_list, ougcawards_page, ougcawards_page_list_empty, ougcawards_page_view_row, ougcawards_page_view';
require_once './global.php';

// Load lang
$awards->lang_load();

// If plugin no active or user is guest then stop.
$awards->is_active or error($lang->ougc_awards_error_active);

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
	$aid = $awards->get_input('view', 1);
	$award = $awards->get_award($aid);
	$category = $awards->get_category($award['cid']);

	// This award doesn't exists or is not visible.
	if(!$award['aid'] || !$award['visible'])
	{
		error($lang->ougc_awards_error_wrongaward);
	}

	if(!$category['cid'] || !$category['visible'])
	{
		$error = $lang->ougc_awards_error_invalidcategory;
	}

	$mybb->user['uid'] = (int)$mybb->user['uid'];
	$query = $db->simple_select('ougc_awards_requests', 'COUNT(rid) as pending_total', "status='1' AND uid='{$mybb->user['uid']}' AND aid='{$award['aid']}'");
	$pending_total = (int)$db->fetch_field($query, 'pending_total');

	if($pending_total)
	{
		$message = $lang->sprintf($lang->ougc_awards_page_pending_requests, my_number_format($pending_total));
		$pending_requests = eval($templates->render('ougcawards_global_notification'));
	}
	else
	{
		$pending_requests = '';
	}

	$plugins->run_hooks('ougc_awards_view_start');

	// Add breadcrumb
	if($name = $awards->get_award_info('name', $award['aid']))
	{
		$award['name'] = $name;
	}

	add_breadcrumb(strip_tags($category['name']));
	add_breadcrumb(strip_tags($award['name']));

	$query = $db->simple_select('ougc_awards_users', 'COUNT(gid) AS users', 'aid=\''.(int)$award['aid'].'\'');
	$userscount = $db->fetch_field($query, 'users');

	if($awards->get_input('page', 1) > 0)
	{
		$start = ($awards->get_input('page', 1)-1)*$limit;
		$pages = ceil($userscount/$limit);
		if($awards->get_input('page', 1) > $pages)
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

	$multipage = (string)multipage($userscount, $limit, $awards->get_input('page', 1), $awards->build_url('view='.$aid));

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

	$request_button = '';

	if(!$pending_total && $category['allowrequests'] && $award['allowrequests'])
	{
		$request_button = eval($templates->render('ougcawards_page_view_request'));
	}

	$plugins->run_hooks('ougc_awards_view_end');

	eval('$content = "'.$templates->get('ougcawards_page_view').'";');
}
elseif($awards->get_input('action') == 'request')
{
	if(!($award = $awards->get_award($awards->get_input('aid', 1))))
	{
		$error = $lang->ougc_awards_error_wrongaward;
	}

	if(!$award['visible'] || !$award['allowrequests'])
	{
		$error = $lang->ougc_awards_error_wrongaward;
	}

	if(!($category = $awards->get_category($award['cid'])))
	{
		$error = $lang->ougc_awards_error_invalidcategory;
	}

	if(!$category['visible'] || !$category['allowrequests'])
	{
		$error = $lang->ougc_awards_error_invalidcategory;
	}

	$award['aid'] = (int)$award['aid'];
	$mybb->user['uid'] = (int)$mybb->user['uid'];

	$query = $db->simple_select('ougc_awards_requests', '*', "status='1' AND uid='{$mybb->user['uid']}' AND aid='{$award['aid']}'", array('limit' => 1));
	if($db->fetch_array($query))
	{
		$error = $lang->ougc_awards_error_pendingrequest;
	}

	$trow = alt_trow();

	$button = '&nbsp;';

	if($error)
	{
		$content = eval($templates->render('ougcawards_page_request_error'));
	}
	else
	{
		if($mybb->request_method == 'post')
		{
			$awards->insert_request(array(
				'uid'		=> $mybb->user['uid'],
				'aid'		=> $award['aid'],
				'message'	=> $awards->get_input('message')
			));

			$awards->log_action();

			header('Content-type: application/json; charset='.$lang->settings['charset']);

			$content = eval($templates->render('ougcawards_page_request_success'));
			$modal = eval($templates->render('ougcawards_page_request', 1, 0));
			$data = array('modal' => $modal);

			echo json_encode($data);
			exit;
		}
		else
		{
			$award['image'] = $awards->get_award_icon($award['aid']);
			$award['name'] = htmlspecialchars_uni($award['name']);

			$button = eval($templates->render('ougcawards_page_request_form_button'));
			$content = eval($templates->render('ougcawards_page_request_form'));
		}
	}

	$page = eval($templates->render('ougcawards_page_request', 1, 0));
	exit($page);
}
else
{
	$categories = $cids = array();

	$query = $db->simple_select('ougc_awards_categories', '*', "visible='1'", array('order_by' => 'disporder'));
	while($category = $db->fetch_array($query))
	{
		$cids[] = (int)$category['cid'];
		$categories[] = $category;
	}

	$whereclause = "a.visible='1' AND a.cid IN ('".implode("','", array_values($cids))."')";

	$query = $db->simple_select('ougc_awards a', 'COUNT(a.aid) AS awards', $whereclause);
	$awardscount = $db->fetch_field($query, 'awards');

	if($awards->get_input('page', 1) > 0)
	{
		$start = ($awards->get_input('page', 1)-1)*$limit;
		$pages = ceil($awardscount/$limit);
		if($awards->get_input('page', 1) > $pages)
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

	$multipage = (string)multipage($awardscount, $limit, $awards->get_input('page', 1), $awards->build_url());

	$query = $db->simple_select('ougc_awards a', 'a.*', $whereclause, array('limit_start' => $start, 'limit' => $limit, 'order_by' => 'a.disporder'));

	while($award = $db->fetch_array($query))
	{
		$_awards[(int)$award['cid']][] = $award;
	}

	$content = '';
	if(!empty($categories))
	{
		foreach($categories as $disporder => $category)
		{
			$request = '';
			$colspan_thead = 3;
			if($category['allowrequests'])
			{
				$request = eval($templates->render('ougcawards_page_list_request'));
				++$colspan_thead;
			}

			$category['name'] = htmlspecialchars_uni($category['name']);
			$category['description'] = htmlspecialchars_uni($category['description']);

			$award_list = '';
			if(!empty($_awards[(int)$category['cid']]))
			{
				$trow = alt_trow(1);
				foreach($_awards[(int)$category['cid']] as $cid => $award)
				{
					$award_request = '';
					$colspan_trow = 2;
					if($category['allowrequests'] && $award['allowrequests'])
					{
						$award_request = eval($templates->render('ougcawards_page_list_award_request'));
						--$colspan_trow;
					}

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

					$trow = alt_trow();
				}
			}

			if(!$award_list)
			{
				eval('$award_list = "'.$templates->get('ougcawards_page_list_empty').'";');
			}

			$plugins->run_hooks('ougc_awards_end');

			eval('$content .= "'.$templates->get('ougcawards_page_list').'";');
		}
	}
}

$jscriptfile = eval($templates->render('ougcawards_js'));
eval('$page = "'.$templates->get('ougcawards_page').'";');
output_page($page);
exit;