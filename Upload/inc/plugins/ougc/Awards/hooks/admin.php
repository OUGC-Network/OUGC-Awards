<?php

/***************************************************************************
 *
 *    ougc Awards plugin (/inc/plugins/ougc/Awards/hooks/admin.php)
 *    Author: Omar Gonzalez
 *    Copyright: © 2012 Omar Gonzalez
 *
 *    Website: https://ougc.network
 *
 *    Manage a powerful awards system for your community.
 *
 ***************************************************************************
 ****************************************************************************
 * This program is protected software: you can make use of it under
 * the terms of the OUGC Network EULA as detailed by the included
 * "EULA.TXT" file.
 *
 * This program is distributed with the expectation that it will be
 * useful, but WITH LIMITED WARRANTY; with a limited warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * OUGC Network EULA included in the "EULA.TXT" file for more details.
 *
 * You should have received a copy of the OUGC Network EULA along with
 * the package which includes this file.  If not, see
 * <https://ougc.network/eula.txt>.
 ****************************************************************************/

declare(strict_types=1);

namespace ougc\Awards\Hooks\Admin;

use MyBB;

use function ougc\Awards\Core\allowImports;
use function ougc\Awards\Core\grantInsert;
use function ougc\Awards\Core\awardInsert;
use function ougc\Awards\Core\categoryInsert;
use function ougc\Awards\Core\loadLanguage;

use const ougc\Awards\ROOT;

function admin_config_plugins_deactivate()
{
    global $mybb, $page;

    if (
        $mybb->get_input('action') != 'deactivate' ||
        $mybb->get_input('plugin') != 'ougc_awards' ||
        !$mybb->get_input('uninstall', MyBB::INPUT_INT)
    ) {
        return;
    }

    if ($mybb->request_method != 'post') {
        $page->output_confirm_action(
            'index.php?module=config-plugins&amp;action=deactivate&amp;uninstall=1&amp;plugin=ougc_awards'
        );
    }

    if ($mybb->get_input('no')) {
        admin_redirect('index.php?module=config-plugins');
    }
}

function admin_config_settings_start()
{
    loadLanguage();
}

function admin_style_templates_set()
{
    loadLanguage();
}

function admin_config_settings_change()
{
    loadLanguage();
}

function admin_config_plugins_begin()
{
    global $mybb, $plugins;

    if (!allowImports() || !($type = $mybb->get_input('ougc_awards_import'))) {
        return false;
    }

    switch ($type) {
        case 'nickawards';
            $name = 'Nickman\'s';
            $tables = ['awards' => 'awards', 'users' => 'awards_given'];
            $keys = [
                'name' => 'name',
                'description' => '',
                'image' => 'image',
                'original_id' => 'id',
                'original_id_u' => 'award_id',
                'uid' => 'to_uid',
                'reason' => 'reason',
                'TIME_NOW' => 'date_given'
            ];
            $img_prefix = '{bburl}/images/awards/';
            $lang_var = 'ougc_awards_import_confirm_nickawards';
            break;
        default;
            $name = 'MyAwards';
            $tables = ['awards' => 'myawards', 'users' => 'myawards_users'];
            $keys = [
                'name' => 'awname',
                'description' => 'awdescr',
                'image' => 'awimg',
                'original_id' => 'awid',
                'original_id_u' => 'awid',
                'uid' => 'awuid',
                'reason' => 'awreason',
                'TIME_NOW' => 'awutime'
            ];
            $img_prefix = '{bburl}/uploads/awards/';
            $lang_var = 'ougc_awards_import_confirm_myawards';
            break;
    }

    $args = [
        'tables' => &$tables,
        'keys' => &$keys,
        'img_prefix' => &$img_prefix,
        'lang_var' => &$lang_var,
    ];

    $plugins->run_hooks('ougc_awards_importer_start', $args);

    global $lang, $mybb, $page;

    loadLanguage();

    if ($mybb->request_method == 'post') {
        if (!verify_post_check($mybb->get_input('my_post_key'))) {
            flash_message($lang->invalid_post_verify_key2, 'error');
            admin_redirect('index.php?module=config-plugins');
        }

        if (isset($mybb->input['no'])) {
            return true;
        }

        global $db;

        if (!$db->table_exists($tables['awards'])) {
            flash_message($lang->sprintf($lang->ougc_awards_import_error, $tables['awards']), 'error');
            admin_redirect('index.php?module=config-plugins');
        }

        $query = $db->simple_select('ougc_awards_categories', 'MAX(disporder) AS max_disporder');
        $disporder = (int)$db->fetch_field($query, 'max_disporder');

        $categoryID = categoryInsert([
            'name' => 'Imported ' . $name . ' Awards',
            'description' => 'Automatic category created after an import.',
            'allowrequests' => 0,
            'disporder' => ++$disporder
        ]);

        $disporder = 0;

        $query = $db->simple_select($tables['awards']);
        while ($award = $db->fetch_array($query)) {
            $insert_award = [
                'cid' => $categoryID,
                'name' => $db->escape_string($award[$keys['name']]),
                'description' => $db->escape_string($award[$keys['description']]),
                'image' => $db->escape_string($img_prefix . $award[$keys['image']]),
                'disporder' => isset($award[$keys['disporder']]) ? (int)$award[$keys['disporder']] : ++$disporder,
                'allowrequests' => 0,
                'pm' => ''
            ];

            $awardID = awardInsert($insert_award);

            $insert_award['aid'] = $awardID;
            $insert_award[$keys['original_id']] = $award[$keys['original_id']];

            $cache_awards[$award[$keys['original_id']]] = $insert_award;
        }

        $mybb->settings['enablepms'] = false;

        $query = $db->simple_select($tables['users']);
        while ($award = $db->fetch_array($query)) {
            $insert_award = [
                'aid' => $cache_awards[$award[$keys['original_id_u']]]['aid'],
                'uid' => $award[$keys['uid']],
                'reason' => $award[$keys['reason']],
                'TIME_NOW' => $award[$keys['TIME_NOW']]
            ];

            grantInsert(
                $cache_awards[$award[$keys['original_id_u']]]['aid'],
                $insert_award['uid'],
                $insert_award['reason']
            );
        }

        $plugins->run_hooks('ougc_awards_importer_end', $args);

        flash_message($lang->ougc_awards_import_end, 'success');
        admin_redirect('index.php?module=config-plugins');
    }

    $page->output_confirm_action(
        "index.php?module=config-plugins&ougc_awards_import={$type}",
        $lang->{$lang_var},
        $lang->ougc_awards_import_title
    );

    return true;
}