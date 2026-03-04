<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @deprecated Gunakan BusinessPartner model. Customer sekarang dibaca dari ERP.
 * Model ini dipertahankan sebagai alias agar tidak ada breaking change.
 */
class Customer extends BusinessPartner
{
    public function scopeActive($query)
    {
        return $query->active()->customers();
    }
}
