<?php

/***************************************************************************
 *
 *    ougc Awards plugin (/inc/plugins/ougc/Awards/core.php)
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

use MyBB;
use MybbStuff_MyAlerts_AlertManager;
use MybbStuff_MyAlerts_AlertTypeManager;
use MybbStuff_MyAlerts_Entity_Alert;
use PluginLibrary;
use pluginSystem;
use postParser;
use stdClass;

use function ougc\Awards\Admin\pluginInfo;
use function ougc\Awards\Hooks\Forum\myalerts_register_client_alert_formatters;

use const ougc\Awards\ROOT;
use const TIME_NOW;

const URL = 'awards.php';

const ADMIN_PERMISSION_DELETE = -1;

const AWARD_TEMPLATE_TYPE_CLASS = 1;

const AWARD_TEMPLATE_TYPE_CUSTOM = 2;

const AWARD_ALLOW_REQUESTS = 1;

const AWARD_STATUS_DISABLED = 0;

const AWARD_STATUS_ENABLED = 1;

const TASK_TYPE_GRANT = 1;

const TASK_TYPE_REVOKE = 2;

const TASK_STATUS_DISABLED = 0;

const TASK_STATUS_ENABLED = 1;

const TASK_ALLOW_MULTIPLE = 1;

const GRANT_STATUS_EVERYWHERE = 0;

const GRANT_STATUS_PROFILE = 1;

const GRANT_STATUS_POSTS = 2;

const GRANT_STATUS_NOT_VISIBLE = 0;

const GRANT_STATUS_VISIBLE = 1;

const REQUEST_STATUS_REJECTED = 2;

const REQUEST_STATUS_ACCEPTED = 0;

const REQUEST_STATUS_PENDING = 1;

const FILE_UPLOAD_ERROR_FAILED = 1;

const FILE_UPLOAD_ERROR_INVALID_TYPE = 2;

const FILE_UPLOAD_ERROR_UPLOAD_SIZE = 3;

const FILE_UPLOAD_ERROR_RESIZE = 4;

const TABLES_DATA = [
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
        'award_file' => [
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
        'reason' => [
            'type' => 'TEXT',
            'null' => true,
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
    'ougc_awards_category_owners' => [
        'ownerID' => [
            'type' => 'INT',
            'unsigned' => true,
            'auto_increment' => true,
            'primary_key' => true
        ],
        'userID' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        'categoryID' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        'ownerDate' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
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
        'active' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 1
        ],
        'taskType' => [
            'type' => 'TINYINT',
            'size' => 1,
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
        'allowmultiple' => [
            'type' => 'SMALLINT',
            'unsigned' => true,
            'default' => 0
        ],
        'revoke' => [
            'type' => 'TEXT',
            'null' => true,
        ],
        'disporder' => [
            'type' => 'SMALLINT',
            'unsigned' => true,
            'default' => 0
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
        'registered' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        'registeredtype' => [
            'type' => 'VARCHAR',
            'size' => 5,
            'default' => ''
        ],
        'online' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        'onlinetype' => [
            'type' => 'VARCHAR',
            'size' => 10,
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
        'previousawards' => [
            'type' => 'TEXT',
            'null' => true,
        ],
        'profilefields' => [
            'type' => 'TEXT',
            'null' => true,
        ],
        /*'mydownloads' => [
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
        ],*/
        'ruleScripts' => [
            'type' => 'TEXT',
            'null' => true,
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
        /*
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
        */
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
        'ougc_awards' => [
            'type' => 'TEXT',
            'null' => true
        ],
        'ougc_awards_category_owner' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 0
        ],
        'ougc_awards_owner' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 0
        ],
        'ougc_awards_preset' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ]
    ]
];

const TASK_REQUIREMENT_TYPE_GROUPS = 'usergroups';

const TASK_REQUIREMENT_TYPE_THREADS = 'threads';

const TASK_REQUIREMENT_TYPE_POSTS = 'posts';

const TASK_REQUIREMENT_TYPE_THREADS_FORUM = 'fthreads';

const TASK_REQUIREMENT_TYPE_POSTS_FORUM = 'fposts';

const TASK_REQUIREMENT_TYPE_REGISTRATION = 'registered';

const TASK_REQUIREMENT_TYPE_ONLINE = 'online';

const TASK_REQUIREMENT_TYPE_REPUTATION = 'reputation';

const TASK_REQUIREMENT_TYPE_REFERRALS = 'referrals';

const TASK_REQUIREMENT_TYPE_WARNINGS = 'warnings';

const TASK_REQUIREMENT_TYPE_AWARDS_GRANTED = 'previousawards';

const TASK_REQUIREMENT_TYPE_FILLED_PROFILE_FIELDS = 'profilefields';

const TASK_REQUIREMENT_TYPE_JSON_SCRIPT = 'ruleScripts';

const TASK_REQUIREMENT_TYPE_NEWPOINTS = 'newpoints';

const TASK_REQUIREMENT_TIME_TYPE_HOURS = 'hours';

const TASK_REQUIREMENT_TIME_TYPE_DAYS = 'days';

const TASK_REQUIREMENT_TIME_TYPE_WEEKS = 'weeks';

const TASK_REQUIREMENT_TIME_TYPE_MONTHS = 'months';

const TASK_REQUIREMENT_TIME_TYPE_YEARS = 'years';

const COMPARISON_TYPE_GREATER_THAN = '>';

const COMPARISON_TYPE_GREATER_THAN_OR_EQUAL = '>=';

const COMPARISON_TYPE_EQUAL = '=';

const COMPARISON_TYPE_NOT_EQUAL = '!=';

const COMPARISON_TYPE_LESS_THAN_OR_EQUAL = '<=';

const COMPARISON_TYPE_LESS_THAN = '<';

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

            $isNegative = substr($hookName, -3, 1) === '_';

            if (is_numeric(substr($hookName, -2))) {
                $hookName = substr($hookName, 0, -2);
            } else {
                $priority = 10;
            }

            if ($isNegative) {
                $plugins->add_hook($hookName, $callable, -$priority);
            } else {
                $plugins->add_hook($hookName, $callable, $priority);
            }
        }
    }
}

function runHooks(string $hookName, array &$hookArguments)
{
    if (getSetting('disablePlugins') !== false) {
        return $hookArguments;
    }

    global $plugins;

    if ($plugins instanceof pluginSystem) {
        $hookArguments = $plugins->run_hooks('ougc_awards_' . $hookName, $hookArguments);
    }

    return $hookArguments;
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

    return SETTINGS[$settingKey] ?? (
        $mybb->settings['ougc_awards_' . $settingKey] ?? false
    );
}

function executeTask(array $awardTaskData = []): bool
{
    global $db;

    loadLanguage(true);

    $tableQueryOptions = [];

    $tableQueryFields = ['u.uid', 'u.username'];

    $requirementCriteria = [
        TASK_REQUIREMENT_TYPE_GROUPS => function (
            array $taskData,
            string $requirementType,
            array &$whereClauses
        ): bool {
            global $db;

            $groupIDs = array_map('intval', explode(',', $taskData[$requirementType]));

            $whereClause = ["usergroup IN ('" . implode("','", $groupIDs) . "')"];

            if (!empty($taskData['additionalgroups'])) {
                foreach ($groupIDs as $groupID) {
                    switch ($db->type) {
                        case 'pgsql':
                        case 'sqlite':
                            $whereClause[] = "','||u.additionalgroups||',' LIKE '%,{$groupID},%'";
                            break;
                        default:
                            $whereClause[] = "CONCAT(',',u.additionalgroups,',') LIKE '%,{$groupID},%'";
                            break;
                    }
                }
            }

            $whereClauses[] = '(' . implode(' OR ', $whereClause) . ')';

            return false;
        },
        TASK_REQUIREMENT_TYPE_THREADS => function (
            array $taskData,
            string $requirementType,
            array &$whereClauses
        ): bool {
            if (in_array($taskData[$requirementType . 'type'], [
                COMPARISON_TYPE_GREATER_THAN,
                COMPARISON_TYPE_GREATER_THAN_OR_EQUAL,
                COMPARISON_TYPE_EQUAL,
                COMPARISON_TYPE_NOT_EQUAL,
                COMPARISON_TYPE_LESS_THAN_OR_EQUAL,
                COMPARISON_TYPE_LESS_THAN,
            ])) {
                $userThreads = (int)$taskData[$requirementType];

                $whereClauses[] = "u.threadnum{$taskData[$requirementType.'type']}'{$userThreads}'";
            }

            return true;
        },
        TASK_REQUIREMENT_TYPE_POSTS => function (
            array $taskData,
            string $requirementType,
            array &$whereClauses
        ): bool {
            if (in_array($taskData[$requirementType . 'type'], [
                COMPARISON_TYPE_GREATER_THAN,
                COMPARISON_TYPE_GREATER_THAN_OR_EQUAL,
                COMPARISON_TYPE_EQUAL,
                COMPARISON_TYPE_NOT_EQUAL,
                COMPARISON_TYPE_LESS_THAN_OR_EQUAL,
                COMPARISON_TYPE_LESS_THAN,
            ])) {
                $userThreads = (int)$taskData[$requirementType];

                $whereClauses[] = "u.postnum{$taskData[$requirementType.'type']}'{$userThreads}'";
            }

            return true;
        },
        TASK_REQUIREMENT_TYPE_THREADS_FORUM => function (
            array $taskData,
            string $requirementType,
            array &$whereClauses,
            array &$tableLeftJoins
        ): bool {
            if (in_array($taskData[$requirementType . 'type'], [
                COMPARISON_TYPE_GREATER_THAN,
                COMPARISON_TYPE_GREATER_THAN_OR_EQUAL,
                COMPARISON_TYPE_EQUAL,
                COMPARISON_TYPE_NOT_EQUAL,
                COMPARISON_TYPE_LESS_THAN_OR_EQUAL,
                COMPARISON_TYPE_LESS_THAN,
            ])) {
                $forumThreads = (int)$taskData[$requirementType];

                $forumIDs = implode(
                    "','",
                    array_map('intval', explode(',', $taskData[$requirementType . 'forums']))
                );

                global $db;

                $tableLeftJoins[] = "(
				SELECT uid, COUNT(tid) AS {$requirementType}
				FROM {$db->table_prefix}threads
				WHERE fid IN ('{$forumIDs}') AND visible > 0 AND closed NOT LIKE 'moved|%'
				GROUP BY uid
			) t ON (t.uid=u.uid)";

                $whereClauses[] = "u.threadnum{$taskData[$requirementType.'type']}'{$forumThreads}'";
            }

            return true;
        },
        TASK_REQUIREMENT_TYPE_POSTS_FORUM => function (
            array $taskData,
            string $requirementType,
            array &$whereClauses,
            array &$tableLeftJoins
        ): bool {
            if (in_array($taskData[$requirementType . 'type'], [
                COMPARISON_TYPE_GREATER_THAN,
                COMPARISON_TYPE_GREATER_THAN_OR_EQUAL,
                COMPARISON_TYPE_EQUAL,
                COMPARISON_TYPE_NOT_EQUAL,
                COMPARISON_TYPE_LESS_THAN_OR_EQUAL,
                COMPARISON_TYPE_LESS_THAN,
            ])) {
                $forumPosts = (int)$taskData[$requirementType];

                $forumIDs = implode(
                    "','",
                    array_map('intval', explode(',', $taskData[$requirementType . 'forums']))
                );

                global $db;

                $tableLeftJoins[] = "(
				SELECT p.uid, COUNT(p.pid) AS {$requirementType}
				FROM {$db->table_prefix}posts p
				LEFT JOIN {$db->table_prefix}threads t ON (t.tid=p.tid)
				WHERE p.fid IN ('{$forumIDs}') AND t.visible > 0 AND p.visible > 0
				GROUP BY p.uid
			) p ON (p.uid=u.uid)";

                $whereClauses[] = "p.{$requirementType}{$taskData[$requirementType.'type']}'{$forumPosts}'";
            }

            return true;
        },
        TASK_REQUIREMENT_TYPE_REGISTRATION => function (
            array $taskData,
            string $requirementType,
            array &$whereClauses
        ): bool {
            $registeredSeconds = (int)$taskData[$requirementType];

            switch ($taskData[$requirementType . 'type']) {
                case TASK_REQUIREMENT_TIME_TYPE_HOURS:
                    $registeredSeconds *= 60 * 60;
                    break;
                case TASK_REQUIREMENT_TIME_TYPE_DAYS:
                    $registeredSeconds *= 60 * 60 * 24;
                    break;
                case TASK_REQUIREMENT_TIME_TYPE_WEEKS:
                    $registeredSeconds *= 60 * 60 * 24 * 7;
                    break;
                case TASK_REQUIREMENT_TIME_TYPE_MONTHS:
                    $registeredSeconds *= 60 * 60 * 24 * 30;
                    break;
                case TASK_REQUIREMENT_TIME_TYPE_YEARS:
                    $registeredSeconds *= 60 * 60 * 24 * 365;
                    break;
            }

            $registeredSeconds = TIME_NOW - $registeredSeconds;

            if ($registeredSeconds > 0) {
                $whereClauses[] = "u.regdate<='{$registeredSeconds}'";
            }

            return true;
        },
        TASK_REQUIREMENT_TYPE_ONLINE => function (
            array $taskData,
            string $requirementType,
            array &$whereClauses
        ): bool {
            $onlineSeconds = (int)$taskData[$requirementType];

            switch ($taskData[$requirementType . 'type']) {
                case TASK_REQUIREMENT_TIME_TYPE_HOURS:
                    $onlineSeconds *= 60 * 60;
                    break;
                case TASK_REQUIREMENT_TIME_TYPE_DAYS:
                    $onlineSeconds *= 60 * 60 * 24;
                    break;
                case TASK_REQUIREMENT_TIME_TYPE_WEEKS:
                    $onlineSeconds *= 60 * 60 * 24 * 7;
                    break;
                case TASK_REQUIREMENT_TIME_TYPE_MONTHS:
                    $onlineSeconds *= 60 * 60 * 24 * 30;
                    break;
                case TASK_REQUIREMENT_TIME_TYPE_YEARS:
                    $onlineSeconds *= 60 * 60 * 24 * 365;
                    break;
            }

            if ($onlineSeconds > 0) {
                $whereClauses[] = "u.timeonline>='{$onlineSeconds}'";
            }

            return true;
        },
        TASK_REQUIREMENT_TYPE_REPUTATION => function (
            array $taskData,
            string $requirementType,
            array &$whereClauses
        ): bool {
            if (in_array($taskData[$requirementType . 'type'], [
                COMPARISON_TYPE_GREATER_THAN,
                COMPARISON_TYPE_GREATER_THAN_OR_EQUAL,
                COMPARISON_TYPE_EQUAL,
                COMPARISON_TYPE_NOT_EQUAL,
                COMPARISON_TYPE_LESS_THAN_OR_EQUAL,
                COMPARISON_TYPE_LESS_THAN,
            ])) {
                $userReputation = (int)$taskData[$requirementType];

                $whereClauses[] = "u.{$requirementType}{$taskData[$requirementType.'type']}'{$userReputation}'";

                return true;
            }

            return false;
        },
        TASK_REQUIREMENT_TYPE_REFERRALS => function (
            array $taskData,
            string $requirementType,
            array &$whereClauses
        ): bool {
            if (in_array($taskData[$requirementType . 'type'], [
                COMPARISON_TYPE_GREATER_THAN,
                COMPARISON_TYPE_GREATER_THAN_OR_EQUAL,
                COMPARISON_TYPE_EQUAL,
                COMPARISON_TYPE_NOT_EQUAL,
                COMPARISON_TYPE_LESS_THAN_OR_EQUAL,
                COMPARISON_TYPE_LESS_THAN,
            ])) {
                $userReferrals = (int)$taskData[$requirementType];

                $whereClauses[] = "u.{$requirementType}{$taskData[$requirementType.'type']}'{$userReferrals}'";
            }

            return true;
        },
        TASK_REQUIREMENT_TYPE_WARNINGS => function (
            array $taskData,
            string $requirementType,
            array &$whereClauses
        ): bool {
            if (in_array($taskData[$requirementType . 'type'], [
                COMPARISON_TYPE_GREATER_THAN,
                COMPARISON_TYPE_GREATER_THAN_OR_EQUAL,
                COMPARISON_TYPE_EQUAL,
                COMPARISON_TYPE_NOT_EQUAL,
                COMPARISON_TYPE_LESS_THAN_OR_EQUAL,
                COMPARISON_TYPE_LESS_THAN,
            ])) {
                $userWarningPoints = (int)$taskData[$requirementType];

                $whereClauses[] = "u.warningpoints{$taskData[$requirementType.'type']}'{$userWarningPoints}'";
            }

            return true;
        },
        TASK_REQUIREMENT_TYPE_AWARDS_GRANTED => function (
            array $taskData,
            string $requirementType,
            array &$whereClauses,
            array &$tableLeftJoins
        ): bool {
            if (!empty($taskData[$requirementType])) {
                global $db;

                $awardIDs = implode("','", array_keys(awardsCacheGet()['awards']));

                foreach (array_map('intval', explode(',', $taskData[$requirementType])) as $previousAwardID) {
                    $tableLeftJoins[] = "(
                            SELECT g.uid, g.aid, COUNT(g.gid) AS {$requirementType}{$previousAwardID}
                            FROM {$db->table_prefix}ougc_awards_users g
                            WHERE g.aid='{$previousAwardID}' AND g.aid IN ('{$awardIDs}')
                            GROUP BY g.uid, g.aid
                        ) aw{$previousAwardID} ON (aw{$previousAwardID}.uid=u.uid)";

                    $whereClauses[] = "aw{$previousAwardID}.{$requirementType}{$previousAwardID}>='1'";
                }
            }

            return true;
        },
        TASK_REQUIREMENT_TYPE_FILLED_PROFILE_FIELDS => function (
            array $taskData,
            string $requirementType,
            array &$whereClauses,
            array &$tableLeftJoins
        ): bool {
            if (!empty($taskData[$requirementType])) {
                global $db;

                $tableLeftJoins[] = "{$db->table_prefix}userfields uf ON (uf.ufid=u.uid)";

                foreach (array_map('intval', explode(',', $taskData[$requirementType])) as $fieldID) {
                    $whereClauses[] = "uf.fid{$fieldID}!=''";
                }
            }

            return true;
        },
        /*
        TASK_REQUIREMENT_TYPE_NEWPOINTS => function (array $taskData, string $requirementType) use (
            &$tableLeftJoins,
            &$whereClauses
        ): bool {
            $userPoints = (float)$taskData[$requirementType];

            if ($userPoints >= 0 && !empty($taskData[$requirementType . 'type'])) {
                $whereClauses[] = "u.{$requirementType}{$taskData[$requirementType.'type']}'{$userPoints}'";
            }


            return true;
        },
        */
    ];

    $hookArguments = [
        'taskData' => &$awardTaskData,
        'tableQueryOptions' => &$tableQueryOptions,
        'tableQueryFields' => &$tableQueryFields,
        'requirementTypes' => &$requirementCriteria
    ];

    // TODO mydownloads
    // TODO myarcadechampions
    // TODO myarcadescores
    // TODO ougc_customrep_r
    // TODO ougc_customrep_g

    $queryTasks = $db->simple_select(
        'ougc_awards_tasks',
        '*',
        "active='1'"
    );

    while ($awardTaskData = $db->fetch_array($queryTasks)) {
        $taskID = (int)$awardTaskData['tid'];

        $taskType = (int)$awardTaskData['taskType'];

        $hookArguments['awardTaskData'] = &$awardTaskData;

        $whereClauses = [];

        $hookArguments['whereClauses'] = &$whereClauses;

        $tableLeftJoins = ['users u'];

        $taskGrantAwardID = (int)$awardTaskData['give'];

        $taskRevokeAwardID = (int)$awardTaskData['revoke'];

        if ($taskType === \ougc\Awards\Core\TASK_TYPE_GRANT && $taskGrantAwardID) {
            if (empty($awardTaskData['allowmultiple'])) {
                $tableQueryFields[] = 'a.totalUserGrants';

                $tableLeftJoins[] = "(
					SELECT uid, COUNT(aid) AS totalUserGrants
					FROM {$db->table_prefix}ougc_awards_users
					WHERE aid IN ('{$taskGrantAwardID}')
					GROUP BY uid
				) a ON (u.uid=a.uid)";

                $whereClauses[] = "a.totalUserGrants>'1'";
            }

            $taskRevokeAwardID = 0;
        } elseif ($taskType === \ougc\Awards\Core\TASK_TYPE_REVOKE && $taskRevokeAwardID) {
            $tableQueryFields[] = 'a.totalUserGrants';

            // if user has no awards from this task, skip
            $tableLeftJoins[] = "(
					SELECT uid, COUNT(aid) AS totalUserGrants
					FROM {$db->table_prefix}ougc_awards_users
					WHERE aid='{$taskRevokeAwardID}'
					GROUP BY uid
				) a ON (u.uid=a.uid)";

            $taskGrantAwardID = 0;
        } else {
            continue;
        }

        // if log exists for user, skip
        $tableQueryFields[] = 'l.totalUserLogs';

        $tableLeftJoins[] = "(
					SELECT uid, COUNT(lid) AS totalUserLogs
					FROM {$db->table_prefix}ougc_awards_tasks_logs
					WHERE tid='{$taskID}'
					GROUP BY uid
				) l ON (u.uid=l.uid)";

        $whereClauses[] = "l.totalUserLogs<'1' OR l.totalUserLogs IS NULL";

        $hookArguments['tableLeftJoins'] = &$tableLeftJoins;

        $hookArguments = runHooks('task_start', $hookArguments);

        $taskThreadID = (int)$awardTaskData['thread'];

        foreach ($requirementCriteria as $requirementType => $callback) {
            if (in_array($requirementType, explode(',', $awardTaskData['requirements']))) {
                $callback($awardTaskData, $requirementType, $whereClauses, $tableLeftJoins);
            }
        }

        $taskLogObjects = [];

        $hookArguments = runHooks('task_intermediate', $hookArguments);

        // todo: $whereClauses to ignore users who already received this task

        $queryUsers = $db->simple_select(
            implode(' LEFT JOIN ', $tableLeftJoins),
            implode(',', $tableQueryFields),
            implode(' AND ', $whereClauses)
        );

        while ($userData = $db->fetch_array($queryUsers)) {
            $userID = (int)$userData['uid'];

            $logTaskGrant = false;

            $userGrantedAwardIDs = $userRevokeAwardIDs = $grandIDs = $revokeAwardIDs = [];

            if ($taskType === \ougc\Awards\Core\TASK_TYPE_GRANT) {
                if (grantInsert(
                    $taskGrantAwardID,
                    $userID,
                    '',
                    $taskThreadID,
                    $taskID
                )) {
                    $grandIDs[] = $taskGrantAwardID;

                    $logTaskGrant = true;
                }
            } else {
                $queryGrants = $db->simple_select(
                    'ougc_awards_users',
                    'gid',
                    "uid='{$userID}' AND aid='{$taskRevokeAwardID}'"
                );

                while ($grandData = $db->fetch_array($queryGrants)) {
                    grantDelete((int)$grandData['gid']);

                    $logTaskGrant = true;
                }
            }

            if ($logTaskGrant) {
                $taskLogObjects[] = [
                    'tid' => $taskID,
                    'uid' => $userID,
                    'gave' => $taskGrantAwardID,
                    'revoked' => $taskRevokeAwardID,
                    'date' => TIME_NOW
                ];
            }
        }

        $hookArguments = runHooks('task_end', $hookArguments);

        if (count($taskLogObjects) > 0) {
            $db->insert_query_multiple('ougc_awards_tasks_logs', $taskLogObjects);
        }
    }

    cacheUpdate();

    return true;
}

function allowImports(): bool
{
    return getSetting('allowImports') && pluginIsInstalled();
}

function getUser(int $userID): array
{
    global $db;

    $dbQuery = $db->simple_select('users', '*', "uid='{$userID}'");

    if ($db->num_rows($dbQuery)) {
        return (array)$db->fetch_array($dbQuery);
    }

    return [];
}

function getUserByUserName(string $userName): array
{
    global $db;

    $dbQuery = $db->simple_select(
        'users',
        'uid, username',
        "LOWER(username)='{$db->escape_string(my_strtolower($userName))}'",
        ['limit' => 1]
    );

    if ($db->num_rows($dbQuery)) {
        return (array)$db->fetch_array($dbQuery);
    }

    return [];
}

function presetInsert(array $insertData, int $presetID = 0, bool $updatePreset = false): int
{
    global $db;

    if ($updatePreset) {
        return (int)$db->update_query('ougc_awards_presets', $insertData, "pid='{$presetID}'");
    }

    return (int)$db->insert_query('ougc_awards_presets', $insertData);
}

function presetUpdate(array $updateData, int $presetID): int
{
    return presetInsert($updateData, $presetID, true);
}

function presetGet(array $whereClauses = [], string $queryFields = '*', array $queryOptions = []): array
{
    global $db;

    $cacheObjects = [];

    $dbQuery = $db->simple_select('ougc_awards_presets', $queryFields, implode(' AND ', $whereClauses), $queryOptions);

    if ($db->num_rows($dbQuery)) {
        if (isset($queryOptions['limit']) && $queryOptions['limit'] === 1) {
            $cacheObjects = $db->fetch_array($dbQuery);
        } else {
            while ($userData = $db->fetch_array($dbQuery)) {
                $cacheObjects[] = $userData;
            }
        }
    }

    return $cacheObjects;
}

function presetDelete(int $presetID): bool
{
    global $db;

    $db->delete_query('ougc_awards_presets', "pid='{$presetID}'");

    return true;
}

function ownerInsert(int $awardID, int $userID): bool
{
    global $db;

    $hookArguments = [
        'awardID' => &$awardID,
        'userID' => &$userID
    ];

    $hookArguments = runHooks('assign_award_owner', $hookArguments);

    $insertData = [
        'aid' => $awardID,
        'uid' => $userID,
        'date' => TIME_NOW
    ];

    $db->insert_query('ougc_awards_owners', $insertData);

    $db->update_query('users', ['ougc_awards_owner' => 1], "uid='{$userID}'");

    return true;
}

function ownerDelete(int $ownerID): bool
{
    global $db;

    $hookArguments = [
        'ownerID' => &$ownerID
    ];

    $hookArguments = runHooks('revoke_award_owner', $hookArguments);

    $db->delete_query('ougc_awards_owners', "oid='{$ownerID}'");

    rebuildOwners();

    return true;
}

function rebuildOwners(): bool
{
    global $db;

    $userIDs = [];

    $dbQuery = $db->simple_select('ougc_awards_owners', 'uid');

    while ($userID = $db->fetch_field($dbQuery, 'uid')) {
        $userIDs[] = (int)$userID;
    }

    $userIDs = implode("','", array_filter($userIDs));

    $db->update_query('users', ['ougc_awards_owner' => 0], "uid NOT IN ('{$userIDs}')");

    $db->update_query('users', ['ougc_awards_owner' => 1], "uid IN ('{$userIDs}')");

    return true;
}

function ownerGetSingle(array $whereClauses = [], string $queryFields = '*'): array
{
    global $db;

    $dbQuery = $db->simple_select('ougc_awards_owners', $queryFields, implode(' AND ', $whereClauses));

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
            return $db->fetch_array($dbQuery);
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

function ownerCategoryInsert(int $categoryID, int $userID): bool
{
    global $db;

    $hookArguments = [
        'categoryID' => &$categoryID,
        'userID' => &$userID
    ];

    $hookArguments = runHooks('assign_category_owner', $hookArguments);

    $insertData = [
        'categoryID' => $categoryID,
        'userID' => $userID,
        'ownerDate' => TIME_NOW
    ];

    $db->insert_query('ougc_awards_category_owners', $insertData);

    $db->update_query('users', ['ougc_awards_category_owner' => 1], "uid='{$userID}'");

    return true;
}

function ownerCategoryDelete(int $ownerID): bool
{
    global $db;

    $hookArguments = [
        'ownerID' => &$ownerID
    ];

    $hookArguments = runHooks('revoke_category_owner', $hookArguments);

    $db->delete_query('ougc_awards_category_owners', "ownerID='{$ownerID}'");

    rebuildOwnersCategories();

    return true;
}

function rebuildOwnersCategories(): bool
{
    global $db;

    $userIDs = [];

    $dbQuery = $db->simple_select('ougc_awards_category_owners', 'uid');

    while ($userID = $db->fetch_field($dbQuery, 'uid')) {
        $userIDs[] = (int)$userID;
    }

    $userIDs = implode("','", array_filter($userIDs));

    $db->update_query('users', ['ougc_awards_category_owner' => 0], "uid NOT IN ('{$userIDs}')");

    $db->update_query('users', ['ougc_awards_category_owner' => 1], "uid IN ('{$userIDs}')");

    return true;
}

function ownerCategoryGetSingle(array $whereClauses = [], string $queryFields = '*'): array
{
    global $db;

    $dbQuery = $db->simple_select('ougc_awards_category_owners', $queryFields, implode(' AND ', $whereClauses));

    if ($db->num_rows($dbQuery)) {
        return $db->fetch_array($dbQuery);
    }

    return [];
}

function ownerCategoryGetUser(
    array $whereClauses = [],
    string $queryFields = '*',
    array $queryOptions = []
): array {
    global $db;

    $usersData = [];

    if (isset($queryOptions['limit'])) {
        $queryOptions['limit'] = (int)$queryOptions['limit'];
    }

    $dbQuery = $db->simple_select(
        'ougc_awards_category_owners',
        $queryFields,
        implode(' AND ', $whereClauses),
        $queryOptions
    );

    if ($db->num_rows($dbQuery)) {
        if (isset($queryOptions['limit']) && $queryOptions['limit'] === 1) {
            return $db->fetch_array($dbQuery);
        } else {
            while ($userData = $db->fetch_array($dbQuery)) {
                $usersData[] = $userData;
            }
        }
    }

    return $usersData;
}

function ownerCategoryFind(int $categoryID, int $userID): array
{
    global $db;

    $query = $db->simple_select(
        'ougc_awards_category_owners',
        '*',
        "categoryID='{$categoryID}' AND userID='{$userID}'"
    );

    if ($db->num_rows($query)) {
        return $db->fetch_array($query);
    }

    return [];
}

function categoryInsert(array $insertData, int $categoryID = null, bool $updateCategory = false): int
{
    global $db;

    if ($updateCategory) {
        return (int)$db->update_query('ougc_awards_categories', $insertData, "cid='{$categoryID}'");
    }

    return (int)$db->insert_query('ougc_awards_categories', $insertData);
}

function categoryUpdate(array $updateData, int $categoryID): int
{
    return categoryInsert($updateData, $categoryID, true);
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

function categoryGetCache(array $whereClauses = [], string $queryFields = '*', array $queryOptions = []): array
{
    global $db;

    $cacheObjects = [];

    if (isset($queryOptions['limit'])) {
        $queryOptions['limit'] = (int)$queryOptions['limit'];
    }

    $dbQuery = $db->simple_select(
        'ougc_awards_categories',
        $queryFields,
        implode(' AND ', $whereClauses),
        $queryOptions
    );

    if ($db->num_rows($dbQuery)) {
        if (isset($queryOptions['limit']) && $queryOptions['limit'] === 1) {
            $cacheObjects = $db->fetch_array($dbQuery);
        } else {
            while ($userData = $db->fetch_array($dbQuery)) {
                if (isset($userData['cid'])) {
                    $cacheObjects[(int)$userData['cid']] = $userData;
                } else {
                    $cacheObjects[] = $userData;
                }
            }
        }
    }

    return $cacheObjects;
}

function awardInsert(array $insertData, int $awardID = 0, bool $updateAward = false): int
{
    global $db;

    if ($updateAward) {
        return (int)$db->update_query('ougc_awards', $insertData, "aid='{$awardID}'");
    }

    return (int)$db->insert_query('ougc_awards', $insertData);
}

function awardUpdate(array $updateData, int $awardID = 0): int
{
    return awardInsert($updateData, $awardID, true);
}

function awardDelete(int $awardID): bool
{
    require_once MYBB_ROOT . 'inc/functions_upload.php';

    global $db;

    $dbQuery = $db->simple_select('ougc_awards_users', 'gid', "aid='{$awardID}'");

    while ($grantID = (int)$db->fetch_field($dbQuery, 'gid')) {
        grantDelete($grantID);
    }

    $dbQuery = $db->simple_select('ougc_awards_owners', 'oid', "aid='{$awardID}'");

    while ($ownerID = (int)$db->fetch_field($dbQuery, 'oid')) {
        ownerDelete($ownerID);
    }

    $db->delete_query('ougc_awards', "aid='{$awardID}'");

    $dir = opendir(getSetting('uploadPath'));

    if ($dir) {
        while ($file = readdir($dir)) {
            if (preg_match('#award_' . $awardID . '\.#', $file) && is_file(
                    getSetting('uploadPath') . '/' . $file
                )) {
                delete_uploaded_file(getSetting('uploadPath') . '/' . $file);
            }
        }

        closedir($dir);
    }

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

    if (empty($awardData['image'])) {
        $awardData['image'] = $mybb->get_asset_url(getSetting('uploadPath') . $awardData['award_file']);
    }

    return str_replace(
        array_keys($replaceObjects),
        array_values($replaceObjects),
        $awardData['image']
    );
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

function awardsGetCache(array $whereClauses = [], string $queryFields = '*', array $queryOptions = []): array
{
    global $db;

    $cacheObjects = [];

    $dbQuery = $db->simple_select('ougc_awards', $queryFields, implode(' AND ', $whereClauses), $queryOptions);

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
    global $db, $mybb;

    $awardData = awardGet($awardID);

    $userData = getUser($userID);

    $hookArguments = [
        'awardData' => &$awardData,
        'userData' => &$userData,
        'reasonText' => &$reasonText
    ];

    $hookArguments = runHooks('grant_award', $hookArguments);

    $insertData = [
        'aid' => $awardID,
        'uid' => $userID,
        'oid' => (int)$mybb->user['uid'],
        'tid' => $taskID,
        'thread' => $threadID,
        'rid' => $requestID,
        'reason' => $db->escape_string($reasonText),
        'date' => TIME_NOW,
        'visible' => (int)getSetting('grantDefaultVisibleStatus')
    ];

    $grantID = $db->insert_query('ougc_awards_users', $insertData);

    global $lang;

    loadLanguage();

    sendPrivateMessage([
        'subject' => $lang->sprintf(
            $lang->ougcAwardsPrivateMessageTitle,
            strip_tags($awardData['name'])
        ),
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

function grantUpdate(array $updateData, int $grantID): bool
{
    global $db;

    $hookArguments = [
        'grantID' => &$grantID,
        'updateData' => &$updateData,
    ];

    $hookArguments = runHooks('update_gived', $hookArguments);

    $db->update_query('ougc_awards_users', $updateData, "gid='{$grantID}'");

    return true;
}

function grantDelete(int $grantID): bool
{
    global $db;

    $hookArguments = [
        'grantID' => &$grantID
    ];

    $hookArguments = runHooks('revoke_award', $hookArguments);

    $db->delete_query('ougc_awards_users', "gid='{$grantID}'");

    return true;
}

function grantGetSingle(array $whereClauses = [], string $queryFields = '*'): array
{
    global $db;

    $dbQuery = $db->simple_select('ougc_awards_users', $queryFields, implode(' AND ', $whereClauses));

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

    if ($updateRequest) {
        return (int)$db->update_query('ougc_awards_requests', $requestData, "rid='{$requestID}'");
    }

    return (int)$db->insert_query('ougc_awards_requests', $requestData);
}

function requestUpdate(array $updateData, int $requestID): int
{
    return requestInsert($updateData, $requestID, true);
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

function requestReject(int $requestID): bool
{
    global $lang, $mybb;

    loadLanguage();

    $requestData = requestGet(["rid='{$requestID}'"]);

    $awardID = (int)($requestData['aid'] ?? 0);

    $userID = (int)($requestData['uid'] ?? 0);

    $awardData = awardGet($awardID);

    $userData = getUser($userID);

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

    sendAlert($awardID, $userID, 'reject_request');

    requestUpdate(['status' => REQUEST_STATUS_REJECTED, 'muid' => $mybb->user['uid']], $requestID);

    return true;
}

function requestApprove(int $requestID): bool
{
    global $mybb;

    $requestData = requestGet(["rid='{$requestID}'"]);

    grantInsert(
        (int)$requestData['aid'],
        (int)$requestData['uid'],
        '',
        0,
        0,
        $requestID
    );

    requestUpdate([
        'status' => REQUEST_STATUS_ACCEPTED,
        'muid' => $mybb->user['uid']
    ], $requestID);

    return true;
}

function taskInsert(array $taskData, int $taskID = 0, bool $updateTask = false): int
{
    global $db;

    $insertData = [];

    foreach (
        [
            'name',
            'description',
            'reason',
            'threadstype',
            'poststype',
            'fthreadstype',
            'fpoststype',
            'registeredtype',
            'onlinetype',
            'reputationtype',
            'referralstype',
            'warningstype',
            //'newpointstype',
            //'mydownloadstype',
            //'myarcadechampionstype',
            //'myarcadescorestype',
            //'ougc_customreptype_r',
            //'ougc_customrepids_r',
            //'ougc_customreptype_g',
            //'ougc_customrepids_g',
            'ruleScripts',
        ] as $k
    ) {
        if (isset($taskData[$k])) {
            $insertData[$k] = $db->escape_string($taskData[$k]);
        }
    }

    foreach (
        [
            //'newpoints',
        ] as $k
    ) {
        if (isset($taskData[$k])) {
            $insertData[$k] = (float)$taskData[$k];
        }
    }

    foreach (
        [
            'tid',
            'active',
            'taskType',
            'logging',
            'give',
            'revoke',
            'thread',
            'allowmultiple',
            'disporder',
            'additionalgroups',
            'threads',
            'posts',
            'fthreads',
            'fposts',
            'registered',
            'online',
            'reputation',
            'referrals',
            'warnings',
            //'mydownloads',
            //'myarcadechampions',
            //'myarcadescores',
            //'ougc_customrep_r',
            //'ougc_customrep_g',
        ] as $k
    ) {
        if (isset($taskData[$k])) {
            $insertData[$k] = (int)$taskData[$k];
        }
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
            //'newpointstype',
            //'mydownloadstype',
            //'myarcadechampionstype',
            //'myarcadescorestype',
            //'ougc_customreptype_r',
            //'ougc_customreptype_g'
        ] as $k
    ) {
        if (isset($taskData[$k]) && in_array($taskData[$k], ['>', '>=', '=', '<=', '<'])) {
            $insertData[$k] = $db->escape_string($taskData[$k]);
        }
    }

    foreach (['registeredtype', 'onlinetype'] as $k) {
        if (isset($taskData[$k]) && in_array($taskData[$k], ['hours', 'days', 'weeks', 'months', 'years'])) {
            $insertData[$k] = $db->escape_string($taskData[$k]);
        }
    }

    foreach (
        [
            'usergroups',
            'fthreadsforums',
            'fpostsforums',
            TASK_REQUIREMENT_TYPE_AWARDS_GRANTED,
            'profilefields'
        ] as $k
    ) {
        if (isset($taskData[$k]) && is_array($taskData[$k])) {
            $insertData[$k] = $db->escape_string(
                implode(',', array_filter(array_unique(array_map('intval', $taskData[$k]))))
            );
        }
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

function taskUpdate(array $taskData, int $taskID): int
{
    return taskInsert($taskData, $taskID, true);
}

function taskDelete(int $taskID): bool
{
    global $db;

    $db->delete_query('ougc_awards_tasks', "tid='{$taskID}'");

    $db->delete_query('ougc_awards_tasks_logs', "tid='{$taskID}'");

    return true;
}

function taskGet(array $whereClauses = [], string $queryFields = '*', array $queryOptions = []): array
{
    global $db;

    $cacheObjects = [];

    $dbQuery = $db->simple_select('ougc_awards_tasks', $queryFields, implode(' AND ', $whereClauses), $queryOptions);

    if ($db->num_rows($dbQuery)) {
        if (isset($queryOptions['limit']) && $queryOptions['limit'] === 1) {
            $cacheObjects = $db->fetch_array($dbQuery);
        } else {
            while ($userData = $db->fetch_array($dbQuery)) {
                $cacheObjects[] = $userData;
            }
        }
    }

    return $cacheObjects;
}

function logGet(array $whereClauses = [], string $queryFields = '*', array $queryOptions = []): array
{
    global $db;

    $cacheObjects = [];

    $dbQuery = $db->simple_select(
        'ougc_awards_tasks_logs',
        $queryFields,
        implode(' AND ', $whereClauses),
        $queryOptions
    );

    if ($db->num_rows($dbQuery)) {
        if (isset($queryOptions['limit']) && $queryOptions['limit'] === 1) {
            $cacheObjects = $db->fetch_array($dbQuery);
        } else {
            while ($logData = $db->fetch_array($dbQuery)) {
                $cacheObjects[] = $logData;
            }
        }
    }

    return $cacheObjects;
}

function sendPrivateMessage(array $privateMessage, int $fromUserID = 0, bool $adminOverride = false): bool
{
    if (getSetting('notificationPrivateMessage')) {
        send_pm($privateMessage, $fromUserID, $adminOverride);
    }

    return true;
}

function sendAlert(int $awardID, int $userID, string $alertTypeKey = 'give_award'): bool
{
    global $lang, $mybb, $alertType, $db;

    loadLanguage();

    if (!class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
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

    $statsLimit = min($mybb->settings['statslimit'], getSetting('statsLatestGrants'));

    $cacheData = [
        'time' => TIME_NOW,
        'awards' => [],
        'categories' => [],
        'requests' => ['pending' => 0],
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

    while ($categoryData = $db->fetch_array($query)) {
        $cacheData['categories'][(int)$categoryData['cid']] = [
            'name' => (string)$categoryData['name'],
            'description' => (string)$categoryData['description'],
            'allowrequests' => (int)$categoryData['allowrequests']
        ];
    }

    if ($categoryIDs = array_keys($cacheData['categories'])) {
        $whereClauses = [
            "visible='1'",
            "cid IN ('" . implode("','", $categoryIDs) . "')"
        ];

        $query = $db->simple_select(
            'ougc_awards',
            'aid, cid, name, template, description, image, allowrequests, type, disporder, visible',
            implode(' AND ', $whereClauses),
            ['order_by' => 'disporder']
        );

        while ($awardData = $db->fetch_array($query)) {
            $cacheData['awards'][(int)$awardData['aid']] = [
                'cid' => (int)$awardData['cid'],
                'name' => (string)$awardData['name'],
                'template' => (int)$awardData['template'],
                'description' => (string)$awardData['description'],
                'image' => (string)$awardData['image'],
                'allowrequests' => (int)$awardData['allowrequests'],
                'type' => (int)$awardData['type'],
                'disporder' => (int)$awardData['disporder'],
                'visible' => (int)$awardData['visible']
            ];
        }
    }

    if ($awardIDs = array_keys($cacheData['awards'])) {
        $requestStatusOpen = REQUEST_STATUS_PENDING;

        $awardIDs = implode("','", $awardIDs);

        $whereClauses = [
            "aid IN ('{$awardIDs}')",
            'status' => "status='{$requestStatusOpen}'"
        ];

        $totalRequestsCount = requestGetPending(
            $whereClauses,
            'COUNT(rid) AS totalRequests',
            ['limit' => 1]
        );

        if (!empty($totalRequestsCount['totalRequests'])) {
            $cacheData['requests'] = ['pending' => (int)$totalRequestsCount['totalRequests']];
        }

        unset($whereClauses['status']);

        $whereClauses = implode(' AND ', $whereClauses);

        $query = $db->query(
            "
				SELECT u.uid, a.awards
				FROM {$db->table_prefix}users u
				LEFT JOIN (
					SELECT g.uid, COUNT(g.aid) AS awards
					FROM {$db->table_prefix}ougc_awards_users g
					WHERE g.{$whereClauses}
					GROUP BY g.uid, g.aid
				) a ON (u.uid=a.uid)
				WHERE a.awards!=''
				ORDER BY a.awards DESC
				LIMIT 0, {$statsLimit}
			;"
        );

        while ($userData = $db->fetch_array($query)) {
            $cacheData['top'][(int)$userData['uid']] = (int)$userData['awards'];
        }

        $query = $db->simple_select(
            'ougc_awards_users',
            'uid, date',
            $whereClauses,
            ['order_by' => 'date', 'order_dir' => 'desc', 'limit' => $statsLimit]
        );

        while ($userData = $db->fetch_array($query)) {
            $cacheData['last'][(int)$userData['date']] = (int)$userData['uid'];
        }
    }

    $query = $db->simple_select('ougc_awards_tasks', 'tid, name, reason', '', ['order_by' => 'disporder']);

    while ($task = $db->fetch_array($query)) {
        $cacheData['tasks'][(int)$task['tid']] = [
            'name' => (string)$task['name'],
            'reason' => (string)$task['reason']
        ];
    }

    $mybb->cache->update('ougc_awards', $cacheData);

    return true;
}

function generateSelectAwards(string $inputName, array $selectedIDs = [], array $selectOptions = []): string
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

function generateSelectProfileFields(string $inputName, array $selectedIDs = [], array $selectOptions = []): string
{
    global $db, $mybb;

    $selectCode = "<select name=\"{$inputName}\"";

    !isset($selectOptions['multiple']) || $selectCode .= " multiple=\"multiple\"";

    !isset($selectOptions['id']) || $selectCode .= " id=\"id\"";

    $selectCode .= '>';

    foreach (getProfileFieldsCache() as $profileFieldData) {
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

        $grantReason = $grantData['reason'];

        parseMessage($grantReason);

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

    $selectName = 'categoryID';

    $dbQuery = $db->simple_select('ougc_awards_categories', '*', '', ['order_by' => 'disporder']);

    $selectOptions = $multipleOption = '';

    while ($categoryData = $db->fetch_array($dbQuery)) {
        $selectedElement = '';

        if ((int)$categoryData['cid'] === $selectedID) {
            $selectedElement = 'selected="selected"';
        }

        $optionValue = (int)$categoryData['cid'];

        $optionName = $categoryData['name'];

        $onChange = '';

        $selectOptions .= eval(getTemplate('selectFieldOption'));
    }

    return eval(getTemplate('selectField'));
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

function canRequestAwards(int $awardID = 0, int $categoryID = 0): bool
{
    global $mybb;

    if (empty($mybb->user['uid'])) {
        return false;
    }

    if (!empty($awardID)) {
        $awardData = awardGet($awardID);

        $categoryID = (int)$awardData['cid'];

        if (empty($awardData['allowrequests'])) {
            return false;
        }
    }

    if (!empty($categoryID)) {
        $categoryData = categoryGet($categoryID);

        if (empty($categoryData['allowrequests'])) {
            return false;
        }
    }

    return true;
}

function canViewMainPage(): bool
{
    global $mybb;

    return (bool)is_member(getSetting('groupsView'));
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

            $presetOptions .= eval(getTemplate('usercp_presets_select_option'));
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
            'allow_html' => false,
            'allow_mycode' => true,
            'allow_smilies' => true,
            'allow_imgcode' => true,
            'filter_badwords' => true,
            'nl2br' => false
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

        $awardName = htmlspecialchars_uni($awardData['name']);

        $awardDescription = htmlspecialchars_uni($awardData['description']);

        $grantID = (int)$grantData['gid'];

        $requestID = (int)$grantData['rid'];

        $taskID = (int)$grantData['tid'];

        $grantReason = $grantData['reason'];

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

        $awardImage = $awardClass = awardGetIcon($awardID);

        $awardImage = eval(
        getTemplate(
            $awardData['template'] === AWARD_TEMPLATE_TYPE_CLASS ? 'awardImageClass' : 'awardImage'
        )
        );

        $awardUrl = urlHandlerBuild(['action' => 'viewUsers', 'awardID' => $awardID]);

        $awardImage = eval(getTemplate('awardWrapper', false));

        $grantDate = my_date('normal', $grantData['date']);

        $formattedContent .= eval(getTemplate($templateName));

        $alternativeBackground = alt_trow();
    }

    return $formattedContent;
}

// Most of this was taken from @Starpaul20's Move Post plugin (https://github.com/PaulBender/Move-Posts)
function getThreadByUrl(string $threadUrl): array
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

    $real_url = explode('#', $threadUrl);

    $threadUrl = $real_url[0];

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
                $temp2 = explode('=', $temp[$i], MyBB::INPUT_ARRAY);

                $parameters[$temp2[0]] = $temp2[1];
            }
        } else {
            $temp2 = explode('=', $splitloc[1], MyBB::INPUT_ARRAY);

            $parameters[$temp2[0]] = $temp2[1];
        }
    }

    $threadID = 0;

    if (!empty($parameters['pid']) && empty($parameters['tid'])) {
        $post = get_post($parameters['pid']);

        $threadID = $post['tid'];
    } elseif (!empty($parameters['tid'])) {
        $threadID = $parameters['tid'];
    }

    return (array)get_thread($threadID);
}

function isModerator(): bool
{
    return (bool)is_member(getSetting('groupsModerators'));
}

function isVisibleCategory(int $categoryID): bool
{
    global $mybb;

    $categoryData = categoryGet($categoryID);

    $currentUserID = (int)$mybb->user['uid'];

    return !empty($categoryData['visible']) || isModerator();
}

function isVisibleAward(int $awardID): bool
{
    global $mybb;

    $awardData = awardGet($awardID);

    $categoryID = (int)$awardData['cid'];

    $currentUserID = (int)$mybb->user['uid'];

    return !empty($awardData['visible']) || isModerator() || ownerCategoryFind($categoryID, $currentUserID);
}

function myAlertsInitiate(): bool
{
    if (!function_exists('myalerts_info')) {
        return false;
    }

    if (class_exists('MybbStuff_MyAlerts_Formatter_AbstractFormatter')) {
        require_once ROOT . '/class_alerts.php';
    }

    if (version_compare(myalerts_info()['version'], getSetting('myAlertsVersion')) <= 0) {
        myalerts_register_client_alert_formatters();
    }

    return true;
}

function uploadAward(array $awardFile, int $awardID): array
{
    require_once MYBB_ROOT . 'inc/functions_upload.php';

    if (!is_uploaded_file($awardFile['tmp_name'])) {
        return ['error' => FILE_UPLOAD_ERROR_FAILED];
    }

    $fileExtension = get_extension(my_strtolower($awardFile['name']));

    if (!preg_match('#^(gif|jpg|jpeg|jpe|bmp|png)$#i', $fileExtension)) {
        return ['error' => FILE_UPLOAD_ERROR_INVALID_TYPE];
    }

    $uploadPath = getSetting('uploadPath');

    $fileName = "award_{$awardID}.{$fileExtension}";

    $fileUpload = upload_file($awardFile, $uploadPath, $fileName);

    $fullFilePath = "{$uploadPath}/{$fileName}";

    if (!empty($fileUpload['error'])) {
        delete_uploaded_file($fullFilePath);

        return ['error' => FILE_UPLOAD_ERROR_FAILED];
    }

    if (!file_exists($fullFilePath)) {
        delete_uploaded_file($fullFilePath);

        return ['error' => FILE_UPLOAD_ERROR_FAILED];
    }

    $imageDimensions = getimagesize($fullFilePath);

    if (!is_array($imageDimensions)) {
        delete_uploaded_file($fullFilePath);

        return ['error' => FILE_UPLOAD_ERROR_FAILED];
    }

    if (getSetting('uploadDimensions')) {
        list($maximumWidth, $maximumHeight) = preg_split('/[|x]/', getSetting('uploadDimensions'));

        if (($maximumWidth && $imageDimensions[0] > $maximumWidth) || ($maximumHeight && $imageDimensions[1] > $maximumHeight)) {
            require_once MYBB_ROOT . 'inc/functions_image.php';

            $thumbnail = generate_thumbnail(
                $fullFilePath,
                $uploadPath,
                $fileName,
                $maximumHeight,
                $maximumWidth
            );

            if (empty($thumbnail['filename'])) {
                delete_uploaded_file($fullFilePath);

                return ['error' => FILE_UPLOAD_ERROR_RESIZE];
            } else {
                copy_file_to_cdn("{$uploadPath}/{$thumbnail['filename']}");

                $awardFile['size'] = filesize($fullFilePath);

                $imageDimensions = getimagesize($fullFilePath);
            }
        }
    }

    $awardFile['type'] = my_strtolower($awardFile['type']);

    switch ($awardFile['type']) {
        case 'image/gif':
            $imageType = 1;
            break;
        case 'image/jpeg':
        case 'image/x-jpg':
        case 'image/x-jpeg':
        case 'image/pjpeg':
        case 'image/jpg':
            $imageType = 2;
            break;
        case 'image/png':
        case 'image/x-png':
            $imageType = 3;
            break;
        case 'image/bmp':
        case 'image/x-bmp':
        case 'image/x-windows-bmp':
            $imageType = 6;
            break;
    }

    if ((int)$imageDimensions[2] !== $imageType || empty($imageType)) {
        delete_uploaded_file($fullFilePath);

        return ['error' => FILE_UPLOAD_ERROR_FAILED];
    }

    if (getSetting('uploadSize') > 0 && $awardFile['size'] > (getSetting('uploadSize') * 1024)) {
        delete_uploaded_file($fullFilePath);

        return ['error' => FILE_UPLOAD_ERROR_UPLOAD_SIZE];
    }

    return [
        'fileName' => $fileName,
        'fileWidth' => (int)$imageDimensions[0],
        'fileHeight' => (int)$imageDimensions[1]
    ];
}

function awardsCacheGet(): array
{
    global $mybb;

    return (array)$mybb->cache->read('ougc_awards');
}

function getComparisonLanguageVariable(string $comparisonOperator): string
{
    global $lang;

    switch ($comparisonOperator) {
        case COMPARISON_TYPE_GREATER_THAN:
            return $lang->ougcAwardsControlPanelViewTasksTypeGreaterThan;
        case COMPARISON_TYPE_GREATER_THAN_OR_EQUAL:
            return $lang->ougcAwardsControlPanelViewTasksTypeGreaterThanOrEqualTo;
        case COMPARISON_TYPE_EQUAL:
            return $lang->ougcAwardsControlPanelViewTasksTypeEqualTo;
        case COMPARISON_TYPE_NOT_EQUAL:
            return $lang->ougcAwardsControlPanelViewTasksTypeNotEqualTo;
        case COMPARISON_TYPE_LESS_THAN_OR_EQUAL:
            return $lang->ougcAwardsControlPanelViewTasksTypeLessThanOrEqualTo;
    }

    return '';
}

function getTimeLanguageVariable(string $timeType, bool $isPlural): string
{
    global $lang;

    switch ($timeType) {
        case TASK_REQUIREMENT_TIME_TYPE_HOURS:
            if ($isPlural) {
                return $lang->ougcAwardsControlPanelViewTasksTimeTypeHoursPlural;
            }
            return $lang->ougcAwardsControlPanelViewTasksTimeTypeHours;
        case TASK_REQUIREMENT_TIME_TYPE_DAYS:
            if ($isPlural) {
                return $lang->ougcAwardsControlPanelViewTasksTimeTypeDaysPlural;
            }
            return $lang->ougcAwardsControlPanelViewTasksTimeTypeDays;
        case TASK_REQUIREMENT_TIME_TYPE_WEEKS:
            if ($isPlural) {
                return $lang->ougcAwardsControlPanelViewTasksTimeTypeWeeksPlural;
            }
            return $lang->ougcAwardsControlPanelViewTasksTimeTypeWeeks;
        case TASK_REQUIREMENT_TIME_TYPE_MONTHS:
            if ($isPlural) {
                return $lang->ougcAwardsControlPanelViewTasksTimeTypeMonthsPlural;
            }
            return $lang->ougcAwardsControlPanelViewTasksTimeTypeMonths;
        case TASK_REQUIREMENT_TIME_TYPE_YEARS:
            if ($isPlural) {
                return $lang->ougcAwardsControlPanelViewTasksTimeTypeYearsPlural;
            }
            return $lang->ougcAwardsControlPanelViewTasksTimeTypeYears;
    }

    return '';
}

function getProfileFieldsCache(): array
{
    global $mybb;
    global $profiecats;

    if (
        class_exists('OUGC_ProfiecatsCache') && $profiecats instanceof OUGC_ProfiecatsCache &&
        !empty($profiecats->cache['original'])
    ) {
        return $profiecats->cache['original'];
    }

    return (array)$mybb->cache->read('profilefields');
}