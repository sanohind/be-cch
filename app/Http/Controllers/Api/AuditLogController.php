<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\CchAuditLog;

class AuditLogController extends Controller
{
    public function index($id): JsonResponse
    {
        $logs = CchAuditLog::with(['changedBy'])
            ->where('cch_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json(['success' => true, 'data' => $logs]);
    }
}
