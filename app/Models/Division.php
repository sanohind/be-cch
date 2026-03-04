<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Division extends Model
{
    protected $connection = 'sphere';
    protected $table = 'departments';

    protected $fillable = [
        'name',
        'code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function plants(): HasMany
    {
        return $this->hasMany(Plant::class, 'division_id', 'id');
    }

    public function cchUsers(): HasMany
    {
        return $this->hasMany(CchUser::class, 'division_id', 'id');
    }

    public function cchs(): HasMany
    {
        return $this->hasMany(Cch::class, 'division_id', 'id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
