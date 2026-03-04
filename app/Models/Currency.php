<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $table = 'm_currencies';
    protected $primaryKey = 'currency_id';
    public $timestamps = false;

    protected $fillable = ['currency_code', 'currency_name'];
}
