<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\CchTemporary;
use App\Models\Cch;
use App\Services\WorkflowService;
use App\Services\AuditLogService;
use App\Models\CchTemporaryAttachment;
use Illuminate\Support\Facades\Storage;

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
            // Author comment sekarang dihandle oleh modul komentar terpisah.
            // Di sini hanya validasi lampiran tambahan (jika ada).
            'attachment_files.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png,xlsx,docx|max:10240'
        ];

        $rules = WorkflowService::applyDraftRules($rules, $isDraft);
        $validated = $request->validate($rules);

        $tempData = collect($validated)->except(['attachment_files'])->toArray();
        if ($isDraft) {
            $tempData = WorkflowService::sanitizeDraftData($tempData, 4);
        }
        $temporary = CchTemporary::updateOrCreate(['cch_id' => $id], $tempData);

        if ($request->hasFile('attachment_files')) {
            foreach ($request->file('attachment_files') as $file) {
                $originalName = $file->getClientOriginalName();
                $fileName = date('Ymd_His') . '_' . preg_replace('/[^A-Za-z0-9\-\_\.]/', '_', $originalName);
                $path = "cch/{$cch->cch_id}/temporary";
                $storedPath = $file->storeAs($path, $fileName, 'public');

                CchTemporaryAttachment::create([
                    'cch_id' => $cch->cch_id,
                    'file_name' => $originalName,
                    'file_path' => $storedPath,
                    'file_size_kb' => round($file->getSize() / 1024, 2),
                    'uploaded_by' => $sphereUser['id']
                ]);
            }
        }

        WorkflowService::updateBlockStatus($cch, 4, $isDraft);

        if ($isDraft) {
            AuditLogService::logDraft($id, 'Block 4', $sphereUser['id']);
        } else {
            AuditLogService::logSubmit($id, 'Block 4', $sphereUser['id']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Block 4 updated successfully',
            'data'    => $temporary
        ]);
    }
}
