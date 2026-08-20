<?php

namespace VolkDev\QuickNode\Repository;

use XF\Mvc\Entity\Repository;
use XF\Mvc\Entity\Finder;

class PermTemplate extends Repository
{
    public function findTemplatesForList(): Finder
    {
        return $this->finder('VolkDev\QuickNode:PermTemplate')
            ->order('display_order', 'ASC')
            ->order('title', 'ASC');
    }

    public function getApplicableTemplates($nodeId, $groupId, \XF\Entity\User $user = null): array
    {
        $user = $user ?: \XF::visitor();

        $templates = $this->finder('VolkDev\QuickNode:PermTemplate')
            ->where('active', 1)
            ->order('display_order', 'ASC')
            ->fetch();

        $applicable = [];
        foreach ($templates as $template) {
            /** @var \VolkDev\QuickNode\Entity\PermTemplate $template */
            if ($template->isApplicableToNode($nodeId)
                && $template->isApplicableToGroup($groupId)
                && $template->canApply($user)
            ) {
                $applicable[$template->template_id] = $template;
            }
        }

        return $applicable;
    }
}
