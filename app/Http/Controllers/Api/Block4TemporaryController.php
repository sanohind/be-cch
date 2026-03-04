<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\CchTemporary;
use App\Models\Cch;
use App\Services\WorkflowService;

class Block4TemporaryController extends Controller
{
    public function show($id): JsonResponse
    {
        $cch = \App\Models\Cch::find($id);
        if ($cch) {
            $sphereUser = request()->attributes->get('sphere_user');
            if ($cch->b4_status === 'draft' && !\App\Services\WorkflowService::checkCanViewDraft($cch, $sphereUser, 4)) {
                return response()->json(['success' => false, 'message' => 'Status tiket masih draft, hanya dapat dilihat oleh user yang mengisi.'], 403);
            }
        }

        $temporary = CchTemporary::where('cch_id', $id)->first();
        if (!$temporary) {
            return response()->json(['success' => false, 'message' => 'Block 4 not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $temporary]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $cch = Cch::find($id);
        if (!$cch) return response()->json(['success' => false, 'message' => 'CCH not found'], 404);

        $isDraft = $request->boolean('is_draft', false);
        $sphereUser = $request->attributes->get('sphere_user');

        if ($error = WorkflowService::checkBlockAccess($cch, $sphereUser, 4)) {
            return response()->json($error, 403);
        }

        $rules = [
            'author_comment' => 'required|string'
        ];

        $rules = WorkflowService::applyDraftRules($rules, $isDraft);
        $validated = $request->validate($rules);

        $temporary = CchTemporary::updateOrCreate(['cch_id' => $id], $validated);

        WorkflowService::updateBlockStatus($cch, 4, $isDraft);

        return response()->json([
            'success' => true,
            'message' => 'Block 4 updated successfully',
            'data'    => $temporary
        ]);
    }
}
