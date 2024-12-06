<?php

/***************************************************************************
 *
 *    ougc Awards plugin (/inc/plugins/ougc/Awards/admin.php)
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

namespace ougc\Awards\Admin;

use DirectoryIterator;

use function ougc\Awards\Core\allowImports;
use function ougc\Awards\Core\cacheUpdate;
use function ougc\Awards\Core\categoryInsert;
use function ougc\Awards\Core\loadLanguage;
use function ougc\Awards\Core\loadPluginLibrary;

use const MYBB_ROOT;
use const ougc\Awards\Core\ADMIN_PERMISSION_DELETE;
use const ougc\Awards\Core\REQUEST_STATUS_REJECTED;
use const ougc\Awards\Core\TABLES_DATA;
use const ougc\Awards\Core\FIELDS_DATA;
use const ougc\Awards\ROOT;

const TASK_ENABLE = 1;

const TASK_DEACTIVATE = 0;

const TASK_DELETE = -1;

function pluginInfo(): array
{
    global $lang;

    loadLanguage();

    $descriptionText = $lang->ougcAwardsDescription;

    if (allowImports()) {
        $descriptionText .= $lang->ougcAwardsImportDescription;
    }

    return [
        'name' => 'ougc Awards',
        'description' => $descriptionText,
        'website' => 'https://ougc.network',
        'author' => 'Omar G.',
        'authorsite' => 'https://ougc.network',
        'version' => '1.8.33',
        'versioncode' => 1833,
        'compatibility' => '18*',
        'myalerts' => '2.0.4',
        'codename' => 'ougc_awards',
        'newpoints' => '2.1.1',
        'pl' => [
            'version' => 13,
            'url' => 'https://community.mybb.com/mods.php?action=view&pid=573'
        ]
    ];
}

function pluginActivate(): bool
{
    global $PL, $cache, $lang;

    loadLanguage();

    $pluginInfo = pluginInfo();

    loadPluginLibrary();

    $settingsContents = file_get_contents(ROOT . '/settings.json');

    $settingsData = json_decode($settingsContents, true);

    foreach ($settingsData as $settingKey => &$settingData) {
        if (empty($lang->{"setting_ougc_awards_{$settingKey}"})) {
            continue;
        }

        if ($settingData['optionscode'] == 'select' || $settingData['optionscode'] == 'checkbox') {
            foreach ($settingData['options'] as $optionKey) {
                $settingData['optionscode'] .= "\n{$optionKey}={$lang->{"setting_ougc_awards_{$settingKey}_{$optionKey}"}}";
            }
        }

        $settingData['title'] = $lang->{"setting_ougc_awards_{$settingKey}"};

        $settingData['description'] = $lang->{"setting_ougc_awards_{$settingKey}_desc"};
    }

    $PL->settings(
        'ougc_awards',
        $lang->setting_group_ougc_awards,
        $lang->setting_group_ougc_awards_desc,
        $settingsData
    );

    $templates = [];

    if (file_exists($templateDirectory = ROOT . '/templates')) {
        $templatesDirIterator = new DirectoryIterator($templateDirectory);

        foreach ($templatesDirIterator as $template) {
            if (!$template->isFile()) {
                continue;
            }

            $pathName = $template->getPathname();

            $pathInfo = pathinfo($pathName);

            if ($pathInfo['extension'] === 'html') {
                $templates[$pathInfo['filename']] = file_get_contents($pathName);
            }
        }
    }

    if ($templates) {
        $PL->templates('ougcawards', 'ougc Awards', $templates);
    }

    global $db, $mybb;

    if (class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
        global $alertTypeManager;

        isset($alertTypeManager) or $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance(
            $db,
            $mybb->cache
        );

        $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

        $alertType = new MybbStuff_MyAlerts_Entity_AlertType();

        $alertType->setCode('ougc_awards');

        $alertType->setEnabled(true);

        $alertType->setCanBeUserDisabled(true);

        $alertTypeManager->add($alertType);
    }

    $plugins = $cache->read('ougc_plugins');

    if (!$plugins) {
        $plugins = [];
    }

    if (!isset($plugins['awards'])) {
        $plugins['awards'] = $pluginInfo['versioncode'];
    }

    /*~*~* RUN UPDATES START *~*~*/

    $db->update_query('ougc_awards_requests ', ['status' => REQUEST_STATUS_REJECTED], 'status="-1"');

    dbVerifyTables();

    dbVerifyColumns();

    enableTask();

    change_admin_permission('tools', 'ougc_awards', ADMIN_PERMISSION_DELETE);

    if ($plugins['awards'] <= 1803) {
        $dbQuery = $db->simple_select('ougc_awards', 'aid');

        if ($db->num_rows($dbQuery)) {
            $categoryID = categoryInsert([
                'name' => 'Default',
                'description' => 'Default category created after an update.',
                'disporder' => 1
            ]);

            $db->update_query('ougc_awards', ['cid' => $categoryID]);
        }
    }

    if ($plugins['awards'] <= 1800) {
        $tmpls = [
            'modcp_ougc_awards' => 'ougcawards_modcp',
            'modcp_ougc_awards_manage' => 'ougcawards_modcp_manage',
            'modcp_ougc_awards_nav' => 'ougcawards_modcp_nav',
            'modcp_ougc_awards_list' => 'ougcawards_modcp_list',
            'modcp_ougc_awards_list_empty' => 'ougcawards_modcp_list_empty',
            'modcp_ougc_awards_list_award' => 'ougcawards_modcp_list_award',
            'modcp_ougc_awards_manage_reason' => 'ougcawards_modcp_manage_reason',
            'postbit_ougc_awards' => 'ougcawards_postbit',
            'member_profile_ougc_awards_row_empty' => 'ougcawards_profile_row_empty',
            'member_profile_ougc_awards_row' => 'ougcawards_profile_row',
            'member_profile_ougc_awards' => 'ougcawards_profile',
            'ougc_awards_page' => 'ougcawards_page',
            'ougc_awards_page_list' => 'ougcawards_page_list',
            'ougc_awards_page_list_award' => 'ougcawards_page_list_award',
            'ougc_awards_page_list_empty' => 'ougcawards_page_list_empty',
            'ougc_awards_page_user' => 'ougcawards_page_user',
            'ougc_awards_page_user_award' => 'ougcawards_page_user_award',
            'ougc_awards_page_user_empty' => 'ougcawards_page_user_empty',
            'ougc_awards_page_view' => 'ougcawards_page_view',
            'ougc_awards_page_view_empty' => 'ougcawards_page_view_empty',
            'ougc_awards_page_view_row' => 'ougcawards_page_view_row',
        ];

        $templateNames = implode("','", $tmpls);

        // Try to update old templates
        $query = $db->simple_select('templates', '*', "title IN ('{$templateNames}')");
        while ($tmpl = $db->fetch_array($query)) {
            check_template($tmpl['template']) or $tmplcache[$tmpl['title']] = $tmpl;
        }

        foreach ($tmpls as $oldtitle => $newtitle) {
            $db->update_query('templates', [
                'title' => $db->escape_string($newtitle),
                'version' => 1,
                'dateline' => TIME_NOW
            ], 'title=\'' . $db->escape_string($oldtitle) . '\' AND sid=\'-2\'');
        }

        // Rebuild templates
        static $done = false;
        if (!$done) {
            $done = true;
            $funct = __FUNCTION__;
            $funct();
        }

        // Delete old templates if not updated
        $tmpls['ougc_awards_image'] = '';
        $db->delete_query(
            'templates',
            'title IN(\'' . implode(
                '\', \'',
                array_keys(array_map([$db, 'escape_string'], $tmpls))
            ) . '\') AND sid=\'-2\''
        );

        // Delete old template group
        $db->delete_query('templategroups', 'prefix=\'ougc_awards\'');
    }

    /*~*~* RUN UPDATES END *~*~*/

    $plugins['Awards'] = $pluginInfo['versioncode'];

    $cache->update('ougc_plugins', $plugins);

    cacheUpdate();

    return true;
}

function pluginDeactivate(): bool
{
    disableTask();

    return true;
}

function pluginInstall(): bool
{
    dbVerifyTables();

    dbVerifyColumns();

    enableTask();

    return true;
}

function pluginIsInstalled(): bool
{
    static $isInstalled = null;

    if ($isInstalled === null) {
        global $db;

        $isInstalled = false;

        foreach (dbTables() as $tableName => $tableData) {
            $isInstalled = (bool)$db->table_exists($tableName) ?? false;

            break;
        }
    }

    return $isInstalled;
}

function pluginUninstall(): bool
{
    global $db, $PL, $cache;

    loadPluginLibrary();

    foreach (dbTables() as $tableName => $tableData) {
        if ($db->table_exists($tableName)) {
            $db->drop_table($tableName);
        }
    }

    foreach (FIELDS_DATA as $table => $columns) {
        if ($db->table_exists($table)) {
            foreach ($columns as $field => $definition) {
                if ($db->field_exists($field, $table)) {
                    $db->drop_column($table, $field);
                }
            }
        }
    }

    $PL->settings_delete('ougc_awards');

    $PL->templates_delete('ougcawards');

    if (class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
        global $alertTypeManager;

        isset($alertTypeManager) or $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance(
            $db,
            $cache
        );

        $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

        $alertTypeManager->deleteByCode('ougc_awards');
    }

    deleteTask();

    // Delete version from cache
    $plugins = (array)$cache->read('ougc_plugins');

    if (isset($plugins['awards'])) {
        unset($plugins['awards']);
    }

    if (!empty($plugins)) {
        $cache->update('ougc_plugins', $plugins);
    } else {
        $cache->delete('ougc_plugins');
    }

    return true;
}

function enableTask(int $action = TASK_ENABLE): bool
{
    global $db, $lang;

    loadLanguage();

    if ($action === TASK_DELETE) {
        $db->delete_query('tasks', "file='ougc_awards'");

        return true;
    }

    $query = $db->simple_select('tasks', '*', "file='ougc_awards'", ['limit' => 1]);

    $task = $db->fetch_array($query);

    if ($task) {
        $db->update_query('tasks', ['enabled' => $action], "file='ougc_awards'");
    } else {
        include_once MYBB_ROOT . 'inc/functions_task.php';

        $_ = $db->escape_string('*');

        $new_task = [
            'title' => $db->escape_string($lang->setting_group_ougc_awards),
            'description' => $db->escape_string($lang->setting_group_ougc_awards_desc),
            'file' => $db->escape_string('ougc_awards'),
            'minute' => 0,
            'hour' => $_,
            'day' => $_,
            'weekday' => $_,
            'month' => $_,
            'enabled' => 1,
            'logging' => 1
        ];

        $new_task['nextrun'] = fetch_next_run($new_task);

        $db->insert_query('tasks', $new_task);
    }

    return true;
}

function disableTask(): bool
{
    enableTask(TASK_DEACTIVATE);

    return true;
}

function deleteTask(): bool
{
    enableTask(TASK_DELETE);

    return true;
}

function dbTables(): array
{
    $tablesData = [];

    foreach (TABLES_DATA as $tableName => $fieldsData) {
        foreach ($fieldsData as $fieldName => $fieldData) {
            $fieldDefinition = '';

            if (!isset($fieldData['type'])) {
                continue;
            }

            $fieldDefinition .= $fieldData['type'];

            if (isset($fieldData['size'])) {
                $fieldDefinition .= "({$fieldData['size']})";
            }

            if (isset($fieldData['unsigned'])) {
                $fieldDefinition .= ' UNSIGNED';
            }

            if (!isset($fieldData['null'])) {
                $fieldDefinition .= ' NOT';
            }

            $fieldDefinition .= ' NULL';

            if (isset($fieldData['auto_increment'])) {
                $fieldDefinition .= ' AUTO_INCREMENT';
            }

            if (isset($fieldData['default'])) {
                $fieldDefinition .= " DEFAULT '{$fieldData['default']}'";
            }

            $tablesData[$tableName][$fieldName] = $fieldDefinition;
        }

        foreach ($fieldsData as $fieldName => $fieldData) {
            if (isset($fieldData['primary_key'])) {
                $tablesData[$tableName]['primary_key'] = $fieldName;
            }
            if ($fieldName === 'unique_key') {
                $tablesData[$tableName]['unique_key'] = $fieldData;
            }
        }
    }

    return $tablesData;
}

function dbVerifyTables(): bool
{
    global $db;

    $collation = $db->build_create_table_collation();

    $tablePrefix = $db->table_prefix;

    foreach (dbTables() as $tableName => $tableData) {
        if ($db->table_exists($tableName)) {
            foreach ($tableData as $field => $definition) {
                if ($field == 'primary_key' || $field == 'unique_key') {
                    continue;
                }

                if ($db->field_exists($field, $tableName)) {
                    $db->modify_column($tableName, "`{$field}`", $definition);
                } else {
                    $db->add_column($tableName, $field, $definition);
                }
            }
        } else {
            $query = "CREATE TABLE IF NOT EXISTS `{$tablePrefix}{$tableName}` (";

            foreach ($tableData as $field => $definition) {
                if ($field == 'primary_key') {
                    $query .= "PRIMARY KEY (`{$definition}`)";
                } elseif ($field != 'unique_key') {
                    $query .= "`{$field}` {$definition},";
                }
            }

            $query .= ") ENGINE=MyISAM{$collation};";

            $db->write_query($query);
        }
    }

    dbVerifyIndexes();

    return true;
}

function dbVerifyIndexes(): bool
{
    global $db;

    $tablePrefix = $db->table_prefix;

    foreach (dbTables() as $tableName => $tableData) {
        if (!$db->table_exists($tableName)) {
            continue;
        }

        if (isset($tableData['unique_key'])) {
            foreach ($tableData['unique_key'] as $keyName => $keyValue) {
                if ($db->index_exists($tableName, $keyName)) {
                    continue;
                }

                $db->write_query("ALTER TABLE {$tablePrefix}{$tableName} ADD UNIQUE KEY {$keyName} ({$keyValue})");
            }
        }
    }

    return true;
}

function dbVerifyColumns(): bool
{
    global $db;

    foreach (FIELDS_DATA as $tableName => $tableColumns) {
        foreach ($tableColumns as $fieldName => $fieldData) {
            if (!isset($fieldData['type'])) {
                continue;
            }

            if ($db->field_exists($fieldName, $tableName)) {
                $db->modify_column($tableName, "`{$fieldName}`", dbBuildFieldDefinition($fieldData));
            } else {
                $db->add_column($tableName, $fieldName, dbBuildFieldDefinition($fieldData));
            }
        }
    }

    return true;
}

function dbBuildFieldDefinition(array $fieldData): string
{
    $fieldDefinition = '';

    $fieldDefinition .= $fieldData['type'];

    if (isset($fieldData['size'])) {
        $fieldDefinition .= "({$fieldData['size']})";
    }

    if (isset($fieldData['unsigned'])) {
        if ($fieldData['unsigned'] === true) {
            $fieldDefinition .= ' UNSIGNED';
        } else {
            $fieldDefinition .= ' SIGNED';
        }
    }

    if (!isset($fieldData['null'])) {
        $fieldDefinition .= ' NOT';
    }

    $fieldDefinition .= ' NULL';

    if (isset($fieldData['auto_increment'])) {
        $fieldDefinition .= ' AUTO_INCREMENT';
    }

    if (isset($fieldData['default'])) {
        $fieldDefinition .= " DEFAULT '{$fieldData['default']}'";
    }

    return $fieldDefinition;
}