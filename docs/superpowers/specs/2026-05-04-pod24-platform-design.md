# Pod24 Booking Platform — Design Spec

**Date:** 2026-05-04
**Status:** Draft for review
**Owner:** Kamel Asmar
**Replaces:** Booqable booking integration on the existing landing-page mockup

---

## 1. Overview

Pod24 is twofour54's portable podcast studio. Today, the public site is a static mockup ([latest version](../../../../swaplyst_backend/.superpowers/brainstorm/14322-1777029836/content/mockup-full.html)) and bookings live in Booqable on a separate domain. This spec defines a from-scratch Laravel-based booking platform that replaces Booqable, hosts the marketing page, and runs the full booking lifecycle end-to-end.

**Primary goals:**
- Self-serve B2C booking for Abu Dhabi onsite sessions, payment to confirmation in under 90 seconds.
- A catalog model that supports multiple facilities, multiple service tiers, and multiple package types — even though MVP launches with one facility (Pod24 portable).
- Admin panel for non-technical staff to manage availability, pricing, content, and quote pipelines.
- Marketing automation hooks (Mailchimp, Hubspot) for retargeting, reminders, and lifecycle engagement.
- Off-site / B2B intake routed to a quote pipeline for manual handling.

**Demo deployment:** `pod24.kamelasmar.com`
**Production deployment:** `pod24.twofour54.com` (env-driven; flip via `APP_URL` + DNS, no code change)

---

## 2. Out of scope (explicit — these are post-MVP phases)

- Multi-language (UI is English-only at launch; data model is i18n-ready so Arabic can be added without schema changes)
- Customer self-serve dashboard beyond minimum (booking history, file downloads, basic profile, reschedule, cancel)
- Native mobile app
- Multi-currency (AED only)
- Automated B2B quoting / pricing engine for off-site shoots — these stay manual through the quote pipeline
- Loyalty / referral programs
- A peer-to-peer studio marketplace
- Real per-operator scheduling (we use facility capacity caps; individual operator assignment is admin-side and out-of-system)

---

## 3. Architecture

**Single Laravel 11 monolith on AWS Lightsail.**

| Layer | Choice | Why |
|---|---|---|
| Framework | Laravel 11 + PHP 8.3 | Mature, idiomatic, Filament/Livewire-compatible |
| Database | PostgreSQL 16 (on-instance for MVP) | Strong date/time handling, JSON columns for translatable fields |
| Cache & queues | Redis (on-instance) + Laravel Horizon | Standard Laravel queue stack |
| Admin panel | Filament v3 | Saves weeks vs hand-rolling; covers all CRUD admin needs |
| Public site & booking flow | Blade + Livewire v3 + Tailwind | SSR-friendly, fast, no separate SPA |
| Payments | Stripe (Cashier for hour-pack subscriptions, direct PaymentIntents for bookings) | User-specified |
| File storage | S3 (eu-central-1 or me-central-1) | Photos, file delivery |
| Transactional email | SendGrid | User-specified |
| Marketing automation | Mailchimp + Hubspot | User-specified |
| Hosting | AWS Lightsail Ubuntu 22.04 (~$20–40/mo) | User-specified |
| Deployment | Laravel Forge "Custom VPS" | SSL, queue workers, deployment automation |
| Anti-spam | Cloudflare Turnstile + honeypot | Forms only |
| Errors / monitoring | Sentry | Standard |

**One box for MVP.** Separating into RDS + ElastiCache + EC2 is unnecessary at expected traffic. Splitting later is a configuration change, not an architecture rewrite.

---

## 4. Bounded contexts (modules)

The codebase is organized as 9 modules under `app/Modules/{Name}/`. Each module exposes Models, Actions (single-purpose service classes), Events, and Filament resources.

| Module | Purpose | Key entities |
|---|---|---|
| **Catalog** | Bookable products and pricing config | Facility, ServiceTier, FacilityPricing, Addon, HourPack |
| **Pricing** | Compute booking totals from inputs | (no entities; service classes only) |
| **Availability** | Open hours, blackouts, capacity caps, slot lookup | AvailabilityRule, AvailabilityBlackout, AvailabilityCapacity |
| **Booking** | Reservation lifecycle | Booking, BookingAddon, BookingHold |
| **Customers** | User accounts, hour-pack balances | User, HourPackTransaction |
| **Payments** | Stripe charges, refunds, webhooks | (Stripe resources; no app entities beyond ledger) |
| **Quotes** | B2B + off-site pipeline | Quote |
| **Notifications** | Transactional email orchestration | (mailables, listeners) |
| **Integrations** | Mailchimp + Hubspot sync | IntegrationSyncFailure |
| **Content** | Editable landing-page bits (FAQ, testimonials, use cases) | FaqItem, Testimonial, UseCase |

---

## 5. Data model

All `*_json` columns use `spatie/laravel-translatable` (JSON-backed) so a second locale can be added without migration. Money is stored as integer minor units (`*_aed_cents`) to avoid float drift.

### 5.1 Catalog

```
facilities
  id, slug, name_json, description_json, address_json,
  is_active, sort_order, photo_media_id (via medialibrary),
  created_at, updated_at

service_tiers
  id, facility_id, name, base_hourly_rate_aed_cents,
  description_json, sort_order, is_active

facility_pricing             -- explicit per-cell pricing
  facility_id, service_tier_id, package_type, hours,
  price_aed_cents
  PRIMARY KEY (facility_id, service_tier_id, package_type)
  -- package_type ∈ {hourly, half_day, full_day, multi_day}
  -- For multi_day: price is per-day; total = price × number-of-days
  -- For hourly: hours column = 1; total = price × hours-booked

addons
  id, facility_id, name_json, description_json,
  price_aed_cents, is_active, sort_order

hour_packs
  id, facility_id, hours, price_aed_cents,
  expiry_days, name_json, description_json, is_active

pricing_modifiers
  id, facility_id,
  type ENUM('weekend','after_hours'),
  percentage,                  -- e.g. 25 = +25%
  -- For after_hours, additional config:
  after_hours_start TIME NULL, after_hours_end TIME NULL
```

### 5.2 Availability

```
availability_rules            -- weekly recurring open hours
  id, facility_id, day_of_week (0-6), open_time, close_time

availability_blackouts        -- one-off closures
  id, facility_id, starts_at, ends_at, reason

-- NOTE: simplification vs. original spec — `availability_capacities` was
-- collapsed into a `max_concurrent_per_day` column directly on `facilities`.
-- See § 7 for how capacity is used in availability logic.
```

### 5.3 Bookings

```
bookings
  id, ulid, facility_id, customer_id NULLABLE,
  service_tier_id, package_type,
  starts_at, ends_at, total_hours,
  status ENUM('hold','pending_payment','confirmed','completed','cancelled'),
  contact_name, contact_email, contact_phone,
  address_json,                -- includes city, must be Abu Dhabi to confirm
  subtotal_aed_cents,
  weekend_markup_aed_cents,
  after_hours_markup_aed_cents,
  addons_aed_cents,
  hour_pack_credits_used,      -- in hours
  hour_pack_credit_value_aed_cents,
  vat_aed_cents,
  total_aed_cents,
  stripe_payment_intent_id NULLABLE,
  hold_expires_at NULLABLE,
  paid_at NULLABLE,
  cancelled_at NULLABLE, cancelled_by ENUM('customer','admin') NULLABLE,
  refund_amount_aed_cents NULLABLE,
  marketing_consent_at NULLABLE,    -- captured at checkout; copied to users.marketing_consent_at if account is created/used
  created_at, updated_at

booking_addons
  id, booking_id, addon_id, qty, price_at_booking_aed_cents
```

### 5.4 Customers & hour packs

```
users                          -- single users table; admin staff distinguished by roles
  id, email, name, phone, password NULLABLE, email_verified_at,
  marketing_consent_at NULLABLE, timezone, created_at, updated_at

hour_pack_transactions         -- ledger pattern; balance = sum(hours)
  id, customer_id, facility_id, hours,             -- positive = credit, negative = debit
  type ENUM('purchase','redeem','expire','admin_adjust'),
  booking_id NULLABLE, stripe_charge_id NULLABLE,
  expires_at NULLABLE,           -- only set on type='purchase'
  notes, created_at
```

### 5.5 Quotes

```
quotes
  id, ulid, type ENUM('b2b','offsite'),
  status ENUM('new','contacted','quoted','won','lost'),
  contact_name, contact_email, contact_phone, company NULLABLE,
  event_type, dates_text, message,
  source_address_json NULLABLE, -- for offsite redirects
  created_at, updated_at
```

### 5.6 Cancellation policy

```
cancellation_policies          -- per-facility tiered refund schedule
  id, facility_id,
  hours_before_min,             -- e.g., 168 (= 7 days)
  refund_percentage             -- e.g., 100
  -- Applied: pick the row with largest hours_before_min ≤ actual hours-before
```

### 5.7 Integrations

```
integration_sync_failures
  id, provider ENUM('mailchimp','hubspot'),
  event_name, payload_json, error_message, retry_count,
  resolved_at NULLABLE, created_at
```

### 5.8 Content

```
faq_items, testimonials, use_cases
  -- All have: id, question_json/quote_json, answer_json/title_json,
  -- sort_order, is_published
```

---

## 6. Pricing engine

The pricing engine is a single `CalculateBookingPrice` action that takes a `BookingDraft` value object and returns a `PriceBreakdown`. Pure function, no DB writes — ideal for unit testing.

**Inputs:** facility_id, service_tier_id, package_type, starts_at, ends_at, addons[], hour_pack_credits_to_redeem, customer_id (optional, for pack balance).

**Output:**
```
PriceBreakdown {
  base_aed_cents              -- from facility_pricing lookup
  weekend_markup_aed_cents
  after_hours_markup_aed_cents
  addons_aed_cents
  hour_pack_credit_value_aed_cents (negative)
  subtotal_aed_cents
  vat_aed_cents               -- subtotal × 5%
  total_aed_cents
}
```

**Algorithm:**

1. **Base price lookup** by (facility, tier, package_type). For `multi_day`, multiply per-day price × number of days.
2. **For hourly bookings:** `base = hourly_price × hours`.
3. **Weekend markup:** count hours falling on Sat or Sun (Asia/Dubai time). Add `(weekend_hours × hourly_price × weekend_modifier_pct)`.
4. **After-hours markup:** count hours falling outside facility business hours window (default before 09:00 / after 18:00, configurable per facility). Add same way.
5. **Add-ons:** sum `addon.price × qty` for each.
6. **Hour-pack credit redemption:** if customer has balance, credits redeemed are valued at the facility's `Recording Only` hourly rate (the base tier — even if booking is on a higher tier, customer pays the tier-uplift in cash and the base hours come out of the pack). This rule is documented for the customer.
7. **Subtotal** = base + markups + addons − pack-credit-value.
8. **VAT** = round(subtotal × 0.05).
9. **Total** = subtotal + VAT.

**TDD coverage:** every combination of (tier × package × weekend × after-hours × addons × pack-redemption) covered by Pest unit tests. This is the highest-test-coverage module in the codebase.

---

## 7. Availability logic

`FindAvailableSlots(facility_id, date_range, package_type)` returns selectable slots.

**For a candidate booking window:**
1. Window must lie inside `availability_rules` for the day-of-week.
2. Window must not overlap any `availability_blackouts`.
3. Count of `bookings` overlapping that day with status ∈ {`hold`, `pending_payment`, `confirmed`} must be < `facilities.max_concurrent_per_day` for the facility.
4. The window must be ≥ now + 24 hours (configurable lead time).

**Concurrency control during checkout:**
- Step 3 of the booking wizard creates a `BookingHold` row inside a serializable transaction:
  - `SELECT FOR UPDATE` on the `facilities` row (capacity now lives on `facilities.max_concurrent_per_day`)
  - Re-check capacity
  - Insert booking with `status='hold'`, `hold_expires_at = now() + 15 min`
  - Commit
- Two simultaneous customers racing for the last slot: one wins, the other sees "Slot just taken — pick another."
- A scheduled job (every minute via Horizon) releases expired holds (`status='hold'` AND `hold_expires_at < now()`).

---

## 8. Booking flow (B2C self-serve)

```
/                          Marketing page (Blade with Livewire CTAs)
/book                      Livewire wizard. State persisted in URL query string
                           so refresh-safe. Each step has its own component.

  Step 1  Facility           Pre-selected if only one active facility
  Step 2  Service tier       Recording / Live Mix / +Edit / +Stream
  Step 3  Package + date     Hourly / Half-day / Full-day / Multi-day
                            + calendar (live availability, capacity-aware)
                            + time slot selection
  Step 4  Address             ZIP/city; if not Abu Dhabi → redirect to
                              /quote/offsite with prefill
  Step 5  Add-ons              Optional
  Step 6  Contact + auth       Guest details OR login OR create account
                              (Hour-pack redemption option appears here if
                              customer is logged in and has balance)
                              Marketing consent checkbox: PRE-CHECKED
  Step 7  Payment              Stripe Payment Element. Booking transitions:
                              hold → pending_payment → confirmed (on webhook)
  ✓       Confirmation         Page + SendGrid email with .ics calendar invite
                              + Mailchimp/Hubspot events fired
```

**Server-side validation guards every step.** Client-side state is untrusted; the price shown at checkout is recomputed server-side at payment confirmation.

**Form state durability:** the Livewire wizard caches state in the URL query string and `localStorage`. A user who navigates away and returns within 24 hours can resume.

---

## 9. Hour-pack purchase flow

Account-required. Hour packs can only be bought by signed-in customers; guest checkout is not supported for packs (because we need to attach the balance to a user identity).

```
/account/packs              Lists active hour_packs for the facility
                            Customer picks pack → Stripe Checkout
                            On checkout.session.completed webhook:
                              - Insert hour_pack_transactions (type=purchase, hours=+N, expires_at)
                              - Send SendGrid receipt
                              - Fire HourPackPurchased event → MC/HS sync
```

**Balance lookup:**
```
balance(customer_id, facility_id) =
  SUM(hours) FROM hour_pack_transactions
  WHERE customer_id=? AND facility_id=?
    AND (expires_at IS NULL OR expires_at > now())
```

**Expiry job:** nightly cron job inserts `type=expire` rows that zero out the expired credits, and sends a SendGrid notice 7 days before expiry.

---

## 10. Off-site / B2B quote flow

**Two entry points:**
1. `/brands` — landing-page form (B2B activations, conferences)
2. Step 4 redirect — customer entered an address outside Abu Dhabi during booking, gets redirected with prefilled data marked `type=offsite`

**Pipeline:**
```
new → contacted → quoted → won / lost
```

- New quote lands in Filament admin "Quotes" inbox. `QuoteSubmitted` event fired (synced to MC/HS).
- Admin contacts customer manually (email/phone), updates status, takes notes (Filament).
- On status='won', admin manually creates a `Booking` from the quote (Filament action). Booking has `customer_id` linked to the quote contact (account auto-created if needed). Stripe payment link emailed for the manual booking total.
- On status='lost', win-back flow eligibility tag set in MC/HS.

---

## 11. Cancellation & reschedule

**Cancellation:**
- Customer logs in (or uses magic-link from booking email) → `/account/bookings/{ulid}` → Cancel button.
- System looks up `cancellation_policies` for the facility, picks the matching tier based on hours-until-`starts_at`.
- Issues Stripe refund for the computed amount.
- `BookingCancelled` event fired.
- **Default policy (admin-editable per facility):**
  - ≥ 168 h (7 days) before: 100%
  - 72–168 h before: 50%
  - < 72 h before: 0%

**Reschedule:**
- One free reschedule allowed up to 48h before `starts_at`.
- Implementation: update the existing booking's `starts_at` / `ends_at` in place; append a `booking_status_changes` audit row; the Stripe charge stays with the same booking row.
- **Constraint to keep MVP simple:** reschedule is only allowed when the new slot's total price exactly matches the original. If the new slot would change the price (different weekend/after-hours coverage, different facility availability that requires repricing), the UI tells the customer to cancel + rebook instead. This avoids partial refunds + new charges in the same flow.
- Subsequent reschedules within the 48h-before window: not free; handled as cancel + new booking through the policy.

```
booking_status_changes        -- audit trail for reschedules and admin edits
  id, booking_id,
  changed_by ENUM('customer','admin','system'),
  field_changed, old_value_json, new_value_json,
  reason, created_at
```

---

## 12. File delivery

Customers receive their broadcast-ready files within 24h of session end.

**Mechanism:**
- After session, admin uploads files in Filament against the booking. Stored in S3 with private ACL.
- A `BookingFilesReady` event triggers a SendGrid email with a magic-link login + a link to `/account/bookings/{ulid}/files`.
- That page renders signed S3 URLs (24-hour expiry, regenerated on each pageview).
- No direct public URLs anywhere.

---

## 13. Marketing & integrations

### 13.1 SendGrid (transactional)

Configured as Laravel mail driver. Used for:
- Booking confirmation
- Booking reminder (24h before, scheduled job)
- Reschedule / cancellation notice
- File-ready notification
- Magic-link login
- Hour-pack purchase receipt
- Hour-pack expiry warning (7 days out)
- Stripe refund receipt
- Quote auto-acknowledgment

All system-generated. Templates live in `resources/views/mail/`. SendGrid dynamic templates not used (keep templates in the repo).

### 13.2 Mailchimp + Hubspot (marketing automation)

**Direction:** outbound only. App is source-of-truth.

**Sync trigger:** all events listed below dispatch a `SyncToMailchimp` and `SyncToHubspot` job onto the `integrations` queue. Failures land in `integration_sync_failures` with manual resync via Filament.

**Events synced to BOTH platforms:**

| Event | When | MC tag / HS deal action |
|---|---|---|
| `CustomerCreated` | Account signup or guest booking | Add to MC audience; HS upsert Contact |
| `BookingStarted` | Step 1 of `/book` (with email captured) | Tag `booking-in-progress` |
| `BookingAbandoned` | 15-min hold expired without payment | Tag `cart-abandoned` (drives MC retargeting flow) |
| `BookingConfirmed` | Stripe webhook success | Tag `booked-{tier-name}`; HS Deal in "B2C Bookings" pipeline at "Won — Paid", amount = total |
| `BookingReminder24h` | Cron 24h before `starts_at` | Tag `reminder-eligible` (drives MC reminder template) |
| `BookingCompleted` | Cron after `ends_at` | Tag `completed-booking`; HS Deal note |
| `BookingCancelled` | Cancellation processed | Tag `cancelled-{reason}`; HS Deal stage |
| `HourPackPurchased` | Pack checkout success | Tag `pack-buyer`; HS Deal in "Pack Sales" pipeline |
| `HourPackLowBalance` | Balance ≤ 2 hrs (after redemption) | Tag `pack-low-balance` |
| `QuoteSubmitted` | B2B or offsite form | HS Deal in "B2B / Offsite" pipeline at "New Lead"; MC tag `quote-submitted` |
| `NewsletterSubscribed` | Footer signup form | Add to MC "Newsletter" audience |

**Synced contact properties (both platforms):**
`email, name, phone, customer_type [b2c|b2b], total_bookings, total_spend_aed, last_booking_date, preferred_service_tier, hour_pack_balance, location, marketing_consent_at`

**Marketing consent:**
- Checkbox at checkout step 6, **pre-checked** with disclosure text linking to privacy policy.
- Recorded as `users.marketing_consent_at` timestamp.
- If unchecked: only transactional SendGrid mail is sent. Contact NOT pushed to MC/HS.
- Withdrawal in `/account/settings`: triggers `MarketingConsentWithdrawn` job that removes from MC audience and tags HS Contact `unsubscribed`.

### 13.3 Newsletter signup

- Form in landing-page footer (HTMX/Livewire).
- Captures email + optional name.
- Fires `NewsletterSubscribed` event.
- Honeypot + Turnstile.

---

## 14. Auth & roles

**Customers (hybrid):**
- Guest checkout supported for single bookings.
- Optional account creation at end of checkout (one-click).
- Login via password OR magic-link (SendGrid email).
- Customers see only their own bookings/files.

**Staff (separate gate):**
- Filament admin at `/admin`.
- Roles via `spatie/laravel-permission`:
  - `Admin` — full access including pricing, refunds, settings
  - `Coordinator` — bookings, quotes, file uploads, customer view (no pricing/settings)
- 2FA required via Filament's built-in 2FA (TOTP).

---

## 15. Admin panel (Filament)

Resources:
- **Facilities** — CRUD, photos, service tiers nested, pricing matrix editor (UI shows `service_tier × package_type` grid)
- **Service tiers** — managed inside Facilities
- **Add-ons** — CRUD per facility
- **Hour packs** — CRUD per facility
- **Pricing modifiers** — weekend %, after-hours window + %
- **Availability** — open hours editor, blackout calendar, capacity slider
- **Bookings** — list/filter, view details, manual status changes, refund button (Stripe API), file upload
- **Quotes** — pipeline kanban, status updates, "Convert to Booking" action
- **Customers** — list, view bookings, hour-pack balance ledger, manual `admin_adjust` transaction
- **Content** — FAQ items, testimonials, use cases (CMS for landing page)
- **Settings** — `.env`-backed, read-only display of API keys (redacted) and integration health
- **Sync failures** — list, retry buttons, dismiss
- **Cancellation policies** — per-facility tier editor

Dashboard widgets: today's bookings, this week's revenue, sync failure count, expiring packs in next 30 days, new quotes.

---

## 16. Landing page

Direct port of the existing mockup at `/Users/kamelasmar/apps/swaplyst_backend/.superpowers/brainstorm/14322-1777029836/content/mockup-full.html`.

**Conversion approach:**
- Tailwind config matches the mockup's CSS variables (`--accent: #00B9E3`, etc.).
- Each section becomes a Blade component: `<x-pod24.hero>`, `<x-pod24.action-cards>`, `<x-pod24.meet-pod>`, `<x-pod24.included>`, `<x-pod24.book-widget>`, `<x-pod24.b2b-form>`, `<x-pod24.how>`, `<x-pod24.use-cases>`, `<x-pod24.testimonials>`, `<x-pod24.faq>`, `<x-pod24.final-cta>`, `<x-pod24.sticky-cta>`, `<x-pod24.footer>`.
- Interactive components (book widget, B2B form, sticky CTA, FAQ accordion, newsletter form) are Livewire.
- Static-content components pull from `Content` module DB tables (FAQ, testimonials, use cases) so non-devs can edit copy without a deploy.
- Hero video: kept as `<video>` with poster fallback. Hosted on S3 (or Vimeo, per current mockup comment).

The booking widget on the landing page deep-links into `/book` step 3 with the calendar pre-rendered.

---

## 17. i18n readiness

- `spatie/laravel-translatable` on all human-readable text columns.
- Default locale `en`. Locale resolution middleware in place but only `en` is populated.
- Routes are non-prefixed at MVP. Adding Arabic later = add `/ar/...` group + populate the translation column for each row + RTL Tailwind config.
- No frozen-text assumptions in code (no hardcoded English strings outside `lang/en.php`).

---

## 18. Error handling

| Failure | Handling |
|---|---|
| Stripe Payment failure | Booking stays in `pending_payment` until 15-min hold expires; auto-released. Customer sees error + "Try again" CTA. |
| Concurrent slot grab | DB serializable transaction; loser sees "Slot taken — pick another." |
| Stripe webhook retries | Idempotency keys on every webhook handler. Duplicate webhooks are no-ops. |
| Email delivery failure | Horizon retries 3× with exponential backoff. Permanent failure → Sentry alert + admin notification. |
| Quote form spam | Turnstile + honeypot + rate-limit (5 submissions/hour/IP). |
| MC/HS sync failure | 3 retries, then row in `integration_sync_failures` for manual review. |
| Address not in Abu Dhabi | Redirect to `/quote/offsite` with prefilled data. |
| File-delivery URL expired | Customer clicks expired link → magic-link re-auth → fresh signed URL generated. |
| User attempts to redeem more pack hours than balance | Server-side validation rejects; UI prevents in real time. |
| Cancellation outside policy window | Customer sees policy and can choose: cancel for partial/no refund, or reschedule (if eligible). |

---

## 19. Security

- Filament admin gated to `Admin`/`Coordinator` roles + TOTP 2FA mandatory.
- Customer auth rate-limited: 5 attempts/15min/IP.
- Stripe Payment Element — no card data ever touches the server.
- All Stripe webhook calls signature-verified.
- File delivery via signed S3 URLs only; no public buckets.
- All forms protected with Turnstile + honeypot.
- CSRF on all state-changing requests (Laravel default).
- HTTPS enforced; HSTS header set.
- Customer addresses stored in `bookings.address_json` only — no separate searchable PII dossier.
- Logs scrubbed of email addresses and phone numbers (custom Monolog processor).
- Database backups: nightly snapshot via Lightsail's automatic snapshots.

---

## 20. Testing strategy

- **Pest** as the test runner.
- **TDD for Pricing + Booking modules** — every modifier/discount/redemption combo unit-tested before implementation, per the project's superpowers TDD skill.
- **Feature tests** for end-to-end booking flow: hold → payment → confirm → cancel paths.
- **Stripe webhooks** tested with Stripe CLI replays + recorded fixtures.
- **Filament admin** smoke-tested with Pest Browser.
- **Integration syncs** mocked at the HTTP boundary (Saloon or Http::fake) — no live calls in CI.
- **Coverage target:** ≥80% on Pricing + Booking + Availability modules. Lighter on Catalog/Content/Admin (mostly CRUD generated by Filament).
- CI: GitHub Actions running tests on every PR.

---

## 21. Hosting & deployment

- **VPS:** AWS Lightsail Ubuntu 22.04, 2 vCPU / 4 GB RAM ($24/mo) for MVP. Scale up via Lightsail's resize when needed.
- **Forge:** "Custom VPS" mode managing the Lightsail instance via SSH. Handles Nginx, PHP-FPM, MySQL/Postgres install, Redis, queue workers, scheduler, SSL via Let's Encrypt.
- **DNS:** Cloudflare in front. Apex CNAME flatten if needed for `pod24.kamelasmar.com`; A/AAAA for `pod24.twofour54.com` to Lightsail static IP.
- **Deployment:** Git push to `main` → Forge auto-deploys → runs migrations + clears caches + restarts queue workers.
- **Backups:** Lightsail automatic snapshots (daily, 7-day retention). Database `pg_dump` to S3 nightly via cron.
- **Monitoring:** Sentry (errors), Forge built-in (uptime + queue depth), Cloudflare analytics (traffic).
- **Env-driven domain switch:** `APP_URL` env var + `SESSION_DOMAIN` controls demo vs. prod. DNS flip + env update + redeploy = ~5 minutes total.

---

## 22. Risks & open items

| Risk | Mitigation |
|---|---|
| Race conditions on capacity-limited slots | Serializable transactions + `SELECT FOR UPDATE` on capacity row + 15-min hold |
| Stripe webhook downtime | Idempotency + retries; reconcile cron job runs hourly cross-checking confirmed bookings vs. Stripe charges |
| Marketing consent reversal not propagating | Sync removal job fires within 5 min of withdrawal; manual MC/HS audit script available to admin |
| File delivery PII leakage | Signed URLs only, 24h expiry, audit log of every download |
| Single-server SPOF | Acceptable for MVP. Roadmap: split DB to RDS, add second app server behind Lightsail load balancer |
| Lightsail rate limits on outbound email | SendGrid handles all email; no Postfix on the box |
| MC/HS API outages | Sync queue has retries; failed sync rows visible in admin; transactional flow never blocks on MC/HS |

**Open items not blocking spec approval, to confirm during implementation:**
- Exact weekend markup % (decision: admin-set, default 25% — confirm during seeding)
- Exact after-hours window (decision: before 09:00 / after 18:00, admin-editable per facility)
- Hour-pack expiry default in days (decision: 365, admin-editable per pack SKU)
- Maximum lead time before bookable (decision: 24h minimum, admin-editable)
- Refund policy schedule (decision: 100% / 50% / 0% at 7d / 3d / cutoff — confirm during seeding)
- Hero video source (Vimeo vs. self-hosted S3 — defer to content/marketing decision)

---

## 23. Phasing

This spec is **MVP only**. Follow-on phases:

- **Phase 2:** Customer dashboard polish (booking history search, profile editing, saved addresses)
- **Phase 3:** B2B quote pipeline polish (multi-stage workflow, automated quoting templates, e-sign)
- **Phase 4:** Arabic locale rollout
- **Phase 5:** Loyalty / referral program
- **Phase 6:** Multi-facility expansion (when twofour54 brings additional studios online — the data model already supports this)

Each phase gets its own spec → plan → implementation cycle.
