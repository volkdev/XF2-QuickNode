<?php
namespace VolkDev\QuickNode\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

class Log extends Entity
{
    public function getActionLabel(): string
    {
        $map = [
            'create' => \XF::phrase('volkdev_qnc_action_create'),
            'edit' => \XF::phrase('volkdev_qnc_action_edit'),
            'delete' => \XF::phrase('volkdev_qnc_action_delete'),
            'pending_delete' => \XF::phrase('volkdev_qnc_action_pending_delete'),
            'perm_change' => \XF::phrase('volkdev_qnc_action_perm_change'),
            'move' => \XF::phrase('volkdev_qnc_action_move'),
        ];
        return $map[$this->action] ?? $this->action;
    }

    public static function getStructure(Structure $structure)
    {
        $structure->table = 'xf_volkdev_qnc_log';
        $structure->shortName = 'VolkDev\QuickNode:Log';
        $structure->primaryKey = 'log_id';
        $structure->columns = [
            'log_id' => ['type' => self::UINT, 'autoIncrement' => true],
            'user_id' => ['type' => self::UINT, 'required' => true],
            'node_id' => ['type' => self::UINT, 'required' => true],
            'action' => ['type' => self::STR, 'maxLength' => 50, 'required' => true],
            'old_data' => ['type' => self::JSON_ARRAY, 'nullable' => true],
            'new_data' => ['type' => self::JSON_ARRAY, 'nullable' => true],
            'log_date' => ['type' => self::UINT, 'default' => \XF::$time],
        ];
        $structure->relations = [
            'User' => ['entity' => 'XF:User', 'type' => self::TO_ONE, 'conditions' => 'user_id', 'primary' => true],
            'Node' => ['entity' => 'XF:Node', 'type' => self::TO_ONE, 'conditions' => 'node_id', 'primary' => true],
        ];

        $structure->getters = [
            'action_label' => true
        ];

        return $structure;
    }
}