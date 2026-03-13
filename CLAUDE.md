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
- **Local** — use PKR as default currency display in Pakistan-facing copy

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
