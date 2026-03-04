<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\CchSrta;
use App\Models\CchSrtaScreening;
use App\Models\Cch;
use App\Services\WorkflowService;
use App\Services\AuditLogService;

class Block3SrtaController extends Controller
{
    public function show($id): JsonResponse
    {
        $cch = \App\Models\Cch::find($id);
        if ($cch) {
            $sphereUser = request()->attributes->get('sphere_user');
            if ($cch->b3_status === 'draft' && !\App\Services\WorkflowService::checkCanViewDraft($cch, $sphereUser, 3)) {
                return response()->json(['success' => false, 'message' => 'Status tiket masih draft, hanya dapat dilihat oleh user yang mengisi.'], 403);
            }
        }

        $srta = CchSrta::with(['screening'])->where('cch_id', $id)->first();
        if (!$srta) {
            return response()->json(['success' => false, 'message' => 'Block 3 not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $srta]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $cch = Cch::find($id);
        if (!$cch) return response()->json(['success' => false, 'message' => 'CCH not found'], 404);

        $isDraft = $request->boolean('is_draft', false);
        $sphereUser = $request->attributes->get('sphere_user');

        if ($error = WorkflowService::checkBlockAccess($cch, $sphereUser, 3)) {
            return response()->json($error, 403);
        }

        $rules = [
            'author_comment' => 'nullable|string'
        ];

        $rules = WorkflowService::applyDraftRules($rules, $isDraft);
        $validated = $request->validate($rules);

        $srta = CchSrta::updateOrCreate(['cch_id' => $id], $validated);

        WorkflowService::updateBlockStatus($cch, 3, $isDraft);

        if ($isDraft) {
            AuditLogService::logDraft($id, 'Block 3', $sphereUser['id']);
        } else {
            AuditLogService::logSubmit($id, 'Block 3', $sphereUser['id']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Block 3 updated successfully',
            'data'    => $srta
        ]);
    }

    public function addScreening(Request $request, $id): JsonResponse
    {
        $cch = Cch::find($id);
        if (!$cch) return response()->json(['success' => false, 'message' => 'CCH not found'], 404);

        $validated = $request->validate([
            'location' => 'required|in:Customer_Completed_cars,Customer_Sorting,Depot,Internal,Supplier',
            'ng_qty' => 'required|integer|min:0',
            'ok_qty' => 'required|integer|min:0',
            'action_taken' => 'required|in:Conversion,Replacement,None',
            'action_result' => 'nullable|string'
        ]);

        $validated['cch_id'] = $id;
        $screening = CchSrtaScreening::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Screening added successfully',
            'data'    => $screening
        ]);
    }

    public function updateScreening(Request $request, $id, $sId): JsonResponse
    {
        $screening = CchSrtaScreening::where('cch_id', $id)->where('screening_id', $sId)->first();
        if (!$screening) return response()->json(['success' => false, 'message' => 'Screening not found'], 404);

        $validated = $request->validate([
            'location' => 'required|in:Customer_Completed_cars,Customer_Sorting,Depot,Internal,Supplier',
            'ng_qty' => 'required|integer|min:0',
            'ok_qty' => 'required|integer|min:0',
            'action_taken' => 'required|in:Conversion,Replacement,None',
            'action_result' => 'nullable|string'
        ]);

        $screening->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Screening updated successfully',
            'data'    => $screening
        ]);
    }

    public function deleteScreening($id, $sId): JsonResponse
    {
        $screening = CchSrtaScreening::where('cch_id', $id)->where('screening_id', $sId)->first();
        if (!$screening) return response()->json(['success' => false, 'message' => 'Screening not found'], 404);

        $screening->delete();

        return response()->json([
            'success' => true,
            'message' => 'Screening deleted successfully'
        ]);
    }
}
