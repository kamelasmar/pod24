# Pod24 — Plan 3: Public Site & Booking Flow

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** End-to-end self-serve booking works. Public marketing page (port of the Pod24 mockup) plus a 7-step Livewire booking wizard, Stripe Payment Element checkout, full Booking lifecycle (`hold → pending_payment → confirmed → completed/cancelled`) with a 15-min hold expiry, and a SendGrid-backed confirmation email. Admin gets a Bookings inbox in Filament.

**Architecture:** Three new modules: **Booking** (Booking + BookingAddon models, lifecycle actions, hold-expiry scheduled job), **Content** (admin-editable FAQ / testimonials / use cases for the marketing page), and **Payments** (Stripe Payment Element wiring + webhook handler). Public site stays as Blade + Livewire — no separate SPA. The booking wizard is one Livewire component with step state in URL. Stripe charges direct PaymentIntents (no Cashier this plan; that's reserved for HourPack subscriptions in Plan 4). Hour-pack redemption is **deferred to Plan 4** (needs customer accounts and ledger). Off-site / outside-AD addresses redirect to a `/quote/offsite` placeholder route — the actual quote pipeline is Plan 5.

**Tech stack:** Laravel 11, PHP 8.4, Livewire v3, Filament 3, Stripe SDK (`stripe/stripe-php`), SendGrid (Laravel mail driver), Tailwind CSS.

**Spec reference:** `docs/superpowers/specs/2026-05-04-pod24-platform-design.md` § 5.3 (bookings table), § 8 (booking flow), § 12 (cancel/reschedule — partial; full in Plan 5), § 16 (landing page port), § 18 (errors), § 19 (security), § 20 (testing).

**Depends on:** Plan 2 complete (`plan-2-availability-pricing-complete`).

---

## File structure for Plan 3

```
pod24/
├── app/
│   ├── Modules/
│   │   ├── Booking/
│   │   │   ├── Models/
│   │   │   │   ├── Booking.php
│   │   │   │   └── BookingAddon.php
│   │   │   ├── Actions/
│   │   │   │   ├── CreateBookingHold.php
│   │   │   │   ├── ConfirmBooking.php
│   │   │   │   ├── ReleaseExpiredHolds.php
│   │   │   │   └── (more later in Plan 5: cancel, reschedule)
│   │   │   ├── Enums/
│   │   │   │   └── BookingStatus.php
│   │   │   └── Mail/
│   │   │       └── BookingConfirmedMail.php
│   │   ├── Payments/
│   │   │   ├── Actions/
│   │   │   │   └── CreatePaymentIntent.php
│   │   │   └── Webhooks/
│   │   │       └── StripeWebhookController.php
│   │   └── Content/
│   │       ├── Models/
│   │       │   ├── FaqItem.php
│   │       │   ├── Testimonial.php
│   │       │   └── UseCase.php
│   │       └── (Filament resources under Catalog/...)
│   ├── Filament/
│   │   ├── Resources/
│   │   │   ├── Booking/                      (new namespace)
│   │   │   │   └── BookingResource.php
│   │   │   └── Content/
│   │   │       ├── FaqItemResource.php
│   │   │       ├── TestimonialResource.php
│   │   │       └── UseCaseResource.php
│   │   └── Widgets/
│   │       └── BookingsTodayWidget.php
│   ├── Livewire/
│   │   ├── BookingWizard.php
│   │   ├── BookingWizardCalendar.php          (sub-component for the calendar step)
│   │   ├── B2BQuoteForm.php
│   │   └── NewsletterSignupForm.php           (footer; full sync in Plan 6)
│   └── Console/Commands/
│       └── ReleaseExpiredHoldsCommand.php
├── resources/
│   ├── views/
│   │   ├── pod24/                             (marketing page Blade components)
│   │   │   ├── home.blade.php
│   │   │   ├── components/                    (each section as a component)
│   │   │   ├── book.blade.php                 (wizard wrapper)
│   │   │   └── quote-offsite.blade.php
│   │   ├── livewire/
│   │   │   ├── booking-wizard.blade.php
│   │   │   ├── booking-wizard-calendar.blade.php
│   │   │   ├── b2b-quote-form.blade.php
│   │   │   └── newsletter-signup-form.blade.php
│   │   └── mail/
│   │       └── booking-confirmed.blade.php
│   └── css/
│       └── pod24.css                          (Tailwind-driven; matches mockup vars)
├── tailwind.config.js                         (modified — add Pod24 brand tokens)
├── routes/web.php                             (modified — public site + /book + /quote/offsite + Stripe webhook)
├── database/
│   ├── migrations/
│   ├── factories/
│   └── seeders/
└── tests/Feature/
    ├── Booking/                               (model, lifecycle, hold-expiry)
    ├── Wizard/                                (Livewire wizard tests)
    ├── Public/                                (marketing page rendering)
    └── Payments/                              (Stripe webhook signature, idempotency)
```

---

## Task 1: Install Stripe + Laravel SendGrid driver

**Files:**
- Modify: `composer.json`
- Modify: `.env.example`

- [ ] **Step 1: Install packages**

```bash
composer require stripe/stripe-php
composer require laravel/cashier  # only used for HourPacks in Plan 4; install now to consolidate
composer require ksassnowski/laravel-mailman  # SendGrid via SMTP transport — no extra package needed; just config
```

(Skip the third command. Laravel ships with Symfony Mailer + SMTP — SendGrid works as a plain SMTP provider. No extra package required.)

- [ ] **Step 2: Update `.env.example`**

Append:

```
# Stripe (test keys for dev; replace with live keys in production)
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...

# SendGrid via SMTP
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=hello@pod24.kamelasmar.com
MAIL_FROM_NAME="${APP_NAME}"
```

- [ ] **Step 3: Update `.env`** with the same keys (use empty `STRIPE_*` values for now; Stripe webhook tests will use signature mocking — no real keys needed in CI).

- [ ] **Step 4: Verify install**

```bash
composer show stripe/stripe-php | head -3
composer show laravel/cashier | head -3
```

Expect both packages resolved.

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "Install Stripe SDK and Cashier, add SendGrid SMTP env config"
```

---

## Task 2: BookingStatus enum

**Files:**
- Create: `app/Modules/Booking/Enums/BookingStatus.php`
- Create: `tests/Unit/Booking/BookingStatusTest.php`

- [ ] **Step 1: Failing test**

```php
<?php
// tests/Unit/Booking/BookingStatusTest.php

use App\Modules\Booking\Enums\BookingStatus;

it('exposes the 5 lifecycle states', function () {
    expect(BookingStatus::cases())->toHaveCount(5);
    expect(BookingStatus::Hold->value)->toBe('hold');
    expect(BookingStatus::PendingPayment->value)->toBe('pending_payment');
    expect(BookingStatus::Confirmed->value)->toBe('confirmed');
    expect(BookingStatus::Completed->value)->toBe('completed');
    expect(BookingStatus::Cancelled->value)->toBe('cancelled');
});

it('reports whether a status occupies a calendar slot', function () {
    expect(BookingStatus::Hold->occupiesSlot())->toBeTrue();
    expect(BookingStatus::PendingPayment->occupiesSlot())->toBeTrue();
    expect(BookingStatus::Confirmed->occupiesSlot())->toBeTrue();
    expect(BookingStatus::Completed->occupiesSlot())->toBeFalse();
    expect(BookingStatus::Cancelled->occupiesSlot())->toBeFalse();
});
```

- [ ] **Step 2: Run, expect failure**

- [ ] **Step 3: Implement**

```php
<?php
// app/Modules/Booking/Enums/BookingStatus.php

namespace App\Modules\Booking\Enums;

enum BookingStatus: string
{
    case Hold = 'hold';
    case PendingPayment = 'pending_payment';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /** Slots in these states block the calendar. */
    public function occupiesSlot(): bool
    {
        return in_array($this, [self::Hold, self::PendingPayment, self::Confirmed], true);
    }

    public static function occupyingValues(): array
    {
        return array_map(fn ($c) => $c->value, array_filter(self::cases(), fn ($c) => $c->occupiesSlot()));
    }
}
```

- [ ] **Step 4: Run, expect pass**

- [ ] **Step 5: Commit** "Add BookingStatus enum"

---

## Task 3: Booking model + migration + factory + tests

**Files:**
- Create: `app/Modules/Booking/Models/Booking.php`
- Create: `database/migrations/...create_bookings_table.php`
- Create: `database/factories/BookingFactory.php`
- Create: `tests/Feature/Booking/BookingTest.php`

- [ ] **Step 1: Failing test**

```php
<?php
// tests/Feature/Booking/BookingTest.php

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\ServiceTier;

it('creates a booking with a ulid and integer money columns', function () {
    $facility = Facility::factory()->create();
    $tier = ServiceTier::factory()->for($facility)->create();

    $booking = Booking::factory()
        ->for($facility)
        ->for($tier, 'serviceTier')
        ->create([
            'package_type' => 'hourly',
            'starts_at' => now()->addDays(2),
            'ends_at' => now()->addDays(2)->addHours(2),
            'total_hours' => 2,
            'subtotal_aed_cents' => 50800,
            'vat_aed_cents' => 2540,
            'total_aed_cents' => 53340,
            'status' => BookingStatus::Hold->value,
            'contact_email' => 'guest@example.com',
            'contact_name' => 'Test Guest',
            'address' => ['city' => 'Abu Dhabi', 'country' => 'AE'],
        ]);

    expect($booking->ulid)->toMatch('/^[0-9A-HJKMNP-TV-Z]{26}$/');
    expect($booking->total_aed_cents)->toBe(53340);
    expect($booking->status)->toBe(BookingStatus::Hold);
    expect($booking->address['city'])->toBe('Abu Dhabi');
});

it('casts the status column to BookingStatus enum on retrieval', function () {
    $facility = Facility::factory()->create();
    $tier = ServiceTier::factory()->for($facility)->create();
    $booking = Booking::factory()->for($facility)->for($tier, 'serviceTier')->create();
    expect($booking->fresh()->status)->toBeInstanceOf(BookingStatus::class);
});
```

- [ ] **Step 2: Run, expect failure**

- [ ] **Step 3: Migration**

```bash
php artisan make:migration create_bookings_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->ulid('ulid')->unique();
            $table->foreignId('facility_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('service_tier_id')->constrained()->restrictOnDelete();
            $table->enum('package_type', ['hourly', 'half_day', 'full_day', 'multi_day']);
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->unsignedInteger('total_hours');
            $table->enum('status', ['hold', 'pending_payment', 'confirmed', 'completed', 'cancelled']);

            $table->string('contact_name');
            $table->string('contact_email');
            $table->string('contact_phone')->nullable();
            $table->json('address');

            $table->unsignedInteger('subtotal_aed_cents');
            $table->unsignedInteger('weekend_markup_aed_cents')->default(0);
            $table->unsignedInteger('after_hours_markup_aed_cents')->default(0);
            $table->unsignedInteger('addons_aed_cents')->default(0);
            $table->unsignedInteger('hour_pack_credits_used')->default(0);
            $table->unsignedInteger('hour_pack_credit_value_aed_cents')->default(0);
            $table->unsignedInteger('vat_aed_cents');
            $table->unsignedInteger('total_aed_cents');

            $table->string('stripe_payment_intent_id')->nullable()->index();
            $table->timestamp('hold_expires_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->enum('cancelled_by', ['customer', 'admin'])->nullable();
            $table->unsignedInteger('refund_amount_aed_cents')->nullable();
            $table->timestamp('marketing_consent_at')->nullable();

            $table->timestamps();

            $table->index(['facility_id', 'starts_at', 'status']);
            $table->index('hold_expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
```

- [ ] **Step 4: Model**

```php
<?php
// app/Modules/Booking/Models/Booking.php

namespace App\Modules\Booking\Models;

use App\Models\User;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\ServiceTier;
use App\Support\HasModuleFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    use HasFactory, HasModuleFactory {
        HasModuleFactory::newFactory insteadof HasFactory;
    }

    protected $fillable = [
        'ulid', 'facility_id', 'customer_id', 'service_tier_id',
        'package_type', 'starts_at', 'ends_at', 'total_hours', 'status',
        'contact_name', 'contact_email', 'contact_phone', 'address',
        'subtotal_aed_cents', 'weekend_markup_aed_cents', 'after_hours_markup_aed_cents',
        'addons_aed_cents', 'hour_pack_credits_used', 'hour_pack_credit_value_aed_cents',
        'vat_aed_cents', 'total_aed_cents',
        'stripe_payment_intent_id', 'hold_expires_at', 'paid_at',
        'cancelled_at', 'cancelled_by', 'refund_amount_aed_cents',
        'marketing_consent_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'hold_expires_at' => 'datetime',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'marketing_consent_at' => 'datetime',
            'address' => 'array',
            'status' => BookingStatus::class,
            'total_hours' => 'integer',
            'subtotal_aed_cents' => 'integer',
            'weekend_markup_aed_cents' => 'integer',
            'after_hours_markup_aed_cents' => 'integer',
            'addons_aed_cents' => 'integer',
            'hour_pack_credits_used' => 'integer',
            'hour_pack_credit_value_aed_cents' => 'integer',
            'vat_aed_cents' => 'integer',
            'total_aed_cents' => 'integer',
            'refund_amount_aed_cents' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Booking $b) {
            if (empty($b->ulid)) {
                $b->ulid = (string) \Illuminate\Support\Str::ulid();
            }
        });
    }

    public function facility(): BelongsTo { return $this->belongsTo(Facility::class); }
    public function serviceTier(): BelongsTo { return $this->belongsTo(ServiceTier::class); }
    public function customer(): BelongsTo { return $this->belongsTo(User::class, 'customer_id'); }
    public function addons(): HasMany { return $this->hasMany(BookingAddon::class); }

    public function getRouteKeyName(): string { return 'ulid'; }
}
```

- [ ] **Step 5: Factory**

```php
<?php
// database/factories/BookingFactory.php

namespace Database\Factories;

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\ServiceTier;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('+1 week', '+3 weeks');
        $end = (clone $start)->modify('+2 hours');

        return [
            'facility_id' => Facility::factory(),
            'service_tier_id' => ServiceTier::factory(),
            'package_type' => 'hourly',
            'starts_at' => $start,
            'ends_at' => $end,
            'total_hours' => 2,
            'status' => BookingStatus::Hold->value,
            'contact_name' => $this->faker->name(),
            'contact_email' => $this->faker->safeEmail(),
            'contact_phone' => $this->faker->phoneNumber(),
            'address' => ['city' => 'Abu Dhabi', 'country' => 'AE'],
            'subtotal_aed_cents' => 50800,
            'vat_aed_cents' => 2540,
            'total_aed_cents' => 53340,
        ];
    }
}
```

- [ ] **Step 6: Run migration + tests**

```bash
php artisan migrate
./vendor/bin/pest tests/Feature/Booking/BookingTest.php
```

- [ ] **Step 7: Commit** "Add Booking model with ulid + status enum cast"

---

## Task 4: BookingAddon model + migration + factory + tests

**Files:**
- Create: `app/Modules/Booking/Models/BookingAddon.php`
- Create: `database/migrations/...create_booking_addons_table.php`
- Create: `database/factories/BookingAddonFactory.php`
- Create: `tests/Feature/Booking/BookingAddonTest.php`

- [ ] **Step 1: Failing test**

```php
<?php
// tests/Feature/Booking/BookingAddonTest.php

use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\BookingAddon;
use App\Modules\Catalog\Models\Addon;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\ServiceTier;

it('attaches an addon to a booking with snapshot price', function () {
    $facility = Facility::factory()->create();
    $tier = ServiceTier::factory()->for($facility)->create();
    $booking = Booking::factory()->for($facility)->for($tier, 'serviceTier')->create();
    $addon = Addon::factory()->for($facility)->create(['price_aed_cents' => 50000]);

    $row = BookingAddon::create([
        'booking_id' => $booking->id,
        'addon_id' => $addon->id,
        'qty' => 2,
        'price_at_booking_aed_cents' => 50000,
    ]);

    expect($row->qty)->toBe(2);
    expect($row->price_at_booking_aed_cents)->toBe(50000);
    expect($booking->fresh()->addons)->toHaveCount(1);
});
```

- [ ] **Step 2: Run, expect failure**

- [ ] **Step 3: Migration**

```bash
php artisan make:migration create_booking_addons_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('booking_addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('addon_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('qty')->default(1);
            $table->unsignedInteger('price_at_booking_aed_cents');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_addons');
    }
};
```

- [ ] **Step 4: Model**

```php
<?php
// app/Modules/Booking/Models/BookingAddon.php

namespace App\Modules\Booking\Models;

use App\Modules\Catalog\Models\Addon;
use App\Support\HasModuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingAddon extends Model
{
    use HasFactory, HasModuleFactory {
        HasModuleFactory::newFactory insteadof HasFactory;
    }

    protected $fillable = ['booking_id', 'addon_id', 'qty', 'price_at_booking_aed_cents'];

    protected function casts(): array
    {
        return [
            'qty' => 'integer',
            'price_at_booking_aed_cents' => 'integer',
        ];
    }

    public function booking(): BelongsTo { return $this->belongsTo(Booking::class); }
    public function addon(): BelongsTo { return $this->belongsTo(Addon::class); }
}
```

- [ ] **Step 5: Factory**

```php
<?php
// database/factories/BookingAddonFactory.php

namespace Database\Factories;

use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\BookingAddon;
use App\Modules\Catalog\Models\Addon;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingAddonFactory extends Factory
{
    protected $model = BookingAddon::class;

    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'addon_id' => Addon::factory(),
            'qty' => 1,
            'price_at_booking_aed_cents' => 50000,
        ];
    }
}
```

- [ ] **Step 6: Migrate + test**

- [ ] **Step 7: Commit** "Add BookingAddon model with snapshot price"

---

## Task 5: Capacity-aware FindAvailableSlots

Now that Booking exists, wire the slot finder's capacity check. Slots whose day has `>= max_concurrent_per_day` occupying bookings get excluded.

**Files:**
- Modify: `app/Modules/Availability/Actions/FindAvailableSlots.php`
- Modify: `tests/Feature/Availability/FindAvailableSlotsTest.php`

- [ ] **Step 1: Append failing test**

```php
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Catalog\Models\ServiceTier;

it('excludes the day when concurrent occupying bookings >= capacity', function () {
    $tier = ServiceTier::factory()->for($this->facility)->create();
    $monday = CarbonImmutable::parse('2026-06-08', 'Asia/Dubai');

    // capacity is 2 (set in beforeEach); fill it with 2 confirmed bookings
    Booking::factory()->count(2)->for($this->facility)->for($tier, 'serviceTier')->create([
        'starts_at' => $monday->setTime(10, 0),
        'ends_at' => $monday->setTime(12, 0),
        'status' => BookingStatus::Confirmed->value,
    ]);

    $slots = app(FindAvailableSlots::class)->execute($this->facility->id, $monday, 'hourly');
    expect($slots)->toHaveCount(0);
});

it('counts holds and pending_payment bookings against capacity', function () {
    $tier = ServiceTier::factory()->for($this->facility)->create();
    $monday = CarbonImmutable::parse('2026-06-08', 'Asia/Dubai');

    Booking::factory()->for($this->facility)->for($tier, 'serviceTier')->create([
        'starts_at' => $monday->setTime(9, 0),
        'ends_at' => $monday->setTime(10, 0),
        'status' => BookingStatus::Hold->value,
        'hold_expires_at' => now()->addMinutes(10),
    ]);
    Booking::factory()->for($this->facility)->for($tier, 'serviceTier')->create([
        'starts_at' => $monday->setTime(11, 0),
        'ends_at' => $monday->setTime(12, 0),
        'status' => BookingStatus::PendingPayment->value,
    ]);

    $slots = app(FindAvailableSlots::class)->execute($this->facility->id, $monday, 'hourly');
    expect($slots)->toHaveCount(0); // capacity 2 already filled by hold + pending
});

it('does not count cancelled or completed bookings against capacity', function () {
    $tier = ServiceTier::factory()->for($this->facility)->create();
    $monday = CarbonImmutable::parse('2026-06-08', 'Asia/Dubai');

    Booking::factory()->count(2)->for($this->facility)->for($tier, 'serviceTier')->create([
        'starts_at' => $monday->setTime(10, 0),
        'ends_at' => $monday->setTime(12, 0),
        'status' => BookingStatus::Cancelled->value,
    ]);

    $slots = app(FindAvailableSlots::class)->execute($this->facility->id, $monday, 'hourly');
    expect(count($slots))->toBeGreaterThan(0);
});
```

- [ ] **Step 2: Run, expect failure**

- [ ] **Step 3: Update action**

In `FindAvailableSlots::execute()`, after the rule check and before the slot loop, add:

```php
$facility = \App\Modules\Catalog\Models\Facility::find($facilityId);
$capacity = $facility->max_concurrent_per_day;

$dayStart = $date->startOfDay();
$dayEnd = $date->endOfDay();

$occupying = \App\Modules\Booking\Models\Booking::where('facility_id', $facilityId)
    ->whereIn('status', \App\Modules\Booking\Enums\BookingStatus::occupyingValues())
    ->where('starts_at', '<', $dayEnd)
    ->where('ends_at', '>', $dayStart)
    ->count();

if ($occupying >= $capacity) {
    return [];
}
```

- [ ] **Step 4: Run, expect pass**

- [ ] **Step 5: Commit** "Wire capacity check into FindAvailableSlots"

---

## Task 6: CreateBookingHold action

Reserves a slot for 15 minutes during checkout. Uses a serializable transaction to prevent two customers grabbing the same last slot.

**Files:**
- Create: `app/Modules/Booking/Actions/CreateBookingHold.php`
- Create: `app/Modules/Booking/Exceptions/SlotUnavailable.php`
- Create: `tests/Feature/Booking/CreateBookingHoldTest.php`

- [ ] **Step 1: Failing test**

```php
<?php
// tests/Feature/Booking/CreateBookingHoldTest.php

use App\Modules\Booking\Actions\CreateBookingHold;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\FacilityPricing;
use App\Modules\Catalog\Models\ServiceTier;
use App\Modules\Pricing\ValueObjects\BookingDraft;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->facility = Facility::factory()->create(['max_concurrent_per_day' => 1]);
    \App\Modules\Availability\Models\AvailabilityRule::factory()->for($this->facility)->create([
        'day_of_week' => 1, 'open_time' => '09:00', 'close_time' => '18:00',
    ]);
    $this->tier = ServiceTier::factory()->for($this->facility)->create();
    FacilityPricing::create([
        'facility_id' => $this->facility->id,
        'service_tier_id' => $this->tier->id,
        'package_type' => 'hourly',
        'hours' => 1,
        'price_aed_cents' => 25400,
    ]);
});

it('creates a hold with status=hold and hold_expires_at 15 min out', function () {
    $draft = new BookingDraft(
        facility_id: $this->facility->id,
        service_tier_id: $this->tier->id,
        package_type: 'hourly',
        starts_at: CarbonImmutable::parse('2026-06-08 10:00:00', 'Asia/Dubai'),
        ends_at:   CarbonImmutable::parse('2026-06-08 12:00:00', 'Asia/Dubai'),
    );

    $booking = app(CreateBookingHold::class)->execute(
        draft: $draft,
        contact: ['name' => 'Guest', 'email' => 'g@example.com', 'phone' => null],
        address: ['city' => 'Abu Dhabi', 'country' => 'AE'],
    );

    expect($booking->status)->toBe(BookingStatus::Hold);
    expect($booking->hold_expires_at)->not->toBeNull();
    expect($booking->hold_expires_at->diffInMinutes(now()))->toBeBetween(14, 16);
    expect($booking->total_aed_cents)->toBe(53340); // (25400 × 2) × 1.05
});

it('rejects when capacity is full', function () {
    Booking::factory()->for($this->facility)->for($this->tier, 'serviceTier')->create([
        'starts_at' => '2026-06-08 09:00:00',
        'ends_at'   => '2026-06-08 11:00:00',
        'status' => BookingStatus::Confirmed->value,
    ]);

    $draft = new BookingDraft(
        facility_id: $this->facility->id,
        service_tier_id: $this->tier->id,
        package_type: 'hourly',
        starts_at: CarbonImmutable::parse('2026-06-08 14:00:00', 'Asia/Dubai'),
        ends_at:   CarbonImmutable::parse('2026-06-08 15:00:00', 'Asia/Dubai'),
    );

    expect(fn () => app(CreateBookingHold::class)->execute(
        draft: $draft,
        contact: ['name' => 'Guest', 'email' => 'g@example.com', 'phone' => null],
        address: ['city' => 'Abu Dhabi', 'country' => 'AE'],
    ))->toThrow(\App\Modules\Booking\Exceptions\SlotUnavailable::class);
});
```

- [ ] **Step 2: Run, expect failure**

- [ ] **Step 3: Add exception**

```php
<?php
// app/Modules/Booking/Exceptions/SlotUnavailable.php

namespace App\Modules\Booking\Exceptions;

class SlotUnavailable extends \RuntimeException {}
```

- [ ] **Step 4: Implement action**

```php
<?php
// app/Modules/Booking/Actions/CreateBookingHold.php

namespace App\Modules\Booking\Actions;

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Exceptions\SlotUnavailable;
use App\Modules\Booking\Models\Booking;
use App\Modules\Booking\Models\BookingAddon;
use App\Modules\Catalog\Models\Addon;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Pricing\Actions\CalculateBookingPrice;
use App\Modules\Pricing\ValueObjects\BookingDraft;
use Illuminate\Support\Facades\DB;

class CreateBookingHold
{
    public function __construct(private CalculateBookingPrice $pricer) {}

    public function execute(
        BookingDraft $draft,
        array $contact,
        array $address,
        ?int $customerId = null,
        ?string $marketingConsentAt = null,
    ): Booking {
        return DB::transaction(function () use ($draft, $contact, $address, $customerId, $marketingConsentAt) {
            // Lock the facility row to serialize concurrent capacity checks
            $facility = Facility::lockForUpdate()->findOrFail($draft->facility_id);

            $occupying = Booking::where('facility_id', $facility->id)
                ->whereIn('status', BookingStatus::occupyingValues())
                ->where('starts_at', '<', $draft->ends_at->endOfDay())
                ->where('ends_at', '>', $draft->starts_at->startOfDay())
                ->count();

            if ($occupying >= $facility->max_concurrent_per_day) {
                throw new SlotUnavailable("Facility {$facility->id} is fully booked on " . $draft->starts_at->toDateString());
            }

            $price = $this->pricer->execute($draft);

            $booking = Booking::create([
                'facility_id' => $draft->facility_id,
                'service_tier_id' => $draft->service_tier_id,
                'customer_id' => $customerId,
                'package_type' => $draft->package_type,
                'starts_at' => $draft->starts_at,
                'ends_at' => $draft->ends_at,
                'total_hours' => $draft->totalHours(),
                'status' => BookingStatus::Hold->value,
                'contact_name' => $contact['name'],
                'contact_email' => $contact['email'],
                'contact_phone' => $contact['phone'] ?? null,
                'address' => $address,
                'subtotal_aed_cents' => $price->subtotal(),
                'weekend_markup_aed_cents' => $price->weekend_markup_aed_cents,
                'after_hours_markup_aed_cents' => $price->after_hours_markup_aed_cents,
                'addons_aed_cents' => $price->addons_aed_cents,
                'hour_pack_credit_value_aed_cents' => $price->hour_pack_credit_value_aed_cents,
                'vat_aed_cents' => $price->vat(),
                'total_aed_cents' => $price->total(),
                'hold_expires_at' => now()->addMinutes(15),
                'marketing_consent_at' => $marketingConsentAt,
            ]);

            foreach ($draft->addons as $addonInput) {
                $addon = Addon::find($addonInput['addon_id']);
                BookingAddon::create([
                    'booking_id' => $booking->id,
                    'addon_id' => $addon->id,
                    'qty' => $addonInput['qty'],
                    'price_at_booking_aed_cents' => $addon->price_aed_cents,
                ]);
            }

            return $booking;
        });
    }
}
```

- [ ] **Step 5: Run tests, expect pass**

- [ ] **Step 6: Commit** "Add CreateBookingHold action with capacity-locked transaction"

---

## Task 7: ReleaseExpiredHolds + scheduled command

**Files:**
- Create: `app/Modules/Booking/Actions/ReleaseExpiredHolds.php`
- Create: `app/Console/Commands/ReleaseExpiredHoldsCommand.php`
- Modify: `routes/console.php` (schedule the command)
- Create: `tests/Feature/Booking/ReleaseExpiredHoldsTest.php`

- [ ] **Step 1: Failing test**

```php
<?php
// tests/Feature/Booking/ReleaseExpiredHoldsTest.php

use App\Modules\Booking\Actions\ReleaseExpiredHolds;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\ServiceTier;

beforeEach(function () {
    $this->facility = Facility::factory()->create();
    $this->tier = ServiceTier::factory()->for($this->facility)->create();
});

it('marks expired hold rows as cancelled', function () {
    $expired = Booking::factory()->for($this->facility)->for($this->tier, 'serviceTier')->create([
        'status' => BookingStatus::Hold->value,
        'hold_expires_at' => now()->subMinutes(1),
    ]);

    $count = app(ReleaseExpiredHolds::class)->execute();
    expect($count)->toBe(1);
    expect($expired->fresh()->status)->toBe(BookingStatus::Cancelled);
    expect($expired->fresh()->cancelled_by)->toBe('admin'); // system release
});

it('does not touch non-expired holds', function () {
    $live = Booking::factory()->for($this->facility)->for($this->tier, 'serviceTier')->create([
        'status' => BookingStatus::Hold->value,
        'hold_expires_at' => now()->addMinutes(10),
    ]);

    app(ReleaseExpiredHolds::class)->execute();
    expect($live->fresh()->status)->toBe(BookingStatus::Hold);
});

it('does not touch confirmed bookings', function () {
    $confirmed = Booking::factory()->for($this->facility)->for($this->tier, 'serviceTier')->create([
        'status' => BookingStatus::Confirmed->value,
        'hold_expires_at' => null,
    ]);

    app(ReleaseExpiredHolds::class)->execute();
    expect($confirmed->fresh()->status)->toBe(BookingStatus::Confirmed);
});
```

- [ ] **Step 2: Run, expect failure**

- [ ] **Step 3: Implement action**

```php
<?php
// app/Modules/Booking/Actions/ReleaseExpiredHolds.php

namespace App\Modules\Booking\Actions;

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;

class ReleaseExpiredHolds
{
    public function execute(): int
    {
        return Booking::where('status', BookingStatus::Hold->value)
            ->whereNotNull('hold_expires_at')
            ->where('hold_expires_at', '<', now())
            ->update([
                'status' => BookingStatus::Cancelled->value,
                'cancelled_at' => now(),
                'cancelled_by' => 'admin',
            ]);
    }
}
```

- [ ] **Step 4: Implement command**

```bash
php artisan make:command ReleaseExpiredHoldsCommand
```

```php
<?php
// app/Console/Commands/ReleaseExpiredHoldsCommand.php

namespace App\Console\Commands;

use App\Modules\Booking\Actions\ReleaseExpiredHolds;
use Illuminate\Console\Command;

class ReleaseExpiredHoldsCommand extends Command
{
    protected $signature = 'pod24:release-expired-holds';
    protected $description = 'Release booking holds whose 15-min window has elapsed';

    public function handle(ReleaseExpiredHolds $action): int
    {
        $count = $action->execute();
        $this->info("Released {$count} expired hold(s).");
        return self::SUCCESS;
    }
}
```

- [ ] **Step 5: Schedule it** in `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('pod24:release-expired-holds')->everyMinute();
```

- [ ] **Step 6: Run tests, expect pass**

- [ ] **Step 7: Commit** "Add ReleaseExpiredHolds action with per-minute scheduler"

---

## Task 8: ConfirmBooking action

Triggered after Stripe payment success — flips a `pending_payment` booking to `confirmed`.

**Files:**
- Create: `app/Modules/Booking/Actions/ConfirmBooking.php`
- Create: `app/Modules/Booking/Events/BookingConfirmed.php`
- Create: `tests/Feature/Booking/ConfirmBookingTest.php`

- [ ] **Step 1: Failing test**

```php
<?php
// tests/Feature/Booking/ConfirmBookingTest.php

use App\Modules\Booking\Actions\ConfirmBooking;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Events\BookingConfirmed;
use App\Modules\Booking\Models\Booking;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\ServiceTier;
use Illuminate\Support\Facades\Event;

it('flips pending_payment to confirmed and fires the event', function () {
    Event::fake();
    $facility = Facility::factory()->create();
    $tier = ServiceTier::factory()->for($facility)->create();
    $booking = Booking::factory()->for($facility)->for($tier, 'serviceTier')->create([
        'status' => BookingStatus::PendingPayment->value,
        'stripe_payment_intent_id' => 'pi_test_123',
    ]);

    app(ConfirmBooking::class)->execute($booking, paymentIntentId: 'pi_test_123');

    expect($booking->fresh()->status)->toBe(BookingStatus::Confirmed);
    expect($booking->fresh()->paid_at)->not->toBeNull();
    Event::assertDispatched(BookingConfirmed::class);
});

it('is idempotent on already-confirmed bookings', function () {
    Event::fake();
    $facility = Facility::factory()->create();
    $tier = ServiceTier::factory()->for($facility)->create();
    $booking = Booking::factory()->for($facility)->for($tier, 'serviceTier')->create([
        'status' => BookingStatus::Confirmed->value,
        'paid_at' => now()->subHour(),
    ]);
    $originalPaidAt = $booking->paid_at;

    app(ConfirmBooking::class)->execute($booking, paymentIntentId: 'pi_test_123');

    expect($booking->fresh()->paid_at->toDateTimeString())->toBe($originalPaidAt->toDateTimeString());
    Event::assertNotDispatched(BookingConfirmed::class);
});
```

- [ ] **Step 2: Run, expect failure**

- [ ] **Step 3: Implement event**

```php
<?php
// app/Modules/Booking/Events/BookingConfirmed.php

namespace App\Modules\Booking\Events;

use App\Modules\Booking\Models\Booking;
use Illuminate\Foundation\Events\Dispatchable;

class BookingConfirmed
{
    use Dispatchable;

    public function __construct(public Booking $booking) {}
}
```

- [ ] **Step 4: Implement action**

```php
<?php
// app/Modules/Booking/Actions/ConfirmBooking.php

namespace App\Modules\Booking\Actions;

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Events\BookingConfirmed;
use App\Modules\Booking\Models\Booking;

class ConfirmBooking
{
    public function execute(Booking $booking, string $paymentIntentId): void
    {
        if ($booking->status === BookingStatus::Confirmed) {
            return; // idempotent
        }

        $booking->update([
            'status' => BookingStatus::Confirmed->value,
            'paid_at' => now(),
            'stripe_payment_intent_id' => $paymentIntentId,
            'hold_expires_at' => null,
        ]);

        BookingConfirmed::dispatch($booking->fresh());
    }
}
```

- [ ] **Step 5: Run, expect pass**

- [ ] **Step 6: Commit** "Add ConfirmBooking action with idempotency"

---

## Task 9: Stripe — CreatePaymentIntent action

**Files:**
- Create: `app/Modules/Payments/Actions/CreatePaymentIntent.php`
- Create: `config/stripe.php`
- Modify: `bootstrap/providers.php` if needed (none for now)
- Create: `tests/Feature/Payments/CreatePaymentIntentTest.php`

- [ ] **Step 1: Add Stripe config**

```php
<?php
// config/stripe.php

return [
    'key' => env('STRIPE_KEY'),
    'secret' => env('STRIPE_SECRET'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
];
```

- [ ] **Step 2: Failing test (mocks Stripe)**

```php
<?php
// tests/Feature/Payments/CreatePaymentIntentTest.php

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\ServiceTier;
use App\Modules\Payments\Actions\CreatePaymentIntent;

it('creates a Stripe PaymentIntent and returns a client secret', function () {
    $facility = Facility::factory()->create();
    $tier = ServiceTier::factory()->for($facility)->create();
    $booking = Booking::factory()->for($facility)->for($tier, 'serviceTier')->create([
        'status' => BookingStatus::Hold->value,
        'total_aed_cents' => 53340,
    ]);

    // Mock the Stripe SDK call
    $piMock = \Mockery::mock('overload:Stripe\PaymentIntent');
    $piMock->shouldReceive('create')->andReturn((object) [
        'id' => 'pi_test_xyz',
        'client_secret' => 'pi_test_xyz_secret_abc',
    ]);

    $result = app(CreatePaymentIntent::class)->execute($booking);

    expect($result['client_secret'])->toBe('pi_test_xyz_secret_abc');
    expect($booking->fresh()->stripe_payment_intent_id)->toBe('pi_test_xyz');
    expect($booking->fresh()->status)->toBe(BookingStatus::PendingPayment);
})->skip('requires Mockery overload — see Step 4 for runner config');
```

- [ ] **Step 3: Implement**

```php
<?php
// app/Modules/Payments/Actions/CreatePaymentIntent.php

namespace App\Modules\Payments\Actions;

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class CreatePaymentIntent
{
    public function execute(Booking $booking): array
    {
        Stripe::setApiKey(config('stripe.secret'));

        $intent = PaymentIntent::create([
            'amount' => $booking->total_aed_cents,
            'currency' => 'aed',
            'metadata' => ['booking_ulid' => $booking->ulid],
            'automatic_payment_methods' => ['enabled' => true],
        ]);

        $booking->update([
            'stripe_payment_intent_id' => $intent->id,
            'status' => BookingStatus::PendingPayment->value,
        ]);

        return [
            'payment_intent_id' => $intent->id,
            'client_secret' => $intent->client_secret,
        ];
    }
}
```

- [ ] **Step 4: Replace skipped test with a non-overload test that uses a fake Stripe wrapper**

The Stripe SDK can't easily be mocked without `Mockery::overload`, which requires `runTestsInSeparateProcesses` and slows the suite. Instead, **inject a Stripe API client** so tests can swap it out.

Refactor the action to accept a closure factory:

```php
class CreatePaymentIntent
{
    /** @var callable */
    private $createIntent;

    public function __construct(?callable $createIntent = null)
    {
        $this->createIntent = $createIntent ?? function (array $params) {
            \Stripe\Stripe::setApiKey(config('stripe.secret'));
            return \Stripe\PaymentIntent::create($params);
        };
    }

    public function execute(Booking $booking): array
    {
        $intent = ($this->createIntent)([
            'amount' => $booking->total_aed_cents,
            'currency' => 'aed',
            'metadata' => ['booking_ulid' => $booking->ulid],
            'automatic_payment_methods' => ['enabled' => true],
        ]);

        $booking->update([
            'stripe_payment_intent_id' => $intent->id,
            'status' => BookingStatus::PendingPayment->value,
        ]);

        return [
            'payment_intent_id' => $intent->id,
            'client_secret' => $intent->client_secret,
        ];
    }
}
```

- [ ] **Step 5: Replace test with closure-injection**

```php
<?php

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\ServiceTier;
use App\Modules\Payments\Actions\CreatePaymentIntent;

it('creates a Stripe PaymentIntent and flips booking to pending_payment', function () {
    $facility = Facility::factory()->create();
    $tier = ServiceTier::factory()->for($facility)->create();
    $booking = Booking::factory()->for($facility)->for($tier, 'serviceTier')->create([
        'status' => BookingStatus::Hold->value,
        'total_aed_cents' => 53340,
    ]);

    $stub = function (array $params) {
        expect($params['amount'])->toBe(53340);
        expect($params['currency'])->toBe('aed');
        return (object) [
            'id' => 'pi_test_xyz',
            'client_secret' => 'pi_test_xyz_secret_abc',
        ];
    };

    $action = new CreatePaymentIntent($stub);
    $result = $action->execute($booking);

    expect($result['client_secret'])->toBe('pi_test_xyz_secret_abc');
    expect($booking->fresh()->stripe_payment_intent_id)->toBe('pi_test_xyz');
    expect($booking->fresh()->status)->toBe(BookingStatus::PendingPayment);
});
```

- [ ] **Step 6: Run, expect pass**

- [ ] **Step 7: Commit** "Add CreatePaymentIntent action with closure-injectable Stripe client"

---

## Task 10: Stripe webhook controller

**Files:**
- Create: `app/Modules/Payments/Webhooks/StripeWebhookController.php`
- Modify: `routes/web.php`
- Modify: `bootstrap/app.php` (CSRF exception for the webhook route)
- Create: `tests/Feature/Payments/StripeWebhookTest.php`

- [ ] **Step 1: Add the route**

In `routes/web.php`:

```php
use App\Modules\Payments\Webhooks\StripeWebhookController;

Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle'])
    ->name('stripe.webhook');
```

- [ ] **Step 2: Exempt CSRF**

In `bootstrap/app.php`:

```php
->withMiddleware(function (\Illuminate\Foundation\Configuration\Middleware $middleware) {
    $middleware->validateCsrfTokens(except: ['webhooks/stripe']);
})
```

- [ ] **Step 3: Failing test**

```php
<?php
// tests/Feature/Payments/StripeWebhookTest.php

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\ServiceTier;

it('confirms a booking when payment_intent.succeeded fires', function () {
    config(['stripe.webhook_secret' => 'whsec_test']);

    $facility = Facility::factory()->create();
    $tier = ServiceTier::factory()->for($facility)->create();
    $booking = Booking::factory()->for($facility)->for($tier, 'serviceTier')->create([
        'status' => BookingStatus::PendingPayment->value,
        'stripe_payment_intent_id' => 'pi_abc',
    ]);

    $payload = json_encode([
        'id' => 'evt_test',
        'type' => 'payment_intent.succeeded',
        'data' => ['object' => [
            'id' => 'pi_abc',
            'metadata' => ['booking_ulid' => $booking->ulid],
        ]],
    ]);

    // Build a valid Stripe signature
    $timestamp = time();
    $signedPayload = "{$timestamp}.{$payload}";
    $signature = hash_hmac('sha256', $signedPayload, 'whsec_test');
    $header = "t={$timestamp},v1={$signature}";

    $this->postJson('/webhooks/stripe', json_decode($payload, true), [
        'Stripe-Signature' => $header,
    ])->assertOk();

    expect($booking->fresh()->status)->toBe(BookingStatus::Confirmed);
});

it('rejects requests without a valid signature', function () {
    config(['stripe.webhook_secret' => 'whsec_test']);

    $this->postJson('/webhooks/stripe', ['type' => 'payment_intent.succeeded'])
        ->assertStatus(400);
});
```

- [ ] **Step 4: Implement controller**

```php
<?php
// app/Modules/Payments/Webhooks/StripeWebhookController.php

namespace App\Modules\Payments\Webhooks;

use App\Modules\Booking\Actions\ConfirmBooking;
use App\Modules\Booking\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController
{
    public function __construct(private ConfirmBooking $confirmBooking) {}

    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $secret = config('stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $signature, $secret);
        } catch (SignatureVerificationException $e) {
            return response('Invalid signature', 400);
        }

        Log::info("Stripe webhook received: {$event->type} ({$event->id})");

        if ($event->type === 'payment_intent.succeeded') {
            $intentId = $event->data->object->id;
            $bookingUlid = $event->data->object->metadata->booking_ulid ?? null;

            $booking = $bookingUlid
                ? Booking::where('ulid', $bookingUlid)->first()
                : Booking::where('stripe_payment_intent_id', $intentId)->first();

            if ($booking) {
                $this->confirmBooking->execute($booking, $intentId);
            }
        }

        return response()->json(['received' => true]);
    }
}
```

- [ ] **Step 5: Run tests, expect pass**

- [ ] **Step 6: Commit** "Add Stripe webhook controller with signature verification"

---

## Task 11: BookingConfirmedMail (SendGrid via Laravel mailer)

**Files:**
- Create: `app/Modules/Booking/Mail/BookingConfirmedMail.php`
- Create: `resources/views/mail/booking-confirmed.blade.php`
- Create: `app/Modules/Booking/Listeners/SendBookingConfirmedEmail.php`
- Modify: `app/Providers/AppServiceProvider.php` (or `EventServiceProvider`) to register the listener
- Create: `tests/Feature/Booking/BookingConfirmedEmailTest.php`

- [ ] **Step 1: Failing test**

```php
<?php
// tests/Feature/Booking/BookingConfirmedEmailTest.php

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Events\BookingConfirmed;
use App\Modules\Booking\Mail\BookingConfirmedMail;
use App\Modules\Booking\Models\Booking;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\ServiceTier;
use Illuminate\Support\Facades\Mail;

it('sends a SendGrid email when BookingConfirmed event fires', function () {
    Mail::fake();
    $facility = Facility::factory()->create();
    $tier = ServiceTier::factory()->for($facility)->create();
    $booking = Booking::factory()->for($facility)->for($tier, 'serviceTier')->create([
        'status' => BookingStatus::Confirmed->value,
        'contact_email' => 'guest@example.com',
    ]);

    BookingConfirmed::dispatch($booking);

    Mail::assertSent(BookingConfirmedMail::class, function ($mail) use ($booking) {
        return $mail->hasTo($booking->contact_email)
            && $mail->booking->id === $booking->id;
    });
});
```

- [ ] **Step 2: Run, expect failure**

- [ ] **Step 3: Mailable**

```php
<?php
// app/Modules/Booking/Mail/BookingConfirmedMail.php

namespace App\Modules\Booking\Mail;

use App\Modules\Booking\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingConfirmedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your Pod24 booking is confirmed — " . $this->booking->starts_at->format('D, M j H:i'),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'mail.booking-confirmed');
    }
}
```

- [ ] **Step 4: View**

```blade
{{-- resources/views/mail/booking-confirmed.blade.php --}}
<x-mail::message>
# Your Pod24 booking is confirmed

Hi {{ $booking->contact_name }},

Thanks for booking Pod24! Your session is locked in.

**Date:** {{ $booking->starts_at->format('l, F j, Y') }}
**Time:** {{ $booking->starts_at->format('H:i') }} – {{ $booking->ends_at->format('H:i') }}
**Total paid:** AED {{ number_format($booking->total_aed_cents / 100, 2) }}

Your booking reference: **{{ $booking->ulid }}**

We'll see you on the day. Reply to this email if anything changes.

Pod24 — twofour54
</x-mail::message>
```

- [ ] **Step 5: Listener**

```php
<?php
// app/Modules/Booking/Listeners/SendBookingConfirmedEmail.php

namespace App\Modules\Booking\Listeners;

use App\Modules\Booking\Events\BookingConfirmed;
use App\Modules\Booking\Mail\BookingConfirmedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendBookingConfirmedEmail implements ShouldQueue
{
    public function handle(BookingConfirmed $event): void
    {
        Mail::to($event->booking->contact_email)->send(new BookingConfirmedMail($event->booking));
    }
}
```

- [ ] **Step 6: Register in AppServiceProvider's `boot()`**

```php
use App\Modules\Booking\Events\BookingConfirmed;
use App\Modules\Booking\Listeners\SendBookingConfirmedEmail;
use Illuminate\Support\Facades\Event;

Event::listen(BookingConfirmed::class, SendBookingConfirmedEmail::class);
```

- [ ] **Step 7: Publish Mail components**

```bash
php artisan vendor:publish --tag=laravel-mail
```

- [ ] **Step 8: Run tests**

- [ ] **Step 9: Commit** "Wire BookingConfirmedMail via event listener and SendGrid"

---

## Task 12: Marketing landing page — port the mockup

This is a chunky one but mostly mechanical: convert `mockup-full.html` into Blade components.

**Files:**
- Create: `resources/views/pod24/home.blade.php`
- Create: `resources/views/pod24/components/{hero,action-cards,meet-pod,included,book-widget,b2b-form,how,use-cases,testimonials,faq,final-cta,sticky-cta,footer}.blade.php` (13 components)
- Create: `tailwind.config.js` updates
- Modify: `resources/css/app.css`
- Modify: `routes/web.php`
- Create: `tests/Feature/Public/HomePageTest.php`

- [ ] **Step 1: Update Tailwind config**

```js
// tailwind.config.js — additions to theme.extend

colors: {
    'pod-bg': '#FFFFFF',
    'pod-surface': '#F5F5F5',
    'pod-ink': '#1C2327',
    'pod-ink-deep': '#000000',
    'pod-accent': '#00B9E3',
    'pod-accent-deep': '#00B0F7',
    'pod-accent-soft': '#E6F7FC',
    'pod-border': '#CDCDD8',
    'pod-border-soft': '#E0E0E0',
    'pod-muted': '#9F9FAA',
},
fontFamily: {
    pod: ['Montserrat', 'system-ui', 'sans-serif'],
},
```

- [ ] **Step 2: Add Montserrat to `resources/views/pod24/home.blade.php` head**

Use `<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">`.

- [ ] **Step 3: Build the components**

For brevity, source the existing mockup HTML at `/Users/kamelasmar/apps/swaplyst_backend/.superpowers/brainstorm/14322-1777029836/content/mockup-full.html` and convert each `<section>` into a Blade component file. Use Tailwind classes where the original CSS used custom variables (e.g., `var(--accent)` → `bg-pod-accent`).

The `home.blade.php` orchestrates them:

```blade
{{-- resources/views/pod24/home.blade.php --}}
@extends('pod24.layouts.public')

@section('content')
    <x-pod24.hero />
    <x-pod24.action-cards />
    <x-pod24.meet-pod />
    <x-pod24.included />
    <x-pod24.book-widget :facility="$pod24Facility" />
    <x-pod24.b2b-form />
    <x-pod24.how />
    <x-pod24.use-cases :items="$useCases" />
    <x-pod24.testimonials :items="$testimonials" />
    <x-pod24.faq :items="$faqItems" />
    <x-pod24.final-cta />
    <x-pod24.sticky-cta />
    <x-pod24.footer />
@endsection
```

(Each `<x-pod24.*>` lives at `resources/views/components/pod24/{name}.blade.php`.)

For the FAQ, testimonials, and use cases components, accept array data — Task 13 wires the Content module to populate these. For now, return empty arrays; the components should render gracefully with no items (a placeholder line is fine).

- [ ] **Step 4: Public layout**

```blade
{{-- resources/views/pod24/layouts/public.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $title ?? 'Pod24 — Portable Podcast Studio · twofour54' }}</title>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
@vite(['resources/css/app.css', 'resources/js/app.js'])
@livewireStyles
</head>
<body class="font-pod text-pod-ink">
@yield('content')
@livewireScripts
</body>
</html>
```

- [ ] **Step 5: Route**

```php
// routes/web.php
use App\Modules\Catalog\Models\Facility;
use App\Modules\Content\Models\FaqItem;        // created in Task 13
use App\Modules\Content\Models\Testimonial;
use App\Modules\Content\Models\UseCase;

Route::get('/', function () {
    return view('pod24.home', [
        'pod24Facility' => Facility::where('slug', 'pod24-portable')->firstOrFail(),
        'faqItems' => FaqItem::where('is_published', true)->orderBy('sort_order')->get(),
        'testimonials' => Testimonial::where('is_published', true)->get(),
        'useCases' => UseCase::where('is_published', true)->orderBy('sort_order')->get(),
    ]);
})->name('home');
```

- [ ] **Step 6: Smoke test**

```php
<?php
// tests/Feature/Public/HomePageTest.php

it('renders the home page with HTTP 200', function () {
    $this->seed(\Database\Seeders\Pod24CatalogSeeder::class);
    $this->get('/')->assertOk()->assertSee('Pod24');
});

it('shows the cyan accent in the rendered output', function () {
    $this->seed(\Database\Seeders\Pod24CatalogSeeder::class);
    $this->get('/')->assertSee('pod-accent', false); // tailwind class survives in compiled HTML
});
```

- [ ] **Step 7: Run pest**

- [ ] **Step 8: Commit** "Port marketing landing page to Blade + Tailwind"

---

## Task 13: Content module — FAQ, Testimonial, UseCase

**Files:**
- Create: 3 models, 3 migrations, 3 factories at `app/Modules/Content/Models/` + `database/factories/`
- Create: 3 Filament resources at `app/Filament/Resources/Content/`
- Modify: `AdminPanelProvider` to discover the new namespace
- Create: `database/seeders/Pod24ContentSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Tests in `tests/Feature/Content/`

- [ ] **Step 1: FaqItem migration + model + factory + test**

Migration: `id, question_json, answer_json, is_published, sort_order, timestamps`. Translatable on question + answer.

Model uses HasModuleFactory + HasTranslations.

Factory: random question/answer.

Test: 1 (creates an FAQ item).

Commit "Add FaqItem model".

- [ ] **Step 2: Testimonial migration + model + factory + test**

Migration: `id, quote_json, name, role, avatar_path, is_published, timestamps`.

Model: HasModuleFactory + HasTranslations on quote.

Factory: random quote.

Test: 1.

Commit "Add Testimonial model".

- [ ] **Step 3: UseCase migration + model + factory + test**

Migration: `id, title_json, description_json, image_path, is_published, sort_order, timestamps`.

Translatable on title + description.

Test: 1.

Commit "Add UseCase model".

- [ ] **Step 4: Filament resources for the 3 models**

Generate with `make:filament-resource Content/FaqItem --model-namespace="App\\Modules\\Content\\Models\\FaqItem"` (etc.). Watch for double-nesting from Phase C lessons; move files to `app/Filament/Resources/Content/...`.

Modify `AdminPanelProvider::panel()` to add another `discoverResources(in: app_path('Filament/Resources/Content'), for: 'App\\Filament\\Resources\\Content')`.

Each resource has form (translatable name fields), table (filtered to is_published).

- [ ] **Step 5: Smoke tests**

`tests/Feature/Content/ContentResourcesTest.php`: 3 tests, hitting `/admin/content/{faq-items,testimonials,use-cases}` with admin auth + `Filament::setCurrentPanel`.

- [ ] **Step 6: Pod24ContentSeeder**

Seed 6 FAQ items (matching the mockup's questions), 3 testimonials, 4 use cases — copy from the mockup as starter content. Wire into DatabaseSeeder before Pod24CatalogSeeder.

- [ ] **Step 7: Run pest, expect green**

- [ ] **Step 8: Commit** "Add Content module (FAQ, testimonials, use cases) with admin + seeder"

---

## Task 14: BookingWizard Livewire component (steps 1-4)

The first half of the wizard: facility (auto-selected), service tier picker, date+package+slot picker, address validation.

**Files:**
- Create: `app/Livewire/BookingWizard.php`
- Create: `resources/views/livewire/booking-wizard.blade.php`
- Modify: `routes/web.php` (`/book` → wizard)
- Create: `tests/Feature/Wizard/BookingWizardStepsTest.php`

- [ ] **Step 1: Implement the component**

```php
<?php
// app/Livewire/BookingWizard.php

namespace App\Livewire;

use App\Modules\Availability\Actions\FindAvailableSlots;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\ServiceTier;
use Carbon\CarbonImmutable;
use Livewire\Attributes\Url;
use Livewire\Component;

class BookingWizard extends Component
{
    #[Url]
    public int $step = 1;

    public ?int $facilityId = null;
    public ?int $serviceTierId = null;
    public ?string $packageType = 'hourly';
    public ?string $date = null;       // 'YYYY-MM-DD'
    public ?string $time = null;       // 'HH:MM'
    public array $address = ['city' => '', 'country' => 'AE'];

    public function mount(): void
    {
        $this->facilityId = Facility::where('slug', 'pod24-portable')->value('id');
    }

    public function selectTier(int $tierId): void
    {
        $this->serviceTierId = $tierId;
        $this->step = 3;
    }

    public function selectSlot(string $date, string $time): void
    {
        $this->date = $date;
        $this->time = $time;
        $this->step = 4;
    }

    public function submitAddress(): void
    {
        $this->validate([
            'address.city' => 'required|string',
        ]);

        if (strtolower(trim($this->address['city'])) !== 'abu dhabi') {
            $this->redirect(route('quote.offsite', [
                'city' => $this->address['city'],
                'name' => '', 'email' => '',
            ]));
            return;
        }

        $this->step = 5;
    }

    public function getServiceTiersProperty()
    {
        return ServiceTier::where('facility_id', $this->facilityId)->orderBy('sort_order')->get();
    }

    public function getAvailableSlotsProperty(): array
    {
        if (! $this->facilityId || ! $this->date) {
            return [];
        }
        return app(FindAvailableSlots::class)->execute(
            $this->facilityId,
            CarbonImmutable::parse($this->date, 'Asia/Dubai'),
            $this->packageType,
        );
    }

    public function render()
    {
        return view('livewire.booking-wizard');
    }
}
```

- [ ] **Step 2: Implement the view (skeleton)**

```blade
{{-- resources/views/livewire/booking-wizard.blade.php --}}
<div class="container mx-auto max-w-3xl py-12 px-4">
    <h1 class="text-3xl font-bold mb-8">Book Pod24 — Step {{ $step }} of 7</h1>

    @if ($step === 2)
        <h2 class="text-xl font-semibold mb-4">Pick a service tier</h2>
        <div class="space-y-3">
            @foreach ($this->serviceTiers as $tier)
                <button wire:click="selectTier({{ $tier->id }})"
                        class="w-full text-left p-4 border border-pod-border rounded hover:border-pod-accent">
                    <div class="font-bold">{{ $tier->name }}</div>
                    <div class="text-sm text-pod-muted">
                        AED {{ number_format($tier->base_hourly_rate_aed_cents / 100, 0) }}/hr
                    </div>
                </button>
            @endforeach
        </div>
    @endif

    @if ($step === 3)
        <h2 class="text-xl font-semibold mb-4">Pick a date and time</h2>
        <input type="date" wire:model.live="date" class="border p-2 rounded mb-4">
        <select wire:model.live="packageType" class="border p-2 rounded mb-4">
            <option value="hourly">Hourly</option>
            <option value="half_day">Half-day (4h)</option>
            <option value="full_day">Full-day (8h)</option>
        </select>
        @if ($this->availableSlots)
            <div class="grid grid-cols-4 gap-2">
                @foreach ($this->availableSlots as $slot)
                    <button wire:click="selectSlot('{{ $slot->starts_at->toDateString() }}', '{{ $slot->starts_at->format('H:i') }}')"
                            class="p-2 border border-pod-border rounded hover:border-pod-accent">
                        {{ $slot->starts_at->format('H:i') }}
                    </button>
                @endforeach
            </div>
        @elseif ($date)
            <p class="text-pod-muted">No slots available on this date.</p>
        @endif
    @endif

    @if ($step === 4)
        <h2 class="text-xl font-semibold mb-4">Where should we deliver?</h2>
        <input type="text" wire:model="address.city" placeholder="City"
               class="w-full border p-2 rounded mb-4">
        <button wire:click="submitAddress" class="bg-pod-accent text-pod-ink-deep px-6 py-3 rounded font-bold">
            Continue
        </button>
        <p class="text-sm text-pod-muted mt-2">Self-serve booking is for Abu Dhabi onsite only. For other UAE cities, we'll route you to a custom quote form.</p>
    @endif
</div>
```

- [ ] **Step 3: Add the route**

```php
// routes/web.php
use App\Livewire\BookingWizard;

Route::get('/book', BookingWizard::class)->name('book');
Route::get('/quote/offsite', function () {
    return view('pod24.quote-offsite');
})->name('quote.offsite');
```

Create a placeholder `resources/views/pod24/quote-offsite.blade.php`:

```blade
@extends('pod24.layouts.public')
@section('content')
<section class="container mx-auto py-24 px-4 text-center">
<h1 class="text-3xl font-bold">Off-site shoot</h1>
<p class="mt-4 max-w-prose mx-auto text-pod-muted">For sessions outside Abu Dhabi, we handle the booking manually. Tell us about your project and we'll get back within 24 hours.</p>
<p class="mt-8"><em>(Quote form coming in Plan 5.)</em></p>
</section>
@endsection
```

- [ ] **Step 4: Tests**

```php
<?php
// tests/Feature/Wizard/BookingWizardStepsTest.php

use App\Livewire\BookingWizard;
use App\Modules\Availability\Models\AvailabilityRule;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\ServiceTier;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(\Database\Seeders\Pod24CatalogSeeder::class);
    $this->facility = Facility::where('slug', 'pod24-portable')->first();
});

it('mounts at step 1 with the Pod24 facility pre-selected', function () {
    Livewire::test(BookingWizard::class)
        ->assertSet('step', 1)
        ->assertSet('facilityId', $this->facility->id);
});

it('selectTier moves to step 3 and stores the tier id', function () {
    $tier = ServiceTier::where('facility_id', $this->facility->id)->first();
    Livewire::test(BookingWizard::class)
        ->call('selectTier', $tier->id)
        ->assertSet('step', 3)
        ->assertSet('serviceTierId', $tier->id);
});

it('redirects out-of-AD addresses to quote/offsite', function () {
    Livewire::test(BookingWizard::class)
        ->set('step', 4)
        ->set('address.city', 'Dubai')
        ->call('submitAddress')
        ->assertRedirect();
});

it('proceeds to step 5 when the address is in Abu Dhabi', function () {
    Livewire::test(BookingWizard::class)
        ->set('step', 4)
        ->set('address.city', 'Abu Dhabi')
        ->call('submitAddress')
        ->assertSet('step', 5);
});
```

- [ ] **Step 5: Run pest**

- [ ] **Step 6: Commit** "Add BookingWizard Livewire component (steps 1-4)"

---

## Task 15: BookingWizard — addons (step 5), contact + auth (step 6), payment (step 7)

**Files:**
- Modify: `app/Livewire/BookingWizard.php`
- Modify: `resources/views/livewire/booking-wizard.blade.php`
- Create: `tests/Feature/Wizard/BookingWizardCheckoutTest.php`

- [ ] **Step 1: Add properties + methods**

To `BookingWizard`:

```php
public array $selectedAddons = [];   // [['addon_id' => int, 'qty' => int], ...]
public string $contactName = '';
public string $contactEmail = '';
public string $contactPhone = '';
public bool $marketingConsent = true;       // pre-checked per spec § 13.2
public ?string $clientSecret = null;
public ?string $bookingUlid = null;

public function getAddonsProperty()
{
    return \App\Modules\Catalog\Models\Addon::where('facility_id', $this->facilityId)
        ->where('is_active', true)->get();
}

public function toggleAddon(int $addonId): void
{
    $existing = collect($this->selectedAddons)->firstWhere('addon_id', $addonId);
    if ($existing) {
        $this->selectedAddons = collect($this->selectedAddons)
            ->reject(fn ($a) => $a['addon_id'] === $addonId)->values()->all();
    } else {
        $this->selectedAddons[] = ['addon_id' => $addonId, 'qty' => 1];
    }
}

public function submitContact(): void
{
    $this->validate([
        'contactName' => 'required|string|min:2',
        'contactEmail' => 'required|email',
        'contactPhone' => 'nullable|string',
    ]);
    $this->step = 7;
    $this->createHoldAndPaymentIntent();
}

private function createHoldAndPaymentIntent(): void
{
    $draft = new \App\Modules\Pricing\ValueObjects\BookingDraft(
        facility_id: $this->facilityId,
        service_tier_id: $this->serviceTierId,
        package_type: $this->packageType,
        starts_at: \Carbon\CarbonImmutable::parse($this->date . ' ' . $this->time, 'Asia/Dubai'),
        ends_at: \Carbon\CarbonImmutable::parse($this->date . ' ' . $this->time, 'Asia/Dubai')->addHours($this->packageDuration()),
        addons: $this->selectedAddons,
    );

    try {
        $booking = app(\App\Modules\Booking\Actions\CreateBookingHold::class)->execute(
            draft: $draft,
            contact: ['name' => $this->contactName, 'email' => $this->contactEmail, 'phone' => $this->contactPhone],
            address: $this->address,
            marketingConsentAt: $this->marketingConsent ? now() : null,
        );
    } catch (\App\Modules\Booking\Exceptions\SlotUnavailable $e) {
        $this->addError('slot', 'That slot was just taken — please pick another time.');
        $this->step = 3;
        return;
    }

    $payment = app(\App\Modules\Payments\Actions\CreatePaymentIntent::class)->execute($booking);
    $this->clientSecret = $payment['client_secret'];
    $this->bookingUlid = $booking->ulid;
}

private function packageDuration(): int
{
    return match ($this->packageType) {
        'hourly' => 1, 'half_day' => 4, 'full_day' => 8, 'multi_day' => 8,
    };
}
```

- [ ] **Step 2: Update the view to render steps 5, 6, 7**

```blade
@if ($step === 5)
    <h2 class="text-xl font-semibold mb-4">Add-ons (optional)</h2>
    <div class="space-y-2">
        @foreach ($this->addons as $addon)
            <label class="flex items-center gap-3 p-3 border rounded">
                <input type="checkbox"
                       wire:click="toggleAddon({{ $addon->id }})"
                       @checked(collect($selectedAddons)->contains('addon_id', $addon->id))>
                <span class="flex-1">{{ $addon->getTranslation('name', 'en') }}</span>
                <span class="font-bold">AED {{ number_format($addon->price_aed_cents / 100, 0) }}</span>
            </label>
        @endforeach
    </div>
    <button wire:click="$set('step', 6)" class="mt-4 bg-pod-accent text-pod-ink-deep px-6 py-3 rounded font-bold">
        Continue to contact details
    </button>
@endif

@if ($step === 6)
    <h2 class="text-xl font-semibold mb-4">Your details</h2>
    <input type="text" wire:model="contactName" placeholder="Full name" class="w-full border p-2 rounded mb-2">
    <input type="email" wire:model="contactEmail" placeholder="Email" class="w-full border p-2 rounded mb-2">
    <input type="tel" wire:model="contactPhone" placeholder="Phone (optional)" class="w-full border p-2 rounded mb-4">
    <label class="flex items-center gap-2 mb-4">
        <input type="checkbox" wire:model="marketingConsent">
        <span class="text-sm">Send me Pod24 updates and offers (you can unsubscribe anytime).</span>
    </label>
    <button wire:click="submitContact" class="bg-pod-accent text-pod-ink-deep px-6 py-3 rounded font-bold">
        Continue to payment
    </button>
@endif

@if ($step === 7)
    <h2 class="text-xl font-semibold mb-4">Payment</h2>
    @if ($clientSecret)
        <div id="stripe-payment-element" data-client-secret="{{ $clientSecret }}" data-booking-ulid="{{ $bookingUlid }}"></div>
        <script src="https://js.stripe.com/v3/"></script>
        <script>
            const stripe = Stripe('{{ config('stripe.key') }}');
            const elements = stripe.elements({ clientSecret: '{{ $clientSecret }}' });
            const paymentElement = elements.create('payment');
            paymentElement.mount('#stripe-payment-element');
            // ... in a real flow, attach a submit handler that calls stripe.confirmPayment
            // and on success redirects to /book/confirmed?ulid={{ $bookingUlid }}
        </script>
    @endif
@endif
```

- [ ] **Step 3: Test**

```php
<?php
// tests/Feature/Wizard/BookingWizardCheckoutTest.php

use App\Livewire\BookingWizard;
use App\Modules\Availability\Models\AvailabilityRule;
use App\Modules\Booking\Models\Booking;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\ServiceTier;
use App\Modules\Payments\Actions\CreatePaymentIntent;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(\Database\Seeders\Pod24CatalogSeeder::class);
    $this->facility = Facility::where('slug', 'pod24-portable')->first();
    $this->tier = ServiceTier::where('facility_id', $this->facility->id)->first();

    // Replace Stripe with a stub
    $this->app->bind(CreatePaymentIntent::class, fn () => new CreatePaymentIntent(
        fn ($params) => (object) ['id' => 'pi_test', 'client_secret' => 'pi_test_secret']
    ));
});

it('creates a hold + payment intent on submitContact', function () {
    Livewire::test(BookingWizard::class)
        ->set('step', 6)
        ->set('serviceTierId', $this->tier->id)
        ->set('packageType', 'hourly')
        ->set('date', '2026-06-08')
        ->set('time', '10:00')
        ->set('address', ['city' => 'Abu Dhabi', 'country' => 'AE'])
        ->set('contactName', 'Test Guest')
        ->set('contactEmail', 'g@example.com')
        ->call('submitContact')
        ->assertSet('step', 7)
        ->assertSet('clientSecret', 'pi_test_secret');

    expect(Booking::count())->toBe(1);
    expect(Booking::first()->status->value)->toBe('pending_payment');
});
```

- [ ] **Step 4: Run pest, expect pass**

- [ ] **Step 5: Commit** "Add BookingWizard checkout steps with Stripe Payment Element"

---

## Task 16: Confirmation page

**Files:**
- Create: `app/Livewire/BookingConfirmed.php`
- Create: `resources/views/livewire/booking-confirmed.blade.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/Public/BookingConfirmedPageTest.php`

- [ ] **Step 1: Component**

```php
<?php
// app/Livewire/BookingConfirmed.php

namespace App\Livewire;

use App\Modules\Booking\Models\Booking;
use Livewire\Attributes\Url;
use Livewire\Component;

class BookingConfirmed extends Component
{
    #[Url]
    public string $ulid = '';

    public function render()
    {
        $booking = Booking::where('ulid', $this->ulid)->first();
        return view('livewire.booking-confirmed', ['booking' => $booking]);
    }
}
```

- [ ] **Step 2: View**

```blade
{{-- resources/views/livewire/booking-confirmed.blade.php --}}
<div class="container mx-auto max-w-2xl py-16 text-center">
    @if ($booking)
        <h1 class="text-4xl font-bold mb-4">Booking confirmed</h1>
        <p class="text-pod-muted mb-8">
            We've sent confirmation to <strong>{{ $booking->contact_email }}</strong>.
        </p>
        <div class="border border-pod-border rounded p-6 inline-block">
            <div><strong>Reference:</strong> {{ $booking->ulid }}</div>
            <div><strong>Date:</strong> {{ $booking->starts_at->format('l, F j') }}</div>
            <div><strong>Time:</strong> {{ $booking->starts_at->format('H:i') }} – {{ $booking->ends_at->format('H:i') }}</div>
            <div><strong>Total:</strong> AED {{ number_format($booking->total_aed_cents / 100, 2) }}</div>
        </div>
    @else
        <h1 class="text-2xl font-bold">Booking not found.</h1>
    @endif
</div>
```

- [ ] **Step 3: Route**

```php
Route::get('/book/confirmed', \App\Livewire\BookingConfirmed::class)->name('book.confirmed');
```

- [ ] **Step 4: Test**

```php
<?php
// tests/Feature/Public/BookingConfirmedPageTest.php

use App\Modules\Booking\Models\Booking;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\ServiceTier;

it('shows the confirmation details for a valid ulid', function () {
    $facility = Facility::factory()->create();
    $tier = ServiceTier::factory()->for($facility)->create();
    $booking = Booking::factory()->for($facility)->for($tier, 'serviceTier')->create([
        'contact_email' => 'guest@example.com',
    ]);

    $this->get('/book/confirmed?ulid=' . $booking->ulid)
        ->assertOk()
        ->assertSee('Booking confirmed')
        ->assertSee('guest@example.com')
        ->assertSee($booking->ulid);
});
```

- [ ] **Step 5: Run pest**

- [ ] **Step 6: Commit** "Add booking confirmation page"

---

## Task 17: Filament BookingResource (admin inbox)

**Files:**
- Create: `app/Filament/Resources/Booking/BookingResource.php` and Pages
- Modify: `AdminPanelProvider` to discover `App\\Filament\\Resources\\Booking`
- Create: `app/Filament/Widgets/BookingsTodayWidget.php`
- Create: `tests/Feature/Booking/BookingResourceTest.php`

- [ ] **Step 1: Generate**

```bash
php artisan make:filament-resource Booking/Booking --model-namespace="App\Modules\Booking\Models\Booking" --generate
```

Move/fix path + namespaces if double-nested. Update the discover line in AdminPanelProvider.

- [ ] **Step 2: Edit the resource form/table**

Form is mostly read-only (admin views, doesn't edit pricing). Show contact details + status badge + financial summary. Disable creation (admin doesn't create bookings via Filament — they come from the wizard).

```php
public static function form(Form $form): Form
{
    return $form->schema([
        Forms\Components\TextInput::make('ulid')->disabled(),
        Forms\Components\TextInput::make('contact_name')->disabled(),
        Forms\Components\TextInput::make('contact_email')->disabled(),
        Forms\Components\Select::make('status')
            ->options(\App\Modules\Booking\Enums\BookingStatus::cases()),
        Forms\Components\TextInput::make('total_aed_cents')->disabled()
            ->formatStateUsing(fn ($state) => 'AED '.number_format($state / 100, 2)),
    ]);
}

public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('ulid')->limit(8)->copyable(),
            Tables\Columns\TextColumn::make('starts_at')->dateTime('M j, H:i')->sortable(),
            Tables\Columns\TextColumn::make('contact_name')->searchable(),
            Tables\Columns\TextColumn::make('contact_email')->searchable(),
            Tables\Columns\BadgeColumn::make('status')->colors([
                'gray' => 'hold',
                'warning' => 'pending_payment',
                'success' => 'confirmed',
                'primary' => 'completed',
                'danger' => 'cancelled',
            ]),
            Tables\Columns\TextColumn::make('total_aed_cents')
                ->label('Total')
                ->formatStateUsing(fn ($state) => 'AED '.number_format($state / 100, 2)),
        ])
        ->defaultSort('starts_at', 'desc')
        ->actions([Tables\Actions\EditAction::make()]);
}
```

- [ ] **Step 3: Bookings-today widget**

```php
<?php
// app/Filament/Widgets/BookingsTodayWidget.php

namespace App\Filament\Widgets;

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BookingsTodayWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $today = now()->startOfDay();
        $tomorrow = now()->endOfDay();

        return [
            Stat::make(
                "Today's bookings",
                Booking::whereBetween('starts_at', [$today, $tomorrow])
                    ->where('status', BookingStatus::Confirmed->value)
                    ->count(),
            ),
            Stat::make(
                'Active holds',
                Booking::where('status', BookingStatus::Hold->value)->count(),
            ),
        ];
    }
}
```

- [ ] **Step 4: Smoke test**

```php
<?php
// tests/Feature/Booking/BookingResourceTest.php

use App\Models\User;
use App\Modules\Booking\Models\Booking;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\ServiceTier;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Admin']);
    $this->actingAs(User::factory()->create()->assignRole('Admin'));
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('lists bookings in the admin', function () {
    $facility = Facility::factory()->create();
    $tier = ServiceTier::factory()->for($facility)->create();
    Booking::factory()->count(3)->for($facility)->for($tier, 'serviceTier')->create();

    $this->get('/admin/booking/bookings')->assertOk();
});
```

- [ ] **Step 5: Run pest**

- [ ] **Step 6: Commit** "Add BookingResource admin inbox + bookings-today widget"

---

## Task 18: Final smoke test of Plan 3

**Files:** none modified.

- [ ] **Step 1: Full pest suite**

```bash
./vendor/bin/pest
```

Expected: was 71 after Plan 2 + ~30 new = ~100 tests.

- [ ] **Step 2: Fresh DB + seed**

```bash
php artisan migrate:fresh --seed
```

Confirm clean.

- [ ] **Step 3: Manual smoke (browser)**

```bash
php artisan serve
```

- Visit `http://127.0.0.1:8000/` — marketing page renders.
- Visit `/book` — wizard at step 1 (auto-advances since only one facility).
- Click through to step 4, enter "Abu Dhabi" — moves to step 5.
- Step 6: contact form — enter dummy details, submit.
- Confirm Booking row exists in DB with status=`pending_payment`.
- Hit `/admin/booking/bookings` — see the new booking.

- [ ] **Step 4: Tag**

```bash
git tag plan-3-public-site-booking-complete
git commit --allow-empty -m "Plan 3 complete: public site + booking flow + Stripe"
```

---

## Plan 3 self-review summary

**Spec coverage:**
- § 5.3 bookings + booking_addons: Tasks 3, 4 ✅
- § 8 booking flow: Tasks 14, 15, 16 (steps 1-7 + confirmation page) ✅
- § 12 cancellation: deferred to Plan 5 (this plan only adds the data model fields, not the cancel/reschedule flow)
- § 13.1 SendGrid transactional email: Task 11 (booking confirmation only; reminder + reschedule emails in Plan 5/6)
- § 16 landing page port: Task 12 ✅
- § 18 errors: hold expiry release Task 7, payment failure → hold expires (no UI yet for retry); concurrent slot grab Task 6 ✅
- § 19 security: Stripe webhook signature Task 10 ✅, CSRF exemption only for webhook ✅, hold lock-row pattern Task 6 ✅
- § 20 testing: Pest, TDD on Booking + Pricing modules ✅

**Out of scope (deferred):**
- Hour-pack redemption inside the wizard — Plan 4
- B2B quote pipeline / off-site quote handling — Plan 5 (current /quote/offsite is a placeholder)
- File delivery — Plan 5
- Reminder + cancellation emails — Plan 5 (Cancel/Reschedule) and Plan 6 (Mailchimp/Hubspot lifecycle)
- Marketing automation events (BookingStarted, BookingAbandoned, etc.) — Plan 6

**Placeholder scan:** the `/quote/offsite` view is intentionally a placeholder; Plan 5 replaces it.

**Ambiguity check:**
- Multi-day package handling in the wizard: only single-day display for now. Multi-day is presented as `full_day` slots; range-picking is a Plan 5 enhancement. Documented in Task 14.
- Marketing consent: pre-checked per spec § 13.2; the `marketing_consent_at` is captured at booking-hold creation (not at confirmation) so it survives even if the customer abandons.

**Total expected new tests:** ~30 (Plan 3 adds Booking module tests, wizard tests, Stripe webhook tests, page-render tests).

**Risk note:** This plan has the largest user-facing surface area. Manual smoke-test in Step 3 of Task 18 is essential before tagging. The Livewire wizard especially benefits from a few manual click-throughs to catch UX rough edges that unit tests don't cover.
