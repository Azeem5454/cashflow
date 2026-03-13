# CashFlow

Business cash flow tracking SaaS for small business owners, freelancers, and their finance teams.
Users can manage multiple businesses, organize cash entries into books (by month/quarter/project),
and collaborate with team members — all with a live balance summary.

---

## Stack

- **Backend:** Laravel 11 (PHP 8.3)
- **Frontend:** Livewire 3 + Alpine.js + Blade templates
- **Styling:** Tailwind CSS (utility classes only, no custom CSS files)
- **Database:** PostgreSQL 16
- **Auth:** Laravel Breeze + Laravel Sanctum
- **Roles:** Spatie Laravel Permission
- **Billing:** Laravel Cashier + Stripe
- **PDF Export:** barryvdh/laravel-dompdf
- **Queue/Cache:** Redis
- **Email:** Laravel Mail (Mailgun or SES)

---

## Design System

- **Fonts:** "Plus Jakarta Sans" (headings) + "DM Sans" (body) — loaded via Google Fonts
- **Theme:** Dark navy background (#0a0f1e), electric blue accent (#3b82f6), white text
- **UI style:** Clean, modern, finance-professional. Confident, not flashy.
- **Components:** All UI is Blade + Livewire. No Vue, no React, no Inertia.
- **Spacing:** Generous padding, card-based layouts, subtle borders
- **Buttons:** Solid blue primary, outlined secondary, destructive red for deletes

---

## Data Hierarchy

```
Account (User)
  └── Business (e.g. Eveso IT Company)
        └── Book (e.g. March 2026)
              └── Entry (Cash In / Cash Out)
```

---

## Subscription Plans

| Feature              | Free         | Pro          |
|----------------------|--------------|--------------|
| Price                | $0/month     | $3/month     |
| Businesses           | 1            | Unlimited    |
| Books per business   | Unlimited    | Unlimited    |
| Entries per book     | Unlimited    | Unlimited    |
| Team members         | 2 max        | Unlimited    |
| PDF / CSV export     | No           | Yes          |
| Support              | Community    | Priority     |

---

## User Roles (per business)

| Role    | Permissions                                                      |
|---------|------------------------------------------------------------------|
| owner   | Full access — settings, billing, team, books, entries            |
| editor  | Create books, add/edit/delete entries. No team or settings access|
| viewer  | Read-only. Cannot modify anything                                |

Roles are managed via Spatie Laravel Permission, scoped per business using the business_user pivot.

---

## Key Commands

```bash
php artisan serve          # Start dev server (http://localhost:8000)
npm run dev                # Compile assets with Vite (keep running during dev)
php artisan migrate        # Run all migrations
php artisan migrate:fresh  # Drop all tables and re-migrate (dev only)
php artisan test           # Run PHPUnit test suite
php artisan queue:work     # Start queue worker (emails, exports)
```

---

## Project Structure

```
app/
  Models/
    User.php
    Business.php
    Book.php
    Entry.php
    Invitation.php
  Livewire/
    Business/
    Book/
    Entry/
    Settings/
  Http/
    Controllers/        # Minimal — prefer Livewire components
    Middleware/
  Policies/             # BusinessPolicy, BookPolicy, EntryPolicy

resources/
  views/
    layouts/
      app.blade.php     # Authenticated layout (sidebar + nav)
      guest.blade.php   # Guest layout (landing, auth pages)
    components/         # Reusable Blade components (button, card, modal, etc.)
    landing.blade.php
    dashboard.blade.php
    auth/
    business/
    book/
    settings/

routes/
  web.php               # All web routes
  auth.php              # Breeze auth routes

database/
  migrations/
  seeders/
```

---

## Database Rules

- All primary keys are **UUIDs** — use `HasUuids` trait on every model
- All tables have `created_at` and `updated_at` (Laravel default timestamps)
- `entries.book_id` must have a database index for fast queries
- `business_user` is a pivot table (no separate model needed)
- `invitations.token` must be unique and indexed
- Financial amounts use `DECIMAL(15, 2)` — never floats

---

## Security Rules

- All authenticated routes protected by `auth` middleware
- Business data is fully isolated — users can only access businesses in `business_user` where their `user_id` exists
- Never store card numbers — Stripe handles all payment data
- Passwords stored as bcrypt hashes
- CSRF protection on all forms (Laravel default)
- Rate limit login, register, and invitation endpoints
- Invitation tokens expire after 72 hours

---

## Plan Enforcement

- Free users attempting to add a 2nd business → show upgrade modal
- Free users attempting to add a 3rd team member → show upgrade modal
- Free users clicking export → show upgrade modal (don't disable the button, gate the action)
- Check plan limits in Livewire components using `auth()->user()->plan === 'pro'`

---

## Livewire Conventions

- One Livewire component per major UI interaction (e.g. `CreateBook`, `AddEntry`, `InviteMember`)
- Use `#[Rule]` attributes for validation (Livewire 3 syntax)
- Emit events to update parent components after mutations (e.g. after adding an entry, refresh balance summary)
- Balance summary must update in real time — use Livewire `$refresh` or reactive properties
- Use slide-over panels (not full page navigations) for Add Entry and Edit Entry

---

## Blade Component Conventions

- Create reusable components in `resources/views/components/`
- Common components needed: `x-button`, `x-card`, `x-modal`, `x-badge`, `x-input`, `x-select`
- Use `x-layouts.app` for authenticated pages and `x-layouts.guest` for public pages

---

## Route Naming

```
/                                    → landing page (guest only)
/dashboard                           → main dashboard (auth)
/businesses/create                   → create business
/businesses/{business}               → business dashboard
/businesses/{business}/settings      → business settings
/businesses/{business}/team          → team management
/businesses/{business}/books/create  → create book
/businesses/{business}/books/{book}  → book detail (ledger)
/settings/profile                    → profile settings
/settings/billing                    → billing & plans
/invitations/{token}/accept          → accept invitation
```

---

## What NOT to do

- Do not use Vue, React, or Inertia — this is a Livewire app
- Do not use `WidthType.PERCENTAGE` in any context
- Do not store Stripe card data in the database
- Do not use inline styles — use Tailwind classes only
- Do not create separate CSS files — all styling via Tailwind
- Do not use floats for financial amounts — always DECIMAL(15,2)
- Do not skip CSRF tokens on forms
- Do not allow cross-business data access — always scope queries to the authenticated user's businesses

---

## Current Build Order

- [x] Project setup (Laravel 11, PostgreSQL, Breeze, Livewire, Tailwind)
- [x] Database migrations and models
- [ ] Landing page (/)
- [ ] Login screen (/login)
- [ ] Register screen (/register)
- [ ] Email verification screen
- [ ] Forgot/reset password screens
- [ ] Main dashboard (/dashboard)
- [ ] Create business
- [ ] Business dashboard
- [ ] Business settings + team management
- [ ] Create book
- [ ] Book detail (ledger) + balance summary
- [ ] Add / edit / delete entry
- [ ] Invite team member flow
- [ ] Profile settings
- [ ] Billing & plans (Stripe)
- [ ] Upgrade prompt modal
- [ ] PDF + CSV export (Pro only)
