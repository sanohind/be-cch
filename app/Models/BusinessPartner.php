<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Business Partner (ERP)
 *
 * Membaca langsung dari tabel business_partner di database ERP (SQL Server).
 * Menggantikan m_customers dan m_suppliers yang sebelumnya ada di CCH lokal.
 *
 * Kolom dari ERP:
 *   bp_code, bp_name, bp_role, bp_role_desc,
 *   bp_currency, bp_status, bp_status_desc,
 *   contry, adr_line_1-4, bp_phone, bp_fax
 *
 * bp_role values (dari ERP): 'C' = Customer, 'S' = Supplier, 'B' = Both
 */
class BusinessPartner extends Model
{
    protected $connection = 'erp';
    protected $table = 'business_partner';
    protected $primaryKey = 'bp_code';
    public $incrementing = false;
    public $keyType = 'string';
    public $timestamps = false;

    /**
     * ERP Integer Codes (dari SELECT DISTINCT di database ERP):
     *
     * bp_status:
     *   2 = Active (data real / production — 641 records)
     *   3 = Active (dummy/test data — 95 records)
     *
     * bp_role:
     *   1 = None
     *   2 = Customer
     *   3 = Supplier
     *   4 = Customer & Supplier (paling banyak — 641 records)
     *
     * Gunakan IN (2, 3) untuk status agar semua data Active tercakup.
     */
    const STATUS_ACTIVE_VALUES = [2, 3]; // semua status yang dianggap Active

    const ROLE_NONE             = 1;
    const ROLE_CUSTOMER          = 2;
    const ROLE_SUPPLIER          = 3;
    const ROLE_CUSTOMER_SUPPLIER = 4;

    protected $fillable = [
        'bp_code', 'bp_name', 'bp_role', 'bp_role_desc',
        'bp_currency', 'bp_status', 'bp_status_desc',
        'contry', 'adr_line_1', 'adr_line_2', 'adr_line_3', 'adr_line_4',
        'bp_phone', 'bp_fax',
    ];

    // ─── Scopes ───────────────────────────────────────────────────────────────

    /** Hanya BP yang active (status IN (2,3) — keduanya artinya Active di ERP ini) */
    public function scopeActive($query)
    {
        return $query->whereIn('bp_status', self::STATUS_ACTIVE_VALUES);
    }

    /** Filter hanya Customer (bp_role = 2 atau 4) */
    public function scopeCustomers($query)
    {
        return $query->whereIn('bp_role', [self::ROLE_CUSTOMER, self::ROLE_CUSTOMER_SUPPLIER]);
    }

    /** Filter hanya Supplier (bp_role = 3 atau 4) */
    public function scopeSuppliers($query)
    {
        return $query->whereIn('bp_role', [self::ROLE_SUPPLIER, self::ROLE_CUSTOMER_SUPPLIER]);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function isCustomer(): bool
    {
        return in_array($this->bp_role, [self::ROLE_CUSTOMER, self::ROLE_CUSTOMER_SUPPLIER]);
    }

    public function isSupplier(): bool
    {
        return in_array($this->bp_role, [self::ROLE_SUPPLIER, self::ROLE_CUSTOMER_SUPPLIER]);
    }

    public function isActive(): bool
    {
        return in_array($this->bp_status, self::STATUS_ACTIVE_VALUES);
    }

    /** Cek apakah supplier Jepang (untuk logika DFA) */
    public function isJapanese(): bool
    {
        return strtolower($this->contry ?? '') === 'japan'
            || strtolower($this->contry ?? '') === 'jp';
    }
}
