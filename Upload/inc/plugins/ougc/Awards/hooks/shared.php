<?php

/***************************************************************************
 *
 *    ougc Awards plugin (/inc/plugins/ougc/Awards/hooks/shared.php)
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

namespace ougc\Awards\Hooks\Shared;

use UserDataHandler;

function datahandler_user_insert(UserDataHandler &$dataHandler): UserDataHandler
{
    $dataHandler->user_insert_data['ougc_awards'] = '';

    return $dataHandler;
}

function datahandler_user_delete_end(UserDataHandler &$dataHandler): UserDataHandler
{
    global $db;

    $db->delete_query('ougc_awards_users', "uid IN ({$dataHandler->delete_uids})");

    $db->delete_query('ougc_awards_category_owners', "userID IN ({$dataHandler->delete_uids})");

    $db->delete_query('ougc_awards_owners', "uid IN ({$dataHandler->delete_uids})");

    $db->delete_query('ougc_awards_requests', "uid IN ({$dataHandler->delete_uids})");

    $db->delete_query('ougc_awards_tasks_logs', "uid IN ({$dataHandler->delete_uids})");

    $db->delete_query('ougc_awards_presets', "uid IN ({$dataHandler->delete_uids})");

    return $dataHandler;
}