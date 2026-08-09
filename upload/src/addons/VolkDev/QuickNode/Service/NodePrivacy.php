<?php
namespace VolkDev\QuickNode\Service;

use XF\Service\AbstractService;

class NodePrivacy extends AbstractService
{
    public function makePrivate(int $nodeId): void
    {
        $visitor = \XF::visitor();

        // 1. Global reset for everyone — hide by default via UpdatePermissions with user_group_id = 0
        // We use UpdatePermissions service for the special allowed groups and creator,
        // but the "global reset" entry (group_id=0, user_id=0) must be inserted directly
        // since XF:UpdatePermissions doesn't support the wildcard group 0 entry.
        $db = \XF::db();
        $db->insert('xf_permission_entry_content', [
            'content_type'       => 'node',
            'content_id'         => $nodeId,
            'user_group_id'      => 0,
            'user_id'            => 0,
            'permission_group_id'=> 'general',
            'permission_id'      => 'viewNode',
            'permission_value'   => 'reset',
            'permission_value_int' => 0
        ], false, 'permission_value = VALUES(permission_value), permission_value_int = VALUES(permission_value_int)');

        // 2. Allow special groups (anti-hide) using UpdatePermissions
        $specialGroupsRaw = \XF::options()->qnc_private_allowed_groups ?? [];
        $specialGroupIds = is_array($specialGroupsRaw)
            ? array_filter(array_map('intval', $specialGroupsRaw))
            : array_filter(array_map('intval', explode(',', (string)$specialGroupsRaw)));

        foreach ($specialGroupIds as $spId)
        {
            $group = $this->em()->find('XF:UserGroup', $spId);
            if ($group) {
                $updater = \XF::app()->service('XF:UpdatePermissions');
                $updater->setContent('node', $nodeId);
                $updater->setUserGroup($group);
                $updater->updatePermissions(['general' => ['viewNode' => 'content_allow']]);
            }
        }

        // 3. Allow the creator to see and manage the node using UpdatePermissions
        if ($visitor->user_id)
        {
            $creatorPerms = [
                'general' => ['viewNode' => 'content_allow'],
                'forum'   => [
                    'editAnyPost'      => 'content_allow',
                    'deleteAnyPost'    => 'content_allow',
                    'deleteAnyThread'  => 'content_allow',
                    'manageAnyThread'  => 'content_allow',
                    'lockUnlockThread' => 'content_allow',
                    'stickUnstickThread' => 'content_allow',
                    'warn'             => 'content_allow',
                    'approveUnapprove' => 'content_allow',
                ]
            ];

            $updater = \XF::app()->service('XF:UpdatePermissions');
            $updater->setContent('node', $nodeId);
            $updater->setUser($visitor);
            $updater->updatePermissions($creatorPerms);
        }
    }

    public function makePublic(int $nodeId): void
    {
        // Remove the global "reset" entry that hides the node
        $resetEntry = $this->finder('XF:PermissionEntryContent')
            ->where('content_type', 'node')
            ->where('content_id', $nodeId)
            ->where('user_group_id', 0)
            ->where('user_id', 0)
            ->where('permission_group_id', 'general')
            ->where('permission_id', 'viewNode')
            ->where('permission_value', 'reset')
            ->fetchOne();

        if ($resetEntry)
        {
            $resetEntry->delete();
        }

        // Remove the allowed-group entries via UpdatePermissions (set to reset)
        $specialGroupsRaw = \XF::options()->qnc_private_allowed_groups ?? [];
        $specialGroupIds = is_array($specialGroupsRaw)
            ? array_filter(array_map('intval', $specialGroupsRaw))
            : array_filter(array_map('intval', explode(',', (string)$specialGroupsRaw)));

        foreach ($specialGroupIds as $spId)
        {
            $group = $this->em()->find('XF:UserGroup', $spId);
            if ($group) {
                $updater = \XF::app()->service('XF:UpdatePermissions');
                $updater->setContent('node', $nodeId);
                $updater->setUserGroup($group);
                $updater->updatePermissions(['general' => ['viewNode' => 'reset']]);
            }
        }
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
