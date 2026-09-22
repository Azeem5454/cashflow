<?php

use App\Models\Book;
use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing-v3');
})->name('home');

Route::view('/terms', 'legal.terms')->name('terms');
Route::view('/privacy', 'legal.privacy')->name('privacy');

// Dynamic sitemap — public pages, /blog, every published post + category.
Route::get('/sitemap.xml', \App\Http\Controllers\SitemapController::class)->name('sitemap');

// Admin-uploaded brand assets — served from DB so they survive redeploys.
Route::get('/brand-asset/{key}', [\App\Http\Controllers\BrandAssetController::class, 'show'])
    ->where('key', '[a-z0-9_-]+')
    ->name('brand-asset');

// Social OAuth (Google for now — add apple/microsoft later by extending
// SocialAuthController::SUPPORTED_PROVIDERS and config/services.php).
Route::middleware('guest')->group(function () {
    Route::get('/auth/{provider}/redirect',  [\App\Http\Controllers\Auth\SocialAuthController::class, 'redirect'])
        ->whereIn('provider', ['google'])
        ->name('social.redirect');
});

// The callback is shared by the web and mobile flows, so it sits OUTSIDE the
// guest group (an in-app browser may already hold a web session). The web
// path re-applies the guest redirect inside the controller.
Route::get('/auth/{provider}/callback',  [\App\Http\Controllers\Auth\SocialAuthController::class, 'callback'])
    ->whereIn('provider', ['google'])
    ->name('social.callback');

// Mobile app entry point for Google sign-in (guest-agnostic). Validates the
// deep-link redirect_uri, then runs the same Google flow; the callback hands
// the app a one-time code redeemable at POST /api/v1/auth/social/exchange.
Route::get('/auth/google/mobile', [\App\Http\Controllers\Auth\SocialAuthController::class, 'mobileRedirect'])
    ->middleware('throttle:20,1')
    ->name('social.mobile');


// Soft email verification: the app works right after sign-up. Only actions
// that email other people (team invites, email reports) check verification —
// see Business\Settings::sendInvite and Book\Show::saveEmailReport.
Route::get('/dashboard', \App\Livewire\Dashboard::class)
    ->middleware(['auth', 'redirect_admin'])
    ->name('dashboard');

Route::middleware(['auth', 'redirect_admin'])->group(function () {
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
    })->name('businesses.books.create')->middleware('business.unlocked');

    Route::get('/businesses/{business}/books/{book}', function (Business $business, Book $book) {
        abort_unless(
            auth()->user()->businesses()->where('businesses.id', $business->id)->exists(),
            403
        );
        abort_unless($book->business_id === $business->id, 404);

        return view('business.book.show', compact('business', 'book'));
    })->name('businesses.books.show')->middleware('business.unlocked');

    Route::get('/businesses/{business}/books/{book}/export/pdf', [\App\Http\Controllers\ExportController::class, 'pdf'])
        ->name('businesses.books.export.pdf')->middleware('business.unlocked');

    Route::get('/businesses/{business}/books/{book}/export/csv', [\App\Http\Controllers\ExportController::class, 'csv'])
        ->name('businesses.books.export.csv')->middleware('business.unlocked');

    Route::get('/businesses/{business}/books/{book}/entries/{entry}/attachment', [\App\Http\Controllers\ExportController::class, 'attachment'])
        ->name('businesses.books.entries.attachment')->middleware('business.unlocked');

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
    session()->regenerate();

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

    // Blog CMS
    Route::get('/blog',              \App\Livewire\Admin\Blog\Index::class)->name('blog.index');
    Route::get('/blog/autopilot',    \App\Livewire\Admin\Blog\Autopilot::class)->name('blog.autopilot');
    Route::get('/blog/create',       \App\Livewire\Admin\Blog\Edit::class)->name('blog.create');
    Route::get('/blog/categories',   \App\Livewire\Admin\Blog\Categories::class)->name('blog.categories');
    Route::get('/blog/{id}/edit',    \App\Livewire\Admin\Blog\Edit::class)->name('blog.edit');
});

// ── Public Blog ──────────────────────────────────────────────
Route::get('/blog',                     \App\Livewire\Blog\Index::class)->name('blog.index');
Route::get('/blog/feed.xml',            [\App\Http\Controllers\BlogFeedController::class, 'rss'])->name('blog.feed');
Route::get('/blog/category/{categorySlug}', \App\Livewire\Blog\Index::class)->name('blog.category');
Route::get('/blog/{slug}',              \App\Livewire\Blog\Show::class)
    ->where('slug', '[a-z0-9]+(?:-[a-z0-9]+)*')
    ->name('blog.show');

require __DIR__.'/auth.php';
