<?php

/***************************************************************************
 *
 *    OUGC Awards plugin (/inc/plugins/ougc/Awards/core.php)
 *    Author: Omar Gonzalez
 *    Copyright: © 2012 Omar Gonzalez
 *
 *    Website: https://ougc.network
 *
 *    Manage a powerful awards system for your community.
 *
 ***************************************************************************
 ****************************************************************************
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 ****************************************************************************/

declare(strict_types=1);

namespace ougc\Awards\Core;

use MybbStuff_MyAlerts_AlertManager;

use MybbStuff_MyAlerts_Entity_Alert;

use PMDataHandler;

use postParser;

use stdClass;

use function ougc\Awards\Admin\pluginIsInstall;

use function ougc\Awards\Admin\pluginInfo;

use const ougc\Awards\ROOT;

use const TIME_NOW;

const URL = 'index.php?module=user-ougc_awards';

const INFORMATION_TYPE_TEMPLATE = 1;

const INFORMATION_TYPE_PRIVATE_MESSAGE = 2;

const INFORMATION_TYPE_REASON = 3;

const INFORMATION_TYPE_NAME = 4;

const INFORMATION_TYPE_DESCRIPTION = 5;

const ADMIN_PERMISSION_ENABLE = 1;

const ADMIN_PERMISSION_DISABLE = 0;

const ADMIN_PERMISSION_DELETE = -1;

const AWARD_TEMPLATE_TYPE_CLASS = 1;

const AWARD_TEMPLATE_TYPE_CUSTOM = 2;

const REQUEST_STATUS_PENDING = 1;

const GRANT_STATUS_EVERYWHERE = 0;

const GRANT_STATUS_PROFILE = 0;

const GRANT_STATUS_POSTS = 2;

const GRANT_STATUS_VISIBLE = 1;

const REQUEST_STATUS_REJECTED = -1;

const REQUEST_STATUS_ACCEPTED = 0;

const REQUEST_STATUS_OPEN = 1;

const AWARD_STATUS_DISABLED = 0;

const AWARD_STATUS_ENABLED = 1;

const TABLES_DATA = [
    'ougc_awards' => [
        'aid' => [
            'type' => 'INT',
            'unsigned' => true,
            'auto_increment' => true,
            'primary_key' => true
        ],
        'cid' => [
            'type' => 'INT',
            'unsigned' => true
        ],
        'name' => [
            'type' => 'VARCHAR',
            'size' => 100,
            'default' => ''
        ],
        'description' => [
            'type' => 'VARCHAR',
            'size' => 255,
            'default' => ''
        ],
        'image' => [
            'type' => 'VARCHAR',
            'size' => 255,
            'default' => ''
        ],
        'template' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 0
        ],
        'disporder' => [
            'type' => 'SMALLINT',
            'unsigned' => true,
            'default' => 0
        ],
        'allowrequests' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 1
        ],
        'visible' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 1
        ],
        'pm' => [
            'type' => 'TEXT',
            'null' => true,
        ],
        'type' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 0
        ]
    ],
    'ougc_awards_users' => [
        'gid' => [
            'type' => 'INT',
            'unsigned' => true,
            'auto_increment' => true,
            'primary_key' => true
        ],
        'uid' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        'oid' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        'aid' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        'rid' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        'tid' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        'thread' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        'pm' => [
            'type' => 'TEXT',
            'null' => true,
        ],
        'date' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        'disporder' => [
            'type' => 'SMALLINT',
            'unsigned' => true,
            'default' => 0
        ],
        'visible' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 1
        ],
        //'visible' => ['uidaid' => 'uid,aid', 'aiduid' => 'aid,uid']
    ],
    'ougc_awards_owners' => [
        'oid' => [
            'type' => 'INT',
            'unsigned' => true,
            'auto_increment' => true,
            'primary_key' => true
        ],
        'uid' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        'aid' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        'date' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
    ],
    'ougc_awards_categories' => [
        'cid' => [
            'type' => 'INT',
            'unsigned' => true,
            'auto_increment' => true,
            'primary_key' => true
        ],
        'name' => [
            'type' => 'VARCHAR',
            'size' => 100,
            'default' => ''
        ],
        'description' => [
            'type' => 'VARCHAR',
            'size' => 255,
            'default' => ''
        ],
        'disporder' => [
            'type' => 'SMALLINT',
            'unsigned' => true,
            'default' => 0
        ],
        'allowrequests' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 1
        ],
        'visible' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 1
        ],
    ],
    'ougc_awards_requests' => [
        'rid' => [
            'type' => 'INT',
            'unsigned' => true,
            'auto_increment' => true,
            'primary_key' => true
        ],
        'aid' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        'uid' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        'muid' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        'message' => [
            'type' => 'TEXT',
            'null' => true,
        ],
        'status' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 1
        ],
    ],
    'ougc_awards_tasks' => [
        'tid' => [
            'type' => 'INT',
            'unsigned' => true,
            'auto_increment' => true,
            'primary_key' => true
        ],
        'name' => [
            'type' => 'VARCHAR',
            'size' => 100,
            'default' => ''
        ],
        'description' => [
            'type' => 'VARCHAR',
            'size' => 255,
            'default' => ''
        ],
        'disporder' => [
            'type' => 'SMALLINT',
            'unsigned' => true,
            'default' => 0
        ],
        'active' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 1
        ],
        'logging' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 1
        ],
        'requirements' => [
            'type' => 'VARCHAR',
            'size' => 200,
            'default' => ''
        ],
        'give' => [
            'type' => 'TEXT',
            'null' => true,
        ],
        'reason' => [
            'type' => 'TEXT',
            'null' => true,
        ],
        'thread' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        'disporder' => [
            'type' => 'SMALLINT',
            'unsigned' => true,
            'default' => 0
        ],
        'revoke' => [
            'type' => 'TEXT',
            'null' => true,
        ],
        'usergroups' => [
            'type' => 'TEXT',
            'null' => true,
        ],
        'additionalgroups' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 1
        ],
        'posts' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        'poststype' => [
            'type' => 'VARCHAR',
            'size' => 2,
            'default' => ''
        ],
        'threads' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        'threadstype' => [
            'type' => 'VARCHAR',
            'size' => 2,
            'default' => ''
        ],
        'fposts' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        'fpoststype' => [
            'type' => 'VARCHAR',
            'size' => 2,
            'default' => ''
        ],
        'fpostsforums' => [
            'type' => 'TEXT',
            'null' => true,
        ],
        'fthreads' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        'fthreadstype' => [
            'type' => 'VARCHAR',
            'size' => 2,
            'default' => ''
        ],
        'fthreadsforums' => [
            'type' => 'TEXT',
            'null' => true,
        ],
        'registered' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        'registeredtype' => [
            'type' => 'VARCHAR',
            'size' => 2,
            'default' => ''
        ],
        'online' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        'onlinetype' => [
            'type' => 'VARCHAR',
            'size' => 2,
            'default' => ''
        ],
        'reputation' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        'reputationtype' => [
            'type' => 'VARCHAR',
            'size' => 2,
            'default' => ''
        ],
        'referrals' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        'referralstype' => [
            'type' => 'VARCHAR',
            'size' => 2,
            'default' => ''
        ],
        'warnings' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        'warningstype' => [
            'type' => 'VARCHAR',
            'size' => 2,
            'default' => ''
        ],
        'newpoints' => [
            'type' => 'FLOAT',
            'unsigned' => true,
            'default' => 0
        ],
        'newpointstype' => [
            'type' => 'VARCHAR',
            'size' => 2,
            'default' => ''
        ],
        'previousawards' => [
            'type' => 'TEXT',
            'null' => true,
        ],
        'profilefields' => [
            'type' => 'TEXT',
            'null' => true,
        ],
        'mydownloads' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        'mydownloadstype' => [
            'type' => 'VARCHAR',
            'size' => 2,
            'default' => ''
        ],
        'myarcadechampions' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        'myarcadechampionstype' => [
            'type' => 'VARCHAR',
            'size' => 2,
            'default' => ''
        ],
        'myarcadescores' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        'myarcadescorestype' => [
            'type' => 'VARCHAR',
            'size' => 2,
            'default' => ''
        ],
        'ougc_customrep_r' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        'ougc_customreptype_r' => [
            'type' => 'VARCHAR',
            'size' => 2,
            'default' => ''
        ],
        'ougc_customrepids_r' => [
            'type' => 'TEXT',
            'null' => true,
        ],
        'ougc_customrep_g' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        'ougc_customreptype_g' => [
            'type' => 'VARCHAR',
            'size' => 2,
            'default' => ''
        ],
        'ougc_customrepids_g' => [
            'type' => 'TEXT',
            'null' => true,
        ],
        'ruleScripts' => [
            'type' => 'TEXT',
            'null' => true,
        ],
    ],
    'ougc_awards_tasks_logs' => [
        'lid' => [
            'type' => 'INT',
            'unsigned' => true,
            'auto_increment' => true,
            'primary_key' => true
        ],
        'tid' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        'uid' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        'gave' => [
            'type' => 'TEXT',
            'null' => true,
        ],
        'revoked' => [
            'type' => 'TEXT',
            'null' => true,
        ],
        'date' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ]
    ],
    'ougc_awards_presets' => [
        'pid' => [
            'type' => 'INT',
            'unsigned' => true,
            'auto_increment' => true,
            'primary_key' => true
        ],
        'uid' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        'name' => [
            'type' => 'VARCHAR',
            'size' => 100,
            'default' => ''
        ],
        'hidden' => [
            'type' => 'TEXT',
            'null' => true,
        ],
        'visible' => [
            'type' => 'TEXT',
            'null' => true,
        ],
    ]
];

const FIELDS_DATA = [
    'users' => [
        'ougc_awards' => 'text NULL',
        'ougc_awards_owner' => "tinyint(1) NOT NULL DEFAULT '0'",
        'ougc_awards_preset' => "int NOT NULL DEFAULT '0'"
    ]
];

function addHooks(string $namespace)
{
    global $plugins;

    $namespaceLowercase = strtolower($namespace);
    $definedUserFunctions = get_defined_functions()['user'];

    foreach ($definedUserFunctions as $callable) {
        $namespaceWithPrefixLength = strlen($namespaceLowercase) + 1;

        if (substr($callable, 0, $namespaceWithPrefixLength) == $namespaceLowercase . '\\') {
            $hookName = substr_replace($callable, '', 0, $namespaceWithPrefixLength);

            $priority = substr($callable, -2);

            if (is_numeric(substr($hookName, -2))) {
                $hookName = substr($hookName, 0, -2);
            } else {
                $priority = 10;
            }

            $plugins->add_hook($hookName, $callable, $priority);
        }
    }
}

function loadLanguage(bool $isDataHandler = false): bool
{
    global $lang;

    if (!isset($lang->ougcAwards)) {
        if (defined('IN_ADMINCP')) {
            $lang->load('user_ougc_awards', $isDataHandler);
        } else {
            $lang->load('ougc_awards', $isDataHandler);
        }

        $lang->load('ougc_awards_extra_vals', true, true);
    }

    return true;
}

function pluginLibraryRequirements(): stdClass
{
    return (object)pluginInfo()['pl'];
}

function loadPluginLibrary(): bool
{
    global $PL, $lang;

    loadLanguage();

    $fileExists = file_exists(PLUGINLIBRARY);

    if ($fileExists && !($PL instanceof PluginLibrary)) {
        require_once PLUGINLIBRARY;
    }

    if (!$fileExists || $PL->version < pluginLibraryRequirements()->version) {
        flash_message(
            $lang->sprintf(
                $lang->ougcAwardsPluginLibrary,
                pluginLibraryRequirements()->url,
                pluginLibraryRequirements()->version
            ),
            'error'
        );

        admin_redirect('index.php?module=config-plugins');
    }

    return true;
}

function urlHandler(string $newUrl = ''): string
{
    static $setUrl = URL;

    if ($newUrl = trim($newUrl)) {
        $setUrl = $newUrl;
    }

    return $setUrl;
}

function urlHandlerSet(string $newUrl)
{
    urlHandler($newUrl);
}

function urlHandlerGet(): string
{
    return urlHandler();
}

function urlHandlerBuild(array $urlAppend = [], bool $fetchImportUrl = false, bool $encode = true): string
{
    global $PL;

    if (!is_object($PL)) {
        $PL or require_once PLUGINLIBRARY;
    }

    if ($fetchImportUrl === false) {
        if ($urlAppend && !is_array($urlAppend)) {
            $urlAppend = explode('=', $urlAppend);
            $urlAppend = [$urlAppend[0] => $urlAppend[1]];
        }
    }

    return $PL->url_append(urlHandlerGet(), $urlAppend, '&amp;', $encode);
}

function getTemplateName(string $templateName = ''): string
{
    $templatePrefix = '';

    if ($templateName) {
        $templatePrefix = '_';
    }

    return "ougcawards{$templatePrefix}{$templateName}";
}

function getTemplate(string $templateName = '', bool $enableHTMLComments = true): string
{
    global $templates;

    if (DEBUG) {
        $filePath = ROOT . "/templates/{$templateName}.html";

        $templateContents = file_get_contents($filePath);

        $templates->cache[getTemplateName($templateName)] = $templateContents;
    } elseif (my_strpos($templateName, '/') !== false) {
        $templateName = substr($templateName, strpos($templateName, '/') + 1);
    }

    return $templates->render(getTemplateName($templateName), true, $enableHTMLComments);
}

function getSetting(string $settingKey = '')
{
    global $mybb;

    return isset(SETTINGS[$settingKey]) ? SETTINGS[$settingKey] : (
    isset($mybb->settings['ougc_awards_' . $settingKey]) ? $mybb->settings['ougc_awards_' . $settingKey] : false
    );
}

function executeTask(): bool
{
    global $mybb, $db, $lang, $plugins;
    
    loadLanguage();

    $query = $db->simple_select('ougc_awards_tasks', '*', 'active=1');

    while ($award_task = $db->fetch_array($query)) {
        $award_task['tid'] = (int)$award_task['tid'];

        $where_clause = $left_join = array();

        $requirements = explode(',', $award_task['requirements']);

        foreach (
            array(
                'posts' => 'postnum',
                'threads' => 'threadnum',
                'referrals' => 'referrals',
                'warnings' => 'warningpoints',
                'newpoints' => 'newpoints'
            ) as $k => $c
        ) {
            $t = $k . 'type';
            if (in_array($k, $requirements) && (int)$award_task[$k] >= 0 && !empty($award_task[$t])) {
                $where_clause[] = "u.{$c}{$award_task[$t]}'{$award_task[$k]}'";
            }
        }

        foreach (array('reputation' => 'reputation') as $k => $c) {
            $t = $k . 'type';
            if (in_array($k, $requirements) && !empty($award_task[$t])) {
                $where_clause[] = "u.{$c}{$award_task[$t]}'{$award_task[$k]}'";
            }
        }

        if (in_array(
                'registered',
                $requirements
            ) && (int)$award_task['registered'] >= 0 && !empty($award_task['registeredtype'])) {
            switch ($award_task['registeredtype']) {
                case 'hours':
                    $regdate = $award_task['registered'] * 60 * 60;
                    break;
                case 'days':
                    $regdate = $award_task['registered'] * 60 * 60 * 24;
                    break;
                case 'weeks':
                    $regdate = $award_task['registered'] * 60 * 60 * 24 * 7;
                    break;
                case 'months':
                    $regdate = $award_task['registered'] * 60 * 60 * 24 * 30;
                    break;
                case 'years':
                    $regdate = $award_task['registered'] * 60 * 60 * 24 * 365;
                    break;
                default:
                    $regdate = $award_task['registered'] * 60 * 60 * 24;
                    break;
            }
            $where_clause[] = "u.regdate<='" . (TIME_NOW - $regdate) . "'";
        }

        if (in_array('online', $requirements) && (int)$award_task['online'] >= 0 && !empty($award_task['onlinetype'])) {
            switch ($award_task['onlinetype']) {
                case 'hours':
                    $timeonline = $award_task['online'] * 60 * 60;
                    break;
                case 'days':
                    $timeonline = $award_task['online'] * 60 * 60 * 24;
                    break;
                case 'weeks':
                    $timeonline = $award_task['online'] * 60 * 60 * 24 * 7;
                    break;
                case 'months':
                    $timeonline = $award_task['online'] * 60 * 60 * 24 * 30;
                    break;
                case 'years':
                    $timeonline = $award_task['online'] * 60 * 60 * 24 * 365;
                    break;
                default:
                    $timeonline = $award_task['online'] * 60 * 60 * 24;
                    break;
            }
            $where_clause[] = "u.timeonline>='{$timeonline}'";
        }

        if (in_array('usergroups', $requirements) && !empty($award_task['usergroups'])) {
            $usergroups = array_map('intval', explode(',', $award_task['usergroups']));
            $group_clause = array("usergroup IN ('" . implode("','", $usergroups) . "')");
            if ($award_task['additionalgroups']) {
                foreach ($usergroups as $gid) {
                    switch ($db->type) {
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
            $where_clause[] = '(' . implode(' OR ', $group_clause) . ')';
        }

        if (in_array('fposts', $requirements) && (int)$award_task['fposts'] >= 0 && !empty($award_task['fposts'])) {
            $left_join[] = 'LEFT JOIN (
				SELECT p.uid, COUNT(p.pid) AS fposts FROM ' . $db->table_prefix . 'posts p
				LEFT JOIN ' . $db->table_prefix . "threads t ON (t.tid=p.tid)
				WHERE p.fid='" . (int)$award_task['fpostsforums'] . "' AND t.visible > 0 AND p.visible > 0
				GROUP BY p.uid
			) p ON (p.uid=u.uid)";
            $where_clause[] = "p.fposts{$award_task['fpoststype']}'{$award_task['fposts']}'";
        }

        if (in_array(
                'fthreads',
                $requirements
            ) && (int)$award_task['fthreads'] >= 0 && !empty($award_task['fthreads'])) {
            $left_join[] = 'LEFT JOIN (
				SELECT uid, COUNT(tid) AS fthreads FROM ' . $db->table_prefix . "threads
				WHERE fid='" . (int)$award_task['fthreadsforums'] . "' AND visible > 0 AND closed NOT LIKE 'moved|%'
				GROUP BY uid
			) t ON (t.uid=u.uid)";
            $where_clause[] = "t.fthreads{$award_task['fthreadstype']}'{$award_task['fthreads']}'";
        }

        if (in_array('previousawards', $requirements) && !empty($award_task['previousawards'])) {
            $awards_cache = $mybb->cache->read('ougc_awards');
            $aids = implode("','", array_keys($awards_cache['awards']));
            foreach (array_map('intval', explode(',', $award_task['previousawards'])) as $aid) {
                $left_join[] = "LEFT JOIN (
					SELECT ua.uid, ua.aid, COUNT(ua.gid) AS previous_awards_{$aid} FROM " . $db->table_prefix . "ougc_awards_users ua
					WHERE ua.aid='{$aid}' AND ua.aid IN ('{$aids}')
					GROUP BY ua.uid
				) a_{$aid} ON (a_{$aid}.uid=u.uid)";
                $where_clause[] = "a_{$aid}.previous_awards_{$aid}>='1'";
            }
        }

        if (in_array('profilefields', $requirements) && !empty($award_task['profilefields'])) {
            $left_join[] = 'LEFT JOIN ' . $db->table_prefix . 'userfields uf ON (uf.ufid=u.uid)';
            foreach (array_map('intval', explode(',', $award_task['profilefields'])) as $fid) {
                $where_clause[] = 'uf.fid' . (int)$fid . "!=''";
            }
        }

        if (in_array(
                'mydownloads',
                $requirements
            ) && (int)$award_task['mydownloads'] >= 0 && !empty($award_task['mydownloads'])) {
            $left_join[] = 'LEFT JOIN (SELECT submitter_uid, COUNT(did) AS downloads FROM ' . $db->table_prefix . "mydownloads_downloads WHERE hidden='0' GROUP BY submitter_uid) myd ON (myd.submitter_uid=u.uid)";
            $where_clause[] = "myd.downloads{$award_task['mydownloadstype']}'{$award_task['mydownloads']}'";
        }

        // TODO myarcadechampions

        if (in_array(
                'myarcadescores',
                $requirements
            ) && (int)$award_task['myarcadescores'] >= 0 && !empty($award_task['myarcadescores'])) {
            $left_join[] = 'LEFT JOIN (
				SELECT s.uid, s.gid, COUNT(s.sid) AS scores FROM ' . $db->table_prefix . 'arcadescores s
				LEFT JOIN ' . $db->table_prefix . "arcadegames g ON (g.gid=s.gid)
				WHERE g.active='1'
				GROUP BY s.uid
			) mya ON (mya.uid=u.uid)";
            $where_clause[] = "mya.scores{$award_task['myarcadescorestype']}'{$award_task['myarcadescores']}'";
        }

        if (in_array(
                'ougc_customrep_r',
                $requirements
            ) && (int)$award_task['ougc_customrep_r'] >= 0 && !empty($award_task['ougc_customrep_r']) && $db->table_exists(
                'ougc_customrep'
            )) {
            $left_join[] = 'LEFT JOIN (
				SELECT p.uid, l.rid, COUNT(l.lid) AS ougc_custom_reputation_receieved FROM ' . $db->table_prefix . 'ougc_customrep_log l
				LEFT JOIN ' . $db->table_prefix . 'ougc_customrep r ON (r.rid=l.rid)
				LEFT JOIN ' . $db->table_prefix . 'posts p ON (p.pid=l.pid)
				LEFT JOIN ' . $db->table_prefix . "threads t ON (t.tid=p.tid)
				WHERE r.visible='1' AND t.visible > 0 AND p.visible > 0 AND r.rid IN ('" . implode(
                    "','",
                    array_map('intval', explode(',', $award_task['ougc_customrepids_r']))
                ) . "')
				GROUP BY p.uid
			) ocr ON (ocr.uid=u.uid)";
            $where_clause[] = "ocr.ougc_custom_reputation_receieved{$award_task['ougc_customreptype_r']}'{$award_task['ougc_customrep_r']}'";
        }

        if (in_array(
                'ougc_customrep_g',
                $requirements
            ) && (int)$award_task['ougc_customrep_g'] >= 0 && !empty($award_task['ougc_customrep_g']) && $db->table_exists(
                'ougc_customrep'
            )) {
            $left_join[] = 'LEFT JOIN (
				SELECT l.uid, l.rid, COUNT(l.lid) AS ougc_custom_reputation_gived FROM ' . $db->table_prefix . 'ougc_customrep_log l
				LEFT JOIN ' . $db->table_prefix . 'ougc_customrep r ON (r.rid=l.rid)
				LEFT JOIN ' . $db->table_prefix . 'posts p ON (p.pid=l.pid)
				LEFT JOIN ' . $db->table_prefix . "threads t ON (t.tid=p.tid)
				WHERE r.visible='1' AND t.visible > 0 AND p.visible > 0 AND r.rid IN ('" . implode(
                    "','",
                    array_map('intval', explode(',', $award_task['ougc_customrepids_g']))
                ) . "')
				GROUP BY l.uid
			) ocg ON (ocg.uid=u.uid)";
            $where_clause[] = "ocg.ougc_custom_reputation_gived{$award_task['ougc_customreptype_g']}'{$award_task['ougc_customrep_g']}'";
        }

        $log_inserts = array();

        if (is_object($plugins)) {
            $args = array(
                'task' => &$task,
                'award_task' => &$award_task,
                'left_join' => &$left_join,
                'where_clause' => &$where_clause
            );

            $plugins->run_hooks('task_ougc_awards', $args);
        }

        $query2 = $db->simple_select(
            'users u ' . implode(' ', $left_join),
            'u.uid, u.username',
            implode(' AND ', $where_clause)
        );

        while ($user = $db->fetch_array($query2)) {
            $log = false;
            $gave_cache = $revoke_cache = $aids = $gave_list = $revoke_list = array();

            if (($award_task['give'] && !$award_task['allowmultiple']) || $award_task['revoke']) {
                $q1 = $db->simple_select(
                    'ougc_awards_users',
                    'gid, aid',
                    "uid='{$user['uid']}' AND aid IN ('" . implode(
                        "','",
                        explode(',', $award_task['revoke'] . ',' . $award_task['give'])
                    ) . "')"
                );
                while ($gave = $db->fetch_array($q1)) {
                    if (my_strpos(',' . $award_task['give'] . ',', ',' . $gave['aid'] . ',') !== false) {
                        $gave_cache[] = $gave['aid'];
                    }
                    if (my_strpos(',' . $award_task['revoke'] . ',', ',' . $gave['aid'] . ',') !== false) {
                        $revoke_cache[$gave['gid']] = $gave['aid'];
                    }
                }
            }

            if ($award_task['give']) {
                $aids = array_flip(explode(',', $award_task['give']));
                if (!$award_task['allowmultiple']) {
                    foreach ($gave_cache as $aid) {
                        if (isset($aids[$aid])) {
                            unset($aids[$aid]);
                        }
                    }
                }

                if (!empty($aids)) {
                    foreach ($aids as $aid => $i) {
                        $gave_list[] = $aid;
                        $award = awardGet($aid);
                        $result = grantInsert(
                            $award,
                            $user,
                            null,
                            $award_task['thread'],
                            $award_task['tid']
                        ); // reason shouldn't be supplied.
                        $log = $result > 0 ?: false;
                    }
                }
            }

            if ($award_task['revoke']) {
                foreach ($revoke_cache as $gid => $aid) {
                    $revoke_list[] = $aid;
                    grantDelete($gid);
                    $log = true;
                }
            }

            !$log or $log_inserts[] = array(
                'tid' => (int)$award_task['tid'],
                'uid' => (int)$user['uid'],
                'gave' => $db->escape_string(implode(',', $gave_list)),
                'revoked' => $db->escape_string(implode(',', $revoke_list)),
                'date' => TIME_NOW
            );
        }

        if (count($log_inserts) > 0) {
            $db->insert_query_multiple('ougc_awards_tasks_logs', $log_inserts);

            $log_inserts = array();
        }
    }

    cacheUpdate();

    return true;
}

function allowImports(): bool
{
    return getSetting('allowImports') && pluginIsInstalled();
}

function getUser(int $userID)
{
    global $db;

    $userData = [];

    $dbQuery = $db->simple_select('users', '*', "uid='{$userID}'");

    if ($db->num_rows($dbQuery)) {
        return $db->fetch_array($dbQuery);
    }

    return $userData;
}

function getUserByUserName(string $userName)
{
    global $db;

    $userData = [];

    $dbQuery = $db->simple_select(
        'users',
        'uid, username',
        "LOWER(username)='{$db->escape_string(my_strtolower($userName))}'",
        ['limit' => 1]
    );

    if ($db->num_rows($dbQuery)) {
        return $db->fetch_array($dbQuery);
    }

    return $userData;
}

function presetInsert(array $presetData, int $presetID = 0, bool $updatePreset = false): int
{
    global $db;

    $insertData = [];

    if (isset($presetData['uid'])) {
        $insertData['uid'] = (int)$presetData['uid'];
    }

    if (isset($presetData['name'])) {
        $insertData['name'] = $db->escape_string($presetData['name']);
    }

    if (isset($presetData['hidden'])) {
        $insertData['hidden'] = $db->escape_string($presetData['hidden']);
    }

    if (isset($presetData['visible'])) {
        $insertData['visible'] = $db->escape_string($presetData['visible']);
    }

    if ($updatePreset) {
        return $db->update_query('ougc_awards_presets', $insertData, "pid='{$presetID}'");
    }

    return $db->insert_query('ougc_awards_presets', $insertData);
}

function presetUpdate(array $presetData, int $presetID): int
{
    return presetInsert($presetData, $presetID, true);
}

function presetGet(int $presetID): array
{
    global $db;

    $presetData = [];

    $dbQuery = $db->simple_select('ougc_awards_presets', '*', "pid='{$presetID}'");

    if ($db->num_rows($dbQuery)) {
        return $db->fetch_array($dbQuery);
    }

    return $presetData;
}

function presetDelete(int $presetID): bool
{
    global $db;

    $db->delete_query('ougc_awards_presets', "pid='{$presetID}'");

    return true;
}

function ownerInsert(int $awardID, int $userID): bool
{
    global $db, $plugins;

    $hookArguments = [
        'awardID' => &$awardID,
        'userID' => &$userID
    ];

    $plugins->run_hooks('ougc_awards_insert_owner', $hookArguments);

    $insertData = [
        'aid' => $awardID,
        'uid' => $userID,
        'date' => TIME_NOW
    ];

    $db->insert_query('ougc_awards_owners', $insertData);

    $db->update_query('users', ['ougc_awards_owner' => 1], "uid='{$userID}'");

    return true;
}

function ownerRevoke(int $ownerID): bool
{
    global $db, $plugins;

    $hookArguments = [
        'ownerID' => &$ownerID
    ];

    $plugins->run_hooks('ougc_awards_revoke_owner', $hookArguments);

    $ownerData = ownerGet($ownerID);

    $db->delete_query('ougc_awards_owners', "oid='{$ownerID}'");

    rebuildOwners();

    return true;
}

function rebuildOwners(): bool
{
    global $db;

    $userIDs = [];

    $dbQuery = $db->simple_select('ougc_awards_owners', 'uid');

    while ($userIDs[] = (int)$db->fetch_field($dbQuery, 'uid')) {
    }

    $userIDs = implode("','", array_filter($userIDs));

    $db->update_query('users', ['ougc_awards_owner' => 0], "uid NOT IN ('{$userIDs}')");

    $db->update_query('users', ['ougc_awards_owner' => 1], "uid IN ('{$userIDs}')");

    return true;
}

function ownerGet(int $ownerID = 0): array
{
    global $db;

    $dbQuery = $db->simple_select('ougc_awards_owners', '*', "oid='{$ownerID}'");

    if ($db->num_rows($dbQuery)) {
        return $db->fetch_array($dbQuery);
    }

    return [];
}

function ownerGetUser(
    array $whereClauses = [],
    string $queryFields = '*',
    array $queryOptions = []
): array {
    global $db;

    $usersData = [];

    if (isset($queryOptions['limit'])) {
        $queryOptions['limit'] = (int)$queryOptions['limit'];
    }

    $dbQuery = $db->simple_select('ougc_awards_owners', $queryFields, implode(' AND ', $whereClauses), $queryOptions);

    if ($db->num_rows($dbQuery)) {
        if (isset($queryOptions['limit']) && $queryOptions['limit'] === 1) {
            $usersData = $db->fetch_array($dbQuery);
        } else {
            while ($userData = $db->fetch_array($dbQuery)) {
                $usersData[] = $userData;
            }
        }
    }

    return $usersData;
}

function ownerFind(int $awardID, int $userID): array
{
    global $db;

    $query = $db->simple_select('ougc_awards_owners', '*', "aid='{$awardID}' AND uid='{$userID}'");

    if ($db->num_rows($query)) {
        return $db->fetch_array($query);
    }

    return [];
}

function categoryInsert(array $categoryData, int $categoryID = null, bool $updateCategory = false): int
{
    global $db;

    $insertData = array();

    !isset($categoryData['name']) || $insertData['name'] = $db->escape_string($categoryData['name']);
    !isset($categoryData['description']) || $insertData['description'] = $db->escape_string(
        $categoryData['description']
    );
    !isset($categoryData['disporder']) || $insertData['disporder'] = (int)$categoryData['disporder'];
    !isset($categoryData['allowrequests']) || $insertData['allowrequests'] = (int)$categoryData['allowrequests'];
    !isset($categoryData['visible']) || $insertData['visible'] = (int)$categoryData['visible'];

    if ($updateCategory) {
        return (int)$db->update_query('ougc_awards_categories', $insertData, "cid='{$categoryID}'");
    }

    return (int)$db->insert_query('ougc_awards_categories', $insertData);
}

function categoryUpdate(array $categoryData, int $categoryID): int
{
    return categoryInsert($categoryData, $categoryID, true);
}

function categoryDelete(int $categoryID): bool
{
    global $db;

    $dbQuery = $db->simple_select('ougc_awards', 'aid', "cid='{$categoryID}'");

    while ($awardID = (int)$db->fetch_field($dbQuery, 'aid')) {
        awardDelete($awardID);
    }

    $db->delete_query('ougc_awards_categories', "cid='{$categoryID}'");

    return true;
}

function categoryGet(int $categoryID): array
{
    static $categoryCache = [];

    if (!isset($categoryCache[$categoryID])) {
        global $db;

        $categoryCache[$categoryID] = [];

        $dbQuery = $db->simple_select('ougc_awards_categories', '*', "cid='{$categoryID}'");

        if ($db->num_rows($dbQuery)) {
            $categoryCache[$categoryID] = $db->fetch_array($dbQuery);
        }
    }

    return $categoryCache[$categoryID];
}

function categoryGetCache(): array
{
    global $db;

    $cacheObjects = [];

    $dbQuery = $db->simple_select('ougc_awards_categories');

    if ($db->num_rows($dbQuery)) {
        while ($rowData = $db->fetch_array($dbQuery)) {
            $cacheObjects[(int)$rowData['cid']] = $rowData;
        }
    }

    return $cacheObjects;
}

function awardInsert(array $awardData, int $awardID = 0, bool $updateAward = false): int
{
    global $db;

    $insertData = array();

    !isset($awardData['name']) || $insertData['name'] = $db->escape_string($awardData['name']);
    !isset($awardData['cid']) || $insertData['cid'] = (int)$awardData['cid'];
    !isset($awardData['description']) || $insertData['description'] = $db->escape_string($awardData['description']);
    !isset($awardData['image']) || $insertData['image'] = $db->escape_string($awardData['image']);
    !isset($awardData['template']) || $insertData['template'] = (int)$awardData['template'];
    !isset($awardData['disporder']) || $insertData['disporder'] = (int)$awardData['disporder'];
    !isset($awardData['allowrequests']) || $insertData['allowrequests'] = (int)$awardData['allowrequests'];
    !isset($awardData['pm']) || $insertData['pm'] = $db->escape_string($awardData['pm']);
    !isset($awardData['visible']) || $insertData['visible'] = (int)$awardData['visible'];
    !isset($awardData['type']) || $insertData['type'] = (int)$awardData['type'];

    if ($updateAward) {
        return (int)$db->update_query('ougc_awards', $insertData, "aid='{$awardID}'");
    }

    return (int)$db->insert_query('ougc_awards', $insertData);
}

function awardUpdate(array $awardData, int $awardID = 0): int
{
    return awardInsert($awardData, $awardID, true);
}

function awardDelete(int $awardID): bool
{
    global $db;

    $dbQuery = $db->simple_select('ougc_awards_users', 'gid', "aid='{$awardID}'");

    while ($grantID = (int)$db->fetch_field($dbQuery, 'gid')) {
        grantDelete($grantID);
    }

    $db->delete_query('ougc_awards', "aid='{$awardID}'");

    return true;
}

function awardGet(int $awardID): array
{
    global $db;

    $awardData = [];

    $dbQuery = $db->simple_select('ougc_awards', '*', "aid='{$awardID}'");

    if ($db->num_rows($dbQuery)) {
        $awardData = $db->fetch_array($dbQuery);
    }

    return $awardData;
}

function awardGetIcon(int $awardID): string
{
    global $mybb;

    $awardData = awardGet($awardID);

    $replaceObjects = [
        '{bburl}' => $mybb->settings['bburl'],
        '{homeurl}' => $mybb->settings['homeurl'],
        '{imgdir}' => !empty($theme['imgdir']) ? $theme['imgdir'] : '',
        '{aid}' => $awardID,
        '{cid}' => $awardData['cid']
    ];

    return str_replace(
        array_keys($replaceObjects),
        array_values($replaceObjects),
        $awardData['image']
    );
}

function awardGetInfo(
    int $informationType = INFORMATION_TYPE_TEMPLATE,
    int $awardID,
    int $grantID = 0,
    int $requestID = 0,
    int $taskID = 0
): string {
    global $lang;

    loadLanguage(true);

    $returnString = '';

    switch ($informationType) {
        case INFORMATION_TYPE_TEMPLATE:

            $returnString = 'awardImage';

            $awardData = awardGet($awardID);

            switch ((int)$awardData['template']) {
                case AWARD_TEMPLATE_TYPE_CUSTOM;
                    global $templates;

                    if (isset($templates->cache["ougcawards_award_image_cat{$awardData['cid']}"])) {
                        $returnString = "award_image_cat{$awardData['cid']}";
                    }

                    if (isset($templates->cache["ougcawards_award_image{$awardID}"])) {
                        $returnString = "award_image{$awardID}";
                    }
                    break;
                case AWARD_TEMPLATE_TYPE_CLASS;
                    $returnString = 'awardImageClass';
                    break;
            }

            break;
        case INFORMATION_TYPE_PRIVATE_MESSAGE:
            if (!empty($lang->ougcAwardsPrivateMessagesOverwrite)) {
                $returnString = $lang->ougcAwardsPrivateMessagesOverwrite;
            }

            break;
        case INFORMATION_TYPE_REASON:
            if ($taskID) {
                $taskData = taskGet($taskID);

                if (!empty($taskData)) {
                    $lang_val = "ougcAwardsReasonTask{$taskID}";

                    isset($lang->{$lang_val}) || $lang->{$lang_val} = $taskData['reason'];

                    if (!empty($lang->{$lang_val})) {
                        $returnString = $lang->{$lang_val};
                    }
                }
            }

            $lang_val = "ougcAwardsReasonAward{$awardID}";

            if (!empty($lang->{$lang_val})) {
                $returnString = $lang->{$lang_val};
            }

            break;
        case INFORMATION_TYPE_NAME:
            $lang_val = "ougcAwardsNameAward{$awardID}";

            if (!empty($lang->{$lang_val})) {
                $returnString = $lang->{$lang_val};
            }

            global $cache;

            $awardData = awardGet($awardID);

            if (!empty($awardData['name'])) {
                $returnString = $awardData['name'];
            }

            break;
        case INFORMATION_TYPE_DESCRIPTION:
            $lang_val = "ougcAwardsDescriptionAward{$awardID}";

            if (!empty($lang->{$lang_val})) {
                $returnString = $lang->{$lang_val};
            }

            global $cache;

            $awardData = awardGet($awardID);

            if (!empty($awardData['description'])) {
                $returnString = $awardData['description'];
            }

            break;
    }

    return $returnString;
}

function awardGetUser(
    array $whereClauses = [],
    string $queryFields = '*',
    array $queryOptions = []
): array {
    global $db;

    $usersData = [];

    if (isset($queryOptions['limit'])) {
        $queryOptions['limit'] = (int)$queryOptions['limit'];
    }

    $dbQuery = $db->simple_select('ougc_awards_users', $queryFields, implode(' AND ', $whereClauses), $queryOptions);

    if ($db->num_rows($dbQuery)) {
        if (isset($queryOptions['limit']) && $queryOptions['limit'] === 1) {
            $usersData = $db->fetch_array($dbQuery);
        } else {
            while ($userData = $db->fetch_array($dbQuery)) {
                $usersData[] = $userData;
            }
        }
    }

    return $usersData;
}

function awardsGetCache(array $whereClauses = []): array
{
    global $db;

    $cacheObjects = [];

    $dbQuery = $db->simple_select('ougc_awards', '*', implode(' AND ', $whereClauses));

    if ($db->num_rows($dbQuery)) {
        while ($rowData = $db->fetch_array($dbQuery)) {
            $cacheObjects[(int)$rowData['aid']] = $rowData;
        }
    }

    return $cacheObjects;
}

function grantInsert(
    int $awardID,
    int $userID,
    string $reasonText,
    int $threadID = 0,
    int $taskID = 0,
    int $requestID = 0
): int {
    global $db, $plugins, $mybb;

    $awardData = awardGet($awardID);

    $userData = getUser($userID);

    $hookArguments = [
        'award' => &$awardData,
        'user' => &$userData,
        'reason' => &$reasonText
    ];

    $plugins->run_hooks('ougc_awards_give_award', $hookArguments);

    $insertData = [
        'aid' => $awardID,
        'uid' => $userID,
        'oid' => (int)$mybb->user['uid'],
        'tid' => $taskID,
        'thread' => $threadID,
        'rid' => $requestID,
        'reason' => $db->escape_string($reasonText),
        'date' => TIME_NOW,
        'visible' => (int)getSetting('sort_visible_default')
    ];

    $grantID = $db->insert_query('ougc_awards_users', $insertData);

    if ($privateMessage = awardGetInfo(INFORMATION_TYPE_PRIVATE_MESSAGE, $awardID)) {
        $awardData['pm'] = $privateMessage;
    }

    if ($awardName = awardGetInfo(INFORMATION_TYPE_NAME, $awardID)) {
        $awardData['name'] = $awardName;
    }

    global $lang;

    loadLanguage(true);

    sendPrivateMessage([
        'subject' => $lang->sprintf($lang->ougcAwardsPrivateMessageTitle, strip_tags($awardData['name'])),
        'message' => $lang->sprintf(
            $awardData['pm'],
            $userData['username'],
            $awardData['name'],
            (empty($reasonText) ? $lang->ougcAwardsNoReason : $reasonText),
            awardGetIcon($awardID),
            $mybb->settings['bbname']
        ),
        'touid' => $userID
    ], -1, true);

    sendAlert($awardID, $userID);

    return $grantID;
}

function grantUpdate(int $grantID, array $grantData): bool
{
    global $db, $plugins;

    $updateData = array();

    !isset($grantData['date']) || $updateData['date'] = (int)$grantData['date'];
    !isset($grantData['reason']) || $updateData['reason'] = $db->escape_string($grantData['reason']);
    !isset($grantData['thread']) || $updateData['thread'] = (int)$grantData['thread'];
    !isset($grantData['visible']) || $updateData['visible'] = (int)$grantData['visible'];
    !isset($grantData['disporder']) || $updateData['disporder'] = (int)$grantData['disporder'];

    $hookArguments = [
        'gid' => &$grantID,
        'data' => &$data,
        'clean_data' => &$updateData,
    ];

    $plugins->run_hooks('ougc_awards_update_gived', $hookArguments);

    $db->update_query('ougc_awards_users', $updateData, "gid='{$grantID}'");

    return true;
}

function grantDelete(int $grantID): bool
{
    global $db, $plugins;

    $hookArguments = [
        'grantID' => &$grantID
    ];

    $plugins->run_hooks('ougc_awards_revoke_award', $hookArguments);

    $db->delete_query('ougc_awards_users', "gid='{$grantID}'");

    return true;
}

function grantGet(int $grantID = 0): array
{
    global $db;

    $dbQuery = $db->simple_select('ougc_awards_users', '*', "gid='{$grantID}'");

    if ($db->num_rows($dbQuery)) {
        return $db->fetch_array($dbQuery);
    }

    return [];
}

function grantFind(int $awardID, int $userID): array
{
    global $db;

    $dbQuery = $db->simple_select('ougc_awards_users', '*', "aid='{$awardID}' AND uid='{$userID}'");

    if ($db->num_rows($dbQuery)) {
        return $db->fetch_array($dbQuery);
    }

    return [];
}

function requestInsert(array $requestData, int $requestID = 0, bool $updateRequest = false): int
{
    global $db;

    $insertData = [];

    !isset($requestData['uid']) || $insertData['uid'] = (int)$requestData['uid'];
    !isset($requestData['muid']) || $insertData['muid'] = (int)$requestData['muid'];
    !isset($requestData['aid']) || $insertData['aid'] = (int)$requestData['aid'];
    !isset($requestData['message']) || $insertData['message'] = $db->escape_string($requestData['message']);
    !isset($requestData['status']) || $insertData['status'] = (int)$requestData['status'];

    if ($updateRequest) {
        return (int)$db->update_query('ougc_awards_requests', $insertData, "rid='{$requestID}'");
    }

    return (int)$db->insert_query('ougc_awards_requests', $insertData);
}

function requestUpdate(array $requestData, int $requestID)
{
    requestInsert($requestData, $requestID, true);
}

function requestGet(array $whereClauses = []): array
{
    global $db;

    $requestData = [];

    $dbQuery = $db->simple_select('ougc_awards_requests', '*', implode(' AND ', $whereClauses));

    if ($db->num_rows($dbQuery)) {
        return $db->fetch_array($dbQuery);
    }

    return $requestData;
}

function requestGetPending(array $whereClauses = [], string $queryFields = '*', array $queryOptions = []): array
{
    global $db;

    $requestData = [];

    if (isset($queryOptions['limit'])) {
        $queryOptions['limit'] = (int)$queryOptions['limit'];
    }

    $dbQuery = $db->simple_select('ougc_awards_requests', $queryFields, implode(' AND ', $whereClauses), $queryOptions);

    if ($db->num_rows($dbQuery)) {
        if (isset($queryOptions['limit']) && $queryOptions['limit'] === 1) {
            $requestData = $db->fetch_array($dbQuery);
        } else {
            while ($userData = $db->fetch_array($dbQuery)) {
                $requestData[] = $userData;
            }
        }
    }

    return $requestData;
}

function requestGetPendingTotal(array $whereClauses = []): int
{
    $pendingRequestTotal = requestGetPending(
        $whereClauses,
        'COUNT(rid) as pendingRequestTotal'
    );

    if (!empty($pendingRequestTotal['pendingRequestTotal'])) {
        return (int)$pendingRequestTotal['pendingRequestTotal'];
    }

    return 0;
}

function requestReject(int $requestID)
{
    global $lang, $mybb;

    loadLanguage(true);

    $requestData = requestGet(["rid='{$requestID}'"]);

    $awardData = awardGet($requestData['aid']);

    $userData = getUser($requestData['uid']);

    sendPrivateMessage([
        'subject' => $lang->sprintf(
            $lang->ougcAwardsPrivateMessageRequestRejectedTitle,
            strip_tags($awardData['name'])
        ),
        'message' => $lang->sprintf(
            $lang->ougcAwardsPrivateMessageRequestRejectedBody,
            $userData['username'],
            strip_tags($awardData['name'])
        ),
        'touid' => $userData['uid']
    ], -1, true);

    sendAlert($requestData['aid'], $requestData['uid'], 'reject_request');

    requestUpdate(['status' => 1, 'muid' => $mybb->user['uid']], $requestID);
}

function requestApprove(int $requestID): bool
{
    global $lang, $mybb;

    loadLanguage();

    $requestData = requestGet(["rid='{$requestID}'"]);

    grantInsert((int)$requestData['aid'], (int)$requestData['uid'], '', 0, 0, $requestID);

    requestUpdate(['status' => 0, 'muid' => $mybb->user['uid']], $requestID);

    return true;
}

function taskInsert(array $taskData, int $taskID, bool $updateTask = false): int
{
    global $db;

    $insertData = array();

    foreach (['name', 'description', 'reason'] as $k) {
        !isset($taskData[$k]) || $insertData[$k] = $db->escape_string($taskData[$k]);
    }

    foreach (
        [
            'active',
            'logging',
            'thread',
            'allowmultiple',
            'additionalgroups',
            'disporder',
            'posts',
            'threads',
            'fposts',
            'fpostsforums',
            'fthreads',
            'fthreadsforums',
            'registered',
            'online',
            'reputation',
            'referrals',
            'warnings',
            'newpoints',
            'mydownloads',
            'myarcadechampions',
            'myarcadescores',
            'ougc_customrep_r',
            'ougc_customrep_g',
            'ougc_customrepids_r',
            'ougc_customrepids_g'
        ] as $k
    ) {
        !isset($taskData[$k]) || $insertData[$k] = (int)$taskData[$k];
    }

    foreach (
        [
            'poststype',
            'threadstype',
            'fpoststype',
            'fthreadstype',
            'reputationtype',
            'referralstype',
            'warningstype',
            'newpointstype',
            'mydownloadstype',
            'myarcadechampionstype',
            'myarcadescorestype',
            'ougc_customreptype_r',
            'ougc_customreptype_g'
        ] as $k
    ) {
        in_array($taskData[$k], ['>', '>=', '=', '<=', '<']) || $taskData[$k] = '=';

        !isset($taskData[$k]) || $insertData[$k] = $db->escape_string($taskData[$k]);
    }

    foreach (['registeredtype', 'onlinetype'] as $k) {
        in_array($taskData[$k], ['hours', 'days', 'weeks', 'months', 'years']) || $taskData[$k] = '=';

        !isset($taskData[$k]) || $insertData[$k] = $db->escape_string($taskData[$k]);
    }

    foreach (['usergroups', 'give', 'revoke', 'previousawards', 'profilefields'] as $k) {
        is_array($taskData[$k]) || $taskData[$k] = [$taskData[$k]];

        $taskData[$k] = implode(',', array_filter(array_unique(array_map('intval', $taskData[$k]))));

        !isset($taskData[$k]) || $insertData[$k] = $db->escape_string($taskData[$k]);
    }

    !isset($taskData['requirements']) || $insertData['requirements'] = $db->escape_string(
        implode(',', array_filter(array_unique((array)$taskData['requirements'])))
    );

    if ($updateTask) {
        return (int)$db->update_query('ougc_awards_tasks', $insertData, "tid='{$taskID}'");
    } else {
        return (int)$db->insert_query('ougc_awards_tasks', $insertData);
    }
}

function taskUpdate(array $taskData, int $taskID)
{
    taskInsert($taskData, $taskID, true);
}

function taskDelete(int $taskID): bool
{
    global $db;

    $db->delete_query('ougc_awards_tasks', "tid='{$taskID}'");

    $db->delete_query('ougc_awards_tasks_logs', "tid='{$taskID}'");

    return true;
}

function taskGet(int $taskID): array
{
    global $db;

    $taskData = [];

    $dbQuery = $db->simple_select('ougc_awards_tasks', '*', "tid='{$taskID}'");

    if ($db->num_rows($dbQuery)) {
        return $db->fetch_array($dbQuery);
    }

    return $taskData;
}

function sendPrivateMessage(array $privateMessage, int $fromUserID = 0, bool $adminOverride = false): bool
{
    global $mybb;

    if (!getSetting('sendpm') || !$mybb->settings['enablepms']) {
        return false;
    }

    if (!$privateMessage['subject'] || !$privateMessage['message'] || (!$privateMessage['receivepms'] && !$adminOverride)) {
        return false;
    }

    global $lang, $db, $session;

    if (defined('IN_ADMINCP')) {
        $lang->load('../messages');
    } else {
        $lang->load('messages');
    }

    require_once MYBB_ROOT . 'inc/datahandlers/pm.php';

    $PMDataHandler = new PMDataHandler();

    $privateMessage = [
        'subject' => $privateMessage['subject'],
        'message' => $privateMessage['message'],
        'icon' => -1,
        'fromid' => ($fromUserID === 0 ? (int)$mybb->user['uid'] : ($fromUserID < 0 ? 0 : $fromUserID)),
        'toid' => [$privateMessage['touid']],
        'bccid' => [],
        'do' => '',
        'pmid' => '',
        'saveasdraft' => 0,
        'options' => [
            'signature' => 0,
            'disablesmilies' => 0,
            'savecopy' => 0,
            'readreceipt' => 0
        ]
    ];

    if (isset($mybb->session)) {
        $privateMessage['ipaddress'] = $mybb->session->packedip;
    }

    $PMDataHandler->admin_override = (int)$adminOverride;

    $PMDataHandler->set_data($privateMessage);

    if ($PMDataHandler->validate_pm()) {
        $PMDataHandler->insert_pm();
        return true;
    }

    return false;
}

function sendAlert(int $awardID, int $userID, string $alertTypeKey = 'give_award'): bool
{
    global $lang, $mybb, $alertType, $db;

    loadLanguage(true);

    if (!(getSetting('myalerts') && $mybb->cache->cache['plugins']['active']['myalerts'] && class_exists(
            'MybbStuff_MyAlerts_AlertTypeManager'
        ))) {
        return false;
    }

    $alertType = MybbStuff_MyAlerts_AlertTypeManager::getInstance()->getByCode('ougc_awards');

    if (!$alertType) {
        return false;
    }

    $query = $db->simple_select(
        'alerts',
        'id',
        "object_id='{$awardID}' AND uid='{$userID}' AND unread=1 AND alert_type_id='{$alertType->getId()}'"
    );

    if ($db->fetch_field($query, 'id')) {
        return false;
    }

    if ($alertType !== null && $alertType->getEnabled()) {
        $alert = new MybbStuff_MyAlerts_Entity_Alert($userID, $alertType, $awardID);

        $alert->setExtraDetails([
            'type' => $alertTypeKey
        ]);

        MybbStuff_MyAlerts_AlertManager::getInstance()->addAlert($alert);
    }

    return true;
}

function logAction(): bool
{
    $data = ['fid' => '', 'tid' => ''];

    if (defined('IN_ADMINCP')) {
        $data = [];
    }

    global $awardID, $userID, $grantID, $categoryID, $requestID, $taskID;

    if (!empty($awardID)) {
        $data['aid'] = (int)$awardID;
    }

    if (!empty($userID)) {
        $data['uid'] = (int)$userID;
    }

    if (!empty($grantID)) {
        $data['gid'] = (int)$grantID;
    }

    if (!empty($categoryID)) {
        $data['cid'] = (int)$categoryID;
    }

    if (!empty($requestID)) {
        $data['rid'] = (int)$requestID;
    }

    if (!empty($taskID)) {
        $data['tid'] = (int)$taskID;
    }

    if (defined('IN_ADMINCP')) {
        log_admin_action($data);
    } else {
        log_moderator_action($data);
    }

    return true;
}

function cacheUpdate(): bool
{
    global $db, $mybb;

    $limit = (int)$mybb->settings['statslimit'];

    $_cache = [
        'time' => TIME_NOW,
        'awards' => [],
        'categories' => [],
        'requests' => [],
        'tasks' => [],
        'top' => [],
        'last' => [],
    ];

    $query = $db->simple_select(
        'ougc_awards_categories',
        'cid, name, description, allowrequests',
        "visible='1'",
        ['order_by' => 'disporder']
    );

    while ($category = $db->fetch_array($query)) {
        $_cache['categories'][(int)$category['cid']] = [
            'name' => (string)$category['name'],
            'description' => (string)$category['description'],
            'allowrequests' => (int)$category['allowrequests']
        ];
    }

    if ($cids = array_keys($_cache['categories'])) {
        $wherecids = "cid IN ('" . implode("','", $cids) . "')";
        $query = $db->simple_select(
            'ougc_awards',
            'aid, cid, name, template, description, image, allowrequests, type, disporder, visible',
            "visible='1' AND {$wherecids}",
            ['order_by' => 'disporder']
        );

        while ($award = $db->fetch_array($query)) {
            $_cache['awards'][(int)$award['aid']] = [
                'cid' => (int)$award['cid'],
                'name' => (string)$award['name'],
                'template' => (int)$award['template'],
                'description' => (string)$award['description'],
                'image' => (string)$award['image'],
                'allowrequests' => (int)$award['allowrequests'],
                'type' => (int)$award['type'],
                'disporder' => (int)$award['disporder'],
                'visible' => (int)$award['visible']
            ];
        }
    }

    if ($aids = array_keys($_cache['awards'])) {
        $where = "aid IN ('" . implode("','", $aids) . "')";

        $query = $db->simple_select('ougc_awards_requests', 'COUNT(rid) AS pending', "status='1' AND {$where}");
        $pending = $db->fetch_field($query, 'pending');

        $_cache['requests'] = [
            'pending' => (int)$pending
        ];

        $query = $db->query(
            '
				SELECT u.uid, a.awards
				FROM ' . $db->table_prefix . 'users u
				LEFT JOIN (
					SELECT ua.uid, COUNT(ua.aid) AS awards
					FROM ' . $db->table_prefix . 'ougc_awards_users ua
					LEFT JOIN ' . $db->table_prefix . "ougc_awards aw ON (aw.aid=ua.aid)
					WHERE ua.{$where} AND aw.{$wherecids} 
					GROUP BY ua.uid
				) a ON (u.uid=a.uid)
				WHERE a.awards!=''
				ORDER BY a.awards DESC
				LIMIT 0, {$limit}
			;"
        );
        while ($user = $db->fetch_array($query)) {
            $_cache['top'][(int)$user['uid']] = (int)$user['awards'];
        }

        $query = $db->simple_select(
            'ougc_awards_users',
            'uid, date',
            $where,
            ['order_by' => 'date', 'order_dir' => 'desc', 'limit' => $limit]
        );

        while ($user = $db->fetch_array($query)) {
            $_cache['last'][(int)$user['date']] = (int)$user['uid'];
        }
    }

    $query = $db->simple_select('ougc_awards_tasks', 'tid, name, reason', '', ['order_by' => 'disporder']);

    while ($task = $db->fetch_array($query)) {
        $_cache['tasks'][(int)$task['tid']] = [
            'name' => (string)$task['name'],
            'reason' => (string)$task['reason']
        ];
    }

    $mybb->cache->update('ougc_awards', $_cache);

    return true;
}

function generateSelectAwards(string $inputName, array $selectedIDs = [], array $selectOptions): string
{
    global $db, $mybb;

    $selectCode = "<select name=\"{$inputName}\"";

    !isset($selectOptions['multiple']) || $selectCode .= " multiple=\"multiple\"";

    !isset($selectOptions['id']) || $selectCode .= " id=\"{$selectOptions['id']}\"";

    $selectCode .= '>';

    $dbQuery = $db->simple_select('ougc_awards', '*', '', ['order_by' => 'disporder']);

    while ($awardData = $db->fetch_array($dbQuery)) {
        $selectedElement = '';

        if (in_array($awardData['aid'], $selectedIDs)) {
            $selectedElement = 'selected="selected"';
        }

        $selectCode .= "<option value=\"{$awardData['aid']}\"{$selectedElement}>{$awardData['name']}</option>";
    }

    $selectCode .= '</select>';

    return $selectCode;
}

function generateSelectProfileFields(string $inputName, array $selectedIDs = [], array $selectOptions): string
{
    global $db, $mybb;

    $selectCode = "<select name=\"{$inputName}\"";

    !isset($selectOptions['multiple']) || $selectCode .= " multiple=\"multiple\"";

    !isset($selectOptions['id']) || $selectCode .= " id=\"id\"";

    $selectCode .= '>';

    $dbQuery = $db->simple_select('profilefields', '*', '', ['order_by' => 'disporder']);

    while ($profileFieldData = $db->fetch_array($dbQuery)) {
        $selectedElement = '';
        if (in_array($profileFieldData['fid'], $selectedIDs)) {
            $selectedElement = 'selected="selected"';
        }

        $selectCode .= "<option value=\"{$profileFieldData['fid']}\"{$selectedElement}>{$profileFieldData['name']}</option>";
    }

    $selectCode .= '</select>';

    return $selectCode;
}

function generateSelectGrant(int $awardID, int $userID, int $selectedID): string
{
    global $db, $mybb, $lang;

    $selectCode = "<select name=\"gid\">\n";

    $dbQuery = $db->simple_select('ougc_awards_users', '*', "aid='{$awardID}' AND uid='{$userID}'");

    while ($grantData = $db->fetch_array($dbQuery)) {
        $grantID = (int)$grantData['gid'];

        $requestID = (int)$grantData['rid'];

        $taskID = (int)$grantData['tid'];

        $selectedElement = '';

        if ($grantData['gid'] == $selectedID) {
            $selectedElement = 'selected="selected"';
        }

        $grantDate = my_date('relative', $grantData['date']);

        if (!($grantReason = awardGetInfo(
            INFORMATION_TYPE_REASON,
            $awardID,
            $grantID,
            $requestID,
            $taskID
        ))) {
            if (!($grantReason = $grantData['reason'])) {
                $grantReason = $lang->ougcAwardsNoReason;
            }
        }

        $grantReason = $grantData['reason'] = htmlspecialchars_uni($grantReason);

        $selectCode .= "<option value=\"{$grantData['gid']}\"{$selectedElement}>" . $grantDate . ' (' . htmlspecialchars_uni(
                $grantData['reason']
            ) . ')</option>';
    }

    $selectCode .= '</select>';

    return $selectCode;
}

function generateSelectCategory(int $selectedID): string
{
    global $db, $mybb;

    $selectCode = "<select name=\"cid\">\n";

    $dbQuery = $db->simple_select('ougc_awards_categories', '*', '', ['order_by' => 'disporder']);

    while ($categoryData = $db->fetch_array($dbQuery)) {
        $selectedElement = '';

        if ($categoryData['cid'] == $selectedID) {
            $selectedElement = 'selected="selected"';
        }

        $selectCode .= "<option value=\"{$categoryData['cid']}\"{$selectedElement}>{$categoryData['name']}</option>";
    }

    $selectCode .= '</select>';

    return $selectCode;
}

function generateSelectCustomReputation(string $inputName, int $selectedID = 0): string
{
    global $db, $mybb;

    if (!$db->table_exists('ougc_customrep')) {
        return '';
    }

    $selectCode = "<select name=\"{$inputName}\"";

    !isset($options['multiple']) || $selectCode .= " multiple=\"multiple\"";

    $selectCode .= '>';

    $dbQuery = $db->simple_select('ougc_customrep', '*', '', ['order_by' => 'disporder']);

    while ($reputationData = $db->fetch_array($dbQuery)) {
        $selectedElement = '';

        if ($reputationData['rid'] == $selectedID) {
            $selectedElement = 'selected="selected"';
        }

        $selectCode .= "<option value=\"{$reputationData['rid']}\"{$selectedElement}>{$reputationData['name']}</option>";
    }

    $selectCode .= '</select>';

    return $selectCode;
}

function canManageUsers(int $userID): bool
{
    global $mybb;

    $currentUserID = (int)$mybb->user['uid'];

    if (
        is_super_admin($currentUserID) ||
        !is_super_admin($userID) ||
        $mybb->usergroup['cancp']
    ) {
        return true;
    }

    $userPermissions = user_permissions($userID);

    if (!$userPermissions['cancp']) {
        return true;
    }

    if (!defined('IN_ADMINCP')) {
        if (
            $mybb->usergroup['issupermod'] ||
            !$userPermissions['issupermod'] ||
            $mybb->user['ismoderator'] ||
            !is_moderator(0, '', $userID) ||
            $currentUserID !== $userID
        ) {
            return true;
        }
    }

    return false;
}

function canRequestAwards(int $awardID = 0): bool
{
    global $mybb;

    if (empty($mybb->user['uid'])) {
        return false;
    }

    if (!empty($awardID)) {
        $awardData = awardGet($awardID);

        $categoryID = (int)$awardData['cid'];

        $categoryData = categoryGet($categoryID);

        return !empty($categoryData['allowrequests']) && !empty($awardData['allowrequests']);
    }

    return true;
}

function canViewMainPage(): bool
{
    global $mybb;

    return (bool)is_member(getSetting('pagegroups'));
}

function pluginIsInstalled(): bool
{
    return function_exists('ougc_awards_info');
}

function parsePresets(string &$preset_options, array $presetsCache, int $selectedID): string
{
    global $templates;

    $presetOptions = '';

    if (!empty($presetsCache)) {
        foreach ($presetsCache as $preset) {
            $preset['name'] = htmlspecialchars_uni($preset['name']);

            $selected = '';

            if ($selectedID === (int)$preset['pid']) {
                $selected = ' selected="selected"';
            }

            $presetOptions .= eval($templates->render('ougcawards_usercp_presets_select_option'));
        }
    }

    return $presetOptions;
}

function parseMessage(string &$messageContent): string
{
    global $parser;

    if (!($parser instanceof postParser)) {
        require_once MYBB_ROOT . 'inc/class_parser.php';

        $parser = new postParser();
    }

    return $parser->parse_message(
        $messageContent,
        [
            'allow_html' => 0,
            'allow_smilies' => 1,
            'allow_mycode' => 1,
            'filter_badwords' => 1,
            'shorten_urls' => 1
        ]
    );
}

function parseUserAwards(
    string &$formattedContent,
    array $grantCacheData,
    string $templateName = 'profile_row'
): string {
    $categoriesCache = categoryGetCache();

    global $mybb, $lang, $parser;

    loadLanguage();

    require_once MYBB_ROOT . 'inc/class_parser.php';

    is_object($parser) || $parser = new postParser();

    $alternativeBackground = alt_trow(true);

    foreach ($grantCacheData as $grantData) {
        $awardID = (int)$grantData['aid'];

        $awardData = awardGet($awardID);

        $categoryID = (int)$awardData['cid'];

        $categoryData = $categoriesCache[$categoryID];

        $categoryName = htmlspecialchars_uni($categoryData['name']);

        $categoryDescription = htmlspecialchars_uni($categoryData['description']);

        if (!($awardName = awardGetInfo(INFORMATION_TYPE_NAME, $awardID))) {
            $awardName = $awardData['name'];
        }

        if (!($awardDescription = awardGetInfo(INFORMATION_TYPE_DESCRIPTION, $awardID))) {
            $awardDescription = $awardData['description'];
        }

        $grantID = (int)$grantData['gid'];

        $requestID = (int)$grantData['rid'];

        $taskID = (int)$grantData['tid'];

        if (!($awardName = awardGetInfo(INFORMATION_TYPE_NAME, $awardID))) {
            $awardName = $awardData['name'];
        }

        if (!($awardDescription = awardGetInfo(INFORMATION_TYPE_DESCRIPTION, $awardID))) {
            $awardDescription = $awardData['description'];
        }

        if (!($grantReason = awardGetInfo(
            INFORMATION_TYPE_REASON,
            $awardID,
            $grantID,
            $requestID,
            $taskID
        ))) {
            if (!($grantReason = $grantData['reason'])) {
                $grantReason = $lang->ougcAwardsNoReason;
            }
        }

        $grantReason = $awardData['reason'] = htmlspecialchars_uni($grantReason);

        parseMessage($grantReason);

        $threadLink = '';

        if (!empty($threadsCache[$grantData['thread']])) {
            $threadData = $threadsCache[$grantData['thread']];

            $threadData['threadPrefix'] = $threadData['threadPrefixDisplay'] = '';

            if ($threadData['prefix']) {
                $prefixData = build_prefixes($threadData['prefix']);

                if (!empty($prefixData['prefix'])) {
                    $threadData['threadPrefix'] = $prefixData['prefix'] . '&nbsp;';

                    $threadData['threadPrefixDisplay'] = $prefixData['displaystyle'] . '&nbsp;';
                }
            }

            $threadSubject = htmlspecialchars_uni(
                $parser->parse_badwords($threadData['subject'])
            );

            $threadLink = get_thread_link($threadData['tid']);

            $threadLink = eval(getTemplate("{$templateName}Link"));
        }

        $awardImage = awardGetIcon($awardID);

        $awardImage = eval(getTemplate(awardGetInfo(INFORMATION_TYPE_TEMPLATE, $awardID)));

        $grantDate = $lang->sprintf(
            $lang->ougcAwardsDate,
            my_date($mybb->settings['dateformat'], $grantData['date']),
            my_date($mybb->settings['timeformat'], $grantData['date'])
        );

        $formattedContent .= eval(getTemplate($templateName));

        $alternativeBackground = alt_trow();
    }

    return $formattedContent;
}

// Most of this was taken from @Starpaul20's Move Post plugin (https://github.com/PaulBender/Move-Posts)
function getThreadByUrl(string $threadUrl)
{
    global $db, $mybb;

    // Google SEO URL support
    if ($db->table_exists('google_seo')) {
        $regexp = "{$mybb->settings['bburl']}/{$mybb->settings['google_seo_url_threads']}";

        if ($regexp) {
            $regexp = preg_quote($regexp, '#');
            $regexp = str_replace('\\{\\$url\\}', '([^./]+)', $regexp);
            $regexp = str_replace('\\{url\\}', '([^./]+)', $regexp);
            $regexp = "#^{$regexp}$#u";
        }

        $url = $threadUrl;

        $url = preg_replace('/^([^#?]*)[#?].*$/u', '\\1', $url);

        $url = preg_replace($regexp, '\\1', $url);

        $url = urldecode($url);

        $query = $db->simple_select('google_seo', 'id', "idtype='4' AND url='" . $db->escape_string($url) . "'");
        $threadID = $db->fetch_field($query, 'id');
    }

    $realurl = explode('#', $threadUrl);

    $threadUrl = $realurl[0];

    if (substr($threadUrl, -4) == 'html') {
        preg_match('#thread-([0-9]+)?#i', $threadUrl, $threadmatch);

        preg_match('#post-([0-9]+)?#i', $threadUrl, $postmatch);

        if ($threadmatch[1]) {
            $parameters['tid'] = $threadmatch[1];
        }

        if ($postmatch[1]) {
            $parameters['pid'] = $postmatch[1];
        }
    } else {
        $splitloc = explode('.php', $threadUrl);

        $temp = explode('&', my_substr($splitloc[1], 1));

        if (!empty($temp)) {
            for ($i = 0; $i < count($temp); $i++) {
                $temp2 = explode('=', $temp[$i], 2);

                $parameters[$temp2[0]] = $temp2[1];
            }
        } else {
            $temp2 = explode('=', $splitloc[1], 2);

            $parameters[$temp2[0]] = $temp2[1];
        }
    }

    if ($parameters['pid'] && !$parameters['tid']) {
        $post = get_post($parameters['pid']);

        $threadID = $post['tid'];
    } elseif ($parameters['tid']) {
        $threadID = $parameters['tid'];
    }

    return get_thread($threadID);
}
