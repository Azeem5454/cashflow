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

## Brand Identity

### Logo Files
All logo assets live in `brand/` in the project root:

```
brand/
  cashflow_logo.png              # 512×512 square icon — favicon, app icon, social media
  cashflow_logo_horizontal.png   # 1200×400 wordmark — navbar, email headers
  cashflow_brand_guidelines.png  # Full brand reference (colours, type, spacing, usage)
```

Copy `cashflow_logo.png` to `public/favicon.png` for the browser tab icon.

**Logo usage rules:**
- Approved backgrounds: navy `#0a0f1e`, white `#f8fafc`, primary blue `#1a56db` only
- Never place the logo on photographic or patterned backgrounds
- Never stretch, rotate, skew, recolour, or add shadows/glows
- Maintain clear space equal to the icon height on all sides

### Colour Palette

| Token        | Hex       | Tailwind Custom Key | Usage                                        |
|--------------|-----------|---------------------|----------------------------------------------|
| Navy         | `#0a0f1e` | `navy`              | Page background, primary dark surface        |
| Dark         | `#111827` | `dark`              | Cards, sidebars, elevated surfaces           |
| Primary Blue | `#1a56db` | `primary`           | Buttons, links, active states, icon bg       |
| Accent Blue  | `#3b82f6` | `accent`            | Hover states, highlights, progress bars      |
| Light Blue   | `#93c5fd` | `blue-light`        | Subtext on dark, muted icons, trend lines    |
| X-Light Blue | `#dbeafe` | `blue-xlight`       | Subtle backgrounds, info banners             |
| Success      | `#22c55e` | —                   | Cash In entries, positive balances           |
| Danger       | `#ef4444` | —                   | Cash Out entries, negative balances, deletes |
| White        | `#f8fafc` | —                   | Body text on dark, light-mode surfaces       |
| Gray Dim     | `#64748b` | —                   | Muted labels, placeholders, captions         |

Register custom tokens in `tailwind.config.js`:
```js
theme: {
  extend: {
    colors: {
      navy:           '#0a0f1e',
      dark:           '#111827',
      primary:        '#1a56db',
      accent:         '#3b82f6',
      'blue-light':   '#93c5fd',
      'blue-xlight':  '#dbeafe',
    }
  }
}
```

### Typography

| Role             | Font                 | Weight | Usage                              |
|------------------|----------------------|--------|------------------------------------|
| Display/Headings | Bricolage Grotesque  | 800    | Hero text, page titles, wordmark   |
| Subheadings      | Plus Jakarta Sans    | 700    | Section headings, card titles      |
| Body / UI        | Outfit               | 400    | Body copy, labels, form fields     |
| Monospace        | Geist Mono           | 400    | Currency amounts, tokens, codes    |

Load all four in the guest and app layouts:
```html
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@400;700;800&family=Plus+Jakarta+Sans:wght@400;600;700&family=Outfit:wght@300;400;500&family=Geist+Mono&display=swap" rel="stylesheet">
```

Set font families in `tailwind.config.js`:
```js
fontFamily: {
  display: ['Bricolage Grotesque', 'sans-serif'],
  heading: ['Plus Jakarta Sans', 'sans-serif'],
  body:    ['Outfit', 'sans-serif'],
  mono:    ['Geist Mono', 'monospace'],
}
```

### Spacing Scale
Use only Tailwind spacing utilities. Standard scale: `4 · 8 · 12 · 16 · 24 · 32 · 48 · 64px`

### Border Radius
| Usage                    | Value  | Tailwind class  |
|--------------------------|--------|-----------------|
| Badges, tags             | 4px    | `rounded`       |
| Inputs, buttons          | 8px    | `rounded-lg`    |
| Cards, panels            | 12px   | `rounded-xl`    |
| Modals, large containers | 16px   | `rounded-2xl`   |
| Pills, avatars           | 9999px | `rounded-full`  |

### Voice & Tone
- **Confident** — short, direct sentences; no filler words
- **Accessible** — no accounting jargon; define any term you must use
- **Honest** — show real numbers; don't oversell
- **Global** — copy targets a worldwide audience; no region-specific currency or locale references in public-facing UI

---

## Frontend Design Principles

Every screen built in this app must be **production-grade and visually distinctive** — not generic AI-generated UI. Before writing any Blade or Livewire code for a new screen, answer these four questions:

1. **Purpose** — What problem does this screen solve? Who is using it and what do they need to feel?
2. **Tone** — CashFlow targets Pakistani small business owners. The aesthetic is: *finance-professional, confident, dark-luxe*. Think dashboard-grade clarity with depth — not a startup landing page, not a plain admin panel.
3. **Differentiation** — What is the one thing a user will remember about this screen? Every screen should have one deliberate "wow" detail: a micro-interaction, a beautifully typeset number, an unexpected layout flourish.
4. **Constraints** — Blade + Livewire + Tailwind only. No Vue, no React. CSS transitions/animations via Tailwind or `<style>` blocks in Blade.

### Aesthetic Direction

CashFlow uses a **refined dark-luxe** aesthetic:
- Deep navy backgrounds with layered card surfaces (`#0a0f1e` → `#111827` → `#1e293b`)
- Electric blue as the sole accent — never competing colours
- Currency amounts rendered large, bold, monospace — they are the hero of every screen
- Subtle grid or dot patterns as background texture (CSS only, very low opacity)
- Generous negative space — never cluttered
- Smooth transitions on state changes (entry added, balance updated, modal opened)

### Typography Hierarchy in UI

Apply the font stack consistently across every screen:

| Context                          | Font                 | Weight | Size approx  |
|----------------------------------|----------------------|--------|--------------|
| Page title / hero number         | Bricolage Grotesque  | 800    | 2xl – 4xl    |
| Section headings / card titles   | Plus Jakarta Sans    | 700    | lg – xl      |
| Labels, nav items, button text   | Outfit               | 500    | sm – base    |
| Body copy, descriptions          | Outfit               | 400    | sm – base    |
| All currency amounts             | Geist Mono           | 400    | base – 3xl   |

### Motion & Micro-interactions

- Balance summary numbers: animate count-up when a new entry is saved (Alpine.js)
- Slide-over panels (Add Entry, Edit Entry): slide in from the right with `transition-transform duration-300`
- Upgrade modal: fade in with subtle scale up (`scale-95 → scale-100`)
- Button hover states: slight brightness lift + shadow increase, not colour change
- Row hover in entry list: left border accent appears (`border-l-2 border-primary`)
- One staggered reveal on page load for dashboard cards (Alpine.js `x-init` + delay)

### What to NEVER do

- Never use Inter, Roboto, Arial, or system-ui as the primary font
- Never use purple gradients, rainbow gradients, or neon glow effects
- Never make a screen that looks like a generic SaaS template
- Never render currency amounts in a regular body font — always Geist Mono
- Never show a negative balance without visual emphasis (red colour + icon)
- Never use flat white backgrounds — all authenticated screens use the navy dark theme

---

## Design System

- **Theme:** Dark navy background (`#0a0f1e`), primary blue (`#1a56db`), white text
- **UI style:** Clean, finance-professional. Confident, not flashy.
- **Components:** All UI is Blade + Livewire. No Vue, no React, no Inertia.
- **Spacing:** Generous padding, card-based layouts, subtle `#1e293b` borders
- **Buttons:** Solid `primary` blue, outlined secondary, `danger` red for destructive actions

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
- Use a `protected rules(): array` method for validation (Livewire 4 — `#[Rule]`/`#[Validate]` attributes are unreliable in v4)
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
- [x] Landing page (/)
- [x] Login screen (/login)
- [x] Register screen (/register)
- [x] Email verification screen
- [x] Forgot/reset password screens
- [x] Main dashboard (/dashboard)
- [x] Create business
- [x] Business dashboard (/businesses/{business}) — books list, search, sort, Cash In/Out/Balance columns
- [x] Business settings + team management (/businesses/{business}/settings) — General tab (name/description/delete), Team tab (invite, members list with role change + remove, pending invitations), upgrade modal
- [x] Invite team member flow — TeamInvitation mailable, email template, invitation accept page (/invitations/{token}/accept) with guest/auth states
- [x] Create book — inline modal on Business dashboard (`Business\Show` component), redirects to book detail on create
- [x] Book detail (ledger) + balance summary (/businesses/{business}/books/{book}) — `Book\Show` component with sticky header, balance summary strip (Cash In / Cash Out / Net Balance), entries table with running balance, filter bar (type + sort), search, empty state
- [x] Add / edit / delete entry (slide-over panel inside book detail) — right-side slide-over with Alpine transitions, type toggle (Cash In/Out), amount + description + date + category + payment mode + reference fields, custom category & payment mode management (add new inline), save & add-new, edit mode, inline delete confirm on hover
- [x] Profile settings (/settings/profile)
- [x] Billing & plans (Stripe) (/settings/billing) — Free/Pro plan cards, Stripe Checkout, Billing Portal, grace period banner with Resume Plan, webhook sync via AppServiceProvider
- [x] Post-cancellation downgrade flow — locked business overlay on dashboard (blur + Resubscribe CTA), route-level gate redirecting locked businesses to billing, grace period expiry date display
- [x] Dashboard — split into "My Businesses" (owned) and "Shared with Me" (invited) sections with left accent border differentiator, owner attribution on shared cards
- [ ] **NEXT: PDF + CSV export (Pro only)**
- [ ] Upgrade prompt modal (reusable component) — inline modal for gated actions instead of redirect to billing
- [ ] Admin panel (/admin/*) — see Admin Panel section below

---

## Session Notes (last updated 2026-03-14)

### Completed this session
- `app/Livewire/Settings/Profile.php` + `resources/views/livewire/settings/profile.blade.php`
  - Two tabs (Profile / Password) + always-visible Danger Zone
  - Profile tab is default-visible — no `x-cloak` on it (avoids layout collapse before Alpine init)
- `app/Livewire/Settings/Billing.php` + `resources/views/livewire/settings/billing.blade.php`
  - Stripe Checkout via `newSubscription()->checkout()`, Billing Portal via `billingPortalUrl()`
  - Race condition fix: trust `?checkout=success` URL directly, don't check `subscribed()` immediately
  - Grace period banner with `$subscription->ends_at` expiry date + Resume Plan button
  - `resume()` method: `$subscription->resume()` during grace period, falls back to fresh checkout if ended
- `app/Providers/AppServiceProvider.php` — WebhookReceived listener syncs `user.plan` on subscription events
- `app/Livewire/Dashboard.php` + `resources/views/livewire/dashboard.blade.php`
  - Split into "My Businesses" (owner) and "Shared with Me" (editor/viewer) sections
  - Locked business overlay (blur + Resubscribe CTA) for extra owned businesses on Free plan
  - `->with('owner')` eager load for "Owned by [name]" attribution on shared cards
- `routes/web.php` — free plan gate on `/businesses/{business}` redirects locked businesses to billing
- Stripe CLI installed to `~/bin/stripe`, webhook secret configured in `.env`

### Key design patterns established
- Sticky header: `dark:bg-navy/95 bg-white/95 backdrop-blur-md` + `dark:border-b dark:border-slate-800`
- Cards/panels: `dark:bg-dark bg-white dark:border dark:border-slate-700`
- Form inputs: `dark:bg-slate-800 bg-white dark:border dark:border-slate-700 border border-gray-300 dark:text-white text-gray-900 rounded-xl`
- Slide-over panel: `fixed inset-y-0 right-0 w-full max-w-lg` with Alpine `$wire.entangle().live` + CSS transitions
- Alpine custom dropdowns instead of native `<select>` (avoids double-chevron artifact)
- Balance summary: `flex` with `flex-1` children + `divide-x` (not grid — more reliable with Tailwind JIT)
- Category/payment mode: custom dropdown with search + "Add New" inline form
- Tailwind safelist in `tailwind.config.js` for dark mode classes that may not appear in scanned templates
- Role change on members: `@change="$wire.updateMemberRole('id', $event.target.value)"`
- Livewire events dispatched from component, caught in Blade with `@event-name.window`
- **Tailwind JIT gotchas:** arbitrary hex with opacity (`dark:bg-[#hex]/95`) and opacity variants (`/30`, `/50`, `/60`) on new classes don't compile — use named tokens (`navy`, `dark`) or safelist explicitly
- **Stripe race condition:** after `?checkout=success`, `subscribed()` returns false (webhook not yet fired) — update `user.plan` directly from the success URL, use webhook as async backup
- **Dashboard sections:** `$businesses->where('pivot.role', 'owner')` and `->whereIn('pivot.role', [...])` to split owned vs shared after a single query

---

## Admin Panel

Internal tool for the CashFlow operator (you). Accessed at `/admin/*`, gated behind `is_admin = true` on the `users` table. Never exposed to regular users.

### Access Control
- Add `is_admin` boolean column to `users` table (default `false`)
- `App\Http\Middleware\AdminMiddleware` — aborts 403 if `!auth()->user()->is_admin`
- All `/admin` routes wrapped in `middleware(['auth', 'admin'])` group
- No role/permission package needed — simple boolean check is sufficient

### Route Structure
```
/admin                          → admin dashboard (overview)
/admin/users                    → users list
/admin/users/{user}             → user detail
/admin/businesses               → all businesses
/admin/subscriptions            → all subscriptions / revenue
/admin/invitations              → pending & expired invitations
/admin/appearance               → brand & appearance settings
```

### Pages & Features

#### 1. Overview (`/admin`) — `Admin\Dashboard`
- **KPI strip:** Total Users · Pro Users · MRR · Active Subscriptions · Churned (30d)
- **Signups chart:** new users per day for last 30 days (simple bar chart, pure Blade/Alpine — no JS chart library)
- **Recent signups table:** last 10 users with name, email, plan, joined date
- **Top businesses:** most active by entry count

#### 2. Users (`/admin/users`) — `Admin\Users`
- Paginated table: name, email, plan badge, businesses count, joined date, last login
- Search by name/email
- Filter by plan (free / pro)
- Per-row actions: **View**, **Force Pro** (set plan=pro without Stripe), **Force Free**, **Delete**
- Clicking a row → user detail page

#### 3. User Detail (`/admin/users/{user}`) — `Admin\UserDetail`
- Full profile: name, email, plan, created_at, Stripe customer ID
- Subscription info: status, current period, next billing date, `ends_at` if on grace period
- Businesses list: name, role (owner/editor/viewer), books count
- Invitations sent: email, role, status, expires_at
- Danger zone: **Impersonate** (login-as), **Delete Account**

#### 4. Businesses (`/admin/businesses`) — `Admin\Businesses`
- Paginated table: name, owner name/email, members count, books count, entries count, created_at
- Search by business name or owner email
- Click row → shows business detail inline (books list, members)

#### 5. Subscriptions (`/admin/subscriptions`) — `Admin\Subscriptions`
- Table of all Cashier subscriptions: user, status, Stripe subscription ID, current period end, `ends_at`
- Filter by status: active / canceled / on_grace_period / ended
- MRR calculation: count of `active` subscriptions × $3
- Month-over-month growth indicator

#### 6. Invitations (`/admin/invitations`) — `Admin\Invitations`
- All invitations: email, business name, role, status (pending/accepted/expired), expires_at
- Filter: pending / accepted / expired
- Action: **Resend** (re-creates invitation with new token + 72h expiry), **Cancel**

#### 7. Appearance (`/admin/appearance`) — `Admin\Appearance`
Controls all brand/visual settings that are rendered dynamically from the database rather than hardcoded. Settings are stored in a `settings` key-value table (`key`, `value`, `updated_at`). Read via a `Setting::get('key', $default)` helper; written via the admin UI only.

**Logo Management**
- **Logo — Dark mode** (used on dark navbar, dark landing): upload replaces `public/brand/logo-dark.png`
- **Logo — Light mode** (used on light navbar, light landing): upload replaces `public/brand/logo-light.png`
- **Favicon**: upload replaces `public/favicon.png`
- Each upload: file input (PNG only, max 1 MB), live preview, "Revert to default" button
- Logos served via `asset()` with a cache-busting query string (`?v={{ filemtime(...) }}`)

**Colour Palette**
- Editable hex inputs for every brand token: Navy, Dark, Primary Blue, Accent Blue, Light Blue, X-Light Blue, Success, Danger
- Changes write to `settings` table and regenerate a `public/brand/theme.css` file with CSS custom properties (`--color-navy: #0a0f1e;` etc.)
- The app layout includes `theme.css` after the Vite bundle — CSS variables override Tailwind defaults at runtime
- "Reset to defaults" button restores all tokens to brand guideline values

**Typography**
- Dropdown per role (Display, Heading, Body, Mono) — choose from curated list of Google Fonts already in the brand guidelines, plus a "Custom" option for any Google Fonts family name
- Changes update the Google Fonts `<link>` href stored in settings and write font-family CSS variables to `theme.css`
- Live preview strip shows a sample sentence in each selected font

**Landing Page Copy**
- Editable fields: Hero headline, Hero subheadline, CTA button text, Footer tagline
- Plain text only — no rich text editor needed
- Rendered in `landing.blade.php` via `Setting::get('landing.hero_headline', 'Track every rupee...')`

**Email Sender**
- Editable: From Name, From Address (saved to settings, written to config at runtime via `Config::set`)
- Does not expose SMTP credentials — only display name/address

### Implementation Notes for Appearance
- `settings` table: `id` (int auto-increment), `key` (string, unique, indexed), `value` (text), `updated_at`
- `App\Helpers\Setting` — static `get($key, $default)` and `set($key, $value)` using `Cache::rememberForever`; cache tagged `settings` so `Setting::flush()` clears all
- Logo uploads handled via Laravel's `Storage` disk — store in `public/brand/`, symlink via `php artisan storage:link`
- `theme.css` regenerated on every colour/font save — write directly to `public/brand/theme.css`
- No live preview of CSS variable changes (too complex) — save and reload to see effect
- All file paths served with `asset()` + filemtime cache-buster so browsers pick up new logos immediately

### Admin Layout
- Separate Blade layout: `resources/views/layouts/admin.blade.php`
- Left sidebar (fixed, narrower than app layout) with nav links to each section
- Dark navy theme (same as app) — admins are power users, dark mode always on
- No dark mode toggle — admin is always dark
- Breadcrumb trail on every page

### Implementation Notes
- All admin Livewire components in `app/Livewire/Admin/`
- All admin views in `resources/views/livewire/admin/`
- Use `Livewire\WithPagination` on list components
- Impersonate: set `session(['impersonating' => $user->id])`, re-auth as that user, store original admin ID to return
- No soft deletes — hard delete with cascading (already handled by DB constraints)
- Never expose raw Stripe secret keys or webhook secrets in admin UI
