<?php

use App\Http\Controllers\ProfileController;
use App\Models\Book;
use App\Models\Business;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing');
})->name('home');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/businesses/create', function () {
        return view('business.create');
    })->name('businesses.create');

    Route::get('/businesses/{business}', function (Business $business) {
        abort_unless(
            auth()->user()->businesses()->where('businesses.id', $business->id)->exists(),
            403
        );

        return view('business.show', compact('business'));
    })->name('businesses.show');

    Route::get('/businesses/{business}/books/create', function (Business $business) {
        abort_unless(
            auth()->user()->businesses()->where('businesses.id', $business->id)->exists(),
            403
        );

        return view('business.book.create', compact('business'));
    })->name('businesses.books.create');

    Route::get('/businesses/{business}/books/{book}', function (Business $business, Book $book) {
        abort_unless(
            auth()->user()->businesses()->where('businesses.id', $business->id)->exists(),
            403
        );
        abort_unless($book->business_id === $business->id, 404);

        return view('business.book.show', compact('business', 'book'));
    })->name('businesses.books.show');

    Route::get('/businesses/{business}/settings', function (Business $business) {
        abort_unless(
            auth()->user()->businesses()->where('businesses.id', $business->id)->exists(),
            403
        );

        return view('business.settings', compact('business'));
    })->name('businesses.settings');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/settings/billing', function () {
        return view('settings.billing');
    })->name('billing');
});

// Invitation acceptance — accessible to guests (shows login prompt if not authenticated)
Route::get('/invitations/{invitation:token}/accept', \App\Livewire\Invitation\Accept::class)
    ->name('invitations.accept');

require __DIR__.'/auth.php';
