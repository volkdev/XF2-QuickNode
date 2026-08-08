<?php

namespace VolkDev\QuickNode\Repository;

use XF\Mvc\Entity\Repository;
use XF\Mvc\Entity\Entity;
use XF\Entity\Node;

class NodePerms extends Repository
{
    public function hasAccess(Entity $entity, $permission = 'canManageQuickNode')
    {
        if ($entity instanceof Node) {
            $node = $entity;
        } elseif (isset($entity->Node)) {
            $node = $entity->Node;
        } else {
            return false;
        }

        $visitor = \XF::visitor();

        if (!in_array($node->node_type_id, ['Category', 'LinkForum'])) {
            return $visitor->hasNodePermission($node->node_id, $permission);
        }

        $hasPerm = $visitor->hasPermission('forum', $permission);

        $nodeIds = [];
        $breadcrumbs = $node->breadcrumb_data;
        if ($breadcrumbs) {
            foreach ($breadcrumbs as $crumb) {
                $nodeIds[] = $crumb['node_id'];
            }
        }
        $nodeIds[] = $node->node_id;
        
        $db = $this->app()->db();
        $groupIds = $visitor->secondary_group_ids;
        $groupIds[] = $visitor->user_group_id;

        $groupIn = implode(',', array_map('intval', $groupIds));
        $nodeIn = implode(',', array_map('intval', $nodeIds));

        $entries = $db->fetchAll("
            SELECT content_id, permission_value, user_id, user_group_id
            FROM xf_permission_entry_content 
            WHERE content_type = 'node' 
              AND content_id IN ($nodeIn)
              AND permission_group_id = 'forum' 
              AND permission_id = ? 
              AND (user_group_id IN ($groupIn) OR user_id = ?)
        ", [$permission, $visitor->user_id]);

        if (!$entries) {
            return $hasPerm;
        }

        $finalPerm = $hasPerm;
        
        foreach ($nodeIds as $nId) {
            $nodeEntries = array_filter($entries, function($e) use ($nId) {
                return $e['content_id'] == $nId;
            });

            if (!$nodeEntries) continue;

            $nodeHasAllow = false;
            $nodeHasDeny = false;
            $nodeHasReset = false;

            $userEntries = array_filter($nodeEntries, function($e) { return $e['user_id'] > 0; });
            $groupEntries = array_filter($nodeEntries, function($e) { return $e['user_id'] == 0; });

            if (!empty($userEntries)) {
                foreach ($userEntries as $e) {
                    if ($e['permission_value'] === 'content_allow') $nodeHasAllow = true;
                    if ($e['permission_value'] === 'deny') $nodeHasDeny = true;
                    if ($e['permission_value'] === 'reset') $nodeHasReset = true;
                }
            } else {
                foreach ($groupEntries as $e) {
                    if ($e['permission_value'] === 'content_allow') $nodeHasAllow = true;
                    if ($e['permission_value'] === 'deny') $nodeHasDeny = true;
                    if ($e['permission_value'] === 'reset') $nodeHasReset = true;
                }
            }

            if ($nodeHasDeny) {
                $finalPerm = false;
            } elseif ($nodeHasAllow) {
                $finalPerm = true;
            } elseif ($nodeHasReset) {
                $finalPerm = false;
            }
        }

        return $finalPerm;
    }
}
