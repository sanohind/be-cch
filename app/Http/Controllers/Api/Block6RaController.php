<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\CchRa;
use App\Models\Cch;
use App\Services\WorkflowService;
use App\Services\AuditLogService;

class Block6RaController extends Controller
{
    public function show($id): JsonResponse
    {
        $cch = \App\Models\Cch::find($id);
        if ($cch) {
            $sphereUser = request()->attributes->get('sphere_user');
            if ($cch->b6_status === 'draft' && !\App\Services\WorkflowService::checkCanViewDraft($cch, $sphereUser, 6)) {
                return response()->json(['success' => false, 'message' => 'Status tiket masih draft, hanya dapat dilihat oleh user yang mengisi.'], 403);
            }
        }

        $ra = CchRa::where('cch_id', $id)->first();
        if (!$ra) {
            return response()->json(['success' => false, 'message' => 'Block 6 not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $ra]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $cch = Cch::find($id);
        if (!$cch) return response()->json(['success' => false, 'message' => 'CCH not found'], 404);

        $isDraft = $request->boolean('is_draft', false);
        $sphereUser = $request->attributes->get('sphere_user');

        if ($error = WorkflowService::checkBlockAccess($cch, $sphereUser, 6)) {
            return response()->json($error, 403);
        }

        $rules = [
            'author_comment' => 'required|string'
        ];

        $rules = WorkflowService::applyDraftRules($rules, $isDraft);
        $validated = $request->validate($rules);

        $ra = CchRa::updateOrCreate(['cch_id' => $id], $validated);

        WorkflowService::updateBlockStatus($cch, 6, $isDraft);

        if ($isDraft) {
            AuditLogService::logDraft($id, 'Block 6', $sphereUser['id']);
        } else {
            AuditLogService::logSubmit($id, 'Block 6', $sphereUser['id']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Block 6 updated successfully',
            'data'    => $ra
        ]);
    }
}
