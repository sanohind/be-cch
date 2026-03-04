<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Division;
use App\Models\Plant;
use App\Models\BusinessPartner;
use App\Models\FailureMode;
use App\Models\ProductCategory;
use App\Models\Process;
use App\Models\Cause;
use App\Models\Currency;

class MasterController extends Controller
{
    public function getDivisions(): JsonResponse
    {
        $divisions = Division::active()->get();
        return response()->json(['success' => true, 'data' => $divisions]);
    }

    public function getPlants(): JsonResponse
    {
        $plants = Plant::active()->with('division')->get();
        return response()->json(['success' => true, 'data' => $plants]);
    }

    /**
     * Daftar Business Partner sebagai Customer (bp_role = C atau B) dari ERP.
     */
    public function getCustomers(): JsonResponse
    {
        $customers = BusinessPartner::active()->customers()
            ->select('bp_code', 'bp_name', 'bp_role', 'contry', 'adr_line_1', 'bp_status')
            ->orderBy('bp_name')
            ->get();

        return response()->json(['success' => true, 'data' => $customers]);
    }

    /**
     * Daftar Business Partner sebagai Supplier (bp_role = S atau B) dari ERP.
     */
    public function getSuppliers(): JsonResponse
    {
        $suppliers = BusinessPartner::active()->suppliers()
            ->select('bp_code', 'bp_name', 'bp_role', 'contry', 'adr_line_1', 'bp_status')
            ->orderBy('bp_name')
            ->get();

        return response()->json(['success' => true, 'data' => $suppliers]);
    }

    /**
     * Semua Business Partner (customers + suppliers) dari ERP.
     */
    public function getBusinessPartners(Request $request): JsonResponse
    {
        $query = BusinessPartner::active()
            ->select('bp_code', 'bp_name', 'bp_role', 'bp_role_desc', 'contry', 'adr_line_1', 'bp_status');

        // Filter by role jika ada: ?role=customer / ?role=supplier / ?role=all
        $role = $request->query('role', 'all');
        if ($role === 'customer') {
            $query->customers();
        } elseif ($role === 'supplier') {
            $query->suppliers();
        }

        // Search by name or code
        if ($request->has('search')) {
            $search = $request->query('search');
            $query->whereRaw("(bp_name LIKE N'%' + ? + N'%' OR bp_code LIKE N'%' + ? + N'%')", [$search, $search]);
        }

        return response()->json([
            'success' => true,
            'data'    => $query->orderBy('bp_name')->get(),
        ]);
    }

    /**
     * Detail satu Business Partner by bp_code.
     */
    public function getBusinessPartnerDetail(string $bpCode): JsonResponse
    {
        $bp = BusinessPartner::find($bpCode);

        if (!$bp) {
            return response()->json(['success' => false, 'message' => 'Business Partner not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $bp]);
    }

    // ─── Endpoint lama untuk CustomerPlants (sekarang dari ERP tidak ada sub-plant) ─────
    // CustomerPlant masih bisa dipakai jika ada tabel lokal m_customer_plants

    public function getFailureModes(): JsonResponse
    {
        $modes = FailureMode::active()->get();
        return response()->json(['success' => true, 'data' => $modes]);
    }

    public function getProductCategories(): JsonResponse
    {
        $categories = ProductCategory::active()->get();
        return response()->json(['success' => true, 'data' => $categories]);
    }

    public function getProductFamilies($categoryId): JsonResponse
    {
        $category = ProductCategory::with(['productFamilies' => function ($query) {
            $query->active();
        }])->find($categoryId);

        if (!$category) {
            return response()->json(['success' => false, 'message' => 'Category not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $category->productFamilies]);
    }

    public function getProcesses(Request $request): JsonResponse
    {
        $query = Process::active();

        if ($request->has('plant_id')) {
            $query->where('plant_id', $request->query('plant_id'));
        }

        return response()->json(['success' => true, 'data' => $query->get()]);
    }

    public function getCauses(Request $request): JsonResponse
    {
        $type = $request->query('type');
        $query = \App\Models\Cause::active();

        if ($type === 'occurrence') {
            $query->forOccurrence();
        } elseif ($type === 'outflow') {
            $query->forOutflow();
        }

        return response()->json(['success' => true, 'data' => $query->get()]);
    }

    public function getCurrencies(): JsonResponse
    {
        $currencies = Currency::all();
        return response()->json(['success' => true, 'data' => $currencies]);
    }

    /**
     * GET /api/v1/masters/cch-filter-options
     *
     * Mengembalikan semua opsi dropdown filter untuk halaman list CCH.
     * Frontend cukup panggil 1 endpoint ini untuk mengisi semua dropdown filter.
     */
    public function getCchFilterOptions(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                // Dropdown: Division
                'divisions' => Division::active()
                    ->select('id', 'name', 'code')
                    ->orderBy('name')
                    ->get(),

                // Dropdown: Report Category (ENUM dari t_cch_basic)
                'report_categories' => [
                    ['value' => 'Customer',  'label' => 'Customer'],
                    ['value' => 'Market',    'label' => 'Market'],
                    ['value' => 'Internal',  'label' => 'Internal'],
                ],

                // Dropdown: Level / Importance Internal (ENUM dari t_cch_basic)
                'levels' => [
                    ['value' => 'A',              'label' => 'A — Major Function'],
                    ['value' => 'B',              'label' => 'B — Minor Function'],
                    ['value' => 'C',              'label' => 'C — Appearance'],
                    ['value' => 'M',              'label' => 'M — Management'],
                    ['value' => 'Not_Applicable', 'label' => 'NA — Not Applicable'],
                ],

                // Dropdown: Status CCH
                'statuses' => [
                    ['value' => 'draft',             'label' => 'Draft'],
                    ['value' => 'in_progress',       'label' => 'In Progress'],
                    ['value' => 'closed_by_manager', 'label' => 'Pending Approval'],
                    ['value' => 'closed',            'label' => 'Closed'],
                    ['value' => 'rejected',          'label' => 'Rejected'],
                ],
            ],
        ]);
    }
}
