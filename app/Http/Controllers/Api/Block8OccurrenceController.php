<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\CchOccurrence;
use App\Models\CchCause;
use App\Models\Cch;
use App\Services\WorkflowService;
use App\Services\AuditLogService;

/**
 * Block 8 - Occurrence Analysis
 *
 * Logika form kondisional berdasarkan `defect_made_by`:
 *
 * | defect_made_by  | Fields yang wajib/tersedia                                            |
 * |-----------------|----------------------------------------------------------------------|
 * | Own_plant       | responsible_plant_id*, responsible_office*, process_id*, process_comment |
 * | Sanoh_group     | responsible_plant_id*, responsible_office*, process_id*, process_comment |
 * | Supplier        | supplier_id*, supplier_process_id*, supplier_process_comment         |
 * | Unknown         | (tidak ada field tambahan, hanya author_comment)                     |
 *
 * Catatan:
 * - `responsible_plant_id` → FK ke m_plants (dropdown plant lokal)
 * - `responsible_office`   → string bebas (nama office/divisi)
 * - `process_id`           → FK ke m_processes (dropdown proses produksi)
 * - `supplier_id`          → bp_code dari ERP business_partner (supplier role)
 * - `supplier_process_id`  → FK ke m_processes (dropdown proses supplier)
 */
class Block8OccurrenceController extends Controller
{
    public function show($id): JsonResponse
    {
        $cch = \App\Models\Cch::find($id);
        if ($cch) {
            $sphereUser = request()->attributes->get('sphere_user');
            if ($cch->b8_status === 'draft' && !WorkflowService::checkCanViewDraft($cch, $sphereUser, 8)) {
                return response()->json(['success' => false, 'message' => 'Status tiket masih draft, hanya dapat dilihat oleh user yang mengisi.'], 403);
            }
        }

        $occurrence = CchOccurrence::with([
            'responsiblePlant',         // Plant lokal (Own_plant / Sanoh_group)
            'process',                  // Process (Own_plant / Sanoh_group)
            'supplier',                 // BusinessPartner dari ERP (Supplier)
            'supplierProcess',          // Process milik supplier
            'causes.cause',         // masterCause dari m_causes
        ])->where('cch_id', $id)->first();

        if (!$occurrence) {
            return response()->json(['success' => false, 'message' => 'Block 8 not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $occurrence]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $cch = Cch::find($id);
        if (!$cch) return response()->json(['success' => false, 'message' => 'CCH not found'], 404);

        $isDraft = $request->boolean('is_draft', false);
        $sphereUser = $request->attributes->get('sphere_user');

        if ($error = WorkflowService::checkBlockAccess($cch, $sphereUser, 8)) {
            return response()->json($error, 403);
        }

        $defectMadeBy = $request->input('defect_made_by');

        // ── Base rules (selalu ada) ─────────────────────────────────────────
        $rules = [
            'defect_made_by' => 'required|in:Own_plant,Sanoh_group,Supplier,Unknown',
            'author_comment' => 'nullable|string',
        ];

        // ── Own_plant & Sanoh_group: Responsible Plant + Process ────────────
        if (in_array($defectMadeBy, ['Own_plant', 'Sanoh_group'])) {
            $rules['responsible_plant_id'] = 'required|exists:m_plants,plant_id';
            $rules['responsible_office']   = 'required|string|max:200';
            $rules['process_id']           = 'required|exists:m_processes,process_id';
            $rules['process_comment']      = 'nullable|string';
        }

        // ── Supplier: BP dari ERP + Supplier Process ────────────────────────
        if ($defectMadeBy === 'Supplier') {
            $rules['supplier_id']              = 'required|string|exists:erp.business_partner,bp_code';
            $rules['supplier_process_id']      = 'required|exists:m_processes,process_id';
            $rules['supplier_process_comment'] = 'nullable|string';
        }

        // ── Unknown: tidak ada field tambahan ───────────────────────────────
        // (tidak ada rules tambahan)

        $rules = WorkflowService::applyDraftRules($rules, $isDraft);
        $validated = $request->validate($rules);

        // ── Nullify fields yang tidak relevan dengan defect_made_by ─────────
        if (in_array($defectMadeBy, ['Own_plant', 'Sanoh_group'])) {
            $validated['supplier_id']              = null;
            $validated['supplier_process_id']      = null;
            $validated['supplier_process_comment'] = null;
        } elseif ($defectMadeBy === 'Supplier') {
            $validated['responsible_plant_id']   = null;
            $validated['responsible_office']     = null;
            $validated['responsible_plant_detail'] = null;
            $validated['process_id']             = null;
            $validated['process_comment']        = null;
        } else { // Unknown
            $validated['responsible_plant_id']     = null;
            $validated['responsible_office']       = null;
            $validated['responsible_plant_detail'] = null;
            $validated['process_id']               = null;
            $validated['process_comment']          = null;
            $validated['supplier_id']              = null;
            $validated['supplier_process_id']      = null;
            $validated['supplier_process_comment'] = null;
        }

        $occurrence = CchOccurrence::updateOrCreate(['cch_id' => $id], $validated);

        WorkflowService::updateBlockStatus($cch, 8, $isDraft);

        if ($isDraft) {
            AuditLogService::logDraft($id, 'Block 8', $sphereUser['id']);
        } else {
            AuditLogService::logSubmit($id, 'Block 8', $sphereUser['id']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Block 8 updated successfully',
            'data'    => $occurrence
        ]);
    }

    // ─── Root Causes ────────────────────────────────────────────────────────

    public function addCause(Request $request, $id): JsonResponse
    {
        $cch = Cch::find($id);
        if (!$cch) return response()->json(['success' => false, 'message' => 'CCH not found'], 404);

        $sphereUser = $request->attributes->get('sphere_user');
        if ($error = WorkflowService::checkBlockAccess($cch, $sphereUser, 8)) {
            return response()->json($error, 403);
        }

        $validated = $request->validate([
            'primary_factor'   => 'required|in:Man,Method,Machine,Material,Design',
            'master_cause_id'  => 'nullable|exists:m_causes,id',
            'cause_description'=> 'required|string',
            'sort_order'       => 'integer',
        ]);

        $validated['cch_id']     = $id;
        $validated['cause_type'] = 'occurrence';
        $validated['sort_order'] = $validated['sort_order'] ?? 1;

        $cause = CchCause::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Occurrence cause added successfully',
            'data'    => $cause
        ]);
    }

    public function updateCause(Request $request, $id, $cId): JsonResponse
    {
        $cause = CchCause::where('cch_id', $id)->where('cause_type', 'occurrence')->where('cause_id', $cId)->first();
        if (!$cause) return response()->json(['success' => false, 'message' => 'Cause not found'], 404);

        $cch = Cch::find($id);
        if (!$cch) return response()->json(['success' => false, 'message' => 'CCH not found'], 404);

        $sphereUser = $request->attributes->get('sphere_user');
        if ($error = WorkflowService::checkBlockAccess($cch, $sphereUser, 8)) {
            return response()->json($error, 403);
        }

        $validated = $request->validate([
            'primary_factor'   => 'required|in:Man,Method,Machine,Material,Design',
            'master_cause_id'  => 'nullable|exists:m_causes,id',
            'cause_description'=> 'required|string',
            'sort_order'       => 'integer',
        ]);

        $cause->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Occurrence cause updated successfully',
            'data'    => $cause
        ]);
    }

    public function deleteCause(Request $request, $id, $cId): JsonResponse
    {
        $cause = CchCause::where('cch_id', $id)->where('cause_type', 'occurrence')->where('cause_id', $cId)->first();
        if (!$cause) return response()->json(['success' => false, 'message' => 'Cause not found'], 404);

        $cch = Cch::find($id);
        if (!$cch) return response()->json(['success' => false, 'message' => 'CCH not found'], 404);

        $sphereUser = $request->attributes->get('sphere_user');
        if ($error = WorkflowService::checkBlockAccess($cch, $sphereUser, 8)) {
            return response()->json($error, 403);
        }

        $cause->delete();

        return response()->json([
            'success' => true,
            'message' => 'Occurrence cause deleted successfully'
        ]);
    }
}
