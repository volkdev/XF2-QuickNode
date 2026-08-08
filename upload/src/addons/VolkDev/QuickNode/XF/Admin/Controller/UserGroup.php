<?php

namespace VolkDev\QuickNode\XF\Admin\Controller;

class UserGroup extends XFCP_UserGroup
{
    protected function userGroupSaveProcess(\XF\Entity\UserGroup $userGroup)
    {
        $form = parent::userGroupSaveProcess($userGroup);

        $form->setup(function () use ($userGroup) {
            $userGroup->qnc_protected = $this->filter('qnc_protected', 'bool');
        });

        return $form;
    }
}
