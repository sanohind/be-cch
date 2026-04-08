<?php

namespace App\Console\Commands;

use App\Models\CchUser;
use App\Models\Division;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncSphereUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Options:
     *   --role-level=  Filter by specific role level (e.g. 4 for Presdir/GM, 5 for Manager)
     *   --dry-run      Preview changes without writing to database
     *   --force        Sync ALL users regardless of is_active status in Sphere
     */
    protected $signature = 'cch:sync-sphere-users
                            {--role-level= : Only sync users with this role level (e.g. 4)}
                            {--dry-run     : Preview without making changes}
                            {--force       : Include inactive Sphere users}';

    protected $description = 'Sync users from Sphere database into the local cch_users table (useful for ensuring Presdir/GM/Manager receive notifications)';

    public function handle(): int
    {
        $isDryRun   = $this->option('dry-run');
        $roleLevel  = $this->option('role-level');
        $force      = $this->option('force');

        $this->info('');
        $this->info('==========================================================');
        $this->info('  CCH – Sync Sphere Users → cch_users');
        $this->info('==========================================================');

        if ($isDryRun) {
            $this->warn('  [DRY-RUN] No changes will be written to the database.');
        }

        // ── 1. Query Sphere DB ──────────────────────────────────────────────
        try {
            $query = DB::connection('sphere')
                ->table('users as u')
                ->join('roles as r', 'u.role_id', '=', 'r.id')
                ->leftJoin('departments as d', 'u.department_id', '=', 'd.id')
                ->select([
                    'u.id         as sphere_user_id',
                    'u.username',
                    'u.name       as full_name',
                    'u.email',
                    'u.is_active',
                    'r.slug       as sphere_role',
                    'r.level      as sphere_role_level',
                    'd.id         as sphere_department_id',
                    'd.code       as sphere_department_code',
                    'd.name       as sphere_department_name',
                ]);

            if (!$force) {
                $query->where('u.is_active', true);
            }

            if ($roleLevel !== null) {
                $query->where('r.level', (int) $roleLevel);
            }

            $sphereUsers = $query->orderBy('r.level')->orderBy('u.name')->get();
        } catch (\Throwable $e) {
            $this->error('Failed to query Sphere database: ' . $e->getMessage());
            Log::error('[SyncSphereUsers] Sphere DB query failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        $total   = $sphereUsers->count();
        $created = 0;
        $updated = 0;
        $skipped = 0;

        $this->info("  Found {$total} user(s) in Sphere" . ($roleLevel ? " (role_level={$roleLevel})" : '') . '.');
        $this->newLine();

        // ── 2. Sync each user ───────────────────────────────────────────────
        $headers = ['Sphere ID', 'Name', 'Email', 'Role Level', 'Department', 'Action'];
        $rows    = [];

        foreach ($sphereUsers as $su) {
            // Skip users without email
            if (empty($su->email)) {
                $skipped++;
                $rows[] = [$su->sphere_user_id, $su->full_name, '(no email)', $su->sphere_role_level, $su->sphere_department_name ?? '-', 'SKIP'];
                continue;
            }

            // Resolve division_id in CCH local DB
            $divisionId = $this->resolveDivisionId($su);

            $payload = [
                'username'               => $su->username,
                'full_name'              => $su->full_name,
                'email'                  => $su->email,
                'sphere_role'            => $su->sphere_role,
                'sphere_role_level'      => $su->sphere_role_level,
                'sphere_department_id'   => $su->sphere_department_id,
                'sphere_department_code' => $su->sphere_department_code,
                'sphere_department_name' => $su->sphere_department_name,
                'division_id'            => $divisionId,
                'is_active'              => (bool) $su->is_active,
            ];

            // Check if already exists
            $existing = CchUser::where('sphere_user_id', $su->sphere_user_id)->first();
            $action   = 'SKIP';

            if (!$existing) {
                if (!$isDryRun) {
                    CchUser::create(array_merge($payload, ['sphere_user_id' => $su->sphere_user_id]));
                }
                $action = 'CREATE';
                $created++;
            } else {
                // Detect if anything has actually changed
                $hasChanges = false;
                foreach ($payload as $key => $value) {
                    if ((string)($existing->$key ?? '') !== (string)($value ?? '')) {
                        $hasChanges = true;
                        break;
                    }
                }

                if ($hasChanges) {
                    if (!$isDryRun) {
                        $existing->update($payload);
                    }
                    $action = 'UPDATE';
                    $updated++;
                } else {
                    $skipped++;
                }
            }

            $rows[] = [
                $su->sphere_user_id,
                $su->full_name,
                $su->email,
                $su->sphere_role_level,
                $su->sphere_department_name ?? '-',
                $action,
            ];
        }

        // ── 3. Output table ─────────────────────────────────────────────────
        $this->table($headers, $rows);
        $this->newLine();

        if ($isDryRun) {
            $this->warn("  [DRY-RUN] Would have created {$created}, updated {$updated}, skipped {$skipped} user(s).");
        } else {
            $this->info("  ✓ Done! Created: {$created} | Updated: {$updated} | Unchanged/Skipped: {$skipped}");
            Log::info("[SyncSphereUsers] Sync completed: created={$created}, updated={$updated}, skipped={$skipped}");
        }

        $this->newLine();
        return self::SUCCESS;
    }

    // ── Helper: resolve CCH local division_id from Sphere department info ──

    private function resolveDivisionId(object $su): ?int
    {
        if ($su->sphere_department_id) {
            $div = Division::find((int) $su->sphere_department_id);
            if ($div) return $div->id;
        }

        if (!empty($su->sphere_department_code)) {
            $div = Division::where('code', $su->sphere_department_code)->first();
            if ($div) return $div->id;
        }

        if (!empty($su->sphere_department_name)) {
            $name = trim($su->sphere_department_name);
            $div = Division::whereRaw('LOWER(TRIM(name)) = ?', [strtolower($name)])->first()
                ?? Division::where('name', 'like', '%' . $name . '%')->first();
            if ($div) return $div->id;
        }

        return null;
    }
}
