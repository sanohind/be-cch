<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plant extends Model
{
    protected $table = 'm_plants';
    protected $primaryKey = 'plant_id';
    public $timestamps = false;

    protected $fillable = [
        'plant_code',
        'plant_name',
        'office',
        'division_id',
        'country',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'division_id', 'id');
    }

    public function processes(): HasMany
    {
        return $this->hasMany(Process::class, 'plant_id', 'plant_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
