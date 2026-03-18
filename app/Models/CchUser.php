<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * SSO Bridge Model
 *
 * Dibuat/diupdate otomatis saat user login via Sphere SSO.
 * Tidak ada password — auth 100% via Sphere JWT.
 */
class CchUser extends Model
{
    protected $table = 'cch_users';
    protected $primaryKey = 'id';

    protected $fillable = [
        'sphere_user_id',
        'username',
        'full_name',
        'email',
        'sphere_role',
        'sphere_role_level',
        'sphere_department_id',
        'sphere_department_code',
        'sphere_department_name',
        'division_id',
        'plant_id',
        'cch_role',
        'is_active',
        'last_login_at',
    ];

    protected $casts = [
        'is_active'        => 'boolean',
        'last_login_at'    => 'datetime',
        'sphere_role_level' => 'integer',
    ];

    protected $hidden = [];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'division_id', 'id');
    }

    public function plant(): BelongsTo
    {
        return $this->belongsTo(Plant::class, 'plant_id', 'plant_id');
    }

    public function cchs(): HasMany
    {
        return $this->hasMany(Cch::class, 'input_by', 'id');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Create or update a CCH user from Sphere JWT claims.
     * Resolves division_id from Sphere department (id/code/name) so PIC department
     * can see CCHs that requested their division in Block 5.
     */
    public static function syncFromSphere(array $sphereUser): self
    {
        $sphereId = $sphereUser['sphere_id'] ?? $sphereUser['id'];
        $user = static::updateOrCreate(
            ['sphere_user_id' => $sphereId],
            [
                'username'               => $sphereUser['username'],
                'full_name'              => $sphereUser['name'] ?? $sphereUser['username'],
                'email'                  => $sphereUser['email'],
                'sphere_role'            => $sphereUser['role'] ?? null,
                'sphere_role_level'      => $sphereUser['role_level'] ?? null,
                'sphere_department_id'   => $sphereUser['department_id'] ?? null,
                'sphere_department_code' => $sphereUser['department_code'] ?? null,
                'sphere_department_name' => $sphereUser['department_name'] ?? null,
                'last_login_at'          => now(),
            ]
        );

        $divisionId = static::resolveDivisionIdFromSphere($sphereUser);
        if ($divisionId !== null && $user->division_id != $divisionId) {
            $user->update(['division_id' => $divisionId]);
            $user->refresh();
        }

        return $user;
    }

    /**
     * Resolve sphere.departments id from Sphere JWT department info.
     * Used so cch_users.division_id is set and list CCH filter (PIC department) works.
     */
    protected static function resolveDivisionIdFromSphere(array $sphereUser): ?int
    {
        $departmentId = $sphereUser['department_id'] ?? null;
        $departmentCode = $sphereUser['department_code'] ?? null;
        $departmentName = $sphereUser['department_name'] ?? null;

        if ($departmentId && is_numeric($departmentId)) {
            $div = Division::query()->find((int) $departmentId);
            if ($div) {
                return (int) $div->id;
            }
        }

        if (!empty($departmentCode) && is_string($departmentCode)) {
            $div = Division::query()->where('code', $departmentCode)->first();
            if ($div) {
                return (int) $div->id;
            }
        }

        if (!empty($departmentName) && is_string($departmentName)) {
            $name = trim($departmentName);
            $div = Division::query()
                ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($name)])
                ->first();
            if ($div) {
                return (int) $div->id;
            }
            $div = Division::query()
                ->where('name', 'like', '%' . $name . '%')
                ->first();
            if ($div) {
                return (int) $div->id;
            }
        }

        return null;
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->cch_role, $roles);
    }

    public function isAdmin(): bool
    {
        return $this->cch_role === 'admin';
    }

    public function isDivisionManager(): bool
    {
        return in_array($this->cch_role, ['division_manager', 'admin']);
    }

    public function isQaManager(): bool
    {
        return in_array($this->cch_role, ['qa_manager', 'division_manager', 'admin']);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
