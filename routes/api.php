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

    // ── Protected (auth:sanctum) ────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        // Auth
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('user', [AuthController::class, 'user']);

        // Businesses
        Route::get('businesses', [BusinessController::class, 'index']);
        Route::get('businesses/{id}', [BusinessController::class, 'show']);
        Route::get('businesses/{id}/books', [BusinessController::class, 'books']);

        // Books
        Route::get('books/recent', [BookController::class, 'recentBooks']);
        Route::get('books/{id}', [BookController::class, 'show']);
        Route::get('books/{id}/entries', [BookController::class, 'entries']);
        Route::get('books/{id}/summary', [BookController::class, 'summary']);
        Route::get('books/{id}/categories', [BookController::class, 'categories']);
        Route::get('books/{id}/payment-modes', [BookController::class, 'paymentModes']);
        Route::post('books/{id}/suggest-category', [BookController::class, 'suggestCategory']);

        // Entries
        Route::post('books/{id}/entries', [EntryController::class, 'store']);
        Route::put('entries/{id}', [EntryController::class, 'update']);
        Route::delete('entries/{id}', [EntryController::class, 'destroy']);

        // OCR scan
        Route::post('books/{id}/scan', [EntryController::class, 'scan']);
    });
});
