<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\CchDfa;
use App\Models\Cch;
use App\Services\WorkflowService;
use App\Services\AuditLogService;
use App\Models\CchDfaAttachment;

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
            'author_comment' => 'nullable|string',
            'dfa_files.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png,xlsx,docx|max:10240',
            'action_files.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png,xlsx,docx|max:10240',
        ];

        $rules = WorkflowService::applyDraftRules($rules, $isDraft);
        $validated = $request->validate($rules);

        $dfaData = collect($validated)->except(['dfa_files', 'action_files'])->toArray();
        $dfa = CchDfa::updateOrCreate(['cch_id' => $id], $dfaData);

        // Upload files
        foreach (['dfa_files', 'action_files'] as $field) {
            if ($request->hasFile($field)) {
                foreach ($request->file($field) as $file) {
                    $originalName = $file->getClientOriginalName();
                    $fileName = date('Ymd_His') . '_' . preg_replace('/[^A-Za-z0-9\-\_\.]/', '_', $originalName);
                    $storedPath = $file->storeAs("cch/{$cch->cch_id}/dfa", $fileName, 'public');

                    CchDfaAttachment::create([
                        'cch_id' => $cch->cch_id,
                        'file_name' => $originalName,
                        'file_path' => $storedPath,
                        'file_size_kb' => round($file->getSize() / 1024, 2),
                        'uploaded_by' => $sphereUser['id'],
                        // must match DB ENUM values (see create_t_cch_dfa_table migration)
                        'attachment_type' => $field === 'dfa_files'
                            ? 'analysis_sheet'
                            : 'corrective_action_sheet'
                    ]);
                }
            }
        }

        WorkflowService::updateBlockStatus($cch, 7, $isDraft);

        if ($isDraft) {
            AuditLogService::logDraft($id, 'Block 7', $sphereUser['id']);
        } else {
            AuditLogService::logSubmit($id, 'Block 7', $sphereUser['id']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Block 7 updated successfully',
            'data'    => $dfa->load('attachments')
        ]);
    }
}
