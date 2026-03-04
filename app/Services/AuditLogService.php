<?php

namespace App\Services;

use App\Models\CchAuditLog;

class AuditLogService
{
    /**
     * Log a critical change in the system
     *
     * @param int $cchId
     * @param string $blockName
     * @param string $action
     * @param mixed $oldValue
     * @param mixed $newValue
     * @param int $userId
     * @return CchAuditLog
     */
    public static function log($cchId, $blockName, $action, $oldValue, $newValue, $userId)
    {
        return CchAuditLog::create([
            'cch_id' => $cchId,
            'block_name' => $blockName,
            'action' => $action,
            'old_value' => is_array($oldValue) || is_object($oldValue) ? json_encode($oldValue) : $oldValue,
            'new_value' => is_array($newValue) || is_object($newValue) ? json_encode($newValue) : $newValue,
            'changed_by' => $userId,
            // 'changed_at' will use the default created_at
        ]);
    }
}
