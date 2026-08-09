<?php
namespace VolkDev\QuickNode\Service;

use XF\Service\AbstractService;
use VolkDev\QuickNode\Entity\Log;

class LogReverter extends AbstractService
{
    public function revert(Log $log): bool
    {
        if ($log->action === 'delete') {
            return false;
        }

        $result = false;
        if ($log->action === 'create') {
            $result = $this->revertCreate($log);
        } elseif ($log->action === 'edit') {
            $result = $this->revertEdit($log);
        } elseif ($log->action === 'pending_delete') {
            $result = $this->revertPendingDelete($log);
        } elseif ($log->action === 'perm_change') {
            $result = $this->revertPermChange($log);
        }

        if ($result && $log->User) {
            \XF::app()->repository('XF:UserAlert')->alert(
                $log->User,
                \XF::visitor()->user_id,
                \XF::visitor()->username,
                'volkdev_qnc_log',
                $log->log_id,
                'revert',
                ['nodeTitle' => $log->Node ? $log->Node->title : 'Unknown']
            );
        }

        return $result;
    }

    public function massRevert(iterable $logs): int
    {
        $revertedCount = 0;
        foreach ($logs as $log) {
            if ($this->revert($log)) {
                $revertedCount++;
            }
        }
        return $revertedCount;
    }

    protected function revertCreate(Log $log): bool
    {
        if ($log->Node) {
            $log->Node->delete();
            return true;
        }
        return false;
    }

    protected function revertEdit(Log $log): bool
    {
        if ($log->Node && $log->old_data) {
            $log->Node->bulkSet([
                'title' => $log->old_data['title'] ?? $log->Node->title,
                'description' => $log->old_data['description'] ?? $log->Node->description
            ]);

            if ($log->Node->node_type_id == 'Forum' && isset($log->old_data['allow_posting'])) {
                $typeData = $log->Node->getDataRelationOrDefault();
                $typeData->allow_posting = $log->old_data['allow_posting'];
                $log->Node->addCascadedSave($typeData);
            }

            $log->Node->save();
            return true;
        }
        return false;
    }

    protected function revertPendingDelete(Log $log): bool
    {
        if ($log->Node && $log->old_data) {
            // Clear pending delete flag
            $log->Node->qnc_pending_delete = 0;
            $log->Node->save();

            /** @var \VolkDev\QuickNode\Service\NodePrivacy $privacyService */
            $privacyService = \XF::app()->service('VolkDev\QuickNode:NodePrivacy');

            if (empty($log->old_data['was_private'])) {
                $privacyService->makePublic($log->node_id);

                // Restore old viewNode permissions using UpdatePermissions
                if (!empty($log->old_data['view_perms'])) {
                    foreach ($log->old_data['view_perms'] as $perm) {
                        $updater = \XF::app()->service('XF:UpdatePermissions');
                        $updater->setContent('node', $log->node_id);
                        if ($perm['user_id'] > 0) {
                            $updater->setUser($perm['user_id']);
                        } else {
                            $updater->setUserGroup($perm['user_group_id']);
                        }
                        $updater->updatePermissions(['general' => ['viewNode' => $perm['permission_value']]]);
                    }
                }
            }
            
            $this->closeReport($log->log_id);
            $log->delete();
            return true;
        }
        return false;
    }

    protected function revertPermChange(Log $log): bool
    {
        if ($log->Node && $log->old_data) {
            $groupId = $log->old_data['group_id'];
            $oldPerms = $log->old_data['perms'] ?? [];

            $entries = $this->finder('XF:PermissionEntryContent')
                ->where('content_type', 'node')
                ->where('content_id', $log->node_id)
                ->where('user_group_id', $groupId)
                ->where('user_id', 0)
                ->fetch();

            foreach ($entries as $entry) {
                $entry->delete();
            }

            if (!empty($oldPerms)) {
                $updater = \XF::app()->service('XF:UpdatePermissions');
                $updater->setContent('node', $log->node_id);
                $updater->setUserGroup($groupId);
                $updater->updatePermissions($oldPerms);
            } else {
                // No old perms to restore — the updater call above handles the rebuild;
                // if there are truly no entries, we still need to purge current ones.
                // The loop above already deleted them via entity->delete(), which triggers
                // XF's own cache-dirty marking, so no manual rebuild is needed.
            }

            return true;
        }
        return false;
    }

    protected function closeReport(int $logId): void
    {
        $report = $this->em()->findOne('XF:Report', [
            'content_type' => 'volkdev_qnc_log',
            'content_id' => $logId
        ]);
        if ($report && $report->report_state == 'open') {
            $commenter = \XF::app()->service('XF:Report\Commenter', $report);
            $commenter->setReportState('rejected');
            $commenter->setMessage(\XF::phrase('volkdev_qnc_delete_rejected'));
            $commenter->save();
        }
    }
}
