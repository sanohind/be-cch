<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\CchClosing;
use App\Models\CchClosingAttachment;
use App\Models\Cch;
use Carbon\Carbon;
use App\Services\AuditLogService;
use App\Services\WorkflowService;

/**
 * Block 10 - Closing
 *
 * Form sesuai gambar referensi:
 *   1. importance_customer_final  → Level of Importance by Customer Information
 *   2. count_by_customer_final    → Count by Customer
 *   3. countermeasure_occurrence  → Countermeasure Against Occurrence (text panjang)
 *   4. countermeasure_outflow     → Countermeasure Against Outflow (text panjang)
 *   5. Final Report               → Upload file via endpoint terpisah (attachment_type = 'final_report')
 *   6. Total Claim Costs          → currency_id, cost_to_customer, cost_to_external, cost_internal
 *                                   (cost_total = GENERATED COLUMN, otomatis dihitung DB)
 *   7. is_recurrence              → Recurrence or Non-recurrence (YES/NO)
 *   8. horizontal_deployment      → Request for Horizontal Deployment (YES/NO)
 *   9. author_comment             → Author Comment
 */
class Block10ClosingController extends Controller
{
    public function show($id): JsonResponse
    {
        $cch = Cch::find($id);
        if ($cch) {
            $sphereUser = request()->attributes->get('sphere_user');
            if ($cch->b10_status === 'draft' && !WorkflowService::checkCanViewDraft($cch, $sphereUser, 10)) {
                return response()->json(['success' => false, 'message' => 'Status tiket masih draft, hanya dapat dilihat oleh user yang mengisi.'], 403);
            }
        }

        $closing = CchClosing::with(['currency', 'submittedBy', 'approvedBy', 'attachments.uploadedBy'])
            ->where('cch_id', $id)->first();

        if (!$closing) {
            return response()->json(['success' => false, 'message' => 'Block 10 not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $closing]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $cch = Cch::find($id);
        if (!$cch) return response()->json(['success' => false, 'message' => 'CCH not found'], 404);

        $isDraft = $request->boolean('is_draft', false);
        $sphereUser = $request->attributes->get('sphere_user');

        if ($error = WorkflowService::checkBlockAccess($cch, $sphereUser, 10)) {
            return response()->json($error, 403);
        }

        $rules = [
            // Item 1 — Level of Importance by Customer Information
            'importance_customer_final' => 'required|in:A,B,C,Undetermined,Not_Applicable',

            // Item 2 — Count by Customer
            'count_by_customer_final'   => 'required|in:YES,NO_Responsibility,NO_No_Responsibility,Undetermined',

            // Item 3 — Countermeasure Against Occurrence
            'countermeasure_occurrence' => 'required|string',

            // Item 4 — Countermeasure Against Outflow
            'countermeasure_outflow'    => 'required|string',

            // Item 5 — Final Report: upload via endpoint /upload-attachment

            // Item 6 — Total Claim Costs
            'currency_id'        => 'required|exists:m_currencies,currency_id',
            'cost_to_customer'   => 'nullable|numeric|min:0',
            'cost_to_external'   => 'nullable|numeric|min:0',
            'cost_internal'      => 'nullable|numeric|min:0',

            // Item 7 — Recurrence or Non-recurrence
            'is_recurrence'          => 'required|in:YES,NO',

            // Item 8 — Request for Horizontal Deployment
            'horizontal_deployment'  => 'required|in:YES,NO',

            // Item 9 — Author Comment
            'author_comment' => 'nullable|string',
        ];

        $rules = WorkflowService::applyDraftRules($rules, $isDraft);
        $validated = $request->validate($rules);

        $closing = CchClosing::updateOrCreate(['cch_id' => $id], $validated);

        WorkflowService::updateBlockStatus($cch, 10, $isDraft);

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
            'file'            => 'required|file|max:10240', // Max 10 MB
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
        if ($error = WorkflowService::checkBlockAccess($cch, $sphereUser, 10)) {
            return response()->json($error, 403);
        }

        $cch->update(['status' => 'closed_by_manager']);
        WorkflowService::updateBlockStatus($cch, 10, false);

        CchClosing::updateOrCreate(['cch_id' => $id], [
            'submitted_by' => $sphereUser['id'],
            'submitted_at' => Carbon::now()
        ]);

        AuditLogService::log($id, 'Block 10', 'SUBMIT_CLOSE_REQUEST', $cch->getOriginal('status'), 'closed_by_manager', $sphereUser['id']);

        return response()->json([
            'success' => true,
            'message' => 'CCH close requested successfully',
            'data'    => $cch
        ]);
    }

    public function approveClose(Request $request, $id): JsonResponse
    {
        $cch = Cch::find($id);
        if (!$cch) return response()->json(['success' => false, 'message' => 'CCH not found'], 404);

        $sphereUser = $request->attributes->get('sphere_user');

        // Hanya Requester asli tiket (input_by) yang boleh finalisasi close
        if ($cch->input_by != $sphereUser['id']) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Requester yang membuat tiket ini yang dapat approve closing'
            ], 403);
        }

        if ($cch->status !== 'closed_by_manager') {
            return response()->json(['success' => false, 'message' => 'Status tiket belum di-submit oleh manager'], 400);
        }

        $cch->update([
            'status'    => 'closed',
            'closed_at' => Carbon::now()
        ]);

        CchClosing::updateOrCreate(['cch_id' => $id], [
            'approved_by' => $sphereUser['id'],
            'approved_at' => Carbon::now()
        ]);

        AuditLogService::log($id, 'Block 10', 'APPROVE_CLOSE', 'closed_by_manager', 'closed', $sphereUser['id']);

        return response()->json([
            'success' => true,
            'message' => 'CCH close approved successfully',
            'data'    => $cch
        ]);
    }
}
