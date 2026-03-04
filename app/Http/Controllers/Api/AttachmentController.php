<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use App\Models\Cch;

class AttachmentController extends Controller
{
    // Mapping of block prefixes to model classes
    private $blockModels = [
        'basic' => \App\Models\CchBasicAttachment::class,
        'primary' => \App\Models\CchPrimaryPhoto::class,
        'srta' => \App\Models\CchSrtaAttachment::class,
        'temporary' => \App\Models\CchTemporaryAttachment::class,
        'ra' => \App\Models\CchRaAttachment::class,
        'dfa' => \App\Models\CchDfaAttachment::class,
        'closing' => \App\Models\CchClosingAttachment::class,
    ];

    public function upload(Request $request, $id, $block): JsonResponse
    {
        if (!array_key_exists($block, $this->blockModels)) {
            return response()->json(['success' => false, 'message' => 'Invalid block type for attachment'], 400);
        }

        $cch = Cch::find($id);
        if (!$cch) return response()->json(['success' => false, 'message' => 'CCH not found'], 404);

        $request->validate([
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png,xlsx,docx|max:10240' // 10MB max
        ]);

        $file = $request->file('file');
        
        $originalName = $file->getClientOriginalName();
        $fileName = date('Ymd_His') . '_' . preg_replace('/[^A-Za-z0-9\-\_\.]/', '_', $originalName);
        $path = "cch/{$id}/{$block}"; // path structure
        
        // Simpan ke storage (Local / MinIO / S3 berdasarkan konfigurasi config/filesystems.php)
        $storedPath = $file->storeAs($path, $fileName, 'public'); // menggunakan public disk sementara

        $sphereUser = $request->attributes->get('sphere_user');
        $modelClass = $this->blockModels[$block];

        $attachmentParams = [
            'cch_id' => $id,
            'file_name' => $originalName,
            'file_path' => $storedPath,
            'file_size_kb' => round($file->getSize() / 1024, 2),
            'uploaded_by' => $sphereUser['id']
        ];
        
        // CchPrimaryPhoto khusus punya description field minimal
        if ($block === 'primary') {
            $attachmentParams['description'] = $request->input('description', null);
        }

        $attachment = $modelClass::create($attachmentParams);

        return response()->json([
            'success' => true,
            'message' => 'File uploaded successfully',
            'data'    => $attachment
        ], 201);
    }

    public function destroy($id, $block, $attachmentId): JsonResponse
    {
        if (!array_key_exists($block, $this->blockModels)) {
            return response()->json(['success' => false, 'message' => 'Invalid block type'], 400);
        }

        $modelClass = $this->blockModels[$block];
        
        // ID primary key column depends on block, standardizing around the assumption model defines it
        // We will try finding the model, if multiple primary keys exist, we fetch where cch_id is current id
        $modelInstance = app($modelClass);
        $primaryKey = $modelInstance->getKeyName();

        $attachment = $modelClass::where('cch_id', $id)->where($primaryKey, $attachmentId)->first();
        if (!$attachment) return response()->json(['success' => false, 'message' => 'Attachment not found'], 404);

        // Hapus fisik file
        if (Storage::disk('public')->exists($attachment->file_path)) {
            Storage::disk('public')->delete($attachment->file_path);
        }

        $attachment->delete();

        return response()->json([
            'success' => true,
            'message' => 'File deleted successfully'
        ]);
    }
}
