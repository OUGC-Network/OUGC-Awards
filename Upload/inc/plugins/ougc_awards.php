<?php

/***************************************************************************
 *
 *    ougc Awards plugin (/inc/plugins/ougc_awards.php)
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

use function ougc\Awards\Admin\pluginUninstall;
use function ougc\Awards\Core\addHooks;
use function ougc\Awards\Core\cacheUpdate;
use function ougc\Awards\Admin\pluginInfo;
use function ougc\Awards\Admin\pluginActivate;
use function ougc\Awards\Admin\pluginDeactivate;
use function ougc\Awards\Admin\pluginIsInstalled;

use const ougc\Awards\ROOT;

defined('IN_MYBB') || die('This file cannot be accessed directly.');

// You can uncomment the lines below to avoid storing some settings in the DB
define('ougc\Awards\Core\SETTINGS', [
    //'key' => '',
    'allowImports' => false,
    'myAlertsVersion' => '2.1.0'
]);

define('ougc\Awards\Core\DEBUG', true);

define('ougc\Awards\ROOT', MYBB_ROOT . 'inc/plugins/ougc/Awards');

require_once ROOT . '/core.php';

defined('PLUGINLIBRARY') || define('PLUGINLIBRARY', MYBB_ROOT . 'inc/plugins/pluginlibrary.php');

if (defined('IN_ADMINCP')) {
    require_once ROOT . '/admin.php';
    require_once ROOT . '/hooks/admin.php';

    addHooks('ougc\Awards\Hooks\Admin');
} else {
    require_once ROOT . '/hooks/forum.php';

    addHooks('ougc\Awards\Hooks\Forum');
}

require_once ROOT . '/hooks/shared.php';

addHooks('ougc\Awards\Hooks\Shared');

function ougc_awards_info(): array
{
    return pluginInfo();
}

function ougc_awards_activate(): bool
{
    return pluginActivate();
}

function ougc_awards_deactivate(): bool
{
    return pluginDeactivate();
}

function ougc_awards_is_installed(): bool
{
    return pluginIsInstalled();
}

function ougc_awards_uninstall(): bool
{
    return pluginUninstall();
}

function update_ougc_awards()
{
    cacheUpdate();
}