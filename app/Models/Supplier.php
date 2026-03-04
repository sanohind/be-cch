<?php

namespace App\Models;

/**
 * @deprecated Gunakan BusinessPartner model. Supplier sekarang dibaca dari ERP.
 * Model ini dipertahankan sebagai alias agar tidak ada breaking change.
 */
class Supplier extends BusinessPartner
{
    public function scopeActive($query)
    {
        return $query->active()->suppliers();
    }

    public function isJapanese(): bool
    {
        return parent::isJapanese();
    }
}
