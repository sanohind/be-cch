<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\CchCause;
use App\Models\Cch;
use App\Models\CchOutflowPic;
use App\Models\CchRequest;
use App\Models\CchUser;
use App\Services\WorkflowService;
use App\Services\AuditLogService;

/**
 * Block 9 - Outflow Analysis
 *
 * Logika form kondisional berdasarkan `defect_made_by` (sama persis dengan Block 8 Occurrence):
 *
 * | defect_made_by  | Fields yang wajib/tersedia                                            |
 * |-----------------|----------------------------------------------------------------------|
 * | Own_plant       | responsible_plant_id*, responsible_office*, process_id*, process_comment |
 * | Other_sanoh_plant | responsible_plant_id*, responsible_office*, process_id*, process_comment |
 * | Supplier        | supplier_id*, supplier_process_id*, supplier_process_comment         |
 * | Unknown         | (tidak ada field tambahan, hanya author_comment)                     |
 */
class Block9OutflowController extends Controller
{
    private function getOwnerId(Cch $cch): ?int
    {
        return (int)($cch->admin_in_charge ?: $cch->input_by) ?: null;
    }

    private function isAdmin(int $roleLevel): bool
    {
        // Level 1 = Superadmin, Level 6 = Supervisor
        return in_array($roleLevel, [1, 6], true);
    }

    private function isManagerOrPresdirGm(int $roleLevel): bool
    {
        // Level 2 = Presdir, Level 4 = GM, Level 5 = Manager
        return in_array($roleLevel, [1, 2, 4, 5], true);
    }

    private function isEligiblePic(int $cchId, int $userId, ?int $userDeptId = null, int $roleLevel = 99): bool
    {
        // PIC harus memiliki departemen yang terdaftar di Block 5 requests
        if (!$userDeptId) return false;

        // PIC harus berperan sebagai Supervisor (6) atau Superadmin (1)
        if (!in_array($roleLevel, [1, 6], true)) return false;

        return CchRequest::where('cch_id', $cchId)
            ->whereNotNull('division_id')
            ->where('division_id', $userDeptId)
            ->exists();
    }

    private function resolvePicUserId(Cch $cch, array $sphereUser): array
    {
        $roleLevel = (int)($sphereUser['role_level'] ?? 99);
        $userId = (int)($sphereUser['id'] ?? 0);
        $ownerId = $this->getOwnerId($cch);

        $isOwnerAdmin = $this->isAdmin($roleLevel) && $ownerId !== null && $ownerId === $userId;
        $isManager = $this->isManagerOrPresdirGm($roleLevel);

        if ($isOwnerAdmin || $isManager) {
            $requested = (int)request()->query('pic_user_id', 0);
            return [$requested ?: $userId, true];
        }

        return [$userId, false];
    }

    public function show($id): JsonResponse
    {
        $cch = \App\Models\Cch::find($id);
        if (!$cch) return response()->json(['success' => false, 'message' => 'CCH not found'], 404);

        $sphereUser = request()->attributes->get('sphere_user') ?? [];
        $roleLevel = (int)($sphereUser['role_level'] ?? 99);
        $userId = (int)($sphereUser['id'] ?? 0);
        $ownerId = $this->getOwnerId($cch);

        $isOwnerAdmin = $this->isAdmin($roleLevel) && $ownerId !== null && $ownerId === $userId;
        $isManager = $this->isManagerOrPresdirGm($roleLevel);
        // PIC: any role level whose division is listed in Block 5 requests
        $userDeptId = (int)($sphereUser['department_id'] ?? 0);
        $isPic = !$isOwnerAdmin && $this->isEligiblePic((int)$id, $userId, $userDeptId, $roleLevel);

        if (!$isOwnerAdmin && !$isManager && !$isPic) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        [$picUserId, $canViewAll] = $this->resolvePicUserId($cch, $sphereUser);

        // Admin owner & manager see all headers; PIC sees all headers as well
        $headers = CchOutflowPic::with(['picUser:id,name,username,department_id'])
            ->where('cch_id', $id)
            ->orderByDesc('updated_at')
            ->get();

        $record = CchOutflowPic::with(['picUser:id,name,username,department_id', 'process', 'supplier', 'supplierProcess'])
            ->where('cch_id', $id)
            ->where('pic_user_id', $picUserId)
            ->first();

        $causes = CchCause::with(['cause'])
            ->where('cch_id', $id)
            ->where('cause_type', 'outflow')
            ->where('pic_user_id', $picUserId)
            ->orderBy('sort_order')
            ->get();

        $attachments = \App\Models\CchOutflowAttachment::where('cch_id', $id)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'headers' => $headers,
                'current' => $record,
                'causes'  => $causes,
                'attachments' => $attachments,
                'pic_user_id' => $picUserId,
                'can_view_all' => $canViewAll,
            ],
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $cch = Cch::find($id);
        if (!$cch) return response()->json(['success' => false, 'message' => 'CCH not found'], 404);

        $isDraft = $request->boolean('is_draft', false);
        $sphereUser = $request->attributes->get('sphere_user');
        $roleLevel = (int)($sphereUser['role_level'] ?? 99);
        $userId = (int)($sphereUser['id'] ?? 0);
        $ownerId = $this->getOwnerId($cch);

        $isOwnerAdmin = $this->isAdmin($roleLevel) && $ownerId !== null && $ownerId === $userId;
        $isManager = $this->isManagerOrPresdirGm($roleLevel);
        $userDeptId = (int)($sphereUser['department_id'] ?? 0);
        $isPic = !$isOwnerAdmin && $this->isEligiblePic((int)$id, $userId, $userDeptId, $roleLevel);

        if ($isManager) {
            return response()->json(['success' => false, 'message' => 'Role anda tidak memiliki akses untuk mengisi Block 9.'], 403);
        }
        if (!$isOwnerAdmin && !$isPic) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $defectMadeBy = $request->input('defect_made_by');
        $picUserId = $isOwnerAdmin ? (int)$request->input('pic_user_id', $userId) : $userId;

        // ── Base rules ──────────────────────────────────────────────────────
        $rules = [
            'defect_made_by' => 'required|in:Own_plant,Other_sanoh_plant,Supplier,Unknown',
        ];

        // ── Own_plant & Sanoh_group ─────────────────────────────────────────
        if (in_array($defectMadeBy, ['Own_plant', 'Other_sanoh_plant'])) {
            $rules['division_id']          = 'required|exists:sphere.departments,id';
            $rules['responsible_office']   = 'required|string|max:200';
            $rules['responsible_department_detail'] = 'nullable|string|max:200';
            $rules['process_id']           = 'required|exists:m_processes,process_id';
            $rules['process_comment']      = 'nullable|string';
        }

        // ── Supplier ────────────────────────────────────────────────────────
        if ($defectMadeBy === 'Supplier') {
            $rules['supplier_id']              = 'required|string|exists:erp.business_partner,bp_code';
            $rules['supplier_process_id']      = 'required|exists:m_processes,process_id';
            $rules['supplier_process_comment'] = 'nullable|string';
        }

        $rules = WorkflowService::applyDraftRules($rules, $isDraft);
        $validated = $request->validate($rules);

        // ── Nullify irrelevant fields ────────────────────────────────────────
        if (in_array($defectMadeBy, ['Own_plant', 'Other_sanoh_plant'])) {
            $validated['supplier_id']              = null;
            $validated['supplier_process_id']      = null;
            $validated['supplier_process_comment'] = null;
        } elseif ($defectMadeBy === 'Supplier') {
            $validated['division_id']              = null;
            $validated['responsible_office']       = null;
            $validated['responsible_department_detail'] = null;
            $validated['process_id']               = null;
            $validated['process_comment']          = null;
        } else { // Unknown
            $validated['division_id']              = null;
            $validated['responsible_office']       = null;
            $validated['responsible_department_detail'] = null;
            $validated['process_id']               = null;
            $validated['process_comment']          = null;
            $validated['supplier_id']              = null;
            $validated['supplier_process_id']      = null;
            $validated['supplier_process_comment'] = null;
        }

        $outflow = CchOutflowPic::updateOrCreate(
            ['cch_id' => $id, 'pic_user_id' => $picUserId],
            array_merge($validated, ['pic_user_id' => $picUserId])
        );

        // Status Block 9 hanya di-submit oleh admin owner (PIC tidak mengubah status master)
        if ($isOwnerAdmin) {
            WorkflowService::updateBlockStatus($cch, 9, $isDraft);
        }

        if ($isDraft) {
            AuditLogService::logDraft($id, 'Block 9', $sphereUser['id']);
        } else {
            AuditLogService::logSubmit($id, 'Block 9', $sphereUser['id']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Block 9 updated successfully',
            'data'    => $outflow
        ]);
    }

    /**
     * Submit Block 9 as a whole (admin owner only).
     * Requires at least 1 PIC header to exist.
     */
    public function submitBlock(Request $request, $id): JsonResponse
    {
        $cch = Cch::find($id);
        if (!$cch) return response()->json(['success' => false, 'message' => 'CCH not found'], 404);

        $sphereUser = $request->attributes->get('sphere_user');
        $roleLevel  = (int)($sphereUser['role_level'] ?? 99);
        $userId     = (int)($sphereUser['id'] ?? 0);
        $ownerId    = $this->getOwnerId($cch);

        if (!$this->isAdmin($roleLevel) || $ownerId === null || $ownerId !== $userId) {
            return response()->json(['success' => false, 'message' => 'Hanya admin penerbit CCH yang dapat submit Block 9.'], 403);
        }

        $headerCount = CchOutflowPic::where('cch_id', $id)->count();
        if ($headerCount < 1) {
            return response()->json(['success' => false, 'message' => 'Minimal 1 header harus ada sebelum Block 9 dapat disubmit.'], 422);
        }

        WorkflowService::updateBlockStatus($cch, 9, false);
        AuditLogService::logSubmit($id, 'Block 9', $userId);

        return response()->json([
            'success' => true,
            'message' => 'Block 9 berhasil disubmit.',
        ]);
    }

    // ─── Root Causes ────────────────────────────────────────────────────────

    public function addCause(Request $request, $id): JsonResponse
    {
        $cch = Cch::find($id);
        if (!$cch) return response()->json(['success' => false, 'message' => 'CCH not found'], 404);

        $sphereUser = $request->attributes->get('sphere_user');
        $roleLevel = (int)($sphereUser['role_level'] ?? 99);
        $userId = (int)($sphereUser['id'] ?? 0);
        $ownerId = $this->getOwnerId($cch);
        $isOwnerAdmin = $this->isAdmin($roleLevel) && $ownerId !== null && $ownerId === $userId;
        $isManager = $this->isManagerOrPresdirGm($roleLevel);
        $userDeptId = (int)($sphereUser['department_id'] ?? 0);
        $isPic = !$isOwnerAdmin && $this->isEligiblePic((int)$id, $userId, $userDeptId, $roleLevel);

        if ($isManager) return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        if (!$isOwnerAdmin && !$isPic) return response()->json(['success' => false, 'message' => 'Forbidden'], 403);

        $validated = $request->validate([
            'primary_factor'   => 'required|in:Uninspected Product,Mishandling,Undetected nonconformity',
            'master_cause_id'  => 'nullable|exists:m_causes,id',
            'cause_description'=> 'required|string',
            'sort_order'       => 'integer',
            'pic_user_id'      => 'nullable|integer',
        ]);

        $picUserId = $isOwnerAdmin ? (int)($validated['pic_user_id'] ?? $userId) : $userId;
        $validated['cch_id']     = $id;
        $validated['pic_user_id']= $picUserId;
        $validated['cause_type'] = 'outflow';
        $validated['sort_order'] = $validated['sort_order'] ?? 1;

        $cause = CchCause::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Outflow cause added successfully',
            'data'    => $cause
        ]);
    }

    public function updateCause(Request $request, $id, $cId): JsonResponse
    {
        $cause = CchCause::where('cch_id', $id)->where('cause_type', 'outflow')->where('cause_id', $cId)->first();
        if (!$cause) return response()->json(['success' => false, 'message' => 'Cause not found'], 404);

        $cch = Cch::find($id);
        if (!$cch) return response()->json(['success' => false, 'message' => 'CCH not found'], 404);

        $sphereUser = $request->attributes->get('sphere_user');
        $roleLevel = (int)($sphereUser['role_level'] ?? 99);
        $userId = (int)($sphereUser['id'] ?? 0);
        $ownerId = $this->getOwnerId($cch);
        $isOwnerAdmin = $this->isAdmin($roleLevel) && $ownerId !== null && $ownerId === $userId;
        $isManager = $this->isManagerOrPresdirGm($roleLevel);
        $userDeptId = (int)($sphereUser['department_id'] ?? 0);
        $isPic = !$isOwnerAdmin && $this->isEligiblePic((int)$id, $userId, $userDeptId, $roleLevel);

        if ($isManager) return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        if (!$isOwnerAdmin && !$isPic) return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        if ($isPic && (int)$cause->pic_user_id !== $userId) return response()->json(['success' => false, 'message' => 'Forbidden'], 403);

        $validated = $request->validate([
            'primary_factor'   => 'required|in:Uninspected Product,Mishandling,Undetected nonconformity',
            'master_cause_id'  => 'nullable|exists:m_causes,id',
            'cause_description'=> 'required|string',
            'sort_order'       => 'integer',
        ]);

        $cause->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Outflow cause updated successfully',
            'data'    => $cause
        ]);
    }

    public function deleteCause(Request $request, $id, $cId): JsonResponse
    {
        $cause = CchCause::where('cch_id', $id)->where('cause_type', 'outflow')->where('cause_id', $cId)->first();
        if (!$cause) return response()->json(['success' => false, 'message' => 'Cause not found'], 404);

        $cch = Cch::find($id);
        if (!$cch) return response()->json(['success' => false, 'message' => 'CCH not found'], 404);

        $sphereUser = $request->attributes->get('sphere_user');
        $roleLevel = (int)($sphereUser['role_level'] ?? 99);
        $userId = (int)($sphereUser['id'] ?? 0);
        $ownerId = $this->getOwnerId($cch);
        $isOwnerAdmin = $this->isAdmin($roleLevel) && $ownerId !== null && $ownerId === $userId;
        $isManager = $this->isManagerOrPresdirGm($roleLevel);
        $userDeptId = (int)($sphereUser['department_id'] ?? 0);
        $isPic = !$isOwnerAdmin && $this->isEligiblePic((int)$id, $userId, $userDeptId, $roleLevel);

        if ($isManager) return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        if (!$isOwnerAdmin && !$isPic) return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        if ($isPic && (int)$cause->pic_user_id !== $userId) return response()->json(['success' => false, 'message' => 'Forbidden'], 403);

        $cause->delete();

        return response()->json([
            'success' => true,
            'message' => 'Outflow cause deleted successfully'
        ]);
    }
}
