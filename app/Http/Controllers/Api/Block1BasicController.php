<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\CchBasic;
use App\Models\CchBasicAttachment;
use App\Models\Cch;
use App\Services\AuditLogService;
use App\Services\AAlertService;
use App\Services\WorkflowService;

class Block1BasicController extends Controller
{
    /**
     * Get Block 1 Details
     */
    public function show($id): JsonResponse
    {
        $cch = \App\Models\Cch::find($id);
        if ($cch) {
            $sphereUser = request()->attributes->get('sphere_user');
            if ($cch->b1_status === 'draft' && !\App\Services\WorkflowService::checkCanViewDraft($cch, $sphereUser, 1)) {
                return response()->json(['success' => false, 'message' => 'Status tiket masih draft, hanya dapat dilihat oleh user yang mengisi.'], 403);
            }
        }

        $basic = CchBasic::with(['customer', 'plant'])->where('cch_id', $id)->first();
        if (!$basic) {
            return response()->json(['success' => false, 'message' => 'Block 1 not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $basic]);
    }

    /**
     * Create or Update Block 1 Details
     */
    public function update(Request $request, $id): JsonResponse
    {
        $cch = Cch::find($id);
        if (!$cch) return response()->json(['success' => false, 'message' => 'CCH not found'], 404);

        $isDraft = $request->boolean('is_draft', false);
        $sphereUser = $request->attributes->get('sphere_user');

        if ($error = WorkflowService::checkBlockAccess($cch, $sphereUser, 1)) {
            return response()->json($error, 403);
        }

        $rules = [
            'subject' => 'required|string|max:200',
            'division_id' => 'required|exists:sphere.departments,id',
            'report_category' => 'required|in:Customer,Market,Internal',
            'customer_id' => 'nullable|string|exists:erp.business_partner,bp_code',
            'plant_of_customer' => 'nullable|integer|exists:m_plants,plant_id',
            'defect_class' => 'required|in:Quality trouble,Delivery trouble',
            'line_stop' => 'required|in:YES,NO',
            'count_by_customer' => 'required|in:YES,NO_Responsibility,NO_No_Responsibility,Undetermined,Not_Applicable',
            'month_of_counted' => 'nullable|date',
            'importance_internal' => 'required|in:A,B,C,M,Not_Applicable',
            'importance_internal_class' => 'nullable|in:1,2,3,4',
            'importance_customer' => 'nullable|in:A,B,C,Undetermined,Not_Applicable',
            'toyota_rank' => 'nullable|in:Critical,Major Function,A,B,C,Undetermined',
            'attachments.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png,xlsx,docx|max:10240' // Multiple file attachments
        ];

        $rules = WorkflowService::applyDraftRules($rules, $isDraft);
        $validated = $request->validate($rules);

        $basicData = collect($validated)->except('attachments')->toArray();

        if (!$isDraft && isset($validated['report_category'])) {
            // Business Rule: Delivery trouble disabled for Market category
            if ($validated['report_category'] === 'Market' && ($validated['defect_class'] ?? '') === 'Delivery trouble') {
                return response()->json([
                    'success' => false,
                    'message' => 'Delivery trouble not allowed for Market category',
                    'errors' => ['defect_class' => ['Delivery trouble tidak diperbolehkan untuk kategori Market.']],
                ], 422);
            }

            // Business Rule: rank class required if internal is A or B
            if (in_array($validated['importance_internal'] ?? '', ['A', 'B']) && empty($validated['importance_internal_class'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Importance internal class is required for rank A or B',
                    'errors' => ['importance_internal_class' => ['Importance internal class wajib diisi untuk rank A atau B.']],
                ], 422);
            }
        }

        $sphereUser = $request->attributes->get('sphere_user');
        $oldBasic = CchBasic::where('cch_id', $id)->first();

        $basic = CchBasic::updateOrCreate(['cch_id' => $id], $basicData);

        // Handle additional file Attachments uploaded during update
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
                    'uploaded_by' => $sphereUser['id']
                ]);
            }
        }

        // Audit Logging — catat setiap kali save/submit sebagai baris baru
        if ($oldBasic) {
            if ($oldBasic->count_by_customer !== $basic->count_by_customer) {
                AuditLogService::log($id, 'Block 1', 'UPDATE_COUNT_BY_CUSTOMER', $oldBasic->count_by_customer, $basic->count_by_customer, $sphereUser['id']);
            }
            if ($oldBasic->importance_internal !== $basic->importance_internal) {
                AuditLogService::log($id, 'Block 1', 'UPDATE_IMPORTANCE_INTERNAL', $oldBasic->importance_internal, $basic->importance_internal, $sphereUser['id']);
            }
        }

        if ($isDraft) {
            AuditLogService::logDraft($id, 'Block 1', $sphereUser['id']);
        } else {
            AuditLogService::logSubmit($id, 'Block 1', $sphereUser['id']);
        }

        // A-Alert trigger if importance_internal === 'A'
        if ($basic->importance_internal === 'A' && (!$oldBasic || $oldBasic->importance_internal !== 'A')) {
            AAlertService::trigger($id, $cch->cch_number, $basic->subject);
        }

        WorkflowService::updateBlockStatus($cch, 1, $isDraft, (int)($sphereUser['id'] ?? 0) ?: null);

        return response()->json([
            'success' => true,
            'message' => 'Block 1 updated successfully',
            'data'    => $cch->load('basic', 'basicAttachments')
        ]);
    }
}
