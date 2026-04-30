<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CchUser extends Model
{
    protected $connection = 'sphere';
    protected $table = 'users';

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'department_id', 'id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }


    // Accessor for full_name to map to name in sphere users table
    public function getFullNameAttribute()
    {
        return $this->name;
    }
}
