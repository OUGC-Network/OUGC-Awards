<?php

/***************************************************************************
 *
 *	OUGC Awards plugin (/inc/anguages/english/ougc_awards.lang.php)
 *	Author: Omar Gonzalez
 *	Copyright: © 2012-2014 Omar Gonzalez
 *
 *	Website: http://omarg.me
 *
 *	Extend your forum with a powerful awards system.
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

// Plugin information.
$l['ougc_awards'] = 'OUGC Awards';

// ModCP
$l['ougc_awards_usercp_nav'] = 'Awards';
$l['ougc_awards_modcp_nav'] = 'Manage Awards';
$l['ougc_awards_modcp_list_desc'] = 'Grant or revoke awards from this quick access panel.';

// Requests
$l['ougc_awards_modcp_requests_nav'] = 'Requests';
$l['ougc_awards_modcp_requests_logs_nav'] = 'Requests';
$l['ougc_awards_modcp_requests_list_title'] = 'Manage Requests';
$l['ougc_awards_modcp_requests_list_desc'] = 'Manage the pending requests of awards or see any request logs.';
$l['ougc_awards_modcp_requests_list_empty'] = 'There are currently no requests to see.';
$l['ougc_awards_modcp_requests_list_viewlogs'] = 'View Logs';
$l['ougc_awards_modcp_requests_list_accept'] = 'Accept';
$l['ougc_awards_modcp_requests_list_reject'] = 'Reject';
$l['ougc_awards_modcp_requests_list_status_pending'] = 'Pending';
$l['ougc_awards_modcp_requests_list_status_rejected'] = 'Rejected';
$l['ougc_awards_modcp_requests_list_status_accepted'] = 'Accepted';

// Errors.
$l['ougc_awards_error_wrongaward'] = 'The selected award does not exists.';
$l['ougc_awards_error_wrongowner'] = 'Your status for this award is invalid.';
$l['ougc_awards_error_invalidcategory'] = 'The selected category does not exists.';
$l['ougc_awards_error_invaliduser'] = 'The selected user is invalid.';
$l['ougc_awards_error_give'] = 'The selected user already has this award.';
$l['ougc_awards_error_notgive'] = 'The selected user doesn\'t have the selected award.';
$l['ougc_awards_error_giveperm'] = 'You don\'t have permission to edit the selected user.';
$l['ougc_awards_error_invalidthread'] = 'You entered an invalid thread value.';
$l['ougc_awards_error_noneselected'] = 'You didn\'t select any award upon which to perform the action.';
$l['ougc_awards_error_active'] = 'The awards system is currently not active.';
$l['ougc_awards_error_pendingrequest'] = 'There is a active request pending for this award from you.';

// Words
$l['ougc_awards_modcp_username'] = 'Username';
$l['ougc_awards_modcp_multiple'] = 'Multiple Users';
$l['ougc_awards_modcp_multiple_note'] = 'Select whether or not you are giving this award to multiple users at once.<br />
The same reason will be used for all users.<br />
All usernames must be correct.';
$l['ougc_awards_modcp_give'] = 'Give';
$l['ougc_awards_modcp_revoke'] = 'Revoke';
$l['ougc_awards_modcp_reason'] = 'Reason';
$l['ougc_awards_modcp_thread'] = 'Thread';
$l['ougc_awards_modcp_gived'] = 'Given Award';

// Phrases
$l['ougc_awards_modcp_title_give'] = 'Manage Award: {1}';
$l['ougc_awards_modcp_title_give_desc'] = 'In this page you can manage this award, whether as a moderator or as an owner.';
$l['ougc_awards_modcp_list_empty'] = 'Currently there are not awards to manage.';
$l['ougc_awards_modcp_cache'] = 'Update Cache';

// Redirects
$l['ougc_awards_redirect_gived'] = 'Award given successfully.';
$l['ougc_awards_redirect_revoked'] = 'Award revoked successfully.';
$l['ougc_awards_redirect_cache'] = 'Cache updated successfully.';
$l['ougc_awards_redirect_request'] = 'Award requested successfully.';
$l['ougc_awards_redirect_request_accepted'] = 'Award request accepted successfully.';
$l['ougc_awards_redirect_request_rejected'] = 'Award request rejected successfully.';

// Profile
$l['ougc_awards_profile_empty'] = 'This user has no awards at this time.';
$l['ougc_awards_profile_tine'] = '{1} <i>at</i> {2}';
$l['ougc_awards_profile_title'] = '{1}\' awards.';

// PMs
$l['ougc_awards_pm_title'] = 'You have been given the {1} award!';
$l['ougc_awards_pm_noreason'] = 'There was no reason specified.';
$l['ougc_awards_pm_noreason_request_accepted'] = 'Your request for this award was accepted.';
$l['ougc_awards_pm_noreason_request_rejected_subject'] = 'Your request for the {1} award was rejected.';
$l['ougc_awards_pm_noreason_request_rejected_message'] = 'Hi {1}. This is an automatic message to inform you that your request for the {2} award was rejected.

Greetings.';

// View all
$l['ougc_awards_viewall'] = 'View User Awards';
$l['ougc_awards_viewall_title'] = '{1}\'s Awards';

// Page strings
$l['ougc_awards_page_title'] = 'Awards';
$l['ougc_awards_page_list_empty'] = 'No awards were found.';
$l['ougc_awards_page_list_award'] = 'Award';
$l['ougc_awards_page_list_name'] = 'Name';
$l['ougc_awards_page_list_description'] = 'Description';
$l['ougc_awards_page_list_request'] = 'Request';
$l['ougc_awards_page_view_date'] = 'Date';
$l['ougc_awards_page_view_empty'] = 'No users were found.';

// Welcomeblock
$l['ougc_awards_welcomeblock'] = 'View My Awards';
$l['ougc_awards_welcomeblock_empty'] = 'Your award list is currently empty.'; 

// Modal
$l['ougc_awards_request_title'] = 'Request Award';
$l['ougc_awards_request_desc'] = 'Request this award to a moderator.';
$l['ougc_awards_request_name'] = 'Award:';
$l['ougc_awards_request_message'] = 'Message:';
$l['ougc_awards_request_button'] = 'Submit';

// Stats
$l['ougc_awards_stats_most'] = 'Most Awarded';
$l['ougc_awards_stats_last'] = 'Lastest Awarded';
$l['ougc_awards_stats_empty'] = 'There are currently no stats to display.';
$l['ougc_awards_stats_username'] = 'Username';
$l['ougc_awards_stats_total'] = 'Total';
$l['ougc_awards_stats_viewall'] = 'View All';

// UserCP
$l['ougc_awards_usercp_list_visible'] = 'Display';
$l['ougc_awards_usercp_list_disporder'] = 'Display Order';
$l['ougc_awards_usercp_list_reason'] = 'Reason';
$l['ougc_awards_usercp_list_from'] = 'From User';

// Global
$l['ougc_awards_global_menu'] = 'Awards';
$l['ougc_awards_page_pending_requests'] = 'You have {1} pending request(s) for this award.';
$l['ougc_awards_page_pending_requests_moderator'] = '<a href="{1}/{2}?action=awards&amp;manage=requests"><strong>Moderator Notice:</strong> There is one award request pending for review.</a>';
$l['ougc_awards_page_pending_requests_moderator_plural'] = '<a href="{1}/{2}?action=awards&amp;manage=requests"><strong>Moderator Notice:</strong> There are {3} award requests pending for review.</a>';

// WOL
$l['ougc_awards_wol'] = 'Viewing <a href="{1}/awards.php">Awards Page</a>';

// MyAlerts
$l['ougc_awards_myalerts'] = '{1}, you were granted the {3} award by {2}.';// {4} outputs the award formatted image
$l['myalerts_setting_ougc_awards'] = 'Receive alerts related to awards?';