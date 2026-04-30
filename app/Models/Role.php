<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $connection = 'sphere';
    protected $table = 'roles';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'slug',
        'level',
    ];
}
