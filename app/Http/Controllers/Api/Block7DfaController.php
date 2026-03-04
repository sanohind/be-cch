<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\CchDfa;
use App\Models\Cch;
use App\Services\WorkflowService;
use App\Services\AuditLogService;

class Block7DfaController extends Controller
{
    public function show($id): JsonResponse
    {
        $cch = \App\Models\Cch::find($id);
        if ($cch) {
            $sphereUser = request()->attributes->get('sphere_user');
            if ($cch->b7_status === 'draft' && !\App\Services\WorkflowService::checkCanViewDraft($cch, $sphereUser, 7)) {
                return response()->json(['success' => false, 'message' => 'Status tiket masih draft, hanya dapat dilihat oleh user yang mengisi.'], 403);
            }
        }

        $dfa = CchDfa::where('cch_id', $id)->first();
        if (!$dfa) {
            return response()->json(['success' => false, 'message' => 'Block 7 not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $dfa]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $cch = Cch::find($id);
        if (!$cch) return response()->json(['success' => false, 'message' => 'CCH not found'], 404);

        $isDraft = $request->boolean('is_draft', false);
        $sphereUser = $request->attributes->get('sphere_user');

        if ($error = WorkflowService::checkBlockAccess($cch, $sphereUser, 7)) {
            return response()->json($error, 403);
        }

        $rules = [
            'occurrence_mechanism' => 'nullable|string',
            'outflow_mechanism' => 'nullable|string',
            'author_comment' => 'nullable|string'
        ];

        $rules = WorkflowService::applyDraftRules($rules, $isDraft);
        $validated = $request->validate($rules);

        $dfa = CchDfa::updateOrCreate(['cch_id' => $id], $validated);

        WorkflowService::updateBlockStatus($cch, 7, $isDraft);

        if ($isDraft) {
            AuditLogService::logDraft($id, 'Block 7', $sphereUser['id']);
        } else {
            AuditLogService::logSubmit($id, 'Block 7', $sphereUser['id']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Block 7 updated successfully',
            'data'    => $dfa
        ]);
    }
}
