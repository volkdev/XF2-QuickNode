<?php
namespace VolkDev\QuickNode\Admin\Controller;

use XF\Admin\Controller\AbstractController;

class Log extends AbstractController
{
    protected function preDispatchController($action, \XF\Mvc\ParameterBag $params)
    {
        $this->assertAdminPermission('qncManageLogs');
    }

    public function actionIndex()
    {
        $timeframeMap = [
            '1h' => 3600,
            '6h' => 21600,
            '24h' => 86400,
            '7d' => 604800,
            '30d' => 2592000,
            'all' => 0,
        ];
        $timeframe = $this->filter('timeframe', 'str', '24h');
        $seconds = $timeframeMap[$timeframe] ?? 86400;

        $username = $this->filter('username', 'str');
        $actionFilter = $this->filter('action', 'str');

        $finder = $this->finder('VolkDev\QuickNode:Log')
            ->order('log_date', 'DESC');

        if ($seconds > 0) {
            $finder->where('log_date', '>=', \XF::$time - $seconds);
        }

        if ($username) {
            $user = $this->em()->findOne('XF:User', ['username' => $username]);
            if ($user) $finder->where('user_id', $user->user_id);
        }

        if ($actionFilter) {
            $finder->where('action', $actionFilter);
        }

        $perPage = 50;
        $page = $this->filterPage();
        $total = $finder->total();
        $logs = $finder->limitByPage($page, $perPage)->fetch();

        $periodLabels = [
            '1h' => \XF::phrase('volkdev_qnc_period_1h'),
            '6h' => \XF::phrase('volkdev_qnc_period_6h'),
            '24h' => \XF::phrase('volkdev_qnc_period_24h'),
            '7d' => \XF::phrase('volkdev_qnc_period_7d'),
            '30d' => \XF::phrase('volkdev_qnc_period_30d'),
            'all' => \XF::phrase('volkdev_qnc_period_all'),
        ];
        $periodLabel = $periodLabels[$timeframe] ?? $periodLabels['24h'];

        return $this->view('VolkDev\QuickNode:Log\Index', 'volkdev_qn_log_list', [
            'logs' => $logs,
            'timeframe' => $timeframe,
            'username' => $username,
            'actionFilter' => $actionFilter,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'periodLabel' => $periodLabel
        ]);
    }

    public function actionView()
    {
        $logId = $this->filter('log_id', 'uint');
        $log = $this->assertRecordExists('VolkDev\QuickNode:Log', $logId);
        
        return $this->view('VolkDev\QuickNode:Log\View', 'volkdev_qn_log_view', ['log' => $log]);
    }

    public function actionApprove()
    {
        $logId = $this->filter('log_id', 'uint');
        $log = $this->assertRecordExists('VolkDev\QuickNode:Log', $logId);

        if ($this->isPost()) {
            if ($log->action == 'pending_delete' && $log->Node) {
                $log->Node->delete();
                $log->action = 'delete';
                $log->save();
                
                $report = $this->em()->findOne('XF:Report', [
                    'content_type' => 'volkdev_qnc_log',
                    'content_id' => $logId
                ]);
                if ($report && $report->report_state == 'open') {
                    $commenter = $this->app()->service('XF:Report\Commenter', $report);
                    $commenter->setReportState('resolved');
                    $commenter->setMessage(\XF::phrase('volkdev_qnc_delete_approved'));
                    $commenter->save();
                }

                if ($log->User) {
                    $this->app()->repository('XF:UserAlert')->alert(
                        $log->User,
                        \XF::visitor()->user_id,
                        \XF::visitor()->username,
                        'volkdev_qnc_log',
                        $log->log_id,
                        'approve',
                        ['nodeTitle' => $log->Node ? $log->Node->title : 'Unknown']
                    );
                }
            }

            return $this->redirect($this->buildLink('qnc-logs'), \XF::phrase('volkdev_qnc_delete_approved'));
        }

        return $this->view('VolkDev\QuickNode:Log\Approve', 'volkdev_qn_log_approve', ['log' => $log]);
    }

    public function actionReject()
    {
        $logId = $this->filter('log_id', 'uint');
        $log = $this->assertRecordExists('VolkDev\QuickNode:Log', $logId);

        if ($this->isPost()) {
            if ($log->action == 'pending_delete' && $log->Node && $log->old_data) {
                $log->Node->display_in_list = $log->old_data['display_in_list'] ?? 1;
                $log->Node->save();

                $db = $this->app()->db();
                // Удаляем все текущие viewNode права
                $db->delete('xf_permission_entry_content', 
                    "content_type = 'node' AND content_id = ? AND permission_group_id = 'general' AND permission_id = 'viewNode'", 
                    $log->node_id
                );
                
                // Восстанавливаем оригинальные права
                if (!empty($log->old_data['view_perms'])) {
                    $inserts = [];
                    foreach ($log->old_data['view_perms'] as $perm) {
                        $inserts[] = [
                            'content_type' => 'node',
                            'content_id' => $log->node_id,
                            'user_group_id' => $perm['user_group_id'],
                            'user_id' => $perm['user_id'],
                            'permission_group_id' => 'general',
                            'permission_id' => 'viewNode',
                            'permission_value' => $perm['permission_value'],
                            'permission_value_int' => 0
                        ];
                    }
                    if ($inserts) {
                        $db->insertBulk('xf_permission_entry_content', $inserts, true);
                    }
                }

                $this->app()->jobManager()->enqueueUnique('permissionRebuild', 'XF:PermissionRebuild');
                
                $report = $this->em()->findOne('XF:Report', [
                    'content_type' => 'volkdev_qnc_log',
                    'content_id' => $logId
                ]);
                if ($report && $report->report_state == 'open') {
                    $commenter = $this->app()->service('XF:Report\Commenter', $report);
                    $commenter->setReportState('rejected');
                    $commenter->setMessage(\XF::phrase('volkdev_qnc_delete_rejected'));
                    $commenter->save();
                }

                if ($log->User) {
                    $this->app()->repository('XF:UserAlert')->alert(
                        $log->User,
                        \XF::visitor()->user_id,
                        \XF::visitor()->username,
                        'volkdev_qnc_log',
                        $log->log_id,
                        'reject',
                        ['nodeTitle' => $log->Node ? $log->Node->title : 'Unknown']
                    );
                }
                
                $log->action = 'rejected';
                $log->save();
            }

            return $this->redirect($this->buildLink('qnc-logs'), \XF::phrase('volkdev_qnc_delete_rejected'));
        }

        return $this->view('VolkDev\QuickNode:Log\Reject', 'volkdev_qn_log_reject', ['log' => $log]);
    }

    public function actionRevert()
    {
        $logId = $this->filter('log_id', 'uint');
        $log = $this->assertRecordExists('VolkDev\QuickNode:Log', $logId);

        if ($this->isPost()) {
            /** @var \VolkDev\QuickNode\Service\LogReverter $reverter */
            $reverter = $this->service('VolkDev\QuickNode:LogReverter');
            $reverter->revert($log);

            return $this->redirect($this->buildLink('qnc-logs'), \XF::phrase('volkdev_qnc_action_reverted'));
        }

        return $this->view('VolkDev\QuickNode:Log\Revert', 'volkdev_qn_log_revert', ['log' => $log]);
    }
    
    public function actionMassRevert()
    {
        $username = $this->filter('username', 'str');
        $timeframe = $this->filter('timeframe', 'str', '24h');

        if (!$username) {
            return $this->error(\XF::phrase('volkdev_qnc_user_not_specified'));
        }

        $user = $this->em()->findOne('XF:User', ['username' => $username]);
        if (!$user) {
            return $this->error(\XF::phrase('volkdev_qnc_user_not_found'));
        }

        if ($this->isPost()) {
            $timeframeMap = [
                '1h' => 3600,
                '6h' => 21600,
                '24h' => 86400,
                '7d' => 604800,
                '30d' => 2592000,
                'all' => 0,
            ];
            $seconds = $timeframeMap[$timeframe] ?? 86400;

            $finder = $this->finder('VolkDev\QuickNode:Log')
                ->where('user_id', $user->user_id)
                ->order('log_date', 'DESC');

            if ($seconds > 0) {
                $finder->where('log_date', '>=', \XF::$time - $seconds);
            }

            $logs = $finder->fetch();

            /** @var \VolkDev\QuickNode\Service\LogReverter $reverter */
            $reverter = $this->service('VolkDev\QuickNode:LogReverter');
            $revertedCount = $reverter->massRevert($logs);

            return $this->redirect($this->buildLink('qnc-logs'), \XF::phrase('volkdev_qnc_mass_revert_done', ['count' => $revertedCount]));
        }

        $periodLabels = [
            '1h' => \XF::phrase('volkdev_qnc_period_1h'),
            '6h' => \XF::phrase('volkdev_qnc_period_6h'),
            '24h' => \XF::phrase('volkdev_qnc_period_24h'),
            '7d' => \XF::phrase('volkdev_qnc_period_7d'),
            '30d' => \XF::phrase('volkdev_qnc_period_30d'),
            'all' => \XF::phrase('volkdev_qnc_period_all'),
        ];
        $periodLabel = $periodLabels[$timeframe] ?? $periodLabels['24h'];

        return $this->view('VolkDev\QuickNode:Log\MassRevert', 'volkdev_qn_log_mass_revert', [
            'username' => $username,
            'timeframe' => $timeframe,
            'periodLabel' => $periodLabel
        ]);
    }
}