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
        Route::get('/items', [\App\Http\Controllers\Api\Block2PrimaryController::class, 'getItems']);
    });

    // ─── CCH Core ────────────────────────────────────────────────────────────
    Route::get('/cch/analytics', [\App\Http\Controllers\Api\CchAnalyticsController::class, 'index']);
    Route::get('/cch', [\App\Http\Controllers\Api\CchController::class, 'index']);
    Route::post('/cch', [\App\Http\Controllers\Api\CchController::class, 'store']);
    Route::get('/cch/{id}', [\App\Http\Controllers\Api\CchController::class, 'show']);

    // ─── Block 1: Basic ──────────────────────────────────────────────────────
    Route::get('/cch/{id}/basic', [\App\Http\Controllers\Api\Block1BasicController::class, 'show']);
    Route::put('/cch/{id}/basic', [\App\Http\Controllers\Api\Block1BasicController::class, 'update']);
    // POST alias untuk update basic — mendukung file upload (PHP tidak bisa baca file dari PUT)
    Route::post('/cch/{id}/basic', [\App\Http\Controllers\Api\Block1BasicController::class, 'update']);

    // ─── Block 2: Primary Info ───────────────────────────────────────────────
    Route::get('/cch/{id}/primary', [\App\Http\Controllers\Api\Block2PrimaryController::class, 'show']);
    Route::put('/cch/{id}/primary', [\App\Http\Controllers\Api\Block2PrimaryController::class, 'update']);
    Route::post('/cch/{id}/primary', [\App\Http\Controllers\Api\Block2PrimaryController::class, 'update']);

    // ─── Block 3: SRTA ───────────────────────────────────────────────────────
    Route::get('/cch/{id}/srta', [\App\Http\Controllers\Api\Block3SrtaController::class, 'show']);
    Route::put('/cch/{id}/srta', [\App\Http\Controllers\Api\Block3SrtaController::class, 'update']);
    Route::post('/cch/{id}/srta', [\App\Http\Controllers\Api\Block3SrtaController::class, 'update']);
    Route::post('/cch/{id}/srta/screening', [\App\Http\Controllers\Api\Block3SrtaController::class, 'addScreening']);
    Route::put('/cch/{id}/srta/screening/{sId}', [\App\Http\Controllers\Api\Block3SrtaController::class, 'updateScreening']);
    Route::delete('/cch/{id}/srta/screening/{sId}', [\App\Http\Controllers\Api\Block3SrtaController::class, 'deleteScreening']);

    // ─── Block 4: Temporary Countermeasures ──────────────────────────────────
    Route::get('/cch/{id}/temporary', [\App\Http\Controllers\Api\Block4TemporaryController::class, 'show']);
    Route::put('/cch/{id}/temporary', [\App\Http\Controllers\Api\Block4TemporaryController::class, 'update']);
    Route::post('/cch/{id}/temporary', [\App\Http\Controllers\Api\Block4TemporaryController::class, 'update']);

    // ─── Block 5: Request ────────────────────────────────────────────────────
    Route::get('/cch/{id}/requests', [\App\Http\Controllers\Api\Block5RequestController::class, 'index']);
    Route::post('/cch/{id}/requests', [\App\Http\Controllers\Api\Block5RequestController::class, 'store']);
    Route::put('/cch/{id}/requests/{reqId}', [\App\Http\Controllers\Api\Block5RequestController::class, 'update']);
    Route::delete('/cch/{id}/requests/{reqId}', [\App\Http\Controllers\Api\Block5RequestController::class, 'destroy']);
    Route::put('/cch/{id}/requests-status', [\App\Http\Controllers\Api\Block5RequestController::class, 'updateStatus']);

    // ─── Block 8: Occurrence ─────────────────────────────────────────────────
    Route::get('/cch/{id}/occurrence', [\App\Http\Controllers\Api\Block8OccurrenceController::class, 'show']);
    Route::put('/cch/{id}/occurrence', [\App\Http\Controllers\Api\Block8OccurrenceController::class, 'update']);
    Route::post('/cch/{id}/occurrence', [\App\Http\Controllers\Api\Block8OccurrenceController::class, 'update']);
    Route::post('/cch/{id}/occurrence/causes', [\App\Http\Controllers\Api\Block8OccurrenceController::class, 'addCause']);
    Route::put('/cch/{id}/occurrence/causes/{cId}', [\App\Http\Controllers\Api\Block8OccurrenceController::class, 'updateCause']);
    Route::delete('/cch/{id}/occurrence/causes/{cId}', [\App\Http\Controllers\Api\Block8OccurrenceController::class, 'deleteCause']);
    Route::post('/cch/{id}/occurrence/submit-block', [\App\Http\Controllers\Api\Block8OccurrenceController::class, 'submitBlock']);

    // ─── Block 9: Outflow ────────────────────────────────────────────────────
    Route::get('/cch/{id}/outflow', [\App\Http\Controllers\Api\Block9OutflowController::class, 'show']);
    Route::put('/cch/{id}/outflow', [\App\Http\Controllers\Api\Block9OutflowController::class, 'update']);
    Route::post('/cch/{id}/outflow', [\App\Http\Controllers\Api\Block9OutflowController::class, 'update']);
    Route::post('/cch/{id}/outflow/causes', [\App\Http\Controllers\Api\Block9OutflowController::class, 'addCause']);
    Route::put('/cch/{id}/outflow/causes/{cId}', [\App\Http\Controllers\Api\Block9OutflowController::class, 'updateCause']);
    Route::delete('/cch/{id}/outflow/causes/{cId}', [\App\Http\Controllers\Api\Block9OutflowController::class, 'deleteCause']);
    Route::post('/cch/{id}/outflow/submit-block', [\App\Http\Controllers\Api\Block9OutflowController::class, 'submitBlock']);

    // ─── Block 10: Closing ───────────────────────────────────────────────────
    Route::get('/cch/{id}/closing', [\App\Http\Controllers\Api\Block10ClosingController::class, 'show']);
    Route::put('/cch/{id}/closing', [\App\Http\Controllers\Api\Block10ClosingController::class, 'update']);
    Route::post('/cch/{id}/closing', [\App\Http\Controllers\Api\Block10ClosingController::class, 'update']);
    Route::post('/cch/{id}/closing/attachments', [\App\Http\Controllers\Api\Block10ClosingController::class, 'uploadAttachment']);
    Route::delete('/cch/{id}/closing/attachments/{attachId}', [\App\Http\Controllers\Api\Block10ClosingController::class, 'deleteAttachment']);
    Route::post('/cch/{id}/closing/submit', [\App\Http\Controllers\Api\Block10ClosingController::class, 'submitClose']);
    Route::post('/cch/{id}/closing/approve', [\App\Http\Controllers\Api\Block10ClosingController::class, 'approveClose']);

    // ─── Author Comments (replace Q&A) ───────────────────────────────────────
    Route::get('/cch/{id}/comments', [\App\Http\Controllers\Api\CchCommentController::class, 'index']);
    Route::post('/cch/{id}/comments', [\App\Http\Controllers\Api\CchCommentController::class, 'store']);
    Route::delete('/cch/{id}/comments/{commentId}', [\App\Http\Controllers\Api\CchCommentController::class, 'destroy']);

    // ─── Attachments ─────────────────────────────────────────────────────────
    Route::post('/cch/{id}/{block}/attachments', [\App\Http\Controllers\Api\AttachmentController::class, 'upload']);
    Route::delete('/cch/{id}/{block}/attachments/{attachmentId}', [\App\Http\Controllers\Api\AttachmentController::class, 'destroy']);

    // ─── Storage (serve files with auth for preview) ──────────────────────────
    Route::get('/storage/{path}', [\App\Http\Controllers\Api\StorageController::class, 'show'])
        ->where('path', '.*');

    // ─── Support (Audit Logs & Notifications) ────────────────────────────────
    Route::get('/cch/{id}/audit-log', [\App\Http\Controllers\Api\AuditLogController::class, 'index']);

    // ─── Admin only: CRUD manage data (role_level 1 or 2) ─────────────────────
    Route::prefix('admin')->group(function () {
        Route::get('/cch', [\App\Http\Controllers\Api\AdminController::class, 'indexCch']);
        Route::delete('/cch/{id}', [\App\Http\Controllers\Api\AdminController::class, 'destroyCch']);
        Route::get('/users', [\App\Http\Controllers\Api\AdminController::class, 'indexUsers']);
        Route::get('/requests', [\App\Http\Controllers\Api\AdminController::class, 'indexRequests']);
        Route::delete('/requests/{id}', [\App\Http\Controllers\Api\AdminController::class, 'destroyRequest']);
        Route::get('/comments', [\App\Http\Controllers\Api\AdminController::class, 'indexComments']);
        Route::delete('/comments/{id}', [\App\Http\Controllers\Api\AdminController::class, 'destroyComment']);

        // ─── Master Data CRUD ─────────────────────────────────────────────────
        Route::prefix('masters')->group(function () {
            // Causes
            Route::get('/causes',        [\App\Http\Controllers\Api\MasterController::class, 'adminIndexCauses']);
            Route::post('/causes',       [\App\Http\Controllers\Api\MasterController::class, 'adminStoreCause']);
            Route::put('/causes/{id}',   [\App\Http\Controllers\Api\MasterController::class, 'adminUpdateCause']);
            Route::delete('/causes/{id}',[\App\Http\Controllers\Api\MasterController::class, 'adminDestroyCause']);

            // Currencies
            Route::get('/currencies',        [\App\Http\Controllers\Api\MasterController::class, 'adminIndexCurrencies']);
            Route::post('/currencies',       [\App\Http\Controllers\Api\MasterController::class, 'adminStoreCurrency']);
            Route::put('/currencies/{id}',   [\App\Http\Controllers\Api\MasterController::class, 'adminUpdateCurrency']);
            Route::delete('/currencies/{id}',[\App\Http\Controllers\Api\MasterController::class, 'adminDestroyCurrency']);

            // Failure Modes
            Route::get('/failure-modes',        [\App\Http\Controllers\Api\MasterController::class, 'adminIndexFailureModes']);
            Route::post('/failure-modes',       [\App\Http\Controllers\Api\MasterController::class, 'adminStoreFailureMode']);
            Route::put('/failure-modes/{id}',   [\App\Http\Controllers\Api\MasterController::class, 'adminUpdateFailureMode']);
            Route::delete('/failure-modes/{id}',[\App\Http\Controllers\Api\MasterController::class, 'adminDestroyFailureMode']);

            // Plants
            Route::get('/plants',        [\App\Http\Controllers\Api\MasterController::class, 'adminIndexPlants']);
            Route::post('/plants',       [\App\Http\Controllers\Api\MasterController::class, 'adminStorePlant']);
            Route::put('/plants/{id}',   [\App\Http\Controllers\Api\MasterController::class, 'adminUpdatePlant']);
            Route::delete('/plants/{id}',[\App\Http\Controllers\Api\MasterController::class, 'adminDestroyPlant']);

            // Processes
            Route::get('/processes',        [\App\Http\Controllers\Api\MasterController::class, 'adminIndexProcesses']);
            Route::post('/processes',       [\App\Http\Controllers\Api\MasterController::class, 'adminStoreProcess']);
            Route::put('/processes/{id}',   [\App\Http\Controllers\Api\MasterController::class, 'adminUpdateProcess']);
            Route::delete('/processes/{id}',[\App\Http\Controllers\Api\MasterController::class, 'adminDestroyProcess']);

            // Product Categories
            Route::get('/product-categories',        [\App\Http\Controllers\Api\MasterController::class, 'adminIndexProductCategories']);
            Route::post('/product-categories',       [\App\Http\Controllers\Api\MasterController::class, 'adminStoreProductCategory']);
            Route::put('/product-categories/{id}',   [\App\Http\Controllers\Api\MasterController::class, 'adminUpdateProductCategory']);
            Route::delete('/product-categories/{id}',[\App\Http\Controllers\Api\MasterController::class, 'adminDestroyProductCategory']);

            // Product Families
            Route::get('/product-families',        [\App\Http\Controllers\Api\MasterController::class, 'adminIndexProductFamilies']);
            Route::post('/product-families',       [\App\Http\Controllers\Api\MasterController::class, 'adminStoreProductFamily']);
            Route::put('/product-families/{id}',   [\App\Http\Controllers\Api\MasterController::class, 'adminUpdateProductFamily']);
            Route::delete('/product-families/{id}',[\App\Http\Controllers\Api\MasterController::class, 'adminDestroyProductFamily']);
        });
    });
});
