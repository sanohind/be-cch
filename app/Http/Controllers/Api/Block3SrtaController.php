<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\CchSrta;
use App\Models\CchSrtaScreening;
use App\Models\Cch;
use App\Services\AuditLogService;
use App\Models\CchSrtaAttachment;
use Illuminate\Support\Facades\Storage;
use App\Services\WorkflowService;

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
            'author_comment' => 'nullable|string',
            'treatment' => 'nullable|string',
            'screenings_json' => 'nullable|string',
            'attachment_files.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png,xlsx,docx|max:10240'
        ];

        $rules = \App\Services\WorkflowService::applyDraftRules($rules, $isDraft);
        $validated = $request->validate($rules);

        $srtaData = collect($validated)->except(['screenings_json', 'attachment_files'])->toArray();
        $srta = CchSrta::updateOrCreate(['cch_id' => $id], $srtaData);

        // Process Screenings Sync
        if ($request->has('screenings_json')) {
            $screenings = json_decode($request->input('screenings_json'), true) ?? [];
            
            // Delete old screenings that are not in the new active list (where ID is present but not passed, etc)
            // Simpler: Just delete ALL current screenings for this CCH, then recreate, OR sync manually.
            $activeIds = collect($screenings)->pluck('id')->filter()->toArray();
            CchSrtaScreening::where('cch_id', $id)->whereNotIn('screening_id', $activeIds)->delete();

            foreach ($screenings as $row) {
                // validate basic
                if (!isset($row['location']) || !isset($row['action_taken'])) continue;
                
                $sd = [
                    'cch_id' => $id,
                    'location' => $row['location'],
                    'ng_qty' => $row['ng_qty'] ?? 0,
                    'ok_qty' => $row['ok_qty'] ?? 0,
                    'action_taken' => $row['action_taken'],
                    'action_result' => $row['action_result'] ?? null
                ];

                if (!empty($row['id'])) {
                    CchSrtaScreening::where('cch_id', $id)->where('screening_id', $row['id'])->update($sd);
                } else {
                    CchSrtaScreening::create($sd);
                }
            }
        }

        // Processing Attachments
        if ($request->hasFile('attachment_files')) {
            foreach ($request->file('attachment_files') as $file) {
                $originalName = $file->getClientOriginalName();
                $fileName = date('Ymd_His') . '_' . preg_replace('/[^A-Za-z0-9\-\_\.]/', '_', $originalName);
                $path = "cch/{$cch->cch_id}/srta";
                $storedPath = $file->storeAs($path, $fileName, 'public');

                CchSrtaAttachment::create([
                    'cch_id' => $cch->cch_id,
                    'file_name' => $originalName,
                    'file_path' => $storedPath,
                    'file_size_kb' => round($file->getSize() / 1024, 2),
                    'uploaded_by' => $sphereUser['id']
                ]);
            }
        }

        \App\Services\WorkflowService::updateBlockStatus($cch, 3, $isDraft);

        if ($isDraft) {
            AuditLogService::logDraft($id, 'Block 3', $sphereUser['id']);
        } else {
            AuditLogService::logSubmit($id, 'Block 3', $sphereUser['id']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Block 3 updated successfully',
            'data'    => $srta->load('screening', 'attachments')
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
