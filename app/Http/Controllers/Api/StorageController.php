<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Serve storage files through API with auth.
 * Required because <img src> cannot send Authorization header for cross-origin requests.
 */
class StorageController extends Controller
{
    /**
     * GET /api/v1/storage/{path}
     * Stream file from storage/app/public. Path must be relative (e.g. cch/123/basic/xxx.png).
     */
    public function show(Request $request, string $path): mixed
    {
        // Prevent path traversal
        if (str_contains($path, '..') || str_starts_with($path, '/')) {
            abort(404);
        }

        $fullPath = $path;
        if (!Storage::disk('public')->exists($fullPath)) {
            abort(404);
        }

        $mimeType = Storage::disk('public')->mimeType($fullPath);
        $stream = Storage::disk('public')->readStream($fullPath);

        return response()->stream(function () use ($stream) {
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
