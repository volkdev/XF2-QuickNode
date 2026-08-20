<?php

namespace VolkDev\QuickNode\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * COLUMNS
 * @property int|null $template_id
 * @property string $title
 * @property string $description
 * @property string $node_scope
 * @property array $node_ids
 * @property string $user_group_scope
 * @property array $user_group_ids
 * @property array $permissions
 * @property bool $admin_only
 * @property int $display_order
 * @property bool $active
 */
class PermTemplate extends Entity
{
    public function isApplicableToNode($nodeId)
    {
        if ($this->node_scope === 'all') {
            return true;
        }

        return in_array((int)$nodeId, array_map('intval', $this->node_ids ?: []));
    }

    public function isApplicableToGroup($groupId)
    {
        if ($this->user_group_scope === 'all') {
            return true;
        }

        return in_array((int)$groupId, array_map('intval', $this->user_group_ids ?: []));
    }

    public function canApply(\XF\Entity\User $user = null)
    {
        $user = $user ?: \XF::visitor();

        if ($this->admin_only && !$user->is_admin) {
            return false;
        }

        return true;
    }

    public function matchesPermissions(array $currentPerms)
    {
        if (empty($this->permissions) || !is_array($this->permissions)) {
            return false;
        }

        foreach ($this->permissions as $group => $perms) {
            if (!is_array($perms)) continue;
            foreach ($perms as $permId => $val) {
                $current = $currentPerms[$group][$permId] ?? null;
                if ($val === 'content_allow' || $val === 'deny' || $val === 'reset') {
                    if ($current !== $val) {
                        return false;
                    }
                } else if (is_numeric($val)) {
                    if ($current != $val) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    public static function getStructure(Structure $structure)
    {
        $structure->table = 'xf_volkdev_qnc_perm_template';
        $structure->shortName = 'VolkDev\QuickNode:PermTemplate';
        $structure->primaryKey = 'template_id';
        $structure->columns = [
            'template_id' => ['type' => self::UINT, 'autoIncrement' => true, 'nullable' => true],
            'title' => ['type' => self::STR, 'maxLength' => 100, 'required' => 'please_enter_valid_title'],
            'description' => ['type' => self::STR, 'default' => ''],
            'node_scope' => ['type' => self::STR, 'default' => 'all', 'allowedValues' => ['all', 'selected']],
            'node_ids' => ['type' => self::JSON_ARRAY, 'default' => []],
            'user_group_scope' => ['type' => self::STR, 'default' => 'all', 'allowedValues' => ['all', 'selected']],
            'user_group_ids' => ['type' => self::JSON_ARRAY, 'default' => []],
            'permissions' => ['type' => self::JSON_ARRAY, 'default' => []],
            'admin_only' => ['type' => self::BOOL, 'default' => false],
            'display_order' => ['type' => self::UINT, 'default' => 1],
            'active' => ['type' => self::BOOL, 'default' => true],
        ];

        return $structure;
    }
}
