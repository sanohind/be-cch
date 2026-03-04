<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Attachment untuk Block 10 - Closing.
 * Dipakai untuk Final Report (Item 5) dan dokumen pendukung lainnya.
 *
 * Kolom attachment_type:
 *   - 'final_report'  → Item 5: Final Report (Choose File)
 *   - 'supporting'    → Dokumen pendukung tambahan
 */
class CchClosingAttachment extends Model
{
    protected $table = 't_cch_closing_attachments';
    protected $primaryKey = 'attachment_id';
    public $timestamps = false;

    protected $fillable = [
        'cch_id',
        'attachment_type',
        'file_name',
        'file_path',
        'file_size_kb',
        'uploaded_by',
        'uploaded_at',
    ];

    protected $casts = ['uploaded_at' => 'datetime'];

    public function cch(): BelongsTo { return $this->belongsTo(Cch::class, 'cch_id', 'cch_id'); }
    public function uploadedBy(): BelongsTo { return $this->belongsTo(CchUser::class, 'uploaded_by', 'id'); }
}
