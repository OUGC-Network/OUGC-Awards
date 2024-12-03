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
use function ougc\Awards\Core\awardGet;
use function ougc\Awards\Core\awardGetIcon;
use function ougc\Awards\Core\cacheUpdate;
use function ougc\Awards\Admin\pluginInfo;
use function ougc\Awards\Admin\pluginActivate;
use function ougc\Awards\Admin\pluginDeactivate;
use function ougc\Awards\Admin\pluginIsInstalled;

use function ougc\Awards\Core\getTemplate;
use function ougc\Awards\Core\loadLanguage;

use const ougc\Awards\Core\AWARD_TEMPLATE_TYPE_CLASS;
use const ougc\Awards\ROOT;

defined('IN_MYBB') || die('This file cannot be accessed directly.');

// You can uncomment the lines below to avoid storing some settings in the DB
define('ougc\Awards\Core\SETTINGS', [
    //'key' => '',
    'allowImports' => false,
    'showInPosts' => 2,
    'showInPostsPresets' => 2,
    'allowedGroupsPresets' => -1,
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

if (class_exists('MybbStuff_MyAlerts_Formatter_AbstractFormatter')) {
    class OUGC_Awards_MyAlerts_Formatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter
    {
        public function init()
        {
            loadLanguage();
        }

        /**
         * Format an alert into it's output string to be used in both the main alerts listing page and the popup.
         *
         * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to format.
         *
         * @return string The formatted alert string.
         */
        public function formatAlert(MybbStuff_MyAlerts_Entity_Alert $alert, array $outputAlert)
        {
            $Details = $alert->toArray();

            $ExtraDetails = $alert->getExtraDetails();

            $awardData = awardGet((int)$Details['object_id']);

            $awardName = htmlspecialchars_uni($awardData['name']);

            $awardImage = $awardClass = awardGetIcon((int)$Details['object_id']);

            $awardImage = eval(
            getTemplate(
                $awardData['template'] === AWARD_TEMPLATE_TYPE_CLASS ? 'awardImageClass' : 'awardImage'
            )
            );

            /*$FromUser = $alert->getFromUser();
			$FromUser['avatar'] = $award['image'];
			$alert->setFromUser($FromUser);*/

            return $this->lang->sprintf(
                $this->lang->ougc_awards_myalerts,
                $outputAlert['username'],
                $outputAlert['from_user'],
                $awardData['name'],
                $awardImage
            );
        }

        /**
         * Build a link to an alert's content so that the system can redirect to it.
         *
         * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to build the link for.
         *
         * @return string The built alert, preferably an absolute link.
         */
        public function buildShowLink(MybbStuff_MyAlerts_Entity_Alert $alert)
        {
            global $settings;

            $Details = $alert->toArray();
            $ExtraDetails = $alert->getExtraDetails();

            return $settings['bburl'] . '/awards.php?view=' . (int)$Details['object_id'];
        }
    }
}