<?php

/***************************************************************************
 *
 *   OUGC Awards plugin (/inc/plugins/ougc_awards.php)
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

// Die if IN_MYBB is not defined, for security reasons.
defined('IN_MYBB') or die('This file cannot be accessed directly.');

// Add hooks.
if(defined('IN_ADMINCP'))
{
	// Add menu to ACP
	$plugins->add_hook('admin_user_menu', create_function('&$args', 'global $lang, $ougc_awards;	$ougc_awards->lang_load();	$args[] = array(\'id\' => \'ougc_awards\', \'title\' => $lang->ougc_awards_acp_nav, \'link\' => \'index.php?module=user-ougc_awards\');'));

	// Add our action handler to config module
	$plugins->add_hook('admin_user_action_handler', create_function('&$args', '$args[\'ougc_awards\'] = array(\'active\' => \'ougc_awards\', \'file\' => \'ougc_awards.php\');'));

	// Insert our plugin into the admin permissions page
	$plugins->add_hook('admin_user_permissions', create_function('&$args', 'global $lang, $ougc_awards;	$ougc_awards->lang_load();	$args[\'ougc_awards\'] = $lang->ougc_awards_acp_permissions;'));// Insert our menu at users section.
}
elseif(defined('THIS_SCRIPT'))
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
			$plugins->add_hook('postbit_prev', 'ougc_awards_postbit');
			$plugins->add_hook('postbit_pm', 'ougc_awards_postbit');
			$plugins->add_hook('postbit_announcement', 'ougc_awards_postbit');
			$plugins->add_hook('postbit', 'ougc_awards_postbit');
			$templatelist .= '';
			break;
		case 'member.php':
			global $mybb;

			if($mybb->input['action'] == 'profile')
			{
				$plugins->add_hook('member_profile_end', 'ougc_awards_profile');
				$templatelist .= '';
			}
			break;
		case 'modcp.php':
			global $mybb;

			$plugins->add_hook('modcp_start', 'ougc_awards_modcp');
			if($mybb->input['action'] == 'awards')
			{
				$templatelist .= '';
			}
			$templatelist .= '';
			break;
		case 'private.php':
			$plugins->add_hook('postbit_pm', 'ougc_awards_postbit');
			$templatelist .= '';
			break;
		case 'announcements.php':
			$plugins->add_hook('postbit_announcement', 'ougc_awards_postbit');
			$templatelist .= '';
			break;
	}
}

// PLUGINLIBRARY
defined('PLUGINLIBRARY') or define('PLUGINLIBRARY', MYBB_ROOT.'inc/plugins/pluginlibrary.php');

// Necessary plugin information for the ACP plugin manager.
function ougc_awards_info()
{
	global $lang, $ougc_awards;
	$ougc_awards->lang_load();

	return array(
		'name'			=> 'OUGC Awards',
		'description'	=> $lang->ougc_awards_plugin_d,
		'website'		=> 'http://mods.mybb.com/view/ougc-custom-reputation',
		'author'		=> 'Omar Gonzalez',
		'authorsite'	=> 'http://community.mybb.com/user-25096.html',
		'version'		=> '1.5',
		'versioncode'	=> 1500,
		'compatibility'	=> '16*',
		'guid'			=> '8172205c3142e4295ed5ed3a7e8f40d6',
		'pl_version' 	=> 11,
		'pl_url'		=> 'http://mods.mybb.com/view/pluginlibrary'
	);
}

// Activate the plugin.
function ougc_awards_activate()
{
	// Modify some templates.
	require_once MYBB_ROOT.'/inc/adminfunctions_templates.php';
	find_replace_templatesets('postbit', '#'.preg_quote('{$post[\'user_details\']}').'#', '{$post[\'user_details\']}{$post[\'ougc_awards\']}');
	find_replace_templatesets('postbit_classic', '#'.preg_quote('{$post[\'user_details\']}').'#', '{$post[\'user_details\']}{$post[\'ougc_awards\']}');
	find_replace_templatesets('member_profile', '#'.preg_quote('{$signature}').'#', '{$signature}{$memprofile[\'ougc_awards\']}');
	find_replace_templatesets('modcp_nav', '#'.preg_quote('mcp_nav_modlogs}</a></td></tr>').'#', 'mcp_nav_modlogs}</a></td></tr><!--OUGC_AWARDS-->');
/**
	$info = ougc_awards_info();
	if(isset($plugins['ougc_awards']))
	{
		if((int)$plugins['ougc_awards'] < 1100)
		{
			global $db;

			$db->modify_column('ougc_customrep', 'rid', "int UNSIGNED NOT NULL AUTO_INCREMENT");
			$db->modify_column('ougc_customrep_log', 'lid', "int UNSIGNED NOT NULL AUTO_INCREMENT");
			$db->modify_column('ougc_customrep_log', 'pid', "int NOT NULL DEFAULT '0'");
			$db->modify_column('ougc_customrep_log', 'uid', "int NOT NULL DEFAULT '0'");
			$db->modify_column('ougc_customrep_log', 'rid', "int NOT NULL DEFAULT '0'");
			$db->modify_column('reputation', 'lid', 'int NOT NULL DEFAULT \'0\'');

			$db->write_query('ALTER TABLE '.TABLE_PREFIX.'ougc_customrep_log ADD UNIQUE KEY piduid (pid,uid)');
			if(!$db->index_exists('ougc_customrep_log', 'pidrid'))
			{
				$db->write_query('CREATE INDEX pidrid ON '.TABLE_PREFIX.'ougc_customrep_log (pid,rid)');
			}
		}
	}
	$plugins['ougc_awards'] = $info['versioncode'];
	$cache->update('ougc_plugins', $plugins);**/
}

// Deactivate the plugin.
function ougc_awards_deactivate()
{
	// Remove added variables.
	require_once MYBB_ROOT.'/inc/adminfunctions_templates.php';
	find_replace_templatesets('postbit', '#'.preg_quote('{$post[\'ougc_awards\']}').'#', '', 0);
	find_replace_templatesets('postbit_classic', '#'.preg_quote('{$post[\'ougc_awards\']}').'#', '', 0);
	find_replace_templatesets('member_profile', '#'.preg_quote('{$memprofile[\'ougc_awards\']}').'#', '', 0);
	find_replace_templatesets('modcp_nav', '#'.preg_quote('<!--OUGC_AWARDS-->').'#', '', 0);
}

// Install the plugin.
function ougc_awards_install()
{
	global $db, $lang;
	ougc_awards_lang_load();

	$collation = $db->build_create_table_collation();
	$db->write_query("CREATE TABLE `".TABLE_PREFIX."ougc_awards` (
			`aid` bigint(30) UNSIGNED NOT NULL AUTO_INCREMENT,
			`name` varchar(100) NOT NULL DEFAULT '',
			`description` varchar(255) NOT NULL DEFAULT '',
			`image` varchar(255) NOT NULL DEFAULT '',
			`visible` smallint(1) NOT NULL DEFAULT '1',
			`pm` text NOT NULL,
			`type` smallint(1) NOT NULL DEFAULT '0',
			PRIMARY KEY (`aid`)
		) ENGINE=MyISAM{$collation};"
	);
	$db->write_query("CREATE TABLE `".TABLE_PREFIX."ougc_awards_users` (
			`gid` bigint(30) UNSIGNED NOT NULL AUTO_INCREMENT,
			`uid` bigint(30) NOT NULL DEFAULT '0',
			`aid` bigint(30) NOT NULL DEFAULT '0',
			`reason` text NOT NULL,
			`date` int(10) NOT NULL DEFAULT '0',
			PRIMARY KEY (`gid`)
		) ENGINE=MyISAM{$collation};"
	);
	//UNIQUE KEY (`uid`, `aid`),
	//UNIQUE INDEX (`uid`, `aid`)

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

	// Insert new templates.
	$templates = require MYBB_ROOT.'inc/plugins/ougc_awards/templates.php';
	foreach($templates as $title => $template)
	{
		ougc_awards_add_template($title, $template['content'], $template['version']);
	}
}

// Is the plugin installed?
function ougc_awards_is_installed()
{
	global $db;

	return $db->table_exists('ougc_awards');
}

// Uninstall the plugin.
function ougc_awards_uninstall()
{
	global $db;

	// Delete our tables
	$db->drop_table('ougc_awards');
	$db->drop_table('ougc_awards_users');

	// Delete setting group.
	$q = $db->simple_select('settinggroups', 'gid', 'name="ougc_awards"');
	$gid = $db->fetch_field($q, 'gid');
	if($gid)
	{
		$db->delete_query('settings', "gid='{$gid}'");
		$db->delete_query('settinggroups', "gid='{$gid}'");
		!$hard or rebuild_settings();
	}

	// Delete any old templates.
	$db->delete_query('templates', "title IN('modcp_ougc_awards', 'modcp_ougc_awards_manage', 'modcp_ougc_awards_nav', 'modcp_ougc_awards_list', 'modcp_ougc_awards_list_empty', 'modcp_ougc_awards_list_award', 'modcp_ougc_awards_manage_reason', 'postbit_ougc_awards', 'member_profile_ougc_awards_row_empty', 'member_profile_ougc_awards_row', 'member_profile_ougc_awards', 'ougc_awards_page', 'ougc_awards_page_list', 'ougc_awards_page_list_award', 'ougc_awards_page_list_empty', 'ougc_awards_page_user', 'ougc_awards_page_user_award', 'ougc_awards_page_user_empty', 'ougc_awards_page_view', 'ougc_awards_page_view_empty', 'ougc_awards_page_view_row') AND sid='-2'");
}

// ModCP Part
function ougc_awards_modcp()
{
	global $mybb, $modcp_nav, $templates, $lang;

	$permission = (($mybb->usergroup['cancp'] || $this->check_groups($mybb->settings['ougc_awards_moderators'], false)) ? true : false);

	if($permission)
	{
		eval('$awards_nav = "'.$templates->get('modcp_ougc_awards_nav').'";');
		$modcp_nav = str_replace('<!--OUGC_AWARDS-->', $awards_nav, $modcp_nav);
	}
	else
	{
		$modcp_nav = str_replace('<!--OUGC_AWARDS-->', '', $modcp_nav);
	}

	if($mybb->input['action'] == 'awards' && $permission)
	{
		global $headerinclude, $header, $errors, $theme, $footer;

		add_breadcrumb($lang->ougc_awards_modcp_nav, 'modcp.php?action=awards');
		$error = array();
		// We can give awards from the ModCP
		if($mybb->input['manage'] == 'give')
		{
			if(!($award = $this->get_award($mybb->input['aid'])))
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
				if(!($uid = $this->get_user_by_username($mybb->input['username'])))
				{
					$error[] = $lang->ougc_awards_error_wronguser;
				}
				if($uid && $this->get_gived_award($award['aid'], $uid))
				{
					$error[] = $lang->ougc_awards_error_duplicated;
				}
				if(!empty($error))
				{
					$errors = inline_error($error);
				}
				else
				{
					$this->give_award($award, $uid, $mybb->input['reason']);
					log_moderator_action(array(
						'award' => $award['name'],
						'awardid' => $award['aid'],
						'user' => $mybb->input['username']
					), $lang->ougc_awards_redirect_gived);
					redirect('modcp.php?action=awards', $lang->ougc_awards_redirect_gived);
				}
			}
			add_breadcrumb($lang->ougc_awards_modcp_give);
			$lang->ougc_awards_modcp_title_give = $lang->sprintf($lang->ougc_awards_modcp_title_give, $this->get_award_info('name', $award['aid'], $award['name']));
			eval('$reason = "'.$templates->get('modcp_ougc_awards_manage_reason').'";');
			eval('$content = "'.$templates->get('modcp_ougc_awards_manage').'";');
			eval('$page = "'.$templates->get('modcp_ougc_awards').'";');
			output_page($page);
			exit;
		}
		// We can revoke awards from the ModCP
		elseif($mybb->input['manage'] == 'revoke')
		{
			if(!($award = $this->get_award($mybb->input['aid'])))
			{
				error($lang->ougc_awards_error_wrongaward);
			}
			if($mybb->settings['ougc_awards_hidemcp'] && !$award['visible'])
			{
				$award['visible'] = 1;
			}

			if(!$award['visible'])
			{
				error($lang->ougc_awards_error_wrongaward);
			}

			if($mybb->request_method == 'post')
			{
				if(!($uid = $this->get_user_by_username($mybb->input['username'])))
				{
					$error[] = $lang->ougc_awards_error_wronguser;
				}
				if($uid && !$this->get_gived_award($award['aid'], $uid))
				{
					$error[] = $lang->ougc_awards_error_nowarded;
				}

				if($error)
				{
					$errors = inline_error($error);
				}
				else
				{
					$this->revoke_award($award['aid'], $uid);
					log_moderator_action(array(
						'award' => $award['name'],
						'awardid' => $award['aid'],
						'user' => $mybb->input['username']
					), $lang->ougc_awards_redirect_revoked);
					redirect('modcp.php?action=awards', $lang->ougc_awards_redirect_revoked);
				}
			}

			add_breadcrumb($lang->ougc_awards_modcp_revoke);
			$lang->ougc_awards_modcp_title_give = $lang->sprintf($lang->ougc_awards_modcp_title_give, $this->get_award_info('name', $award['aid'], $award['name']));
			$lang->ougc_awards_modcp_give = $lang->ougc_awards_modcp_revoke;
			eval('$content = "'.$templates->get('modcp_ougc_awards_manage').'";');
			eval('$page = "'.$templates->get('modcp_ougc_awards').'";');
			output_page($page);
			exit;
		}
		elseif($mybb->input['manage'] == 'edit')
		{
			// TODO: Write this part.
					$log = array(
						'awardid' => $award['aid'],
						'uid' => $award['uid']
					);
					log_moderator_action($log, $lang->ougc_awards_redirect_revoked);
		}
		else
		{
			$where = '';
			if(!$mybb->settings['ougc_awards_hidemcp'])
			{
				$where .= "visible='1'";
			}
			if(!($award_list = $this->get_award(false, 'image, name, description, aid', $where)))
			{
				eval('$awards = "'.$templates->get('modcp_ougc_awards_list_empty').'";');
			}
			else
			{
				$awards = '';
				foreach($award_list as $award)
				{
					$trow = alt_trow();

					$award['aid'] = intval($award['aid']);
					$award['image'] = $this->get_icon($award['image'], $award['aid']);
					$award['name'] = $this->get_award_info('name', $award['aid'], $award['name']);
					$award['description'] = $this->get_award_info('desc', $award['aid'], $award['description']);

					eval('$awards .= "'.$templates->get('modcp_ougc_awards_list_award').'";');
				}
			}

			eval('$content = "'.$templates->get('modcp_ougc_awards_list').'";');
			eval('$page = "'.$templates->get('modcp_ougc_awards').'";');
			output_page($page);
			exit;
		}
	}
	elseif($mybb->input['action'] == 'awards')
	{
		error_no_permission();
	}
}

// Show awards in profile function.
function ougc_awards_profile()
{
	global $mybb, $memprofile, $ougc_awards, $templates;

	$memprofile['ougc_awards'] = '';
	$max_profile = (int)$mybb->settings['ougc_awards_profile'];
	if(($max_profile > 0 || $max_profile == -1) && my_strpos($templates->cache['member_profile'], '{$memprofile[\'ougc_awards\']}'))
	{
		global $db, $lang, $theme;

		$limit = '';
		if($max_profile != -1)
		{
			$limit = ' LIMIT '.$max_profile;
		}

		$memprofile['uid'] = (int)$memprofile['uid'];
		// Query our data.
		if(!(bool)$mybb->settings['ougc_awards_multipage'])
		{
			// Get awards
			$query = $db->query('
				SELECT u.*, a.*
				FROM '.TABLE_PREFIX.'ougc_awards_users u
				LEFT JOIN '.TABLE_PREFIX.'ougc_awards a ON (u.aid=a.aid)
				WHERE u.uid=\''.$memprofile['uid'].'\' AND a.visible=\'1\'
				ORDER BY u.date desc'.$limit
			);
		}
		else
		{
			// First we need to figure out the total amount of awards.
			$query = $db->query('
				SELECT COUNT(u.aid) AS total_awards
				FROM '.TABLE_PREFIX.'ougc_awards_users u
				LEFT JOIN '.TABLE_PREFIX.'ougc_awards a ON (u.aid=a.aid)
				WHERE u.uid=\''.$memprofile['uid'].'\' AND a.visible=\'1\'
				ORDER BY u.date desc
			');
			$awardscount = (int)$db->fetch_field($query, 'total_awards');

			// Now we get the awards.
			$multipage = '';
			if((bool)$mybb->settings['ougc_awards_multipage'])
			{
				if($max_profile == -1)
				{
					$max_profile = 10;
				}
				$page = (int)$mybb->input['page'];
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
				$limit = ' LIMIT '.$limit_start.', '.$max_profile;
				$link = get_profile_link($memprofile['uid']);
				$multipage = multipage($awardscount, $max_profile, $page, $link.(!my_strpos($link, '?') ? '?' : '&amp;').'awards');
			}
			$query = $db->query('
				SELECT u.*, a.*
				FROM '.TABLE_PREFIX.'ougc_awards_users u
				LEFT JOIN '.TABLE_PREFIX.'ougc_awards a ON (u.aid=a.aid)
				WHERE u.uid=\''.$memprofile['uid'].'\' AND a.visible=\'1\'
				ORDER BY u.date desc'.$limit
			);
		}

		// Output ouw awards.
		$awards = '';
		while($award = $db->fetch_array($query))
		{
			$trow = alt_trow();

			if($name = $ougc_awards->get_award_info('name', $award['aid']))
			{
				$award['name'] = $name;
			}
			if($description = $ougc_awards->get_award_info('description', $award['aid']))
			{
				$award['description'] = $description;
			}
			if($reason = $ougc_awards->get_award_info('reason', $award['aid'], $award['gid']))
			{
				$award['reason'] = $reason;
			}

			if(empty($award['reason']))
			{
				$award['reason'] = $lang->ougc_awards_pm_noreason;
			}

			$ougc_awards->parse_text($award['reason']);

			$award['image'] = $ougc_awards->get_icon($award['image'], $award['aid']);

			$award['date'] = $lang->sprintf($lang->ougc_awards_profile_tine, my_date($mybb->settings['dateformat'], $award['date']), my_date($mybb->settings['timeformat'], $award['date']));

			eval('$awards .= "'.$templates->get('member_profile_ougc_awards_row').'";');
		}

		// User has no awards.
		if(!$awards)
		{
			eval('$awards = "'.$templates->get('member_profile_ougc_awards_row_empty').'";');
		}

		$lang->ougc_awards_profile_title = $lang->sprintf($lang->ougc_awards_profile_title, htmlspecialchars_uni($memprofile['username']));

		eval('$memprofile[\'ougc_awards\'] = "'.$templates->get('member_profile_ougc_awards').'";');
	}
}

// Show awards in profile function.
function ougc_awards_postbit(&$post)
{
	global $settings;

	$post['ougc_awards'] = '';
	$max_postbit = (int)$settings['ougc_awards_postbit'];

	if($max_postbit < 1 && $max_postbit != -1)
	{
		return;
	}

	// First we need to get our data
	if(THIS_SCRIPT == 'private.php')
	{
		global $db, $pm;

		$query = $db->query('
			SELECT a.aid, a.name, a.image
			FROM '.TABLE_PREFIX.'ougc_awards a
			JOIN '.TABLE_PREFIX.'ougc_awards_users ag ON (ag.aid=a.aid)
			WHERE ag.uid=\''.(int)$post['uid'].'\' AND a.visible=\'1\' AND a.type!=\'1\'
			ORDER BY ag.date desc
			'.($max_postbit == -1 ? '' : 'LIMIT '.$max_postbit)
		);
	
		while($data = $db->fetch_array($query))
		{
			$awards[$data['aid']] = $data;
		}
	}
	elseif(THIS_SCRIPT == 'announcements.php')
	{
		global $db, $aid;

		$query = $db->query('
			SELECT a.aid, a.name, a.image
			FROM '.TABLE_PREFIX.'ougc_awards a
			JOIN '.TABLE_PREFIX.'ougc_awards_users ag ON (ag.aid=a.aid)
			WHERE ag.uid=\''.(int)$post['uid'].'\' AND a.visible=\'1\' AND a.type!=\'1\'
			ORDER BY ag.date desc
			'.($max_postbit == -1 ? '' : 'LIMIT '.$max_postbit)
		);

		while($data = $db->fetch_array($query))
		{
			$awards[$data['aid']] = $data;
		}
	}
	elseif(THIS_SCRIPT == 'showthread.php')
	{
		if($mybb->input['mode'] == 'threaded')
		{
			$query = $db->query('
				SELECT a.aid, a.name, a.image
				FROM '.TABLE_PREFIX.'ougc_awards a
				JOIN '.TABLE_PREFIX.'ougc_awards_users ag ON (ag.aid=a.aid)
				WHERE ag.uid=\''.(int)$post['uid'].'\' AND a.visible=\'1\' AND a.type!=\'1\'
				ORDER BY ag.date desc
				'.($max_postbit == -1 ? '' : 'LIMIT '.$max_postbit)
			);

			while($data = $db->fetch_array($query))
			{
				$awards[$data['aid']] = $data;
			}
		}
		else
		{
			static $ougc_awards_cache = null;
			if(!isset($ougc_awards_cache))
			{
				global $db, $pids;
		
				$query = $db->query('
					SELECT a.aid, a.name, a.image, p.uid
					FROM '.TABLE_PREFIX.'ougc_awards a
					JOIN '.TABLE_PREFIX.'ougc_awards_users ag ON (ag.aid=a.aid)
					JOIN '.TABLE_PREFIX.'posts p ON (p.uid=ag.uid)
					WHERE p.'.$pids.' AND a.visible=\'1\' AND a.type!=\'1\'
					ORDER BY ag.date desc'
				);
				// how to limit by uid here?
				// -- '.($max_postbit == -1 ? '' : 'LIMIT '.$max_postbit)

				while($data = $db->fetch_array($query))
				{
					$ougc_awards_cache[$data['uid']][$data['aid']] = $data;
				}
			}
			$awards = $ougc_awards_cache[$post['uid']];
		}
	}
	else
	{
		global $db, $mybb;

		$query = $db->query('
			SELECT a.aid, a.name, a.image, ag.uid
			FROM '.TABLE_PREFIX.'ougc_awards a
			JOIN '.TABLE_PREFIX.'ougc_awards_users ag ON (ag.aid=a.aid)
			WHERE ag.uid=\''.(int)$post['uid'].'\' AND a.visible=\'1\' AND a.type!=\'1\'
			ORDER BY ag.date desc
			'.($max_postbit == -1 ? '' : 'LIMIT '.$max_postbit)
		);
	
		while($data = $db->fetch_array($query))
		{
			$awards[$data['aid']] = $data;
		}
	}

	// User has no awards
	if(empty($awards))
	{
		return;
	}

	global $templates, $ougc_awards;

	$count = 0;

	// Format the awards
	foreach($awards as $award)
	{
		$award['aid'] = (int)$award['aid'];
		if($name = $ougc_awards->get_award_info('name', $award['aid']))
		{
			$award['name'] = $name;
		}
		$award['name_ori'] = $award['name'];
		$award['name'] = strip_tags($award['name_ori']);

		$award['image'] = $ougc_awards->get_icon($award['image'], $award['aid']);

		if($max_postbit == -1 || $count < $max_postbit)
		{
			$count++;
			$br = '';
			if($count == 1)
			{
				$br = '<br />'; // We insert a break if it is the first award.
			}

			eval('$new_award = "'.$templates->get('postbit_ougc_awards', 1, 0).'";');
			$post['ougc_awards'] .= trim($new_award);
		}
	}

	$post['user_details'] = str_replace('<!--OUGC_AWARDS-->', $post['ougc_awards'], $post['user_details']);
}

class OUGC_Awards
{
	// Define our ACP url
	protected $acp_url = 'index.php?module=user-ougc_awards';

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

	// Award data
	public $award_data = array();

	// Construct the data (?)
	function __construct()
	{
	}

	// Load lang files from our plugin directory, not from mybb default.
	function lang_load($datahandler=false, $force=false)
	{
		global $lang;

		// Check if already loaded
		if(!$force)
		{
			if(defined('IN_ADMINCP') && isset($lang->ougc_awards_plugin))
			{
				return;
			}
			elseif(!defined('IN_ADMINCP') && isset($lang->ougc_awards_modcp_nav))
			{
				return;
			}
		}

		$language_bu = $lang->language;
		$lang->load((defined('IN_ADMINCP') ? 'user_' : '').'ougc_awards', $datahandler);
		$lang->load('ougc_awards_extra_vals', true, true);
		$lang->language = $language_bu;
	}

	// Modify acp url
	function set_url($params)
	{
		if(is_array($params) && !empty($params))
		{
			global $PL;
			$PL or require_once PLUGINLIBRARY;

			$this->acp_url = $PL->url_append($this->acp_url, $params);
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

		return $PL->url_append($this->acp_url, $params);
	}

	// Get the rate icon
	function get_icon($img, $aid)
	{
		if(!isset($this->cache['images'][$aid]))
		{
			global $settings;

			// The image is suppose to be external.
			if(my_strpos($img, 'ttp:/') || my_strpos($img, 'ttps:/')) 
			{
				$this->cache['images'][$aid] = $img;
			}
			// The image is suppose to be internal inside our images folder.
			elseif(!my_strpos($img, '/') && !empty($img) && file_exists(MYBB_ROOT.'/images/ougc_awards/'.$img)) 
			{
				$this->cache['images'][$aid] = $settings['bburl'].'/images/ougc_awards/'.htmlspecialchars_uni($img);
			}
			// Image is suppose to be internal.
			elseif(!empty($img) && file_exists(MYBB_ROOT.'/'.$img))
			{
				$this->cache['images'][$aid] = $settings['bburl'].'/'.htmlspecialchars_uni($img);
			}
			// Default image.
			else
			{
				$this->cache['images'][$aid] = $settings['bburl'].'/images/ougc_awards/default.png';
			}
		}

		return $this->cache['images'][$aid];
	}

	// Set data award
	function set_award_data($aid=false)
	{
		if($aid !== false && (int)$aid > 0 )
		{
			$award = $this->get_award($aid);

			$this->award_data = array(
				'name'			=> $award['name'],
				'description'	=> $award['description'],
				'image'			=> $award['image'],
				'visible'		=> (int)$award['visible'],
				'pm'			=> $award['pm'],
				'type'			=> (int)$award['type'],
			);
		}
		else
		{
			$this->award_data = array(
				'name' 			=> '',
				'description' 	=> '',
				'image' 		=> '',
				'visible' 		=> 1,
				'pm' 			=> '',
				'type'		 	=> 0,
			);
		}

		global $mybb;

		if($mybb->request_method == 'post')
		{
			foreach((array)$mybb->input as $key => $value)
			{
				if(isset($this->award_data[$key]))
				{
					$this->award_data[$key] = $value;
				}
			}
		}
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

	// Validate award data
	function validate_award()
	{
		global $mybb;

		$valid = true;

		if(!$this->award_data['name'] || isset($foo{100}))
		{
			$this->validation_errors[] = 'Invalid name';
			$valid = false;
		}

		if(isset($this->award_data['description']{255}))
		{
			$this->validation_errors[] = 'Invalid description';
			$valid = false;
		}

		if(isset($this->award_data['image']{255}))
		{
			$this->validation_errors[] = 'Invalid image';
			$valid = false;
		}

		return $valid;
	}

	// Insert a new rate to the DB
	function insert_award($data, $aid=null, $update=false)
	{
		global $db;

		$clean_data = array();
		if(isset($data['name']))
		{
			$clean_data['name'] = $db->escape_string($data['name']);
		}
		if(isset($data['description']))
		{
			$clean_data['description'] = $db->escape_string($data['description']);
		}
		if(isset($data['image']))
		{
			$clean_data['image'] = $db->escape_string($data['image']);
		}
		if(isset($data['pm']))
		{
			$clean_data['pm'] = $db->escape_string($data['pm']);
		}
		if(isset($data['visible']))
		{
			$clean_data['visible'] = (int)$data['visible'];
		}
		if(isset($data['type']))
		{
			$clean_data['type'] = (int)$data['type'];
		}

		if($update && $clean_data)
		{
			$this->aid = (int)$aid;
			$db->update_query('ougc_awards', $clean_data, 'aid=\''.$this->aid.'\'');
		}
		elseif($clean_data)
		{
			$this->aid = (int)$db->insert_query('ougc_awards', $clean_data);
		}
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

	// Log admin action
	function log_action()
	{
		if(defined('IN_ADMINCP'))
		{
			if($this->aid)
			{
				if($this->uid)
				{
					log_admin_action($this->aid, $this->uid);
				}
				else
				{
					log_admin_action($this->aid);
				}
			}
			elseif($this->gid)
			{
				log_admin_action($this->gid);
			}
			else
			{
				log_admin_action();
			}
		}
		else
		{
			// modcp
		}
	}
	// Get user by username
	function get_user_by_username($username)
	{
		global $db;

		$query = $db->simple_select('users', 'uid, username', 'LOWER(username)=\''.$db->escape_string(my_strtolower($username)).'\'', array('limit' => 1));

		if($user = $db->fetch_array($query))
		{
			return array('uid' => (int)$user['uid'], 'username' => $user['username']);
		}

		// Lets assume that admin inserted a uid..
		$query = $db->simple_select('users', 'uid, username', 'uid=\''.(int)$username.'\'', array('limit' => 1));

		if($user = $db->fetch_array($query))
		{
			return array('uid' => (int)$user['uid'], 'username' => $user['username']);
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
			'date'		=> TIME_NOW
		);

		$db->insert_query('ougc_awards_users', $insert_data);

		$this->send_pm($award, $user, $reason);
	}

	// Send a PM when award is given.
	function send_pm($award, $user, $reason)
	{
		global $settings;

		// Check if send this award.
		if((!$settings['ougc_awards_pmuser'] && !$settings['ougc_awards_pmuserid']) || !$award['aid'] || !$award['visible'] || !$settings['enablepms'])
		{
			return;
		}

		// Get the award PM content.
		if($message = $this->get_award_info('pm', $award['aid']))
		{
			$award['pm'] = $message;
		}
		unset($message);

		if(empty($award['pm']))
		{
			return;
		}

		// Set up teh pm handler
		require_once MYBB_ROOT.'inc/datahandlers/pm.php';
		$pmhandler = new PMDataHandler;
		$pmhandler->admin_override = true;

		// Figure out if to use current connected user as PM sender.
		$uid = (int)$settings['ougc_awards_pmuserid'];
		if((bool)$settings['ougc_awards_pmuser'])
		{
			$uid = (int)$GLOBALS['mybb']->user['uid'];
		}
		if($uid < 1)
		{
			$uid = -1;
		}

		global $lang;

		// Get the award name.
		if($name = $this->get_award_info('name', $award['aid']))
		{
			$award['name'] = $name;
		}
		unset($name);

		$award['name'] = strip_tags($award['name']);

		$pmhandler->set_data(array(
			'subject'	=>	$lang->sprintf($lang->ougc_awards_pm_title, $award['name']),
			'message'	=>	$lang->sprintf($award['pm'], htmlspecialchars_uni($user['username']), $award['name'], (!empty($reason) ? htmlspecialchars_uni($reason) : $lang->ougc_awards_pm_noreason), $this->get_icon($award['image'], $award['aid']), htmlspecialchars_uni($mybb->settings['bbname'])),
			'icon'		=>	-1,
			'fromid'	=>	$uid,
			'toid'		=>	array((int)$user['uid'])
		));

		if($pmhandler->validate_pm())
		{
			$pmhandler->insert_pm();
			return true;
		}

		return false;
	}

	// I liked as I did the pm thing, so what about award name, description, and reasons?
	function get_award_info($type, $aid, $gid=0)
	{
		global $lang;
		$this->lang_load();
		$this->lang_load(true, true);
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

		$clean_data = array();
		if(isset($data['date']))
		{
			$clean_data['date'] = (int)$data['date'];
		}
		if(isset($data['reason']))
		{
			$clean_data['reason'] = $db->escape_string($data['reason']);
		}

		$args = array(
			'gid'			=> &$this->gid,
			'data'			=> &$data,
			'clean_data'	=> &$clean_data,
		);

		$plugins->run_hooks('ougc_awards_update_gived', $args);

		if($clean_data)
		{

			$db->update_query('ougc_awards_users', $clean_data, 'gid=\''.$this->gid.'\'');
		}
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

		return (bool)$PL->is_member($groups);
	}
}
$GLOBALS['ougc_awards'] = new OUGC_Awards;