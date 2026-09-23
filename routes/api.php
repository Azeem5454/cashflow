<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BillingController;
use App\Http\Controllers\Api\V1\BookController;
use App\Http\Controllers\Api\V1\BusinessController;
use App\Http\Controllers\Api\V1\EntryController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\SettingsController;
use App\Http\Controllers\Api\V1\SocialAuthController;
use App\Http\Controllers\Webhooks\RevenueCatWebhookController;
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

// ── Webhooks (no auth; verified by shared secret; API routes carry no CSRF) ──
Route::post('webhooks/revenuecat', RevenueCatWebhookController::class)
    ->middleware('throttle:120,1')
    ->name('webhooks.revenuecat');

Route::prefix('v1')->group(function () {

    // ── Public (no auth) ────────────────────────────────────────────
    Route::post('auth/register', [AuthController::class, 'register'])
        ->middleware('throttle:5,1');

    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1');

    Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword'])
        ->middleware('throttle:5,1');

    // Email-first sign-in: which step to show next (password / create / social hint)
    Route::post('auth/check-email', [AuthController::class, 'checkEmail'])
        ->middleware('throttle:10,1');

    // Mobile social sign-in (public; same response shape as auth/login)
    Route::post('auth/social/exchange', [SocialAuthController::class, 'exchange'])
        ->middleware('throttle:10,1');

    Route::post('auth/apple', [SocialAuthController::class, 'apple'])
        ->middleware('throttle:10,1');

    // ── Protected (auth:sanctum) ────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        // Auth + Profile
        Route::post('auth/logout',         [AuthController::class, 'logout']);
        // Biometric (Face ID / fingerprint) sign-in — see AuthController for the contract
        Route::post  ('auth/biometric-token', [AuthController::class, 'createBiometricToken'])->middleware('throttle:10,1');
        Route::delete('auth/biometric-token', [AuthController::class, 'deleteBiometricToken'])->middleware('throttle:10,1');
        Route::post  ('auth/biometric-login', [AuthController::class, 'biometricLogin'])->middleware('throttle:10,1');
        Route::post('auth/email/resend',   [AuthController::class, 'resendVerification'])->middleware('throttle:6,1');
        Route::get ('user',                [AuthController::class, 'user']);
        Route::put ('profile',             [AuthController::class, 'updateProfile']);
        Route::put ('profile/password',    [AuthController::class, 'changePassword']);
        Route::delete('profile',           [AuthController::class, 'deleteAccount']);

        // ── Data endpoints ──
        // Soft verification: unverified users can use the product right away.
        // Only actions that email OTHER people require a verified address
        // (`api.verified` on team invitations + email report schedules).

        // Global search — entries across every accessible business + book
        Route::get('search', [SearchController::class, 'index'])->middleware('throttle:30,1');

        // Businesses
        Route::get   ('businesses',                  [BusinessController::class, 'index']);
        Route::post  ('businesses',                  [BusinessController::class, 'store']);
        Route::get   ('businesses/{id}',             [BusinessController::class, 'show']);
        Route::put   ('businesses/{id}',             [BusinessController::class, 'update']);
        Route::delete('businesses/{id}',             [BusinessController::class, 'destroy']);
        Route::get   ('businesses/{id}/books',       [BusinessController::class, 'books']);
        // Recycle bin (owner only) — must be declared before the {id} book routes below.
        Route::get   ('businesses/{id}/books/deleted', [BusinessController::class, 'deletedBooks'])
            ->middleware('throttle:60,1');
        Route::post  ('businesses/{id}/books',       [BusinessController::class, 'createBook']);
        Route::get   ('businesses/{id}/suggested-opening', [BusinessController::class, 'suggestedOpening']);
        Route::post  ('businesses/{id}/logo',        [BusinessController::class, 'uploadLogo'])->middleware('throttle:20,1');
        Route::delete('businesses/{id}/logo',        [BusinessController::class, 'deleteLogo']);
        Route::get   ('businesses/{id}/members',     [BusinessController::class, 'members']);
        Route::get   ('businesses/{id}/invitations', [BusinessController::class, 'invitations']);
        Route::post  ('businesses/{id}/invitations', [BusinessController::class, 'invite'])->middleware('api.verified');
        Route::delete('invitations/{id}',            [BusinessController::class, 'cancelInvitation']);
        Route::put   ('businesses/{businessId}/members/{userId}',    [BusinessController::class, 'updateMemberRole']);
        Route::delete('businesses/{businessId}/members/{userId}',    [BusinessController::class, 'removeMember']);

        // Books
        Route::get   ('books/recent',          [BookController::class, 'recentBooks']);
        Route::get   ('books/{id}',            [BookController::class, 'show']);
        Route::put   ('books/{id}',            [BookController::class, 'update']);
        Route::delete('books/{id}',            [BookController::class, 'destroy']);
        // Recycle bin: restore / permanent delete (owner only, throttled).
        Route::post  ('books/{id}/restore',    [BookController::class, 'restore'])->middleware('throttle:30,1');
        Route::delete('books/{id}/force',      [BookController::class, 'forceDestroy'])->middleware('throttle:30,1');
        Route::post  ('books/{id}/duplicate',  [BookController::class, 'duplicate']);
        Route::get   ('books/{id}/entries',    [BookController::class, 'entries']);
        Route::get   ('books/{id}/summary',    [BookController::class, 'summary']);
        Route::get   ('books/{id}/categories', [BookController::class, 'categories']);
        Route::get   ('books/{id}/payment-modes', [BookController::class, 'paymentModes']);
        Route::post  ('books/{id}/categories',    [BookController::class, 'addCategory']);
        Route::post  ('books/{id}/payment-modes', [BookController::class, 'addPaymentMode']);
        Route::get   ('books/{id}/activity',   [BookController::class, 'activity']);
        Route::get   ('books/{id}/recurring',  [BookController::class, 'recurringEntries']);
        Route::get   ('books/{id}/insights',   [BookController::class, 'aiInsights']);
        Route::get   ('books/{id}/report-data',     [BookController::class, 'reportData']);
        Route::get   ('books/{id}/report-schedule', [BookController::class, 'reportSchedule']);
        Route::put   ('books/{id}/report-schedule', [BookController::class, 'saveReportSchedule'])->middleware('api.verified');
        Route::delete('books/{id}/report-schedule', [BookController::class, 'deleteReportSchedule']);
        Route::post  ('books/{id}/suggest-category', [BookController::class, 'suggestCategory']);
        // AI entries: typed / spoken transaction → entry fields, and the quota that governs them
        Route::post  ('books/{id}/parse',    [BookController::class, 'parseEntry'])->middleware('throttle:20,1');
        Route::get   ('books/{id}/ai-quota', [BookController::class, 'aiQuota']);

        // Entries
        Route::post  ('books/{id}/entries',              [EntryController::class, 'store']);
        Route::post  ('books/{id}/entries/bulk-delete', [EntryController::class, 'bulkDelete']);
        Route::post  ('books/{id}/entries/bulk-update', [EntryController::class, 'bulkUpdate']);
        Route::post  ('books/{id}/entries/bulk-move',   [EntryController::class, 'bulkMove']);
        Route::get   ('entries/{id}',                    [EntryController::class, 'show']);
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
        Route::put   ('recurring/{id}',        [BookController::class, 'updateRecurring']);
        Route::put   ('recurring/{id}/toggle', [BookController::class, 'toggleRecurring']);
        Route::delete('recurring/{id}',        [BookController::class, 'deleteRecurring']);

        // OCR scan
        Route::post('books/{id}/scan', [EntryController::class, 'scan']);

        // Export (returns download URL)
        Route::get('books/{id}/export/{format}', [BookController::class, 'export'])
            ->where('format', 'pdf|csv');

        // Settings, billing, notifications, announcements
        Route::get   ('billing/checkout-url',        [SettingsController::class, 'billingCheckoutUrl']);
        // In-app purchase: pull the user's store entitlement from RevenueCat right after purchase/restore
        Route::post  ('billing/sync',                [BillingController::class, 'sync'])->middleware('throttle:10,1');
        Route::get   ('announcement',                [SettingsController::class, 'announcement']);
        // AI usage for the user's OWN plan (profile / billing usage card)
        Route::get   ('ai-quota',                    [SettingsController::class, 'aiQuota']);
        Route::get   ('notifications',               [SettingsController::class, 'notifications']);
        Route::post  ('notifications/mark-all-read', [SettingsController::class, 'markAllRead']);
        Route::delete('notifications/{id}',          [SettingsController::class, 'deleteNotification']);
    });
});
