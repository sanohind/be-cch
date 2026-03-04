<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductFamily extends Model
{
    protected $table = 'm_product_families';
    protected $primaryKey = 'family_id';
    public $timestamps = false;

    protected $fillable = ['category_id', 'family_code', 'family_name', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id', 'category_id');
    }

    public function scopeActive($query) { return $query->where('is_active', true); }
}
