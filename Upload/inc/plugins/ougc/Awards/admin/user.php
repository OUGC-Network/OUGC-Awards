<?php

/***************************************************************************
 *
 *    OUGC Awards plugin (/inc/plugins/ougc/Awards/admin/user.php)
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

use function ougc\Awards\Core\awardDelete;

use function ougc\Awards\Core\awardGet;

use function ougc\Awards\Core\awardGetIcon;

use function ougc\Awards\Core\getTemplate;

use function ougc\Awards\Core\awardGetInfo;

use function ougc\Awards\Core\awardInsert;

use function ougc\Awards\Core\awardUpdate;

use function ougc\Awards\Core\cacheUpdate;

use function ougc\Awards\Core\canManageUsers;

use function ougc\Awards\Core\categoryDelete;

use function ougc\Awards\Core\categoryGet;

use function ougc\Awards\Core\categoryInsert;

use function ougc\Awards\Core\categoryUpdate;

use function ougc\Awards\Core\generateSelectAwards;

use function ougc\Awards\Core\generateSelectCategory;

use function ougc\Awards\Core\generateSelectCustomReputation;

use function ougc\Awards\Core\generateSelectGrant;

use function ougc\Awards\Core\generateSelectProfileFields;

use function ougc\Awards\Core\getThreadByUrl;

use function ougc\Awards\Core\getUser;

use function ougc\Awards\Core\getUserByUserName;

use function ougc\Awards\Core\grantFind;

use function ougc\Awards\Core\grantGetSingle;

use function ougc\Awards\Core\grantUpdate;

use function ougc\Awards\Core\loadLanguage;

use function ougc\Awards\Core\logAction;

use function ougc\Awards\Core\ownerFind;

use function ougc\Awards\Core\ownerInsert;

use function ougc\Awards\Core\c;

use function ougc\Awards\Core\redirectAdmin;

use function ougc\Awards\Core\taskDelete;

use function ougc\Awards\Core\taskGet;

use function ougc\Awards\Core\taskInsert;

use function ougc\Awards\Core\taskUpdate;

use function ougc\Awards\Core\urlHandlerBuild;

use function ougc\Awards\Core\getSetting;

use function ougc\Awards\Core\urlHandlerSet;

use const ougc\Awards\Core\INFORMATION_TYPE_TEMPLATE;

global $awards, $lang, $mybb, $db, $templates;
global $run_module, $page, $parser;

loadLanguage();

$sub_tabs['ougc_awards_categories'] = array(
    'title' => $lang->ougc_awards_tab_categories,
    'link' => 'index.php?module=user-ougc_awards',
    'description' => $lang->ougc_awards_tab_categories_desc
);

$aid = $mybb->get_input('aid', MyBB::INPUT_INT);
$cid = $mybb->get_input('cid', MyBB::INPUT_INT);
$tid = $mybb->get_input('tid', MyBB::INPUT_INT);

$category = categoryGet($cid);
$category_url = urlHandlerBuild(['view' => 'category', 'cid' => $cid]);

$sub_tabs['ougc_awards_tasks'] = array(
    'title' => $lang->ougc_awards_tab_tasks,
    'link' => 'index.php?module=user-ougc_awards&amp;view=tasks',
    'description' => $lang->ougc_awards_tab_tasks_desc
);

if ($mybb->get_input('view') == 'tasks') {
    urlHandlerSet(urlHandlerBuild(['view' => 'tasks']));

    $lang->load($run_module . '_group_promotions', false, true);

    $sub_tabs['ougc_awards_add'] = array(
        'title' => $lang->ougc_awards_tab_add,
        'link' => urlHandlerBuild(['action' => 'add']),
        'description' => $lang->ougc_awards_tab_add_d,
        'align' => 'right'
    );

    switch ($mybb->get_input('action')) {
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

    if ($mybb->get_input('action') == 'add' || $mybb->get_input('action') == 'edit') {
        $page->add_breadcrumb_item($lang->ougc_awards_acp_nav, urlHandlerBuild());

        if ($category) {
            $page->add_breadcrumb_item($category['name'], $category_url);
        }

        if (!($add = $mybb->get_input('action') == 'add')) {
            if (!($task = taskGet($mybb->get_input('tid', MyBB::INPUT_INT)))) {
                redirectAdmin($lang->ougc_awards_error_invalitask, true);
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
                'ougc_customrepids_g',
                'ruleScripts'
            ) as $key
        ) {
            $mergeinput[$key] = isset($mybb->input[$key]) ? $mybb->input[$key] : ($add ? '' : $task[$key]);
        }
        $mybb->input = array_merge($mybb->input, $mergeinput);

        $page->output_header($lang->ougc_awards_acp_nav);
        $page->output_nav_tabs($sub_tabs, $add ? 'ougc_awards_add' : 'ougc_awards_edit');

        if ($mybb->request_method == 'post') {
            $errors = array();

            if (!$mybb->get_input('name') || my_strlen($mybb->get_input('name')) > 100) {
                $errors[] = $lang->ougc_awards_error_invalidname;
            }

            if (my_strlen($mybb->input['description']) > 255) {
                $errors[] = $lang->ougc_awards_error_invaliddesscription;
            }

            if (my_strlen($mybb->input['image']) > 255) {
                $errors[] = $lang->ougc_awards_error_invalidimage;
            }

            if ($mybb->get_input('thread')) {
                if (!($thread = getThreadByUrl($mybb->get_input('thread')))) {
                    $errors[] = $lang->ougc_awards_error_invalidthread;
                }
            }

            if (empty($errors)) {
                $taskData = array(
                    'name' => $mybb->get_input('name'),
                    'description' => $mybb->get_input('description'),
                    'active' => $mybb->get_input('active', MyBB::INPUT_INT),
                    'logging' => $mybb->get_input('logging', MyBB::INPUT_INT),
                    'requirements' => $mybb->get_input('requirements', MyBB::INPUT_ARRAY),
                    'usergroups' => $mybb->get_input('usergroups', MyBB::INPUT_ARRAY),
                    'additionalgroups' => $mybb->get_input('additionalgroups', MyBB::INPUT_INT),
                    'give' => $mybb->get_input('give', MyBB::INPUT_ARRAY),
                    'reason' => $mybb->get_input('reason'),
                    'thread' => !empty($thread['tid']) ? (int)$thread['tid'] : 0,
                    'allowmultiple' => $mybb->get_input('allowmultiple', MyBB::INPUT_INT),
                    'revoke' => $mybb->get_input('revoke', MyBB::INPUT_ARRAY),
                    'disporder' => $mybb->get_input('disporder', MyBB::INPUT_INT),
                    'posts' => $mybb->get_input('posts', MyBB::INPUT_INT),
                    'poststype' => $mybb->get_input('poststype'),
                    'threads' => $mybb->get_input('threads', MyBB::INPUT_INT),
                    'threadstype' => $mybb->get_input('threadstype'),
                    'fposts' => $mybb->get_input('fposts', MyBB::INPUT_INT),
                    'fpoststype' => $mybb->get_input('fpoststype'),
                    'fpostsforums' => $mybb->get_input('fpostsforums', MyBB::INPUT_INT),
                    'fthreads' => $mybb->get_input('fthreads', MyBB::INPUT_INT),
                    'fthreadstype' => $mybb->get_input('fthreadstype'),
                    'fthreadsforums' => $mybb->get_input('fthreadsforums', MyBB::INPUT_INT),
                    'registered' => $mybb->get_input('registered', MyBB::INPUT_INT),
                    'registeredtype' => $mybb->get_input('registeredtype'),
                    'online' => $mybb->get_input('online', MyBB::INPUT_INT),
                    'onlinetype' => $mybb->get_input('onlinetype'),
                    'reputation' => $mybb->get_input('reputation', MyBB::INPUT_INT),
                    'reputationtype' => $mybb->get_input('reputationtype'),
                    'referrals' => $mybb->get_input('referrals', MyBB::INPUT_INT),
                    'referralstype' => $mybb->get_input('referralstype'),
                    'warnings' => $mybb->get_input('warnings', MyBB::INPUT_INT),
                    'warningstype' => $mybb->get_input('warningstype'),
                    'newpoints' => $mybb->get_input('newpoints', MyBB::INPUT_INT),
                    'newpointstype' => $mybb->get_input('newpointstype'),
                    'previousawards' => $mybb->get_input('previousawards', MyBB::INPUT_ARRAY),
                    'profilefields' => $mybb->get_input('profilefields', MyBB::INPUT_ARRAY),
                    'mydownloads' => $mybb->get_input('mydownloads', MyBB::INPUT_INT),
                    'mydownloadstype' => $mybb->get_input('mydownloadstype'),
                    'myarcadechampions' => $mybb->get_input('myarcadechampions', MyBB::INPUT_INT),
                    'myarcadechampionstype' => $mybb->get_input('myarcadechampionstype'),
                    'myarcadescores' => $mybb->get_input('myarcadescores', MyBB::INPUT_INT),
                    'myarcadescorestype' => $mybb->get_input('myarcadescorestype'),
                    'ougc_customrep_r' => $mybb->get_input('ougc_customrep_r', MyBB::INPUT_INT),
                    'ougc_customreptype_r' => $mybb->get_input('ougc_customreptype_r'),
                    'ougc_customrepids_r' => $mybb->get_input('ougc_customrepids_r', MyBB::INPUT_INT),
                    'ougc_customrep_g' => $mybb->get_input('ougc_customrep_g', MyBB::INPUT_INT),
                    'ougc_customreptype_g' => $mybb->get_input('ougc_customreptype_g'),
                    'ougc_customrepids_g' => $mybb->get_input('ougc_customrepids_g', MyBB::INPUT_INT),
                    'ruleScripts' => $mybb->get_input('ruleScripts'),
                );

                if ($add) {
                    taskInsert($taskData);

                    $lang_val = 'ougc_awards_success_add';
                } else {
                    taskUpdate($taskData, $mybb->get_input('tid', MyBB::INPUT_INT), true);

                    $lang_val = 'ougc_awards_success_edit';
                }

                cacheUpdate();
                logAction();
                redirectAdmin($lang->{$lang_val});
            } else {
                $page->output_inline_error($errors);
            }
        }

        if ($add) {
            $link = urlHandlerBuild(['action' => 'add']);
        } else {
            $link = urlHandlerBuild(['action' => 'edit', 'tid' => $task['tid']]);
        }

        $form = new Form(
            $link,
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
            ), $mybb->get_input('requirements', MyBB::INPUT_ARRAY), array('multiple' => true, 'size' => 5))
        );
        $form_container->output_row(
            $lang->ougc_awards_form_give,
            $lang->ougc_awards_form_give_desc,
            generateSelectAwards('give[]', $mybb->get_input('give', \MyBB::INPUT_ARRAY), array('multiple' => true))
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
                isset($mybb->input['thread']) ? $mybb->get_input('thread') : ($task['thread'] ? get_thread_link(
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
            generateSelectAwards('revoke[]', $mybb->get_input('revoke', \MyBB::INPUT_ARRAY), array('multiple' => true))
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
            $form->generate_numeric_field(
                'posts',
                $mybb->get_input('posts', MyBB::INPUT_INT),
                array('id' => 'posts', 'min' => 0)
            ) . ' ' . $form->generate_select_box(
                'poststype',
                $options_type,
                $mybb->get_input('poststype'),
                array('id' => 'poststype')
            ),
            'posts'
        );
        $form_container->output_row(
            $lang->thread_count,
            $lang->thread_count_desc,
            $form->generate_numeric_field(
                'threads',
                $mybb->get_input('threads', MyBB::INPUT_INT),
                array('id' => 'threads', 'min' => 0)
            ) . ' ' . $form->generate_select_box(
                'threadstype',
                $options_type,
                $mybb->get_input('threadstype'),
                array('id' => 'threadstype')
            ),
            'threads'
        );
        $form_container->output_row(
            $lang->ougc_awards_form_requirements_fposts,
            $lang->ougc_awards_form_requirements_fposts_desc,
            $form->generate_numeric_field(
                'fposts',
                $mybb->get_input('fposts', MyBB::INPUT_INT),
                array('id' => 'fposts', 'min' => 0)
            ) . ' ' . $form->generate_select_box(
                'fpoststype',
                $options_type,
                $mybb->get_input('fpoststype'),
                array('id' => 'fpoststype')
            ) . '' . $form->generate_forum_select('fpostsforums', $mybb->get_input('fpostsforums', MyBB::INPUT_ARRAY)),
            'fposts'
        );
        $form_container->output_row(
            $lang->ougc_awards_form_requirements_fthreads,
            $lang->ougc_awards_form_requirements_fthreads_desc,
            $form->generate_numeric_field(
                'fthreads',
                $mybb->get_input('fthreads', MyBB::INPUT_INT),
                array('id' => 'fthreads', 'min' => 0)
            ) . ' ' . $form->generate_select_box(
                'fthreadstype',
                $options_type,
                $mybb->get_input('fthreadstype'),
                array('id' => 'fthreadstype')
            ) . '' . $form->generate_forum_select(
                'fthreadsforums',
                $mybb->get_input('fthreadsforums', MyBB::INPUT_ARRAY)
            ),
            'fthreads'
        );
        $form_container->output_row(
            $lang->time_registered,
            $lang->time_registered_desc,
            $form->generate_numeric_field(
                'registered',
                $mybb->get_input('registered', MyBB::INPUT_INT),
                array('id' => 'registered', 'min' => 0)
            ) . ' ' . $form->generate_select_box(
                'registeredtype',
                $options_time,
                $mybb->get_input('registeredtype'),
                array('id' => 'registeredtype')
            ),
            'registered'
        );
        $form_container->output_row(
            $lang->time_online,
            $lang->time_online_desc,
            $form->generate_numeric_field(
                'online',
                $mybb->get_input('online', MyBB::INPUT_INT),
                array('id' => 'online', 'min' => 0)
            ) . ' ' . $form->generate_select_box(
                'onlinetype',
                $options_time,
                $mybb->get_input('onlinetype'),
                array('id' => 'onlinetype')
            ),
            'online'
        );
        $form_container->output_row(
            $lang->reputation_count,
            $lang->reputation_count_desc,
            $form->generate_numeric_field(
                'reputation',
                $mybb->get_input('reputation', MyBB::INPUT_INT),
                array('id' => 'reputation', 'min' => 0)
            ) . '' . $form->generate_select_box(
                'reputationtype',
                $options_type,
                $mybb->get_input('reputationtype'),
                array('id' => 'reputationtype')
            ),
            'reputation'
        );
        $form_container->output_row(
            $lang->referral_count,
            $lang->referral_count_desc,
            $form->generate_numeric_field(
                'referrals',
                $mybb->get_input('referrals', MyBB::INPUT_INT),
                array('id' => 'referrals', 'min' => 0)
            ) . '' . $form->generate_select_box(
                'referralstype',
                $options_type,
                $mybb->get_input('referralstype'),
                array('id' => 'referralstype')
            ),
            'referrals'
        );
        $form_container->output_row(
            $lang->warning_points,
            $lang->warning_points_desc,
            $form->generate_numeric_field(
                'warnings',
                $mybb->get_input('warnings', MyBB::INPUT_INT),
                array('id' => 'warnings', 'min' => 0)
            ) . '' . $form->generate_select_box(
                'warningstype',
                $options_type,
                $mybb->get_input('warningstype'),
                array('id' => 'warningstype')
            ),
            'warnings'
        );
        $form_container->output_row(
            $lang->ougc_awards_form_requirements_newpoints,
            $lang->ougc_awards_form_requirements_newpoints_desc,
            $form->generate_numeric_field(
                'newpoints',
                $mybb->get_input('newpoints', MyBB::INPUT_INT),
                array('id' => 'newpoints', 'min' => 0)
            ) . ' ' . $form->generate_select_box(
                'newpointstype',
                $options_type,
                $mybb->get_input('newpointstype'),
                array('id' => 'newpointstype')
            ),
            'newpoints'
        );
        $form_container->output_row(
            $lang->ougc_awards_form_requirements_previousawards,
            $lang->ougc_awards_form_requirements_previousawards_desc,
            generateSelectAwards(
                'previousawards[]',
                $mybb->get_input('previousawards', \MyBB::INPUT_ARRAY),
                ['multiple' => true]
            )
        );
        $form_container->output_row(
            $lang->ougc_awards_form_requirements_profilefields,
            $lang->ougc_awards_form_requirements_profilefields_desc,
            generateSelectProfileFields(
                'profilefields[]',
                $mybb->get_input('profilefields', MyBB::INPUT_ARRAY),
                array('multiple' => true, 'id' => 'profilefields')
            ),
            'profilefields'
        );
        $form_container->output_row(
            $lang->ougc_awards_form_requirements_mydownloads,
            $lang->ougc_awards_form_requirements_mydownloads_desc,
            $form->generate_numeric_field(
                'mydownloads',
                $mybb->get_input('mydownloads', MyBB::INPUT_INT),
                array('id' => 'mydownloads', 'min' => 0)
            ) . ' ' . $form->generate_select_box(
                'mydownloadstype',
                $options_type,
                $mybb->get_input('mydownloadstype'),
                array('id' => 'mydownloadstype')
            ),
            'mydownloads'
        );
        //$form_container->output_row($lang->ougc_awards_form_requirements_myarcadechampions, $lang->ougc_awards_form_requirements_myarcadechampions_desc, $form->generate_numeric_field('myarcadechampions', $mybb->get_input('myarcadechampions', \MyBB::INPUT_INT), array('id' => 'myarcadechampions', 'min' => 0)).' '.$form->generate_select_box('myarcadechampionstype', $options_type, $mybb->get_input('myarcadechampionstype'), array('id' => 'myarcadechampionstype')), 'myarcadechampions');
        $form_container->output_row(
            $lang->ougc_awards_form_requirements_myarcadescores,
            $lang->ougc_awards_form_requirements_myarcadescores_desc,
            $form->generate_numeric_field(
                'myarcadescores',
                $mybb->get_input('myarcadescores', MyBB::INPUT_INT),
                array('id' => 'myarcadescores', 'min' => 0)
            ) . ' ' . $form->generate_select_box(
                'myarcadescorestype',
                $options_type,
                $mybb->get_input('myarcadescorestype'),
                array('id' => 'myarcadescorestype')
            ),
            'myarcadescores'
        );
        if ($reputation_select_r = generateSelectCustomReputation(
            'ougc_customrepids_r',
            $mybb->get_input('ougc_customrepids_r', \MyBB::INPUT_INT)
        )) {
            $form_container->output_row(
                $lang->ougc_awards_form_requirements_ougc_customrep_r,
                $lang->ougc_awards_form_requirements_ougc_customrep_r_desc,
                $form->generate_numeric_field(
                    'ougc_customrep_r',
                    $mybb->get_input('ougc_customrep_r', MyBB::INPUT_INT),
                    array('id' => 'ougc_customrep_r', 'min' => 0)
                ) . ' ' . $form->generate_select_box(
                    'ougc_customreptype_r',
                    $options_type,
                    $mybb->get_input('ougc_customreptype_r'),
                    array('id' => 'ougc_customreptype_r')
                ) . '' . $reputation_select_r,
                'ougc_customrep_r'
            );
        }
        if ($reputation_select_g = generateSelectCustomReputation(
            'ougc_customrepids_g',
            $mybb->get_input('ougc_customrepids_g', MyBB::INPUT_INT)
        )) {
            $form_container->output_row(
                $lang->ougc_awards_form_requirements_ougc_customrep_g,
                $lang->ougc_awards_form_requirements_ougc_customrep_g_desc,
                $form->generate_numeric_field(
                    'ougc_customrep_g',
                    $mybb->get_input('ougc_customrep_g', MyBB::INPUT_INT),
                    array('id' => 'ougc_customrep_g', 'min' => 0)
                ) . ' ' . $form->generate_select_box(
                    'ougc_customreptype_g',
                    $options_type,
                    $mybb->get_input('ougc_customreptype_g'),
                    array('id' => 'ougc_customreptype_g')
                ) . '' . $reputation_select_g,
                'ougc_customrep_g'
            );
        }

        $form_container->output_row(
            $lang->ougc_awards_form_requirements_ruleScripts,
            $lang->ougc_awards_form_requirements_ruleScripts_desc,
            $form->generate_text_area(
                'ruleScripts',
                $mybb->get_input('ruleScripts'),
                [
                    'style' => '" placeholder="' . str_replace(
                            '"',
                            '&quot;',
                            $lang->ougc_awards_form_requirements_ruleScripts_placeHolder
                        )
                ]
            ),
            'ruleScripts'
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
    } elseif ($mybb->get_input('action') == 'delete') {
        if (!($task = taskGet($mybb->get_input('tid', MyBB::INPUT_INT)))) {
            redirectAdmin($lang->ougc_awards_error_invalidtask, true);
        }

        if ($mybb->request_method == 'post') {
            if (!verify_post_check($mybb->input['my_post_key'], true)) {
                redirectAdmin($lang->invalid_post_verify_key2, true);
            }

            !isset($mybb->input['no']) or redirectAdmin();

            taskDelete($task['tid']);

            cacheUpdate();
            logAction();
            redirectAdmin($lang->ougc_awards_success_delete);
        }
        $page->output_confirm_action(
            urlHandlerBuild(['action' => 'delete', 'tid' => $task['tid'], 'my_post_key' => $mybb->post_code])
        );
    } elseif ($mybb->get_input('action') == 'logs') {
        $page->add_breadcrumb_item($lang->ougc_awards_acp_nav, urlHandlerBuild());
        $page->add_breadcrumb_item($sub_tabs['ougc_awards_tasks_logs']['title']);
        $page->output_header($lang->ougc_awards_acp_nav);
        $page->output_nav_tabs($sub_tabs, 'ougc_awards_tasks_logs');

        $table = new Table();
        $table->construct_header($lang->ougc_awards_logs_task, array('width' => '20%'));
        $table->construct_header($lang->ougc_awards_logs_user, array('width' => '40%'));
        $table->construct_header($lang->ougc_awards_logs_received, array('width' => '15%'));
        $table->construct_header($lang->ougc_awards_logs_revoked, array('width' => '15%'));
        $table->construct_header($lang->ougc_awards_logs_date, array('width' => '10%', 'class' => 'align_center'));

        if ($mybb->get_input('start', MyBB::INPUT_INT) > 0) {
            $start = ($mybb->get_input('start', MyBB::INPUT_INT) - 1) * (int)getSetting('perpage');
        } else {
            $start = 0;
            $mybb->input['page'] = 1;
        }

        $query = $db->simple_select(
            'ougc_awards_tasks_logs',
            '*',
            '',
            array(
                'limit_start' => $start,
                'limit' => (int)getSetting('perpage'),
                'order_by' => 'date',
                'order_dir' => 'desc'
            )
        );

        if (!$db->num_rows($query)) {
            $table->construct_cell(
                '<div align="center">' . $lang->ougc_awards_logs_empty . '</div>',
                array('colspan' => 5)
            );
            $table->construct_row();
            $table->output($sub_tabs['ougc_awards_tasks_logs']['title']);
        } else {
            if ($mybb->request_method == 'post') {
                $db->delete_query('ougc_awards_tasks_logs');

                urlHandlerSet(urlHandlerBuild(['action' => 'logs']));
                cacheUpdate();
                redirectAdmin($lang->ougc_awards_success_prunelogs);
            }

            $form = new Form(urlHandlerBuild('action=logs'), 'post');

            $query2 = $db->simple_select('ougc_awards_tasks_logs', 'COUNT(lid) AS logs');
            $logscount = (int)$db->fetch_field($query2, 'logs');

            echo draw_admin_pagination(
                $mybb->input['page'],
                (int)getSetting('perpage'),
                $logscount,
                'index.php?module=user-ougc_awards&amp;view=tasks&amp;action=logs'
            );

            while ($log = $db->fetch_array($query)) {
                $logID = (int)$log['tid'];

                $task = taskGet($logID);
                $user = getUser($log['uid']);

                $gave_list = $revoked_list = array();
                if (!empty($log['gave'])) {
                    foreach (explode(',', $log['gave']) as $aid) {
                        $award = awardGet($aid);
                        $gave_list[] = $award['name'];
                    }
                }
                if (!empty($log['revoked'])) {
                    foreach (explode(',', $log['revoked']) as $aid) {
                        $award = awardGet($aid);
                        $revoked_list[] = $award['name'];
                    }
                }

                !empty($gave_list) or $gave_list = array('<i>' . $lang->ougc_awards_logs_none . '</i>');
                !empty($revoked_list) or $revoked_list = array('<i>' . $lang->ougc_awards_logs_none . '</i>');

                $table->construct_cell(
                    '<a href="' . urlHandlerBuild(['action' => 'edit', 'tid' => $log['tid']]
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
            $table->output($sub_tabs['ougc_awards_tasks_logs']['title']);

            $form->output_submit_wrapper(array($form->generate_submit_button($lang->ougc_awards_logs_prune)));
            $form->end();
        }
        $page->output_footer();
    } else {
        $page->add_breadcrumb_item($lang->ougc_awards_acp_nav, urlHandlerBuild());
        $page->add_breadcrumb_item($sub_tabs['ougc_awards_tasks']['title']);
        $page->output_header($lang->ougc_awards_acp_nav);
        $page->output_nav_tabs($sub_tabs, 'ougc_awards_tasks');

        $table = new Table();
        $table->construct_header($lang->ougc_awards_form_name, array('width' => '20%'));
        $table->construct_header($lang->ougc_awards_form_desc, array('width' => '45%'));
        $table->construct_header($lang->ougc_awards_form_order, array('width' => '10%', 'class' => 'align_center'));
        $table->construct_header($lang->ougc_awards_form_active, array('width' => '10%', 'class' => 'align_center'));
        $table->construct_header($lang->ougc_awards_view_actions, array('width' => '15%', 'class' => 'align_center'));

        if ($mybb->get_input('start', MyBB::INPUT_INT) > 0) {
            $start = ($mybb->get_input('start', MyBB::INPUT_INT) - 1) * (int)getSetting('perpage');
        } else {
            $start = 0;
            $mybb->input['page'] = 1;
        }

        $query = $db->simple_select(
            'ougc_awards_tasks',
            '*',
            '',
            array('limit_start' => $start, 'limit' => (int)getSetting('perpage'), 'order_by' => 'disporder')
        );

        if (!$db->num_rows($query)) {
            $table->construct_cell(
                '<div align="center">' . $lang->ougc_awards_view_empty . '</div>',
                array('colspan' => 5)
            );
            $table->construct_row();
            $table->output($sub_tabs['ougc_awards_tasks']['title']);
        } else {
            if ($mybb->request_method == 'post' && $mybb->get_input('action') == 'updatedisporder') {
                foreach ($mybb->input['disporder'] as $tid => $disporder) {
                    taskUpdate(['disporder' => $disporder], $tid);
                }
                cacheUpdate();
                redirectAdmin();
            }

            $form = new Form(urlHandlerBuild(['action' => 'updatedisporder']), 'post');

            $query2 = $db->simple_select('ougc_awards_tasks', 'COUNT(tid) AS tasks');
            $taskcount = (int)$db->fetch_field($query2, 'tasks');

            echo draw_admin_pagination(
                $mybb->input['page'],
                (int)getSetting('perpage'),
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
            $table->output($sub_tabs['ougc_awards_tasks']['title']);

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
} elseif (true or $mybb->get_input('view') == 'category' && $category) {
    urlHandlerSet(urlHandlerBuild(['view' => 'category', 'cid' => $cid]));

    $sub_tabs['ougc_awards_view'] = array(
        'title' => $lang->ougc_awards_tab_view,
        'link' => urlHandlerBuild(),
        'description' => $lang->ougc_awards_tab_view_d
    );
    $sub_tabs['ougc_awards_add'] = array(
        'title' => $lang->ougc_awards_tab_add,
        'link' => urlHandlerBuild(['action' => 'add']),
        'description' => $lang->ougc_awards_tab_add_d,
        'align' => 'right'
    );

    switch ($mybb->get_input('action')) {
        case 'edit':
            $sub_tabs['ougc_awards_edit'] = array(
                'title' => $lang->ougc_awards_tab_edit,
                'link' => urlHandlerBuild(
                    ['action' => 'edit', 'aid' => $mybb->get_input('aid', MyBB::INPUT_INT)]
                ),
                'description' => $lang->ougc_awards_tab_edit_d,
                'align' => 'right'
            );
            break;
        case 'owners':
            $sub_tabs['ougc_awards_owners'] = array(
                'title' => $lang->ougc_awards_tab_owners,
                'link' => urlHandlerBuild(
                    ['action' => 'owners', 'aid' => $mybb->get_input('aid', MyBB::INPUT_INT)]
                ),
                'description' => $lang->ougc_awards_tab_owners_d
            );
            break;
        case 'user':
            $sub_tabs['ougc_awards_edit_user'] = array(
                'title' => $lang->ougc_awards_tab_edit_user,
                'link' => urlHandlerBuild(
                    [
                        'action' => 'user',
                        'aid' => $mybb->get_input('aid', MyBB::INPUT_INT),
                        'uid' => $mybb->get_input('uid', MyBB::INPUT_INT)
                    ]
                ),
                'description' => $lang->ougc_awards_tab_edit_user_d
            );
            break;
    }

    $page->add_breadcrumb_item($lang->ougc_awards_acp_nav, urlHandlerBuild());
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

    if ($mybb->get_input('start', MyBB::INPUT_INT) > 0) {
        $start = ($mybb->get_input('start', MyBB::INPUT_INT) - 1) * (int)getSetting('perpage');
    } else {
        $start = 0;
        $mybb->input['page'] = 1;
    }

    $query = $db->simple_select(
        'ougc_awards',
        '*',
        "cid='{$cid}'",
        array('limit_start' => $start, 'limit' => (int)getSetting('perpage'), 'order_by' => 'disporder')
    );

    if (!$db->num_rows($query)) {
        $table->construct_cell(
            '<div align="center">' . $lang->ougc_awards_view_empty . '</div>',
            array('colspan' => 6)
        );
        $table->construct_row();
        $table->output($sub_tabs['ougc_awards_view']['title']);
    } else {
        if ($mybb->request_method == 'post' && $mybb->get_input('action') == 'updatedisporder') {
            foreach ($mybb->input['disporder'] as $aid => $disporder) {
                awardUpdate(['disporder' => $disporder], $aid);
            }
            cacheUpdate();
            redirectAdmin();
        }

        $form = new Form(urlHandlerBuild(['action' => 'updatedisporder']), 'post');

        $query2 = $db->simple_select('ougc_awards', 'COUNT(aid) AS awards', "cid='{$cid}'");
        $awardscount = (int)$db->fetch_field($query2, 'awards');

        echo draw_admin_pagination(
            $mybb->input['page'],
            (int)getSetting('perpage'),
            $awardscount,
            'index.php?module=user-ougc_awards&amp;view=category&amp;cid=' . $cid . ''
        );

        while ($award = $db->fetch_array($query)) {
            $edit_link = "index.php?module=user-ougc_awards&amp;view=category&amp;cid={$cid}&amp;action=edit&amp;aid={$award['aid']}";

            $award['visible'] or $award['name'] = '<i>' . $award['name'] . '</i>';

            if ((int)$award['template'] === 1) {
                $load_fa = true;
            }

            $awardID = (int)$award['aid'];

            $awardImage = awardGetIcon($awardID);

            $awardImage = eval(getTemplate(awardGetInfo(INFORMATION_TYPE_TEMPLATE, $awardID)));

            $table->construct_cell($awardImage, array('class' => 'align_center'));
            $table->construct_cell('<a href="' . $edit_link . '">' . $award['name'] . '</a>');
            $table->construct_cell($award['description']);
            $table->construct_cell(
                $form->generate_text_box(
                    'disporder[' . $awardID . ']',
                    (int)$award['disporder'],
                    array('style' => 'text-align: center; width: 30px;')
                ),
                array('class' => 'align_center')
            );
            $table->construct_cell(
                '<img src="styles/default/images/icons/bullet_o' . (!$award['visible'] ? 'ff' : 'n') . ($mybb->version_code >= 1800 ? '.png' : '.gif') . '" alt="" title="' . (!$award['visible'] ? $lang->ougc_awards_form_hidden : $lang->ougc_awards_form_visible) . '" />',
                array('class' => 'align_center')
            );

            $popup = new PopupMenu("award_{$awardID}", $lang->options);
            $popup->add_item(
                $lang->ougc_awards_tab_owners,
                "index.php?module=user-ougc_awards&amp;view=category&amp;cid={$cid}&amp;action=owners&amp;aid={$awardID}"
            );
            $popup->add_item($lang->ougc_awards_tab_edit, $edit_link);
            $popup->add_item(
                $lang->ougc_awards_tab_delete,
                "index.php?module=user-ougc_awards&amp;view=category&amp;cid={$cid}&amp;action=delete&amp;aid={$awardID}"
            );
            $table->construct_cell($popup->fetch(), array('class' => 'align_center'));

            $table->construct_row();
        }

        if (!empty($load_fa)) {
            echo '<link href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" integrity="sha384-wvfXpqpZZVQGK6TAh5PVlGOfQNHSoD2xbE+QkPxCAFlNEevoEH3Sl0sibVcOQVnN" crossorigin="anonymous">';
        }

        $table->output($sub_tabs['ougc_awards_view']['title']);

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