<?php
namespace VolkDev\QuickNode\Service;

use XF\Service\AbstractService;

class NodePrivacy extends AbstractService
{
    public function makePrivate(int $nodeId): void
    {
        $db = \XF::db();
        
        // 1. Global reset (hide from everyone by default)
        $db->insert('xf_permission_entry_content', [
            'content_type' => 'node',
            'content_id' => $nodeId,
            'user_group_id' => 0,
            'user_id' => 0,
            'permission_group_id' => 'general',
            'permission_id' => 'viewNode',
            'permission_value' => 'reset',
            'permission_value_int' => 0
        ], false, 'permission_value = VALUES(permission_value), permission_value_int = VALUES(permission_value_int)');

        // 2. Allow special groups (anti-hide)
        $specialGroupsRaw = \XF::options()->qnc_private_allowed_groups ?? '';
        $specialGroupIds = array_filter(array_map('intval', explode(',', $specialGroupsRaw)));

        foreach ($specialGroupIds as $spId) {
            $db->insert('xf_permission_entry_content', [
                'content_type' => 'node',
                'content_id' => $nodeId,
                'user_group_id' => $spId,
                'user_id' => 0,
                'permission_group_id' => 'general',
                'permission_id' => 'viewNode',
                'permission_value' => 'content_allow',
                'permission_value_int' => 0
            ], false, 'permission_value = VALUES(permission_value), permission_value_int = VALUES(permission_value_int)');
        }

        // 3. Allow the creator to see and manage the node
        $visitor = \XF::visitor();
        if ($visitor->user_id) {
            $creatorPerms = [
                ['general', 'viewNode', 'content_allow'],
                ['forum', 'editAnyPost', 'content_allow'],
                ['forum', 'deleteAnyPost', 'content_allow'],
                ['forum', 'deleteAnyThread', 'content_allow'],
                ['forum', 'manageAnyThread', 'content_allow'],
                ['forum', 'lockUnlockThread', 'content_allow'],
                ['forum', 'stickUnstickThread', 'content_allow'],
                ['forum', 'warn', 'content_allow'],
                ['forum', 'approveUnapprove', 'content_allow'],
            ];
            
            $inserts = [];
            foreach ($creatorPerms as [$groupId, $permId, $value]) {
                $inserts[] = [
                    'content_type' => 'node',
                    'content_id' => $nodeId,
                    'user_group_id' => 0,
                    'user_id' => $visitor->user_id,
                    'permission_group_id' => $groupId,
                    'permission_id' => $permId,
                    'permission_value' => $value,
                    'permission_value_int' => 0
                ];
            }
            $db->insertBulk('xf_permission_entry_content', $inserts, false,
                'permission_value = VALUES(permission_value), permission_value_int = VALUES(permission_value_int)');
        }
        
        // Single async permission rebuild
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
