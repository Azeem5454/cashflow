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
- [x] AI auto-categorization (Pro) — `wire:blur` on description field → `suggestCategory()` in `Book\Show` → `AiService::suggestCategory()` Claude Haiku text API call → "AI suggests: [Category]" violet chip fades in below field → one-click accept (`applyAiCategory()`) or dismiss; silent fail on error; logs to `ai_usage_logs` with type 'categorize'; chip hidden for free users and when category already set; chip reset on `openAddEntry()`
- [x] UX polish — global action toast: `wire:ignore` div always in DOM, shown via Livewire `dispatch('entry-saved', message: '...')` + Alpine `x-on:entry-saved.window`; covers all actions: add entry, edit entry, save & add new, delete entry, all 6 bulk operations; replaced old inline `bulkSuccessMessage` banner; delete entry replaced from inline "Yes/No" row confirm to full modal (warning banner + entry details card + Yes Delete / Cancel buttons) matching reference design; Pro badges hidden for Pro users on Reports tab, Recurring tab, Export menu, recurring toggle
- [x] AI cash flow insights (Pro) — auto-triggers on Reports tab open; `AiService::generateInsights()` with aggregated data only; sentiment badge (Healthy/Watch/Concern) + 3 bullets + tip; 24h cache; cross-book comparison with previous period; 1/min burst + 10/day cap; all UI states dark/light mode; `database/migrations/2026_03_18_000001_add_ai_insights_to_books_table.php`
- [x] Book audit log (Free) — `book_activity_log` table; "Activity" tab on book detail; chronological feed with avatar, action dot, description, relative timestamp; lazy-loaded (limit 100); 8 log points across all entry + bulk actions
- [x] Book modal redesign — Create Book popup with Flatpickr period picker + 4 preset tiles (This Month / Last Month / This Quarter / This Year), opening balance field, collapsible description; Edit Book popup (pre-filled); Duplicate Book popup (keep categories / payment methods / entries toggles + period for new book); edit/duplicate icons revealed on book row hover; `bookPeriodPicker()` Alpine factory (no `$wire` calls during interaction, dates passed as args on submit); period presets use Alpine object-syntax `:class` for reliable highlighting
- [x] Entry creator attribution — `created_by` UUID FK on `entries` (nullable, `nullOnDelete`); migration `2026_03_20_100001_add_created_by_to_entries_table.php`; `doSaveEntry()` stamps `auth()->id()` on new entries; `Book\Show::render()` eager-loads `creator`; "by You" / "by [Name]" shown in muted text under description on both desktop and mobile entry rows
- [x] Dark mode flash fix + theme polish — `theme-transition` CSS class temporarily enables `transition: background-color/color/border-color 200ms` on ALL elements during toggle (class added before + removed after via `setTimeout(300)`); dark mode toggle button icons use CSS `dark:hidden`/`dark:block` instead of Alpine `x-show` (eliminates Alpine-driven DOM mutation); toggle pill uses `dark:bg-primary`/`dark:translate-x-4` CSS instead of Alpine `:class`
- [x] UX polish II — comment icon hover behavior (always visible with count when comments exist, hover-reveal when none); upgrade modal migrated to string-property pattern with feature-specific gold copy for all 6 features; tab reorder (Entries | Activity | Reports | Recurring); activity log extended with comment/attachment/recurring events; delete comment confirmation modal; flash messages on all actions; notification bell full-width sidebar row (`sidebar` prop); entry ordering stability (three-level sort: date → created_at → id); book detail "Rename Book" + "Duplicate Book" upgraded to full period-picker modals matching All Books page

### Pending App Features (Priority Order)

#### Pro Tier
- [x] **Email reports** — `report_schedules` table (book_id, frequency: weekly/monthly, recipients JSON, is_active, last_sent_at); "Email Reports" option in book settings gear dropdown; modal with enable/disable toggle, frequency pills (Weekly/Monthly), comma-separated recipients (max 10, validated); `BookEmailReport` mailable with branded custom HTML template (dark-luxe design: summary cards, category progress bars, entry list, CTA button); shared email layout partial (`emails.partials.layout`) with dynamic logo + app name; `SendEmailReports` artisan command (`reports:send`) scheduled daily at 09:00 UTC; `ReportSchedule::isDue()` + `buildReportData()` on model; "Send Test" button in modal (queues one email to current user); "Last sent" timestamp display; auto-sends first report immediately on enable; report schedules paused on Pro downgrade (webhook + admin force-free); cleaned up on book delete; Pro gate with `emailreports` upgrade modal feature
- [x] **Date range filtering & comparison** — Flatpickr date pickers in custom date modal (Pro gate); two comparison modes (Previous Period / Same Period Last Year); 3-row comparison card between filter bar and balance strip with ↑↓% change badges; `buildComparisonData()` in `Book\Show`; `upgradeModalFeature = 'daterange'` gate
- [x] **Entry notes/comments** — `entry_comments` table (entry_id, user_id, body, created_at); comment icon on entry rows showing count; thread opens in slide-over; delete confirmation modal; activity log integration; free users see upgrade modal

#### AI Features (Pro — ship with $5/month price update)
- [x] **AI receipt OCR** — built and live. Claude claude-haiku-4-5 vision API, currency auto-detection + conversion, 200/month + 5/min rate limits, `ai_usage_logs` table, `AiService.php`
- [x] **AI auto-categorization** — built and live. `wire:blur` on description → `AiService::suggestCategory()` → violet "AI suggests" chip → one-click accept; silent fail; logs to `ai_usage_logs`
- [x] **AI cash flow insights** — auto-triggers on Reports tab open; `AiService::generateInsights()` sends aggregated data only; 3-bullet card with sentiment badge (Healthy/Watch/Concern) + tip; 24h cache in `books.ai_insights_cache`; cross-book comparison with previous period; 1/min burst + 10/day cap per user; all UI states (shimmer, loaded, failed, not_enough_data, limit); dark/light mode; regenerate button; stale cache shown when limit hit

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

### Phase 1 — API Layer — ✅ DONE (60 endpoints live on Railway)
`routes/api.php` exposes `/api/v1/` protected by `auth:sanctum`. Full list via `php artisan route:list --path=api/v1`. Controllers: `AuthController`, `BusinessController`, `BookController`, `EntryController`, `SettingsController`.

Groups:
- **Auth+Profile**: register/login/logout/forgot-password, user, profile (PUT/DELETE), profile/password, auth/email/resend
- **Businesses**: index/show/store/update/destroy, books, createBook, members, invitations (list/invite/cancel), members role/remove
- **Books**: show/update/destroy/duplicate, recent, entries, summary, categories, payment-modes, activity, recurring, insights, report-data, report-schedule (CRUD), suggest-category, export
- **Entries**: store, update, destroy, bulk-delete, bulk-update, bulk-move, comments (list/add/delete), attachment (upload/get/delete), scan
- **Recurring**: toggle, delete
- **Settings**: billing/checkout-url, announcement, notifications (list/mark-all-read/delete)

All responses: JSON, camelCase keys, ISO 8601 dates, amounts as strings (not floats).

### Phase 2 — Mobile App (React Native + Expo) — ⚠️ ~90% DONE
Core screens implemented and live on Expo Go. Stack: Expo SDK 54, expo-router v6, React Native 0.81, TypeScript strict. Font: Bricolage Grotesque + Plus Jakarta Sans + Outfit + Geist Mono. Theme: dark-luxe navy (default) + light mode toggle in profile.

**Implemented screens** (`project-mobile/app/`):
- `(auth)/login.tsx`, `(auth)/register.tsx`, `(auth)/forgot-password.tsx` — Breeze-equivalent auth, toast feedback, error sanitization
- `(app)/index.tsx` — Dashboard with owned/shared split, recently-edited books, notifications bell, announcement banner, skeleton loaders, empty state CTA
- `(app)/profile/index.tsx` + `profile/edit.tsx` — Profile view with actions (Edit Profile / Billing / Dark Mode toggle) + edit form (name/email/password)
- `(app)/billing.tsx` — WebView wrapping `/settings/billing` for Stripe Checkout (auto-detects `?checkout=success`)
- `(app)/business/create.tsx` — Create Business with 24-currency searchable picker
- `(app)/business/[id].tsx` — Business detail with books list + totals strip + search/sort + Rename/Manage Team/Delete menu (owner only) + FAB
- `(app)/business/members.tsx` — Team management with invite FAB (email + role), pending invitations, change role, remove member
- `(app)/business/create-book.tsx` — Create book with period presets + Geist Mono opening balance
- `(app)/business/book/[id].tsx` — Ledger with 4 tabs (Entries / Activity / Reports / Recurring), balance strip, type filter, duration filter (All/Today/Yesterday/7d/30d/Custom), long-press bulk select (Delete/Move/Copy/Flip), comments panel, AI insights, charts
- `(app)/business/book/entry.tsx` — Add/Edit entry with AI receipt scan (camera or gallery), AI auto-categorization, native date picker, receipt attachment (edit mode)
- `(app)/business/book/edit.tsx` — Edit/Duplicate Book (mode param), period presets, carry-over toggles
- `(app)/business/book/email-reports.tsx` — Weekly/Monthly email reports settings with recipients

**Components** (`project-mobile/src/components/`):
- `Toast.tsx` — slide-up toast with success/error/info + haptics
- `DatePickerInput.tsx` — cross-platform (Android inline, iOS modal spinner)
- `DrawerMenu.tsx` — left drawer with business switcher + nav
- `NotificationsBell.tsx` — header bell with unread badge + slide-in panel
- `AnnouncementBanner.tsx` — dismissible banner with SecureStore persistence
- `CommentsPanel.tsx` — Modal + KeyboardAvoidingView (fixed iOS keyboard bug)
- `ActivityFeed.tsx`, `RecurringTab.tsx`, `AiInsightsCard.tsx`, `ReportsTab.tsx` — tab content
- `PickerDropdown.tsx` — category/payment mode picker with "Add New" inline
- `Skeleton.tsx` — animated shimmer placeholders (SkeletonCard, SkeletonEntryRow)

**Context/utils**:
- `src/context/AuthContext.tsx` — Sanctum token in expo-secure-store, biometric gate on launch, retry flow, 401 global handler
- `src/context/ThemeContext.tsx` — dark/light mode with SecureStore persistence + `useThemedStyles(makeStyles)` hook
- `src/api/client.ts` — axios instance, base URL from `app.json` extra, 30s timeout, auth interceptors
- `src/utils/errors.ts` — `errorMessage()` safe message extraction

**Key patterns**:
- Bulk operations: long-press entry → selection mode → bottom toolbar
- Receipt OCR: camera/gallery → upload → shimmer → typewriter field fill
- Navigation: expo-router Stack, `headerBackButtonDisplayMode: 'minimal'` at `(app)/_layout.tsx` to hide iOS back label
- Theme system: `const styles = useThemedStyles(makeStyles)` pattern (profile/dashboard/business detail/ledger/entry form converted; others pending)

**Not yet done**:
- Auth screens still dark-only (light mode not applied)
- ~10 secondary screens still use static `colors` import
- Offline SQLite cache
- Push notifications
- Certificate pinning (requires ejecting from Expo managed)

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

## Session Notes (last updated 2026-04-16)

### Completed this session (2026-04-15 → 2026-04-16) — Launch-prep: rebrand, security, social preview, OAuth, observability

**Rebrand CashFlow → TheCashFox**
- All 23 hardcoded `'CashFlow'` fallback literals across controllers/mailers/notifications/views replaced with `'TheCashFox'`. `config('app.name')` is live-editable via `/admin/appearance` and preloaded into `config()` via `AppServiceProvider::boot()`; fallbacks only apply on first boot before APP_NAME env is set.

**DB-backed brand assets — logos now survive Railway redeploys**
- New `uploaded_assets` table (key/mime/data/size/updated_at). `data` column is longText holding base64-encoded bytes — avoids Postgres bytea UTF-8 driver issues, portable across Postgres/MySQL/SQLite.
- `App\Models\UploadedAsset::has()/cacheBuster()/meta()/put()/forgetKey()/payload()`. Metadata + payload cached separately so hot pages pay one Redis round-trip, never a DB query.
- `App\Http\Controllers\BrandAssetController` + `GET /brand-asset/{key}` with strict key allow-list (`logo-dark`, `logo-light`, `favicon`, `og-image`), immutable long-cache, `X-Content-Type-Options: nosniff`.
- Migration backfills any existing `public/brand/*.png` on first run.
- `Admin\Appearance::uploadLogo*` writes to DB via `UploadedAsset::put`; revert deletes. All Blade consumers (`x-app-logo`, landing nav + footer, guest layout desktop/mobile, verify-email, email partials, error layout, admin previews, favicon `<link>` tags) read from the DB-backed route.

**Security hardening**
- `User::$fillable` removed `plan` + `is_admin`; all call sites converted to explicit `$user->plan = ...; $user->save()` (AuthController register, AppServiceProvider webhook, Billing::subscribe/resume, Admin\UserDetail force-pro/free, Admin\Users force-pro/free).
- Admin `forceFree()` now `cancelNow()`s the Stripe subscription BEFORE downgrading — stops billing cancelled users.
- Stripe error messages sanitised — no more `$e->getMessage()` to users; full error still logged.
- Sanctum token expiration: default 30 days (`SANCTUM_EXPIRATION=43200`, env-configurable).
- `AiService` prompts JSON-encode user-controlled strings (book names, periods, category names, descriptions) — neutralises prompt injection via category like `"rent\n\nIGNORE..."`.
- `guardEditor()` re-fetches role from DB on every write (prevents stale Livewire session state).
- `validateBulkIds()` strips IDs not belonging to current book.
- `SecurityHeaders` middleware (X-Frame-Options DENY, nosniff, XSS, Referrer-Policy, Permissions-Policy, HSTS in production).
- MIME whitelist on attachment serving + `X-Content-Type-Options: nosniff` on file responses.
- OCR 5/min, login 10/min, export 10/min, invitation 5/hr, social-callback 10/min rate limits.

**Per-page SEO + social preview coverage (every public route)**
- `layouts/guest.blade.php`: `match()` on `request()->routeIs(...)` → distinct titles/descriptions for login, register, password.request, password.reset, invitations.accept; `shouldNoindex` for tokenised routes.
- All public pages (landing, auth, legal) emit full OG (og:type, og:site_name, og:title, og:description, og:url, og:image 1200×630, og:image:alt, og:locale) + Twitter (summary_large_image) + canonical + theme-color + author + format-detection: telephone=no.
- Landing JSON-LD: `SoftwareApplication` (with AggregateRating + pricing Offers), standalone `Organization`, `FAQPage` mirroring on-page accordion. All escaped via `@verbatim` to avoid Blade 11 `@context`/`@type` directive collision.
- Legal layout: `BreadcrumbList` schema (Home › Current).
- Dynamic `/sitemap.xml` route emits `/, /login, /register, /terms, /privacy` with lastmod + priority.
- `robots.txt` disallows admin, api, dashboard, businesses, settings, profile, tokenised auth flows, brand-asset, invitations. Adds `Sitemap:` directive.

**CRITICAL Blade pitfall discovered + documented**
- `{{-- ... @verbatim ... --}}` — the word `@verbatim` (or any real directive name) inside a Blade comment IS picked up by Blade's directive scanner BEFORE comment stripping, opening an unclosed verbatim block that silently swallows everything below. This broke landing: `@vite`, Alpine CDN, GA4 conditional, and JSON-LD blocks all disappeared. Symptom: page renders without Tailwind (only inline `<style>` works), no SPA JS, no analytics, no schema. Fix: never write `@directiveName` as prose inside a Blade template — use `at-verbatim` or `&#64;verbatim` or wrap in `<code>`.
- Same gotcha fixed earlier in this repo: Laravel 11 added `@context` Blade directive which collided with JSON-LD `"@context"` key — escape with `@verbatim` blocks around the literal JSON.

**Vite build artifacts committed to git**
- `/public/build` un-gitignored; run `npm run build` before every commit touching Blade classes / CSS / JS. Railway's Railpack doesn't reliably build Node assets for PHP-declared projects. Committing the build artifacts guarantees styled pages regardless of what the builder does.
- Comment in `.gitignore` documents the workflow rule.

**Google OAuth (Laravel Socialite)**
- `GET /auth/google/{redirect,callback}` via `guest` middleware group.
- `SocialAuthController` matches users on email (Google emails are verified), creates with `plan=free` + `email_verified_at=now()` on first sign-in, stamps `provider + provider_id` columns (new migration, nullable, composite unique index). `plan`/`provider_id` NOT fillable — set explicitly.
- Errors (user cancel, no email returned, network) route back to `/login` with sanitised flash.
- Rate-limited 10/min per IP on callback.
- "Continue with Google" button on `/login` + `/register` — renders only when `GOOGLE_CLIENT_ID` is configured (silent in local dev). Button styling: white bg + dark text in BOTH light and dark modes (per Google brand guidelines).

**Cloudflare Turnstile**
- Widget on `/register` (renders only when `TURNSTILE_SITE_KEY` set).
- `RegisteredUserController::verifyTurnstile()` calls Cloudflare siteverify server-side. Fails closed on network errors (won't let bots DOS siteverify to bypass).

**Sentry (error monitoring)**
- `composer require sentry/sentry-laravel`; `vendor:publish` emitted `config/sentry.php`; reads `SENTRY_LARAVEL_DSN` + `SENTRY_TRACES_SAMPLE_RATE`. Service provider auto-registers — no code changes needed to capture exceptions.

**Google Analytics 4**
- Snippet in landing-v3 + guest layout + app layout, gated on `config('services.analytics.ga4_id')`. App layout fires virtual `page_view` on `livewire:navigated` so SPA-style navigation is tracked.

**Terms + Privacy pages**
- `/terms` and `/privacy` with generic best-practice copy. Shared `legal/_layout.blade.php` with BreadcrumbList + full OG + canonical. Linked from register form + landing footer.

**UX polish from launch-prep audit**
- Footer CTA buttons fit 360px mobile (text-[13px] on mobile, full-width stacked).
- Final CTA heading clamp minimum dropped `3rem → 2.4rem`.
- FAQ accordion titles: `text-base` on mobile, `text-lg` sm+.
- "Add Entry" button always shows "Add" on mobile (was icon-only).
- Password-toggle hit targets on all auth + Change Password forms: expanded from ~20px wide to `w-11` (44px).
- Landing mobile hamburger drawer (Alpine, slides down, X/close icon swap).
- Sidebar auto-closes on `livewire:navigated` + `popstate` — mobile hamburger no longer sticks open after navigation.
- Mobile app-header logo now links to `/dashboard`.
- Mobile Scan-Receipt picker fix: file input moved off-screen (display:none blocks `.click()` on mobile Safari/Chrome); Pro users trigger `.click()` synchronously from the user gesture instead of via Livewire round-trip.
- Default currency on Create Business flipped from `PKR` → `USD`.
- Stripe error messages sanitised on Billing page.
- Landing footer giant wordmark opacity raised from 0.04 → 0.13 (was invisible).
- Landing footer links simplified to just Terms + Privacy.

**Error pages polish (404/403/419/429/500/503)**
- Always-dark (removed light-mode variants) to match landing's aesthetic — users arriving from a broken share link now see continuity.
- Giant gradient error code, subtle dot-grid background, 3 floating glow orbs, pulsing icon, fade-up entrance.
- Brand strip at top links to home.
- 404 gains context-aware quick-links: authenticated users see Dashboard / Billing / Profile; guests see Register / Login / Terms / Privacy.
- Favicon uses DB-backed `brand-asset/favicon` route.
- Footer with Terms + Privacy links.

**Removed from Build Order (deferred):**
- Apple / Microsoft / Facebook OAuth — Google-only for launch
- Welcome email on signup
- Custom OG image (1200×630) — landing falls back to logo-dark
- Webhook event log table
- Admin audit log
- Mobile app Google OAuth (separate native flow)
- Full desktop-audit cosmetic polish items

### Key patterns / gotchas documented this session

- **Railway is ephemeral** — `public/brand/` uploads wiped every redeploy. Solution for this project: DB-backed `uploaded_assets` table. Alternative for other uploads: mount a Railway volume.
- **`@directiveName` inside `{{-- --}}` Blade comments IS a directive** — silent swallowing of everything downstream. Always write "at-directiveName" or HTML-escape.
- **Google OAuth "unverified app" warning** — avoid by (a) verifying domain in Google Search Console via DNS TXT record, then (b) publishing the app on Google Auth Platform. Takes ~10 min, vs Google's multi-week app verification (only needed for sensitive scopes / 100+ users).
- **Turnstile widget classes** — `<div class="cf-turnstile" data-sitekey="..." data-theme="auto" data-size="flexible">`. Server-side verify via `https://challenges.cloudflare.com/turnstile/v0/siteverify` with `secret + response + remoteip`.
- **Sentry**: `vendor:publish --provider="Sentry\Laravel\ServiceProvider"` publishes `config/sentry.php`. No kernel changes needed — the service provider auto-hooks into the exception handler.

### Session Notes (previous — 2026-04-07)

### Completed this session (2026-04-07) — Mobile app Phase A/B/C/D + device testing fixes

**Massive session: mobile app brought from ~60% to near-parity with the web app, tested on real device, UX issues patched iteratively.** Touches both `project-web` (backend API expansion) and `project-mobile` (Expo React Native).

#### Phase A — Mobile foundation fixes
- `project-mobile/src/api/client.ts` — moved API URL to `app.json` → `expo.extra.apiUrl` (single source of truth), pointing at `https://cashflow-production-fd97.up.railway.app/api/v1`. Removed hardcoded dev IP. Bumped axios timeout to 30s for Railway cold-starts. Added global 401 handler via `setAuthExpiredHandler()`.
- `project-mobile/src/utils/errors.ts` — `errorMessage()` extracts safe user-facing messages from axios errors; strips SQL/stack-trace leakage; handles 401/403/404/422/429/500/network/timeout; `isSafeMessage()` heuristic blocks messages containing banned terms.
- `project-mobile/src/components/Toast.tsx` — animated slide-up toast with `success/error/info` variants, auto-dispatches haptics (Success/Error `Haptics.notificationAsync`), safe-area aware positioning. `ToastProvider` at root; `useToast()` hook. Replaces `Alert.alert` for non-destructive feedback across all screens.
- `project-mobile/app/_layout.tsx` — wraps children in `SafeAreaProvider` + `ThemeProvider` + `AuthProvider` + `ToastProvider`. Loads `GeistMono_400Regular` + `GeistMono_700Bold` via `@expo-google-fonts/geist-mono`. `ThemedRoot` inner component makes StatusBar style reactive to theme.
- `project-mobile/src/theme/typography.ts` — `fonts.mono` = `GeistMono_400Regular`, `fonts.monoBold` = `GeistMono_700Bold`. All `'Courier'` fallbacks replaced across ~8 files (dashboard, business detail, ledger, entry form, recurring tab, activity feed, drawer, create-book).
- `project-mobile/src/context/AuthContext.tsx` — fixed biometric cancel no longer leaving the app on an infinite loading screen. `retryBiometric()` method added. Registers global 401 handler via `setAuthExpiredHandler`. Proper cleanup on unmount.
- `project-mobile/src/components/CommentsPanel.tsx` — rewritten as `<Modal>` with `KeyboardAvoidingView` so iOS keyboard no longer hides the input. Wired to toast system. Added `hitSlop` on close/delete. Safe-area bottom padding.

#### Phase B — Backend REST API expansion (60 total endpoints, +30 new)
- `project-web/app/Http/Controllers/Api/V1/AuthController.php` — `PUT /profile` (updateProfile, invalidates email verification on change), `PUT /profile/password` (with currentPassword check + revokes all OTHER tokens preserving current), `POST /auth/email/resend`, `DELETE /profile` (requires password; cancels active subscription via `$user->subscription('default')->cancelNow()`).
- `project-web/app/Http/Controllers/Api/V1/BusinessController.php` — `POST /businesses` (create, Free plan 1-business gate, owner pivot attach, currency regex validation), `PUT/DELETE /businesses/{id}`, team management: `POST /businesses/{id}/invitations` (Free 2-member gate, 5/hour rate limit via `RateLimiter`, queues `TeamInvitation` mailable), `GET /businesses/{id}/invitations` (pending only), `DELETE /invitations/{id}`, `PUT/DELETE /businesses/{businessId}/members/{userId}` (role change + remove). Private `ensureOwner()` guard.
- `project-web/app/Http/Controllers/Api/V1/BookController.php` — `PUT/DELETE /books/{id}`, `POST /books/{id}/duplicate` (with `copyCategories/copyPaymentModes/copyEntries` flags; creates new book, optionally loops entries stamping `created_by`), `GET /books/{id}/report-data` (full period summary + auto-bucketed trend by day/week/month + category breakdown in/out + payment mode breakdown; Pro-gated), `GET/PUT/DELETE /books/{id}/report-schedule` (CRUD for `ReportSchedule` model). Private `ensureEditor()` guard.
- `project-web/app/Http/Controllers/Api/V1/EntryController.php` — bulk ops: `POST /books/{id}/entries/bulk-delete` (cleans up attachment files), `POST /books/{id}/entries/bulk-update` (category/paymentMode change + `flipType: true` toggles in↔out via temp marker), `POST /books/{id}/entries/bulk-move` (move or copy with `copy: true/false` flag, target book must be same business). Attachment endpoints: `POST/GET/DELETE /entries/{id}/attachment` with MIME whitelist + `X-Content-Type-Options: nosniff`. `validatedBulkIds()` private helper verifies all IDs belong to current book (security: prevents cross-book manipulation).
- `project-web/app/Http/Controllers/Api/V1/SettingsController.php` — NEW controller: `GET /billing/checkout-url` (returns web `/settings/billing` URL for WebView), `GET /announcement` (reads `Setting::get('announcement')` JSON, checks `is_active` + expiry), `GET /notifications`, `POST /notifications/mark-all-read`, `DELETE /notifications/{id}`.
- `project-web/routes/api.php` — rewrote to register all 60 endpoints in grouped blocks (Auth+Profile / Businesses / Books / Entries / Attachments / Comments / Recurring / OCR / Export / Settings).

#### Phase C — Mobile screens consuming Phase B
- `project-mobile/src/api/auth.ts` — added `updateProfile`, `changePassword`, `resendVerification`, `deleteAccount` methods.
- `project-mobile/src/api/businesses.ts` — massive expansion: added `Invitation`, `ReportSchedule`, `ReportData`, `AppNotification`, `Announcement` interfaces. Added methods for business CRUD, book CRUD + duplicate, team (invitations/invite/cancelInvitation/updateMemberRole/removeMember), reports (reportData/reportSchedule/saveReportSchedule/deleteReportSchedule), billing, announcement, notifications.
- `project-mobile/src/api/entries.ts` — added `bulkDelete`, `bulkUpdate`, `bulkMove`, `uploadAttachment` (FormData with RN file blob shape), `attachmentUrl`, `deleteAttachment`.
- `project-mobile/app/(app)/profile/` — converted from file to directory. `profile/index.tsx` redesigned with action rows (Edit Profile / Billing & Plans / Dark Mode toggle) plus account info card + upgrade card. `profile/edit.tsx` new screen with profile form (name/email) + password form + email verification resend link when unverified.
- `project-mobile/app/(app)/business/create.tsx` — new Create Business screen with 24-currency searchable picker modal (PKR, USD, EUR, GBP, INR, AED, SAR, BDT, CAD, AUD, JPY, CNY, SGD, MYR, IDR, THB, PHP, TRY, EGP, ZAR, NGN, KES, BRL, MXN), name/description fields, Free plan note.
- `project-mobile/app/(app)/business/book/edit.tsx` — new Edit/Duplicate Book screen. Mode-aware via `?mode=edit|duplicate` param. Shares period presets (This Month / Last Month / This Quarter / This Year) + start/end date fields + opening balance + description. Duplicate mode adds `copyCategories/copyPaymentModes/copyEntries` toggles.
- `project-mobile/app/(app)/business/book/email-reports.tsx` — new Email Reports settings screen. Toggle active/paused, frequency pills (Weekly Mondays / Monthly 1st), recipients textarea (comma-separated, max 10, validated), last-sent-at display, delete schedule action.
- `project-mobile/app/(app)/business/members.tsx` — rewrote from read-only to full team management. Owner sees: invite FAB, pending invitations section with cancel action, change-role modal (editor ↔ viewer), remove member with confirmation. Non-owners see read-only list.
- `project-mobile/app/(app)/billing.tsx` — new Billing WebView screen. `react-native-webview` installed via `npx expo install`. Loads `/settings/billing` URL from `GET /billing/checkout-url`. Banner explains one-time web login. Detects `?checkout=success` URL on navigation and calls `refreshUser()` + shows "Welcome to Pro!" toast.
- `project-mobile/src/components/ReportsTab.tsx` — new Reports tab component. Uses `ReportData` from API. Period summary grid (Cash In / Cash Out / Net Balance cards). Horizontal-scrolling trend chart built with pure Views (no chart lib) — stacked in/out bars scaled to `trendMax`. `CategoryBars` sub-component for top 5 outflow / top 5 inflow / top 5 payment modes with horizontal bar fills. Free user sees gated preview.
- `project-mobile/app/(app)/business/book/[id].tsx` — huge updates:
  - Bulk selection mode via long-press (`selectedIds: Set<string>`). Bulk action bar appears at bottom with count + Select All + Move + Copy + Flip Type + Delete. `openBookPicker('move'|'copy')` fetches sibling books and shows a book picker modal. `handleBulkMoveTo()` calls the right endpoint.
  - Custom date range filter: new `'custom'` chip in duration bar opens a modal with two `DatePickerInput`s. `customFrom/customTo` state flows into the filteredEntries memo.
  - Reports tab now renders `AiInsightsCard` + `ReportsTab` component.
  - Book settings header dropdown: Export PDF/CSV, Edit Book, Duplicate Book, Email Reports, Delete Book. Delete uses existing Alert with destructive confirm.
  - **Device test fixes (round 2)**: comment icon wasn't clickable because it was a `TouchableOpacity` nested inside a parent `TouchableOpacity` (RN parent swallows press). Restructured entry row with sibling `Pressable`s — info zone + amount zone + comment button each handle their own presses independently. `commentBtn` got `hitSlop={10}`.
  - **Device test fixes (round 2)**: settings icon (`⚙️` emoji) looked inconsistent/"fake". Replaced with a clean `⋯` kebab in a `headerBtn` styled as a 36×36 circular dark-card button. `hitSlop={12}`.
  - **Device test fixes (round 3)**: tab bar was invisible on device — no explicit height, ScrollView was collapsing. Added `minHeight: 48, maxHeight: 48, backgroundColor: colors.dark`, bumped tab text to `fonts.headingSemi`, made inactive state `grayLight` instead of `gray` (higher contrast), thickened active indicator from 2px to 3px.
  - **Device test fixes (round 3)**: duration filter chips were faded/grey — bumped font from 11 to 12, changed inactive text from `gray` to `grayLight`, active state `white` instead of `accent`, fully rounded pills, larger padding.
  - **Device test fixes (round 3)**: filter row restructured — segmented control (All / Cash In / Cash Out) now full-width on its own row with `flex: 1` on each button; `filterStatus` row below with entry count + Clear filters link.
  - **Device test fixes (round 3)**: back button was showing "index"/"Personal" (previous route slug). The fix used `headerBackTitleVisible` which **doesn't exist** in this Expo Router version (silent at runtime, TS error). Correct prop is `headerBackButtonDisplayMode: 'minimal'` — set at layout level in `(app)/_layout.tsx` and explicitly on each `Stack.Screen` override.
- `project-mobile/app/(app)/business/[id].tsx` — **Device test fix (round 3)**: added header `⋯` button (owner only) with dropdown: Rename Business / Manage Team / Delete Business. Rename opens inline modal with TextInput + Cancel/Save actions. Delete uses Alert confirm.

#### Phase D — Polish
- `project-mobile/src/components/DatePickerInput.tsx` — cross-platform native date picker. Android uses inline default display, iOS uses modal sheet with dark-themed spinner + Cancel/Done header. Installed via `npx expo install @react-native-community/datetimepicker`. Wired into entry form, create-book, edit-book, and custom date range modal. Replaces all `YYYY-MM-DD` text inputs.
- `project-mobile/src/components/AnnouncementBanner.tsx` — new component, reads `GET /announcement`, dismissal persisted via `SecureStore` keyed by `updatedAt` (so new announcements re-show). Info/warning/success type variants. Mounted in root layout after auth.
- `project-mobile/src/components/NotificationsBell.tsx` — new component in dashboard header. Unread count badge. Tap opens slide-in panel from right with pull-to-refresh + mark-all-read + per-item delete. Empty state.
- `project-mobile/src/components/Skeleton.tsx` — animated shimmer blocks using `Animated.loop` on opacity. `SkeletonCard` for business list, `SkeletonEntryRow` for ledger. Used in initial loading states of dashboard + ledger.

#### Theme system (light + dark mode)
- `project-mobile/src/theme/colors.ts` — split into `darkColors` and `lightColors` exports conforming to `ThemeColors` interface (typed as `Record<...>` with string values, not `as const`, so both palettes are assignable). Dark is the navy `#0a0f1e` aesthetic; light is `#f8fafc` bg with deeper `#1e40af` accents + `#15803d` green + `#b91c1c` red for WCAG contrast.
- `project-mobile/src/context/ThemeContext.tsx` — new `ThemeProvider` + `useTheme()` + `useColors()` + `useThemedStyles(factory)` hooks. Mode persisted in `SecureStore` under `theme_mode`. `useThemedStyles` takes a `(colors: ThemeColors) => StyleSheet` factory and memoizes on colors change.
- **Pattern:** each screen converts from `const styles = StyleSheet.create(...)` at module scope → `const makeStyles = (colors: ThemeColors) => StyleSheet.create(...)` + `const styles = useThemedStyles(makeStyles)` inside the component. Static `colors` import removed from the file; inline `colors.X` references in JSX use the destructured `colors` from `useTheme()`. Hardcoded `colors.white` on always-primary surfaces (FAB text, avatar text) replaced with literal `'#f8fafc'` so they stay white even in light mode.
- **Converted so far:** profile (`(app)/profile/index.tsx`), dashboard (`(app)/index.tsx`), business detail (`(app)/business/[id].tsx`), ledger (`(app)/business/book/[id].tsx`), entry form (`(app)/business/book/entry.tsx`).
- **Not yet converted:** auth screens, edit profile, edit book, email reports, create business, members, drawer menu, billing webview. These stay dark until Phase E.
- Toggle lives in Profile → Dark Mode switch row with sun/moon icon.

#### Backend bug fix (round 2 device test)
- `project-web/app/Http/Resources/V1/BookResource.php` — **critical bug**: `whenAppended('total_in', ...)` only fires when attribute is in Eloquent's `$appends` array. But `BusinessController@books` sets totals as dynamic attributes (`$book->total_in = $book->totalIn()`), NOT appended. So mobile `GET /businesses/{id}/books` was returning `totalIn: null, totalOut: null, balance: null` → the books list on mobile showed zero everywhere. Fixed by switching to direct `isset()` checks + string casts. **Deployed and verified live on Railway.**

#### Infrastructure / testing
- Expo Go testing via tunnel mode (`npx expo start --tunnel`). Tunnel URL: `exp://erinsfw-anonymous-8081.exp.direct` (persistent during this session). User tested on iPhone via Expo Go.
- Backend deployed to Railway; mobile app targets Railway URL via `app.json` → `expo.extra.apiUrl`.
- Commits pushed to `github.com/Azeem5454/cashflow` main branch.

### Key mobile patterns established this session
- **Themed styles**: `const makeStyles = (colors: ThemeColors) => StyleSheet.create({...})` + `const styles = useThemedStyles(makeStyles)` at component top. Requires destructuring `colors` from `useTheme()` separately for inline JSX references. Hardcode brand constants (`#f8fafc` for text-on-primary) to survive theme switches.
- **Back button on iOS**: use `headerBackButtonDisplayMode: 'minimal'` (not `headerBackTitleVisible`, which doesn't exist in this Expo Router version). Set at layout level AND each Stack.Screen — the layout default doesn't always win.
- **Nested touchables**: RN parent `TouchableOpacity` swallows children's presses. Fix by using a wrapping `View` with sibling `Pressable` zones (one per interactive region). Each Pressable handles its own `onPress`/`onLongPress` independently.
- **Tab bar visibility**: always set explicit `minHeight/maxHeight` on horizontal `ScrollView` tab bars or they collapse on device. Bump inactive text to `grayLight` for contrast on dark navy.
- **API URL config**: single source in `app.json` → `expo.extra.apiUrl`, read via `Constants.expoConfig?.extra?.apiUrl`. No dev/prod forking — edit one line + reload Expo Go.
- **Dynamic attributes on Eloquent resources**: `whenAppended()` requires actual `$appends` registration. For controller-assigned dynamic attributes, use `isset()` + direct access.
- **RN FormData file upload**: shape `{ uri, type, name }` cast as `unknown as Blob`; set `Content-Type: multipart/form-data` header explicitly on the axios call.
- **Toast over Alert.alert**: destructive confirmations (delete, sign out) + picker choice lists (image source) stay as `Alert.alert`. Everything else (success/error feedback) uses `toast.success()/error()/info()` from `useToast()`.
- **Error sanitization**: every `catch` block calls `toast.error(errorMessage(e))` — the `errorMessage()` util strips SQL/stack traces and maps HTTP codes to friendly strings.

### Pending for next session (Phase E — mobile polish round 2)
- Convert remaining screens to themed styles: auth screens, edit profile, edit book, email reports, create business, members, drawer menu, billing webview, create-book, ActivityFeed, AiInsightsCard, RecurringTab, CommentsPanel, NotificationsBell, AnnouncementBanner, DatePickerInput, Skeleton, Toast, PickerDropdown, ReportsTab.
- Date picker integration in edit-book.tsx + create.tsx if not already (verify).
- Light mode fixes for any screens with hardcoded text-on-primary that doesn't survive theme flip.
- Convert `app/(auth)/_layout.tsx` to use useTheme for contentStyle.
- Skeleton loaders on business detail, members, reports.
- Animation polish: balance count-up on save.

### Completed this session (2026-03-26)

- **Email reports (Pro)** — full feature build:
  - `database/migrations/2026_03_25_000001_create_report_schedules_table.php` — UUID PK, book_id FK cascadeOnDelete, frequency (weekly/monthly), recipients JSON, is_active, last_sent_at
  - `app/Models/ReportSchedule.php` — `isDue()` (frequency-aware interval check), `buildReportData()` (period summary, top 5 categories, 10 recent entries — reused by command + Livewire)
  - `app/Models/Book.php` — added `reportSchedule()` HasOne relationship
  - `app/Mail/BookEmailReport.php` — queued mailable with custom HTML view (not markdown)
  - `app/Console/Commands/SendEmailReports.php` — `reports:send` scheduled daily at 09:00 UTC; iterates active schedules, checks `isDue()` + Pro gate, queues emails, updates `last_sent_at`
  - `routes/console.php` — added `Schedule::command('reports:send')->dailyAt('09:00')`
  - `app/Livewire/Book/Show.php` — 8 new properties + `openEmailReportModal()`, `saveEmailReport()`, `deleteEmailReport()`, `sendTestReport()` methods
  - `resources/views/livewire/book/show.blade.php` — "Email Reports" in settings gear dropdown (Pro badge for free users); full modal with enable/disable toggle, weekly/monthly frequency pills, recipients textarea, "Last sent" timestamp + "Send Test" button, info note with first-report notice
  - `resources/views/components/upgrade-modal.blade.php` — added `emailreports` feature type with envelope icon, body copy, 4-item feature list
  - **Send Test** — `sendTestReport()` queues one email to current user only; loading spinner on button
  - **Last sent** — shows `diffForHumans()` timestamp in modal when schedule exists
  - **Auto-send on first enable** — `saveEmailReport()` immediately queues the first report when creating a new active schedule
  - **Downgrade pausing** — `ReportSchedule::update(['is_active' => false])` added to: Stripe webhook (AppServiceProvider), admin force-free (UserDetail + Users)
  - **Book delete cleanup** — `deleteBook()` now calls `$this->book->reportSchedule?->delete()`

- **Unified branded email system** — all emails now share a common dark-luxe design:
  - `resources/views/emails/partials/layout.blade.php` — shared HTML layout with dynamic logo (`brand/logo-dark.png` or text fallback), dynamic app name (`config('app.name')`), blue gradient accent bar, `@media` mobile responsive queries, badge slot, footer slot
  - `resources/views/emails/book-email-report.blade.php` — rewritten from markdown to custom HTML; 3 summary cards (Cash In/Out/Net), category progress bars, entry list with type dots, CTA button; mobile: cards stack vertically, padding reduces, amounts scale down
  - `resources/views/emails/team-invitation.blade.php` — rewritten from markdown to branded layout; invitation details card with business name + role badge, permissions checklist, CTA button, expiry note
  - `resources/views/emails/admin-email-verification.blade.php` — rewritten from markdown to branded layout; large monospace OTP code in dark card, amber expiry warning, numbered instructions
  - `resources/views/emails/verify-email.blade.php` — new branded signup verification email; verify button + fallback URL link + 60-minute expiry
  - `resources/views/emails/reset-password.blade.php` — new branded password reset email; security note card with lock icon, reset button + fallback URL link + 60-minute expiry
  - `app/Notifications/CustomVerifyEmail.php` — overrides Laravel's `VerifyEmail` to use branded view
  - `app/Notifications/CustomResetPassword.php` — overrides Laravel's `ResetPassword` to use branded view
  - `app/Models/User.php` — `sendEmailVerificationNotification()` and `sendPasswordResetNotification()` overridden to use custom notifications
  - `app/Mail/TeamInvitation.php` — changed from `markdown:` to `view:`, subject uses dynamic app name
  - `app/Mail/AdminEmailVerification.php` — changed from `markdown:` to `view:`, subject uses dynamic app name
  - `app/Mail/BookEmailReport.php` — changed from `markdown:` to `view:`

- **Custom error pages** — branded animated error pages for all common HTTP errors:
  - `resources/views/errors/layout.blade.php` — standalone HTML layout (no Vite/Livewire dependency); animated gradient error code with shimmer, 3 floating background orbs with drift animation, pulsing icon, fade-up entrance; dark/light mode via `cashflow_theme` localStorage; brand fonts loaded via Google Fonts CDN; fully responsive with `clamp()` typography
  - `resources/views/errors/403.blade.php` — "Access Denied" with lock icon (red); explains wrong account or insufficient role; Dashboard + Go Back buttons
  - `resources/views/errors/404.blade.php` — "Page Not Found" with search icon (blue); explains page doesn't exist or moved; Dashboard + Go Back buttons
  - `resources/views/errors/419.blade.php` — "Session Expired" with clock icon (amber); explains timeout; Refresh + Sign In Again buttons
  - `resources/views/errors/429.blade.php` — "Slow Down" with warning icon (amber); rate limit explanation; Try Again + Go Back buttons
  - `resources/views/errors/500.blade.php` — "Something Went Wrong" with wrench icon (red); team notified; Try Again + Dashboard buttons
  - `resources/views/errors/503.blade.php` — "We'll Be Right Back" with wrench icon (blue); maintenance message; Check Again button

### Completed this session (2026-03-25)

- **Landing page v3 (`landing-v3.blade.php`)** — full redesign and cleanup:
  - Removed scrolling testimonial strip (deleted `$row1`/`$row2` PHP arrays + `.ml` scroll div)
  - Hero mockup moved up (`md:self-end` → `md:-mt-16`)
  - Dot grids removed from all sections except Pain (kept 1, subtler: `rgba(59,130,246,0.038)`, `28px 28px`); How it Works uses diagonal gradient `linear-gradient(140deg,var(--dark2) 0%,#0c1a2e 55%,var(--dark2) 100%)`; Outcomes uses vertical gradient `linear-gradient(to bottom,var(--black),#081525 50%,var(--black))`
  - Alpine CDN added in `<head>` (`https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js`) — landing has no Livewire so CDN is required
  - Smooth scroll: `html { scroll-behavior: smooth; scroll-padding-top: 90px; }` — all nav links use `href="#section-id"` anchors

- **Floating island navbar** — permanent island state (not only on scroll):
  - Wrapper div pattern: outer `div#nav-wrapper` is `sticky top-0 z-50` with `padding: 12px 5% 0` (creates visible top gap when stuck); inner `<nav id="main-nav">` has no sticky
  - Nav CSS: `border-radius:20px; border:1px solid rgba(255,255,255,0.13); background:rgba(7,11,22,0.92); backdrop-filter:blur(24px); box-shadow: 0 4px 6px ...`
  - Mobile: `padding: 8px 3% 0`, `border-radius: 16px`
  - All scroll JS (`.classList.add('scrolled')`) removed — island is always visible
  - Key pattern: `sticky top-3` with `margin-top` breaks sticking; must use wrapper with `sticky top-0` + `padding-top` on wrapper

- **Deleted old landing files** — `resources/views/landing.blade.php` and `resources/views/landing-v2.blade.php` deleted; `/home` route removed from `routes/web.php`; `landing-v3.blade.php` is now the sole landing page at `/`

- **Security audit — 20 issues found and addressed**:
  - `routes/web.php`: `session()->regenerate()` added after stop-impersonating to prevent session fixation
  - `app/Livewire/Admin/UserDetail.php`: admin re-verification (`abort_unless(auth()->user()->is_admin)`) + guard prevents impersonating another admin
  - `app/Livewire/Business/Create.php`: currency validation tightened to `size:3` + `regex:/^[A-Z]{3}$/`
  - `app/Providers/AppServiceProvider.php`: HTTPS enforcement changed from `isProduction()` to `!isLocal()` (covers staging too)
  - `app/Livewire/Settings/Billing.php`: verbose Stripe exception message replaced with generic "Payment processing is temporarily unavailable"
  - `app/Livewire/Book/Show.php`: `$this->guardEditor()` added to `updatedOcrFile()` to block viewer role from OCR
  - `app/Http/Controllers/ExportController.php`: rate limiting added to `pdf()` and `csv()` (10 exports/minute per user via `RateLimiter`)
  - `{!! $comment->renderedBody() !!}` confirmed safe — `renderedBody()` calls `e($this->body)` to escape raw input first, then only injects `<span>` with `e($m[1])` on matched mention names
  - `{!! $d !!}` in landing-v3 confirmed safe — hardcoded PHP array strings, not user input

- **Admin panel mobile fixes**:
  - All 9 admin Blade pages: `p-8` → `p-4 sm:p-8` for comfortable mobile padding
  - `admin/businesses.blade.php`: added `overflow-x-auto` wrapper + `min-w-[640px]` on table
  - `admin/dashboard.blade.php`: Recent Signups table wrapped in `overflow-x-auto` + `min-w-[500px]`
  - `layouts/app.blade.php` impersonation banner: `min-w-0` + `truncate` on name span, `flex-shrink-0` on form, button text shortened to "Stop" to prevent overflow on narrow screens

### Completed this session (2026-03-24)

- **Unified theme persistence** — landing page, guest layout (login/register), app layout, and admin layout all read/write the same `cashflow_theme` localStorage key and default to `light`; landing page now reads saved preference before paint via blocking `<script>` and applies `data-theme="dark"` when dark is saved; admin layout changed from `?? 'dark'` to `?? 'light'` in all 3 locations; result: selecting dark on dashboard persists to landing/login and vice versa
- **Billing page updated to $5/month** — Pro plan price changed from `$3` to `$5`; feature list expanded from 6 to 10 items including AI features (receipt OCR 200/month, auto-categorization, cash flow insights); added "Billed monthly · Cancel anytime" subtitle
- **Dashboard onboarding empty state** — new 3-section empty state for users with no businesses: welcome banner + CTA, "How it works" 3-step guide, use-case tiles (My Business / Freelance+Personal / Team); staggered reveal animation; dark mode fixed (`dark:bg-slate-800` not `dark:bg-slate-800/50`)
- **Currency dropdown on Create Business** — replaced native `<select>` with custom Alpine.js dropdown; 60+ currencies across South Asia, Middle East, Europe, East Asia, Africa, Latin America; search bar filters by code or name; removed `overflow-hidden` from parent card (clipping fix), inline `style="max-height: 220px"` on options list; `$wire.entangle('currency').live` binding
- **Dashboard "+Add Book" button fixed** — links to `route('businesses.show', $business) . '?createBook=1'`; `business/show.blade.php` adds Alpine `x-init` on root div to call `$wire.call('openCreateBook')` when param present
- **Admin panel light mode** — all text-white hardcodes fixed across 8 admin blade files to `dark:text-white text-gray-900`; FREE badges, section headings, badges, user links all fixed; admin layout default changed from `?? 'dark'` to `?? 'light'`
- **Admin announcement date picker** — replaced `<input type="datetime-local">` with Flatpickr date-only picker (`disableMobile: true`, `minDate: today`)
- **All Books page redesign** — new design matching dashboard aesthetic (see below)

### Completed this session (2026-03-23)

- **Date range filtering & comparison (Pro)** — custom date modal upgraded from native `<input type="date">` to Flatpickr (`appendTo: document.body`, `disableMobile: true`) to fix browser calendar overlapping modal; two comparison modes added: "Previous Period" (same duration immediately before) and "Same Period Last Year" (exact dates minus 1 year via Carbon `subYear()`); period comparison card inserted between filter bar and balance strip showing 3-row table (Cash In / Cash Out / Net) with ↑↓% change badges; `$compareEnabled` + `$compareMode` properties; `toggleComparison()` (Pro-gated); `buildComparisonData()` private method; `clearFilters()` resets comparison state; `upgradeModalFeature = 'daterange'` on free-user click; `upgrade-modal.blade.php` extended with `daterange` feature type; `app.css` gets `.flatpickr-wrapper { display: block !important; width: 100%; }` to fix icon positioning

- **Mobile responsiveness audit & fixes** — comprehensive audit of all screens, fixes applied:
  - `book/show.blade.php`: 4-tab row (`Entries | Activity | Reports | Recurring`) wrapped in `overflow-x-auto` + `w-max` container so it scrolls horizontally on narrow screens instead of overflowing; toast `fixed bottom-4 sm:bottom-6 left-4 right-4 sm:left-1/2 sm:right-auto sm:w-auto sm:-translate-x-1/2 sm:whitespace-nowrap` (mobile: full-width toast, desktop: centered); slide-over close button `p-1.5` → `p-2`; comments panel close button `p-1.5` → `p-2`; preset grids `grid-cols-4` → `grid-cols-2 sm:grid-cols-4` (Edit Book + Duplicate Book modals); added "Flip Type" (Copy Opposite) button to mobile bottom bulk toolbar
  - `business/show.blade.php`: preset grids `grid-cols-4` → `grid-cols-2 sm:grid-cols-4` (Create + Edit + Duplicate Book modals)
  - `layouts/app.blade.php`: sidebar close button `p-1.5` → `p-2` for better touch target
  - `settings/billing.blade.php`: plan status card and billing management card changed `p-6` → `p-5 sm:p-6` + `flex-wrap` on both flex rows so button wraps gracefully on narrow screens

- **Key mobile patterns established**:
  - Tab rows with 3+ tabs: wrap in `overflow-x-auto` > `w-max` container
  - Toasts: `left-4 right-4 sm:left-1/2 sm:right-auto sm:w-auto sm:-translate-x-1/2 sm:whitespace-nowrap`
  - Modal preset grids: always `grid-cols-2 sm:grid-cols-4` (never raw `grid-cols-4` which is too tight on 360px)
  - Touch targets: all icon-only close/action buttons must use at least `p-2` (32px total with 16px icon)

### Completed this session (2026-03-22)

- **Landing page v2 live at `/`** — `landing-v2.blade.php` promoted to the main route; old landing moved to `/home`; landing page **always opens in light mode** (init script no longer reads from localStorage — toggle still saves to `cashflow_theme` for the app); dark mode toggle moved to end of navbar: `Sign in | Start free | [toggle]`

- **Unified theme key** — landing page was using a separate `cf-theme` localStorage key; changed to `cashflow_theme` so theme choice is shared with the app; app layout default changed from `dark` to `light` (`?? 'dark'` → `?? 'light'` in all three script locations in `app.blade.php`)

- **Guest layout full redesign** — `resources/views/layouts/guest.blade.php` rewritten from hardcoded-dark to fully theme-aware; reads `cashflow_theme` via blocking script before paint; light mode: soft blue-to-indigo gradient background with dot grid; dark mode: navy with glow orbs; right form panel: `bg-white dark:bg-dark`; all mock card dark-mode backgrounds implemented via CSS classes in `<style>` block (`.mock-card-shell`, `.mock-card-header`, `.mock-balance-strip`, `.mock-entry-row` + `html.dark` overrides) rather than Tailwind arbitrary values which require Vite rebuild

- **Login and signup left panels are now distinct** — detected via `request()->routeIs('login')` in the layout:
  - **Login**: "Your books are waiting for you." + 3 stats grid (500+ businesses / ₨1T+ / 4.9★) + 3 trust bullets (encrypted / free plan / device sync)
  - **Signup**: "See exactly where your money goes." + mini live dashboard card (cash book header, balance strip, 3 entry rows)
  - Both share the same testimonial footer

- **Auth form fonts fixed** — guest layout hard-codes Google Fonts URL (identical to landing page: `Bricolage+Grotesque:opsz,wght@12..96,...`); explicit CSS classes `.guest-display` / `.guest-body` / `.guest-mono` with direct `font-family` declarations bypass the Tailwind CSS variable fallback (`var(--font-display, Bricolage Grotesque)` without quotes fails when `theme.css` absent); all `font-body` / `font-display` classes in `login.blade.php`, `register.blade.php`, `forgot-password.blade.php`, `reset-password.blade.php` replaced with `guest-body` / `guest-display`

- **Auth form colors — light mode** — all four auth views updated from hardcoded-dark to light-first with `dark:` variants: headings `text-slate-900 dark:text-white`, labels `text-slate-700 dark:text-slate-300`, icons `text-slate-400 dark:text-slate-500`, links `text-primary dark:text-blue-light`, dividers `bg-gray-200 dark:bg-white/8`, secondary action buttons `border-gray-200 hover:bg-gray-50 dark:border-white/10 dark:hover:bg-white/5`; `.auth-input` CSS has explicit `html.dark` override block; eyebrow text uses `.guest-eyebrow` CSS class with `html.dark` override (avoids opacity modifier compilation issues)

- **Stats `\n` bug fixed** — PHP single-quoted strings don't process escape sequences; changed from `'Businesses\ntracking cash'` to two separate array elements `['Businesses', 'tracking cash']` rendered as `{{ $stat[1] }}<br>{{ $stat[2] }}`

### Completed this session (2026-03-21)

- **Book settings modals upgraded** — "Rename Book" and "Duplicate Book" in the book detail settings dropdown now use the same full-featured modals as the All Books page; `Book\Show.php` replaces `$showRenameBook`/`$renameBookName`/`openRenameBook()`/`renameBook()` with full edit properties + `openEditBook()`/`saveEditBook()`; `duplicateBook()` replaced with `openDuplicateBook()`/`executeDuplicate()`; Edit Book modal has name, period presets (This Month/Last Month/This Quarter/This Year), Flatpickr date pickers, opening balance, description; Duplicate modal has same period picker + Carry Over checkboxes (Categories / Payment Methods / Entries); `bookPeriodPicker()` Alpine factory embedded via `<script>` block in book/show.blade.php; `saveEditBook()` dispatches "Book updated successfully." toast; `executeDuplicate()` redirects to new book

- **Comment icon hover behavior** — comment icon on entry rows: always visible with count (violet) when `comments_count > 0`; revealed on row hover when zero comments (uses existing `hovered` Alpine state per row); placed inline after description (not in hover-actions column, which caused balance overlap); mobile: shown below amount/balance in right column always

- **Upgrade modal — feature-specific copy + gold color** — migrated from `public bool $showUpgradeModal` to `public string $upgradeModalFeature` in `Book\Show`, `Business\Create`, `Business\Settings`; each Pro gate sets correct feature string: `'ai'`, `'export'`, `'comments'`, `'recurring'`, `'business'`, `'team'`; `x-upgrade-modal` component rewrote to support 6 feature types with feature-specific headings; all 6 features now use consistent gold/amber scheme (`from-amber-400 to-amber-500` gradient, `bg-amber-400/10` icon bg, `bg-amber-400 hover:bg-amber-300 text-gray-900` CTA); price updated to $5/mo; dismiss uses `$set('upgradeModalFeature', '')`

- **Tab reorder** — book detail tabs reordered to: Entries | Activity (both free) | Reports Pro | Recurring Pro; Activity tab moved from 4th to 2nd position so free features are grouped first

- **Activity log extended** — `BookActivityLog::describe()` + `iconType()` extended with 8 new action types: `comment_added`, `comment_deleted`, `attachment_added`, `attachment_removed`, `recurring_created`, `recurring_paused`, `recurring_resumed`, `recurring_deleted`; `logActivity()` calls added in `Book\Show` for: `addComment()`, `deleteComment()`, `toggleRecurringStatus()` (paused/resumed), `deleteRecurring()`; icon colors: comments/attachment_added/recurring_created → green (created); recurring_paused/resumed → blue (updated); comment_deleted/attachment_removed/recurring_deleted → red (deleted)

- **Delete comment confirmation modal** — replaced browser `wire:confirm` on delete comment button with proper in-app modal; `Book\Show` adds `$showDeleteCommentModal`, `$pendingDeleteCommentId`, `$pendingDeleteCommentExcerpt`; `confirmDeleteComment(string $id)` loads excerpt + sets modal; `deleteComment()` now takes no args (uses pending property); modal matches delete entry modal style (red warning banner + comment excerpt preview + Yes Delete / Cancel)

- **Flash messages** — `dispatch('entry-saved', message: '...')` toasts added to: `addComment()` ("Comment added."), `deleteComment()` ("Comment deleted."), `toggleRecurringStatus()` ("Recurring rule paused." / "Recurring rule resumed."), `markAllRead()` in NotificationBell ("All notifications marked as read."), `deleteNotification()` ("Notification dismissed.")

- **Notification bell sidebar row** — `NotificationBell` Livewire component gains `public bool $sidebar = false` prop; when `$sidebar = true` renders as full-width row (`w-full px-3 py-2.5 gap-3 rounded-xl`) matching Dark Mode toggle style exactly (same font, same padding, icon at same 18px size, label inline); `app.blade.php` sidebar uses `<livewire:notification-bell :sidebar="true" />`; mobile top bar continues using compact `w-9 h-9` icon button

- **Entry ordering stability** — fixed non-deterministic PostgreSQL ordering when multiple entries share the same `date`; three-level sort added: `->orderBy('date', 'asc')->orderBy('created_at', 'asc')->orderBy('id', 'asc')`; UUID `id` as final tiebreaker ensures the same row sequence is returned consistently across re-fetches after delete/edit

### Completed this session (2026-03-20)

- **Book modal redesign** — `bookPeriodPicker(initStart, initEnd)` Alpine factory function (no `$wire` calls — stores dates in Alpine state only, passes as method args on submit); all three modals (Create / Edit / Duplicate) share the same factory; `x-init="$nextTick(() => { show = true; initFlatpickr($refs.x, $refs.y); })"` on each modal; preset tiles use Alpine object-syntax `:class` (reliable with `/` and `:` class names); `Business\Show.php` + `render()` provides `$presets`, `$editPresets`, `$dupPresets` and all edit/dup properties; `saveEditBook()` + `executeDuplicate()` dispatch `book-saved` toast; `executeDuplicate()` copies categories/paymentModes/entries based on checkbox booleans; book row hover reveals edit (pencil) + duplicate (copy) icons via Tailwind `group`/`group-hover`

- **Entry creator attribution** — `database/migrations/2026_03_20_100001_add_created_by_to_entries_table.php` adds nullable UUID `created_by` FK → users with `nullOnDelete`; `Entry::$fillable` includes `created_by`; `Entry::creator()` BelongsTo; `doSaveEntry()` stamps `auth()->id()` on new entries only; `Book\Show::render()` adds `->with('creator')`; desktop rows show `<p class="text-[11px] ... dark:text-slate-600">by You / by [Name]</p>` under description; mobile rows show `· by You / by [Name]` inline

- **Dark mode flash fix** — `theme-transition` CSS in `resources/css/app.css` applies `!important` transitions to all elements/pseudo-elements during toggle; `toggleTheme()` in `app.blade.php` adds the class → toggles `dark` → removes after 300ms; eliminates the stark flash when switching between navy-dark and slate-light themes

- **Preset tile highlighting fix** — switched all three book modals from Alpine string-ternary `:class` to object-syntax `:class`; string ternary is unreliable with class names containing `/` (opacity modifiers) and `:` (variant prefixes); object syntax guarantees correct add/remove per Alpine's class diffing algorithm

- **Dark mode toggle polish** — sun/moon icons changed from `x-show="darkMode"` / `x-show="!darkMode"` to `dark:block hidden` / `dark:hidden`; label from `x-text` ternary to two static spans with `dark:hidden`/`hidden dark:inline`; pill/knob from Alpine `:class` to `bg-gray-300 dark:bg-primary` / `translate-x-0.5 dark:translate-x-4` — all dark-mode logic now handled by CSS, zero Alpine reactive bindings on the toggle button

### Completed this session (2026-03-19)

- **Book audit log (Free)** — `book_activity_log` table (UUID PK, book_id FK cascadeOnDelete, user_id FK cascadeOnDelete, action string, entry_id nullable UUID no-FK, meta JSON, timestamps; composite index on `book_id + created_at`); `BookActivityLog` model with `describe()` (human-readable action string) and `iconType()` (created/updated/deleted/bulk); `private logActivity()` helper in `Book\Show` wrapped in try/catch so it never breaks user actions; 8 log points: `entry_created`, `entry_updated`, `entry_deleted`, `bulk_delete`, `bulk_move`, `bulk_copy`, `bulk_copy_opposite`, `bulk_change_category`, `bulk_change_payment_mode`; lazy-loaded in `render()` only when `$activeTab === 'activity'` (limit 100, newest first, with user eager-load); "Activity" tab button added as 4th tab (no Pro gate); feed shows avatar (initials circle, primary for self/slate for others), colored action dot (green/blue/red/amber), bold "You"/"Name" + `describe()` text, monospace amount in green/red, sub-detail description, relative timestamp; empty state with clock icon; "100 most recent" footer note when limit hit



- **Invitation accept screen redesign** — removed own full-page layout wrapper (`min-h-screen dark:bg-navy bg-gray-50`) that was covering the guest layout's always-dark right panel with a white/gray background in light mode; rewrote all states (expired, accepted, already_member, pending) to render directly in the `$slot` of `layouts.guest`, styled for the dark panel (no `dark:` prefixes needed, white text, slate borders); added "Team Invitation" pill badge matching landing page style; improved viewer permissions list (added "No edit access" with X indicator); pending state uses `anim-fade-up` like login/register; button loading state scoped with `wire:target="accept"`

- **Dashboard card dark mode fix** — removed opacity modifiers (`/40`, `/60`) from `dark:border-slate-*` and `dark:bg-slate-*` classes on both "My Businesses" and "Shared with Me" cards and their stats strips; root cause: JIT does not reliably compile `dark:border-slate-700/40` — only the plain `dark:border-slate-700` form compiles reliably; also fixed `hover:dark:border-*` → `dark:hover:border-*` (correct Tailwind variant stacking order); result: cards now have visible dark mode outer borders and stats strip background/separator lines in both card types

### Completed this session (2026-03-18)

- **AI auto-categorization (Pro)** — `wire:blur` on description input → `Book\Show::suggestCategory()` → `AiService::suggestCategory()` (Claude Haiku text API, ~120 input + ~15 output tokens, ~$0.00016/call) → `$aiCategorySuggestion` + `$showCategoryChip` properties → violet "AI suggests: [Category]" chip fades in below field with accept + dismiss buttons; `applyAiCategory()` copies suggestion to `$entryCategory`; chip skipped if category already set or description < 3 chars; silent catch on API failure; free users: chip never shown; logs to `ai_usage_logs` with type 'categorize'

- **Global action toast** — replaced all per-action success feedback with a single `wire:ignore` toast div (always in DOM, `display:none` initially), triggered via `$this->dispatch('entry-saved', message: '...')` from PHP and caught by Alpine `x-on:entry-saved.window`; `wire:ignore` prevents Livewire morphdom from resetting Alpine's display state; covers: add entry ("Entry added successfully."), edit entry ("Entry edited successfully."), save & add new ("Saved. Continue adding more entries."), delete entry ("Entry deleted."), bulk delete, bulk move, bulk copy, bulk copy opposite, change category, change payment mode; replaced old inline `bulkSuccessMessage` banner system entirely

- **Delete entry modal** — replaced inline row "Delete? Yes/No" Alpine confirm with a proper modal: `confirmDeleteEntry(string $id)` loads entry details into `$pendingDelete*` properties + sets `$showDeleteEntryModal = true`; modal shows red warning banner + entry details card (type, amount, date, description); `deleteEntry()` now takes no args (uses `$pendingDeleteEntryId`); "Yes, Delete" button (red outlined) + "Cancel" button (blue solid); dispatches toast on confirm

- **Pro badge cleanup** — Pro badges (`bg-amber-100 dark:bg-amber-500/15`) now wrapped in `@if(!$business->isPro())` on: Reports tab, Recurring tab, Export Book dropdown header, recurring toggle in slide-over; Pro users see clean UI with no upsell noise

- **AI cash flow insights (Pro)** — auto-generates on Reports tab open (two-request pattern: `updatedActiveTab()` sets `$aiInsightsLoading = true` → shimmer renders → `wire:init="generateInsights"` fires API call); `AiService::generateInsights()` sends aggregated totals/categories only (never raw descriptions); cross-book comparison via previous book lookup (`period_ends_at < current.period_starts_at`); 24h cache in `books.ai_insights_cache` + `books.ai_insights_generated_at`; rate limits: 1/min burst (`RateLimiter`) + 10/day cap (`AiUsageLog` count); UI states: loading shimmer, not_enough_data (<3 entries), failed (API error + Retry), limit reached (stale cache shown if available), loaded (sentiment badge + reason + 3 bullets + tip + regenerate button + timestamp); regenerate button hidden when daily limit hit; inline limit warning on loaded card; `sanitiseInsights()` strict whitelist; dark/light mode fully verified; `database/migrations/2026_03_18_000001_add_ai_insights_to_books_table.php`

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
- **Dark mode border/bg opacity modifiers:** `dark:border-slate-700/40`, `dark:bg-slate-800/40` etc. do NOT reliably compile — always drop the opacity modifier and use the plain form (`dark:border-slate-700`, `dark:bg-slate-800`). This applies to ALL `dark:border-*` and `dark:bg-*` with `/N` opacity modifiers. Plain forms are already in the safelist.
- **Tailwind variant stacking order:** `dark:hover:class` (dark first, then hover) is correct; `hover:dark:class` (hover first) may not generate the expected selector — always put `dark:` before state variants
- **Guest layout right panel is always dark:** `bg-dark` is applied unconditionally — Livewire views rendered via `->layout('layouts.guest')` should NOT add their own `min-h-screen dark:bg-navy bg-gray-*` wrapper; style directly for dark (no `dark:` prefixes needed inside the slot)
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
