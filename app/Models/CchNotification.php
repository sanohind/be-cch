<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CchNotification extends Model
{
    protected $table = 't_cch_notifications';
    protected $primaryKey = 'notification_id';
    public $timestamps = false; // Karena hanya ada created_at dan tidak ada updated_at, atau set ke null
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'cch_id',
        'notification_type',
        'sent_to',
        'message',
        'is_sent',
        'sent_at'
    ];

    protected $casts = [
        'is_sent' => 'boolean',
        'sent_at' => 'datetime',
    ];
}
