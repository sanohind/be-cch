<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CchOccurrenceAttachment extends Model
{
    use HasFactory;

    protected $table = 't_cch_occurrence_attachments';
    protected $primaryKey = 'attachment_id';

    protected $fillable = [
        'cch_id',
        'file_name',
        'file_path',
        'file_size_kb',
        'uploaded_by',
    ];
}
