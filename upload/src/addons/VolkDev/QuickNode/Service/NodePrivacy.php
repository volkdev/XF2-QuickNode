<?php
namespace VolkDev\QuickNode\Service;

use XF\Service\AbstractService;

class NodePrivacy extends AbstractService
{
    public function makePrivate(int $nodeId): void
    {
        \XF::db()->insert('xf_permission_entry_content', [
            'content_type' => 'node',
            'content_id' => $nodeId,
            'user_group_id' => 0,
            'user_id' => 0,
            'permission_group_id' => 'general',
            'permission_id' => 'viewNode',
            'permission_value' => 'reset',
            'permission_value_int' => 0
        ], false, 'permission_value = VALUES(permission_value), permission_value_int = VALUES(permission_value_int)');

        $specialGroupsRaw = \XF::options()->qnc_private_allowed_groups ?? '';
        $specialGroupIds = array_filter(array_map('intval', explode(',', $specialGroupsRaw)));

        foreach ($specialGroupIds as $spId) {
            $spGroup = $this->em()->find('XF:UserGroup', $spId);
            if ($spGroup) {
                $spUpdater = \XF::app()->service('XF:UpdatePermissions');
                $spUpdater->setContent('node', $nodeId);
                $spUpdater->setUserGroup($spGroup);
                $spUpdater->updatePermissions(['general' => ['viewNode' => 'content_allow']]);
            }
        }

        $visitor = \XF::visitor();
        if ($visitor->user_id) {
            $updater = \XF::app()->service('XF:UpdatePermissions');
            $updater->setContent('node', $nodeId);
            $updater->setUser($visitor);
            $updater->updatePermissions([
                'general' => [
                    'viewNode' => 'content_allow'
                ],
                'forum' => [
                    'editAnyPost' => 'content_allow',
                    'deleteAnyPost' => 'content_allow',
                    'deleteAnyThread' => 'content_allow',
                    'manageAnyThread' => 'content_allow',
                    'lockUnlockThread' => 'content_allow',
                    'stickUnstickThread' => 'content_allow',
                    'warn' => 'content_allow',
                    'approveUnapprove' => 'content_allow'
                ]
            ]);
        }
        
        \XF::app()->jobManager()->enqueueUnique('permissionRebuild', 'XF:PermissionRebuild');
    }

    public function makePublic(int $nodeId): void
    {
        $resetEntry = $this->finder('XF:PermissionEntryContent')
            ->where('content_type', 'node')
            ->where('content_id', $nodeId)
            ->where('user_group_id', 0)
            ->where('user_id', 0)
            ->where('permission_group_id', 'general')
            ->where('permission_id', 'viewNode')
            ->where('permission_value', 'reset')
            ->fetchOne();

        if ($resetEntry) {
            $resetEntry->delete();
        }

        $specialGroupsRaw = \XF::options()->qnc_private_allowed_groups ?? '';
        $specialGroupIds = array_filter(array_map('intval', explode(',', $specialGroupsRaw)));

        if (!empty($specialGroupIds)) {
            $allowEntries = $this->finder('XF:PermissionEntryContent')
                ->where('content_type', 'node')
                ->where('content_id', $nodeId)
                ->where('user_group_id', $specialGroupIds)
                ->where('user_id', 0)
                ->where('permission_group_id', 'general')
                ->where('permission_id', 'viewNode')
                ->where('permission_value', 'content_allow')
                ->fetch();

            foreach ($allowEntries as $entry) {
                $entry->delete();
            }
        }
        
        \XF::app()->jobManager()->enqueueUnique('permissionRebuild', 'XF:PermissionRebuild');
    }

    public function isPrivate(int $nodeId): bool
    {
        $privateEntry = $this->finder('XF:PermissionEntryContent')
            ->where('content_type', 'node')
            ->where('content_id', $nodeId)
            ->where('user_group_id', 0)
            ->where('user_id', 0)
            ->where('permission_group_id', 'general')
            ->where('permission_id', 'viewNode')
            ->where('permission_value', 'reset')
            ->fetchOne();

        return (bool) $privateEntry;
    }
}
