<?php

namespace VolkDev\QuickNode\Admin\Controller;

use XF\Admin\Controller\AbstractController;
use XF\Mvc\ParameterBag;
use XF\Mvc\FormAction;

class PermTemplate extends AbstractController
{
    protected function preDispatchController($action, ParameterBag $params)
    {
        $this->assertAdminPermission('node');
    }

    public function actionIndex()
    {
        /** @var \VolkDev\QuickNode\Repository\PermTemplate $repo */
        $repo = $this->repository('VolkDev\QuickNode:PermTemplate');
        $templates = $repo->findTemplatesForList()->fetch();

        return $this->view('VolkDev\QuickNode:PermTemplate\Index', 'volkdev_qnc_perm_template_list', [
            'templates' => $templates
        ]);
    }

    protected function templateAddEdit(\VolkDev\QuickNode\Entity\PermTemplate $template)
    {
        /** @var \XF\Repository\Node $nodeRepo */
        $nodeRepo = $this->repository('XF:Node');
        $nodeTree = $nodeRepo->createNodeTree($nodeRepo->getFullNodeList());

        $userGroups = $this->finder('XF:UserGroup')->order('title')->fetch();

        $availablePermissions = [
            'general' => [
                'title' => \XF::phrase('volkdev_qnc_perm_group_general'),
                'permissions' => [
                    'viewNode' => \XF::phrase('volkdev_qnc_perm_view'),
                ]
            ],
            'forum' => [
                'title' => \XF::phrase('volkdev_qnc_perm_group_forum'),
                'permissions' => [
                    'viewOthers' => \XF::phrase('volkdev_qnc_perm_view_others'),
                    'viewContent' => \XF::phrase('volkdev_qnc_perm_view_content'),
                    'postThread' => \XF::phrase('volkdev_qnc_perm_post_thread'),
                    'postReply' => \XF::phrase('volkdev_qnc_perm_post_reply'),
                    'deleteOwnPost' => \XF::phrase('volkdev_qnc_perm_delete_own_post'),
                    'editOwnPost' => \XF::phrase('volkdev_qnc_perm_edit_own_post'),
                    'uploadAttachment' => \XF::phrase('volkdev_qnc_perm_upload_attachment'),
                    'react' => \XF::phrase('volkdev_qnc_perm_react'),
                    'manageAnyThread' => \XF::phrase('volkdev_qnc_perm_manage_any_thread'),
                    'deleteAnyThread' => \XF::phrase('volkdev_qnc_perm_delete_any_thread'),
                    'deleteAny' => \XF::phrase('volkdev_qnc_perm_delete_any_post'),
                    'lockUnlockThread' => \XF::phrase('volkdev_qnc_perm_lock_unlock'),
                    'stickUnstickThread' => \XF::phrase('volkdev_qnc_perm_stick_unstick'),
                    'inlineMod' => \XF::phrase('volkdev_qnc_perm_inline_mod'),
                    'editAnyPost' => \XF::phrase('volkdev_qnc_perm_edit_any_post'),
                    'warn' => \XF::phrase('volkdev_qnc_perm_warn'),
                    'viewDeleted' => \XF::phrase('volkdev_qnc_perm_view_deleted'),
                    'viewModerated' => \XF::phrase('volkdev_qnc_perm_view_moderated'),
                    'undelete' => \XF::phrase('volkdev_qnc_perm_undelete'),
                    'approveUnapprove' => \XF::phrase('volkdev_qnc_perm_approve_unapprove'),
                    'hardDeleteAnyThread' => \XF::phrase('volkdev_qnc_perm_hard_delete_thread'),
                ]
            ]
        ];

        return $this->view('VolkDev\QuickNode:PermTemplate\Edit', 'volkdev_qnc_perm_template_edit', [
            'template' => $template,
            'nodeTree' => $nodeTree,
            'userGroups' => $userGroups,
            'availablePermissions' => $availablePermissions
        ]);
    }

    public function actionEdit(ParameterBag $params)
    {
        $templateId = $params->template_id ?: $this->filter('template_id', 'uint');
        $template = $this->assertRecordExists('VolkDev\QuickNode:PermTemplate', $templateId);
        return $this->templateAddEdit($template);
    }

    public function actionAdd()
    {
        /** @var \VolkDev\QuickNode\Entity\PermTemplate $template */
        $template = $this->em()->create('VolkDev\QuickNode:PermTemplate');
        return $this->templateAddEdit($template);
    }

    protected function templateSaveProcess(\VolkDev\QuickNode\Entity\PermTemplate $template): FormAction
    {
        $form = $this->formAction();

        $input = $this->filter([
            'title' => 'str',
            'description' => 'str',
            'display_order' => 'uint',
            'active' => 'bool',
            'admin_only' => 'bool',
            'node_ids' => 'array-uint',
            'user_group_scope' => 'str',
            'user_group_ids' => 'array-uint',
            'permissions' => 'array'
        ]);

        $cleanedPermissions = [];
        if (!empty($input['permissions']) && is_array($input['permissions'])) {
            foreach ($input['permissions'] as $group => $perms) {
                if (!is_array($perms)) continue;
                foreach ($perms as $permId => $val) {
                    if (in_array($val, ['content_allow', 'reset', 'deny'])) {
                        if ($val !== 'reset') {
                            $cleanedPermissions[$group][$permId] = $val;
                        }
                    }
                }
            }
        }

        $nodeIds = array_values(array_filter($input['node_ids']));
        $nodeScope = !empty($nodeIds) ? 'selected' : 'all';

        $form->basicEntitySave($template, [
            'title' => $input['title'],
            'description' => $input['description'],
            'display_order' => $input['display_order'],
            'active' => $input['active'],
            'admin_only' => $input['admin_only'],
            'node_scope' => $nodeScope,
            'node_ids' => $nodeIds,
            'user_group_scope' => in_array($input['user_group_scope'], ['all', 'selected']) ? $input['user_group_scope'] : 'all',
            'user_group_ids' => $input['user_group_scope'] === 'selected' ? array_values(array_filter($input['user_group_ids'])) : [],
            'permissions' => $cleanedPermissions,
        ]);

        return $form;
    }

    public function actionSave(ParameterBag $params)
    {
        $this->assertPostOnly();

        $templateId = $params->template_id ?: $this->filter('template_id', 'uint');
        if ($templateId) {
            $template = $this->assertRecordExists('VolkDev\QuickNode:PermTemplate', $templateId);
        } else {
            $template = $this->em()->create('VolkDev\QuickNode:PermTemplate');
        }

        $this->templateSaveProcess($template)->run();

        return $this->redirect($this->buildLink('qnc-perm-templates'));
    }

    public function actionDelete(ParameterBag $params)
    {
        $templateId = $params->template_id ?: $this->filter('template_id', 'uint');
        $template = $this->assertRecordExists('VolkDev\QuickNode:PermTemplate', $templateId);

        /** @var \XF\ControllerPlugin\Delete $plugin */
        $plugin = $this->plugin('XF:Delete');
        return $plugin->actionDelete(
            $template,
            $this->buildLink('qnc-perm-templates/delete', $template),
            $this->buildLink('qnc-perm-templates/edit', $template),
            $this->buildLink('qnc-perm-templates'),
            $template->title
        );
    }

    public function actionToggle()
    {
        /** @var \XF\ControllerPlugin\Toggle $plugin */
        $plugin = $this->plugin('XF:Toggle');
        return $plugin->actionToggle('VolkDev\QuickNode:PermTemplate', 'active');
    }
}
