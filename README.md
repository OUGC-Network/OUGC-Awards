<h3 align="center">ougc Awards</h3>

<div align="center">

[![Status](https://img.shields.io/badge/status-active-success.svg)]()
[![GitHub Issues](https://img.shields.io/github/issues/OUGC-Network/ougc-Awards.svg)](./issues)
[![GitHub Pull Requests](https://img.shields.io/github/issues-pr/OUGC-Network/ougc-Awards.svg)](./pulls)
[![License](https://img.shields.io/badge/license-GPL-blue)](/LICENSE)

</div>

---

<p align="center"> Manage a powerful awards system for your community.
    <br> 
</p>

## 📜 Table of Contents <a name = "table_of_contents"></a>

- [About](#about)
- [Getting Started](#getting_started)
    - [Dependencies](#dependencies)
    - [File Structure](#file_structure)
    - [Install](#install)
    - [Update](#update)
    - [Template Modifications](#template_modifications)
- [Settings](#settings)
    - [File Level Settings](#file_level_settings)
- [Templates](#templates)
- [Built Using](#built_using)
- [Authors](#authors)
- [Acknowledgments](#acknowledgement)
- [Support & Feedback](#support)

## 🚀 About <a name = "about"></a>

Manage a powerful awards system for your community.

[Go up to Table of Contents](#table_of_contents)

## 📍 Getting Started <a name = "getting_started"></a>

The following information will assist you into getting a copy of this plugin up and running on your forum.

### Dependencies <a name = "dependencies"></a>

A setup that meets the following requirements is necessary to use this plugin.

- [MyBB](https://mybb.com/) >= 1.8
- PHP >= 7
- [MyBB-PluginLibrary](https://github.com/frostschutz/MyBB-PluginLibrary) >= 13

### File structure <a name = "file_structure"></a>

  ```
   .
   ├── inc
   │ ├── plugins
   │ │ ├── ougc
   │ │ │ ├── Awards
   │ │ │ │ ├── admin
   │ │ │ │ │ ├── user.php
   │ │ │ │ ├── hooks
   │ │ │ │ │ ├── admin.php
   │ │ │ │ │ ├── forum.php
   │ │ │ │ │ ├── shared.php
   │ │ │ │ ├── templates
   │ │ │ │ │ ├── awardImage.html
   │ │ │ │ │ ├── awardImageClass.html
   │ │ │ │ │ ├── awardWrapper.html
   │ │ │ │ │ ├── controlPanel.html
   │ │ │ │ │ ├── controlPanelButtons.html
   │ │ │ │ │ ├── controlPanelCategoryOwners.html
   │ │ │ │ │ ├── controlPanelConfirmation.html
   │ │ │ │ │ ├── controlPanelConfirmationDeleteAward.html
   │ │ │ │ │ ├── controlPanelConfirmationDeleteCategory.html
   │ │ │ │ │ ├── controlPanelConfirmationDeleteOwner.html
   │ │ │ │ │ ├── controlPanelContents.html
   │ │ │ │ │ ├── controlPanelEmpty.html
   │ │ │ │ │ ├── controlPanelGrantEdit.html
   │ │ │ │ │ ├── controlPanelList.html
   │ │ │ │ │ ├── controlPanelListButtonUpdateCategory.html
   │ │ │ │ │ ├── controlPanelListCategoryLinks.html
   │ │ │ │ │ ├── controlPanelListCategoryLinksModerator.html
   │ │ │ │ │ ├── controlPanelListColumnDisplayOrder.html
   │ │ │ │ │ ├── controlPanelListColumnEnabled.html
   │ │ │ │ │ ├── controlPanelListColumnOptions.html
   │ │ │ │ │ ├── controlPanelListColumnRequest.html
   │ │ │ │ │ ├── controlPanelListRow.html
   │ │ │ │ │ ├── controlPanelListRowDisplayOrder.html
   │ │ │ │ │ ├── controlPanelListRowEmpty.html
   │ │ │ │ │ ├── controlPanelListRowEnabled.html
   │ │ │ │ │ ├── controlPanelListRowOptions.html
   │ │ │ │ │ ├── controlPanelListRowRequest.html
   │ │ │ │ │ ├── controlPanelListRowRequestButton.html
   │ │ │ │ │ ├── controlPanelLogs.html
   │ │ │ │ │ ├── controlPanelLogsRow.html
   │ │ │ │ │ ├── controlPanelMyAwards.html
   │ │ │ │ │ ├── controlPanelMyAwardsEmpty.html
   │ │ │ │ │ ├── controlPanelMyAwardsRow.html
   │ │ │ │ │ ├── controlPanelMyAwardsRowLink.html
   │ │ │ │ │ ├── controlPanelNewEditAwardForm.html
   │ │ │ │ │ ├── controlPanelNewEditAwardFormUpload.html
   │ │ │ │ │ ├── controlPanelNewEditCategoryForm.html
   │ │ │ │ │ ├── controlPanelNewEditTaskForm.html
   │ │ │ │ │ ├── controlPanelNewEditTaskFormRequirementRow.html
   │ │ │ │ │ ├── controlPanelOwners.html
   │ │ │ │ │ ├── controlPanelOwnersEmpty.html
   │ │ │ │ │ ├── controlPanelOwnersRow.html
   │ │ │ │ │ ├── controlPanelPresets.html
   │ │ │ │ │ ├── controlPanelPresetsAward.html
   │ │ │ │ │ ├── controlPanelPresetsDefault.html
   │ │ │ │ │ ├── controlPanelPresetsForm.html
   │ │ │ │ │ ├── controlPanelPresetsRow.html
   │ │ │ │ │ ├── controlPanelPresetsSelect.html
   │ │ │ │ │ ├── controlPanelRequests.html
   │ │ │ │ │ ├── controlPanelRequestsEmpty.html
   │ │ │ │ │ ├── controlPanelRequestsRow.html
   │ │ │ │ │ ├── controlPanelTasks.html
   │ │ │ │ │ ├── controlPanelTasksRow.html
   │ │ │ │ │ ├── controlPanelUsers.html
   │ │ │ │ │ ├── controlPanelUsersColumnOptions.html
   │ │ │ │ │ ├── controlPanelUsersEmpty.html
   │ │ │ │ │ ├── controlPanelUsersFormGrant.html
   │ │ │ │ │ ├── controlPanelUsersFormRevoke.html
   │ │ │ │ │ ├── controlPanelUsersRow.html
   │ │ │ │ │ ├── controlPanelUsersRowLink.html
   │ │ │ │ │ ├── controlPanelUsersRowOptions.html
   │ │ │ │ │ ├── css.html
   │ │ │ │ │ ├── global_menu.html
   │ │ │ │ │ ├── globalNotification.html
   │ │ │ │ │ ├── globalPagination.html
   │ │ │ │ │ ├── inputField.html
   │ │ │ │ │ ├── js.html
   │ │ │ │ │ ├── modcp_requests_buttons.html
   │ │ │ │ │ ├── page.html
   │ │ │ │ │ ├── pageRequest.html
   │ │ │ │ │ ├── pageRequestButton.html
   │ │ │ │ │ ├── pageRequestError.html
   │ │ │ │ │ ├── pageRequestForm.html
   │ │ │ │ │ ├── pageRequestSuccess.html
   │ │ │ │ │ ├── postBitPreset.html
   │ │ │ │ │ ├── postBitViewAll.html
   │ │ │ │ │ ├── profile.html
   │ │ │ │ │ ├── profile_row.html
   │ │ │ │ │ ├── profileEmpty.html
   │ │ │ │ │ ├── profilePagination.html
   │ │ │ │ │ ├── profilePresets.html
   │ │ │ │ │ ├── profilePresetsRow.html
   │ │ │ │ │ ├── profileViewAll.html
   │ │ │ │ │ ├── radioField.html
   │ │ │ │ │ ├── selectField.html
   │ │ │ │ │ ├── selectFieldOption.html
   │ │ │ │ │ ├── stats.html
   │ │ │ │ │ ├── stats_empty.html
   │ │ │ │ │ ├── statsUserRow.html
   │ │ │ │ │ ├── textAreaField.html
   │ │ │ │ │ ├── viewAll.html
   │ │ │ │ │ ├── viewUser.html
   │ │ │ │ │ ├── viewUserEmpty.html
   │ │ │ │ │ ├── viewUserError.html
   │ │ │ │ │ ├── viewUserRow.html
   │ │ │ │ ├── admin.php
   │ │ │ │ ├── class_alerts.php
   │ │ │ │ ├── core.php
   │ │ │ │ ├── settings.json
   │ │ │ ├── ougc_awards.php
   │ ├── tasks
   │ │ ├── ougc_awards.php
   ├── jscripts
   │ ├── ougc_awards.js
   ├── uploads
   │ ├── awards
   │ │ ├── index.html
   └── awards.php
   ```

### Installing <a name = "install"></a>

Follow the next steps in order to install a copy of this plugin on your forum.

1. Download the latest package from the [MyBB Extend](https://community.mybb.com/mods.php) site or
   from the [repository releases](https://github.com/OUGC-Network/ougc-Awards/releases/latest).
2. Upload the contents of the _Upload_ folder to your MyBB root directory.
3. Browse to _Configuration » Plugins_ and install this plugin by clicking _Install & Activate_.
4. Browse to _Settings_ to manage the plugin settings.

### Updating <a name = "update"></a>

Follow the next steps in order to update your copy of this plugin.

1. Browse to _Configuration » Plugins_ and deactivate this plugin by clicking _Deactivate_.
2. Follow step 1 and 2 from the [Install](#install) section.
3. Browse to _Configuration » Plugins_ and activate this plugin by clicking _Activate_.
4. Browse to _Settings_ to manage the plugin settings.

### Template Modifications <a name = "template_modifications"></a>

To display Awards data it is required that you edit the following template for each of your themes.

1. Place `{$ougcAwardsJavaScript}` in the `headerinclude` template.
2. Place `{$ougcAwardsCSS}` in the `headerinclude` template.
3. Place `{$ougcAwardsMenu}` after `{$menu_calendar}` in the `header` template to display a link to the awards page.
4. Place `{$memprofile['ougc_awards']}` after `{$profilefields}` in the `member_profile` template to display the awards
   in the user profile.
5. Place `{$memprofile['ougc_awards_preset']}` after `{$warning_level}` in the `member_profile` template to display the
   user preset in the user profile.
6. Place `{$memprofile['ougc_awards_view_all']}` in the `member_profile` template to display a link to view all awards
   in the user profile.
7. Place `{$post['ougc_awards']}` after `{$post['user_details']}` in the `postbit` and `postbit_classic` template to
   display the awards in the user posts.
8. Place `{$post['ougc_awards_preset']}` after `{$post['user_details']}` in the `postbit` and `postbit_classic` template
   to display the
   user preset in the user posts.
9. Place `{$post['ougc_awards_view_all']}` in the `postbit` and `postbit_classic` template to display a link to view all
   awards in the user posts
10. Place `{$ougcAwardsStatsLast}` in the `stats` template to display the last award grants in the stats page.
11. Place `{$ougcAwardsViewAll}` in the `header` template to display a link to view all awards for the current user.
12. Place `{$ougcAwardsGlobalNotificationRequests}` in the `header` template to display the requests notification to
    moderator, category owners, and user owners.

[Go up to Table of Contents](#table_of_contents)

## 🛠 Settings <a name = "settings"></a>

Below you can find a description of the plugin settings.

### Main Settings

- **Maximum Awards in Posts** `numeric`
    - _Maximum number of awards to be shown in posts._
- **Maximum Presets in Posts** `numeric`
    - _Type the maximum preset awards to display in posts._
- **Maximum Awards in Profile** `numeric`
    - _Maximum number of awards to be shown in profiles._
- **Maximum Preset Awards in Profiles** `numeric`
    - _Type the maximum preset awards to display in profiles._
- **Items Per Page** `numeric`
    - _Maximum number of items to show per page or within listings._
- **View Groups** `select`
    - _Allowed groups to view the awards page._
- **Presets Allowed Groups** `select`
    - _Select which groups are allowed to use and create presets._
- **Maximum Presets** `numeric`
    - _Select the maximum amount of presets can create._
- **Moderator Groups** `select`
    - _Allowed groups to manage awards._
- **Enable Stats** `yesNo`
    - _Do you want to enable the top and last granted users in the stats page?_
- **Latest Grants in Stats** `numeric`
    - _Type the maximum number of latest grants to show in the stats page._
- **Send PM** `yesNo`
    - _Do you want to send an PM to users when receiving an award?_
- **Grant Default Visible Status** `yesNo`
    - _Select the visible status of awards when granting awards to users. If set to <code>No</code>, users will need to
      set their awards as visible in the sorting page from withing the My Awards page._
- **Uploads Path** `text`
    - _Type the path where the awards images will be uploaded._
- **Uploads Dimensions** `text`
    - _Type the maximum dimensions for the awards images. Default <code>32|32</code>._
- **Uploads Size** `numeric`
    - _Type the maximum size in bytes for the awards images. Default <code>50</code>._

### File Level Settings <a name = "file_level_settings"></a>

Additionally, you can force your settings by updating the `SETTINGS` array constant in the `ougc\Awards\Core`
namespace in the `./inc/plugins/ougc_awards.php` file. Any setting set this way will always bypass any front-end
configuration. Use the setting key as shown below:

```PHP
define('ougc\Awards\Core\SETTINGS', [
    'allowImports' => false,
    'myAlertsVersion' => '2.1.0'
]);
```

[Go up to Table of Contents](#table_of_contents)

## 📐 Templates <a name = "templates"></a>

The following is a list of templates available for this plugin.

- `ougcawards_awardImage`
    - _front end_;
- `ougcawards_awardImageClass`
    - _front end_;
- `ougcawards_awardWrapper`
    - _front end_;
- `ougcawards_controlPanel`
    - _front end_;
- `ougcawards_controlPanelButtons`
    - _front end_;
- `ougcawards_controlPanelCategoryOwners`
    - _front end_;
- `ougcawards_controlPanelConfirmation`
    - _front end_;
- `ougcawards_controlPanelConfirmationDeleteAward`
    - _front end_;
- `ougcawards_controlPanelConfirmationDeleteCategory`
    - _front end_;
- `ougcawards_controlPanelConfirmationDeleteOwner`
    - _front end_;
- `ougcawards_controlPanelContents`
    - _front end_;
- `ougcawards_controlPanelEmpty`
    - _front end_;
- `ougcawards_controlPanelGrantEdit`
    - _front end_;
- `ougcawards_controlPanelList`
    - _front end_;
- `ougcawards_controlPanelListButtonUpdateCategory`
    - _front end_;
- `ougcawards_controlPanelListCategoryLinks`
    - _front end_;
- `ougcawards_controlPanelListCategoryLinksModerator`
    - _front end_;
- `ougcawards_controlPanelListColumnDisplayOrder`
    - _front end_;
- `ougcawards_controlPanelListColumnEnabled`
    - _front end_;
- `ougcawards_controlPanelListColumnOptions`
    - _front end_;
- `ougcawards_controlPanelListColumnRequest`
    - _front end_;
- `ougcawards_controlPanelListRow`
    - _front end_;
- `ougcawards_controlPanelListRowDisplayOrder`
    - _front end_;
- `ougcawards_controlPanelListRowEmpty`
    - _front end_;
- `ougcawards_controlPanelListRowEnabled`
    - _front end_;
- `ougcawards_controlPanelListRowOptions`
    - _front end_;
- `ougcawards_controlPanelListRowRequest`
    - _front end_;
- `ougcawards_controlPanelListRowRequestButton`
    - _front end_;
- `ougcawards_controlPanelLogs`
    - _front end_;
- `ougcawards_controlPanelLogsRow`
    - _front end_;
- `ougcawards_controlPanelMyAwards`
    - _front end_;
- `ougcawards_controlPanelMyAwardsEmpty`
    - _front end_;
- `ougcawards_controlPanelMyAwardsRow`
    - _front end_;
- `ougcawards_controlPanelMyAwardsRowLink`
    - _front end_;
- `ougcawards_controlPanelNewEditAwardForm`
    - _front end_;
- `ougcawards_controlPanelNewEditAwardFormUpload`
    - _front end_;
- `ougcawards_controlPanelNewEditCategoryForm`
    - _front end_;
- `ougcawards_controlPanelNewEditTaskForm`
    - _front end_;
- `ougcawards_controlPanelNewEditTaskFormRequirementRow`
    - _front end_;
- `ougcawards_controlPanelOwners`
    - _front end_;
- `ougcawards_controlPanelOwnersEmpty`
    - _front end_;
- `ougcawards_controlPanelOwnersRow`
    - _front end_;
- `ougcawards_controlPanelPresets`
    - _front end_;
- `ougcawards_controlPanelPresetsAward`
    - _front end_;
- `ougcawards_controlPanelPresetsDefault`
    - _front end_;
- `ougcawards_controlPanelPresetsForm`
    - _front end_;
- `ougcawards_controlPanelPresetsRow`
    - _front end_;
- `ougcawards_controlPanelPresetsSelect`
    - _front end_;
- `ougcawards_controlPanelRequests`
    - _front end_;
- `ougcawards_controlPanelRequestsEmpty`
    - _front end_;
- `ougcawards_controlPanelRequestsRow`
    - _front end_;
- `ougcawards_controlPanelTasks`
    - _front end_;
- `ougcawards_controlPanelTasksRow`
    - _front end_;
- `ougcawards_controlPanelUsers`
    - _front end_;
- `ougcawards_controlPanelUsersColumnOptions`
    - _front end_;
- `ougcawards_controlPanelUsersEmpty`
    - _front end_;
- `ougcawards_controlPanelUsersFormGrant`
    - _front end_;
- `ougcawards_controlPanelUsersFormRevoke`
    - _front end_;
- `ougcawards_controlPanelUsersRow`
    - _front end_;
- `ougcawards_controlPanelUsersRowLink`
    - _front end_;
- `ougcawards_controlPanelUsersRowOptions`
    - _front end_;
- `ougcawards_css`
    - _front end_;
- `ougcawards_global_menu`
    - _front end_;-
- `ougcawards_globalNotification`
    - _front end_;
- `ougcawards_globalPagination`
    - _front end_;
- `ougcawards_inputField`
    - _front end_;
- `ougcawards_js`
    - _front end_;
- `ougcawards_modcp_requests_buttons`
    - _front end_;
- `ougcawards_page`
    - _front end_;
- `ougcawards_pageRequest`
    - _front end_;
- `ougcawards_pageRequestButton`
    - _front end_;
- `ougcawards_pageRequestError`
    - _front end_;
- `ougcawards_pageRequestForm`
    - _front end_;
- `ougcawards_pageRequestSuccess`
    - _front end_;
- `ougcawards_postBitPreset`
    - _front end_;
- `ougcawards_postBitViewAll`
    - _front end_;
- `ougcawards_profile`
    - _front end_;
- `ougcawards_profile_row`
    - _front end_;
- `ougcawards_profileEmpty`
    - _front end_;
- `ougcawards_profilePagination`
    - _front end_;
- `ougcawards_profilePresets`
    - _front end_;
- `ougcawards_profilePresetsRow`
    - _front end_;
- `ougcawards_profileViewAll`
    - _front end_;
- `ougcawards_radioField`
    - _front end_;
- `ougcawards_selectField`
    - _front end_;
- `ougcawards_selectFieldOption`
    - _front end_;
- `ougcawards_stats`
    - _front end_;
- `ougcawards_stats_empty`
    - _front end_;
- `ougcawards_statsUserRow`
    - _front end_;
- `ougcawards_textAreaField`
    - _front end_;
- `ougcawards_viewAll`
    - _front end_;
- `ougcawards_viewUser`
    - _front end_;
- `ougcawards_viewUserEmpty`
    - _front end_;
- `ougcawards_viewUserError`
    - _front end_;
- `ougcawards_viewUserRow`
    - _front end_;

[Go up to Table of Contents](#table_of_contents)

## ⛏ Built Using <a name = "built_using"></a>

- [MyBB](https://mybb.com/) - Web Framework
- [MyBB PluginLibrary](https://github.com/frostschutz/MyBB-PluginLibrary) - A collection of useful functions for MyBB
- [PHP](https://www.php.net/) - Server Environment

[Go up to Table of Contents](#table_of_contents)

## ✍️ Authors <a name = "authors"></a>

- [@Omar G](https://github.com/Sama34) - Idea & Initial work

See also the list of [contributors](https://github.com/OUGC-Network/ougc-Awards/contributors) who participated in
this
project.

[Go up to Table of Contents](#table_of_contents)

## 🎉 Acknowledgements <a name = "acknowledgement"></a>

- [The Documentation Compendium](https://github.com/kylelobo/The-Documentation-Compendium)

[Go up to Table of Contents](#table_of_contents)

## 🎈 Support & Feedback <a name="support"></a>

This is free development and any contribution is welcome. Get support or leave feedback at the
official [MyBB Community](https://community.mybb.com/thread-159249.html).

Thanks for downloading and using our plugins!

[Go up to Table of Contents](#table_of_contents)