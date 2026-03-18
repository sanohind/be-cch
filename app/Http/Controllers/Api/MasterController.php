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
     */
    public function getCchFilterOptions(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'divisions' => Division::active()->select('id', 'name', 'code')->orderBy('name')->get(),
                'report_categories' => [
                    ['value' => 'Customer',  'label' => 'Customer'],
                    ['value' => 'Market',    'label' => 'Market'],
                    ['value' => 'Internal',  'label' => 'Internal'],
                ],
                'levels' => [
                    ['value' => 'A',              'label' => 'A — Major Function'],
                    ['value' => 'B',              'label' => 'B — Minor Function'],
                    ['value' => 'C',              'label' => 'C — Appearance'],
                    ['value' => 'M',              'label' => 'M — Management'],
                    ['value' => 'Not_Applicable', 'label' => 'NA — Not Applicable'],
                ],
                'statuses' => [
                    ['value' => 'draft',       'label' => 'Draft'],
                    ['value' => 'submitted',   'label' => 'Submitted'],
                    ['value' => 'in_progress', 'label' => 'In Progress'],
                    ['value' => 'closed',      'label' => 'Closed'],
                ],
            ],
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // ADMIN CRUD — Master Data
    // ═══════════════════════════════════════════════════════════════

    private function paginateQuery($query, Request $request, int $perPage = 20)
    {
        if ($s = $request->query('search')) {
            // generic search: find columns that start with 'name' or 'code'
            $cols = array_filter(
                \Schema::getColumnListing($query->getModel()->getTable()),
                fn($c) => str_contains($c, 'name') || str_contains($c, 'code')
            );
            $query->where(function ($q) use ($cols, $s) {
                foreach ($cols as $col) {
                    $q->orWhere($col, 'like', "%{$s}%");
                }
            });
        }
        return $query->paginate($perPage)->toArray();
    }

    // ── Causes ──────────────────────────────────────────────────────────────────
    public function adminIndexCauses(Request $request): JsonResponse
    {
        $q = Cause::query()->orderBy('type')->orderBy('cause_name');
        $paginated = $this->paginateQuery($q, $request);
        return response()->json(['success' => true, 'data' => $paginated['data'], 'meta' => collect($paginated)->except('data')]);
    }

    public function adminStoreCause(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type'        => 'required|in:occurrence,outflow',
            'cause_name'  => 'required|string|max:200',
            'description' => 'nullable|string|max:500',
            'is_active'   => 'boolean',
        ]);
        $item = Cause::create($data + ['is_active' => $data['is_active'] ?? true]);
        return response()->json(['success' => true, 'data' => $item], 201);
    }

    public function adminUpdateCause(Request $request, $id): JsonResponse
    {
        $item = Cause::findOrFail($id);
        $data = $request->validate([
            'type'        => 'required|in:occurrence,outflow',
            'cause_name'  => 'required|string|max:200',
            'description' => 'nullable|string|max:500',
            'is_active'   => 'boolean',
        ]);
        $item->update($data);
        return response()->json(['success' => true, 'data' => $item]);
    }

    public function adminDestroyCause($id): JsonResponse
    {
        Cause::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    // ── Currencies ───────────────────────────────────────────────────────────────
    public function adminIndexCurrencies(Request $request): JsonResponse
    {
        $q = Currency::query()->orderBy('currency_code');
        $paginated = $this->paginateQuery($q, $request);
        return response()->json(['success' => true, 'data' => $paginated['data'], 'meta' => collect($paginated)->except('data')]);
    }

    public function adminStoreCurrency(Request $request): JsonResponse
    {
        $data = $request->validate([
            'currency_code' => 'required|string|max:10|unique:m_currencies,currency_code',
            'currency_name' => 'required|string|max:100',
        ]);
        $item = Currency::create($data);
        return response()->json(['success' => true, 'data' => $item], 201);
    }

    public function adminUpdateCurrency(Request $request, $id): JsonResponse
    {
        $item = Currency::findOrFail($id);
        $data = $request->validate([
            'currency_code' => "required|string|max:10|unique:m_currencies,currency_code,{$id},currency_id",
            'currency_name' => 'required|string|max:100',
        ]);
        $item->update($data);
        return response()->json(['success' => true, 'data' => $item]);
    }

    public function adminDestroyCurrency($id): JsonResponse
    {
        Currency::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    // ── Failure Modes ────────────────────────────────────────────────────────────
    public function adminIndexFailureModes(Request $request): JsonResponse
    {
        $q = \App\Models\FailureMode::query()->orderBy('failure_mode_code');
        $paginated = $this->paginateQuery($q, $request);
        return response()->json(['success' => true, 'data' => $paginated['data'], 'meta' => collect($paginated)->except('data')]);
    }

    public function adminStoreFailureMode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'failure_mode_code' => 'required|string|max:50|unique:m_failure_modes,failure_mode_code',
            'failure_mode_name' => 'required|string|max:200',
            'is_active'         => 'boolean',
        ]);
        $item = \App\Models\FailureMode::create($data + ['is_active' => $data['is_active'] ?? true]);
        return response()->json(['success' => true, 'data' => $item], 201);
    }

    public function adminUpdateFailureMode(Request $request, $id): JsonResponse
    {
        $item = \App\Models\FailureMode::findOrFail($id);
        $data = $request->validate([
            'failure_mode_code' => "required|string|max:50|unique:m_failure_modes,failure_mode_code,{$id},failure_mode_id",
            'failure_mode_name' => 'required|string|max:200',
            'is_active'         => 'boolean',
        ]);
        $item->update($data);
        return response()->json(['success' => true, 'data' => $item]);
    }

    public function adminDestroyFailureMode($id): JsonResponse
    {
        \App\Models\FailureMode::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    // ── Plants ───────────────────────────────────────────────────────────────────
    public function adminIndexPlants(Request $request): JsonResponse
    {
        $q = Plant::with('division')->orderBy('plant_code');
        $paginated = $this->paginateQuery($q, $request);
        return response()->json(['success' => true, 'data' => $paginated['data'], 'meta' => collect($paginated)->except('data')]);
    }

    public function adminStorePlant(Request $request): JsonResponse
    {
        $data = $request->validate([
            'plant_code'  => 'required|string|max:20|unique:m_plants,plant_code',
            'plant_name'  => 'required|string|max:200',
            'office'      => 'nullable|string|max:200',
            'division_id' => 'nullable|integer|exists:m_divisions,id',
            'country'     => 'nullable|string|max:100',
            'is_active'   => 'boolean',
        ]);
        $item = Plant::create($data + ['is_active' => $data['is_active'] ?? true]);
        return response()->json(['success' => true, 'data' => $item], 201);
    }

    public function adminUpdatePlant(Request $request, $id): JsonResponse
    {
        $item = Plant::findOrFail($id);
        $data = $request->validate([
            'plant_code'  => "required|string|max:20|unique:m_plants,plant_code,{$id},plant_id",
            'plant_name'  => 'required|string|max:200',
            'office'      => 'nullable|string|max:200',
            'division_id' => 'nullable|integer|exists:m_divisions,id',
            'country'     => 'nullable|string|max:100',
            'is_active'   => 'boolean',
        ]);
        $item->update($data);
        return response()->json(['success' => true, 'data' => $item]);
    }

    public function adminDestroyPlant($id): JsonResponse
    {
        Plant::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    // ── Processes ────────────────────────────────────────────────────────────────
    public function adminIndexProcesses(Request $request): JsonResponse
    {
        $q = Process::with('plant')->orderBy('process_code');
        $paginated = $this->paginateQuery($q, $request);
        return response()->json(['success' => true, 'data' => $paginated['data'], 'meta' => collect($paginated)->except('data')]);
    }

    public function adminStoreProcess(Request $request): JsonResponse
    {
        $data = $request->validate([
            'process_code' => 'required|string|max:50|unique:m_processes,process_code',
            'process_name' => 'required|string|max:200',
            'plant_id'     => 'nullable|integer|exists:m_plants,plant_id',
            'is_active'    => 'boolean',
        ]);
        $item = Process::create($data + ['is_active' => $data['is_active'] ?? true]);
        return response()->json(['success' => true, 'data' => $item], 201);
    }

    public function adminUpdateProcess(Request $request, $id): JsonResponse
    {
        $item = Process::findOrFail($id);
        $data = $request->validate([
            'process_code' => "required|string|max:50|unique:m_processes,process_code,{$id},process_id",
            'process_name' => 'required|string|max:200',
            'plant_id'     => 'nullable|integer|exists:m_plants,plant_id',
            'is_active'    => 'boolean',
        ]);
        $item->update($data);
        return response()->json(['success' => true, 'data' => $item]);
    }

    public function adminDestroyProcess($id): JsonResponse
    {
        Process::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    // ── Product Categories ───────────────────────────────────────────────────────
    public function adminIndexProductCategories(Request $request): JsonResponse
    {
        $q = ProductCategory::query()->orderBy('category_code');
        $paginated = $this->paginateQuery($q, $request);
        return response()->json(['success' => true, 'data' => $paginated['data'], 'meta' => collect($paginated)->except('data')]);
    }

    public function adminStoreProductCategory(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category_code' => 'required|string|max:50|unique:m_product_categories,category_code',
            'category_name' => 'required|string|max:200',
            'is_active'     => 'boolean',
        ]);
        $item = ProductCategory::create($data + ['is_active' => $data['is_active'] ?? true]);
        return response()->json(['success' => true, 'data' => $item], 201);
    }

    public function adminUpdateProductCategory(Request $request, $id): JsonResponse
    {
        $item = ProductCategory::findOrFail($id);
        $data = $request->validate([
            'category_code' => "required|string|max:50|unique:m_product_categories,category_code,{$id},category_id",
            'category_name' => 'required|string|max:200',
            'is_active'     => 'boolean',
        ]);
        $item->update($data);
        return response()->json(['success' => true, 'data' => $item]);
    }

    public function adminDestroyProductCategory($id): JsonResponse
    {
        ProductCategory::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }

    // ── Product Families ─────────────────────────────────────────────────────────
    public function adminIndexProductFamilies(Request $request): JsonResponse
    {
        $q = \App\Models\ProductFamily::with('category')->orderBy('family_code');
        $paginated = $this->paginateQuery($q, $request);
        return response()->json(['success' => true, 'data' => $paginated['data'], 'meta' => collect($paginated)->except('data')]);
    }

    public function adminStoreProductFamily(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category_id' => 'required|integer|exists:m_product_categories,category_id',
            'family_code' => 'required|string|max:50|unique:m_product_families,family_code',
            'family_name' => 'required|string|max:200',
            'is_active'   => 'boolean',
        ]);
        $item = \App\Models\ProductFamily::create($data + ['is_active' => $data['is_active'] ?? true]);
        return response()->json(['success' => true, 'data' => $item], 201);
    }

    public function adminUpdateProductFamily(Request $request, $id): JsonResponse
    {
        $item = \App\Models\ProductFamily::findOrFail($id);
        $data = $request->validate([
            'category_id' => 'required|integer|exists:m_product_categories,category_id',
            'family_code' => "required|string|max:50|unique:m_product_families,family_code,{$id},family_id",
            'family_name' => 'required|string|max:200',
            'is_active'   => 'boolean',
        ]);
        $item->update($data);
        return response()->json(['success' => true, 'data' => $item]);
    }

    public function adminDestroyProductFamily($id): JsonResponse
    {
        \App\Models\ProductFamily::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }
}

