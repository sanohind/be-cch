<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FailureMode extends Model
{
    protected $table = 'm_failure_modes';
    protected $primaryKey = 'failure_mode_id';
    public $timestamps = false;

    protected $fillable = ['failure_mode_code', 'failure_mode_name', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function scopeActive($query) { return $query->where('is_active', true); }
}
