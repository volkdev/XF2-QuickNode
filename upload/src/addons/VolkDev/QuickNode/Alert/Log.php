<?php
namespace VolkDev\QuickNode\Alert;

use XF\Alert\AbstractHandler;
use XF\Entity\UserAlert;
use XF\Mvc\Entity\Entity;

class Log extends AbstractHandler
{
    public function getEntityWith()
    {
        return ['Node'];
    }

    public function canViewContent(Entity $entity, &$error = null)
    {
        return true;
    }

    public function getTemplateData($action, UserAlert $alert, Entity $content = null)
    {
        return [
            'action' => $action,
            'log' => $content,
            'nodeTitle' => $alert->extra_data['nodeTitle'] ?? ($content && $content->Node ? $content->Node->title : 'Unknown')
        ];
    }

    public function getTemplateName($action)
    {
        return 'public:alert_volkdev_qnc_log_' . $action;
    }
}
