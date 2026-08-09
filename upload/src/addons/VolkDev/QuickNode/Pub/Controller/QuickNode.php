<?php

namespace VolkDev\QuickNode\Pub\Controller;

use XF\Pub\Controller\AbstractController;
use XF\Mvc\ParameterBag;

class QuickNode extends AbstractController
{
    protected function hasQuickNodeAccess($nodeId, $permission = 'canManageQuickNode')
    {
        if (!$nodeId) {
            return false;
        }

        $node = $this->em()->find('XF:Node', $nodeId);
        if (!$node) {
            return false;
        }

        return $this->repository('VolkDev\QuickNode:NodePerms')->hasAccess($node, $permission);
    }

    protected function assertQuickNodeAccess($nodeId, $permission = 'canManageQuickNode')
    {
        if (!$this->hasQuickNodeAccess($nodeId, $permission)) {
            throw $this->exception($this->noPermission());
        }
    }


    public function actionIndex(ParameterBag $params)
    {
        $parentNodeId = $this->filter('parent_node_id', 'uint');
        $this->assertQuickNodeAccess($parentNodeId);

        $nodeRepo = $this->repository('XF:Node');
        $allNodeOptions = $nodeRepo->getNodeOptionsData(true, ['Forum', 'Category']);

        $nodeOptions = [];
        foreach ($allNodeOptions as $nodeOption) {
            $nodeId = $nodeOption['value'];
            if ($nodeId == 0 || $nodeId == $parentNodeId || $this->hasQuickNodeAccess($nodeId, 'canManageQuickNode')) {
                $nodeOptions[] = $nodeOption;
            }
        }

        $prefixRepo = $this->repository('XF:ThreadPrefix');
        $prefixListData = $prefixRepo->getPrefixListData();

        return $this->view('VolkDev\QuickNode:QuickNode\Index', 'volkdev_qn_index', [
            'parentNodeId' => $parentNodeId,
            'nodeOptions' => $nodeOptions,
            'prefixGroups' => $prefixListData['prefixGroups'],
            'prefixesGrouped' => $prefixListData['prefixesGrouped'],
        ]);
    }

    public function actionSave()
    {
        $this->assertPostOnly();
        $this->assertRegistrationRequired();

        $input = $this->filter([
            'node' => [
                'title' => 'str',
                'description' => 'str',
                'node_type_id' => 'str',
                'parent_node_id' => 'uint',
                'display_order' => 'uint'
            ],
            'allow_posting' => 'bool',
            'is_private' => 'bool',
            'available_prefixes' => 'array-uint'
        ]);

        $this->assertQuickNodeAccess($input['node']['parent_node_id']);

        $node = $this->em()->create('XF:Node');
        $node->bulkSet($input['node']);
        $node->display_in_list = 1;

        if (!in_array($node->node_type_id, ['Forum', 'Category', 'LinkForum'])) {
            return $this->error(\XF::phrase('please_enter_valid_value'));
        }

        $typeData = $node->getDataRelationOrDefault();
        if ($input['node']['node_type_id'] == 'Forum') {
            $typeData->allow_posting = $input['allow_posting'];
        } elseif ($input['node']['node_type_id'] == 'LinkForum') {
            $linkUrl = $this->filter('link_url', 'str');
            if (preg_match('#^\s*(javascript|data|vbscript):#i', $linkUrl)) {
                return $this->error(\XF::phrase('please_enter_valid_url'));
            }
            $typeData->link_url = $linkUrl;
        }
        $node->addCascadedSave($typeData);
        $node->save();

        if ($input['node']['node_type_id'] == 'Forum' && !empty($input['available_prefixes'])) {
            foreach ($input['available_prefixes'] as $prefixId) {
                $forumPrefix = $this->em()->create('XF:ForumPrefix');
                $forumPrefix->node_id = $node->node_id;
                $forumPrefix->prefix_id = $prefixId;
                $forumPrefix->save();
            }
        }

        if ($input['is_private']) {
            /** @var \VolkDev\QuickNode\Service\NodePrivacy $privacyService */
            $privacyService = $this->service('VolkDev\QuickNode:NodePrivacy');
            $privacyService->makePrivate($node->node_id);
        }

        $newData = $node->toArray();
        if ($node->node_type_id == 'Forum') {
            $newData['allow_posting'] = $input['allow_posting'];
        }
        $newData['is_private'] = $input['is_private'];

        /** @var \VolkDev\QuickNode\Service\LogCreator $logCreator */
        $logCreator = $this->service('VolkDev\QuickNode:LogCreator');
        $logCreator->logAction(\XF::visitor()->user_id, $node->node_id, 'create', null, $newData);

        return $this->redirect($this->getDynamicRedirect(), \XF::phrase('volkdev_qnc_node_created'));
    }

    public function actionEdit()
    {
        $this->assertRegistrationRequired();

        $nodeId = $this->filter('node_id', 'uint');
        $node = $this->assertRecordExists('XF:Node', $nodeId);
        $this->assertQuickNodeAccess($node->node_id, 'canEditQuickNode');

        /** @var \VolkDev\QuickNode\Service\NodePrivacy $privacyService */
        $privacyService = $this->service('VolkDev\QuickNode:NodePrivacy');
        $isPrivate = $privacyService->isPrivate($node->node_id);

        $nodeRepo = $this->repository('XF:Node');
        $allNodeOptions = $nodeRepo->getNodeOptionsData(true, ['Forum', 'Category']);

        $nodeOptions = [];
        foreach ($allNodeOptions as $nodeOption) {
            $nodeId = $nodeOption['value'];
            if ($nodeId == 0 || $nodeId == $node->node_id || $nodeId == $node->parent_node_id || $this->hasQuickNodeAccess($nodeId, 'canManageQuickNode')) {
                $nodeOptions[] = $nodeOption;
            }
        }

        $prefixRepo = $this->repository('XF:ThreadPrefix');
        $prefixListData = $prefixRepo->getPrefixListData();
        $availablePrefixes = [];
        if ($node->node_type_id == 'Forum') {
            $availablePrefixes = $this->finder('XF:ForumPrefix')->where('node_id', $node->node_id)->pluckFrom('prefix_id')->fetch()->toArray();
        }

        if ($this->isPost()) {
            $oldData = $node->toArray();
            if ($node->node_type_id == 'Forum') {
                $oldData['allow_posting'] = $node->getDataRelationOrDefault()->allow_posting;
                $oldData['available_prefixes'] = $availablePrefixes;
            } elseif ($node->node_type_id == 'LinkForum') {
                $oldData['link_url'] = $node->getDataRelationOrDefault()->link_url;
            }
            $oldData['is_private'] = $isPrivate;
            
            $input = $this->filter([
                'node' => ['title' => 'str', 'description' => 'str', 'parent_node_id' => 'uint', 'display_order' => 'uint'],
                'allow_posting' => 'bool',
                'is_private' => 'bool',
                'available_prefixes' => 'array-uint'
            ]);

            if ($input['node']['parent_node_id'] != $node->parent_node_id) {
                $this->assertQuickNodeAccess($input['node']['parent_node_id']);
            }

            $node->bulkSet($input['node']);

            if ($node->node_type_id == 'Forum') {
                $typeData = $node->getDataRelationOrDefault();
                $typeData->allow_posting = $input['allow_posting'];
                $node->addCascadedSave($typeData);

                $db = $this->app()->db();
                $db->delete('xf_forum_prefix', 'node_id = ?', $node->node_id);
                foreach ($input['available_prefixes'] as $prefixId) {
                    $forumPrefix = $this->em()->create('XF:ForumPrefix');
                    $forumPrefix->node_id = $node->node_id;
                    $forumPrefix->prefix_id = $prefixId;
                    $forumPrefix->save();
                }
            } elseif ($node->node_type_id == 'LinkForum') {
                $typeData = $node->getDataRelationOrDefault();
                $linkUrl = $this->filter('link_url', 'str');
                if (preg_match('#^\s*(javascript|data|vbscript):#i', $linkUrl)) {
                    return $this->error(\XF::phrase('please_enter_valid_url'));
                }
                $typeData->link_url = $linkUrl;
                $node->addCascadedSave($typeData);
            }

            $node->save();

            if ($input['is_private']) {
                $privacyService->makePrivate($node->node_id);
            } else {
                if ($isPrivate) {
                    $privacyService->makePublic($node->node_id);
                }
            }

            $newData = $node->toArray();
            if ($node->node_type_id == 'Forum') {
                $newData['allow_posting'] = $input['allow_posting'];
            }
            $newData['is_private'] = $input['is_private'];

            /** @var \VolkDev\QuickNode\Service\LogCreator $logCreator */
            $logCreator = $this->service('VolkDev\QuickNode:LogCreator');
            $logCreator->logAction(\XF::visitor()->user_id, $node->node_id, 'edit', $oldData, $newData);

            return $this->redirect($this->getDynamicRedirect(), \XF::phrase('volkdev_qnc_node_updated'));
        }

        $allowPosting = true;
        $linkUrl = '';
        if ($node->node_type_id == 'Forum') {
            $allowPosting = $node->getDataRelationOrDefault()->allow_posting;
        } elseif ($node->node_type_id == 'LinkForum') {
            $linkUrl = $node->getDataRelationOrDefault()->link_url;
        }

        return $this->view('VolkDev\QuickNode:QuickNode\Edit', 'volkdev_qn_edit', [
            'node' => $node,
            'allowPosting' => $allowPosting,
            'linkUrl' => $linkUrl,
            'isPrivate' => $isPrivate,
            'nodeOptions' => $nodeOptions,
            'prefixGroups' => $prefixListData['prefixGroups'],
            'prefixesGrouped' => $prefixListData['prefixesGrouped'],
            'availablePrefixes' => $availablePrefixes
        ]);
    }

    public function actionDelete()
    {
        $this->assertRegistrationRequired();

        $nodeId = $this->filter('node_id', 'uint');
        $node = $this->assertRecordExists('XF:Node', $nodeId);
        $this->assertQuickNodeAccess($node->node_id, 'canDeleteQuickNode');

        /** @var \VolkDev\QuickNode\Service\NodePrivacy $privacyService */
        $privacyService = $this->service('VolkDev\QuickNode:NodePrivacy');

        if ($this->isPost()) {
            $oldData = $node->toArray();
            if ($node->node_type_id == 'Forum') {
                $oldData['allow_posting'] = $node->getDataRelationOrDefault()->allow_posting;
            }

            $visitor = \XF::visitor();
            
            /** @var \VolkDev\QuickNode\Service\LogCreator $logCreator */
            $logCreator = $this->service('VolkDev\QuickNode:LogCreator');

            // Save current viewNode permissions before making private
            $oldEntries = $this->finder('XF:PermissionEntryContent')
                ->where('content_type', 'node')
                ->where('content_id', $node->node_id)
                ->where('permission_group_id', 'general')
                ->where('permission_id', 'viewNode')
                ->fetch();
                
            $oldPerms = [];
            foreach ($oldEntries as $entry) {
                $oldPerms[] = [
                    'user_group_id' => $entry->user_group_id,
                    'user_id' => $entry->user_id,
                    'permission_value' => $entry->permission_value,
                ];
            }
            $oldData['view_perms'] = $oldPerms;
            $oldData['was_private'] = $privacyService->isPrivate($node->node_id);

            // Mark as pending delete and make private
            $node->qnc_pending_delete = 1;
            $node->save();

            $privacyService->makePrivate($node->node_id);

            $logEntity = $logCreator->logAction($visitor->user_id, $nodeId, 'pending_delete', $oldData, null);

            if ($logEntity) {
                $reportCreator = $this->app()->service('XF:Report\Creator', 'volkdev_qnc_log', $logEntity);
                $reportCreator->setMessage(\XF::phrase('volkdev_qnc_delete_request_title', ['title' => $node->title]));
                $reportCreator->save();
            }

            $redirect = $this->buildLink('forums');
            if ($node->Parent) {
                $redirect = $this->buildLink($node->Parent->getRoute(), $node->Parent);
            }
            return $this->redirect($redirect, \XF::phrase('volkdev_qnc_delete_requested'));
        }

        return $this->view('VolkDev\QuickNode:QuickNode\Delete', 'volkdev_qn_delete', ['node' => $node]);
    }

    public function actionPermissions()
    {
        $this->assertRegistrationRequired();

        $nodeId = $this->filter('node_id', 'uint');
        $this->assertQuickNodeAccess($nodeId, 'canManageQuickNodePerms');
        $node = $this->assertRecordExists('XF:Node', $nodeId);
        $visitor = \XF::visitor();

        $protectedRaw = \XF::options()->qnc_protected_groups ?? '';
        $protectedIds = array_filter(array_map('intval', explode(',', $protectedRaw)));

        $groupFinder = $this->finder('XF:UserGroup')->order('title');

        if (!$visitor->is_admin && !empty($protectedIds)) {
            $groupFinder->where('user_group_id', '<>', $protectedIds);
        }

        $viewParams = [
            'node' => $node,
            'groups' => $groupFinder->fetch()
        ];

        return $this->view('VolkDev\QuickNode:QuickNode\Permissions', 'volkdev_qn_permissions_list', $viewParams);
    }

    public function actionPermissionsEdit()
    {
        $this->assertRegistrationRequired();

        $nodeId = $this->filter('node_id', 'uint');
        $this->assertQuickNodeAccess($nodeId, 'canManageQuickNodePerms');
        $groupId = $this->filter('user_group_id', 'uint');

        $node = $this->assertRecordExists('XF:Node', $nodeId);
        $targetGroup = $this->assertRecordExists('XF:UserGroup', $groupId);

        $visitor = \XF::visitor();

        $protectedRaw = \XF::options()->qnc_protected_groups ?? '';
        $protectedIds = array_filter(array_map('intval', explode(',', $protectedRaw)));

        if (!$visitor->is_admin && ($targetGroup->qnc_protected || in_array($targetGroup->user_group_id, $protectedIds))) {
            throw $this->exception($this->noPermission(\XF::phrase('volkdev_qnc_protected_group')));
        }

        if ($this->isPost()) {
            $input = $this->filter([
                'view' => 'str',
                'post' => 'str',
                'is_mod' => 'bool',
                'is_admin' => 'bool'
            ]);

            // Block moderator/admin permissions for Unregistered (1) and Registered (2) groups
            if (in_array($groupId, [1, 2]) && ($input['is_mod'] || $input['is_admin'])) {
                return $this->error(\XF::phrase('volkdev_qnc_cannot_give_mod_to_base_groups'));
            }

            $permissions = [];

            if (in_array($input['view'], ['content_allow', 'reset', 'deny'])) {
                $permissions['general']['viewNode'] = $input['view'];
                $permissions['forum']['viewOthers'] = $input['view'];
                $permissions['forum']['viewContent'] = $input['view'];
            }

            if (in_array($input['post'], ['content_allow', 'reset', 'deny'])) {
                $permissions['forum']['postThread'] = $input['post'];
                $permissions['forum']['postReply'] = $input['post'];
            }

            $modPerm = $input['is_mod'] || $input['is_admin'] ? 'content_allow' : 'reset';
            $permissions['forum']['manageAnyThread'] = $modPerm; 
            $permissions['forum']['deleteAnyThread'] = $modPerm; 
            $permissions['forum']['deleteAny'] = $modPerm; 
            $permissions['forum']['lockUnlockThread'] = $modPerm; 
            $permissions['forum']['stickUnstickThread'] = $modPerm; 
            $permissions['forum']['inlineMod'] = $modPerm; 

            $adminPerm = $input['is_admin'] ? 'content_allow' : 'reset';
            $permissions['forum']['editAnyPost'] = $adminPerm; 
            $permissions['forum']['warn'] = $adminPerm; 
            $permissions['forum']['viewDeleted'] = $adminPerm; 
            $permissions['forum']['viewModerated'] = $adminPerm; 
            $permissions['forum']['undelete'] = $adminPerm; 
            $permissions['forum']['approveUnapprove'] = $adminPerm; 

            $oldEntries = $this->finder('XF:PermissionEntryContent')
                ->where('content_type', 'node')
                ->where('content_id', $node->node_id)
                ->where('user_group_id', $groupId)
                ->where('user_id', 0)
                ->fetch();

            $oldPerms = [];
            foreach ($oldEntries AS $entry) {
                $oldPerms[$entry->permission_group_id][$entry->permission_id] = $entry->permission_value;
            }

            $updater = $this->service('XF:UpdatePermissions');
            $updater->setContent('node', $node->node_id);
            $updater->setUserGroup($targetGroup);
            $updater->updatePermissions($permissions);

            /** @var \VolkDev\QuickNode\Service\LogCreator $logCreator */
            $logCreator = $this->service('VolkDev\QuickNode:LogCreator');
            $logCreator->logAction(
                $visitor->user_id,
                $node->node_id,
                'perm_change',
                ['group_id' => $groupId, 'perms' => $oldPerms, 'node_title' => $node->title],
                ['group_id' => $groupId, 'perms' => $permissions, 'node_title' => $node->title]
            );

            return $this->redirect(
                $this->buildLink('quick-node/permissions', null, ['node_id' => $node->node_id]), 
                \XF::phrase('volkdev_qnc_permissions_updated')
            );
        }

        $entries = $this->finder('XF:PermissionEntryContent')
            ->where('content_type', 'node')
            ->where('content_id', $node->node_id)
            ->where('user_group_id', $groupId)
            ->where('user_id', 0)
            ->fetch();

        $currentPerms = [];
        foreach ($entries AS $entry) {
            $currentPerms[$entry->permission_group_id][$entry->permission_id] = $entry->permission_value;
        }

        $viewState = $currentPerms['general']['viewNode'] ?? 'reset';
        $postState = $currentPerms['forum']['postThread'] ?? 'reset';
        $isMod = (!empty($currentPerms['forum']['manageAnyThread']) && $currentPerms['forum']['manageAnyThread'] === 'content_allow');
        $isAdmin = (!empty($currentPerms['forum']['editAnyPost']) && $currentPerms['forum']['editAnyPost'] === 'content_allow');

        return $this->view('VolkDev\QuickNode:QuickNode\PermissionsEdit', 'volkdev_qn_permissions_edit', [
            'node' => $node,
            'userGroup' => $targetGroup,
            'viewState' => $viewState,
            'postState' => $postState,
            'isMod' => $isMod,
            'isAdmin' => $isAdmin
        ]);
    }
}