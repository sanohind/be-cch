<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\CchPrimary;
use App\Models\Cch;
use App\Services\AuditLogService;
use App\Services\WorkflowService;
use App\Models\CchPrimaryPhoto;
use Illuminate\Support\Facades\Storage;

class Block2PrimaryController extends Controller
{
    public function show($id): JsonResponse
    {
        $cch = \App\Models\Cch::find($id);
        if ($cch) {
            $sphereUser = request()->attributes->get('sphere_user');
            if ($cch->b2_status === 'draft' && !\App\Services\WorkflowService::checkCanViewDraft($cch, $sphereUser, 2)) {
                return response()->json(['success' => false, 'message' => 'Status tiket masih draft, hanya dapat dilihat oleh user yang mengisi.'], 403);
            }
        }

        $primary = CchPrimary::with(['failureMode', 'productCategory', 'productFamily'])->where('cch_id', $id)->first();
        if (!$primary) {
            return response()->json(['success' => false, 'message' => 'Block 2 not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $primary]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $cch = Cch::find($id);
        if (!$cch) return response()->json(['success' => false, 'message' => 'CCH not found'], 404);

        $isDraft = $request->boolean('is_draft', false);
        $sphereUser = $request->attributes->get('sphere_user');

        if ($error = WorkflowService::checkBlockAccess($cch, $sphereUser, 2)) {
            return response()->json($error, 403);
        }

        $rules = [
            'failure_mode_id' => 'required|exists:m_failure_modes,failure_mode_id',
            'defect_found_date' => 'required|date',
            'defect_found_date_end' => 'nullable|date|after_or_equal:defect_found_date',
            'defect_qty' => 'required|integer|min:0',
            'comment' => 'nullable|string',
            'part_number' => 'required|string|max:100',
            'part_name' => 'required|string|max:200', 
            'product_category_id' => 'required|exists:m_product_categories,category_id',
            'product_family_id' => 'required|exists:m_product_families,family_id',
            'phase' => 'required|in:Trial,Trail_for_mass_production,Mass_production_first_3months,Mass_production_after_3months,Service_parts',
            'product_supply_form' => 'required|in:Knock_down_product,Pass_through_product,Not_subject',
            'estimation_occurrence_outflow' => 'nullable|string',
            'possibility_spreading' => 'required|in:YES,NO',
            'spreading_detail' => 'nullable|string',
            'qa_director_comment' => 'nullable|string',
            'author_comment' => 'nullable|string',
            'overall_files.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png,xlsx,docx|max:10240',
            'rejection_files.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png,xlsx,docx|max:10240',
            'attachment_files.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png,xlsx,docx|max:10240'
        ];

        $rules = WorkflowService::applyDraftRules($rules, $isDraft);
        $validated = $request->validate($rules);

        // spreading_detail required when possibility_spreading = YES (non-draft)
        if (!$isDraft && ($validated['possibility_spreading'] ?? '') === 'YES' && empty(trim($validated['spreading_detail'] ?? ''))) {
            return response()->json([
                'success' => false,
                'message' => 'Detail is required when Possibility of defects spreading is Yes',
                'errors' => ['spreading_detail' => ['Detail is required when Possibility of defects spreading is Yes.']],
            ], 422);
        }

        $oldPrimary = CchPrimary::where('cch_id', $id)->first();

        $primaryData = collect($validated)->except(['overall_files', 'rejection_files', 'attachment_files'])->toArray();
        if ($isDraft) {
            $primaryData = WorkflowService::sanitizeDraftData($primaryData, 2);
        }
        $primary = CchPrimary::updateOrCreate(['cch_id' => $id], $primaryData);

        // Upload Attachments
        foreach (['overall_files' => 'overall', 'rejection_files' => 'rejection_area', 'attachment_files' => null] as $key => $type) {
            if ($request->hasFile($key)) {
                foreach ($request->file($key) as $file) {
                    $originalName = $file->getClientOriginalName();
                    $fileName = date('Ymd_His') . '_' . preg_replace('/[^A-Za-z0-9\-\_\.]/', '_', $originalName);
                    $path = "cch/{$cch->cch_id}/primary";
                    $storedPath = $file->storeAs($path, $fileName, 'public');

                    CchPrimaryPhoto::create([
                        'cch_id' => $cch->cch_id,
                        'photo_type' => $type ?? 'overall', // Fallback as 'overall' since only 2 types in ENUM
                        'file_name' => $originalName,
                        'file_path' => $storedPath,
                        'file_size_kb' => round($file->getSize() / 1024, 2),
                        'uploaded_by' => $sphereUser['id']
                    ]);
                }
            }
        }

        if ($oldPrimary && array_key_exists('defect_qty', $validated) && $oldPrimary->defect_qty != $primary->defect_qty) {
            AuditLogService::log($id, 'Block 2', 'UPDATE_DEFECT_QTY', $oldPrimary->defect_qty, $primary->defect_qty, $sphereUser['id']);
        }

        if ($isDraft) {
            AuditLogService::logDraft($id, 'Block 2', $sphereUser['id']);
        } else {
            AuditLogService::logSubmit($id, 'Block 2', $sphereUser['id']);
        }

        WorkflowService::updateBlockStatus($cch, 2, $isDraft, (int) $sphereUser['id']);

        return response()->json([
            'success' => true,
            'message' => 'Block 2 updated successfully',
            'data'    => $primary
        ]);
    }
}
