<?php

namespace App\Services;

use App\Models\CchAuditLog;
use Carbon\Carbon;

/**
 * AuditLogService — Append-only audit trail.
 *
 * Setiap panggilan log() SELALU membuat ROW BARU di t_cch_audit_log.
 * Tidak pernah update row yang sudah ada.
 *
 * Kolom `action` (konvensi):
 *   SAVE_DRAFT     → User menyimpan sebagai draft
 *   SUBMIT         → User submit final (status blok → submitted)
 *   ADD_{ENTITY}   → Menambahkan sub-entitas (causes, screening, dll.)
 *   UPDATE_{ENTITY}→ Mengubah sub-entitas
 *   DELETE_{ENTITY}→ Menghapus sub-entitas
 *   UPLOAD_FILE    → Upload lampiran
 *   DELETE_FILE    → Hapus lampiran
 *   SUBMIT_CLOSE_REQUEST → Manager submit closing
 *   APPROVE_CLOSE  → Requester approve close
 */
class AuditLogService
{
    /**
     * Catat 1 aktivitas sebagai baris baru di t_cch_audit_log.
     *
     * @param int    $cchId
     * @param string $blockName  Contoh: 'Block 1', 'Block 8', 'Block 10'
     * @param string $action     Konvensi: SAVE_DRAFT | SUBMIT | ADD_CAUSE | ...
     * @param mixed  $oldValue   Nilai sebelumnya (string, array, atau null)
     * @param mixed  $newValue   Nilai baru (string, array, atau null)
     * @param int    $userId     ID user dari cch_users
     * @return CchAuditLog
     */
    public static function log($cchId, $blockName, $action, $oldValue, $newValue, $userId): CchAuditLog
    {
        return CchAuditLog::create([
            'cch_id'     => $cchId,
            'block_name' => $blockName,
            'action'     => $action,
            'old_value'  => self::encode($oldValue),
            'new_value'  => self::encode($newValue),
            'changed_by' => $userId,
            'changed_at' => Carbon::now(),
        ]);
    }

    /**
     * Shortcut: log save-draft tanpa old/new value.
     */
    public static function logDraft($cchId, $blockName, $userId): CchAuditLog
    {
        return self::log($cchId, $blockName, 'SAVE_DRAFT', null, null, $userId);
    }

    /**
     * Shortcut: log submit final tanpa old/new value.
     */
    public static function logSubmit($cchId, $blockName, $userId): CchAuditLog
    {
        return self::log($cchId, $blockName, 'SUBMIT', null, null, $userId);
    }

    /**
     * Encode value ke string/JSON untuk disimpan.
     */
    private static function encode(mixed $value): ?string
    {
        if (is_null($value)) return null;
        if (is_array($value) || is_object($value)) return json_encode($value, JSON_UNESCAPED_UNICODE);
        return (string) $value;
    }
}
