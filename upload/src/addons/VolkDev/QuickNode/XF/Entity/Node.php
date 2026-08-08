<?php

namespace VolkDev\QuickNode\XF\Entity;

class Node extends XFCP_Node
{
    public function hasQuickNodeAccess($permission = 'canManageQuickNode')
    {
        /** @var \VolkDev\QuickNode\Repository\NodePerms $repo */
        $repo = $this->repository('VolkDev\QuickNode:NodePerms');
        return $repo->hasAccess($this, $permission);
    }

    public static function getStructure(\XF\Mvc\Entity\Structure $structure)
    {
        $structure = parent::getStructure($structure);
        
        $structure->columns['qnc_pending_delete'] = ['type' => self::BOOL, 'default' => false];
        
        return $structure;
    }
}
