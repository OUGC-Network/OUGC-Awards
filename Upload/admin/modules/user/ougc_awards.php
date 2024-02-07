<?php

/***************************************************************************
 *
 *    OUGC Awards plugin (/admin/modules/user/ougc_awards.php)
 *    Author: Omar Gonzalez
 *    Copyright: © 2012-2020 Omar Gonzalez
 *
 *    Website: https://ougc.network
 *
 *    Adds a powerful awards system to you community.
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

global $awards, $lang, $mybb, $db, $templates;
global $run_module, $page, $parser;

$awards->lang_load();

$sub_tabs['ougc_awards_categories'] = array(
    'title' => $lang->ougc_awards_tab_categories,
    'link' => 'index.php?module=user-ougc_awards',
    'description' => $lang->ougc_awards_tab_categories_desc
);

$aid = $awards->get_input('aid', 1);
$cid = $awards->get_input('cid', 1);
$tid = $awards->get_input('tid', 1);

$category = $awards->get_category($cid);
$category_url = $awards->build_url(array('view' => 'category', 'cid' => $cid));

$sub_tabs['ougc_awards_tasks'] = array(
    'title' => $lang->ougc_awards_tab_tasks,
    'link' => 'index.php?module=user-ougc_awards&amp;view=tasks',
    'description' => $lang->ougc_awards_tab_tasks_desc
);

if ($awards->get_input('view') == 'tasks'):

    $awards->set_url('view=tasks');

    $lang->load($run_module . '_group_promotions', false, true);

    $sub_tabs['ougc_awards_add'] = array(
        'title' => $lang->ougc_awards_tab_add,
        'link' => $awards->build_url('action=add'),
        'description' => $lang->ougc_awards_tab_add_d,
        'align' => 'right'
    );

    switch ($awards->get_input('action')) {
        case 'edit':
            $sub_tabs['ougc_awards_edit'] = array(
                'title' => $lang->ougc_awards_tab_edit,
                'link' => 'index.php?module=user-ougc_awards&amp;view=tasks&amp;action=edit&amp;tid=' . $tid,
                'description' => $lang->ougc_awards_tab_editc_desc,
                'align' => 'right'
            );
            break;
    }

    $sub_tabs['ougc_awards_tasks_logs'] = array(
        'title' => $lang->ougc_awards_tab_tasks_logs,
        'link' => 'index.php?module=user-ougc_awards&amp;view=tasks&amp;action=logs',
        'description' => $lang->ougc_awards_tab_tasks_logs_desc
    );

    if ($awards->get_input('action') == 'add' || $awards->get_input('action') == 'edit') {
        $page->add_breadcrumb_item($lang->ougc_awards_acp_nav, $awards->build_url());

        if ($category) {
            $page->add_breadcrumb_item($category['name'], $category_url);
        }

        if (!($add = $awards->get_input('action') == 'add')) {
            if (!($task = $awards->get_task($awards->get_input('tid', 1)))) {
                $awards->admin_redirect($lang->ougc_awards_error_invalitask, true);
            }

            $page->add_breadcrumb_item(strip_tags($task['name']));

            foreach (array('requirements', 'usergroups', 'give', 'revoke', 'previousawards', 'profilefields') as $k) {
                //_dump($task, $task[$k]);
                $task[$k] = explode(',', $task[$k]);
            }
        }

        $mergeinput = array();
        foreach (
            array(
                'name',
                'description',
                'disporder',
                'active',
                'logging',
                'requirements',
                'usergroups',
                'additionalgroups',
                'give',
                'reason',
                'thread',
                'allowmultiple',
                'revoke',
                'posts',
                'poststype',
                'threads',
                'threadstype',
                'fposts',
                'fpoststype',
                'fpostsforums',
                'fthreads',
                'fthreadstype',
                'fthreadsforums',
                'registered',
                'registeredtype',
                'online',
                'onlinetype',
                'reputation',
                'reputationtype',
                'referrals',
                'referralstype',
                'warnings',
                'warningstype',
                'newpoints',
                'newpointstype',
                'previousawards',
                'profilefields',
                'mydownloads',
                'mydownloadstype',
                'myarcadechampions',
                'myarcadechampionstype',
                'myarcadescores',
                'myarcadescorestype',
                'ougc_customrep_r',
                'ougc_customreptype_r',
                'ougc_customrepids_r',
                'ougc_customrep_g',
                'ougc_customreptype_g',
                'ougc_customrepids_g'
            ) as $key
        ) {
            $mergeinput[$key] = isset($mybb->input[$key]) ? $mybb->input[$key] : ($add ? '' : $task[$key]);
        }
        $mybb->input = array_merge($mybb->input, $mergeinput);

        $page->output_header($lang->ougc_awards_acp_nav);
        $page->output_nav_tabs($sub_tabs, $add ? 'ougc_awards_add' : 'ougc_awards_edit');

        if ($mybb->request_method == 'post') {
            $errors = array();

            if (!$awards->get_input('name') || my_strlen($awards->get_input('name')) > 100) {
                $errors[] = $lang->ougc_awards_error_invalidname;
            }

            if (my_strlen($mybb->input['description']) > 255) {
                $errors[] = $lang->ougc_awards_error_invaliddesscription;
            }

            if (my_strlen($mybb->input['image']) > 255) {
                $errors[] = $lang->ougc_awards_error_invalidimage;
            }

            if ($awards->get_input('thread')) {
                if (!($thread = $awards->get_thread_by_url($awards->get_input('thread')))) {
                    $errors[] = $lang->ougc_awards_error_invalidthread;
                }
            }

            if (empty($errors)) {
                $method = $add ? 'insert_task' : 'update_task';
                $lang_val = $add ? 'ougc_awards_success_add' : 'ougc_awards_success_edit';

                $awards->{$method}(array(
                    'name' => $awards->get_input('name'),
                    'description' => $awards->get_input('description'),
                    'active' => $awards->get_input('active', 1),
                    'logging' => $awards->get_input('logging', 1),
                    'requirements' => $awards->get_input('requirements', 2),
                    'usergroups' => $awards->get_input('usergroups', 2),
                    'additionalgroups' => $awards->get_input('additionalgroups', 1),
                    'give' => $awards->get_input('give', 2),
                    'reason' => $awards->get_input('reason'),
                    'thread' => !empty($thread['tid']) ? (int)$thread['tid'] : 0,
                    'allowmultiple' => $awards->get_input('allowmultiple', 1),
                    'revoke' => $awards->get_input('revoke', 2),
                    'disporder' => $awards->get_input('disporder', 1),
                    'posts' => $awards->get_input('posts', 1),
                    'poststype' => $awards->get_input('poststype'),
                    'threads' => $awards->get_input('threads', 1),
                    'threadstype' => $awards->get_input('threadstype'),
                    'fposts' => $awards->get_input('fposts', 1),
                    'fpoststype' => $awards->get_input('fpoststype'),
                    'fpostsforums' => $awards->get_input('fpostsforums', 1),
                    'fthreads' => $awards->get_input('fthreads', 1),
                    'fthreadstype' => $awards->get_input('fthreadstype'),
                    'fthreadsforums' => $awards->get_input('fthreadsforums', 1),
                    'registered' => $awards->get_input('registered', 1),
                    'registeredtype' => $awards->get_input('registeredtype'),
                    'online' => $awards->get_input('online', 1),
                    'onlinetype' => $awards->get_input('onlinetype'),
                    'reputation' => $awards->get_input('reputation', 1),
                    'reputationtype' => $awards->get_input('reputationtype'),
                    'referrals' => $awards->get_input('referrals', 1),
                    'referralstype' => $awards->get_input('referralstype'),
                    'warnings' => $awards->get_input('warnings', 1),
                    'warningstype' => $awards->get_input('warningstype'),
                    'newpoints' => $awards->get_input('newpoints', 1),
                    'newpointstype' => $awards->get_input('newpointstype'),
                    'previousawards' => $awards->get_input('previousawards', 2),
                    'profilefields' => $awards->get_input('profilefields', 2),
                    'mydownloads' => $awards->get_input('mydownloads', 1),
                    'mydownloadstype' => $awards->get_input('mydownloadstype'),
                    'myarcadechampions' => $awards->get_input('myarcadechampions', 1),
                    'myarcadechampionstype' => $awards->get_input('myarcadechampionstype'),
                    'myarcadescores' => $awards->get_input('myarcadescores', 1),
                    'myarcadescorestype' => $awards->get_input('myarcadescorestype'),
                    'ougc_customrep_r' => $awards->get_input('ougc_customrep_r', 1),
                    'ougc_customreptype_r' => $awards->get_input('ougc_customreptype_r'),
                    'ougc_customrepids_r' => $awards->get_input('ougc_customrepids_r', 1),
                    'ougc_customrep_g' => $awards->get_input('ougc_customrep_g', 1),
                    'ougc_customreptype_g' => $awards->get_input('ougc_customreptype_g'),
                    'ougc_customrepids_g' => $awards->get_input('ougc_customrepids_g', 1)
                ), $awards->get_input('tid', 1));

                $awards->update_cache();
                $awards->log_action();
                $awards->admin_redirect($lang->{$lang_val});
            } else {
                $page->output_inline_error($errors);
            }
        }

        $form = new Form(
            $awards->build_url(($add ? 'action=add' : array('action' => 'edit', 'tid' => $task['tid']))),
            'post'
        );
        $form_container = new FormContainer(($add ? $lang->ougc_awards_form_add : $lang->ougc_awards_tab_editt_desc));

        $form_container->output_row(
            $lang->ougc_awards_form_name . ' <em>*</em>',
            $lang->ougc_awards_form_name_d,
            $form->generate_text_box('name', $mybb->input['name'])
        );
        $form_container->output_row(
            $lang->ougc_awards_form_desc,
            $lang->ougc_awards_form_desc_d,
            $form->generate_text_box('description', $mybb->input['description'])
        );
        $form_container->output_row(
            $lang->ougc_awards_form_active,
            $lang->ougc_awards_form_active_desc,
            $form->generate_yes_no_radio('active', (int)$mybb->input['active'])
        );
        $form_container->output_row(
            $lang->ougc_awards_form_logging,
            $lang->ougc_awards_form_logging_desc,
            $form->generate_yes_no_radio('logging', (int)$mybb->input['logging'])
        );
        $form_container->output_row(
            $lang->ougc_awards_form_requirements,
            $lang->ougc_awards_form_requirements_desc,
            $form->generate_select_box('requirements[]', array(
                'usergroups' => $lang->primary_user_group,
                'posts' => $lang->post_count,
                'threads' => $lang->thread_count,
                'fposts' => $lang->ougc_awards_form_requirements_fposts,
                'fthreads' => $lang->ougc_awards_form_requirements_fthreads,
                'registered' => $lang->time_registered,
                'online' => $lang->time_online,
                'reputation' => $lang->reputation,
                'referrals' => $lang->referrals,
                'warnings' => $lang->warning_points,
                'newpoints' => $lang->ougc_awards_form_requirements_newpoints,
                'previousawards' => $lang->ougc_awards_form_requirements_previousawards,
                'profilefields' => $lang->ougc_awards_form_requirements_profilefields,
                'mydownloads' => $lang->ougc_awards_form_requirements_mydownloads,
                //'myarcadechampions'	=> $lang->ougc_awards_form_requirements_myarcadechampions,
                'myarcadescores' => $lang->ougc_awards_form_requirements_myarcadescores,
                'ougc_customrep_r' => $lang->ougc_awards_form_requirements_ougc_customrep_r,
                'ougc_customrep_g' => $lang->ougc_awards_form_requirements_ougc_customrep_g
            ), $awards->get_input('requirements', 2), array('multiple' => true, 'size' => 5))
        );
        $form_container->output_row(
            $lang->ougc_awards_form_give,
            $lang->ougc_awards_form_give_desc,
            $awards->generate_awards_select('give[]', $mybb->input['give'], array('multiple' => true))
        );
        $form_container->output_row(
            $lang->ougc_awards_form_reason,
            $lang->ougc_awards_form_reason_d,
            $form->generate_text_area(
                'reason',
                (string)$mybb->input['reason'],
                array('rows' => 8, 'style' => 'width:80%;')
            )
        );
        $form_container->output_row(
            $lang->ougc_awards_form_thread,
            $lang->ougc_awards_form_thread_d,
            $form->generate_text_box(
                'thread',
                isset($mybb->input['thread']) ? $awards->get_input('thread') : ($task['thread'] ? get_thread_link(
                    $task['thread']
                ) : '')
            )
        );
        $form_container->output_row(
            $lang->ougc_awards_form_allowmultiple,
            $lang->ougc_awards_form_allowmultiple_desc,
            $form->generate_yes_no_radio('allowmultiple', (int)$mybb->input['allowmultiple'])
        );
        $form_container->output_row(
            $lang->ougc_awards_form_revoke,
            $lang->ougc_awards_form_revoke_desc,
            $awards->generate_awards_select('revoke[]', $mybb->input['revoke'], array('multiple' => true))
        );
        $form_container->output_row(
            $lang->ougc_awards_form_order,
            $lang->ougc_awards_form_order_d,
            $form->generate_text_box(
                'disporder',
                (int)$mybb->input['disporder'],
                array('style' => 'text-align: center; width: 30px;" maxlength="5')
            )
        );
        $form_container->end();

        $options_type = array(
            '>' => $lang->greater_than,
            '>=' => $lang->greater_than_or_equal_to,
            '=' => $lang->equal_to,
            '<=' => $lang->less_than_or_equal_to,
            '<' => $lang->less_than
        );
        $options_time = array(
            'hours' => $lang->hours,
            'days' => $lang->days,
            'weeks' => $lang->weeks,
            'months' => $lang->months,
            'years' => $lang->years
        );

        $form_container = new FormContainer($lang->ougc_awards_form_requirements);
        $form_container->output_row(
            $lang->ougc_awards_form_usergroups,
            $lang->ougc_awards_form_usergroups_desc,
            $form->generate_group_select('usergroups[]', $mybb->input['usergroups'], array('multiple' => true))
        );
        $form_container->output_row(
            $lang->ougc_awards_form_additionalgroups,
            $lang->ougc_awards_form_additionalgroups_desc,
            $form->generate_yes_no_radio('additionalgroups', $mybb->input['additionalgroups'])
        );
        $form_container->output_row(
            $lang->post_count,
            $lang->post_count_desc,
            $form->generate_numeric_field('posts', $awards->get_input('posts', 1), array('id' => 'posts', 'min' => 0)
            ) . ' ' . $form->generate_select_box(
                'poststype',
                $options_type,
                $awards->get_input('poststype'),
                array('id' => 'poststype')
            ),
            'posts'
        );
        $form_container->output_row(
            $lang->thread_count,
            $lang->thread_count_desc,
            $form->generate_numeric_field(
                'threads',
                $awards->get_input('threads', 1),
                array('id' => 'threads', 'min' => 0)
            ) . ' ' . $form->generate_select_box(
                'threadstype',
                $options_type,
                $awards->get_input('threadstype'),
                array('id' => 'threadstype')
            ),
            'threads'
        );
        $form_container->output_row(
            $lang->ougc_awards_form_requirements_fposts,
            $lang->ougc_awards_form_requirements_fposts_desc,
            $form->generate_numeric_field('fposts', $awards->get_input('fposts', 1), array('id' => 'fposts', 'min' => 0)
            ) . ' ' . $form->generate_select_box(
                'fpoststype',
                $options_type,
                $awards->get_input('fpoststype'),
                array('id' => 'fpoststype')
            ) . '' . $form->generate_forum_select('fpostsforums', $awards->get_input('fpostsforums', 1)),
            'fposts'
        );
        $form_container->output_row(
            $lang->ougc_awards_form_requirements_fthreads,
            $lang->ougc_awards_form_requirements_fthreads_desc,
            $form->generate_numeric_field(
                'fthreads',
                $awards->get_input('fthreads', 1),
                array('id' => 'fthreads', 'min' => 0)
            ) . ' ' . $form->generate_select_box(
                'fthreadstype',
                $options_type,
                $awards->get_input('fthreadstype'),
                array('id' => 'fthreadstype')
            ) . '' . $form->generate_forum_select('fthreadsforums', $awards->get_input('fthreadsforums', 1)),
            'fthreads'
        );
        $form_container->output_row(
            $lang->time_registered,
            $lang->time_registered_desc,
            $form->generate_numeric_field(
                'registered',
                $awards->get_input('registered', 1),
                array('id' => 'registered', 'min' => 0)
            ) . ' ' . $form->generate_select_box(
                'registeredtype',
                $options_time,
                $awards->get_input('registeredtype'),
                array('id' => 'registeredtype')
            ),
            'registered'
        );
        $form_container->output_row(
            $lang->time_online,
            $lang->time_online_desc,
            $form->generate_numeric_field('online', $awards->get_input('online', 1), array('id' => 'online', 'min' => 0)
            ) . ' ' . $form->generate_select_box(
                'onlinetype',
                $options_time,
                $awards->get_input('onlinetype'),
                array('id' => 'onlinetype')
            ),
            'online'
        );
        $form_container->output_row(
            $lang->reputation_count,
            $lang->reputation_count_desc,
            $form->generate_numeric_field(
                'reputation',
                $awards->get_input('reputation', 1),
                array('id' => 'reputation', 'min' => 0)
            ) . '' . $form->generate_select_box(
                'reputationtype',
                $options_type,
                $awards->get_input('reputationtype'),
                array('id' => 'reputationtype')
            ),
            'reputation'
        );
        $form_container->output_row(
            $lang->referral_count,
            $lang->referral_count_desc,
            $form->generate_numeric_field(
                'referrals',
                $awards->get_input('referrals', 1),
                array('id' => 'referrals', 'min' => 0)
            ) . '' . $form->generate_select_box(
                'referralstype',
                $options_type,
                $awards->get_input('referralstype'),
                array('id' => 'referralstype')
            ),
            'referrals'
        );
        $form_container->output_row(
            $lang->warning_points,
            $lang->warning_points_desc,
            $form->generate_numeric_field(
                'warnings',
                $awards->get_input('warnings', 1),
                array('id' => 'warnings', 'min' => 0)
            ) . '' . $form->generate_select_box(
                'warningstype',
                $options_type,
                $awards->get_input('warningstype'),
                array('id' => 'warningstype')
            ),
            'warnings'
        );
        $form_container->output_row(
            $lang->ougc_awards_form_requirements_newpoints,
            $lang->ougc_awards_form_requirements_newpoints_desc,
            $form->generate_numeric_field(
                'newpoints',
                $awards->get_input('newpoints', 1),
                array('id' => 'newpoints', 'min' => 0)
            ) . ' ' . $form->generate_select_box(
                'newpointstype',
                $options_type,
                $awards->get_input('newpointstype'),
                array('id' => 'newpointstype')
            ),
            'newpoints'
        );
        $form_container->output_row(
            $lang->ougc_awards_form_requirements_previousawards,
            $lang->ougc_awards_form_requirements_previousawards_desc,
            $awards->generate_awards_select(
                'previousawards[]',
                $mybb->input['previousawards'],
                array('multiple' => true)
            )
        );
        $form_container->output_row(
            $lang->ougc_awards_form_requirements_profilefields,
            $lang->ougc_awards_form_requirements_profilefields_desc,
            $awards->generate_profilefields_select(
                'profilefields[]',
                $awards->get_input('profilefields', 2),
                array('multiple' => true, 'id' => 'profilefields')
            ),
            'profilefields'
        );
        $form_container->output_row(
            $lang->ougc_awards_form_requirements_mydownloads,
            $lang->ougc_awards_form_requirements_mydownloads_desc,
            $form->generate_numeric_field(
                'mydownloads',
                $awards->get_input('mydownloads', 1),
                array('id' => 'mydownloads', 'min' => 0)
            ) . ' ' . $form->generate_select_box(
                'mydownloadstype',
                $options_type,
                $awards->get_input('mydownloadstype'),
                array('id' => 'mydownloadstype')
            ),
            'mydownloads'
        );
        //$form_container->output_row($lang->ougc_awards_form_requirements_myarcadechampions, $lang->ougc_awards_form_requirements_myarcadechampions_desc, $form->generate_numeric_field('myarcadechampions', $awards->get_input('myarcadechampions', 1), array('id' => 'myarcadechampions', 'min' => 0)).' '.$form->generate_select_box('myarcadechampionstype', $options_type, $awards->get_input('myarcadechampionstype'), array('id' => 'myarcadechampionstype')), 'myarcadechampions');
        $form_container->output_row(
            $lang->ougc_awards_form_requirements_myarcadescores,
            $lang->ougc_awards_form_requirements_myarcadescores_desc,
            $form->generate_numeric_field(
                'myarcadescores',
                $awards->get_input('myarcadescores', 1),
                array('id' => 'myarcadescores', 'min' => 0)
            ) . ' ' . $form->generate_select_box(
                'myarcadescorestype',
                $options_type,
                $awards->get_input('myarcadescorestype'),
                array('id' => 'myarcadescorestype')
            ),
            'myarcadescores'
        );
        if ($reputation_select_r = $awards->generate_ougc_custom_reputation_select(
            'ougc_customrepids_r',
            $awards->get_input('ougc_customrepids_r', 1)
        )) {
            $form_container->output_row(
                $lang->ougc_awards_form_requirements_ougc_customrep_r,
                $lang->ougc_awards_form_requirements_ougc_customrep_r_desc,
                $form->generate_numeric_field(
                    'ougc_customrep_r',
                    $awards->get_input('ougc_customrep_r', 1),
                    array('id' => 'ougc_customrep_r', 'min' => 0)
                ) . ' ' . $form->generate_select_box(
                    'ougc_customreptype_r',
                    $options_type,
                    $awards->get_input('ougc_customreptype_r'),
                    array('id' => 'ougc_customreptype_r')
                ) . '' . $reputation_select_r,
                'ougc_customrep_r'
            );
        }
        if ($reputation_select_g = $awards->generate_ougc_custom_reputation_select(
            'ougc_customrepids_g',
            $awards->get_input('ougc_customrepids_g', 1)
        )) {
            $form_container->output_row(
                $lang->ougc_awards_form_requirements_ougc_customrep_g,
                $lang->ougc_awards_form_requirements_ougc_customrep_g_desc,
                $form->generate_numeric_field(
                    'ougc_customrep_g',
                    $awards->get_input('ougc_customrep_g', 1),
                    array('id' => 'ougc_customrep_g', 'min' => 0)
                ) . ' ' . $form->generate_select_box(
                    'ougc_customreptype_g',
                    $options_type,
                    $awards->get_input('ougc_customreptype_g'),
                    array('id' => 'ougc_customreptype_g')
                ) . '' . $reputation_select_g,
                'ougc_customrep_g'
            );
        }
        $form_container->end();

        $form->output_submit_wrapper(
            array(
                $form->generate_submit_button($lang->ougc_awards_button_submit),
                $form->generate_reset_button($lang->reset)
            )
        );
        $form->end();
        $page->output_footer();
    } elseif ($awards->get_input('action') == 'delete') {
        if (!($task = $awards->get_task($awards->get_input('tid', 1)))) {
            $awards->admin_redirect($lang->ougc_awards_error_invalidtask, true);
        }

        if ($mybb->request_method == 'post') {
            if (!verify_post_check($mybb->input['my_post_key'], true)) {
                $awards->admin_redirect($lang->invalid_post_verify_key2, true);
            }

            !isset($mybb->input['no']) or $awards->admin_redirect();

            $awards->delete_task($task['tid']);
            $awards->update_cache();
            $awards->log_action();
            $awards->admin_redirect($lang->ougc_awards_success_delete);
        }
        $page->output_confirm_action(
            $awards->build_url(array('action' => 'delete', 'tid' => $task['tid'], 'my_post_key' => $mybb->post_code))
        );
    } elseif ($awards->get_input('action') == 'logs') {
        $page->add_breadcrumb_item($lang->ougc_awards_acp_nav, $awards->build_url());
        $page->add_breadcrumb_item($sub_tabs['ougc_awards_tasks_logs']['title']);
        $page->output_header($lang->ougc_awards_acp_nav);
        $page->output_nav_tabs($sub_tabs, 'ougc_awards_tasks_logs');

        $table = new Table();
        $table->construct_header($lang->ougc_awards_logs_task, array('width' => '20%'));
        $table->construct_header($lang->ougc_awards_logs_user, array('width' => '40%'));
        $table->construct_header($lang->ougc_awards_logs_received, array('width' => '15%'));
        $table->construct_header($lang->ougc_awards_logs_revoked, array('width' => '15%'));
        $table->construct_header($lang->ougc_awards_logs_date, array('width' => '10%', 'class' => 'align_center'));

        if ($awards->get_input('page', 1) > 0) {
            $start = ($awards->get_input('page', 1) - 1) * $awards->query_limit;
        } else {
            $start = 0;
            $mybb->input['page'] = 1;
        }

        $query = $db->simple_select(
            'ougc_awards_tasks_logs',
            '*',
            '',
            array('limit_start' => $start, 'limit' => $awards->query_limit, 'order_by' => 'date', 'order_dir' => 'desc')
        );

        if (!$db->num_rows($query)) {
            $table->construct_cell(
                '<div align="center">' . $lang->ougc_awards_logs_empty . '</div>',
                array('colspan' => 5)
            );
            $table->construct_row();
            $table->output($sub_tabs['ougc_awards_tasks_logs']['description']);
        } else {
            if ($mybb->request_method == 'post') {
                $db->delete_query('ougc_awards_tasks_logs');

                $awards->set_url('action=logs');
                $awards->update_cache();
                $awards->admin_redirect($lang->ougc_awards_success_prunelogs);
            }

            $form = new Form($awards->build_url('action=logs'), 'post');

            $query2 = $db->simple_select('ougc_awards_tasks_logs', 'COUNT(lid) AS logs');
            $logscount = (int)$db->fetch_field($query2, 'logs');

            echo draw_admin_pagination(
                $mybb->input['page'],
                $awards->query_limit,
                $logscount,
                'index.php?module=user-ougc_awards&amp;view=tasks&amp;action=logs'
            );

            while ($log = $db->fetch_array($query)) {
                $task = $awards->get_task($log['tid']);
                $user = $awards->get_user($log['uid']);

                $gave_list = $revoked_list = array();
                if (!empty($log['gave'])) {
                    foreach (explode(',', $log['gave']) as $aid) {
                        $award = $awards->get_award($aid);
                        $gave_list[] = $award['name'];
                    }
                }
                if (!empty($log['revoked'])) {
                    foreach (explode(',', $log['revoked']) as $aid) {
                        $award = $awards->get_award($aid);
                        $revoked_list[] = $award['name'];
                    }
                }

                !empty($gave_list) or $gave_list = array('<i>' . $lang->ougc_awards_logs_none . '</i>');
                !empty($revoked_list) or $revoked_list = array('<i>' . $lang->ougc_awards_logs_none . '</i>');

                $table->construct_cell(
                    '<a href="' . $awards->build_url(array('action' => 'edit', 'tid' => $log['tid'])
                    ) . '">' . $task['name'] . '</a>'
                );
                $table->construct_cell(
                    build_profile_link(htmlspecialchars_uni($user['username']), $user['uid'], '_blank')
                );
                $table->construct_cell(implode(', ', $gave_list));
                $table->construct_cell(implode(', ', $revoked_list));
                $table->construct_cell(my_date('relative', $log['date']), array('class' => 'align_center'));

                $table->construct_row();
            }
            $table->output($sub_tabs['ougc_awards_tasks_logs']['description']);

            $form->output_submit_wrapper(array($form->generate_submit_button($lang->ougc_awards_logs_prune)));
            $form->end();
        }
        $page->output_footer();
    } else {
        $page->add_breadcrumb_item($lang->ougc_awards_acp_nav, $awards->build_url());
        $page->add_breadcrumb_item($sub_tabs['ougc_awards_tasks']['title']);
        $page->output_header($lang->ougc_awards_acp_nav);
        $page->output_nav_tabs($sub_tabs, 'ougc_awards_tasks');

        $table = new Table();
        $table->construct_header($lang->ougc_awards_form_name, array('width' => '20%'));
        $table->construct_header($lang->ougc_awards_form_desc, array('width' => '45%'));
        $table->construct_header($lang->ougc_awards_form_order, array('width' => '10%', 'class' => 'align_center'));
        $table->construct_header($lang->ougc_awards_form_active, array('width' => '10%', 'class' => 'align_center'));
        $table->construct_header($lang->ougc_awards_view_actions, array('width' => '15%', 'class' => 'align_center'));

        if ($awards->get_input('page', 1) > 0) {
            $start = ($awards->get_input('page', 1) - 1) * $awards->query_limit;
        } else {
            $start = 0;
            $mybb->input['page'] = 1;
        }

        $query = $db->simple_select(
            'ougc_awards_tasks',
            '*',
            '',
            array('limit_start' => $start, 'limit' => $awards->query_limit, 'order_by' => 'disporder')
        );

        if (!$db->num_rows($query)) {
            $table->construct_cell(
                '<div align="center">' . $lang->ougc_awards_view_empty . '</div>',
                array('colspan' => 5)
            );
            $table->construct_row();
            $table->output($sub_tabs['ougc_awards_tasks']['description']);
        } else {
            if ($mybb->request_method == 'post' && $awards->get_input('action') == 'updatedisporder') {
                foreach ($mybb->input['disporder'] as $tid => $disporder) {
                    $awards->update_task(array('disporder' => $disporder), $tid);
                }
                $awards->update_cache();
                $awards->admin_redirect();
            }

            $form = new Form($awards->build_url('action=updatedisporder'), 'post');

            $query2 = $db->simple_select('ougc_awards_tasks', 'COUNT(tid) AS tasks');
            $taskcount = (int)$db->fetch_field($query2, 'tasks');

            echo draw_admin_pagination(
                $mybb->input['page'],
                $awards->query_limit,
                $taskcount,
                'index.php?module=user-ougc_awards&amp;view=tasks'
            );

            while ($task = $db->fetch_array($query)) {
                $edit_link = "index.php?module=user-ougc_awards&amp;view=tasks&amp;action=edit&amp;tid={$task['tid']}";

                $task['active'] or $task['name'] = '<i>' . $task['name'] . '</i>';

                $table->construct_cell('<a href="' . $edit_link . '">' . $task['name'] . '</a>');
                $table->construct_cell($task['description']);
                $table->construct_cell(
                    $form->generate_text_box(
                        'disporder[' . $task['tid'] . ']',
                        (int)$task['disporder'],
                        array('style' => 'text-align: center; width: 30px;')
                    ),
                    array('class' => 'align_center')
                );
                $table->construct_cell(
                    '<img src="styles/default/images/icons/bullet_o' . (!$task['active'] ? 'ff' : 'n') . ($mybb->version_code >= 1800 ? '.png' : '.gif') . '" alt="" title="' . (!$task['active'] ? $lang->ougc_awards_form_hidden : $lang->ougc_awards_form_visible) . '" />',
                    array('class' => 'align_center')
                );

                $popup = new PopupMenu("award_{$task['tid']}", $lang->options);
                $popup->add_item($lang->ougc_awards_tab_edit, $edit_link);
                $popup->add_item(
                    $lang->ougc_awards_tab_delete,
                    "index.php?module=user-ougc_awards&amp;view=tasks&amp;action=delete&amp;tid={$task['tid']}"
                );
                $table->construct_cell($popup->fetch(), array('class' => 'align_center'));

                $table->construct_row();
            }
            $table->output($lang->ougc_awards_tab_tasks_desc);

            $form->output_submit_wrapper(
                array(
                    $form->generate_submit_button($lang->ougc_awards_button_order),
                    $form->generate_reset_button($lang->reset)
                )
            );
            $form->end();
        }
        $page->output_footer();
    }

elseif ($awards->get_input('view') == 'category' && $category):

    $awards->set_url(array('view' => 'category', 'cid' => $cid));

    $sub_tabs['ougc_awards_view'] = array(
        'title' => $lang->ougc_awards_tab_view,
        'link' => $awards->build_url(),
        'description' => $lang->ougc_awards_tab_view_d
    );
    $sub_tabs['ougc_awards_add'] = array(
        'title' => $lang->ougc_awards_tab_add,
        'link' => $awards->build_url('action=add'),
        'description' => $lang->ougc_awards_tab_add_d,
        'align' => 'right'
    );

    switch ($awards->get_input('action')) {
        case 'edit':
            $sub_tabs['ougc_awards_edit'] = array(
                'title' => $lang->ougc_awards_tab_edit,
                'link' => $awards->build_url(array('action' => 'edit', 'aid' => $awards->get_input('aid', 1))),
                'description' => $lang->ougc_awards_tab_edit_d,
                'align' => 'right'
            );
            break;
        case 'give':
            $sub_tabs['ougc_awards_give'] = array(
                'title' => $lang->ougc_awards_tab_give,
                'link' => $awards->build_url(array('action' => 'give', 'aid' => $awards->get_input('aid', 1))),
                'description' => $lang->ougc_awards_tab_give_d
            );
            break;
        case 'revoke':
            $sub_tabs['ougc_awards_revoke'] = array(
                'title' => $lang->ougc_awards_tab_revoke,
                'link' => $awards->build_url(array('action' => 'revoke', 'aid' => $awards->get_input('aid', 1))),
                'description' => $lang->ougc_awards_tab_revoke_d
            );
            break;
        case 'users':
            $sub_tabs['ougc_awards_users'] = array(
                'title' => $lang->ougc_awards_tab_users,
                'link' => $awards->build_url(array('action' => 'users', 'aid' => $awards->get_input('aid', 1))),
                'description' => $lang->ougc_awards_tab_users_d
            );
            break;
        case 'owners':
            $sub_tabs['ougc_awards_owners'] = array(
                'title' => $lang->ougc_awards_tab_owners,
                'link' => $awards->build_url(array('action' => 'owners', 'aid' => $awards->get_input('aid', 1))),
                'description' => $lang->ougc_awards_tab_owners_d
            );
            break;
        case 'user':
            $sub_tabs['ougc_awards_edit_user'] = array(
                'title' => $lang->ougc_awards_tab_edit_user,
                'link' => $awards->build_url(
                    array(
                        'action' => 'user',
                        'aid' => $awards->get_input('aid', 1),
                        'uid' => $awards->get_input('uid', 1)
                    )
                ),
                'description' => $lang->ougc_awards_tab_edit_user_d
            );
            break;
    }

    if ($awards->get_input('action') == 'add' || $awards->get_input('action') == 'edit') {
        $page->add_breadcrumb_item($lang->ougc_awards_acp_nav, $awards->build_url());
        $page->add_breadcrumb_item($category['name'], $category_url);

        if (!($add = $awards->get_input('action') == 'add')) {
            if (!($award = $awards->get_award($awards->get_input('aid', 1)))) {
                $awards->admin_redirect($lang->ougc_awards_error_invalidaward, true);
            }

            $page->add_breadcrumb_item(strip_tags($award['name']));
        } else {
            $award = [
                'aid' => 0
            ];
        }

        $mergeinput = array();
        foreach (
            array(
                'name',
                'cid',
                'description',
                'image',
                'template',
                'allowrequests',
                'visible',
                'pm',
                'type',
                'disporder'
            ) as $key
        ) {
            $mergeinput[$key] = isset($mybb->input[$key]) ? $mybb->input[$key] : ($add ? '' : $award[$key]);
        }
        $mybb->input = array_merge($mybb->input, $mergeinput);

        $page->output_header($lang->ougc_awards_acp_nav);
        $page->output_nav_tabs($sub_tabs, $add ? 'ougc_awards_add' : 'ougc_awards_edit');

        if ($mybb->request_method == 'post') {
            $errors = array();
            if (!$awards->get_input('name') || my_strlen($mybb->input['name']) > 100) {
                $errors[] = $lang->ougc_awards_error_invalidname;
            }

            if (my_strlen($mybb->input['description']) > 255) {
                $errors[] = $lang->ougc_awards_error_invaliddesscription;
            }

            if (my_strlen($mybb->input['image']) > 255) {
                $errors[] = $lang->ougc_awards_error_invalidimage;
            }

            $awards->get_category($awards->get_input('cid')) or $errors[] = $lang->ougc_awards_error_invalidcategory;

            if (empty($errors)) {
                $method = $add ? 'insert_award' : 'update_award';
                $lang_val = $add ? 'ougc_awards_success_add' : 'ougc_awards_success_edit';

                $awards->{$method}(array(
                    'name' => $awards->get_input('name'),
                    'cid' => $awards->get_input('cid', 1),
                    'description' => $awards->get_input('description'),
                    'image' => $awards->get_input('image'),
                    'template' => $awards->get_input('template', 1),
                    'allowrequests' => $awards->get_input('allowrequests', 1),
                    'visible' => $awards->get_input('visible', 1),
                    'pm' => $awards->get_input('pm'),
                    'type' => $awards->get_input('type', 1),
                    'disporder' => $awards->get_input('disporder', 1),
                ), $awards->get_input('aid', 1));
                $awards->update_cache();
                $awards->log_action();
                $awards->admin_redirect($lang->{$lang_val});
            } else {
                $page->output_inline_error($errors);
            }
        }

        $form = new Form(
            $awards->build_url(($add ? 'action=add' : array('action' => 'edit', 'aid' => $award['aid']))),
            'post'
        );
        $form_container = new FormContainer($sub_tabs['ougc_awards_' . ($add ? 'add' : 'edit')]['description']);

        $form_container->output_row(
            $lang->ougc_awards_form_name . ' <em>*</em>',
            $lang->ougc_awards_form_name_d,
            $form->generate_text_box('name', $mybb->input['name'])
        );
        $form_container->output_row(
            $lang->ougc_awards_form_desc,
            $lang->ougc_awards_form_desc_d,
            $form->generate_text_box('description', $mybb->input['description'])
        );
        $form_container->output_row(
            $lang->ougc_awards_form_category,
            $lang->ougc_awards_form_category_desc,
            $awards->generate_category_select($award['aid'], $awards->get_input('cid'))
        );
        $form_container->output_row(
            $lang->ougc_awards_form_image,
            $lang->ougc_awards_form_image_d,
            $form->generate_text_box('image', $mybb->input['image'])
        );
        $form_container->output_row(
            $lang->ougc_awards_form_template,
            $lang->ougc_awards_form_template_d,
            $form->generate_select_box('template', array(
                0 => $lang->ougc_awards_form_template_0,
                1 => $lang->ougc_awards_form_template_1,
                2 => $lang->ougc_awards_form_template_2
            ), $awards->get_input('template', 1))
        );
        $form_container->output_row(
            $lang->ougc_awards_form_allowrequests,
            $lang->ougc_awards_form_allowrequests_desc,
            $form->generate_yes_no_radio('allowrequests', (int)$mybb->input['allowrequests'])
        );
        $form_container->output_row(
            $lang->ougc_awards_form_visible,
            $lang->ougc_awards_form_visible_d,
            $form->generate_yes_no_radio('visible', (int)$mybb->input['visible'])
        );
        $form_container->output_row(
            $lang->ougc_awards_form_pm,
            $lang->ougc_awards_form_pm_d,
            $form->generate_text_area('pm', $mybb->input['pm'], array('rows' => 8, 'style' => 'width:80%;'))
        );
        $form_container->output_row(
            $lang->ougc_awards_form_type,
            $lang->ougc_awards_form_type_d,
            $form->generate_select_box('type', array(
                0 => $lang->ougc_awards_form_type_0,
                1 => $lang->ougc_awards_form_type_1,
                2 => $lang->ougc_awards_form_type_2
            ), $awards->get_input('type', 1))
        );
        $form_container->output_row(
            $lang->ougc_awards_form_order,
            $lang->ougc_awards_form_order_d,
            $form->generate_text_box(
                'disporder',
                (int)$mybb->input['disporder'],
                array('style' => 'text-align: center; width: 30px;" maxlength="5')
            )
        );

        $form_container->end();
        $form->output_submit_wrapper(
            array(
                $form->generate_submit_button($lang->ougc_awards_button_submit),
                $form->generate_reset_button($lang->reset)
            )
        );
        $form->end();
        $page->output_footer();
    } elseif ($awards->get_input('action') == 'delete') {
        if (!($award = $awards->get_award($awards->get_input('aid', 1)))) {
            $awards->admin_redirect($lang->ougc_awards_error_invalidaward, true);
        }

        if ($mybb->request_method == 'post') {
            if (!verify_post_check($mybb->input['my_post_key'], true)) {
                $awards->admin_redirect($lang->invalid_post_verify_key2, true);
            }

            !isset($mybb->input['no']) or $awards->admin_redirect();

            $awards->delete_award($award['aid']);
            $awards->update_cache();
            $awards->log_action();
            $awards->admin_redirect($lang->ougc_awards_success_delete);
        }
        $page->output_confirm_action(
            $awards->build_url(array('action' => 'delete', 'aid' => $award['aid'], 'my_post_key' => $mybb->post_code))
        );
    } elseif ($awards->get_input('action') == 'give' || $awards->get_input('action') == 'revoke') {
        $page->add_breadcrumb_item($lang->ougc_awards_acp_nav, $awards->build_url());
        $page->add_breadcrumb_item($category['name'], $category_url);

        $give = ($awards->get_input('action') == 'give');
        $revoke = ($awards->get_input('action') == 'revoke');

        if (!($award = $awards->get_award($awards->get_input('aid', 1)))) {
            $awards->admin_redirect($lang->ougc_awards_error_invalidaward, true);
        }

        $page->add_breadcrumb_item(strip_tags($award['name']));
        $page->output_header($lang->ougc_awards_acp_nav);
        $page->output_nav_tabs($sub_tabs, $give ? 'ougc_awards_give' : 'ougc_awards_revoke');

        $show_gived_list = false;

        if ($mybb->request_method == 'post') {
            $errors = $users = array();
            if (!$awards->get_input('multiple', 1) || $revoke) {
                $user = $awards->get_user_by_username($mybb->input['username']);
                if (!$user) {
                    $errors[] = $lang->ougc_awards_error_invaliduser;
                } else {
                    $users[] = $user;
                }
            } else {
                foreach (explode(',', $mybb->input['username']) as $username) {
                    $user = $awards->get_user_by_username($username);
                    if (!$user) {
                        $errors[] = $lang->ougc_awards_error_invaliduser;
                        break;
                    }
                    $users[] = $user;
                }
            }

            if ($awards->get_input('thread')) {
                if (!($thread = $awards->get_thread_by_url($awards->get_input('thread')))) {
                    $errors[] = $lang->ougc_awards_error_invalidthread;
                }
            }

            /*if($give && $awards->get_gived_award($award['aid'], $user['uid']))
            {
                $errors[] = $lang->ougc_awards_error_give;
            }*/
            if ($revoke) {
                if (!$awards->get_gived_award($award['aid'], $user['uid'])) {
                    $errors[] = $lang->ougc_awards_error_revoke;
                } elseif (!$awards->get_input('gid')) {
                    $show_gived_list = true;
                } else {
                    if (!($gived = $awards->get_gived_award(null, null, $awards->get_input('gid', 1)))) {
                        $errors[] = $lang->ougc_awards_error_revoke;
                    }
                }
            }

            foreach ($users as $user) {
                if (!$awards->can_edit_user($user['uid'])) {
                    $errors[] = $lang->ougc_awards_error_giveperm;
                    break;
                }
            }

            if (empty($errors) && !$show_gived_list) {
                if ($give) {
                    foreach ($users as $user) {
                        $awards->give_award(
                            $award,
                            $user,
                            $mybb->input['reason'],
                            !empty($thread['tid']) ? $thread['tid'] : 0
                        );
                        $awards->log_action();
                    }
                } else {
                    $awards->revoke_award($gived['gid']);
                    $awards->log_action();
                }

                $lang_var = $give ? 'ougc_awards_success_give' : 'ougc_awards_success_revoke';
                $awards->admin_redirect($lang->{$lang_var});
            } elseif (!$show_gived_list) {
                $page->output_inline_error($errors);
            }
        }

        $params = array('action' => $give ? 'give' : 'revoke', 'aid' => $award['aid']);

        if ($revoke && $show_gived_list) {
            $params['username'] = $awards->get_input('username');
        }

        $form = new Form($awards->build_url($params), 'post');
        $form_container = new FormContainer($sub_tabs['ougc_awards_' . ($give ? 'give' : 'revoke')]['description']);

        if (!$revoke || !$show_gived_list) {
            $form_container->output_row(
                $lang->ougc_awards_form_username . ' <em>*</em>',
                $lang->ougc_awards_form_username_d,
                $form->generate_text_box('username', isset($mybb->input['username']) ? $mybb->input['username'] : '')
            );
        }

        if ($give) {
            $form_container->output_row(
                $lang->ougc_awards_form_reason,
                $lang->ougc_awards_form_reason_d,
                $form->generate_text_area(
                    'reason',
                    !empty($mybb->input['reason']) ? $mybb->input['reason'] : '',
                    array('rows' => 8, 'style' => 'width:80%;')
                )
            );
            $form_container->output_row(
                $lang->ougc_awards_form_thread,
                $lang->ougc_awards_form_thread_d,
                $form->generate_text_box('thread', isset($mybb->input['thread']) ? $mybb->input['thread'] : '')
            );
            $form_container->output_row(
                $lang->ougc_awards_form_multiple,
                $lang->ougc_awards_form_multiple_desc,
                $form->generate_yes_no_radio('multiple', $awards->get_input('multiple', 1))
            );
        } elseif ($revoke && $show_gived_list) {
            $form_container->output_row(
                $lang->ougc_awards_form_gived,
                $lang->ougc_awards_form_gived_desc,
                $awards->generate_gived_select($award['aid'], $user['uid'], $awards->get_input('gid'))
            );
        }

        $form_container->end();
        $form->output_submit_wrapper(
            array(
                $form->generate_submit_button($lang->ougc_awards_button_submit),
                $form->generate_reset_button($lang->reset)
            )
        );
        $form->end();
        $page->output_footer();
    } elseif ($awards->get_input('action') == 'users') {
        if (!($award = $awards->get_award($awards->get_input('aid', 1)))) {
            $awards->admin_redirect();
        }

        $page->add_breadcrumb_item($lang->ougc_awards_acp_nav, $awards->build_url());
        $page->add_breadcrumb_item($category['name'], $category_url);
        $page->add_breadcrumb_item(strip_tags($award['name']));
        $page->output_header($lang->ougc_awards_acp_nav);
        $page->output_nav_tabs($sub_tabs, 'ougc_awards_users');

        $table = new Table();
        $table->construct_header($lang->ougc_awards_form_username, array('width' => '10%'));
        $table->construct_header($lang->ougc_awards_form_reason, array('width' => '40%'));
        $table->construct_header($lang->ougc_awards_form_thread, array('width' => '20%'));
        $table->construct_header($lang->ougc_awards_users_date, array('width' => '20%', 'class' => 'align_center'));
        $table->construct_header($lang->ougc_awards_view_actions, array('width' => '10%', 'class' => 'align_center'));

        $mybb->input['page'] = $awards->get_input('page', 1);
        if ($mybb->input['page'] > 0) {
            $start = ($mybb->input['page'] - 1) * $awards->query_limit;
        } else {
            $start = 0;
            $mybb->input['page'] = 1;
        }

        $query = $db->simple_select(
            'ougc_awards_users au LEFT JOIN ' . $db->table_prefix . 'users u ON (u.uid=au.uid)',
            'au.*, u.username, u.usergroup, u.displaygroup',
            'au.aid=\'' . (int)$award['aid'] . '\'',
            array(
                'limit_start' => $start,
                'limit' => $awards->query_limit,
                'order_by' => 'au.date',
                'order_dir' => 'desc'
            )
        );

        if (!$db->num_rows($query)) {
            $table->construct_cell(
                '<div align="center">' . $lang->ougc_awards_users_empty . '</div>',
                array('colspan' => 6)
            );
            $table->construct_row();
            $table->output($sub_tabs['ougc_awards_users']['description']);
        } else {
            $query2 = $db->simple_select(
                'ougc_awards_users',
                'COUNT(uid) AS users',
                'aid=\'' . (int)$award['aid'] . '\''
            );
            $givedscount = (int)$db->fetch_field($query2, 'users');

            echo draw_admin_pagination(
                $awards->get_input('page', 1),
                $awards->query_limit,
                $givedscount,
                'index.php?module=user-ougc_awards&amp;view=category&amp;cid=' . $cid . '&amp;action=users&amp;aid=' . $award['aid']
            );

            while ($gived = $db->fetch_array($query)) {
                $gived['username'] = format_name(
                    htmlspecialchars_uni($gived['username']),
                    $gived['usergroup'],
                    $gived['displaygroup']
                );
                $table->construct_cell(
                    "<a href=\"index.php?module=user-users&amp;view=category&action=edit&uid={$gived['uid']}\">{$gived['username']}</a>"
                );
                $table->construct_cell(htmlspecialchars_uni($gived['reason']));

                $threadlink = '';
                if ($gived['thread'] && $thread = get_thread($gived['thread'])) {
                    $thread['threadprefix'] = $thread['displayprefix'] = '';
                    if ($thread['prefix']) {
                        $threadprefix = build_prefixes($thread['prefix']);

                        if (!empty($threadprefix['prefix'])) {
                            $thread['threadprefix'] = htmlspecialchars_uni($threadprefix['prefix']) . '&nbsp;';
                            $thread['displayprefix'] = $threadprefix['displaystyle'] . '&nbsp;';
                        }
                    }

                    require_once \MYBB_ROOT . 'inc/class_parser.php';
                    is_object($parser) or $parser = new postParser();

                    $thread['subject'] = $parser->parse_badwords($thread['subject']);

                    $threadlink = '<a href="' . $mybb->settings['bburl'] . '/' . get_thread_link(
                            $thread['tid']
                        ) . '">' . $thread['displayprefix'] . htmlspecialchars_uni($thread['subject']) . '</a>';
                }

                $table->construct_cell($threadlink);
                $table->construct_cell(
                    $lang->sprintf(
                        $lang->ougc_awards_users_time,
                        my_date($mybb->settings['dateformat'], intval($gived['date'])),
                        my_date($mybb->settings['timeformat'], intval($gived['date']))
                    ),
                    array('class' => 'align_center')
                );
                $table->construct_cell(
                    "<a href=\"{$awards->build_url(array('action' => 'user', 'gid' => $gived['gid']))}\">{$lang->ougc_awards_tab_edit}</a>",
                    array('class' => 'align_center')
                );
                $table->construct_row();
            }

            $table->output($sub_tabs['ougc_awards_users']['description']);
        }
        $page->output_footer();
    } elseif ($awards->get_input('action') == 'owners') {
        if (!($award = $awards->get_award($awards->get_input('aid', 1)))) {
            $awards->admin_redirect();
        }

        $grant = true;

        $awards->set_url(array('action' => 'owners', 'aid' => $award['aid']));

        $grant = $awards->get_input('delete', 1) == 1 ? false : true;
        $revoke = $awards->get_input('delete', 1) == 1 ? true : false;

        if ($mybb->request_method == 'post') {
            $errors = $users = array();

            if ($revoke) {
                if (!verify_post_check($awards->get_input('my_post_key'))) {
                    flash_message($lang->invalid_post_verify_key2, 'error');
                    admin_redirect('index.php?module=config-plugins');
                }

                if (isset($mybb->input['no'])) {
                    $awards->admin_redirect();
                }
            } else {
                $user = $awards->get_user_by_username($mybb->input['username']);
                if (!$user) {
                    $errors[] = $lang->ougc_awards_error_invaliduser;
                }

                if (!$awards->can_edit_user($user['uid'])) {
                    $errors[] = $lang->ougc_awards_error_giveperm;
                }

                if ($awards->get_owner($award['aid'], $user['uid'])) {
                    $errors[] = $lang->ougc_awards_success_owner_duplicated;
                }
            }

            if (empty($errors)) {
                if ($revoke) {
                    $awards->revoke_owner($awards->get_input('oid'));
                } else {
                    $awards->insert_owner($award, $user);
                }

                $awards->log_action();

                $lang_var = $grant ? 'ougc_awards_success_owner_grant' : 'ougc_awards_success_owner_revoke';
                $awards->admin_redirect($lang->{$lang_var});
            }
        }

        if ($revoke) {
            $page->output_confirm_action($awards->build_url(array('oid' => $awards->get_input('oid'), 'delete' => '1')),
                $lang->ougc_awards_owner_revoke_desc,
                $lang->ougc_awards_owner_revoke_title);
        }

        $page->add_breadcrumb_item($lang->ougc_awards_acp_nav, $awards->build_url());
        $page->add_breadcrumb_item($category['name'], $category_url);
        $page->add_breadcrumb_item(strip_tags($award['name']));
        $page->output_header($lang->ougc_awards_acp_nav);
        $page->output_nav_tabs($sub_tabs, 'ougc_awards_owners');

        if (!empty($errors)) {
            $page->output_inline_error($errors);
        }

        $table = new Table();
        $table->construct_header($lang->ougc_awards_form_username, array('width' => '30%'));
        $table->construct_header($lang->ougc_awards_users_date, array('width' => '30%', 'class' => 'align_center'));
        $table->construct_header($lang->ougc_awards_view_actions, array('width' => '40%', 'class' => 'align_center'));

        $mybb->input['page'] = $awards->get_input('page', 1);
        if ($mybb->input['page'] > 0) {
            $start = ($mybb->input['page'] - 1) * $awards->query_limit;
        } else {
            $start = 0;
            $mybb->input['page'] = 1;
        }

        $query = $db->simple_select(
            'ougc_awards_owners au LEFT JOIN ' . $db->table_prefix . 'users u ON (u.uid=au.uid)',
            'au.*, u.username, u.usergroup, u.displaygroup',
            'au.aid=\'' . (int)$award['aid'] . '\'',
            array(
                'limit_start' => $start,
                'limit' => $awards->query_limit,
                'order_by' => 'au.date',
                'order_dir' => 'desc'
            )
        );

        if (!$db->num_rows($query)) {
            $table->construct_cell(
                '<div align="center">' . $lang->ougc_awards_users_empty . '</div>',
                array('colspan' => 6)
            );
            $table->construct_row();
            $table->output($sub_tabs['ougc_awards_owners']['description']);
        } else {
            $query2 = $db->simple_select(
                'ougc_awards_owners',
                'COUNT(uid) AS users',
                'aid=\'' . (int)$award['aid'] . '\''
            );
            $givedscount = (int)$db->fetch_field($query2, 'users');

            echo draw_admin_pagination(
                $awards->get_input('page', 1),
                $awards->query_limit,
                $givedscount,
                'index.php?module=user-ougc_awards&amp;view=category&amp;cid=' . $cid . '&amp;action=owners&amp;aid=' . $award['aid']
            );

            while ($owner = $db->fetch_array($query)) {
                $owner['username'] = format_name(
                    htmlspecialchars_uni($owner['username']),
                    $owner['usergroup'],
                    $owner['displaygroup']
                );
                $table->construct_cell(build_profile_link($owner['username'], $owner['uid']));
                $table->construct_cell(
                    $lang->sprintf(
                        $lang->ougc_awards_users_time,
                        my_date($mybb->settings['dateformat'], intval($owner['date'])),
                        my_date($mybb->settings['timeformat'], intval($owner['date']))
                    ),
                    array('class' => 'align_center')
                );
                $table->construct_cell(
                    "<a href=\"{$awards->build_url(array('oid' => $owner['oid'], 'delete' => '1'))}\">{$lang->ougc_awards_tab_delete}</a>",
                    array('class' => 'align_center')
                );
                $table->construct_row();
            }

            $table->output($sub_tabs['ougc_awards_owners']['description']);
        }

        $form = new Form($awards->build_url(), 'post');
        $form_container = new FormContainer($lang->ougc_awards_tab_owners_form);

        $form_container->output_row(
            $lang->ougc_awards_form_username . ' <em>*</em>',
            $lang->ougc_awards_form_owner_username_d,
            $form->generate_text_box('username', isset($mybb->input['username']) ? $mybb->input['username'] : '')
        );

        $form_container->end();
        $form->output_submit_wrapper(
            array(
                $form->generate_submit_button($lang->ougc_awards_button_submit),
                $form->generate_reset_button($lang->reset)
            )
        );
        $form->end();

        $page->output_footer();
    } elseif ($awards->get_input('action') == 'user') {
        if (!($gived = $awards->get_gived_award(null, null, $awards->get_input('gid', 1)))) {
            $awards->admin_redirect($lang->ougc_awards_error_invaliduser, true);
        }

        if (!($award = $awards->get_award($gived['aid']))) {
            $awards->admin_redirect($lang->ougc_awards_error_invaliduser, true);
        }

        $awards->set_url(array('aid' => $award['aid']));

        $page->add_breadcrumb_item($lang->ougc_awards_acp_nav, $awards->build_url(array('action' => 'users')));
        $page->output_header($lang->ougc_awards_acp_nav);
        $page->output_nav_tabs($sub_tabs, 'ougc_awards_edit_user');

        $timestamp = $awards->get_input('date', 1);

        if ($mybb->request_method == 'post') {
            if ($awards->get_input('thread')) {
                if (!($thread = $awards->get_thread_by_url($awards->get_input('thread')))) {
                    $errors[] = $lang->ougc_awards_error_invalidthread;
                }
            }

            /*if(($timestamp <= PHP_INT_MAX) && ($timestamp >= ~PHP_INT_MAX))
            {
                $errors[] = $lang->ougc_awards_error_invaliddate;
            }*/

            if (empty($errors)) {
                $update_data = array(
                    'date' => $mybb->input['date'],
                    'reason' => $mybb->input['reason']
                );

                !$thread or $update_data['thread'] = $thread['tid'];

                $awards->update_gived($gived['gid'], $update_data);

                $awards->set_url(array('action' => 'users'));

                $awards->log_action();
                $awards->admin_redirect($lang->ougc_awards_success_edit);
            } else {
                $page->output_inline_error($errors);
            }
        }

        $form = new Form($awards->build_url(array('action' => 'user', 'gid' => $gived['gid'])), 'post');
        $form_container = new FormContainer($lang->ougc_awards_tab_edit_user_d);

        $form_container->output_row(
            $lang->ougc_awards_form_reason,
            $lang->ougc_awards_form_reason_d,
            $form->generate_text_area(
                'reason',
                isset($mybb->input['reason']) ? $mybb->input['reason'] : $gived['reason'],
                array('rows' => 8, 'style' => 'width:80%;')
            )
        );
        $form_container->output_row(
            $lang->ougc_awards_form_thread,
            $lang->ougc_awards_form_thread_d,
            $form->generate_text_box(
                'thread',
                isset($mybb->input['thread']) ? $awards->get_input('thread') : ($gived['thread'] ? get_thread_link(
                    $gived['thread']
                ) : '')
            )
        );
        $form_container->output_row(
            $lang->ougc_awards_users_timestamp,
            $lang->ougc_awards_users_timestamp_d,
            $form->generate_text_box('date', isset($mybb->input['date']) ? $timestamp : intval($gived['date']))
        );

        $form_container->end();
        $form->output_submit_wrapper(
            array(
                $form->generate_submit_button($lang->ougc_awards_button_submit),
                $form->generate_reset_button($lang->reset)
            )
        );
        $form->end();
        $page->output_footer();
    } else {
        $page->add_breadcrumb_item($lang->ougc_awards_acp_nav, $awards->build_url());
        $page->add_breadcrumb_item($category['name'], $category_url);
        $page->output_header($lang->ougc_awards_acp_nav);
        $page->output_nav_tabs($sub_tabs, 'ougc_awards_view');

        $table = new Table();
        $table->construct_header($lang->ougc_awards_view_image, array('width' => '1%'));
        $table->construct_header($lang->ougc_awards_form_name, array('width' => '19%'));
        $table->construct_header($lang->ougc_awards_form_desc, array('width' => '45%'));
        $table->construct_header($lang->ougc_awards_form_order, array('width' => '10%', 'class' => 'align_center'));
        $table->construct_header($lang->ougc_awards_form_visible, array('width' => '10%', 'class' => 'align_center'));
        $table->construct_header($lang->ougc_awards_view_actions, array('width' => '15%', 'class' => 'align_center'));

        if ($awards->get_input('page', 1) > 0) {
            $start = ($awards->get_input('page', 1) - 1) * $awards->query_limit;
        } else {
            $start = 0;
            $mybb->input['page'] = 1;
        }

        $query = $db->simple_select(
            'ougc_awards',
            '*',
            "cid='{$cid}'",
            array('limit_start' => $start, 'limit' => $awards->query_limit, 'order_by' => 'disporder')
        );

        if (!$db->num_rows($query)) {
            $table->construct_cell(
                '<div align="center">' . $lang->ougc_awards_view_empty . '</div>',
                array('colspan' => 6)
            );
            $table->construct_row();
            $table->output($lang->ougc_awards_tab_view_d);
        } else {
            if ($mybb->request_method == 'post' && $awards->get_input('action') == 'updatedisporder') {
                foreach ($mybb->input['disporder'] as $aid => $disporder) {
                    $awards->update_award(array('disporder' => $disporder), $aid);
                }
                $awards->update_cache();
                $awards->admin_redirect();
            }

            $form = new Form($awards->build_url('action=updatedisporder'), 'post');

            $query2 = $db->simple_select('ougc_awards', 'COUNT(aid) AS awards', "cid='{$cid}'");
            $awardscount = (int)$db->fetch_field($query2, 'awards');

            echo draw_admin_pagination(
                $mybb->input['page'],
                $awards->query_limit,
                $awardscount,
                'index.php?module=user-ougc_awards&amp;view=category&amp;cid=' . $cid . ''
            );

            while ($award = $db->fetch_array($query)) {
                $edit_link = "index.php?module=user-ougc_awards&amp;view=category&amp;cid={$cid}&amp;action=edit&amp;aid={$award['aid']}";

                $award['visible'] or $award['name'] = '<i>' . $award['name'] . '</i>';

                if ((int)$award['template'] === 1) {
                    $load_fa = true;
                }

                $award['image'] = $awards->get_award_icon($award['aid']);
                $award['fimage'] = eval($templates->render($awards->get_award_info('template', $award['aid'])));

                $table->construct_cell($award['fimage'], array('class' => 'align_center'));
                $table->construct_cell('<a href="' . $edit_link . '">' . $award['name'] . '</a>');
                $table->construct_cell($award['description']);
                $table->construct_cell(
                    $form->generate_text_box(
                        'disporder[' . $award['aid'] . ']',
                        (int)$award['disporder'],
                        array('style' => 'text-align: center; width: 30px;')
                    ),
                    array('class' => 'align_center')
                );
                $table->construct_cell(
                    '<img src="styles/default/images/icons/bullet_o' . (!$award['visible'] ? 'ff' : 'n') . ($mybb->version_code >= 1800 ? '.png' : '.gif') . '" alt="" title="' . (!$award['visible'] ? $lang->ougc_awards_form_hidden : $lang->ougc_awards_form_visible) . '" />',
                    array('class' => 'align_center')
                );

                $popup = new PopupMenu("award_{$award['aid']}", $lang->options);
                $popup->add_item(
                    $lang->ougc_awards_tab_give,
                    "index.php?module=user-ougc_awards&amp;view=category&amp;cid={$cid}&amp;action=give&amp;aid={$award['aid']}"
                );
                $popup->add_item(
                    $lang->ougc_awards_tab_revoke,
                    "index.php?module=user-ougc_awards&amp;view=category&amp;cid={$cid}&amp;action=revoke&amp;aid={$award['aid']}"
                );
                $popup->add_item(
                    $lang->ougc_awards_tab_users,
                    "index.php?module=user-ougc_awards&amp;view=category&amp;cid={$cid}&amp;action=users&amp;aid={$award['aid']}"
                );
                $popup->add_item(
                    $lang->ougc_awards_tab_owners,
                    "index.php?module=user-ougc_awards&amp;view=category&amp;cid={$cid}&amp;action=owners&amp;aid={$award['aid']}"
                );
                $popup->add_item($lang->ougc_awards_tab_edit, $edit_link);
                $popup->add_item(
                    $lang->ougc_awards_tab_delete,
                    "index.php?module=user-ougc_awards&amp;view=category&amp;cid={$cid}&amp;action=delete&amp;aid={$award['aid']}"
                );
                $table->construct_cell($popup->fetch(), array('class' => 'align_center'));

                $table->construct_row();
            }

            if (!empty($load_fa)) {
                echo '<link href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" integrity="sha384-wvfXpqpZZVQGK6TAh5PVlGOfQNHSoD2xbE+QkPxCAFlNEevoEH3Sl0sibVcOQVnN" crossorigin="anonymous">';
            }

            $table->output($lang->ougc_awards_tab_view_d);

            $form->output_submit_wrapper(
                array(
                    $form->generate_submit_button($lang->ougc_awards_button_order),
                    $form->generate_reset_button($lang->reset)
                )
            );
            $form->end();
        }
        $page->output_footer();
    }

else:

    $sub_tabs['ougc_awards_add'] = array(
        'title' => $lang->ougc_awards_tab_add,
        'link' => 'index.php?module=user-ougc_awards&amp;action=add',
        'description' => $lang->ougc_awards_tab_addc_desc,
        'align' => 'right'
    );

    switch ($awards->get_input('action')) {
        case 'edit':
            $sub_tabs['ougc_awards_edit'] = array(
                'title' => $lang->ougc_awards_tab_edit,
                'link' => 'index.php?module=user-ougc_awards&amp;action=edit&amp;cid=' . $cid,
                'description' => $lang->ougc_awards_tab_editc_desc,
                'align' => 'right'
            );
            break;
    }

    if ($awards->get_input('action') == 'add' || $awards->get_input('action') == 'edit') {
        $page->add_breadcrumb_item($lang->ougc_awards_acp_nav, $awards->build_url());

        if ($category) {
            $page->add_breadcrumb_item($category['name'], $category_url);
        }

        if (!($add = $awards->get_input('action') == 'add')) {
            if (!$category) {
                $awards->admin_redirect($lang->ougc_awards_error_invalidcategory, true);
            }

            $page->add_breadcrumb_item(strip_tags($category['name']));
        }

        $mergeinput = array();
        foreach (array('name', 'description', 'allowrequests', 'visible', 'disporder') as $key) {
            $mergeinput[$key] = isset($mybb->input[$key]) ? $mybb->input[$key] : ($add ? '' : $category[$key]);
        }

        $mybb->input = array_merge($mybb->input, $mergeinput);

        $page->output_header($lang->ougc_awards_acp_nav);
        $page->output_nav_tabs($sub_tabs, $add ? 'ougc_awards_add' : 'ougc_awards_edit');

        if ($mybb->request_method == 'post') {
            $errors = array();
            if (!$awards->get_input('name') || my_strlen($mybb->input['name']) > 100) {
                $errors[] = $lang->ougc_awards_error_invalidname;
            }

            if (my_strlen($mybb->input['description']) > 255) {
                $errors[] = $lang->ougc_awards_error_invaliddesscription;
            }

            if (empty($errors)) {
                $method = $add ? 'insert_category' : 'update_category';
                $lang_val = $add ? 'ougc_awards_success_add' : 'ougc_awards_success_edit';

                $awards->{$method}(array(
                    'name' => $awards->get_input('name'),
                    'description' => $awards->get_input('description'),
                    'allowrequests' => $awards->get_input('allowrequests', 1),
                    'visible' => $awards->get_input('visible', 1),
                    'disporder' => $awards->get_input('disporder', 1),
                ), $awards->get_input('cid', 1));
                $awards->update_cache();
                $awards->log_action();
                $awards->admin_redirect($lang->{$lang_val});
            } else {
                $page->output_inline_error($errors);
            }
        }

        $form = new Form($awards->build_url(($add ? 'action=add' : array('action' => 'edit', 'cid' => $cid))), 'post');
        $form_container = new FormContainer($sub_tabs['ougc_awards_' . ($add ? 'add' : 'edit')]['description']);

        $form_container->output_row(
            $lang->ougc_awards_form_name . ' <em>*</em>',
            $lang->ougc_awards_form_name_d,
            $form->generate_text_box('name', $mybb->input['name'])
        );
        $form_container->output_row(
            $lang->ougc_awards_form_desc,
            $lang->ougc_awards_form_desc_d,
            $form->generate_text_box('description', $mybb->input['description'])
        );
        $form_container->output_row(
            $lang->ougc_awards_form_allowrequests,
            $lang->ougc_awards_form_allowrequests_desc,
            $form->generate_yes_no_radio('allowrequests', (int)$mybb->input['allowrequests'])
        );
        $form_container->output_row(
            $lang->ougc_awards_form_visible,
            $lang->ougc_awards_form_visible_d,
            $form->generate_yes_no_radio('visible', (int)$mybb->input['visible'])
        );
        $form_container->output_row(
            $lang->ougc_awards_form_order,
            $lang->ougc_awards_form_order_d,
            $form->generate_text_box(
                'disporder',
                (int)$mybb->input['disporder'],
                array('style' => 'text-align: center; width: 30px;" maxlength="5')
            )
        );

        $form_container->end();
        $form->output_submit_wrapper(
            array(
                $form->generate_submit_button($lang->ougc_awards_button_submit),
                $form->generate_reset_button($lang->reset)
            )
        );
        $form->end();
        $page->output_footer();
    } elseif ($awards->get_input('action') == 'delete') {
        if (!$category) {
            $awards->admin_redirect($lang->ougc_awards_error_invalidaward, true);
        }

        if ($mybb->request_method == 'post') {
            if (!verify_post_check($mybb->input['my_post_key'], true)) {
                $awards->admin_redirect($lang->invalid_post_verify_key2, true);
            }

            !isset($mybb->input['no']) or $awards->admin_redirect();

            $awards->delete_category($category['cid']);
            $awards->update_cache();
            $awards->log_action();
            $awards->admin_redirect($lang->ougc_awards_success_delete);
        }
        $page->output_confirm_action(
            $awards->build_url(array('action' => 'delete', 'cid' => $category['cid'], 'my_post_key' => $mybb->post_code)
            )
        );
    } else {
        $page->add_breadcrumb_item($lang->ougc_awards_acp_nav, $awards->build_url());

        if ($category) {
            $page->add_breadcrumb_item($category['name'], $category_url);
        }

        $page->output_header($lang->ougc_awards_acp_nav);
        $page->output_nav_tabs($sub_tabs, 'ougc_awards_categories');

        $table = new Table();
        $table->construct_header($lang->ougc_awards_form_name, array('width' => '20%'));
        $table->construct_header($lang->ougc_awards_form_desc, array('width' => '45%'));
        $table->construct_header($lang->ougc_awards_form_order, array('width' => '10%', 'class' => 'align_center'));
        $table->construct_header($lang->ougc_awards_form_visible, array('width' => '10%', 'class' => 'align_center'));
        $table->construct_header($lang->ougc_awards_view_actions, array('width' => '15%', 'class' => 'align_center'));

        if ($awards->get_input('page', 1) > 0) {
            $start = ($awards->get_input('page', 1) - 1) * $awards->query_limit;
        } else {
            $start = 0;
            $mybb->input['page'] = 1;
        }

        $query = $db->simple_select(
            'ougc_awards_categories',
            '*',
            '',
            array('limit_start' => $start, 'limit' => $awards->query_limit, 'order_by' => 'disporder')
        );

        if (!$db->num_rows($query)) {
            $table->construct_cell(
                '<div align="center">' . $lang->ougc_awards_view_empty . '</div>',
                array('colspan' => 5)
            );
            $table->construct_row();
            $table->output($lang->ougc_awards_tab_categories_desc);
        } else {
            if ($mybb->request_method == 'post' && $awards->get_input('action') == 'updatedisporder') {
                foreach ($mybb->input['disporder'] as $cid => $disporder) {
                    $awards->update_category(array('disporder' => $disporder), $cid);
                }
                $awards->update_cache();
                $awards->admin_redirect();
            }

            $form = new Form($awards->build_url('action=updatedisporder'), 'post');

            $query2 = $db->simple_select('ougc_awards_categories', 'COUNT(cid) AS categories');
            $catscount = (int)$db->fetch_field($query2, 'categories');

            echo draw_admin_pagination(
                $mybb->input['page'],
                $awards->query_limit,
                $catscount,
                'index.php?module=user-ougc_awards'
            );

            while ($category = $db->fetch_array($query)) {
                $view_link = '';

                $category['visible'] or $category['name'] = '<i>' . $category['name'] . '</i>';

                $table->construct_cell(
                    "<a href=\"index.php?module=user-ougc_awards&amp;view=category&amp;cid={$category['cid']}\">{$category['name']}</a>"
                );
                $table->construct_cell($category['description']);
                $table->construct_cell(
                    $form->generate_text_box(
                        'disporder[' . $category['cid'] . ']',
                        (int)$category['disporder'],
                        array('style' => 'text-align: center; width: 30px;')
                    ),
                    array('class' => 'align_center')
                );
                $table->construct_cell(
                    '<img src="styles/default/images/icons/bullet_o' . (!$category['visible'] ? 'ff' : 'n') . ($mybb->version_code >= 1800 ? '.png' : '.gif') . '" alt="" title="' . (!$category['visible'] ? $lang->ougc_awards_form_hidden : $lang->ougc_awards_form_visible) . '" />',
                    array('class' => 'align_center')
                );

                $popup = new PopupMenu("award_{$category['cid']}", $lang->options);
                $popup->add_item(
                    $lang->ougc_awards_tab_edit,
                    "index.php?module=user-ougc_awards&amp;action=edit&amp;cid={$category['cid']}"
                );
                $popup->add_item(
                    $lang->ougc_awards_tab_delete,
                    "index.php?module=user-ougc_awards&amp;action=delete&amp;cid={$category['cid']}"
                );
                $table->construct_cell($popup->fetch(), array('class' => 'align_center'));

                $table->construct_row();
            }
            $table->output($lang->ougc_awards_tab_categories_desc);

            $form->output_submit_wrapper(
                array(
                    $form->generate_submit_button($lang->ougc_awards_button_order),
                    $form->generate_reset_button($lang->reset)
                )
            );
            $form->end();
        }
        $page->output_footer();
    }

endif;
