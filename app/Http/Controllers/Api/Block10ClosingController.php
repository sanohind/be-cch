<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\CchClosing;
use App\Models\CchClosingAttachment;
use App\Models\Cch;
use App\Models\CchUser;
use Carbon\Carbon;
use App\Services\AuditLogService;
use App\Services\WorkflowService;
use App\Services\CchNotificationService;

/**
 * Block 10 - Closing
 *
 * Access rules for Close tab:
 *
 * ┌──────────────────────────────────────────────────────────────────────────┐
 * │ Rank A (importance_internal = 'A')                                       │
 * │  • Superadmin (1)       → View + Fill form + Close                       │
 * │  • Presdir/GM (4)       → View + Close (cannot fill form via admin flow) │
 * │  • Manager (5) + dept=Basic.division_id → View + Author Comment only     │
 * │  • Admin owner (2)      → View + Fill form (cannot Close)                │
 * ├──────────────────────────────────────────────────────────────────────────┤
 * │ Non-Rank A (B, C, etc.)                                                  │
 * │  • Superadmin (1)       → View + Fill form + Close                       │
 * │  • Manager (5) + dept=Basic.division_id → View + Close                   │
 * │  • Admin owner (2)      → View + Fill form (cannot Close)                │
 * └──────────────────────────────────────────────────────────────────────────┘
 */
class Block10ClosingController extends Controller
{
    // ─── Constants ─────────────────────────────────────────────────────────────

    /** ID departemen QC di Sphere (sphere.departments.id = 7) */
    private const QC_DEPT_ID = 7;

    // ─── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Resolve importance, CCH division, and user division from request context.
     *
     * isDeptMatch  : user division_id === t_cch.division_id (Block 1 division)
     * isOwnerAdmin : user is the CCH creator (input_by) AND role is admin/superadmin
     * isMgrQc      : user is Manager (role_level=5) AND sphere_department_id=7 (QC)
     */
    private function resolveContext(Cch $cch, array $sphereUser): array
    {
        $cch->loadMissing('basic');
        $importance    = $cch->basic?->importance_internal ?? '';
        // Referensi divisi CCH: t_cch.division_id (diset saat membuat CCH di Block 1)
        $cchDivisionId = $cch->division_id ?? null;

        $userId          = (int)($sphereUser['id'] ?? 0);
        $roleLevel       = (int)($sphereUser['role_level'] ?? 99);
        $userDivisionId  = (int)($sphereUser['department_id'] ?? 0);
        $isDeptMatch     = $cchDivisionId !== null
                           && $userDivisionId !== 0
                           && (int)$cchDivisionId === $userDivisionId;

        // isMgrQc is no longer strictly used for QC, but kept for compatibility or removed.
        // We will remove it from ctx to avoid confusion.
        
        // isOwnerAdmin: hanya creator (input_by) dengan role supervisor/superadmin (1, 6)
        $isOwnerAdmin = in_array($roleLevel, [1, 6], true)
                        && $userId !== 0
                        && $userId === (int)$cch->input_by;

        return compact('importance', 'cchDivisionId', 'userId', 'roleLevel', 'isDeptMatch', 'isOwnerAdmin');
    }

    /**
     * Check if the user is allowed to VIEW the Close tab.
     * Returns null on success, or an error array on failure.
     */
    private function checkViewAccess(Cch $cch, array $sphereUser): ?array
    {
        $ctx = $this->resolveContext($cch, $sphereUser);

        // Superadmin
        if ($ctx['roleLevel'] === 1) return null;

        // Admin owner (fills form)
        if ($ctx['isOwnerAdmin']) return null;

        // Rank A: Presdir/GM (2, 4)
        if ($ctx['importance'] === 'A' && in_array($ctx['roleLevel'], [2, 4])) {
            return null;
        }

        // Manager: hanya bisa melihat tab close jika departemennya sesuai atau request departemennya
        // Catatan: logika ini untuk tombol/tab view.
        if ($ctx['roleLevel'] === 5) {
            return null; // Manager generally can view it if they can access the CCH
        }

        return ['success' => false, 'message' => 'Anda tidak memiliki akses ke tab Close.'];
    }

    /**
     * Check if the user is allowed to SUBMIT CLOSE (trigger final close).
     * Returns null on success, or an error array on failure.
     */
    private function checkCloseAccess(Cch $cch, array $sphereUser): ?array
    {
        $ctx = $this->resolveContext($cch, $sphereUser);

        // Superadmin always
        if ($ctx['roleLevel'] === 1) return null;

        // Rank A: only Presdir/GM
        if ($ctx['importance'] === 'A') {
            if (in_array($ctx['roleLevel'], [2, 4])) return null;
            return ['success' => false, 'message' => 'Untuk CCH Rank A, hanya Presdir/GM yang dapat melakukan Close Application.'];
        }

        // Non-A: hanya Manager departemen pembuat tiket
        if ($ctx['roleLevel'] === 5) {
            if ($ctx['isDeptMatch']) return null;
            return ['success' => false, 'message' => 'Hanya Manager dari departemen pembuat CCH yang dapat melakukan Close Application.'];
        }

        return ['success' => false, 'message' => 'Hanya Manager dari departemen terkait yang dapat melakukan Close Application untuk CCH ini.'];
    }

    // ─── Endpoints ─────────────────────────────────────────────────────────────

    public function show($id): JsonResponse
    {
        $cch = Cch::find($id);
        if (!$cch) return response()->json(['success' => false, 'message' => 'CCH not found'], 404);

        $sphereUser = request()->attributes->get('sphere_user');

        // Check view access
        if ($error = $this->checkViewAccess($cch, $sphereUser)) {
            return response()->json($error, 403);
        }

        // If still draft, only owner-admin can see it
        if ($cch->b10_status === 'draft' && !WorkflowService::checkCanViewDraft($cch, $sphereUser, 10)) {
            return response()->json(['success' => false, 'message' => 'Status tiket masih draft, hanya dapat dilihat oleh user yang mengisi.'], 403);
        }

        $closing = CchClosing::with(['currency', 'submittedBy', 'approvedBy', 'attachments.uploadedBy'])
            ->where('cch_id', $id)->first();

        // Jika record belum ada (belum pernah di-save), kembalikan data:null + meta permission
        // agar frontend bisa menampilkan form kosong untuk pertama kali.
        $ctx = $this->resolveContext($cch, $sphereUser);
        if (!$closing) {
            return response()->json([
                'success' => true,
                'data'    => null,
                'meta'    => [
                    'can_close'     => $this->checkCloseAccess($cch, $sphereUser) === null,
                    'can_edit_form' => $ctx['isOwnerAdmin'] || $ctx['roleLevel'] === 1,
                    'importance'    => $ctx['importance'],
                    'is_dept_match' => $ctx['isDeptMatch'],
                ],
            ]);
        }

        // Include permission flags so frontend can adapt UI
        return response()->json([
            'success' => true,
            'data'    => $closing,
            'meta'    => [
                'can_close'     => $this->checkCloseAccess($cch, $sphereUser) === null,
                'can_edit_form' => $ctx['isOwnerAdmin'] || $ctx['roleLevel'] === 1,
                'importance'    => $ctx['importance'],
                'is_dept_match' => $ctx['isDeptMatch'],
            ],
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $cch = Cch::find($id);
        if (!$cch) return response()->json(['success' => false, 'message' => 'CCH not found'], 404);

        $isDraft    = $request->boolean('is_draft', false);
        $sphereUser = $request->attributes->get('sphere_user');

        // Only admin owner (or superadmin) can fill the form fields
        if ($error = WorkflowService::checkBlockAccess($cch, $sphereUser, 10)) {
            return response()->json($error, 403);
        }

        $rules = [
            'importance_customer_final' => 'required|in:A,B,C,Undetermined,Not_Applicable',
            'count_by_customer_final'   => 'required|in:YES,NO_Responsibility,NO_No_Responsibility,Undetermined',
            'countermeasure_occurrence' => 'required|string',
            'countermeasure_outflow'    => 'required|string',
            'currency_id'        => 'required|exists:m_currencies,currency_id',
            'cost_to_customer'   => 'nullable|numeric|min:0',
            'cost_to_external'   => 'nullable|numeric|min:0',
            'cost_internal'      => 'nullable|numeric|min:0',
            'is_recurrence'          => 'required|in:YES,NO',
            'horizontal_deployment'  => 'required|in:YES,NO',
            'author_comment' => 'nullable|string',
        ];

        $rules     = WorkflowService::applyDraftRules($rules, $isDraft);
        $validated = $request->validate($rules);
        if ($isDraft) {
            $validated = WorkflowService::sanitizeDraftData($validated, 10);
        }

        $closing = CchClosing::updateOrCreate(['cch_id' => $id], $validated);

        WorkflowService::updateBlockStatus($cch, 10, $isDraft);

        if ($isDraft) {
            AuditLogService::logDraft($id, 'Block 10', $sphereUser['id']);
        } else {
            AuditLogService::logSubmit($id, 'Block 10', $sphereUser['id']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Block 10 updated successfully',
            'data'    => $closing
        ]);
    }

    // ─── Item 5: Upload Final Report / Attachment ─────────────────────────────

    public function uploadAttachment(Request $request, $id): JsonResponse
    {
        $cch = Cch::find($id);
        if (!$cch) return response()->json(['success' => false, 'message' => 'CCH not found'], 404);

        $sphereUser = $request->attributes->get('sphere_user');
        if ($error = WorkflowService::checkBlockAccess($cch, $sphereUser, 10)) {
            return response()->json($error, 403);
        }

        $validated = $request->validate([
            'file'            => 'required|file|max:10240',
            'attachment_type' => 'required|in:final_report,supporting',
        ]);

        $file     = $request->file('file');
        $path     = $file->store('cch/closing', 'public');
        $sizeKb   = (int) round($file->getSize() / 1024);

        $attachment = CchClosingAttachment::create([
            'cch_id'          => $id,
            'attachment_type' => $validated['attachment_type'],
            'file_name'       => $file->getClientOriginalName(),
            'file_path'       => $path,
            'file_size_kb'    => $sizeKb,
            'uploaded_by'     => $sphereUser['id'],
            'uploaded_at'     => Carbon::now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Attachment uploaded successfully',
            'data'    => $attachment
        ]);
    }

    public function deleteAttachment(Request $request, $id, $attachId): JsonResponse
    {
        $attachment = CchClosingAttachment::where('cch_id', $id)->where('attachment_id', $attachId)->first();
        if (!$attachment) return response()->json(['success' => false, 'message' => 'Attachment not found'], 404);

        $cch = Cch::find($id);
        if (!$cch) return response()->json(['success' => false, 'message' => 'CCH not found'], 404);

        $sphereUser = $request->attributes->get('sphere_user');
        if ($error = WorkflowService::checkBlockAccess($cch, $sphereUser, 10)) {
            return response()->json($error, 403);
        }

        \Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();

        return response()->json(['success' => true, 'message' => 'Attachment deleted successfully']);
    }

    // ─── Approval Flow ────────────────────────────────────────────────────────

    public function submitClose(Request $request, $id): JsonResponse
    {
        $cch = Cch::find($id);
        if (!$cch) return response()->json(['success' => false, 'message' => 'CCH not found'], 404);

        $sphereUser = $request->attributes->get('sphere_user');

        if (($cch->status ?? '') === 'closed') {
            return response()->json(['success' => false, 'message' => 'CCH sudah Closed'], 400);
        }

        // Check close permission using new role logic
        if ($error = $this->checkCloseAccess($cch, $sphereUser)) {
            return response()->json($error, 403);
        }

        // Block 10 form must be submitted first
        if (($cch->b10_status ?? null) !== 'submitted') {
            return response()->json(['success' => false, 'message' => 'Block 10 belum disubmit secara final'], 400);
        }

        $oldStatus = $cch->status;
        $cch->update([
            'status'    => 'closed',
            'closed_at' => Carbon::now(),
        ]);

        CchClosing::updateOrCreate(['cch_id' => $id], [
            'submitted_by' => $sphereUser['id'],
            'submitted_at' => Carbon::now()
        ]);

        AuditLogService::log($id, 'Block 10', 'CLOSE_APPLICATION', $oldStatus, 'closed', $sphereUser['id']);

        // Send email notification to creator
        $closerName = $sphereUser['name'] ?? $sphereUser['username'] ?? 'Unknown';
        CchNotificationService::notifyCchClosed($cch, $closerName);

        return response()->json([
            'success' => true,
            'message' => 'CCH closed successfully',
            'data'    => $cch
        ]);
    }

    public function approveClose(Request $request, $id): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Approve flow sudah tidak digunakan pada proses bisnis terbaru.',
        ], 410);
    }
}
