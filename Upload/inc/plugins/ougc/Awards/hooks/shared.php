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