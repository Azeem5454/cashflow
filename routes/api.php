<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BookController;
use App\Http\Controllers\Api\V1\BusinessController;
use App\Http\Controllers\Api\V1\EntryController;
use App\Http\Controllers\Api\V1\SettingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — /api/v1/
|--------------------------------------------------------------------------
|
| All responses: JSON, camelCase keys, ISO 8601 dates, amounts as strings.
| Auth: Laravel Sanctum token-based authentication.
|
*/

Route::prefix('v1')->group(function () {

    // ── Public (no auth) ────────────────────────────────────────────
    Route::post('auth/register', [AuthController::class, 'register'])
        ->middleware('throttle:5,1');

    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1');

    Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword'])
        ->middleware('throttle:5,1');

    // ── Protected (auth:sanctum) ────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        // Auth + Profile
        Route::post('auth/logout',         [AuthController::class, 'logout']);
        Route::post('auth/email/resend',   [AuthController::class, 'resendVerification']);
        Route::get ('user',                [AuthController::class, 'user']);
        Route::put ('profile',             [AuthController::class, 'updateProfile']);
        Route::put ('profile/password',    [AuthController::class, 'changePassword']);
        Route::delete('profile',           [AuthController::class, 'deleteAccount']);

        // Businesses
        Route::get   ('businesses',                  [BusinessController::class, 'index']);
        Route::post  ('businesses',                  [BusinessController::class, 'store']);
        Route::get   ('businesses/{id}',             [BusinessController::class, 'show']);
        Route::put   ('businesses/{id}',             [BusinessController::class, 'update']);
        Route::delete('businesses/{id}',             [BusinessController::class, 'destroy']);
        Route::get   ('businesses/{id}/books',       [BusinessController::class, 'books']);
        Route::post  ('businesses/{id}/books',       [BusinessController::class, 'createBook']);
        Route::get   ('businesses/{id}/members',     [BusinessController::class, 'members']);
        Route::get   ('businesses/{id}/invitations', [BusinessController::class, 'invitations']);
        Route::post  ('businesses/{id}/invitations', [BusinessController::class, 'invite']);
        Route::delete('invitations/{id}',            [BusinessController::class, 'cancelInvitation']);
        Route::put   ('businesses/{businessId}/members/{userId}',    [BusinessController::class, 'updateMemberRole']);
        Route::delete('businesses/{businessId}/members/{userId}',    [BusinessController::class, 'removeMember']);

        // Books
        Route::get   ('books/recent',          [BookController::class, 'recentBooks']);
        Route::get   ('books/{id}',            [BookController::class, 'show']);
        Route::put   ('books/{id}',            [BookController::class, 'update']);
        Route::delete('books/{id}',            [BookController::class, 'destroy']);
        Route::post  ('books/{id}/duplicate',  [BookController::class, 'duplicate']);
        Route::get   ('books/{id}/entries',    [BookController::class, 'entries']);
        Route::get   ('books/{id}/summary',    [BookController::class, 'summary']);
        Route::get   ('books/{id}/categories', [BookController::class, 'categories']);
        Route::get   ('books/{id}/payment-modes', [BookController::class, 'paymentModes']);
        Route::get   ('books/{id}/activity',   [BookController::class, 'activity']);
        Route::get   ('books/{id}/recurring',  [BookController::class, 'recurringEntries']);
        Route::get   ('books/{id}/insights',   [BookController::class, 'aiInsights']);
        Route::get   ('books/{id}/report-data',     [BookController::class, 'reportData']);
        Route::get   ('books/{id}/report-schedule', [BookController::class, 'reportSchedule']);
        Route::put   ('books/{id}/report-schedule', [BookController::class, 'saveReportSchedule']);
        Route::delete('books/{id}/report-schedule', [BookController::class, 'deleteReportSchedule']);
        Route::post  ('books/{id}/suggest-category', [BookController::class, 'suggestCategory']);

        // Entries
        Route::post  ('books/{id}/entries',              [EntryController::class, 'store']);
        Route::post  ('books/{id}/entries/bulk-delete', [EntryController::class, 'bulkDelete']);
        Route::post  ('books/{id}/entries/bulk-update', [EntryController::class, 'bulkUpdate']);
        Route::post  ('books/{id}/entries/bulk-move',   [EntryController::class, 'bulkMove']);
        Route::put   ('entries/{id}',                    [EntryController::class, 'update']);
        Route::delete('entries/{id}',                    [EntryController::class, 'destroy']);

        // Entry attachments
        Route::post  ('entries/{id}/attachment', [EntryController::class, 'uploadAttachment']);
        Route::get   ('entries/{id}/attachment', [EntryController::class, 'getAttachment']);
        Route::delete('entries/{id}/attachment', [EntryController::class, 'deleteAttachment']);

        // Entry comments
        Route::get   ('entries/{id}/comments', [EntryController::class, 'comments']);
        Route::post  ('entries/{id}/comments', [EntryController::class, 'addComment']);
        Route::delete('comments/{id}',         [EntryController::class, 'deleteComment']);

        // Recurring management
        Route::put   ('recurring/{id}/toggle', [BookController::class, 'toggleRecurring']);
        Route::delete('recurring/{id}',        [BookController::class, 'deleteRecurring']);

        // OCR scan
        Route::post('books/{id}/scan', [EntryController::class, 'scan']);

        // Export (returns download URL)
        Route::get('books/{id}/export/{format}', [BookController::class, 'export'])
            ->where('format', 'pdf|csv');

        // Settings, billing, notifications, announcements
        Route::get   ('billing/checkout-url',        [SettingsController::class, 'billingCheckoutUrl']);
        Route::get   ('announcement',                [SettingsController::class, 'announcement']);
        Route::get   ('notifications',               [SettingsController::class, 'notifications']);
        Route::post  ('notifications/mark-all-read', [SettingsController::class, 'markAllRead']);
        Route::delete('notifications/{id}',          [SettingsController::class, 'deleteNotification']);
    });
});
