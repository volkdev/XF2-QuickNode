<?php

namespace VolkDev\QuickNode\Option;

use XF\Option\AbstractOption;

class UserGroup extends AbstractOption
{
    public static function renderCheckbox(\XF\Entity\Option $option, array $htmlParams)
    {
        /** @var \XF\Repository\UserGroup $userGroupRepo */
        $userGroupRepo = \XF::repository('XF:UserGroup');

        $choices = $userGroupRepo->getUserGroupOptionsData(false, 'option');
        $choices = array_map(function($v) {
            $v['label'] = \XF::escapeString($v['label']);
            return $v;
        }, $choices);

        $controlOptions = self::getControlOptions($option, $htmlParams);
        $rowOptions = self::getRowOptions($option, $htmlParams);

        return self::getTemplater()->formCheckBoxRow(
            $controlOptions, $choices, $rowOptions
        );
    }
}
