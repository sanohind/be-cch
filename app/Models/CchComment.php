<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CchComment extends Model
{
    protected $table = 't_cch_comments';
    protected $primaryKey = 'comment_id';

    protected $fillable = [
        'cch_id',
        'block_number',
        'comment_type',
        'parent_comment_id',
        'subject',
        'description',
        'created_by',
        'attachment_path',
        'attachment_name',
        'attachment_size_kb',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(CchUser::class, 'created_by', 'id');
    }
}

