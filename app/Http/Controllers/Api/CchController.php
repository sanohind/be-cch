<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Cch;
use App\Models\CchBasic;
use App\Models\CchBasicAttachment;
use App\Models\CchUser;
use App\Services\WorkflowService;
use App\Services\AAlertService;
use App\Services\CchNotificationService;

class CchController extends Controller
{
    /**
     * GET /api/v1/cch
     *
     * Daftar CCH dalam format ringkas untuk tampilan list/table.
     * Output per baris: status, level, customer, division, date,
     *                   report_category, failure_mode, subject, creator
     *
     * Filter params:
     *   ?per_page=20          (default 20)
     *   ?page=1
     *   ?search=              (cari subject)
     *   ?status=              (in_progress | closed | ...)
     *   ?division_id=         (dropdown divisions)
     *   ?report_category=     (Customer | Market | Internal)
     *   ?importance_internal= (A | B | C | M | Not_Applicable)
     *   ?date_from=YYYY-MM-DD (filter created_at >=)
     *   ?date_to=YYYY-MM-DD   (filter created_at <=)
     *   ?sort_by=created_at   (default)
     *   ?sort_direction=desc  (default)
     */
    public function index(Request $request): JsonResponse
    {
        $sphereUser = $request->attributes->get('sphere_user');
        
        $query = Cch::query()
            ->select([
                't_cch.cch_id', 't_cch.cch_number', 't_cch.status',
                't_cch.b1_status', 't_cch.division_id',
                't_cch.input_by', 't_cch.created_at',
            ])
            ->with([
                // Creator
                'inputBy:id,name,username',
                // Division (dari sphere)
                'division:id,name,code',
                // Basic — hanya kolom yang ditampilkan di list
                'basic' => function ($q) {
                    $q->select([
                        'basic_id', 'cch_id', 'subject',
                        'report_category', 'customer_id',
                        'importance_internal', 'defect_class',
                    ])->with('customer:bp_code,bp_name');
                },
                // Primary — hanya failure_mode
                'primary' => function ($q) {
                    $q->select(['primary_id', 'cch_id', 'failure_mode_id'])
                      ->with('failureMode:failure_mode_id,failure_mode_name');
                },
            ]);

        // ── Filters ──────────────────────────────────────────────────────────

        // Draft hanya bisa dilihat oleh pembuat
        $cchUserId = $sphereUser['id'] ?? null;
        $roleLevel = (int) ($sphereUser['role_level'] ?? 99);

        $query->where(function ($q) use ($cchUserId) {
            $q->where('t_cch.status', '!=', 'draft')
              ->orWhere('t_cch.input_by', $cchUserId);
        });

        /**
         * ── Visibility per role ─────────────────────────────────────────────
         *
         * Level 1 (Super Admin) : lihat semua
         * Level 2, 4 (Presdir, GM) : lihat semua Rank A
         * Level 5 (Manager)     : lihat CCH yg pembuatnya se-departemen + CCH yg dept-nya di-request
         * Level 6 (Supervisor)  : lihat CCH yg pembuatnya se-departemen + CCH yg dept-nya di-request
         * Level 8 (Staff)       : lihat CCH yg pembuatnya se-departemen
         * Lainnya               : hanya CCH yang dia buat
         */
        $userDivisionId = $sphereUser['department_id'] ?? null;

        // Nama database Sphere untuk fully-qualified cross-db query
        $sphereDb = config('database.connections.sphere.database', env('DB_DATABASE_SPHERE', 'be_sphere'));

        if ($roleLevel === 1) {
            // Superadmin: lihat semua
        } elseif (in_array($roleLevel, [2, 4])) {
            // Presdir & GM: hanya CCH rank A
            $query->whereHas('basic', function ($q) {
                $q->where('importance_internal', 'A');
            });
        } elseif (in_array($roleLevel, [5, 6, 8])) {
            // Manager, Supervisor, Staff:
            // Lihat CCH yang PEMBUATNYA (input_by) se-departemen
            // Gunakan raw subquery karena users ada di DB berbeda (sphere)
            $query->where(function ($q) use ($userDivisionId, $roleLevel, $sphereDb) {
                // CCH yang pembuatnya berasal dari departemen yang sama
                $q->whereRaw(
                    "EXISTS (SELECT 1 FROM `{$sphereDb}`.`users` WHERE `{$sphereDb}`.`users`.`id` = `t_cch`.`input_by` AND `{$sphereDb}`.`users`.`department_id` = ?)",
                    [$userDivisionId]
                );

                // Manager & Supervisor juga bisa melihat CCH yang dept mereka di-request
                if ($userDivisionId && in_array($roleLevel, [5, 6])) {
                    $q->orWhereHas('requests', function ($r) use ($userDivisionId) {
                        $r->where('division_id', $userDivisionId);
                    });
                }
            });
        } else {
            // Role lain: hanya CCH yang dia buat
            $query->where('t_cch.input_by', $cchUserId);
        }

        // Status tiket
        if ($request->filled('status')) {
            $query->where('t_cch.status', $request->query('status'));
        }

        // Division
        if ($request->filled('division_id')) {
            $query->where('t_cch.division_id', $request->query('division_id'));
        }

        // Date range (berdasarkan created_at tiket)
        if ($request->filled('date_from')) {
            $query->whereDate('t_cch.created_at', '>=', $request->query('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('t_cch.created_at', '<=', $request->query('date_to'));
        }

        // Filter dari t_cch_basic (report_category & importance_internal)
        if ($request->filled('report_category') || $request->filled('importance_internal')) {
            $query->whereHas('basic', function ($q) use ($request) {
                if ($request->filled('report_category')) {
                    $q->where('report_category', $request->query('report_category'));
                }
                if ($request->filled('importance_internal')) {
                    $q->where('importance_internal', $request->query('importance_internal'));
                }
            });
        }

        // Search subject
        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('t_cch.cch_number', 'LIKE', "%{$search}%")
                  ->orWhereHas('basic', fn($b) => $b->where('subject', 'LIKE', "%{$search}%"));
            });
        }

        // ── Sorting & Pagination ──────────────────────────────────────────────
        $sortBy  = in_array($request->query('sort_by'), ['created_at', 'cch_number', 'status'])
                    ? $request->query('sort_by') : 'created_at';
        $sortDir = $request->query('sort_direction', 'desc') === 'asc' ? 'asc' : 'desc';
        $perPage = (int) $request->query('per_page', 20);

        $query->orderBy("t_cch.{$sortBy}", $sortDir);
        $paginator = $query->paginate($perPage);

        // ── Transform ke format list ──────────────────────────────────────────
        $items = $paginator->getCollection()->map(function ($cch) {
            $basic   = $cch->basic;
            $primary = $cch->primary;

            return [
                'cch_id'           => $cch->cch_id,
                'cch_number'       => $cch->cch_number,
                'status'           => $cch->status,
                'b1_status'        => $cch->b1_status,
                'date'             => $cch->created_at?->toDateString(),
                // Level (dari t_cch_basic.importance_internal)
                'level'            => $basic?->importance_internal,
                // Customer (dari ERP melalui t_cch_basic.customer_id)
                'customer'         => $basic?->customer ? [
                    'bp_code' => $basic->customer->bp_code,
                    'bp_name' => $basic->customer->bp_name,
                ] : null,
                // Division
                'division'         => $cch->division ? [
                    'id'   => $cch->division->id,
                    'name' => $cch->division->name,
                    'code' => $cch->division->code,
                ] : null,
                // Report Category
                'report_category'  => $basic?->report_category,
                // Failure Mode (dari t_cch_primary)
                'failure_mode'     => $primary?->failureMode ? [
                    'id'   => $primary->failureMode->failure_mode_id,
                    'name' => $primary->failureMode->failure_mode_name,
                ] : null,
                // Subject
                'subject'          => $basic?->subject,
                // Creator
                'creator'          => $cch->inputBy ? [
                    'id'        => $cch->inputBy->id,
                    'full_name' => $cch->inputBy?->name,
                ] : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $items,
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
        ]);
    }

    /**
     * Create a new CCH report
     */
    public function store(Request $request): JsonResponse
    {
        $isDraft = $request->boolean('is_draft', false);
        $sphereUser = $request->attributes->get('sphere_user');

        // Semua Supervisor (Level 6) dan Superadmin (Level 1) boleh membuat CCH
        $roleLevel = (int)($sphereUser['role_level'] ?? 99);

        if ($roleLevel !== 1 && $roleLevel !== 6) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Supervisor yang dapat membuat CCH baru.',
            ], 403);
        }

        $rules = [
            'subject' => 'required|string|max:200',
            'division_id' => 'required|exists:sphere.departments,id',
            'report_category' => 'required|in:Customer,Market,Internal',
            'customer_id' => 'nullable|string|exists:erp.business_partner,bp_code',
            'plant_of_customer' => 'nullable|string|max:255',
            'defect_class' => 'required|in:Quality trouble,Delivery trouble',
            'line_stop' => 'required|in:YES,NO',
            'count_by_customer' => 'required|in:YES,NO_Responsibility,NO_No_Responsibility,Undetermined,Not_Applicable',
            'month_of_counted' => 'nullable|date',
            'importance_internal' => 'required|in:A,B,C,M,Not_Applicable',
            'importance_customer' => 'nullable|in:A,B,C,Undetermined,Not_Applicable',
            'toyota_rank' => 'nullable|in:Critical,Major Function,A,B,C,Undetermined',
            'attachments.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png,xlsx,docx|max:10240' // Multiple file attachments
        ];

        $rules = WorkflowService::applyDraftRules($rules, $isDraft);
        $validated = $request->validate($rules);

        if (!$isDraft && isset($validated['report_category'])) {
            // Business Rule: Delivery trouble disabled for Market category
            if ($validated['report_category'] === 'Market' && ($validated['defect_class'] ?? '') === 'Delivery trouble') {
                return response()->json([
                    'success' => false,
                    'message' => 'Delivery trouble not allowed for Market category',
                    'errors' => ['defect_class' => ['Delivery trouble tidak diperbolehkan untuk kategori Market.']],
                ], 422);
            }

        }

        $year = date('Y');
        $cchNumber = Cch::generateCchNumber($year);

        // Create main record (draft initially)
        $cch = Cch::create([
            'cch_number' => $cchNumber,
            'status'     => 'draft',
            'input_by'   => $sphereUser['id'], // gunakan Sphere ID
            'division_id'=> $request->division_id,
        ]);

        // Create block 1 (Basic)
        $basicData = collect($validated)->except('attachments')->toArray();
        $basicData['cch_id'] = $cch->cch_id;
        $basic = CchBasic::create($basicData);

        // Handle File Attachments
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $originalName = $file->getClientOriginalName();
                $fileName = date('Ymd_His') . '_' . preg_replace('/[^A-Za-z0-9\-\_\.]/', '_', $originalName);
                $path = "cch/{$cch->cch_id}/basic";
                
                $storedPath = $file->storeAs($path, $fileName, 'public');

                CchBasicAttachment::create([
                    'cch_id' => $cch->cch_id,
                    'file_name' => $originalName,
                    'file_path' => $storedPath,
                    'file_size_kb' => round($file->getSize() / 1024, 2),
                    'uploaded_by' => $sphereUser['id']  // gunakan sphere user id
                ]);
            }
        }

        // A-Alert trigger if importance_internal === 'A'
        if ($basic->importance_internal === 'A') {
            AAlertService::trigger($cch->cch_id, $cch->cch_number, $basic->subject);
        }

        WorkflowService::updateBlockStatus($cch, 1, $isDraft, (int)($sphereUser['id'] ?? 0) ?: null);

        // Send email notification when block 1 is fully submitted (not draft)
        if (!$isDraft) {
            CchNotificationService::notifyCchCreated(
                $cch,
                $sphereUser['name'] ?? $sphereUser['username'] ?? 'Admin'
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'CCH generated and Block 1 saved successfully',
            'data'    => $cch->load('basic', 'basicAttachments')
        ], 201);
    }

    /**
     * Get complete details of a single CCH
     */
    public function show(Request $request, $id): JsonResponse
    {
        $cch = Cch::with([
            'inputBy', 'division',
            'basic.customer', 'basicAttachments',
            'primary.failureMode', 'primary.productCategory', 'primary.productFamily', 'primaryPhotos',
            'srta.screening', 'srta.attachments', 'srtaAttachments',
            'temporary', 'temporaryAttachments',
            'requests',
            'ra', 'raAttachments',
            'dfa', 'dfaAttachments',
            'occurrence.responsiblePlant', 'occurrence.process', 'occurrence.supplier', 'occurrence.supplierProcess', 'occurrenceCauses.cause',
            'outflow.responsiblePlant', 'outflow.process', 'outflow.supplier', 'outflow.supplierProcess', 'outflowCauses.cause',
            'closing.currency', 'closing.submittedBy', 'closing.approvedBy', 'closingAttachments'
        ])->find($id);

        if (!$cch) {
            return response()->json([
                'success' => false,
                'message' => 'CCH not found'
            ], 404);
        }

        $sphereUser = $request->attributes->get('sphere_user') ?? [];
        $cchUserId  = $sphereUser['id'] ?? null;
        $roleLevel  = (int) ($sphereUser['role_level'] ?? 99);

        // ── Draft guard: hanya creator yang boleh lihat CCH draft ──────────
        if ($cch->status === 'draft') {
            if ($cchUserId !== $cch->input_by && $roleLevel !== 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden. This CCH is in draft status and can only be accessed by its creator.'
                ], 403);
            }
        }

        // ── Visibility guard (same rules as index) ─────────────────────────
        if ($roleLevel !== 1) {
            $userDivisionId = (int) ($sphereUser['department_id'] ?? 0);
            $importance     = $cch->basic?->importance_internal ?? null;

            // Cek apakah pembuat CCH berasal dari departemen yang sama
            // Gunakan data Sphere yang sudah di-eager load
            $cch->loadMissing('inputBy');
            $creatorDeptId  = (int) ($cch->inputBy?->department_id ?? 0);
            $isSameDept     = ($creatorDeptId > 0) && ($creatorDeptId === $userDivisionId);

            // Cek apakah departemen user di-request di Block 5
            $cch->loadMissing('requests');
            $isRequestedDept = $cch->requests->contains('division_id', $userDivisionId);

            $allowed = false;

            if (in_array($roleLevel, [2, 4])) {
                // Presdir & GM: hanya rank A
                $allowed = ($importance === 'A');

            } elseif (in_array($roleLevel, [5, 6])) {
                // Manager & Supervisor: dept pembuat sama ATAU dept di-request
                $allowed = $isSameDept || $isRequestedDept;

            } elseif ($roleLevel === 8) {
                // Staff: hanya dept pembuat sama
                $allowed = $isSameDept;

            } else {
                // Lainnya: hanya creator
                $allowed = ($cchUserId === $cch->input_by);
            }

            if (!$allowed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses untuk melihat CCH ini.'
                ], 403);
            }
        }

        if ($cch->b1_status === 'draft' && !\App\Services\WorkflowService::checkCanViewDraft($cch, $sphereUser, 1)) {
            $cch->setRelation('basic', null);
            $cch->setRelation('basicAttachments', collect());
        }
        if ($cch->b2_status === 'draft' && !\App\Services\WorkflowService::checkCanViewDraft($cch, $sphereUser, 2)) {
            $cch->setRelation('primary', null);
            $cch->setRelation('primaryPhotos', collect());
        }
        if ($cch->b3_status === 'draft' && !\App\Services\WorkflowService::checkCanViewDraft($cch, $sphereUser, 3)) {
            $cch->setRelation('srta', null);
            $cch->setRelation('srtaAttachments', collect());
        }
        if ($cch->b4_status === 'draft' && !\App\Services\WorkflowService::checkCanViewDraft($cch, $sphereUser, 4)) {
            $cch->setRelation('temporary', null);
            $cch->setRelation('temporaryAttachments', collect());
        }
        if ($cch->b5_status === 'draft' && !\App\Services\WorkflowService::checkCanViewDraft($cch, $sphereUser, 5)) {
            $cch->setRelation('requests', collect());
        }
        if ($cch->b6_status === 'draft' && !\App\Services\WorkflowService::checkCanViewDraft($cch, $sphereUser, 6)) {
            $cch->setRelation('ra', null);
            $cch->setRelation('raAttachments', collect());
        }
        if ($cch->b7_status === 'draft' && !\App\Services\WorkflowService::checkCanViewDraft($cch, $sphereUser, 7)) {
            $cch->setRelation('dfa', null);
            $cch->setRelation('dfaAttachments', collect());
        }
        if ($cch->b8_status === 'draft' && !\App\Services\WorkflowService::checkCanViewDraft($cch, $sphereUser, 8)) {
            $cch->setRelation('occurrence', null);
            $cch->setRelation('occurrenceCauses', collect());
        }
        if ($cch->b9_status === 'draft' && !\App\Services\WorkflowService::checkCanViewDraft($cch, $sphereUser, 9)) {
            $cch->setRelation('outflow', null);
            $cch->setRelation('outflowCauses', collect());
        }
        if ($cch->b10_status === 'draft' && !\App\Services\WorkflowService::checkCanViewDraft($cch, $sphereUser, 10)) {
            $cch->setRelation('closing', null);
            $cch->setRelation('closingAttachments', collect());
        }

        return response()->json([
            'success' => true,
            'data'    => $cch
        ]);
    }
}
