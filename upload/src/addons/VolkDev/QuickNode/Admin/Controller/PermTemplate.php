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

        /** @var \XF\Repository\Permission $permissionRepo */
        $permissionRepo = $this->repository('XF:Permission');
        $permissionData = $permissionRepo->getContentPermissionListData('node');

        return $this->view('VolkDev\QuickNode:PermTemplate\Edit', 'volkdev_qnc_perm_template_edit', [
            'template' => $template,
            'nodeTree' => $nodeTree,
            'userGroups' => $userGroups,
            'permissionData' => $permissionData
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
                    if ($val === 'unset' || $val === '' || $val === '0' || $val === 0) {
                        continue;
                    }
                    if (in_array($val, ['content_allow', 'reset', 'deny'])) {
                        $cleanedPermissions[$group][$permId] = $val;
                    } else if (is_numeric($val) && intval($val) > 0) {
                        $cleanedPermissions[$group][$permId] = intval($val);
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
