<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPlant extends Model
{
    protected $table = 'm_customer_plants';
    protected $primaryKey = 'customer_plant_id';
    public $timestamps = false;

    protected $fillable = [
        'customer_id',
        'plant_name',
        'plant_location',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id', 'customer_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
