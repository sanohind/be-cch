<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\CchRequest;
use App\Models\Cch;
use App\Services\WorkflowService;
use App\Services\AuditLogService;

class Block5RequestController extends Controller
{
    public function index($id): JsonResponse
    {
        $requests = CchRequest::where('cch_id', $id)->get();
        return response()->json(['success' => true, 'data' => $requests]);
    }

    public function store(Request $request, $id): JsonResponse
    {
        $cch = Cch::find($id);
        if (!$cch) return response()->json(['success' => false, 'message' => 'CCH not found'], 404);

        $sphereUser = $request->attributes->get('sphere_user');
        if ($error = WorkflowService::checkBlockAccess($cch, $sphereUser, 5)) {
            return response()->json($error, 403);
        }

        $validated = $request->validate([
            'department' => 'required|string|max:200',
            'due_date' => 'required|date',
            'description' => 'required|string',
            'status' => 'nullable|in:open,in_progress,completed'
        ]);

        $validated['cch_id'] = $id;
        $validated['status'] = $validated['status'] ?? 'open';
        
        $cchRequest = CchRequest::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Request added successfully',
            'data'    => $cchRequest
        ], 201);
    }

    public function update(Request $request, $id, $reqId): JsonResponse
    {
        $cchRequest = CchRequest::where('cch_id', $id)->where('request_id', $reqId)->first();
        if (!$cchRequest) return response()->json(['success' => false, 'message' => 'Request not found'], 404);

        $cch = Cch::find($id);
        if (!$cch) return response()->json(['success' => false, 'message' => 'CCH not found'], 404);

        $sphereUser = $request->attributes->get('sphere_user');
        if ($error = WorkflowService::checkBlockAccess($cch, $sphereUser, 5)) {
            return response()->json($error, 403);
        }

        $validated = $request->validate([
            'department' => 'sometimes|required|string|max:200',
            'due_date' => 'sometimes|required|date',
            'description' => 'sometimes|required|string',
            'status' => 'sometimes|required|in:open,in_progress,completed'
        ]);

        $cchRequest->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Request updated successfully',
            'data'    => $cchRequest
        ]);
    }

    public function destroy($id, $reqId): JsonResponse
    {
        $cchRequest = CchRequest::where('cch_id', $id)->where('request_id', $reqId)->first();
        if (!$cchRequest) return response()->json(['success' => false, 'message' => 'Request not found'], 404);

        $cch = Cch::find($id);
        if (!$cch) return response()->json(['success' => false, 'message' => 'CCH not found'], 404);

        $sphereUser = $request->attributes->get('sphere_user');
        if ($error = WorkflowService::checkBlockAccess($cch, $sphereUser, 5)) {
            return response()->json($error, 403);
        }

        $cchRequest->delete();

        return response()->json([
            'success' => true,
            'message' => 'Request deleted successfully'
        ]);
    }

    public function updateStatus(Request $request, $id): JsonResponse
    {
        $cch = Cch::find($id);
        if (!$cch) return response()->json(['success' => false, 'message' => 'CCH not found'], 404);

        $isDraft = $request->boolean('is_draft', false);
        $sphereUser = $request->attributes->get('sphere_user');

        if ($error = WorkflowService::checkBlockAccess($cch, $sphereUser, 5)) {
            return response()->json($error, 403);
        }

        WorkflowService::updateBlockStatus($cch, 5, $isDraft);

        if ($isDraft) {
            AuditLogService::logDraft($id, 'Block 5', $sphereUser['id']);
        } else {
            AuditLogService::logSubmit($id, 'Block 5', $sphereUser['id']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Block 5 status updated successfully',
            'data'    => $cch
        ]);
    }
}
