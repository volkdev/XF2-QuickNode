<?php
namespace VolkDev\QuickNode\Service;

use XF\Service\AbstractService;
use VolkDev\QuickNode\Entity\Log;

class LogCreator extends AbstractService
{
    /**
     * Creates a log entry for a QuickNode action.
     *
     * @param int $userId
     * @param int $nodeId
     * @param string $action One of: create, edit, delete, pending_delete, perm_change, move
     * @param array|null $oldData Previous state (JSON-serializable)
     * @param array|null $newData New state (JSON-serializable)
     * @return Log
     */
    public function logAction(int $userId, int $nodeId, string $action, ?array $oldData = null, ?array $newData = null): Log
    {
        /** @var Log $log */
        $log = $this->em()->create('VolkDev\QuickNode:Log');
        $log->user_id = $userId;
        $log->node_id = $nodeId;
        $log->action = $action;
        $log->old_data = $oldData;
        $log->new_data = $newData;
        $log->log_date = \XF::$time;
        $log->save();

        return $log;
    }
}
