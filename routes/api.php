<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BookController;
use App\Http\Controllers\Api\V1\BusinessController;
use App\Http\Controllers\Api\V1\EntryController;
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

        // Auth
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('user', [AuthController::class, 'user']);

        // Businesses
        Route::get('businesses', [BusinessController::class, 'index']);
        Route::get('businesses/{id}', [BusinessController::class, 'show']);
        Route::get('businesses/{id}/books', [BusinessController::class, 'books']);
        Route::get('businesses/{id}/members', [BusinessController::class, 'members']);
        Route::post('businesses/{id}/books', [BusinessController::class, 'createBook']);

        // Books
        Route::get('books/recent', [BookController::class, 'recentBooks']);
        Route::get('books/{id}', [BookController::class, 'show']);
        Route::get('books/{id}/entries', [BookController::class, 'entries']);
        Route::get('books/{id}/summary', [BookController::class, 'summary']);
        Route::get('books/{id}/categories', [BookController::class, 'categories']);
        Route::get('books/{id}/payment-modes', [BookController::class, 'paymentModes']);
        Route::get('books/{id}/activity', [BookController::class, 'activity']);
        Route::get('books/{id}/recurring', [BookController::class, 'recurringEntries']);
        Route::get('books/{id}/insights', [BookController::class, 'aiInsights']);
        Route::post('books/{id}/suggest-category', [BookController::class, 'suggestCategory']);

        // Entries
        Route::post('books/{id}/entries', [EntryController::class, 'store']);
        Route::put('entries/{id}', [EntryController::class, 'update']);
        Route::delete('entries/{id}', [EntryController::class, 'destroy']);

        // Entry comments
        Route::get('entries/{id}/comments', [EntryController::class, 'comments']);
        Route::post('entries/{id}/comments', [EntryController::class, 'addComment']);
        Route::delete('comments/{id}', [EntryController::class, 'deleteComment']);

        // Recurring management
        Route::put('recurring/{id}/toggle', [BookController::class, 'toggleRecurring']);
        Route::delete('recurring/{id}', [BookController::class, 'deleteRecurring']);

        // OCR scan
        Route::post('books/{id}/scan', [EntryController::class, 'scan']);

        // Export (returns download URL)
        Route::get('books/{id}/export/{format}', [BookController::class, 'export'])
            ->where('format', 'pdf|csv');
    });
});
