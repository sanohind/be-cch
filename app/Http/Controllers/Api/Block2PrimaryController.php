<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\CchPrimary;
use App\Models\Cch;
use App\Services\AuditLogService;
use App\Services\WorkflowService;

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
            'qa_director_comment' => 'nullable|string',
            'author_comment' => 'nullable|string'
        ];

        $rules = WorkflowService::applyDraftRules($rules, $isDraft);
        $validated = $request->validate($rules);

        $oldPrimary = CchPrimary::where('cch_id', $id)->first();

        $primary = CchPrimary::updateOrCreate(['cch_id' => $id], $validated);

        if ($oldPrimary && array_key_exists('defect_qty', $validated) && $oldPrimary->defect_qty != $primary->defect_qty) {
            AuditLogService::log($id, 'Block 2', 'UPDATE_DEFECT_QTY', $oldPrimary->defect_qty, $primary->defect_qty, $sphereUser['id']);
        }

        if ($isDraft) {
            AuditLogService::logDraft($id, 'Block 2', $sphereUser['id']);
        } else {
            AuditLogService::logSubmit($id, 'Block 2', $sphereUser['id']);
        }

        WorkflowService::updateBlockStatus($cch, 2, $isDraft);

        return response()->json([
            'success' => true,
            'message' => 'Block 2 updated successfully',
            'data'    => $primary
        ]);
    }
}
