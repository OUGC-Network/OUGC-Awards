<?php

/***************************************************************************
 *
 *	OUGC Awards plugin (/admin/modules/user/ougc_awards.php)
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

$ougc_awards->lang_load();
$mybb->input['action'] = (isset($mybb->input['action']) ? trim($mybb->input['action']) : '');
$mybb->input['uid'] = (isset($mybb->input['uid']) ? (int)$mybb->input['uid'] : 0);

$sub_tabs['ougc_awards_view'] = array(
	'title'			=> $lang->ougc_awards_tab_view,
	'link'			=> 'index.php?module=user-ougc_awards',
	'description'	=> $lang->ougc_awards_tab_view_d
);
$sub_tabs['ougc_awards_add'] = array(
	'title'			=> $lang->ougc_awards_tab_add,
	'link'			=> 'index.php?module=user-ougc_awards&amp;action=add',
	'description'	=> $lang->ougc_awards_tab_add_d
);

switch($mybb->input['action'])
{
	case 'edit':
		$sub_tabs['ougc_awards_edit'] = array(
			'title'			=> $lang->ougc_awards_tab_edit,
			'link'			=> 'index.php?module=user-ougc_awards&amp;action=edit&amp;aid='.$mybb->input['aid'],
			'description'	=> $lang->ougc_awards_tab_edit_d
		);
		break;
	case 'give':
		$sub_tabs['ougc_awards_give'] = array(
			'title'			=> $lang->ougc_awards_tab_give,
			'link'			=> 'index.php?module=user-ougc_awards&amp;action=give&amp;aid='.$mybb->input['aid'],
			'description'	=> $lang->ougc_awards_tab_give_d
		);
		break;
	case 'revoke':
		$sub_tabs['ougc_awards_revoke'] = array(
			'title'			=> $lang->ougc_awards_tab_revoke,
			'link'			=> 'index.php?module=user-ougc_awards&amp;action=revoke&amp;aid='.$mybb->input['aid'],
			'description'	=> $lang->ougc_awards_tab_revoke_d
		);
		break;
	case 'users':
		$sub_tabs['ougc_awards_users'] = array(
			'title'			=> $lang->ougc_awards_tab_users,
			'link'			=> 'index.php?module=user-ougc_awards&amp;action=users&amp;aid='.$mybb->input['aid'],
			'description'	=> $lang->ougc_awards_tab_users_d
		);
		break;
	/*
	case 'user':
	$sub_tabs['ougc_awards_edit_user'] = array(
		'title'			=> $lang->ougc_awards_tab_edit_user,
		'link'			=> 'index.php?module=user-ougc_awards&amp;action=user&amp;aid='.$mybb->input['aid'].'&amp;uid='.$mybb->input['uid'],
		'description'	=> $lang->ougc_awards_tab_edit_user_d
	);
		break;*/
}

if($mybb->input['action'] == 'add' || $mybb->input['action'] == 'edit')
{
	$add = ($mybb->input['action'] == 'add');

	$page->add_breadcrumb_item($lang->ougc_awards_acp_nav, $ougc_awards->build_url());

	if(!$add)
	{
		if(!($award = $ougc_awards->get_award($mybb->input['aid'])))
		{
			$awards->admin_redirect($lang->ougc_awards_error_edit, true);
		}

		$page->add_breadcrumb_item(strip_tags($award['name']));
	}

	$ougc_awards->set_award_data();

	$page->output_header($lang->ougc_awards_acp_nav);

	if($add)
	{
		$ougc_awards->set_award_data();
		$page->output_nav_tabs($sub_tabs, 'ougc_awards_add');
	}
	else
	{
		$ougc_awards->set_award_data($award['aid']);
		$page->output_nav_tabs($sub_tabs, 'ougc_awards_edit');
	}

	if($mybb->request_method == 'post')
	{
		if($ougc_awards->validate_award())
		{
			if($add)
			{
				$ougc_awards->insert_award($ougc_awards->award_data);
				$lang_val = 'ougc_awards_success_add';
			}
			else
			{
				$ougc_awards->update_award($ougc_awards->award_data, $award['aid']);
				$lang_val = 'ougc_awards_success_edit';
			}
			$ougc_awards->log_action();
			$ougc_awards->admin_redirect($lang->$lang_val);
		}
		else
		{
			$page->output_inline_error($ougc_awards->validation_errors);
		}
	}

	if($add)
	{
		$form = new Form($ougc_awards->build_url('action=add'), 'post');
		$form_container = new FormContainer($sub_tabs['ougc_awards_add']['description']);
	}
	else
	{
		$form = new Form($ougc_awards->build_url(array('action' => 'edit', 'aid' => $award['aid'])), 'post');
		$form_container = new FormContainer($sub_tabs['ougc_awards_edit']['description']);
	}


	$form_container->output_row($lang->ougc_awards_form_name.' <em>*</em>', $lang->ougc_awards_form_name_d, $form->generate_text_box('name', $ougc_awards->award_data['name']));
	$form_container->output_row($lang->ougc_awards_form_desc, $lang->ougc_awards_form_desc_d, $form->generate_text_box('description', $ougc_awards->award_data['description']));
	$form_container->output_row($lang->ougc_awards_form_image, $lang->ougc_awards_form_image_d, $form->generate_text_box('image', $ougc_awards->award_data['image']));
	$form_container->output_row($lang->ougc_awards_form_visible, $lang->ougc_awards_form_visible_d, $form->generate_yes_no_radio('visible', $ougc_awards->award_data['visible']));
	$form_container->output_row($lang->ougc_awards_form_pm, $lang->ougc_awards_form_pm_d, $form->generate_text_area('pm', $ougc_awards->award_data['pm'], array('rows' => 8, 'style' => 'width:80%;')));
	$form_container->output_row($lang->ougc_awards_form_type, $lang->ougc_awards_form_type_d, $form->generate_select_box('type', array(
		0 => $lang->ougc_awards_form_type_0,
		1 => $lang->ougc_awards_form_type_1,
		2 => $lang->ougc_awards_form_type_2
	), $ougc_awards->award_data['type']));

	$form_container->end();
	$form->output_submit_wrapper(array($form->generate_submit_button($lang->ougc_awards_button_submit), $form->generate_reset_button($lang->reset)));
	$form->end();
	$page->output_footer();
}
elseif($mybb->input['action'] == 'delete')
{
	if(!($award = $ougc_awards->get_award($mybb->input['aid'])))
	{
		$ougc_awards->admin_redirect($lang->ougc_awards_error_delete, true);
	}

	if($mybb->request_method == 'post')
	{
		if(!verify_post_check($mybb->input['my_post_key'], true))
		{
			$ougc_awards->admin_redirect($lang->invalid_post_verify_key2, true);
		}

		if(isset($mybb->input['no']))
		{
			$ougc_awards->admin_redirect($lang->ougc_awards_error_delete, true);
		}

		$ougc_awards->delete_award($award['aid']);
		$ougc_awards->log_action();
		$ougc_awards->admin_redirect($lang->ougc_awards_success_delete);
	}
	$page->output_confirm_action($ougc_awards->build_url(array('action' => 'delete', 'aid' => $award['aid'], 'my_post_key' => $mybb->post_code)));
}
elseif($mybb->input['action'] == 'give')
{
	if(!($award = $ougc_awards->get_award($mybb->input['aid'])))
	{
		$awards->admin_redirect($lang->ougc_awards_error_give, true);
	}

	$page->add_breadcrumb_item($lang->ougc_awards_acp_nav, $ougc_awards->build_url());
	$page->add_breadcrumb_item(strip_tags($award['name']));
	$page->output_header($lang->ougc_awards_acp_nav);
	$page->output_nav_tabs($sub_tabs, 'ougc_awards_give');

	if($mybb->request_method == 'post')
	{
		if(!($user = $ougc_awards->get_user_by_username($mybb->input['username'])))
		{
			$page->output_inline_error($lang->ougc_awards_error_invaliduser);
		}
		elseif($ougc_awards->get_gived_award($award['aid'], $user['uid']))
		{
			$page->output_inline_error($lang->ougc_awards_error_give);
		}
		elseif(!$ougc_awards->can_edit_user($user['uid']))
		{
			$page->output_inline_error($lang->ougc_awards_error_giveperm);
		}
		else
		{
			$ougc_awards->give_award($award, $user, $mybb->input['reason']);
			$ougc_awards->log_action();
			$ougc_awards->admin_redirect($lang->ougc_awards_success_give);
		}
	}

	$form = new Form($ougc_awards->build_url(array('action' => 'give', 'aid' => $award['aid'])), 'post');
	$form_container = new FormContainer($lang->ougc_awards_tab_give_d);

	$form_container->output_row($lang->ougc_awards_form_username.' <em>*</em>', $lang->ougc_awards_form_username_d, $form->generate_text_box('username', !empty($mybb->input['username']) ? $mybb->input['username'] : ''));
	$form_container->output_row($lang->ougc_awards_form_reason, $lang->ougc_awards_form_reason_d, $form->generate_text_area('reason', !empty($mybb->input['reason']) ? $mybb->input['reason'] : '', array('rows' => 8, 'style' => 'width:80%;')));

	$form_container->end();
	$form->output_submit_wrapper(array($form->generate_submit_button($lang->ougc_awards_button_submit), $form->generate_reset_button($lang->reset)));
	$form->end();
	$page->output_footer();
}	
elseif($mybb->input['action'] == 'revoke')
{
	if(!($award = $ougc_awards->get_award($mybb->input['aid'])))
	{
		$awards->admin_redirect($lang->ougc_awards_error_revoke, true);
	}

	$page->add_breadcrumb_item($lang->ougc_awards_acp_nav, $ougc_awards->build_url());
	$page->add_breadcrumb_item(strip_tags($award['name']));
	$page->output_header($lang->ougc_awards_acp_nav);
	$page->output_nav_tabs($sub_tabs, 'ougc_awards_revoke');

	if($mybb->request_method == 'post')
	{
		if(!($user = $ougc_awards->get_user_by_username($mybb->input['username'])))
		{
			$page->output_inline_error($lang->ougc_awards_error_invaliduser);
		}
		elseif(!$ougc_awards->get_gived_award($award['aid'], $user['uid']))
		{
			$page->output_inline_error($lang->ougc_awards_error_notgive);
		}
		elseif(!$ougc_awards->can_edit_user($user['uid']))
		{
			$page->output_inline_error($lang->ougc_awards_error_giveperm);
		}
		else
		{
			$ougc_awards->revoke_award($award['aid'], $user['uid']);
			$ougc_awards->log_action();
			$ougc_awards->admin_redirect($lang->ougc_awards_success_revoke);
		}
	}

	$form = new Form("index.php?module=user-ougc_awards&amp;action=revoke&amp;aid={$mybb->input['aid']}", "post");
	$form_container = new FormContainer($sub_tabs['ougc_awards_revoke']['description']);

	$form_container->output_row($lang->ougc_awards_form_username.' <em>*</em>', $lang->ougc_awards_form_username_d, $form->generate_text_box('username', !empty($mybb->input['username']) ? $mybb->input['username'] : ''));

	$form_container->end();
	$form->output_submit_wrapper(array($form->generate_submit_button($lang->ougc_awards_button_submit), $form->generate_reset_button($lang->reset)));
	$form->end();
	$page->output_footer();
}
elseif($mybb->input['action'] == 'users')
{
	if(!($award = $ougc_awards->get_award($mybb->input['aid'])))
	{
		$ougc_awards->admin_redirect();
	}

	$page->add_breadcrumb_item($lang->ougc_awards_acp_nav, $ougc_awards->build_url());
	$page->add_breadcrumb_item(strip_tags($award['name']));
	$page->output_header($lang->ougc_awards_acp_nav);
	$page->output_nav_tabs($sub_tabs, 'ougc_awards_users');

	$table = new Table;
	$table->construct_header($lang->ougc_awards_form_username, array('width' => '15%'));
	$table->construct_header($lang->ougc_awards_form_reason, array('width' => '45%'));
	$table->construct_header($lang->ougc_awards_users_date, array('width' => '25%', 'class' => 'align_center'));
	$table->construct_header($lang->ougc_awards_view_actions, array('width' => '15%', 'class' => 'align_center'));

	$limit = 20;
	$mybb->input['page'] = (int)$mybb->input['page'];
	if($mybb->input['page'] && $mybb->input['page'] > 0)
	{
		$start = ($mybb->input['page'] - 1)*$limit;
	}
	else
	{
		$start = 0;
		$mybb->input['page'] = 1;
	}

	$query = $db->simple_select('ougc_awards_users', '*', 'aid=\''.(int)$award['aid'].'\'', array('order_by' => 'date', 'order_dir' => 'desc', 'limit_start' => $start, 'limit' => $limit));

	if(!$db->num_rows($query))
	{
		$table->construct_cell('<div align="center">'.$lang->ougc_awards_users_empty.'</div>', array('colspan' => 6));
		$table->construct_row();
		$table->output($sub_tabs['ougc_awards_users']['description']);
	}
	else
	{
		while($gived = $db->fetch_array($query))
		{
			$user = get_user($gived['uid']);
			$user['username'] = format_name(htmlspecialchars_uni($user['username']), $user['usergroup'], $user['displaygroup']);
			$table->construct_cell("<a href=\"index.php?module=user-users&action=edit&uid={$user['uid']}\">{$user['username']}</a>");
			$table->construct_cell(htmlspecialchars_uni($gived['reason']));
			$table->construct_cell($lang->sprintf($lang->ougc_awards_users_time, my_date($mybb->settings['dateformat'], intval($gived['date'])), my_date($mybb->settings['timeformat'], intval($gived['date']))), array('class' => 'align_center'));
			$table->construct_cell("<a href=\"index.php?module=user-ougc_awards&amp;action=user&amp;aid={$gived['aid']}&amp;uid={$user['uid']}\">{$lang->ougc_awards_tab_edit}</a>", array('class' => 'align_center'));

			$table->construct_row();
		}

		$table->output($sub_tabs['ougc_awards_users']['description']);

		$query = $db->simple_select('ougc_awards_users', 'COUNT(uid) AS users', 'aid=\''.(int)$award['aid'].'\'');
		$giveds = (int)$db->fetch_field($query, 'users');

		echo draw_admin_pagination($mybb->input['page'], $limit, $giveds, $view['url'].'index.php?module=user-ougc_awards&amp;action=users&amp;aid='.$award['aid']);
	}
	$page->output_footer();
}
elseif($mybb->input['action'] == 'user')
{
	if(!($award = $ougc_awards->get_award($mybb->input['aid'])))
	{
		$ougc_awards->admin_redirect($lang->ougc_awards_error_edit, true);
	}

	$ougc_awards->set_url(array('action' => 'users', 'aid' => $award['aid']));
	if(!($gived = $ougc_awards->get_gived_award($award['aid'], $mybb->input['uid'])))
	{
		$ougc_awards->admin_redirect($lang->ougc_awards_error_edit, true);
	}

	$page->add_breadcrumb_item($lang->ougc_awards_acp_nav, $ougc_awards->build_url());
	$page->output_header($lang->ougc_awards_acp_nav);
	$page->output_nav_tabs($sub_tabs, 'ougc_awards_edit_user');

	if($mybb->request_method == 'post')
	{
		$ougc_awards->update_gived($gived['gid'], array(
			'date' => $mybb->input['date'],
			'reason' => $mybb->input['reason']
		));

		$ougc_awards->log_action();
		$ougc_awards->admin_redirect($lang->ougc_awards_success_edit);
	}

	$form = new Form('index.php?module=user-ougc_awards&amp;action=user&aid='.$mybb->input['aid'].'&uid='.$mybb->input['uid'], 'post');
	$form_container = new FormContainer($lang->ougc_awards_tab_edit_user_d);

	$form_container->output_row($lang->ougc_awards_form_reason, $lang->ougc_awards_form_reason_d, $form->generate_text_area('reason', isset($mybb->input['reason']) ? $mybb->input['reason'] : $gived['reason'], array('rows' => 8, 'style' => 'width:80%;')));
	$form_container->output_row($lang->ougc_awards_users_timestamp, $lang->ougc_awards_users_timestamp_d, $form->generate_text_box('date', isset($mybb->input['date']) ? (int)$mybb->input['date'] : intval($gived['date'])));

	$form_container->end();
	$form->output_submit_wrapper(array($form->generate_submit_button($lang->ougc_awards_button_submit), $form->generate_reset_button($lang->reset)));
	$form->end();
	$page->output_footer();
}
else
{
	$page->add_breadcrumb_item($lang->ougc_awards_acp_nav, $ougc_awards->build_url());
	$page->output_header($lang->ougc_awards_acp_nav);
	$page->output_nav_tabs($sub_tabs, 'ougc_awards_view');

	$table = new Table;
	$table->construct_header($lang->ougc_awards_view_image, array('width' => '1%'));
	$table->construct_header($lang->ougc_awards_form_name, array('width' => '19%'));
	$table->construct_header($lang->ougc_awards_form_desc, array('width' => '45%'));
	$table->construct_header($lang->ougc_awards_form_visible, array('width' => '10%', 'class' => 'align_center'));
	$table->construct_header($lang->ougc_awards_view_actions, array('width' => '15%', 'class' => 'align_center'));

	$limit = 20;
	$mybb->input['page'] = (int)$mybb->input['page'];
	if($mybb->input['page'] && $mybb->input['page'] > 0)
	{
		$start = ($mybb->input['page'] - 1)*$limit;
	}
	else
	{
		$start = 0;
		$mybb->input['page'] = 1;
	}

	$query = $db->simple_select('ougc_awards', '*', '', array('limit_start' => $start, 'limit' => $limit));
	
	if(!$db->num_rows($query))
	{
		$table->construct_cell('<div align="center">'.$lang->ougc_awards_view_empty.'</div>', array('colspan' => 6));
		$table->construct_row();
		$table->output($lang->ougc_awards_tab_view_d);
	}
	else
	{
		while($award = $db->fetch_array($query))
		{
			$table->construct_cell('<img src="'.$ougc_awards->get_icon($award['image'], $award['aid']).'" />', array('class' => 'align_center'));
			$table->construct_cell($award['name']);
			$table->construct_cell($award['description']);
			$table->construct_cell('<img src="styles/default/images/icons/bullet_o'.(!$award['visible'] ? 'ff' : 'n').'.gif" alt="" title="'.(!$award['visible'] ? $lang->ougc_awards_form_hidden : $lang->ougc_awards_form_visible).'" />', array('class' => 'align_center'));

			$popup = new PopupMenu("award_{$award['aid']}", $lang->options);
			$popup->add_item($lang->ougc_awards_tab_give, "index.php?module=user-ougc_awards&amp;action=give&amp;aid={$award['aid']}");
			$popup->add_item($lang->ougc_awards_tab_revoke, "index.php?module=user-ougc_awards&amp;action=revoke&amp;aid={$award['aid']}");
			$popup->add_item($lang->ougc_awards_tab_users, "index.php?module=user-ougc_awards&amp;action=users&amp;aid={$award['aid']}");
			$popup->add_item($lang->ougc_awards_tab_edit, "index.php?module=user-ougc_awards&amp;action=edit&amp;aid={$award['aid']}");
			$popup->add_item($lang->ougc_awards_tab_delete, "index.php?module=user-ougc_awards&amp;action=delete&amp;aid={$award['aid']}");
			$table->construct_cell($popup->fetch(), array('class' => 'align_center'));

			$table->construct_row();
		}
		$table->output($lang->ougc_awards_tab_view_d);

		$query = $db->simple_select('ougc_awards', 'COUNT(aid) AS awards');
		$awards = (int)$db->fetch_field($query, 'awards');

		echo draw_admin_pagination($mybb->input['page'], $limit, $awards, 'index.php?module=user-ougc_awards');
	}
	$page->output_footer();
}