<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cch;
use App\Models\CchComment;
use App\Models\CchUser;
use App\Services\CchNotificationService;
use App\Services\WorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CchCommentController extends Controller
{
    private function canAccessCch(Cch $cch, array $sphereUser): bool
    {
        $roleLevel = (int)($sphereUser['role_level'] ?? 99);
        $userId = $sphereUser['id'] ?? null;

        // Superadmin: lihat semua
        if ($roleLevel === 1) return true;

        // Presdir, GM, Manager: akses penuh untuk baca
        if (in_array($roleLevel, [2, 4, 5], true)) return true;

        // Pemilik CCH
        $ownerId = $cch->admin_in_charge ?: $cch->input_by;
        if ($ownerId == $userId) return true;

        // Supervisor/Staff: cek departemen dari sphereUser (tanpa query DB tambahan)
        $userDivisionId = (int)($sphereUser['department_id'] ?? 0);
        if ($userDivisionId) {
            if ($cch->requests()->where('division_id', $userDivisionId)->exists()) return true;
        }
        return false;
    }

    /**
     * Bisa mengisi author comment: (1) Admin penerbit CCH, (2) PIC department yang di-request, (3) Manager department terkait (divisi CCH dari basic).
     */
    private function canComment(Cch $cch, array $sphereUser): bool
    {
        if (($cch->status ?? '') === 'closed') {
            return false;
        }
        $roleLevel = (int)($sphereUser['role_level'] ?? 99);
        $userId = $sphereUser['id'] ?? null;

        // Superadmin
        if ($roleLevel === 1) return true;

        // Presdir, GM, Manager: boleh komentar
        if (in_array($roleLevel, [2, 4, 5], true)) return true;

        // Admin penerbit (yang menerbitkan CCH)
        if ($cch->input_by == $userId) return true;

        // Supervisor / Staff: gunakan department_id dari sphereUser
        $userDivisionId = (int)($sphereUser['department_id'] ?? 0);
        if (!$userDivisionId) return false;

        // PIC department yang di-request di Block 5
        if ($cch->requests()->where('division_id', $userDivisionId)->exists()) return true;

        return false;
    }

    public function index(Request $request, $id): JsonResponse
    {
        $cch = Cch::find($id);
        if (!$cch) return response()->json(['success' => false, 'message' => 'CCH not found'], 404);

        $sphereUser = $request->attributes->get('sphere_user') ?? [];
        if (!$this->canAccessCch($cch, $sphereUser)) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $block = $request->query('block');
        $query = CchComment::with(['createdBy:id,name,username'])
            ->where('cch_id', $id);

        if ($block !== null) {
            $query->where('block_number', (int)$block);
        }

        $comments = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $comments,
        ]);
    }

    public function store(Request $request, $id): JsonResponse
    {
        $cch = Cch::find($id);
        if (!$cch) return response()->json(['success' => false, 'message' => 'CCH not found'], 404);

        $sphereUser = $request->attributes->get('sphere_user') ?? [];
        if (!$this->canComment($cch, $sphereUser)) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'block_number' => 'required|integer|in:1,2,3,4,5,8,9,10',
            'comment_type' => 'required|in:question,answer,response',
            'subject' => 'required|string|max:200',
            'description' => 'required|string',
            'reply_to' => 'nullable|integer|exists:t_cch_comments,comment_id',
            'attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png,xlsx,docx|max:10240', // 10 MB
        ]);

        $parentId = $validated['reply_to'] ?? null;
        if ($parentId) {
            // Ensure parent comment belongs to same CCH and (optionally) same block
            $parent = CchComment::where('cch_id', $id)->where('comment_id', $parentId)->first();
            if (!$parent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid parent comment.',
                ], 422);
            }
        }

        $attachmentPath = null;
        $attachmentName = null;
        $attachmentSizeKb = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $originalName = $file->getClientOriginalName();
            $safeName = date('Ymd_His') . '_' . preg_replace('/[^A-Za-z0-9\-\_\.]/', '_', $originalName);
            $path = "cch/{$cch->cch_id}/comments";
            $storedPath = $file->storeAs($path, $safeName, 'public');

            $attachmentPath = $storedPath;
            $attachmentName = $originalName;
            $attachmentSizeKb = round($file->getSize() / 1024, 2);
        }

        $comment = CchComment::create([
            'cch_id' => (int)$id,
            'block_number' => (int)$validated['block_number'],
            'comment_type' => $validated['comment_type'],
            'subject' => $validated['subject'],
            'description' => $validated['description'],
            'parent_comment_id' => $parentId,
            'attachment_path' => $attachmentPath,
            'attachment_name' => $attachmentName,
            'attachment_size_kb' => $attachmentSizeKb,
            'created_by' => (int)($sphereUser['id'] ?? 0),
        ]);

        // Jika comment untuk Block 4 (Temporary), anggap block sudah disubmit (auto trigger submit)
        if ((int)$validated['block_number'] === 4) {
            WorkflowService::updateBlockStatus($cch, 4, false, (int)($sphereUser['id'] ?? 0));
        }

        // Send email notification to all CCH participants
        $commenter = CchUser::select('name', 'username')->find($sphereUser['id']);
        $commenterName = $commenter?->name ?? $commenter?->username ?? 'Unknown';
        CchNotificationService::notifyCommentAdded(
            $cch,
            $validated['subject'],
            $validated['description'],
            $commenterName,
            (int)$validated['block_number']
        );

        return response()->json([
            'success' => true,
            'message' => 'Comment added successfully',
            'data' => $comment->load('createdBy:id,name,username'),
        ], 201);
    }

    public function destroy(Request $request, $id, $commentId): JsonResponse
    {
        $cch = Cch::find($id);
        if (!$cch) return response()->json(['success' => false, 'message' => 'CCH not found'], 404);

        $sphereUser = $request->attributes->get('sphere_user') ?? [];
        if (!$this->canComment($cch, $sphereUser)) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $comment = CchComment::where('cch_id', $id)->where('comment_id', $commentId)->first();
        if (!$comment) return response()->json(['success' => false, 'message' => 'Comment not found'], 404);

        // Only creator, admin owner, or manager/presdir can delete
        $roleLevel = (int)($sphereUser['role_level'] ?? 99);
        $userId = $sphereUser['id'] ?? null;
        $ownerId = $cch->admin_in_charge ?: $cch->input_by;
        $canDelete = $roleLevel === 1
            || in_array($roleLevel, [4, 5], true)
            || $comment->created_by == $userId
            || $ownerId == $userId;

        if (!$canDelete) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $comment->delete();

        return response()->json(['success' => true, 'message' => 'Comment deleted successfully']);
    }
}

