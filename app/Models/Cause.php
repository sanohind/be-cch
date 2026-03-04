<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cause extends Model
{
    protected $table = 'm_causes';
    public $timestamps = false;

    protected $fillable = [
        'type',
        'cause_name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForOccurrence($query)
    {
        return $query->where('type', 'occurrence');
    }

    public function scopeForOutflow($query)
    {
        return $query->where('type', 'outflow');
    }
}
