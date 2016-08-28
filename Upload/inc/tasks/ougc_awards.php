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
	global $mybb, $db, $lang, $plugins, $awards;
	$awards->lang_load();

	$query = $db->simple_select('ougc_awards_tasks', '*', 'active=1');
	while($award_task = $db->fetch_array($query))
	{
		$award_task['tid'] = (int)$award_task['tid'];

		$where_clause = $left_join = array();

		$requirements = explode(',', $award_task['requirements']);

		foreach(array('posts' => 'postnum', 'threads' => 'threadnum', 'referrals' => 'referrals', 'warnings' => 'warningpoints', 'newpoints' => 'newpoints') as $k => $c)
		{
			$t = $k.'type';
			if(in_array($k, $requirements) && (int)$award_task[$k] >= 0 && !empty($award_task[$t]))
			{
				$where_clause[] = "u.{$c}{$award_task[$t]}'{$award_task[$k]}'";
			}
		}

		foreach(array('reputation' => 'reputation') as $k => $c)
		{
			$t = $k.'type';
			if(in_array($k, $requirements) && !empty($award_task[$t]))
			{
				$where_clause[] = "u.{$c}{$award_task[$t]}'{$award_task[$k]}'";
			}
		}

		if(in_array('registered', $requirements) && (int)$award_task['registered'] >= 0 && !empty($award_task['registeredtype']))
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
			$where_clause[] = "u.regdate<='".(TIME_NOW-$regdate)."'";
		}

		if(in_array('online', $requirements) && (int)$award_task['online'] >= 0 && !empty($award_task['onlinetype']))
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
			$where_clause[] = "u.timeonline<='".(TIME_NOW-$timeonline)."'";
		}

		if(in_array('usergroups', $requirements) && !empty($award_task['usergroups']))
		{
			$usergroups = array_map('intval', explode(',', $award_task['usergroups']));
			$group_clause = array("usergroup IN ('".implode("','", $usergroups)."')");
			if($award_task['additionalgroups'])
			{
				foreach($usergroups as $gid)
				{
					switch($db->type)
					{
						case 'pgsql':
						case 'sqlite':
							$group_clause[] = "','||u.additionalgroups||',' LIKE '%,{$gid},%'";
							break;
						default:
							$group_clause[] = "CONCAT(',',u.additionalgroups,',') LIKE '%,{$gid},%'";
							break;
					}
				}
			}
			$where_clause[] = '('.implode(' OR ', $group_clause).')';
		}

		if(in_array('fposts', $requirements) && (int)$award_task['fposts'] >= 0 && !empty($award_task['fposts']))
		{
			$left_join[] = "LEFT JOIN (
				SELECT p.uid, COUNT(p.pid) AS fposts FROM ".TABLE_PREFIX."posts p
				LEFT JOIN ".TABLE_PREFIX."threads t ON (t.tid=p.tid)
				WHERE p.fid='".(int)$award_task['fpostsforums']."' AND t.visible > 0 AND p.visible > 0
				GROUP BY p.uid
			) p ON (p.uid=u.uid)";
			$where_clause[] = "p.fposts{$award_task['fpoststype']}'{$award_task['fposts']}'";
		}

		if(in_array('fthreads', $requirements) && (int)$award_task['fthreads'] >= 0 && !empty($award_task['fthreads']))
		{
			$left_join[] = "LEFT JOIN (SELECT uid, COUNT(tid) AS fthreads FROM ".TABLE_PREFIX."threads WHERE visible > 0 AND closed NOT LIKE 'moved|%' GROUP BY uid) t ON (t.uid=u.uid)";
			$where_clause[] = "t.fthreads{$award_task['fthreadstype']}'{$award_task['fthreads']}'";
		}

		if(in_array('previousawards', $requirements) && !empty($award_task['previousawards']))
		{
			$awards_cache = $mybb->cache->read('ougc_awards');
			$aids = implode("','", array_keys($awards_cache['awards']));
			foreach(array_map('intval', explode(',', $award_task['previousawards'])) as $aid)
			{
				$left_join[] = "LEFT JOIN (
					SELECT ua.uid, ua.aid, COUNT(ua.gid) AS previous_awards_{$aid} FROM ".TABLE_PREFIX."ougc_awards_users ua
					WHERE ua.aid='{$aid}' AND ua.aid IN ('{$aids}')
					GROUP BY ua.uid
				) a_{$aid} ON (a_{$aid}.uid=u.uid)";
				$where_clause[] = "a_{$aid}.previous_awards_{$aid}>='1'";
			}
		}

		if(in_array('profilefields', $requirements) && !empty($award_task['profilefields']))
		{
			$left_join[] = "LEFT JOIN ".TABLE_PREFIX."userfields uf ON (uf.ufid=u.uid)";
			foreach(array_map('intval', explode(',', $award_task['profilefields'])) as $fid)
			{
				$where_clause[] = "uf.fid".(int)$fid."!=''";
			}
		}

		if(in_array('mydownloads', $requirements) && (int)$award_task['mydownloads'] >= 0 && !empty($award_task['mydownloads']))
		{
			$left_join[] = "LEFT JOIN (SELECT submitter_uid, COUNT(did) AS downloads FROM ".TABLE_PREFIX."mydownloads_downloads WHERE hidden='0' GROUP BY submitter_uid) myd ON (myd.submitter_uid=u.uid)";
			$where_clause[] = "myd.downloads{$award_task['mydownloadstype']}'{$award_task['mydownloads']}'";
		}

		// TODO myarcadechampions

		if(in_array('myarcadescores', $requirements) && (int)$award_task['myarcadescores'] >= 0 && !empty($award_task['myarcadescores']))
		{
			$left_join[] = "LEFT JOIN (
				SELECT s.uid, s.gid, COUNT(s.sid) AS scores FROM ".TABLE_PREFIX."arcadescores s
				LEFT JOIN ".TABLE_PREFIX."arcadegames g ON (g.gid=s.gid)
				WHERE g.active='1'
				GROUP BY s.uid
			) mya ON (mya.uid=u.uid)";
			$where_clause[] = "mya.scores{$award_task['myarcadescorestype']}'{$award_task['myarcadescores']}'";
		}

		if(in_array('ougc_customrep_r', $requirements) && (int)$award_task['ougc_customrep_r'] >= 0 && !empty($award_task['ougc_customrep_r']))
		{
			$left_join[] = "LEFT JOIN (
				SELECT p.uid, l.rid, COUNT(l.lid) AS ougc_custom_reputation_receieved FROM ".TABLE_PREFIX."ougc_customrep_log l
				LEFT JOIN ".TABLE_PREFIX."ougc_customrep r ON (r.rid=l.rid)
				LEFT JOIN ".TABLE_PREFIX."posts p ON (p.pid=l.pid)
				LEFT JOIN ".TABLE_PREFIX."threads t ON (t.tid=p.tid)
				WHERE r.visible='1' AND t.visible > 0 AND p.visible > 0 AND r.rid IN ('".implode("','", array_map('intval', explode(',', $award_task['ougc_customrepids_r'])))."')
				GROUP BY p.uid
			) ocr ON (ocr.uid=u.uid)";
			$where_clause[] = "ocr.ougc_custom_reputation_receieved{$award_task['ougc_customreptype_r']}'{$award_task['ougc_customrep_r']}'";
		}

		if(in_array('ougc_customrep_g', $requirements) && (int)$award_task['ougc_customrep_g'] >= 0 && !empty($award_task['ougc_customrep_g']))
		{
			$left_join[] = "LEFT JOIN (
				SELECT l.uid, l.rid, COUNT(l.lid) AS ougc_custom_reputation_gived FROM ".TABLE_PREFIX."ougc_customrep_log l
				LEFT JOIN ".TABLE_PREFIX."ougc_customrep r ON (r.rid=l.rid)
				LEFT JOIN ".TABLE_PREFIX."posts p ON (p.pid=l.pid)
				LEFT JOIN ".TABLE_PREFIX."threads t ON (t.tid=p.tid)
				WHERE r.visible='1' AND t.visible > 0 AND p.visible > 0 AND r.rid IN ('".implode("','", array_map('intval', explode(',', $award_task['ougc_customrepids_g'])))."')
				GROUP BY l.uid
			) ocg ON (ocg.uid=u.uid)";
			$where_clause[] = "ocg.ougc_custom_reputation_gived{$award_task['ougc_customreptype_g']}'{$award_task['ougc_customrep_g']}'";
		}

		$log_inserts = $uids = array();

		if(is_object($plugins))
		{
			$args = array(
				'task'			=> &$task,
				'award_task'	=> &$award_task,
				'left_join'		=> &$left_join,
				'where_clause'	=> &$where_clause,
				'uids'			=> &$uids
			);

			$plugins->run_hooks('task_ougc_awards', $args);
		}

		$query2 = $db->simple_select('users u '.implode(' ', $left_join), 'u.uid', implode(' AND ', $where_clause));

		while($uid = $db->fetch_field($query2, 'uid'))
		{
			$log_inserts[] = array(
				'tid'		=> (int)$award_task['tid'],
				'uid'		=> (int)$uid,
				'gave'		=> $db->escape_string($award_task['give']),
				'revoked'	=> $db->escape_string($award_task['revoke']),
				'date'		=> TIME_NOW
			);

			$uids[] = $uid;

			if($award_task['give'])
			{
				foreach(explode(',', $award_task['give']) as $aid)
				{
					$award = $awards->get_award($aid);
					$awards->give_award($award, array('user' => $uid), $award_task['reason']);
				}
			}

			if($award_task['revoke'])
			{
				$q = $db->simple_select('ougc_awards_users', 'gid', "uid='{uid}' AND aid IN ('".implode("','", explode(',', $award_task['revoke']))."')");
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
