<?php

use App\Http\Controllers\Api\SSOAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| CCH API Routes
|--------------------------------------------------------------------------
|
| Auth via Sphere SSO: semua route yang butuh autentikasi harus
| menggunakan middleware 'sphere.auth'.
|
| Base URL: /api
|
*/

// ─── Auth (SSO) ──────────────────────────────────────────────────────────────
Route::prefix('auth')->middleware('sphere.auth')->group(function () {
    // GET /api/auth/user — sync & return cch_users record dari token
    Route::get('/user', [SSOAuthController::class, 'user']);

    // GET /api/auth/verify — verifikasi token valid (lightweight)
    Route::get('/verify', [SSOAuthController::class, 'verify']);
});

// ─── Protected API v1 ────────────────────────────────────────────────────────
// Semua route CCH akan ditambahkan di sini dengan prefix v1 dan sphere.auth
Route::prefix('v1')->middleware('sphere.auth')->group(function () {
    // Health check
    Route::get('/ping', function () {
        return response()->json([
            'success' => true,
            'message' => 'CCH API v1 — authenticated',
            'user'    => request()->attributes->get('sphere_user'),
        ]);
    });

    // ─── Master Data ─────────────────────────────────────────────────────────
    Route::prefix('masters')->group(function () {
        Route::get('/divisions', [\App\Http\Controllers\Api\MasterController::class, 'getDivisions']);
        Route::get('/plants', [\App\Http\Controllers\Api\MasterController::class, 'getPlants']);

        // Business Partners dari ERP (menggantikan m_customers & m_suppliers)
        Route::get('/business-partners', [\App\Http\Controllers\Api\MasterController::class, 'getBusinessPartners']);
        Route::get('/business-partners/{code}', [\App\Http\Controllers\Api\MasterController::class, 'getBusinessPartnerDetail']);

        // Alias untuk backward-compatibility (filter by role)
        Route::get('/customers', [\App\Http\Controllers\Api\MasterController::class, 'getCustomers']);
        Route::get('/suppliers', [\App\Http\Controllers\Api\MasterController::class, 'getSuppliers']);
        Route::get('/failure-modes', [\App\Http\Controllers\Api\MasterController::class, 'getFailureModes']);
        Route::get('/product-categories', [\App\Http\Controllers\Api\MasterController::class, 'getProductCategories']);
        Route::get('/product-categories/{id}/families', [\App\Http\Controllers\Api\MasterController::class, 'getProductFamilies']);
        Route::get('/processes', [\App\Http\Controllers\Api\MasterController::class, 'getProcesses']);
        Route::get('/causes', [\App\Http\Controllers\Api\MasterController::class, 'getCauses']);
        Route::get('/currencies', [\App\Http\Controllers\Api\MasterController::class, 'getCurrencies']);
        Route::get('/cch-filter-options', [\App\Http\Controllers\Api\MasterController::class, 'getCchFilterOptions']);
    });

    // ─── CCH Core ────────────────────────────────────────────────────────────
    Route::get('/cch', [\App\Http\Controllers\Api\CchController::class, 'index']);
    Route::post('/cch', [\App\Http\Controllers\Api\CchController::class, 'store']);
    Route::get('/cch/{id}', [\App\Http\Controllers\Api\CchController::class, 'show']);

    // ─── Block 1: Basic ──────────────────────────────────────────────────────
    Route::get('/cch/{id}/basic', [\App\Http\Controllers\Api\Block1BasicController::class, 'show']);
    Route::put('/cch/{id}/basic', [\App\Http\Controllers\Api\Block1BasicController::class, 'update']);

    // ─── Block 2: Primary Info ───────────────────────────────────────────────
    Route::get('/cch/{id}/primary', [\App\Http\Controllers\Api\Block2PrimaryController::class, 'show']);
    Route::put('/cch/{id}/primary', [\App\Http\Controllers\Api\Block2PrimaryController::class, 'update']);

    // ─── Block 3: SRTA ───────────────────────────────────────────────────────
    Route::get('/cch/{id}/srta', [\App\Http\Controllers\Api\Block3SrtaController::class, 'show']);
    Route::put('/cch/{id}/srta', [\App\Http\Controllers\Api\Block3SrtaController::class, 'update']);
    Route::post('/cch/{id}/srta/screening', [\App\Http\Controllers\Api\Block3SrtaController::class, 'addScreening']);
    Route::put('/cch/{id}/srta/screening/{sId}', [\App\Http\Controllers\Api\Block3SrtaController::class, 'updateScreening']);
    Route::delete('/cch/{id}/srta/screening/{sId}', [\App\Http\Controllers\Api\Block3SrtaController::class, 'deleteScreening']);

    // ─── Block 4: Temporary Countermeasures ──────────────────────────────────
    Route::get('/cch/{id}/temporary', [\App\Http\Controllers\Api\Block4TemporaryController::class, 'show']);
    Route::put('/cch/{id}/temporary', [\App\Http\Controllers\Api\Block4TemporaryController::class, 'update']);

    // ─── Block 5: Request ────────────────────────────────────────────────────
    Route::get('/cch/{id}/requests', [\App\Http\Controllers\Api\Block5RequestController::class, 'index']);
    Route::post('/cch/{id}/requests', [\App\Http\Controllers\Api\Block5RequestController::class, 'store']);
    Route::put('/cch/{id}/requests/{reqId}', [\App\Http\Controllers\Api\Block5RequestController::class, 'update']);
    Route::delete('/cch/{id}/requests/{reqId}', [\App\Http\Controllers\Api\Block5RequestController::class, 'destroy']);
    Route::put('/cch/{id}/requests-status', [\App\Http\Controllers\Api\Block5RequestController::class, 'updateStatus']);

    // ─── Block 6: Rejection Analysis ─────────────────────────────────────────
    Route::get('/cch/{id}/ra', [\App\Http\Controllers\Api\Block6RaController::class, 'show']);
    Route::put('/cch/{id}/ra', [\App\Http\Controllers\Api\Block6RaController::class, 'update']);

    // ─── Block 7: Defective Factor Analysis ──────────────────────────────────
    Route::get('/cch/{id}/dfa', [\App\Http\Controllers\Api\Block7DfaController::class, 'show']);
    Route::put('/cch/{id}/dfa', [\App\Http\Controllers\Api\Block7DfaController::class, 'update']);

    // ─── Block 8: Occurrence ─────────────────────────────────────────────────
    Route::get('/cch/{id}/occurrence', [\App\Http\Controllers\Api\Block8OccurrenceController::class, 'show']);
    Route::put('/cch/{id}/occurrence', [\App\Http\Controllers\Api\Block8OccurrenceController::class, 'update']);
    Route::post('/cch/{id}/occurrence/causes', [\App\Http\Controllers\Api\Block8OccurrenceController::class, 'addCause']);
    Route::put('/cch/{id}/occurrence/causes/{cId}', [\App\Http\Controllers\Api\Block8OccurrenceController::class, 'updateCause']);
    Route::delete('/cch/{id}/occurrence/causes/{cId}', [\App\Http\Controllers\Api\Block8OccurrenceController::class, 'deleteCause']);

    // ─── Block 9: Outflow ────────────────────────────────────────────────────
    Route::get('/cch/{id}/outflow', [\App\Http\Controllers\Api\Block9OutflowController::class, 'show']);
    Route::put('/cch/{id}/outflow', [\App\Http\Controllers\Api\Block9OutflowController::class, 'update']);
    Route::post('/cch/{id}/outflow/causes', [\App\Http\Controllers\Api\Block9OutflowController::class, 'addCause']);
    Route::put('/cch/{id}/outflow/causes/{cId}', [\App\Http\Controllers\Api\Block9OutflowController::class, 'updateCause']);
    Route::delete('/cch/{id}/outflow/causes/{cId}', [\App\Http\Controllers\Api\Block9OutflowController::class, 'deleteCause']);

    // ─── Block 10: Closing ───────────────────────────────────────────────────
    Route::get('/cch/{id}/closing', [\App\Http\Controllers\Api\Block10ClosingController::class, 'show']);
    Route::put('/cch/{id}/closing', [\App\Http\Controllers\Api\Block10ClosingController::class, 'update']);
    Route::post('/cch/{id}/closing/attachments', [\App\Http\Controllers\Api\Block10ClosingController::class, 'uploadAttachment']);
    Route::delete('/cch/{id}/closing/attachments/{attachId}', [\App\Http\Controllers\Api\Block10ClosingController::class, 'deleteAttachment']);
    Route::post('/cch/{id}/closing/submit', [\App\Http\Controllers\Api\Block10ClosingController::class, 'submitClose']);
    Route::post('/cch/{id}/closing/approve', [\App\Http\Controllers\Api\Block10ClosingController::class, 'approveClose']);

    // ─── Q&A ─────────────────────────────────────────────────────────────────
    Route::get('/cch/{id}/questions', [\App\Http\Controllers\Api\QnaController::class, 'index']);
    Route::post('/cch/{id}/questions', [\App\Http\Controllers\Api\QnaController::class, 'storeQuestion']);
    Route::post('/cch/{id}/questions/{qId}/responses', [\App\Http\Controllers\Api\QnaController::class, 'storeResponse']);
    Route::patch('/cch/{id}/questions/{qId}/resolve', [\App\Http\Controllers\Api\QnaController::class, 'resolveQuestion']);

    // ─── Attachments ─────────────────────────────────────────────────────────
    Route::post('/cch/{id}/{block}/attachments', [\App\Http\Controllers\Api\AttachmentController::class, 'upload']);
    Route::delete('/cch/{id}/{block}/attachments/{attachmentId}', [\App\Http\Controllers\Api\AttachmentController::class, 'destroy']);

    // ─── Support (Audit Logs & Notifications) ────────────────────────────────
    Route::get('/cch/{id}/audit-log', [\App\Http\Controllers\Api\AuditLogController::class, 'index']);
});
