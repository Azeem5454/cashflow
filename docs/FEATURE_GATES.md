# CashFlow — Feature Gates (Free vs Pro)

**Last updated:** 2026-04-11
**Price:** Free $0/mo · Pro **$5/mo** (monthly only)

This is the canonical source of truth for what each plan includes. Anytime a feature is added, moved, or re-gated, update both this doc and the code gate at the same time.

---

## At-a-glance

| Category | Free | Pro |
|---|---|---|
| **Account** | ✅ | ✅ |
| Sign up, email verification, password reset | Yes | Yes |
| Multiple businesses (as owner) | 1 | Unlimited |
| Team members per business | 2 max | Unlimited |
| **Core bookkeeping** | | |
| Unlimited books per business | Yes | Yes |
| Unlimited entries per book | Yes | Yes |
| Running balance + summaries | Yes | Yes |
| Categories & payment modes | Yes | Yes |
| Entry references & notes field | Yes | Yes |
| Receipt photo / PDF attachments per entry | Yes | Yes |
| Book audit log (who did what) | Yes | Yes |
| Search & type/duration filters | Yes | Yes |
| **Bulk operations** (delete, move, copy, flip type) | Yes | Yes |
| Mobile app access | Yes | Yes |
| **Sharing** | | |
| Invite teammates (owner, editor, viewer roles) | Up to 2 total | Unlimited |
| **Pro-only features** | ❌ | ✅ |
| Unlimited businesses | No | Yes |
| Unlimited team members | No | Yes |
| PDF export | No | Yes |
| CSV export | No | Yes |
| Reports tab (trend chart, category breakdown, payment mode breakdown) | Blurred preview | Full |
| Date range filter + period comparison | No (type/duration only) | Yes |
| Recurring entries (auto-generated daily/weekly/monthly) | No | Yes |
| Email reports (weekly/monthly to chosen recipients) | No | Yes |
| Entry comments / team collaboration on entries | No | Yes |
| **AI receipt OCR** (scan photo → auto-fill entry) | No | Yes · 200 scans/month |
| **AI auto-categorization** (suggests category from description) | No | Yes · unlimited |
| **AI cash flow insights** (plain-English summary of the period) | No | Yes · 10/day cache |
| Priority email support | No | Yes |

---

## Why these gates (design rationale)

**Free is intentionally generous** — the core bookkeeping loop (add entry, see balance, attach receipt, audit who-did-what) is fully free. We win by making the free product good enough to rely on.

**Pro unlocks three value tiers:**
1. **Growth** — run multiple businesses, bring in your accountant (unlimited team)
2. **Analysis** — reports, date ranges, email summaries, exports
3. **Automation & AI** — recurring entries, receipt OCR, auto-categorization, insights

---

## Where each gate lives in code

Every Pro-gated feature has a gate in both the UI (Livewire) and the API (controllers). Missing either side is a security hole.

| Feature | UI gate | API gate |
|---|---|---|
| Multiple businesses | `app/Livewire/Business/Create.php:28,37` · `routes/web.php:32` | `app/Http/Controllers/Api/V1/BusinessController.php:131` |
| Team member limit (2) | `app/Livewire/Business/Settings.php:69` · `app/Livewire/Invitation/Accept.php` (accept-time re-check) | `app/Http/Controllers/Api/V1/BusinessController.php:203` |
| Export PDF/CSV | `app/Livewire/Book/Show.php:987,997` | `app/Http/Controllers/ExportController.php:22` · `app/Http/Controllers/Api/V1/BookController.php:365` |
| Reports tab | `app/Livewire/Book/Show.php:178,201,234` | `app/Http/Controllers/Api/V1/BookController.php:310` |
| Date range comparison | `app/Livewire/Book/Show.php:944` | `app/Http/Controllers/Api/V1/BookController.php:310` (reportData) |
| Recurring entries | `app/Livewire/Book/Show.php:602,1674` · `app/Console/Commands/GenerateRecurringEntries.php:27` | `app/Http/Controllers/Api/V1/BookController.php:479` |
| Email reports | `app/Livewire/Book/Show.php:1341,1372` · `app/Console/Commands/SendEmailReports.php:30` | `app/Http/Controllers/Api/V1/BookController.php:573` |
| Entry comments | `app/Livewire/Book/Show.php:1453,1498,1557,1585` | (to be added — currently read-only allowed for all) |
| AI receipt OCR | `app/Livewire/Book/Show.php:679` | `app/Http/Controllers/Api/V1/EntryController.php:106` |
| AI auto-categorization | — | `app/Http/Controllers/Api/V1/BookController.php:479` (suggestCategory) |
| AI insights | `app/Livewire/Book/Show.php` (insights tab) | `app/Http/Controllers/Api/V1/BookController.php:200` (aiInsights) |

---

## Plan transitions — what happens automatically

**Free → Pro** (user subscribes):
- Stripe webhook fires `customer.subscription.created` or `customer.subscription.updated`
- `app/Providers/AppServiceProvider.php:83` sets `user.plan = 'pro'`
- All Pro features immediately unlock
- No data migration needed

**Pro → Free** (subscription cancelled, ended, or unpaid):
- Webhook fires `customer.subscription.deleted` (or `updated` with status `canceled`/`unpaid`/`incomplete_expired`)
- `app/Providers/AppServiceProvider.php:86-103` sets `user.plan = 'free'` **AND**:
  - Pauses all **recurring entries** in books owned by this user (`RecurringEntry::update(['status' => 'paused'])`)
  - Deactivates all **email report schedules** in books owned by this user (`ReportSchedule::update(['is_active' => false])`)
- Pro data (reports, past AI insights, past exports) is retained but becomes read-only / blurred

**Admin-forced Free** (`/admin/users/{id}` → Force Free):
- Same as above — also pauses recurring + email reports

**Free user exceeding a cap** (e.g., team member limit):
- Cannot add new members, but existing members stay (no auto-kick)
- Invitations pending at the time of downgrade will fail at accept time with "Team Full" message (`app/Livewire/Invitation/Accept.php`)

---

## Security — "bypass attempts" that are blocked

1. **Direct API calls without UI** — all Pro-gated endpoints re-check `$business->isPro()` at the server. A Free user with a valid Sanctum token cannot call `/api/v1/books/{id}/insights` and get AI insights.
2. **Spoofed success URL** — visiting `/settings/billing?checkout=success` manually does NOT flip you to Pro. `app/Livewire/Settings/Billing.php:mount()` verifies via `user->stripe()->subscriptions->all()` that Stripe has an active subscription before updating the local plan.
3. **Pending invitation abuse** — if owner was Pro, invited 5 people, then downgraded to Free, pending invites fail at accept time (`app/Livewire/Invitation/Accept.php:isOverSeatLimit`).
4. **Cross-business data access** — every query scopes to `$user->businesses()` — a user cannot see/modify entries in a business they aren't a member of.
5. **Created-by attribution** — entries store `created_by` UUID; audit log records who did what.

---

## Decisions made (open questions resolved)

| Feature | Decision | Rationale |
|---|---|---|
| Receipt attachments | **Free** | Core bookkeeping need — users expect this. Storage cost is minimal. |
| Audit log | **Free** | Trust/accountability feature — makes team collaboration safer. Withholding this from free teams is hostile. |
| Bulk operations | **Free** | Essential for cleanup (especially on import/migration). Building this is moderate effort but the UX value is huge for onboarding. Pro is differentiated enough via AI + exports + team. |
| Mobile app | **Free** | Same tier as web — a mobile-only Pro tier would split the user base weirdly. |
| Categories / payment modes | **Free** | Core UX — withholding makes the app feel crippled. |

---

## Pending work

- [ ] **Add API gate to entry comments** — currently `POST /entries/{id}/comments` has no Pro check (UI enforces it). Free users with an API token could add comments bypassing the UI modal.
- [ ] **Add API gate to invitation creation** (web side) — the Livewire `Business/Settings.php:69` check is sufficient for web, but if we ever expose the POST from JS outside the modal, the server-side check needs to stay tight. Already tight; just noted for future audits.
- [ ] **Trial period** — currently no trial. Decision: do we want a 7-day Pro trial? Pro: higher conversion. Con: fraud + "churn-before-charge" users. Defer until we have ≥1k signups and real data.
- [ ] **Annual plan** — removed from landing page (previously showed $4/mo toggle); can reintroduce once we have a live Stripe annual price configured. See `services.stripe.pro_price_id` config.

---

## For developers adding new features

If your feature is **Pro only**:

1. **UI (Livewire)** — at the top of the method that triggers the feature:
   ```php
   if (! $this->business->isPro()) {
       $this->upgradeModalFeature = 'your_feature_key';
       return;
   }
   ```
   Add a case to `resources/views/components/upgrade-modal.blade.php` for your feature key.

2. **API (controller)** — at the top of the endpoint:
   ```php
   if (! $book->business->isPro()) {
       return response()->json(['message' => 'Pro subscription required.'], 403);
   }
   ```

3. **Mobile** — display the feature as "blurred/gated" for Free users via the `isPro` field on the business or user resource.

4. **Downgrade behavior** — if your feature persists state (like recurring entries, email schedules), add a handler to `AppServiceProvider.php:86` that pauses/disables the feature's state when user downgrades.

5. **Update this document** and the `Subscription Plans` table in `CLAUDE.md`.
