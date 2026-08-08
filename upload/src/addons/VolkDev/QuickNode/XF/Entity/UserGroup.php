<?php

namespace VolkDev\QuickNode\XF\Entity;

use XF\Mvc\Entity\Structure;

class UserGroup extends XFCP_UserGroup
{
    public static function getStructure(Structure $structure)
    {
        $structure = parent::getStructure($structure);
        $structure->columns['qnc_protected'] = ['type' => self::BOOL, 'default' => false];
        return $structure;
    }
}
