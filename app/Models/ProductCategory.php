<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductCategory extends Model
{
    protected $table = 'm_product_categories';
    protected $primaryKey = 'category_id';
    public $timestamps = false;

    protected $fillable = ['category_code', 'category_name', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    public function productFamilies(): HasMany
    {
        return $this->hasMany(ProductFamily::class, 'category_id', 'category_id');
    }

    public function scopeActive($query) { return $query->where('is_active', true); }
}
