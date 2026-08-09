<?php

namespace VolkDev\QuickNode\Repository;

use XF\Mvc\Entity\Repository;
use XF\Mvc\Entity\Entity;
use XF\Entity\Node;

class NodePerms extends Repository
{
    public function hasAccess(Entity $entity, $permission = 'canManageQuickNode')
    {
        $visitor = \XF::visitor();

        // Guests must never have access to node management
        if ($visitor->user_id == 0) {
            return false;
        }

        if ($entity instanceof Node) {
            $node = $entity;
        } elseif (isset($entity->Node)) {
            $node = $entity->Node;
        } else {
            return false;
        }

        return $visitor->hasNodePermission($node->node_id, $permission);
    }
}
