<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\CchQuestion;
use App\Models\CchQuestionResponse;
use App\Models\Cch;

class QnaController extends Controller
{
    public function index($id): JsonResponse
    {
        $questions = CchQuestion::with(['askedBy', 'responses.respondedBy'])
            ->where('cch_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json(['success' => true, 'data' => $questions]);
    }

    public function storeQuestion(Request $request, $id): JsonResponse
    {
        $cch = Cch::find($id);
        if (!$cch) return response()->json(['success' => false, 'message' => 'CCH not found'], 404);

        $validated = $request->validate([
            'message' => 'required|string',
            'target_department' => 'nullable|string|max:100'
        ]);

        $sphereUser = $request->attributes->get('sphere_user');

        $question = CchQuestion::create([
            'cch_id' => $id,
            'message' => $validated['message'],
            'target_department' => $validated['target_department'],
            'asked_by' => $sphereUser['id'],
            'status' => 'open'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Question added successfully',
            'data'    => $question->load('askedBy')
        ], 201);
    }

    public function storeResponse(Request $request, $id, $qId): JsonResponse
    {
        $question = CchQuestion::where('cch_id', $id)->where('question_id', $qId)->first();
        if (!$question) return response()->json(['success' => false, 'message' => 'Question not found'], 404);

        $validated = $request->validate([
            'message' => 'required|string',
        ]);

        $sphereUser = $request->attributes->get('sphere_user');

        $response = CchQuestionResponse::create([
            'question_id' => $qId,
            'message' => $validated['message'],
            'responded_by' => $sphereUser['id']
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Response added successfully',
            'data'    => $response->load('respondedBy')
        ], 201);
    }

    public function resolveQuestion(Request $request, $id, $qId): JsonResponse
    {
        $question = CchQuestion::where('cch_id', $id)->where('question_id', $qId)->first();
        if (!$question) return response()->json(['success' => false, 'message' => 'Question not found'], 404);

        $question->update(['status' => 'resolved']);

        return response()->json([
            'success' => true,
            'message' => 'Question resolved successfully',
            'data'    => $question
        ]);
    }
}
