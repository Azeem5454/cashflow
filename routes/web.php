<?php

use App\Models\Book;
use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing');
})->name('home');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified', 'redirect_admin'])->name('dashboard');

Route::middleware(['auth', 'verified', 'redirect_admin'])->group(function () {
    Route::get('/businesses/create', function () {
        return view('business.create');
    })->name('businesses.create');

    Route::get('/businesses/{business}', function (Business $business) {
        $user = auth()->user();

        abort_unless(
            $user->businesses()->where('businesses.id', $business->id)->exists(),
            403
        );

        // Free plan: block access to extra owned businesses
        if (! $user->isPro()) {
            $role = $user->businesses()->where('businesses.id', $business->id)->first()?->pivot?->role;
            if ($role === 'owner') {
                $firstOwnedId = $user->ownedBusinesses()->oldest()->value('id');
                if ($business->id !== $firstOwnedId) {
                    return redirect()->route('billing');
                }
            }
        }

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

    Route::get('/businesses/{business}/books/{book}/export/pdf', [\App\Http\Controllers\ExportController::class, 'pdf'])
        ->name('businesses.books.export.pdf');

    Route::get('/businesses/{business}/books/{book}/export/csv', [\App\Http\Controllers\ExportController::class, 'csv'])
        ->name('businesses.books.export.csv');

    Route::get('/businesses/{business}/books/{book}/entries/{entry}/attachment', [\App\Http\Controllers\ExportController::class, 'attachment'])
        ->name('businesses.books.entries.attachment');

    Route::get('/businesses/{business}/settings', function (Business $business) {
        abort_unless(
            auth()->user()->businesses()->where('businesses.id', $business->id)->exists(),
            403
        );

        return view('business.settings', compact('business'));
    })->name('businesses.settings');
});

Route::middleware(['auth', 'redirect_admin'])->group(function () {
    Route::get('/profile', \App\Livewire\Settings\Profile::class)->name('profile.edit');

    Route::get('/settings/billing', \App\Livewire\Settings\Billing::class)->name('billing');
});

// Invitation acceptance — accessible to guests (shows login prompt if not authenticated)
Route::get('/invitations/{invitation:token}/accept', \App\Livewire\Invitation\Accept::class)
    ->name('invitations.accept');

// ── Stop Impersonation ──────────────────────────────────────
Route::post('/admin/stop-impersonating', function () {
    $adminId = session('impersonating_admin_id');
    if (! $adminId) {
        return redirect()->route('dashboard');
    }

    $admin = User::findOrFail($adminId);
    session()->forget('impersonating_admin_id');
    Auth::login($admin);

    return redirect()->route('admin.dashboard');
})->middleware('auth')->name('admin.stop-impersonating');

// ── Admin Panel ─────────────────────────────────────────────
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', \App\Livewire\Admin\Dashboard::class)->name('dashboard');
    Route::get('/users', \App\Livewire\Admin\Users::class)->name('users');
    Route::get('/users/{user}', \App\Livewire\Admin\UserDetail::class)->name('users.show');
    Route::get('/businesses', \App\Livewire\Admin\Businesses::class)->name('businesses');
    Route::get('/subscriptions', \App\Livewire\Admin\Subscriptions::class)->name('subscriptions');
    Route::get('/invitations', \App\Livewire\Admin\Invitations::class)->name('invitations');
    Route::get('/appearance', \App\Livewire\Admin\Appearance::class)->name('appearance');
    Route::get('/announcement', \App\Livewire\Admin\Announcement::class)->name('announcement');
    Route::get('/profile', \App\Livewire\Admin\Profile::class)->name('profile');
});

require __DIR__.'/auth.php';
