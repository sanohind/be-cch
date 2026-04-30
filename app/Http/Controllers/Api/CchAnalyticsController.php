<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cch;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CchAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $sphereUser = $request->attributes->get('sphere_user');
        $roleLevel = (int)($sphereUser['role_level'] ?? 99);
        $departmentId = $sphereUser['department_id'] ?? null;

        $filterType = $request->input('filter_type', 'monthly'); // daily, monthly, yearly
        $date = $request->input('date');

        $query = Cch::where('status', '!=', 'draft');

        $sphereDb = config('database.connections.sphere.database', env('DB_DATABASE_SPHERE', 'be_sphere'));

        // RBAC Filter like CchController
        $cchUserId = $sphereUser['id'] ?? null;
        if ($roleLevel === 1) {
            // Superadmin: lihat semua
        } elseif (in_array($roleLevel, [2, 4])) {
            // Presdir & GM: hanya CCH rank A
            $query->whereHas('basic', function ($q) {
                $q->where('importance_internal', 'A');
            });
        } elseif (in_array($roleLevel, [5, 6, 8])) {
            // Manager, Supervisor, Staff
            $query->where(function ($q) use ($departmentId, $roleLevel, $sphereDb) {
                $q->whereRaw(
                    "EXISTS (SELECT 1 FROM `{$sphereDb}`.`users` WHERE `{$sphereDb}`.`users`.`id` = `t_cch`.`input_by` AND `{$sphereDb}`.`users`.`department_id` = ?)",
                    [$departmentId]
                );
                if ($departmentId && in_array($roleLevel, [5, 6])) {
                    $q->orWhereHas('requests', function ($r) use ($departmentId) {
                        $r->where('division_id', $departmentId);
                    });
                }
            });
        } else {
            $query->where('input_by', $cchUserId);
        }

        // Date Filtering
        if ($date) {
            $parsedDate = Carbon::parse($date);
            if ($filterType === 'daily') {
                $query->whereDate('t_cch.created_at', $parsedDate->format('Y-m-d'));
            } elseif ($filterType === 'monthly') {
                $query->whereYear('t_cch.created_at', $parsedDate->year)
                      ->whereMonth('t_cch.created_at', $parsedDate->month);
            } elseif ($filterType === 'yearly') {
                $query->whereYear('t_cch.created_at', $parsedDate->year);
            }
        }

        // Clone query for two different charts
        $queryStatus = clone $query;
        $queryCustomers = clone $query;

        // 1. Pie Chart: Count by Status
        $statuses = $queryStatus->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get();

        $statusData = [];
        foreach ($statuses as $stat) {
            $statusData[] = [
                'name' => ucwords(str_replace('_', ' ', $stat->status)),
                'value' => $stat->total
            ];
        }

        // 2. Bar Chart: Top 5 Customers
        // Need to join with t_cch_basic to get customer name
        $customers = clone $query;
        $topCustomersCount = $customers->join('t_cch_basic', 't_cch.cch_id', '=', 't_cch_basic.cch_id')
            ->select('t_cch_basic.customer_id', DB::raw('count(*) as total'))
            ->groupBy('t_cch_basic.customer_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $customerIds = $topCustomersCount->pluck('customer_id')->filter()->toArray();
        $businessPartners = \App\Models\BusinessPartner::whereIn('bp_code', $customerIds)->get()->keyBy('bp_code');

        $customerData = [];
        foreach ($topCustomersCount as $cust) {
            $bpName = $cust->customer_id ? ($businessPartners[$cust->customer_id]->bp_name ?? $cust->customer_id) : 'Unknown';
            $customerData[] = [
                'name' => $bpName,
                'value' => $cust->total
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'cch_by_status' => $statusData,
                'top_customers' => $customerData,
            ]
        ]);
    }
}
