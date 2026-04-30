<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cch;
use App\Models\CchUser;
use App\Models\CchRequest;
use App\Models\CchComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Admin-only CRUD: list & delete data. Hanya role_level 1 (Superadmin) atau 6 (Supervisor).
 */
class AdminController extends Controller
{
    private function ensureAdmin(Request $request): ?JsonResponse
    {
        $sphereUser = $request->attributes->get('sphere_user') ?? [];
        $roleLevel = (int)($sphereUser['role_level'] ?? 99);
        if (!in_array($roleLevel, [1, 6], true)) {
            return response()->json(['success' => false, 'message' => 'Forbidden. Admin only.'], 403);
        }
        return null;
    }

    /** GET /api/v1/admin/cch — list all CCH (paginated) */
    public function indexCch(Request $request): JsonResponse
    {
        if ($err = $this->ensureAdmin($request)) return $err;

        $perPage = min((int)$request->query('per_page', 20), 100);
        $query = Cch::query()
            ->select(['t_cch.cch_id', 't_cch.cch_number', 't_cch.status', 't_cch.division_id', 't_cch.input_by', 't_cch.created_at'])
            ->with(['inputBy:id,username,name', 'division:id,name,code', 'basic:basic_id,cch_id,subject'])
            ->orderByDesc('t_cch.cch_id');

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('t_cch.cch_number', 'like', "%{$search}%")
                  ->orWhereHas('basic', fn($b) => $b->where('subject', 'like', "%{$search}%"));
            });
        }

        $paginator = $query->paginate($perPage);
        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /** GET /api/v1/admin/users — list all cch_users */
    public function indexUsers(Request $request): JsonResponse
    {
        if ($err = $this->ensureAdmin($request)) return $err;

        $perPage = min((int)$request->query('per_page', 20), 100);
        $query = CchUser::query()
            ->select(['id', 'username', 'name', 'email', 'department_id', 'role_id', 'last_login_at', 'is_active'])
            ->with('division:id,name,code')
            ->orderByDesc('id');

        if ($request->filled('search')) {
            $s = $request->query('search');
            $query->where(function ($q) use ($s) {
                $q->where('username', 'like', "%{$s}%")
                  ->orWhere('name', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%");
            });
        }

        $paginator = $query->paginate($perPage);
        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /** GET /api/v1/admin/requests — list all t_cch_request */
    public function indexRequests(Request $request): JsonResponse
    {
        if ($err = $this->ensureAdmin($request)) return $err;

        $perPage = min((int)$request->query('per_page', 20), 100);
        $query = CchRequest::query()
            ->with(['cch:cch_id,cch_number,division_id', 'cch.division:id,name'])
            ->orderByDesc('request_id');

        if ($request->filled('cch_id')) {
            $query->where('cch_id', $request->query('cch_id'));
        }

        $paginator = $query->paginate($perPage);
        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /** GET /api/v1/admin/comments — list all t_cch_comments */
    public function indexComments(Request $request): JsonResponse
    {
        if ($err = $this->ensureAdmin($request)) return $err;

        $perPage = min((int)$request->query('per_page', 20), 100);
        $query = CchComment::query()
            ->with(['createdBy:id,username,name'])
            ->orderByDesc('comment_id');

        if ($request->filled('cch_id')) {
            $query->where('cch_id', $request->query('cch_id'));
        }
        if ($request->filled('block_number')) {
            $query->where('block_number', $request->query('block_number'));
        }

        $paginator = $query->paginate($perPage);
        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /** DELETE /api/v1/admin/cch/{id} */
    public function destroyCch(Request $request, $id): JsonResponse
    {
        if ($err = $this->ensureAdmin($request)) return $err;

        $cch = Cch::find($id);
        if (!$cch) return response()->json(['success' => false, 'message' => 'CCH not found'], 404);

        $cch->delete();
        return response()->json(['success' => true, 'message' => 'CCH deleted']);
    }

    /** DELETE /api/v1/admin/comments/{id} */
    public function destroyComment(Request $request, $id): JsonResponse
    {
        if ($err = $this->ensureAdmin($request)) return $err;

        $comment = CchComment::find($id);
        if (!$comment) return response()->json(['success' => false, 'message' => 'Comment not found'], 404);

        $comment->delete();
        return response()->json(['success' => true, 'message' => 'Comment deleted']);
    }

    /** DELETE /api/v1/admin/requests/{id} */
    public function destroyRequest(Request $request, $id): JsonResponse
    {
        if ($err = $this->ensureAdmin($request)) return $err;

        $req = CchRequest::find($id);
        if (!$req) return response()->json(['success' => false, 'message' => 'Request not found'], 404);

        $req->delete();
        return response()->json(['success' => true, 'message' => 'Request deleted']);
    }
}
