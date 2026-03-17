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

> **Pricing note:** Pro is currently $3/month. Once AI features ship, raise to **$5/month** — still the cheapest AI-powered cash flow tool on the market by 3×. AI cost per Pro user is ~$0.40/month at typical usage, well within margin.

| Feature                         | Free                 | Pro ($5/month)        |
|---------------------------------|----------------------|-----------------------|
| Businesses                      | 1                    | Unlimited             |
| Books per business              | Unlimited            | Unlimited             |
| Entries per book                | Unlimited            | Unlimited             |
| Team members                    | 2 max                | Unlimited             |
| Entry attachments (receipts)    | Yes                  | Yes                   |
| Book audit log                  | Yes                  | Yes                   |
| PDF / CSV export                | No                   | Yes                   |
| Book reports & charts           | No (blurred preview) | Yes                   |
| Recurring entries               | No                   | Yes                   |
| Email reports (weekly/monthly)  | No                   | Yes                   |
| Date range comparison           | No                   | Yes                   |
| Entry notes/comments            | No                   | Yes                   |
| **AI receipt OCR**              | No                   | Yes (200 scans/month) |
| **AI auto-categorization**      | No                   | Yes (unlimited)       |
| **AI cash flow insights**       | No                   | Yes                   |
| Support                         | Community            | Priority              |

### Pro Features Roadmap

Features listed in implementation priority order. All gated behind `auth()->user()->isPro()` — Free users see upgrade modal or blurred preview.

#### 1. Book-Level Reports & Charts (Pro)
Visual analytics tab on the book detail page (`/businesses/{business}/books/{book}`):
- **Cash flow trend chart** — weekly or monthly bars showing Cash In vs Cash Out over time (pure Alpine.js bar chart, no JS library)
- **Category breakdown** — pie/donut chart showing spending by category (top 5 + "Other")
- **Payment mode breakdown** — bar chart or list showing totals per payment mode
- **Period summary** — total in, total out, net, daily average, busiest day
- Free users see a blurred preview of the charts with an upgrade CTA overlay
- All calculations done in PHP (Eloquent aggregates), rendered in Blade, animated with Alpine
- Route: existing book detail page, new "Reports" tab or section below entries

#### 2. Recurring Entries (Pro)
Auto-create entries on a schedule:
- **Recurrence rule** per entry: frequency (daily/weekly/monthly/yearly), start date, optional end date
- **New table**: `recurring_entries` (id, book_id, type, amount, description, category, payment_mode, frequency, next_run_at, ends_at, is_active)
- **Scheduled command**: `php artisan entries:generate-recurring` runs daily via cron, creates entries for all due recurring rules
- UI: "Make Recurring" toggle in add/edit entry slide-over; separate "Recurring" tab on book detail showing all rules with enable/disable/edit/delete
- Free users see upgrade modal when toggling "Make Recurring"

#### 3. Entry Attachments — Receipts & Invoices (Free)
Upload a receipt photo or invoice PDF per entry:
- **New column**: `entries.attachment_path` (nullable string)
- File stored on `local` disk (or S3 in production) under `attachments/{business_id}/{book_id}/`
- Max file size: 2 MB, allowed types: PNG, JPG, PDF
- UI: file upload field in add/edit entry slide-over; thumbnail/icon preview in entry row; click to view full-size in modal
- Available to all users (free and Pro)

#### 4. Book Audit Log (Free)
Track who did what in each book:
- **New table**: `book_activity_log` (id, book_id, user_id, action, entry_id nullable, meta JSON, created_at)
- Actions logged: entry_created, entry_updated, entry_deleted, bulk_delete, bulk_move, bulk_copy, category_changed, payment_mode_changed
- UI: "Activity" tab on book detail page, chronological feed with user avatar, action description, timestamp
- Available to all users (free and Pro)

#### 5. Email Reports (Pro)
Automated summary emails:
- **Schedule**: weekly (every Monday) and/or monthly (1st of month)
- **Content**: period summary (total in/out/net), top categories, recent entries, book link
- **Settings**: per-book toggle in book settings, choose frequency, add recipient emails (defaults to owner)
- **New table**: `report_schedules` (id, book_id, frequency, recipients JSON, is_active, last_sent_at)
- Queued job via Laravel Mail, rendered as branded HTML email

#### 6. Date Range Filtering & Comparison (Pro)
Advanced filtering on the book detail page:
- **Date range picker**: custom start/end date (Free gets basic type/sort filters only)
- **Period comparison**: "Compare with previous period" toggle — shows side-by-side summary (this month vs last month)
- UI: date range inputs in filter bar, comparison summary card above entries

#### 7. Entry Notes/Comments (Pro)
Team collaboration on entries:
- **New table**: `entry_comments` (id, entry_id, user_id, body, created_at)
- UI: comment icon on entry row (shows count), click opens comment thread in slide-over
- Free users see upgrade modal when clicking comment icon

---

## AI Features Roadmap

All AI features use the **Claude API — claude-haiku-4-5** (vision + text). Cost per Pro user at typical usage: ~$0.002/OCR scan, ~$0.0004/categorization call. At 200 OCR scans/month the AI cost is ~$0.40/user — well within the $5/month margin.

**New table: `ai_usage_logs`** (id, user_id, type enum[ocr,categorize,insights,nlp], tokens_in, tokens_out, cost_usd DECIMAL(8,6), created_at) — audit trail + cost analytics for admin dashboard.

**Monthly OCR limit**: 200 scans/month per Pro user, tracked via count on `ai_usage_logs`. Resets on 1st of month. Free users see upgrade modal on "Scan Receipt" click.

### 1. Receipt OCR → Auto-fill Entry (Pro) ← BUILD NEXT
User uploads or photographs a receipt → AI reads it and auto-populates amount, date, description, and category instantly. Removes the #1 friction point: manual data entry.

- **Trigger**: "Scan Receipt" button in the Add Entry slide-over (Pro only; free → upgrade modal)
- **UX flow**:
  1. User clicks "Scan Receipt" → file picker opens (PNG/JPG/PDF, max 5MB)
  2. File uploads → button shows shimmer/scanning animation with pulsing AI badge
  3. Claude vision API call server-side → returns structured JSON (type, amount, date, description, category, payment_mode)
  4. Fields animate in with a typewriter/fade-fill effect — user sees them populate live
  5. Green "AI filled" badge on each auto-filled field; user can edit any field before saving
  6. If OCR confidence is low on a field, show it with amber "Review" badge instead of green
- **Backend**: `App\Livewire\Book\Show::scanReceipt()` — Livewire upload handler, calls `App\Services\AiService::extractReceipt($imagePath)`, returns structured array, maps to entry form properties
- **New service**: `app/Services/AiService.php` — wrapper around Claude API HTTP calls (receipt OCR, categorization, insights). Uses `ANTHROPIC_API_KEY` env var.
- **Prompt**: structured JSON extraction prompt with field list, confidence scoring, currency normalization
- **Attachment**: scanned file auto-attached to the entry (reuses existing attachment system)
- **Scan counter**: increments `ai_usage_logs` on each call; `scanReceipt()` checks count before proceeding

### 2. AI Auto-Categorization (Pro)
On description field blur, AI suggests the most likely category. One click to accept.

- **Trigger**: `wire:blur` on description input → Livewire `suggestCategory()` → Claude text API
- **UX**: subtle "AI suggests: [Category]" chip fades in below the field → click to apply → chip disappears
- **Prompt**: short, single-purpose — "Given this description, pick one category from this list. Return JSON: {category: string, confidence: float}"
- No new DB column — stateless per-request. Logs to `ai_usage_logs`.
- Free users: suggestion chip is hidden entirely (no modal, just silent)

### 3. AI Cash Flow Insights (Pro)
Plain-English 3-bullet insight card on the Reports tab. Generated on demand, cached 24h per book.

- **Trigger**: "Generate Insights" button on Reports tab, or auto-generated on first Reports tab open
- **UX**: shimmer card placeholder → insight card fades in with 3 bullet points + an overall sentiment badge (Healthy / Watch / Concern)
- **Content examples**: "Your Cash Out increased 34% vs last month, driven by Supplies." / "You have 3 recurring expenses totalling PKR 45,000." / "Your busiest day was March 15 — 8 transactions."
- **Backend**: `books.ai_insights_cache` (text) + `books.ai_insights_generated_at` (timestamp) — cache invalidated when new entries are added
- **Data sent to API**: aggregated numbers only (totals, counts, categories) — never raw descriptions or sensitive data
- Free users: blurred insight card with "Upgrade to see AI insights" CTA

### 4. Natural Language Entry (Pro) — Phase 2
User types "Paid 5000 for office rent yesterday" → AI parses into a fully populated entry form.

- Text input field at top of slide-over with placeholder "Describe a transaction..."
- Parses: type (in/out), amount, description, date (relative dates handled), category, payment mode
- Fields animate in below after parsing
- Falls back gracefully if parsing fails — shows raw text in description field

### 5. Anomaly Detection (Pro) — Phase 2
AI flags entries that are statistically unusual compared to historical averages.

- Runs asynchronously after entry save (queued job)
- Stores flag on `entries.is_flagged` (bool) + `entries.flag_reason` (string)
- Inline amber warning icon on flagged entry rows with tooltip: "This is 3× your average electricity bill"

### 6. Cash Flow Forecast (Pro) — Phase 2
Projects next 30-day cash in/out/net based on recurring entries + trailing 90-day averages.

- "Forecast" section on Reports tab below insights
- Shows projected balance as a single large number with confidence range
- Powered by simple statistical model + recurring entry schedule (no ML needed initially)

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
- Free users clicking Reports tab → show blurred preview with upgrade CTA overlay
- Free users toggling "Make Recurring" → show upgrade modal
- Entry attachments → available to all users (no Pro gate)
- Book audit log → available to all users (no Pro gate)
- Free users clicking date range/comparison → show upgrade modal
- Free users clicking comment icon → show upgrade modal
- Check plan limits in Livewire components using `auth()->user()->isPro()`

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
- [x] Mobile responsiveness — all pages audited and fixed (sidebar close button, z-index stacking, balance strip, landing page)
- [x] Admin panel (/admin/*) — Dashboard, Users, User Detail, Businesses, Subscriptions, Invitations, Profile
  - KPI strip, signups bar chart, top businesses, recent signups
  - Users list with search + plan filter, force pro/free, delete
  - User detail with subscription info, businesses, invitations sent, danger zone
  - Businesses list with search, members/books/entries counts
  - Subscriptions with MRR, active count, status filter
  - Invitations with resend/cancel actions
  - Admin profile: edit name, change password, change email with 6-digit OTP (Redis, 10min TTL)
  - Admin middleware, RedirectIfAdmin middleware (admins redirect away from app routes)
  - Sidebar with expandable user chip dropdown (Profile Settings + Sign Out)
- [x] PDF + CSV export (Pro only) — `ExportController`, dompdf for PDF, streamed CSV, Pro gate
- [x] Upgrade prompt modal — `resources/views/components/upgrade-modal.blade.php`, props: `feature` (business/team/export), `isOwner`, `businessName`, `dismissHref`; replaces 3 inline modals in create/settings/book-show
- [x] Appearance page (`/admin/appearance`) — `Admin\Appearance` Livewire component with 6 tabs (General, Logos, Colours, Typography, Landing Copy, Email Sender); `settings` table + `Setting` helper + `theme.css` generation + dynamic `config('app.name')` override
- [x] Dynamic logo system — `x-app-logo` component + all layouts (app, guest, landing) check for custom `brand/logo-dark.png`; when uploaded, shows logo only (no text, since logo is a wordmark); when absent, shows default SVG icon + app name text
- [x] Business switcher dropdown — sidebar dropdown in `app.blade.php` showing all user's businesses with current business highlighted, search filter (when > 4 businesses), and "Add New Business" link; enables quick switching without returning to dashboard
- [x] Bulk entry operations — select multiple entries via Alpine.js checkboxes (zero server round-trips for toggling), bulk actions toolbar (desktop top bar + mobile fixed bottom bar): Bulk Delete, Move Entries, Copy Entries, Copy Opposite (flip type), Change Category, Change Payment Mode; 4 modals (delete confirm, book picker, category picker, payment mode picker); auto-clear selection on completion; viewer role excluded
- [x] Admin businesses detail inline — expandable row in `/admin/businesses` showing members + books with chevron toggle, wire:click row expand
- [x] Admin dark/light theme toggle — `localStorage('cashflow_theme')` toggle in admin sidebar, defaults to dark
- [x] In-app announcement banner — admin sets message + type + expiry at `/admin/announcement`; dismissible banner on dashboard for all users; localStorage dismissal keyed by `updated_at`
- [x] Book-level reports & charts (Pro) — "Reports" tab on book detail page with period summary cards, cash flow trend chart (auto-groups daily/weekly/monthly), category breakdown (in/out toggle), payment mode breakdown; free users see blurred preview + upgrade CTA; respects all active filters; `buildReportData()` in `Book\Show`
- [x] Recurring entries (Pro) — "Repeat this entry" toggle in slide-over (frequency pills + optional end date), "Recurring" tab on book detail with manage/edit/pause/delete, recurring icon on linked entries, "Update future entries?" confirmation on edit (Google Calendar-style), `GenerateRecurringEntries` daily cron with catch-up loop + Pro check, auto-pause on downgrade (webhook + admin force-free), `recurring_entry_id` FK on entries with `nullOnDelete`
- [x] Entry attachments (Free) — file upload in slide-over (PNG/JPG/PDF, max 2MB), amber paperclip icon on entry rows (desktop + mobile) clickable to preview, preview modal with inline image or PDF open button, auth-protected serving via `ExportController@attachment`, private storage under `attachments/{business_id}/{book_id}/`, old files cleaned up on replace/delete, `x-cloak` fix for slide-over flash on page refresh
- [x] AI receipt OCR (Pro) — "Scan Receipt" button in slide-over; `app/Services/AiService.php` wrapper around Claude claude-haiku-4-5 vision API; shimmer scanning animation; typewriter field-fill effect; green "AI filled" / amber "Review" badges per field; auto-currency detection + live exchange rate conversion (Frankfurter API, cached 1h); scanned file auto-attached to entry; 200 scans/month limit + 5/minute burst rate limit via `RateLimiter`; usage logged to `ai_usage_logs`; `database/migrations/2026_03_17_000001_create_ai_usage_logs_table.php`
- [x] Enterprise security hardening — `guardEditor()` re-fetches role from DB on every write (prevents stale Livewire state); `validateBulkIds()` verifies all bulk IDs belong to current book; `SecurityHeaders` middleware (X-Frame-Options, X-Content-Type-Options, X-XSS-Protection, Referrer-Policy, Permissions-Policy, HSTS in production); MIME whitelist in `ExportController@attachment`; double file validation (`mimes` + `mimetypes`); OCR + invitation rate limiting; AI JSON field whitelist (`array_intersect_key`); currency code regex whitelist; sanitized error logging; removed `book_id` from `Entry::$fillable`

### Pending App Features (Priority Order)

#### Free Tier
- [ ] **Book audit log** — `book_activity_log` table (book_id, user_id, action, entry_id nullable, meta JSON); actions: entry_created/updated/deleted, bulk_delete/move/copy, category_changed, payment_mode_changed; "Activity" tab on book detail page with chronological feed, user name, action description, timestamp

#### Pro Tier
- [ ] **Email reports** — `report_schedules` table (book_id, frequency: weekly/monthly, recipients JSON, is_active, last_sent_at); per-book toggle in book settings; queued Laravel Mail job; branded HTML email with period summary, top categories, recent entries
- [ ] **Date range filtering & comparison** — custom start/end date picker in filter bar (Pro only); "Compare with previous period" toggle showing side-by-side summary card; free users see upgrade modal on date range inputs
- [ ] **Entry notes/comments** — `entry_comments` table (entry_id, user_id, body, created_at); comment icon on entry rows showing count; thread opens in slide-over; free users see upgrade modal

#### AI Features (Pro — ship with $5/month price update)
- [x] **AI receipt OCR** — built and live. Claude claude-haiku-4-5 vision API, currency auto-detection + conversion, 200/month + 5/min rate limits, `ai_usage_logs` table, `AiService.php`
- [ ] **AI auto-categorization** — `wire:blur` on description → Claude text API → "AI suggests: [Category]" chip → one-click accept; logs to `ai_usage_logs`
- [ ] **AI cash flow insights** — "Generate Insights" button on Reports tab; 3-bullet plain-English card with sentiment badge; cached in `books.ai_insights_cache` (24h); data sent = aggregates only, never raw descriptions

#### AI Features — Phase 2
- [ ] **Natural language entry** — free-text field in slide-over: "Paid 5000 for rent yesterday" → AI parses into full entry form
- [ ] **Anomaly detection** — queued job after entry save; flags unusual entries with `entries.is_flagged` + `flag_reason`; amber badge on entry rows
- [ ] **Cash flow forecast** — 30-day projection on Reports tab using recurring entries + 90-day trailing averages

### Pending Admin Tasks

#### App Identity
- [x] **App name & tagline** (`/admin/appearance` → General tab) — editable fields: App Name, Tagline, Support Email, App URL; saved to `settings` table; overrides `config('app.name')` via `Config::set` in `AppServiceProvider::boot()`; touches: browser `<title>` tags in both layouts, navbar wordmark, landing page title + footer

#### Already Planned
- [x] **Impersonate user** — "Impersonate" button in User Detail danger zone; `session(['impersonating' => $user->id])`, re-auth as target user, store original admin ID to allow return; stop-impersonation banner in app layout
- [x] **Business detail inline** — clicking a row in `/admin/businesses` expands inline detail showing members + books with chevron toggle
- [x] **Last login tracking** — `last_login_at` timestamp on `users` table + middleware to update on auth; displayed in admin Users list
- [ ] **Subscriptions: month-over-month growth** — compare active sub count this month vs last month, show `+N` / `-N` indicator next to Active count on the Subscriptions page
- [x] **Appearance page** (`/admin/appearance`) — `Admin\Appearance` Livewire component with 6 tabs: General (app identity), Logos (dark/light/favicon upload), Colours (hex inputs → `theme.css`), Typography (font dropdowns → `theme.css`), Landing Copy (hero/sub/cta/footer), Email Sender (from name/address)

#### Revenue & Growth Intelligence
- [ ] **MRR trend chart** — line chart of MRR over the last 12 months on the Subscriptions page (pure Blade/Alpine bar chart, same technique as signups chart); shows revenue trajectory not just snapshot
- [ ] **Churn rate** — percentage of paying users who cancelled in the last 30 days; shown as a KPI on Dashboard alongside Churned (30d) count
- [ ] **Free-to-Pro conversion rate** — `(new Pro this month / new signups this month) × 100`; single KPI on Dashboard; the key growth lever to watch
- [ ] **Promo / coupon codes** — create Stripe coupons via admin UI (percent-off or amount-off, expiry date, max redemptions); list existing coupons with redemption count; apply a coupon to a specific user's next invoice

#### User Communication
- [ ] **Email broadcast** — compose and send a one-off email to a user segment (All / Free / Pro / specific user); uses Laravel Mail + queue; stores send history (subject, segment, sent_at, recipient count) in a `broadcasts` table
- [x] **In-app announcement banner** — `Admin\Announcement` component at `/admin/announcement`; message + type (info/warning/success) + optional expiry date stored as JSON in `settings` table; rendered as dismissible top banner in `app.blade.php`; dismissed state in `localStorage` keyed by `updated_at` (updating message resets all dismissals); activate/deactivate toggle, clear action, live preview in admin UI
- [ ] **User notes** — internal text notes on a user record (visible only in admin); stored in a `admin_notes` table (`user_id`, `body`, `created_by`, `created_at`); shown in User Detail below the profile card; useful for support context

#### Support & Debugging Tools
- [ ] **Stripe webhook event log** — log every incoming Stripe webhook to a `webhook_logs` table (`event_type`, `stripe_event_id`, `payload` JSON, `processed_at`, `status`); viewable in admin at `/admin/webhooks`; invaluable for debugging billing edge cases (double-fires, missed cancellations)
- [ ] **Failed jobs monitor** — `/admin/jobs` showing Laravel's `failed_jobs` table: job name, payload excerpt, failed_at, exception message; "Retry" and "Delete" actions; alerts admin when export or email jobs fail silently
- [ ] **Activity audit log** — record every admin action (force pro, delete user, impersonate, resend invite, etc.) to an `admin_audit_logs` table (`admin_id`, `action`, `target_type`, `target_id`, `meta` JSON, `created_at`); viewable at `/admin/audit`; accountability trail

#### Security & Access
- [ ] **Admin 2FA** — TOTP two-factor authentication for admin login (Laravel Fortify or manual TOTP); admin-only, not exposed to regular users; required before accessing any `/admin/*` route after login
- [ ] **Admin activity sessions** — show currently active admin sessions (IP, user-agent, last seen) in Profile Settings; "Revoke all other sessions" button

#### Data & Compliance
- [ ] **User data export (GDPR)** — "Export all data" button on User Detail generates a ZIP: user record, all businesses, all books, all entries as JSON + CSV; queued job, download link emailed to admin
- [ ] **Users CSV export** — export the current filtered user list (respects search + plan filter) as CSV; single button on `/admin/users`; streamed response, no queue needed
- [ ] **Retention cohort table** — weekly cohort table showing % of users still active (logged in) 1/2/4/8 weeks after signup; pure SQL, rendered as an HTML table; reveals whether the product is sticky

---

## Mobile App Roadmap

The web backend is already mobile-ready — Laravel Sanctum is installed and all business logic lives in the backend. The mobile app consumes a REST API layer built on top of the same database.

### Strategy: React Native (recommended)
One codebase → iOS + Android. Expo managed workflow keeps tooling simple.

### Phase 1 — API Layer (build before touching mobile)
Add `/api/v1/` routes in `routes/api.php` protected by `auth:sanctum`:

```
POST   /api/v1/auth/login              → issue Sanctum token
POST   /api/v1/auth/register
POST   /api/v1/auth/logout             → revoke token
GET    /api/v1/businesses              → list user's businesses
GET    /api/v1/businesses/{id}/books   → list books
GET    /api/v1/books/{id}/entries      → paginated entries + running balance
POST   /api/v1/books/{id}/entries      → create entry
PUT    /api/v1/entries/{id}            → update entry
DELETE /api/v1/entries/{id}            → delete entry
POST   /api/v1/entries/{id}/scan       → OCR receipt (same AiService)
GET    /api/v1/books/{id}/summary      → cash in / out / balance totals
```

All responses: JSON, camelCase keys, ISO 8601 dates, amounts as strings (not floats).

### Phase 2 — Mobile App (React Native + Expo)
Core screens mirror the web app:
- Dashboard — businesses list
- Book list — books for a business
- Ledger — entries with running balance, pull-to-refresh
- Add/Edit Entry — form with OCR "Scan Receipt" using device camera
- Reports — charts (Victory Native or Skia)
- Settings/Profile, Billing (Stripe mobile SDK)

### Security for Mobile API
- Tokens stored in `expo-secure-store` (iOS Keychain / Android Keystore) — never AsyncStorage
- Token rotation: refresh tokens with sliding expiry
- Certificate pinning for production builds
- Biometric authentication gate before app opens (FaceID / fingerprint)
- All API routes rate-limited via Laravel's `throttle:60,1` middleware
- CORS locked to app bundle IDs only (no wildcard)

### Estimated effort
| Phase | Effort | Notes |
|---|---|---|
| API layer (Laravel) | ~1 week | Routes, resources, tests |
| React Native app — core screens | ~3 weeks | Auth, dashboard, ledger, add entry |
| OCR + camera integration | ~3 days | Expo Camera + existing AiService |
| Charts + reports | ~1 week | Victory Native |
| Stripe mobile billing | ~2 days | Stripe React Native SDK |
| App Store submission | ~1 week | Review times, screenshots, metadata |
| **Total** | **~6–7 weeks** | One developer, focused |

### Prerequisites before starting mobile
1. All core web features complete (audit log, email reports, AI features)
2. API layer built and tested with Postman/Pest
3. Staging environment on Railway with separate `.env`
4. Apple Developer account ($99/year) + Google Play Console ($25 one-time)

---

## Session Notes (last updated 2026-03-17)

### Completed this session (2026-03-17)

- **AI receipt OCR (Pro)** — full implementation shipped and tested:
  - `app/Services/AiService.php` — Claude claude-haiku-4-5 vision API wrapper; `extractFromReceipt()` encodes image as base64, sends structured JSON extraction prompt, returns typed fields; `convertCurrency()` calls Frankfurter API (free, no key) with 1h Redis cache; `sanitise()` cleans + type-coerces all fields
  - `app/Models/AiUsageLog.php` — UUID model, `monthlyOcrCount()` static method for limit checking
  - `database/migrations/2026_03_17_000001_create_ai_usage_logs_table.php` — `type` enum (ocr/categorize/insights/nlp), token counts, cost_usd DECIMAL(8,6)
  - `app/Livewire/Book/Show.php` — `ocrFile` property, `WithFileUploads`, `updatedOcrFile()` method: Pro gate → rate limit → monthly limit → validate → upload temp → call AiService → fill form fields with typewriter effect → currency convert if needed → log usage → dispatch `ocr-complete`; `clearOcr()` reset method
  - `resources/views/livewire/book/show.blade.php` — "Scan Receipt" button (Pro only, free → upgrade modal) above amount field; shimmer scanning state with pulsing AI badge; green "AI filled" / amber "Review" per-field badges on amount/date/description/category/payment_mode; currency conversion note ("Converted from USD 5.00 at rate 278.50"); scan error dismissible banner; OCR file input hidden, triggered programmatically

- **AI cost analysis & pricing decision**:
  - Claude claude-haiku-4-5: ~$0.002/OCR scan, ~$0.0004/categorization call
  - At 100 OCR + 100 categorization calls/month: ~$0.24/user — 8% of $3 revenue
  - Decision: raise to **$5/month** when AI features fully ship — still 3× cheaper than any competitor; gross margin ~83%
  - 200 OCR scans/month hard limit per Pro user (abuse protection, not cost concern at Haiku prices)

- **Enterprise security hardening** — full audit + fixes across 6 files:
  - `app/Models/Entry.php` — removed `book_id` from `$fillable` (mass assignment protection; always created via `$book->entries()->create()`)
  - `app/Services/AiService.php` — `array_intersect_key` whitelist on AI JSON response; currency code regex whitelist `^[A-Z]{3}$`; sanitized error logging (message only, not full response body)
  - `app/Http/Controllers/ExportController.php` — MIME whitelist before serving attachments; added `X-Content-Type-Options: nosniff` + `Content-Security-Policy: default-src 'none'` headers on file responses
  - `app/Livewire/Book/Show.php` — `guardEditor()` private method re-fetches role from DB on every write call (prevents stale Livewire session role exploit); `validateBulkIds()` strips any IDs not belonging to current book; `mimetypes:` validator added to uploads alongside `mimes:`; OCR 5/minute burst rate limit via `RateLimiter`
  - `app/Livewire/Business/Settings.php` — invitation rate limit: 5/hour per user via `RateLimiter`
  - `app/Http/Middleware/SecurityHeaders.php` — new middleware: `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, `X-XSS-Protection: 1; mode=block`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy: camera=(), microphone=(), geolocation=()`, `Strict-Transport-Security` (production only)
  - `bootstrap/app.php` — `SecurityHeaders` registered globally via `$middleware->append()`

- **Product decisions**: Entry attachments and Book Audit Log moved to Free tier (both are trust/accountability features, not luxury Pro features). Pro plan will raise from $3 → $5/month when AI features ship. Decision rationale: attachments are core to cash flow tracking (replaces paper/WhatsApp), audit log makes team collaboration safe — neither justifies a Pro gate.
- **AI roadmap defined**: Receipt OCR ✅, AI auto-categorization (next), AI cash flow insights, NLP entry (phase 2), anomaly detection (phase 2), forecast (phase 2). 200 OCR scans/month limit for abuse protection. See AI Features Roadmap section.

### Completed this session (2026-03-16 latest)
- **Entry attachments (Free)** — upload receipts/invoices per entry:
  - `database/migrations/2026_03_16_400001_add_attachment_path_to_entries_table.php` — nullable string column
  - `app/Models/Entry.php` — added `attachment_path` to fillable
  - `app/Livewire/Book/Show.php` — `WithFileUploads` trait, 6 new properties (`entryAttachment`, `existingAttachmentPath`, `removeAttachment`, `showAttachmentPreview`, `previewAttachmentPath/Name`, `previewEntryId`), 4 new methods (`removeExistingAttachment`, `clearNewAttachment`, `openAttachmentPreview`, `closeAttachmentPreview`); `doSaveEntry()` handles upload/replace/remove with validation (max 2MB, PNG/JPG/PDF); `deleteEntry()` cleans up attachment file from disk
  - `app/Http/Controllers/ExportController.php` — `attachment()` method serving files from private storage with auth check (no Pro gate); `authorise()` refactored with `$requirePro` parameter
  - `routes/web.php` — `GET /businesses/{business}/books/{book}/entries/{entry}/attachment` route
  - `resources/views/livewire/book/show.blade.php` — dashed file drop zone in slide-over (after Reference, before Recurring), green success state with filename after upload, existing attachment display with remove button when editing; amber paperclip icon on entry rows (desktop + mobile) clickable to open preview modal; preview modal with inline image display or PDF placeholder + "Open PDF" button + "Open in new tab" header action
  - `resources/views/layouts/app.blade.php` — added `[x-cloak] { display: none !important; }` CSS rule + `x-cloak` on slide-over backdrop/panel to fix flash on page refresh
  - Files stored privately under `storage/app/private/attachments/{business_id}/{book_id}/`, served only through auth-checked controller route
  - Subscription table updated: attachments + audit log moved from Pro to Free tier

- **Recurring entries (Pro)** — full implementation:
  - `database/migrations/2026_03_16_300001_create_recurring_entries_table.php` — schema with composite index on `(is_active, next_run_at)`
  - `database/migrations/2026_03_16_300002_add_recurring_entry_id_to_entries_table.php` — nullable UUID FK with `nullOnDelete`
  - `app/Models/RecurringEntry.php` — UUID model, `advanceNextRun()` (daily/weekly/monthly/yearly), `book()` + `entries()` relationships
  - `app/Models/Entry.php` — added `recurring_entry_id` to fillable, `recurringEntry()` BelongsTo
  - `app/Models/Book.php` — added `recurringEntries()` HasMany
  - `app/Console/Commands/GenerateRecurringEntries.php` — daily cron with catch-up while-loop, Pro check via eager-loaded `book.business.owner`, sets `recurring_entry_id` on generated entries, deactivates past end date
  - `routes/console.php` — `Schedule::command('entries:generate-recurring')->daily()`
  - `app/Livewire/Book/Show.php` — 11 new properties, 7 new methods: `enableRecurring()` (Pro gate), `toggleRecurring()`, `deleteRecurring()`, `openEditRecurring()`, `updateRecurring()`, `closeEditRecurring()`, `applyToRecurring()`, `skipRecurringUpdate()`; `doSaveEntry()` return type changed to `?\App\Models\Entry`; `saveEntry()` creates RecurringEntry + links initial entry on new entries, shows "Update future entries?" on edit of linked entries (Pro only)
  - `resources/views/livewire/book/show.blade.php` — Recurring tab button with Pro badge, recurring toggle in slide-over (frequency pills + end date), Recurring tab content (cards with type dot, amount, frequency badge, next run, edit/pause/delete), free user blurred preview, recurring icon (blue circular arrows SVG) on entry rows (desktop + mobile), "Update Recurring Entry?" confirmation modal, edit recurring modal with full form
  - `app/Providers/AppServiceProvider.php` — on subscription downgrade, pauses all recurring entries for user's owned businesses
  - `app/Livewire/Admin/UserDetail.php` + `app/Livewire/Admin/Users.php` — `forceFree()` now pauses recurring entries on admin force-free
  - Blade tab structure refactored from `@if/@else` to `@if/@elseif/@elseif/@endif` for 3-tab support
  - Edge cases: free user clicks toggle → upgrade modal; viewer role → toggle hidden; missed runs → catch-up loop; book deleted → cascade; downgrade → auto-pause all recurring entries

- **Book-level reports & charts (Pro)**
  - `app/Livewire/Book/Show.php` — `$activeTab` property, `buildReportData()` method (~100 lines): period summary (totals, counts, daily avg), trend chart (auto-groups daily/weekly/monthly with gap-filling), category breakdown (top 5 + Other + Uncategorized per type), payment mode breakdown; conditionally computed only when Reports tab active + user is Pro
  - `resources/views/livewire/book/show.blade.php` — ~350 new lines: pill-style tab toggle (Entries/Reports with Pro badge), period summary 4-card grid, grouped bar chart (emerald in + red out) with hover tooltips, category horizontal bars with in/out Alpine toggle, payment mode horizontal bars, free user blurred preview with fake chart data + upgrade overlay CTA
  - Reports respect all active filters (type, date range, category, payment mode, search)
  - Trend chart auto-detects grouping: <60 days → daily, <180 → weekly, else → monthly
  - Edge cases: <3 entries → "Add more entries" instead of trend chart; empty categories/modes → centered empty state

- **In-app announcement banner**
  - `app/Livewire/Admin/Announcement.php` — save/toggleActive/clear methods, stores JSON in `settings` table via `Setting::set('announcement', ...)`
  - `resources/views/livewire/admin/announcement.blade.php` — form (message textarea, type radio cards with icons, datetime-local expiry), activate/deactivate/clear buttons, live preview panel, "How it works" info card
  - `resources/views/layouts/admin.blade.php` — nav link added under Settings section
  - `resources/views/layouts/app.blade.php` — banner between impersonation bar and `<main>`; reads `Setting::get('announcement')`, checks `is_active` + expiry; Alpine `x-data` with `localStorage` dismissal keyed by `updated_at` (updating message resets dismissals); slide-down transition; type-based styling (info=blue, warning=amber, success=emerald); X dismiss button
  - `routes/web.php` — `GET /admin/announcement` route

- **Bulk entry operations** — full implementation in `Book\Show` component:
  - Alpine.js selection state (selectedIds, filteredIds, toggleEntry, toggleSelectAll, syncSelectAll) — zero server round-trips for checkbox toggling
  - Desktop bulk toolbar: appears between filter bar and balance strip with Select All + count, Delete, "Move or Copy" dropdown (Move/Copy/Copy Opposite), "Change Fields" dropdown (Category/Payment Mode), clear X
  - Mobile bulk toolbar: fixed bottom bar with compact scrollable action buttons
  - 4 modals: Bulk Delete confirmation (red warning), Book Picker (radio-selectable, excludes current), Change Category (radio list + "None"), Change Payment Mode (same pattern)
  - 7 new Livewire methods: `bulkDelete`, `openBulkBookPicker`, `executeBulkBookAction`, `bulkMoveEntries`, `bulkCopyEntries`, `bulkCopyOppositeEntries`, `bulkChangeCategory`, `bulkChangePaymentMode`
  - All methods: guard viewer role, scope to `$this->book->entries()`, call `$this->book->touch()`, dispatch `bulk-operation-complete`
  - Success banner: auto-dismissing emerald banner with 3-second timeout
  - Grid updated from 7→8 columns (36px checkbox column for non-viewers)
  - `filteredIds` injected from Blade PHP into Alpine via `x-init`, selection pruned on filter change
- **Admin businesses styling fixes** — removed opacity modifiers from border/divide classes, added `dark:divide-slate-800` to safelist, fixed chevron toggle (two SVG paths instead of CSS rotate)
- **Admin dark/light theme toggle restored** — `localStorage('cashflow_theme')` toggle accidentally removed in prior session, restored on `<html>` tag in `admin.blade.php`

### Previously completed (2026-03-16 cont.)
- **Dynamic logo system** — `x-app-logo` component now checks for `brand/logo-dark.png`; if custom logo exists, shows only the image (wordmark); if not, shows default SVG icon + text. Applied to: app layout sidebar, app layout mobile bar, admin layout sidebar, landing navbar + footer, guest layout (login/register) desktop + mobile logos. All logos also use `config('app.name')` for alt text and dynamic app name.
- **Business switcher dropdown** — added to sidebar in `app.blade.php` between logo and nav. Shows all user's businesses (owned + shared), highlights current business, Alpine search filter (> 4 businesses), "Add New Business" link to `/businesses/create`. Detects current business from route parameter.
- **Appearance page** (`/admin/appearance`) — `Admin\Appearance` Livewire component:
  - 6 tabs: General, Logos, Colours, Typography, Landing Copy, Email Sender
  - `settings` key-value table + `App\Helpers\Setting` static helper (cache-backed)
  - `theme.css` generation with RGB channel CSS variables for Tailwind opacity modifier support
  - Dynamic Google Fonts URL generation + storage in settings
  - Runtime `Config::set` overrides for app name, mail from name/address in `AppServiceProvider::boot()`
  - Logo uploads to `brand` filesystem disk, cache-busted via filemtime
  - Recommended logo dimensions: 1200×400 (dark/light), 512×512 (favicon)

### Previously completed (2026-03-16)
- **Admin panel — full build** (`/admin/*`)
  - `app/Livewire/Admin/`: Dashboard, Users, UserDetail, Businesses, Subscriptions, Invitations, Profile
  - `resources/views/livewire/admin/`: all 6 list/detail views + profile page
  - `resources/views/layouts/admin.blade.php` — dark sidebar, expandable user chip (Profile Settings + Sign Out)
  - `app/Http/Middleware/AdminMiddleware.php` — 403 gate
  - `app/Http/Middleware/RedirectIfAdmin.php` — redirects admins away from app routes to `/admin`
  - `bootstrap/app.php` — registered `admin` + `redirect_admin` middleware aliases
  - `routes/web.php` — `redirect_admin` on all app routes; `/admin/*` group with all 6 pages + profile
  - `app/Mail/AdminEmailVerification.php` + `resources/views/emails/admin-email-verification.blade.php` — OTP email for email change
  - Dark mode border fix: removed `dark:bg-dark` (custom color overridden by @tailwindcss/forms), switched to `dark:bg-slate-900`/`dark:bg-slate-800`; removed opacity modifiers (`/60`) from `divide-` and `border-` classes that JIT wouldn't compile

- **Mobile responsiveness — full audit and fix across all pages** (commit `94abfbd`)
  - `resources/views/layouts/app.blade.php`
    - Added X close button inside sidebar (`lg:hidden`) — users can now close the mobile sidebar
    - Raised overlay z-index `z-20 → z-40`, sidebar `z-30 → z-50` (page sticky headers use `z-10`/`z-30`, so overlay now sits above them)
  - `resources/views/layouts/guest.blade.php`
    - Testimonial spacing: removed `justify-between` from left panel, added `mt-16` to middle content, `mt-auto` to testimonial card — anchors to bottom regardless of viewport height
  - `resources/views/livewire/book/show.blade.php`
    - Balance strip: icons `hidden sm:flex`, padding `px-3 py-3 sm:px-5 sm:py-4`, amount `text-base sm:text-xl`, label `text-[10px] sm:text-xs` — all 3 columns fit on 375px screens
  - `resources/views/landing.blade.php`
    - Navbar: Alpine `x-data="{ mobileMenu: false }"` + hamburger/X toggle + slide-down mobile drawer with all nav links
    - Hero: `py-16 sm:py-24`, H1 `text-4xl sm:text-5xl lg:text-[3.75rem]`, CTA `flex-col sm:flex-row`
    - Stats: `grid grid-cols-2 sm:flex sm:flex-wrap` — `hidden sm:block` dividers prevent float artifacts on mobile
    - All sections: `py-20 md:py-32` halves vertical padding on mobile

### Previously completed (2026-03-14)
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
- **Mobile z-index stack:** page sticky headers `z-10`/`z-30`, overlay `z-40`, sidebar `z-50` — never go below `z-40` for overlay
- **Mobile balance strip:** always use `hidden sm:flex` on decorative icons, `text-[10px] sm:text-xs` for labels — `divide-x` stays (design requirement)
- **Mobile stats grid:** `grid grid-cols-2 sm:flex sm:flex-wrap` + `hidden sm:block` on `w-px` dividers to avoid dividers floating solo on mobile
- **`mt-auto` pattern:** in a flex-column container, `mt-auto` on the last child pushes it to the bottom without `justify-between` (which breaks when there are 3+ children)
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
- Dark navy theme by default (same as app), with light/dark mode toggle in sidebar
- Theme toggle uses `localStorage('cashflow_theme')` — persists across sessions, defaults to dark
- Breadcrumb trail on every page

### Implementation Notes
- All admin Livewire components in `app/Livewire/Admin/`
- All admin views in `resources/views/livewire/admin/`
- Use `Livewire\WithPagination` on list components
- Impersonate: set `session(['impersonating' => $user->id])`, re-auth as that user, store original admin ID to return
- No soft deletes — hard delete with cascading (already handled by DB constraints)
- Never expose raw Stripe secret keys or webhook secrets in admin UI
