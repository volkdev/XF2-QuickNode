<?php
namespace VolkDev\QuickNode\Report;

use XF\Entity\Report;
use XF\Mvc\Entity\Entity;
use XF\Report\AbstractHandler;

class QuickNodeLog extends AbstractHandler
{
    protected function canViewContent(Report $report)
    {
        return \XF::visitor()->hasAdminPermission('qncManageLogs');
    }

    protected function canActionContent(Report $report)
    {
        return \XF::visitor()->hasAdminPermission('qncManageLogs');
    }

    public function setupReportEntityContent(Report $report, Entity $content)
    {
        $report->content_user_id = $content->user_id;

        if (!empty($content->Node)) {
            $report->content_info = [
                'node_id' => $content->node_id,
                'node_title' => $content->Node->title
            ];
        } else {
            $report->content_info = [
                'node_id' => $content->node_id,
                'node_title' => \XF::phrase('volkdev_qnc_node_x', ['id' => $content->node_id])
            ];
        }
    }

    public function getContentTitle(Report $report)
    {
        return \XF::phrase('volkdev_qnc_delete_request_title', ['title' => $report->content_info['node_title'] ?? '?']);
    }

    public function getContentMessage(Report $report)
    {
        return \XF::phrase('volkdev_qnc_delete_request_message');
    }

    public function getContentLink(Report $report)
    {
        if (isset($report->content_info['node_id'])) {
            return \XF::app()->router('public')->buildLink('canonical:forums', ['node_id' => $report->content_info['node_id']]);
        }
        return \XF::app()->router('admin')->buildLink('canonical:qnc-logs');
    }
}