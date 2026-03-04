<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Process extends Model
{
    protected $table = 'm_processes';
    protected $primaryKey = 'process_id';
    public $timestamps = false;

    protected $fillable = ['process_code', 'process_name', 'plant_id', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function plant(): BelongsTo
    {
        return $this->belongsTo(Plant::class, 'plant_id', 'plant_id');
    }

    public function scopeActive($query) { return $query->where('is_active', true); }
    public function scopeByPlant($query, int $plantId) { return $query->where('plant_id', $plantId); }
}
