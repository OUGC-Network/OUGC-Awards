<?php

/***************************************************************************
 *
 *	OUGC Awards plugin (/inc/tasks/ougc_awards.php)
 *	Author: Omar Gonzalez
 *	Copyright: © 2012-2016 Omar Gonzalez
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

function task_ougc_awards($task)
{
	global $mybb, $db, $lang, $cache, $plugins, $awards;
	$awards->lang_load();

	$query = $db->simple_select('ougc_awards_tasks', '*', 'enabled=1');
	while($award_task = $db->fetch_array($query))
	{
		$award_task['tid'] = (int)$award_task['tid'];

		$where_clause = array();

		$requirements = explode(',', $award_task['requirements']);

		foreach(array('posts' => 'postnum', 'threads' => 'threadnum', 'referrals' => 'referrals', 'warnings' => 'warningpoints') as $k => $c)
		{
			$t = $k.'type';
			if(in_array($k, $requirements) && (int)$award_task[$k] >= 0 && !empty($award_task[$t]))
			{
				$where_clause[] = "{$c}{$award_task[$t]}'{$award_task[$k]}'";
			}
		}

		foreach(array('reputation' => 'reputation') as $k => $c)
		{
			$t = $k.'type';
			if(in_array($k, $requirements) && !empty($award_task[$t]))
			{
				$where_clause[] = "{$c}{$award_task[$t]}'{$award_task[$k]}'";
			}
		}

		if(in_array('registered', $requirements) && (int)$award_task['registered'] > 0 && !empty($award_task['registeredtype']))
		{
			switch($award_task['registeredtype'])
			{
				case 'hours':
					$regdate = $award_task['registered']*60*60;
					break;
				case 'days':
					$regdate = $award_task['registered']*60*60*24;
					break;
				case 'weeks':
					$regdate = $award_task['registered']*60*60*24*7;
					break;
				case 'months':
					$regdate = $award_task['registered']*60*60*24*30;
					break;
				case 'years':
					$regdate = $award_task['registered']*60*60*24*365;
					break;
				default:
					$regdate = $award_task['registered']*60*60*24;
			}
			$where_clause[] = "regdate<='".(TIME_NOW-$regdate)."'";
		}

		if(in_array('online', $requirements) && (int)$award_task['online'] > 0 && !empty($award_task['onlinetype']))
		{
			switch($award_task['onlinetype'])
			{
				case 'hours':
					$timeonline = $award_task['online']*60*60;
					break;
				case 'days':
					$timeonline = $award_task['online']*60*60*24;
					break;
				case 'weeks':
					$timeonline = $award_task['online']*60*60*24*7;
					break;
				case 'months':
					$timeonline = $award_task['online']*60*60*24*30;
					break;
				case 'years':
					$timeonline = $award_task['online']*60*60*24*365;
					break;
				default:
					$timeonline = $award_task['online']*60*60*24;
			}
			$where_clause[] = "timeonline<='".(TIME_NOW-$timeonline)."'";
		}

		$usergroups = array_map('intval', explode(',', $award_task['usergroups']));
		$group_clause = array("usergroup IN ('".implode("','", $usergroups)."')");
		foreach($usergroups as $gid)
		{
			
			switch($db->type)
			{
				case 'pgsql':
				case 'sqlite':
					$group_clause[] = "','||additionalgroups||',' LIKE '%,{$gid},%'";
					break;
				default:
					$group_clause[] = "CONCAT(',',additionalgroups,',') LIKE '%,{$gid},%'";
					break;
			}
		}
		$where_clause[] = '('.implode(' OR ', $group_clause).')';

/*
'fposts'				=> $awards->get_input('fposts', 1),
'fpoststype'			=> $awards->get_input('fpoststype'),
'fpostsforums'			=> $awards->get_input('fpostsforums', 1),
'fthreads'				=> $awards->get_input('fthreads', 1),
'fthreadstype'			=> $awards->get_input('fthreadstype'),
'fthreadsforums'		=> $awards->get_input('fthreadsforums', 1),
'newpoints'				=> $awards->get_input('newpoints', 1),
'newpointstype'			=> $awards->get_input('newpointstype'),
'profilefields'			=> $awards->get_input('profilefields', 2),
'mydownloads'			=> $awards->get_input('mydownloads', 1),
'mydownloadstype'		=> $awards->get_input('mydownloadstype'),
'myarcadechampions'		=> $awards->get_input('myarcadechampions', 1),
'myarcadechampionstype'	=> $awards->get_input('myarcadechampionstype'),
'myarcadescores'		=> $awards->get_input('myarcadescores', 1),
'myarcadescorestype'	=> $awards->get_input('myarcadescorestype'),
'ougc_customrep_r'		=> $awards->get_input('ougc_customrep_r', 1),
'ougc_customreptype_r'	=> $awards->get_input('ougc_customreptype_r'),
'ougc_customrepids_r'	=> $awards->get_input('ougc_customrepids_r', 1),
'ougc_customrep_g'		=> $awards->get_input('ougc_customrep_g', 1),
'ougc_customreptype_g'	=> $awards->get_input('ougc_customreptype_g'),
'ougc_customrepids_g'	=> $awards->get_input('ougc_customrepids_g', 1)
*/

		$uid = array();
		$log_inserts = array();

		if(is_object($plugins))
		{
			$args = array(
				'task'			=> &$task,
				'award_task'	=> &$award_task,
				'where_clause'	=> &$where_clause
			);

			$plugins->run_hooks('task_ougc_awards', $args);
		}

		$query2 = $db->simple_select('users', 'uid', implode(' AND ', $where_clause));

		$uids = array();
		while($user = $db->fetch_array($query2))
		{
			$log_inserts[] = array(
				'tid'		=> $award_task['tid'],
				'uid'		=> $user['uid'],
				'dateline'	=> TIME_NOW
			);

			$uids[] = $user['uid'];

			if($award_task['give'])
			{
				foreach(explode(',', $award_task['revoke']) as $aid)
				{
					$awards->give_award($aid, $uid, $award_task['reason']);
				}
			}
			if($award_task['revoke'])
			{
				$q = $db->simple_select('ougc_awards_users', 'gid', "uid='' AND aid IN ('".implode("','", explode(',', $award_task['revoke']))."')");
				while($gid = $db->fetch_field($q, 'gid'))
				{
					$awards->revoke_award($gid);
				}
			}
		}

		if(count($uids) > 0)
		{
			if(!empty($log_inserts))
			{
				$db->insert_query_multiple('ougc_awards_tasks_logs', $log_inserts);
			}

			$uids = array();
			$log_inserts = array();
		}
	}

	$awards->update_cache();

	add_task_log($task, $lang->ougc_awards_task_ran);
}
