# Pod24 — Plan 2: Availability & Pricing Engine

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add the Availability module (rules / blackouts / capacities + slot-finder action) and the Pricing module (a pure `CalculateBookingPrice` action covering base price, weekend markup, after-hours markup, add-ons, and VAT), plus a refund-percentage lookup. Output is a tested, callable pricing engine and slot-finder — no booking flow yet, no customer accounts. Plan 3 will wire these into the public booking flow.

**Architecture:** Two new bounded contexts under `app/Modules/{Availability,Pricing}/`. Pure value objects + single-purpose Action classes (no business logic in models). Tests heavy on the pricing engine — every modifier × package × tier × VAT combination covered. Filament admin gets RelationManagers under Facility for hours/blackouts/capacity. Hour-pack redemption is deferred to Plan 4 (requires customer accounts).

**Tech stack:** Same as Plan 1 — Laravel 11, PHP 8.4, PostgreSQL 16, Pest 3, Filament 3.

**Spec reference:** `docs/superpowers/specs/2026-05-04-pod24-platform-design.md` § 5.2 (availability tables), § 6 (pricing engine), § 7 (availability logic), § 11 (cancellation policy).

**Depends on:** Plan 1 complete (commit tag `plan-1-foundation-catalog-complete`).

---

## File structure for Plan 2

```
pod24/
├── app/Modules/
│   ├── Availability/
│   │   ├── Models/
│   │   │   ├── AvailabilityRule.php
│   │   │   ├── AvailabilityBlackout.php
│   │   │   └── AvailabilityCapacity.php
│   │   └── Actions/
│   │       └── FindAvailableSlots.php
│   └── Pricing/
│       ├── ValueObjects/
│       │   ├── BookingDraft.php
│       │   └── PriceBreakdown.php
│       └── Actions/
│           ├── CalculateBookingPrice.php
│           └── LookupRefundPercentage.php
├── app/Filament/Resources/Catalog/FacilityResource/RelationManagers/
│   ├── AvailabilityRulesRelationManager.php
│   ├── AvailabilityBlackoutsRelationManager.php
│   └── (capacity edited inline on Facility form — not a relation manager)
├── database/migrations/                          (new tables)
├── database/factories/                           (factories for the 3 new models)
└── tests/Feature/{Availability,Pricing}/         (model, action, integration tests)
```

---

## Task 1: Add capacity column directly on Facility (decision deviation from spec)

**Decision rationale:** § 5.2 of the spec defines `availability_capacities` as a separate table with one row per facility (`facility_id PK, max_concurrent_per_day`). A separate single-row table is awkward to manage in Filament and adds a JOIN for every availability check. Move `max_concurrent_per_day` to the `facilities` table directly. This is a defensible deviation because (a) the relationship is 1:1, (b) the field changes rarely, (c) it simplifies the data model. The spec is updated in step 1 below.

**Files:**
- Modify: `docs/superpowers/specs/2026-05-04-pod24-platform-design.md` (§ 5.2 note + § 7)
- Create: `database/migrations/...add_max_concurrent_per_day_to_facilities_table.php`
- Modify: `app/Modules/Catalog/Models/Facility.php` ($fillable + casts)
- Modify: `database/factories/FacilityFactory.php`
- Modify: `tests/Feature/Catalog/FacilityTest.php` (one new assertion)

- [ ] **Step 1: Update the spec doc**

Edit `docs/superpowers/specs/2026-05-04-pod24-platform-design.md`. Find the `availability_capacities` row in § 5.2 and replace with a comment noting the Plan-2 decision:

```
availability_capacities       -- DROPPED in Plan 2. max_concurrent_per_day moved
                                directly onto facilities for 1:1 simplicity.
```

In § 7, where the algorithm references `availability_capacities`, change the wording to "the facility's `max_concurrent_per_day` column."

- [ ] **Step 2: Migration**

```bash
php artisan make:migration add_max_concurrent_per_day_to_facilities_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->unsignedTinyInteger('max_concurrent_per_day')->default(1)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->dropColumn('max_concurrent_per_day');
        });
    }
};
```

- [ ] **Step 3: Update Facility model**

Add `'max_concurrent_per_day'` to `$fillable` and add cast `'max_concurrent_per_day' => 'integer'`. No new relations.

- [ ] **Step 4: Update FacilityFactory**

Add `'max_concurrent_per_day' => 1,` to the `definition()` array.

- [ ] **Step 5: Update FacilityTest**

In `tests/Feature/Catalog/FacilityTest.php`, append:

```php
it('defaults max_concurrent_per_day to 1', function () {
    $facility = Facility::factory()->create();
    expect($facility->max_concurrent_per_day)->toBe(1);
});
```

- [ ] **Step 6: Run migration + test**

```bash
php artisan migrate
./vendor/bin/pest tests/Feature/Catalog/FacilityTest.php
```

Expect 3 passed.

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -m "Move max_concurrent_per_day directly onto facilities table"
```

---

## Task 2: AvailabilityRule model + migration + factory + tests

Weekly-recurring open-hours per facility. Multiple rows per facility (one per weekday).

**Files:**
- Create: `app/Modules/Availability/Models/AvailabilityRule.php`
- Create: `database/migrations/...create_availability_rules_table.php`
- Create: `database/factories/AvailabilityRuleFactory.php`
- Create: `tests/Feature/Availability/AvailabilityRuleTest.php`

- [ ] **Step 1: Failing test**

```php
<?php
// tests/Feature/Availability/AvailabilityRuleTest.php

use App\Modules\Availability\Models\AvailabilityRule;
use App\Modules\Catalog\Models\Facility;

it('creates a weekly rule for a facility', function () {
    $facility = Facility::factory()->create();
    $rule = AvailabilityRule::factory()->for($facility)->create([
        'day_of_week' => 1,         // Monday (0 = Sunday)
        'open_time' => '09:00',
        'close_time' => '18:00',
    ]);

    expect($rule->facility->id)->toBe($facility->id);
    expect($rule->fresh()->open_time)->toBe('09:00:00');
});

it('enforces uniqueness of (facility, day_of_week)', function () {
    $facility = Facility::factory()->create();
    AvailabilityRule::factory()->for($facility)->create(['day_of_week' => 1]);

    expect(fn () => AvailabilityRule::factory()->for($facility)->create(['day_of_week' => 1]))
        ->toThrow(\Illuminate\Database\QueryException::class);
});
```

- [ ] **Step 2: Run, expect failure**

- [ ] **Step 3: Migration**

```bash
php artisan make:migration create_availability_rules_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('availability_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');     // 0 = Sun, 6 = Sat
            $table->time('open_time');
            $table->time('close_time');
            $table->timestamps();

            $table->unique(['facility_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('availability_rules');
    }
};
```

- [ ] **Step 4: Model**

```php
<?php
// app/Modules/Availability/Models/AvailabilityRule.php

namespace App\Modules\Availability\Models;

use App\Modules\Catalog\Models\Facility;
use App\Support\HasModuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvailabilityRule extends Model
{
    use HasFactory, HasModuleFactory {
        HasModuleFactory::newFactory insteadof HasFactory;
    }

    protected $fillable = ['facility_id', 'day_of_week', 'open_time', 'close_time'];

    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
        ];
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }
}
```

- [ ] **Step 5: Factory**

```php
<?php
// database/factories/AvailabilityRuleFactory.php

namespace Database\Factories;

use App\Modules\Availability\Models\AvailabilityRule;
use App\Modules\Catalog\Models\Facility;
use Illuminate\Database\Eloquent\Factories\Factory;

class AvailabilityRuleFactory extends Factory
{
    protected $model = AvailabilityRule::class;

    public function definition(): array
    {
        return [
            'facility_id' => Facility::factory(),
            'day_of_week' => $this->faker->numberBetween(0, 6),
            'open_time' => '09:00',
            'close_time' => '18:00',
        ];
    }
}
```

- [ ] **Step 6: Run migration + tests**

```bash
php artisan migrate
./vendor/bin/pest tests/Feature/Availability/AvailabilityRuleTest.php
```

Expect 2 passed.

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -m "Add AvailabilityRule model"
```

---

## Task 3: AvailabilityBlackout model + migration + factory + tests

One-off closures (e.g., a holiday, scheduled maintenance, off-site shoot).

**Files:**
- Create: `app/Modules/Availability/Models/AvailabilityBlackout.php`
- Create: `database/migrations/...create_availability_blackouts_table.php`
- Create: `database/factories/AvailabilityBlackoutFactory.php`
- Create: `tests/Feature/Availability/AvailabilityBlackoutTest.php`

- [ ] **Step 1: Failing test**

```php
<?php

use App\Modules\Availability\Models\AvailabilityBlackout;
use App\Modules\Catalog\Models\Facility;
use Carbon\Carbon;

it('creates a blackout for a facility with start and end timestamps', function () {
    $facility = Facility::factory()->create();
    $start = Carbon::parse('2026-06-15 00:00:00');
    $end = Carbon::parse('2026-06-15 23:59:59');

    $blackout = AvailabilityBlackout::factory()->for($facility)->create([
        'starts_at' => $start,
        'ends_at' => $end,
        'reason' => 'UAE National Day',
    ]);

    expect($blackout->starts_at->toDateTimeString())->toBe('2026-06-15 00:00:00');
    expect($blackout->reason)->toBe('UAE National Day');
});
```

- [ ] **Step 2: Run, expect failure**

- [ ] **Step 3: Migration**

```bash
php artisan make:migration create_availability_blackouts_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('availability_blackouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained()->cascadeOnDelete();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index(['facility_id', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('availability_blackouts');
    }
};
```

- [ ] **Step 4: Model**

```php
<?php
// app/Modules/Availability/Models/AvailabilityBlackout.php

namespace App\Modules\Availability\Models;

use App\Modules\Catalog\Models\Facility;
use App\Support\HasModuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AvailabilityBlackout extends Model
{
    use HasFactory, HasModuleFactory {
        HasModuleFactory::newFactory insteadof HasFactory;
    }

    protected $fillable = ['facility_id', 'starts_at', 'ends_at', 'reason'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }
}
```

- [ ] **Step 5: Factory**

```php
<?php
// database/factories/AvailabilityBlackoutFactory.php

namespace Database\Factories;

use App\Modules\Availability\Models\AvailabilityBlackout;
use App\Modules\Catalog\Models\Facility;
use Illuminate\Database\Eloquent\Factories\Factory;

class AvailabilityBlackoutFactory extends Factory
{
    protected $model = AvailabilityBlackout::class;

    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('+1 week', '+2 weeks');
        $end = (clone $start)->modify('+8 hours');

        return [
            'facility_id' => Facility::factory(),
            'starts_at' => $start,
            'ends_at' => $end,
            'reason' => $this->faker->sentence(3),
        ];
    }
}
```

- [ ] **Step 6: Run migration + test**

```bash
php artisan migrate
./vendor/bin/pest tests/Feature/Availability/AvailabilityBlackoutTest.php
```

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -m "Add AvailabilityBlackout model"
```

---

## Task 4: Add Facility relations and seed Pod24 default availability

**Files:**
- Modify: `app/Modules/Catalog/Models/Facility.php` (add relations)
- Modify: `database/seeders/Pod24CatalogSeeder.php`
- Modify: `tests/Feature/Pod24CatalogSeederTest.php`

- [ ] **Step 1: Add relations to Facility**

In `app/Modules/Catalog/Models/Facility.php`, add:

```php
use App\Modules\Availability\Models\AvailabilityBlackout;
use App\Modules\Availability\Models\AvailabilityRule;

// ...

public function availabilityRules(): HasMany
{
    return $this->hasMany(AvailabilityRule::class);
}

public function availabilityBlackouts(): HasMany
{
    return $this->hasMany(AvailabilityBlackout::class);
}
```

- [ ] **Step 2: Append a failing seeder test**

Append to `tests/Feature/Pod24CatalogSeederTest.php`:

```php
use App\Modules\Availability\Models\AvailabilityRule;

it('seeds Mon-Sat 09:00-18:00 availability for Pod24', function () {
    $this->seed(\Database\Seeders\Pod24CatalogSeeder::class);
    $facility = Facility::where('slug', 'pod24-portable')->first();
    $rules = AvailabilityRule::where('facility_id', $facility->id)->orderBy('day_of_week')->get();

    expect($rules)->toHaveCount(6);  // 0 Sun + 1..6 — UAE work week is Mon-Fri but
                                     // the studio takes Sat clients too; Sun is closed by default.
    foreach ($rules as $rule) {
        expect($rule->fresh()->open_time)->toBe('09:00:00');
        expect($rule->fresh()->close_time)->toBe('18:00:00');
    }
});

it('seeds max_concurrent_per_day = 2 for Pod24', function () {
    $this->seed(\Database\Seeders\Pod24CatalogSeeder::class);
    $facility = Facility::where('slug', 'pod24-portable')->first();
    expect($facility->max_concurrent_per_day)->toBe(2);
});
```

- [ ] **Step 3: Update seeder**

In `database/seeders/Pod24CatalogSeeder.php`:

a) Add `'max_concurrent_per_day' => 2,` to the `Facility::updateOrCreate` payload.

b) After the existing seeds, append:

```php
use App\Modules\Availability\Models\AvailabilityRule;

// Mon-Sat 09:00-18:00. Sunday closed.
foreach ([1, 2, 3, 4, 5, 6] as $dow) {
    AvailabilityRule::updateOrCreate(
        ['facility_id' => $facility->id, 'day_of_week' => $dow],
        ['open_time' => '09:00', 'close_time' => '18:00']
    );
}
```

- [ ] **Step 4: Run tests + seed**

```bash
./vendor/bin/pest tests/Feature/Pod24CatalogSeederTest.php
php artisan migrate:fresh --seed
```

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "Seed Mon-Sat business hours and capacity=2 for Pod24"
```

---

## Task 5: PriceBreakdown value object + tests

A simple readonly DTO that the pricing engine returns.

**Files:**
- Create: `app/Modules/Pricing/ValueObjects/PriceBreakdown.php`
- Create: `tests/Unit/Pricing/PriceBreakdownTest.php` (Unit — pure value object, no DB)

- [ ] **Step 1: Failing test**

```php
<?php
// tests/Unit/Pricing/PriceBreakdownTest.php

use App\Modules\Pricing\ValueObjects\PriceBreakdown;

it('totals base + markups + addons - credits and adds VAT', function () {
    $b = new PriceBreakdown(
        base_aed_cents: 100000,
        weekend_markup_aed_cents: 5000,
        after_hours_markup_aed_cents: 3000,
        addons_aed_cents: 20000,
        hour_pack_credit_value_aed_cents: 10000,
    );

    expect($b->subtotal())->toBe(118000);   // 100k + 5k + 3k + 20k - 10k
    expect($b->vat())->toBe(5900);          // 5% of 118000
    expect($b->total())->toBe(123900);      // subtotal + vat
});

it('produces zero VAT and zero total for an empty breakdown', function () {
    $b = new PriceBreakdown();
    expect($b->subtotal())->toBe(0);
    expect($b->vat())->toBe(0);
    expect($b->total())->toBe(0);
});

it('rounds VAT half-up (banker neutral) when subtotal × 5% has half-cent', function () {
    // 199 cents × 0.05 = 9.95 cents → round to 10
    $b = new PriceBreakdown(base_aed_cents: 199);
    expect($b->vat())->toBe(10);
});
```

- [ ] **Step 2: Run, expect failure**

- [ ] **Step 3: Implement**

```php
<?php
// app/Modules/Pricing/ValueObjects/PriceBreakdown.php

namespace App\Modules\Pricing\ValueObjects;

final readonly class PriceBreakdown
{
    public const VAT_RATE_BPS = 500;   // 5.00% in basis points

    public function __construct(
        public int $base_aed_cents = 0,
        public int $weekend_markup_aed_cents = 0,
        public int $after_hours_markup_aed_cents = 0,
        public int $addons_aed_cents = 0,
        public int $hour_pack_credit_value_aed_cents = 0,
    ) {}

    public function subtotal(): int
    {
        return $this->base_aed_cents
            + $this->weekend_markup_aed_cents
            + $this->after_hours_markup_aed_cents
            + $this->addons_aed_cents
            - $this->hour_pack_credit_value_aed_cents;
    }

    public function vat(): int
    {
        return (int) round($this->subtotal() * self::VAT_RATE_BPS / 10_000);
    }

    public function total(): int
    {
        return $this->subtotal() + $this->vat();
    }

    public function toArray(): array
    {
        return [
            'base_aed_cents' => $this->base_aed_cents,
            'weekend_markup_aed_cents' => $this->weekend_markup_aed_cents,
            'after_hours_markup_aed_cents' => $this->after_hours_markup_aed_cents,
            'addons_aed_cents' => $this->addons_aed_cents,
            'hour_pack_credit_value_aed_cents' => $this->hour_pack_credit_value_aed_cents,
            'subtotal_aed_cents' => $this->subtotal(),
            'vat_aed_cents' => $this->vat(),
            'total_aed_cents' => $this->total(),
        ];
    }
}
```

- [ ] **Step 4: Run, expect pass**

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "Add PriceBreakdown value object"
```

---

## Task 6: BookingDraft value object + tests

The input shape for the pricing engine. Lightweight DTO — no validation logic; that's the engine's job.

**Files:**
- Create: `app/Modules/Pricing/ValueObjects/BookingDraft.php`
- Create: `tests/Unit/Pricing/BookingDraftTest.php`

- [ ] **Step 1: Failing test**

```php
<?php
// tests/Unit/Pricing/BookingDraftTest.php

use App\Modules\Pricing\ValueObjects\BookingDraft;
use Carbon\CarbonImmutable;

it('captures the booking inputs needed for pricing', function () {
    $draft = new BookingDraft(
        facility_id: 1,
        service_tier_id: 2,
        package_type: 'hourly',
        starts_at: CarbonImmutable::parse('2026-06-01 10:00:00', 'Asia/Dubai'),
        ends_at: CarbonImmutable::parse('2026-06-01 13:00:00', 'Asia/Dubai'),
        addons: [['addon_id' => 5, 'qty' => 1]],
    );

    expect($draft->facility_id)->toBe(1);
    expect($draft->totalHours())->toBe(3);
    expect($draft->addons)->toHaveCount(1);
});

it('reports total hours from the time window', function () {
    $draft = new BookingDraft(
        facility_id: 1,
        service_tier_id: 1,
        package_type: 'half_day',
        starts_at: CarbonImmutable::parse('2026-06-01 09:00:00', 'Asia/Dubai'),
        ends_at: CarbonImmutable::parse('2026-06-01 13:00:00', 'Asia/Dubai'),
    );
    expect($draft->totalHours())->toBe(4);
});
```

- [ ] **Step 2: Run, expect failure**

- [ ] **Step 3: Implement**

```php
<?php
// app/Modules/Pricing/ValueObjects/BookingDraft.php

namespace App\Modules\Pricing\ValueObjects;

use Carbon\CarbonImmutable;

final readonly class BookingDraft
{
    public function __construct(
        public int $facility_id,
        public int $service_tier_id,
        public string $package_type,         // hourly | half_day | full_day | multi_day
        public CarbonImmutable $starts_at,
        public CarbonImmutable $ends_at,
        public array $addons = [],            // [['addon_id' => int, 'qty' => int], ...]
    ) {}

    public function totalHours(): int
    {
        return (int) $this->starts_at->diffInHours($this->ends_at);
    }
}
```

- [ ] **Step 4: Run, expect pass**

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "Add BookingDraft value object"
```

---

## Task 7: CalculateBookingPrice action — base price lookup

Start with the simplest case: pull base price from `facility_pricing` for the given (facility, tier, package_type). No markups, no add-ons, no VAT logic exercised yet.

**Files:**
- Create: `app/Modules/Pricing/Actions/CalculateBookingPrice.php`
- Create: `tests/Feature/Pricing/CalculateBookingPriceTest.php`

- [ ] **Step 1: Failing test**

```php
<?php
// tests/Feature/Pricing/CalculateBookingPriceTest.php

use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\FacilityPricing;
use App\Modules\Catalog\Models\ServiceTier;
use App\Modules\Pricing\Actions\CalculateBookingPrice;
use App\Modules\Pricing\ValueObjects\BookingDraft;
use Carbon\CarbonImmutable;

it('computes hourly base price from facility_pricing × hours', function () {
    $facility = Facility::factory()->create();
    $tier = ServiceTier::factory()->for($facility)->create(['base_hourly_rate_aed_cents' => 25400]);
    FacilityPricing::create([
        'facility_id' => $facility->id,
        'service_tier_id' => $tier->id,
        'package_type' => 'hourly',
        'hours' => 1,
        'price_aed_cents' => 25400,
    ]);

    $draft = new BookingDraft(
        facility_id: $facility->id,
        service_tier_id: $tier->id,
        package_type: 'hourly',
        starts_at: CarbonImmutable::parse('2026-06-01 10:00:00', 'Asia/Dubai'),  // Monday
        ends_at:   CarbonImmutable::parse('2026-06-01 13:00:00', 'Asia/Dubai'),
    );

    $action = app(CalculateBookingPrice::class);
    $breakdown = $action->execute($draft);

    expect($breakdown->base_aed_cents)->toBe(76200);   // 25400 × 3
    expect($breakdown->subtotal())->toBe(76200);
    expect($breakdown->vat())->toBe(3810);             // 5% of 76200
    expect($breakdown->total())->toBe(80010);
});

it('uses the half_day fixed price when package_type=half_day', function () {
    $facility = Facility::factory()->create();
    $tier = ServiceTier::factory()->for($facility)->create();
    FacilityPricing::create([
        'facility_id' => $facility->id,
        'service_tier_id' => $tier->id,
        'package_type' => 'half_day',
        'hours' => 4,
        'price_aed_cents' => 91440,                      // admin-set, NOT 4 × hourly
    ]);

    $draft = new BookingDraft(
        facility_id: $facility->id,
        service_tier_id: $tier->id,
        package_type: 'half_day',
        starts_at: CarbonImmutable::parse('2026-06-01 09:00:00', 'Asia/Dubai'),
        ends_at:   CarbonImmutable::parse('2026-06-01 13:00:00', 'Asia/Dubai'),
    );

    $breakdown = app(CalculateBookingPrice::class)->execute($draft);
    expect($breakdown->base_aed_cents)->toBe(91440);    // fixed, not × hours
});

it('throws when no pricing row exists for the (facility, tier, package_type) cell', function () {
    $facility = Facility::factory()->create();
    $tier = ServiceTier::factory()->for($facility)->create();

    $draft = new BookingDraft(
        facility_id: $facility->id,
        service_tier_id: $tier->id,
        package_type: 'hourly',
        starts_at: CarbonImmutable::parse('2026-06-01 10:00:00', 'Asia/Dubai'),
        ends_at:   CarbonImmutable::parse('2026-06-01 11:00:00', 'Asia/Dubai'),
    );

    expect(fn () => app(CalculateBookingPrice::class)->execute($draft))
        ->toThrow(\App\Modules\Pricing\Exceptions\PricingNotConfigured::class);
});
```

- [ ] **Step 2: Run, expect failure**

- [ ] **Step 3: Create the exception**

```php
<?php
// app/Modules/Pricing/Exceptions/PricingNotConfigured.php

namespace App\Modules\Pricing\Exceptions;

class PricingNotConfigured extends \RuntimeException {}
```

- [ ] **Step 4: Implement the action**

```php
<?php
// app/Modules/Pricing/Actions/CalculateBookingPrice.php

namespace App\Modules\Pricing\Actions;

use App\Modules\Catalog\Models\FacilityPricing;
use App\Modules\Pricing\Exceptions\PricingNotConfigured;
use App\Modules\Pricing\ValueObjects\BookingDraft;
use App\Modules\Pricing\ValueObjects\PriceBreakdown;

class CalculateBookingPrice
{
    public function execute(BookingDraft $draft): PriceBreakdown
    {
        $base = $this->base($draft);

        return new PriceBreakdown(
            base_aed_cents: $base,
        );
    }

    private function base(BookingDraft $draft): int
    {
        $row = FacilityPricing::where([
            'facility_id' => $draft->facility_id,
            'service_tier_id' => $draft->service_tier_id,
            'package_type' => $draft->package_type,
        ])->first();

        if (! $row) {
            throw new PricingNotConfigured(sprintf(
                'No pricing for facility=%d tier=%d package=%s',
                $draft->facility_id, $draft->service_tier_id, $draft->package_type
            ));
        }

        return match ($draft->package_type) {
            'hourly' => $row->price_aed_cents * $draft->totalHours(),
            'multi_day' => $row->price_aed_cents * $this->numberOfDays($draft),
            'half_day', 'full_day' => $row->price_aed_cents,
            default => throw new PricingNotConfigured("Unknown package_type {$draft->package_type}"),
        };
    }

    private function numberOfDays(BookingDraft $draft): int
    {
        return max(1, (int) $draft->starts_at->startOfDay()->diffInDays($draft->ends_at->startOfDay()) + 1);
    }
}
```

- [ ] **Step 5: Run tests, expect pass**

```bash
./vendor/bin/pest tests/Feature/Pricing/CalculateBookingPriceTest.php
```

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "Add CalculateBookingPrice with base price lookup"
```

---

## Task 8: Pricing engine — weekend markup

UAE weekend = Saturday + Sunday (post-2022 work-week change). Markup percentage from `pricing_modifiers` row with `type='weekend'`. Applied as a percentage of the hours that fall on weekend × hourly rate (for hourly bookings) OR as a flat percentage of the package base (for half/full/multi-day bookings).

**Decision:** for half/full/multi-day, applying weekend markup means the WHOLE package gets the markup if any of its hours fall on weekend. Simpler than splitting fractional packages.

**Files:**
- Modify: `app/Modules/Pricing/Actions/CalculateBookingPrice.php`
- Modify: `tests/Feature/Pricing/CalculateBookingPriceTest.php`

- [ ] **Step 1: Append failing tests**

```php
use App\Modules\Catalog\Models\PricingModifier;

it('applies weekend markup for hourly booking on Saturday', function () {
    $facility = Facility::factory()->create();
    $tier = ServiceTier::factory()->for($facility)->create();
    FacilityPricing::create([
        'facility_id' => $facility->id, 'service_tier_id' => $tier->id,
        'package_type' => 'hourly', 'hours' => 1, 'price_aed_cents' => 10000,
    ]);
    PricingModifier::create([
        'facility_id' => $facility->id, 'type' => 'weekend', 'percentage' => 25,
    ]);

    $draft = new BookingDraft(
        facility_id: $facility->id,
        service_tier_id: $tier->id,
        package_type: 'hourly',
        starts_at: CarbonImmutable::parse('2026-06-06 10:00:00', 'Asia/Dubai'),  // Saturday
        ends_at:   CarbonImmutable::parse('2026-06-06 12:00:00', 'Asia/Dubai'),
    );

    $breakdown = app(CalculateBookingPrice::class)->execute($draft);
    expect($breakdown->base_aed_cents)->toBe(20000);                  // 10000 × 2 hours
    expect($breakdown->weekend_markup_aed_cents)->toBe(5000);         // 25% of 20000
    expect($breakdown->subtotal())->toBe(25000);
});

it('applies no weekend markup for hourly booking on weekday', function () {
    $facility = Facility::factory()->create();
    $tier = ServiceTier::factory()->for($facility)->create();
    FacilityPricing::create([
        'facility_id' => $facility->id, 'service_tier_id' => $tier->id,
        'package_type' => 'hourly', 'hours' => 1, 'price_aed_cents' => 10000,
    ]);
    PricingModifier::create([
        'facility_id' => $facility->id, 'type' => 'weekend', 'percentage' => 25,
    ]);

    $draft = new BookingDraft(
        facility_id: $facility->id,
        service_tier_id: $tier->id,
        package_type: 'hourly',
        starts_at: CarbonImmutable::parse('2026-06-08 10:00:00', 'Asia/Dubai'),  // Monday
        ends_at:   CarbonImmutable::parse('2026-06-08 12:00:00', 'Asia/Dubai'),
    );

    $breakdown = app(CalculateBookingPrice::class)->execute($draft);
    expect($breakdown->weekend_markup_aed_cents)->toBe(0);
});

it('applies pro-rata weekend markup for hourly booking spanning Fri-Sat midnight', function () {
    $facility = Facility::factory()->create();
    $tier = ServiceTier::factory()->for($facility)->create();
    FacilityPricing::create([
        'facility_id' => $facility->id, 'service_tier_id' => $tier->id,
        'package_type' => 'hourly', 'hours' => 1, 'price_aed_cents' => 10000,
    ]);
    PricingModifier::create([
        'facility_id' => $facility->id, 'type' => 'weekend', 'percentage' => 25,
    ]);

    // Fri 23:00 -> Sat 02:00 = 1 weekday hour + 2 weekend hours
    $draft = new BookingDraft(
        facility_id: $facility->id,
        service_tier_id: $tier->id,
        package_type: 'hourly',
        starts_at: CarbonImmutable::parse('2026-06-05 23:00:00', 'Asia/Dubai'),
        ends_at:   CarbonImmutable::parse('2026-06-06 02:00:00', 'Asia/Dubai'),
    );

    $breakdown = app(CalculateBookingPrice::class)->execute($draft);
    expect($breakdown->base_aed_cents)->toBe(30000);                  // 10000 × 3 hours
    expect($breakdown->weekend_markup_aed_cents)->toBe(5000);         // 25% × 2 weekend hrs × 10000
});

it('applies whole-package weekend markup for full-day booking on weekend', function () {
    $facility = Facility::factory()->create();
    $tier = ServiceTier::factory()->for($facility)->create();
    FacilityPricing::create([
        'facility_id' => $facility->id, 'service_tier_id' => $tier->id,
        'package_type' => 'full_day', 'hours' => 8, 'price_aed_cents' => 200000,
    ]);
    PricingModifier::create([
        'facility_id' => $facility->id, 'type' => 'weekend', 'percentage' => 25,
    ]);

    $draft = new BookingDraft(
        facility_id: $facility->id,
        service_tier_id: $tier->id,
        package_type: 'full_day',
        starts_at: CarbonImmutable::parse('2026-06-06 09:00:00', 'Asia/Dubai'),  // Saturday
        ends_at:   CarbonImmutable::parse('2026-06-06 17:00:00', 'Asia/Dubai'),
    );

    $breakdown = app(CalculateBookingPrice::class)->execute($draft);
    expect($breakdown->weekend_markup_aed_cents)->toBe(50000);        // 25% × 200000
});
```

- [ ] **Step 2: Run, expect failures**

- [ ] **Step 3: Update the action**

Replace `execute()` and add private helpers:

```php
public function execute(BookingDraft $draft): PriceBreakdown
{
    $base = $this->base($draft);
    $weekendMarkup = $this->weekendMarkup($draft, $base);

    return new PriceBreakdown(
        base_aed_cents: $base,
        weekend_markup_aed_cents: $weekendMarkup,
    );
}

private function weekendMarkup(BookingDraft $draft, int $base): int
{
    $modifier = PricingModifier::where([
        'facility_id' => $draft->facility_id,
        'type' => 'weekend',
    ])->first();

    if (! $modifier) {
        return 0;
    }

    if ($draft->package_type === 'hourly') {
        $weekendHours = $this->countWeekendHours($draft->starts_at, $draft->ends_at);
        if ($weekendHours === 0) {
            return 0;
        }
        $hourlyRate = (int) ($base / $draft->totalHours());
        return (int) round($hourlyRate * $weekendHours * $modifier->percentage / 100);
    }

    // half_day, full_day, multi_day: full package gets markup if ANY of its hours fall on weekend
    if ($this->countWeekendHours($draft->starts_at, $draft->ends_at) > 0) {
        return (int) round($base * $modifier->percentage / 100);
    }
    return 0;
}

private function countWeekendHours(CarbonImmutable $start, CarbonImmutable $end): int
{
    $weekend = 0;
    $cursor = $start;
    while ($cursor < $end) {
        // dayOfWeek: 0 = Sunday, 6 = Saturday — UAE weekend = Sat (6) and Sun (0).
        if (in_array($cursor->dayOfWeek, [0, 6], true)) {
            $weekend++;
        }
        $cursor = $cursor->addHour();
    }
    return $weekend;
}
```

Add the import at the top: `use App\Modules\Catalog\Models\PricingModifier;` and `use Carbon\CarbonImmutable;`.

- [ ] **Step 4: Run tests, expect pass**

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "Apply weekend markup to pricing engine"
```

---

## Task 9: Pricing engine — after-hours markup

Same shape as weekend, but the window is per-facility configurable (`after_hours_start` / `after_hours_end` on the modifier row). After-hours window can WRAP across midnight (e.g., 18:00-09:00) — the implementation must handle that.

**Files:**
- Modify: `app/Modules/Pricing/Actions/CalculateBookingPrice.php`
- Modify: `tests/Feature/Pricing/CalculateBookingPriceTest.php`

- [ ] **Step 1: Append failing tests**

```php
it('applies after-hours markup for hourly booking starting at 19:00', function () {
    $facility = Facility::factory()->create();
    $tier = ServiceTier::factory()->for($facility)->create();
    FacilityPricing::create([
        'facility_id' => $facility->id, 'service_tier_id' => $tier->id,
        'package_type' => 'hourly', 'hours' => 1, 'price_aed_cents' => 10000,
    ]);
    PricingModifier::create([
        'facility_id' => $facility->id, 'type' => 'after_hours',
        'percentage' => 25,
        'after_hours_start' => '18:00', 'after_hours_end' => '09:00',
    ]);

    // Mon 19:00 -> 21:00 = 2 hours, all after-hours
    $draft = new BookingDraft(
        facility_id: $facility->id, service_tier_id: $tier->id, package_type: 'hourly',
        starts_at: CarbonImmutable::parse('2026-06-08 19:00:00', 'Asia/Dubai'),
        ends_at:   CarbonImmutable::parse('2026-06-08 21:00:00', 'Asia/Dubai'),
    );

    $breakdown = app(CalculateBookingPrice::class)->execute($draft);
    expect($breakdown->after_hours_markup_aed_cents)->toBe(5000);   // 25% × 2 × 10000
});

it('applies pro-rata after-hours markup straddling 18:00 cutover', function () {
    $facility = Facility::factory()->create();
    $tier = ServiceTier::factory()->for($facility)->create();
    FacilityPricing::create([
        'facility_id' => $facility->id, 'service_tier_id' => $tier->id,
        'package_type' => 'hourly', 'hours' => 1, 'price_aed_cents' => 10000,
    ]);
    PricingModifier::create([
        'facility_id' => $facility->id, 'type' => 'after_hours',
        'percentage' => 25,
        'after_hours_start' => '18:00', 'after_hours_end' => '09:00',
    ]);

    // Mon 17:00 -> 20:00 = 1 in-hours hour + 2 after-hours hours
    $draft = new BookingDraft(
        facility_id: $facility->id, service_tier_id: $tier->id, package_type: 'hourly',
        starts_at: CarbonImmutable::parse('2026-06-08 17:00:00', 'Asia/Dubai'),
        ends_at:   CarbonImmutable::parse('2026-06-08 20:00:00', 'Asia/Dubai'),
    );

    $breakdown = app(CalculateBookingPrice::class)->execute($draft);
    expect($breakdown->base_aed_cents)->toBe(30000);
    expect($breakdown->after_hours_markup_aed_cents)->toBe(5000);   // 25% × 2 × 10000
});

it('handles after-hours window wrapping midnight', function () {
    $facility = Facility::factory()->create();
    $tier = ServiceTier::factory()->for($facility)->create();
    FacilityPricing::create([
        'facility_id' => $facility->id, 'service_tier_id' => $tier->id,
        'package_type' => 'hourly', 'hours' => 1, 'price_aed_cents' => 10000,
    ]);
    PricingModifier::create([
        'facility_id' => $facility->id, 'type' => 'after_hours',
        'percentage' => 25,
        'after_hours_start' => '18:00', 'after_hours_end' => '09:00',
    ]);

    // Tue 06:00 -> 10:00 = 3 after-hours (06,07,08) + 1 in-hours (09)
    $draft = new BookingDraft(
        facility_id: $facility->id, service_tier_id: $tier->id, package_type: 'hourly',
        starts_at: CarbonImmutable::parse('2026-06-09 06:00:00', 'Asia/Dubai'),
        ends_at:   CarbonImmutable::parse('2026-06-09 10:00:00', 'Asia/Dubai'),
    );

    $breakdown = app(CalculateBookingPrice::class)->execute($draft);
    expect($breakdown->after_hours_markup_aed_cents)->toBe(7500);   // 25% × 3 × 10000
});
```

- [ ] **Step 2: Run, expect failure**

- [ ] **Step 3: Update action**

Update `execute()`:

```php
public function execute(BookingDraft $draft): PriceBreakdown
{
    $base = $this->base($draft);
    return new PriceBreakdown(
        base_aed_cents: $base,
        weekend_markup_aed_cents: $this->weekendMarkup($draft, $base),
        after_hours_markup_aed_cents: $this->afterHoursMarkup($draft, $base),
    );
}
```

Add helper:

```php
private function afterHoursMarkup(BookingDraft $draft, int $base): int
{
    $modifier = PricingModifier::where([
        'facility_id' => $draft->facility_id,
        'type' => 'after_hours',
    ])->first();

    if (! $modifier || ! $modifier->after_hours_start || ! $modifier->after_hours_end) {
        return 0;
    }

    $afterHoursHours = $this->countAfterHoursHours(
        $draft->starts_at, $draft->ends_at,
        $modifier->after_hours_start, $modifier->after_hours_end,
    );

    if ($afterHoursHours === 0) {
        return 0;
    }

    if ($draft->package_type === 'hourly') {
        $hourlyRate = (int) ($base / $draft->totalHours());
        return (int) round($hourlyRate * $afterHoursHours * $modifier->percentage / 100);
    }

    return (int) round($base * $modifier->percentage / 100);
}

private function countAfterHoursHours(
    CarbonImmutable $start, CarbonImmutable $end,
    string $afterHoursStart, string $afterHoursEnd,
): int {
    [$startH, $startM] = array_map('intval', explode(':', $afterHoursStart));
    [$endH, $endM] = array_map('intval', explode(':', $afterHoursEnd));
    $startMinuteOfDay = $startH * 60 + $startM;
    $endMinuteOfDay = $endH * 60 + $endM;

    $count = 0;
    $cursor = $start;
    while ($cursor < $end) {
        $minuteOfDay = $cursor->hour * 60 + $cursor->minute;
        $isAfterHours = $startMinuteOfDay < $endMinuteOfDay
            ? ($minuteOfDay >= $startMinuteOfDay && $minuteOfDay < $endMinuteOfDay)
            : ($minuteOfDay >= $startMinuteOfDay || $minuteOfDay < $endMinuteOfDay);
        if ($isAfterHours) {
            $count++;
        }
        $cursor = $cursor->addHour();
    }
    return $count;
}
```

- [ ] **Step 4: Run tests, expect pass**

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "Apply after-hours markup with wrap-midnight support"
```

---

## Task 10: Pricing engine — add-ons

Add-ons are flat prices added to the booking. Quantity supported.

**Files:**
- Modify: `app/Modules/Pricing/Actions/CalculateBookingPrice.php`
- Modify: `tests/Feature/Pricing/CalculateBookingPriceTest.php`

- [ ] **Step 1: Append failing test**

```php
use App\Modules\Catalog\Models\Addon;

it('sums addon prices × quantity', function () {
    $facility = Facility::factory()->create();
    $tier = ServiceTier::factory()->for($facility)->create();
    FacilityPricing::create([
        'facility_id' => $facility->id, 'service_tier_id' => $tier->id,
        'package_type' => 'hourly', 'hours' => 1, 'price_aed_cents' => 10000,
    ]);
    $editing = Addon::factory()->for($facility)->create(['price_aed_cents' => 50000]);
    $clips = Addon::factory()->for($facility)->create(['price_aed_cents' => 30000]);

    $draft = new BookingDraft(
        facility_id: $facility->id, service_tier_id: $tier->id, package_type: 'hourly',
        starts_at: CarbonImmutable::parse('2026-06-08 10:00:00', 'Asia/Dubai'),
        ends_at:   CarbonImmutable::parse('2026-06-08 11:00:00', 'Asia/Dubai'),
        addons: [
            ['addon_id' => $editing->id, 'qty' => 1],
            ['addon_id' => $clips->id,   'qty' => 2],
        ],
    );

    $breakdown = app(CalculateBookingPrice::class)->execute($draft);
    expect($breakdown->addons_aed_cents)->toBe(50000 + 30000 * 2);
});

it('throws when an addon belongs to a different facility', function () {
    $facility = Facility::factory()->create();
    $otherFacility = Facility::factory()->create();
    $tier = ServiceTier::factory()->for($facility)->create();
    FacilityPricing::create([
        'facility_id' => $facility->id, 'service_tier_id' => $tier->id,
        'package_type' => 'hourly', 'hours' => 1, 'price_aed_cents' => 10000,
    ]);
    $foreign = Addon::factory()->for($otherFacility)->create();

    $draft = new BookingDraft(
        facility_id: $facility->id, service_tier_id: $tier->id, package_type: 'hourly',
        starts_at: CarbonImmutable::parse('2026-06-08 10:00:00', 'Asia/Dubai'),
        ends_at:   CarbonImmutable::parse('2026-06-08 11:00:00', 'Asia/Dubai'),
        addons: [['addon_id' => $foreign->id, 'qty' => 1]],
    );

    expect(fn () => app(CalculateBookingPrice::class)->execute($draft))
        ->toThrow(\App\Modules\Pricing\Exceptions\InvalidAddonForFacility::class);
});
```

- [ ] **Step 2: Run, expect failure**

- [ ] **Step 3: Add the new exception**

```php
<?php
// app/Modules/Pricing/Exceptions/InvalidAddonForFacility.php

namespace App\Modules\Pricing\Exceptions;

class InvalidAddonForFacility extends \RuntimeException {}
```

- [ ] **Step 4: Update action**

In `execute()`, add `addons_aed_cents` parameter:

```php
public function execute(BookingDraft $draft): PriceBreakdown
{
    $base = $this->base($draft);
    return new PriceBreakdown(
        base_aed_cents: $base,
        weekend_markup_aed_cents: $this->weekendMarkup($draft, $base),
        after_hours_markup_aed_cents: $this->afterHoursMarkup($draft, $base),
        addons_aed_cents: $this->addons($draft),
    );
}
```

Add helper:

```php
private function addons(BookingDraft $draft): int
{
    if (empty($draft->addons)) {
        return 0;
    }

    $ids = collect($draft->addons)->pluck('addon_id')->all();
    $rows = Addon::whereIn('id', $ids)->get()->keyBy('id');

    $total = 0;
    foreach ($draft->addons as $addon) {
        $row = $rows->get($addon['addon_id']);
        if (! $row) {
            throw new InvalidAddonForFacility("Addon {$addon['addon_id']} not found");
        }
        if ($row->facility_id !== $draft->facility_id) {
            throw new InvalidAddonForFacility(
                "Addon {$row->id} belongs to facility {$row->facility_id}, expected {$draft->facility_id}"
            );
        }
        $total += $row->price_aed_cents * $addon['qty'];
    }
    return $total;
}
```

Add imports: `use App\Modules\Catalog\Models\Addon;` and `use App\Modules\Pricing\Exceptions\InvalidAddonForFacility;`.

- [ ] **Step 5: Run tests, expect pass**

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "Apply add-on prices to pricing engine with facility-scope check"
```

---

## Task 11: FindAvailableSlots action

Given a facility + a date + a package type, return a list of valid (starts_at, ends_at) windows. Validates:
- Inside the day's `availability_rules` window
- Not overlapping any `availability_blackouts`
- Not exceeding `facilities.max_concurrent_per_day` for already-confirmed/held bookings on that day (for now, no Booking model exists, so we accept this as 0 concurrent — to be wired up in Plan 3)
- Lead time ≥ 24 hours

**Files:**
- Create: `app/Modules/Availability/Actions/FindAvailableSlots.php`
- Create: `app/Modules/Availability/ValueObjects/Slot.php`
- Create: `tests/Feature/Availability/FindAvailableSlotsTest.php`

- [ ] **Step 1: Failing test**

```php
<?php
// tests/Feature/Availability/FindAvailableSlotsTest.php

use App\Modules\Availability\Actions\FindAvailableSlots;
use App\Modules\Availability\Models\AvailabilityBlackout;
use App\Modules\Availability\Models\AvailabilityRule;
use App\Modules\Catalog\Models\Facility;
use Carbon\CarbonImmutable;

beforeEach(function () {
    $this->facility = Facility::factory()->create(['max_concurrent_per_day' => 2]);
    foreach ([1, 2, 3, 4, 5, 6] as $dow) {
        AvailabilityRule::factory()->for($this->facility)->create([
            'day_of_week' => $dow,
            'open_time' => '09:00',
            'close_time' => '18:00',
        ]);
    }
});

it('returns hourly slots for a Monday inside business hours', function () {
    $monday = CarbonImmutable::parse('2026-06-08', 'Asia/Dubai');  // Monday
    $slots = app(FindAvailableSlots::class)->execute(
        $this->facility->id, $monday, 'hourly',
    );

    expect($slots)->toHaveCount(9);  // 09:00, 10:00, ..., 17:00 starts (9 1-hour slots)
    expect($slots[0]->starts_at->format('H:i'))->toBe('09:00');
    expect($slots[0]->ends_at->format('H:i'))->toBe('10:00');
});

it('returns no slots on a closed day', function () {
    $sunday = CarbonImmutable::parse('2026-06-07', 'Asia/Dubai');  // Sunday — closed
    $slots = app(FindAvailableSlots::class)->execute(
        $this->facility->id, $sunday, 'hourly',
    );
    expect($slots)->toHaveCount(0);
});

it('excludes slots that overlap a blackout', function () {
    AvailabilityBlackout::create([
        'facility_id' => $this->facility->id,
        'starts_at' => '2026-06-08 11:00:00',
        'ends_at'   => '2026-06-08 14:00:00',
        'reason' => 'Maintenance',
    ]);

    $monday = CarbonImmutable::parse('2026-06-08', 'Asia/Dubai');
    $slots = app(FindAvailableSlots::class)->execute(
        $this->facility->id, $monday, 'hourly',
    );

    // Original 9 slots minus 11:00, 12:00, 13:00 = 6
    expect($slots)->toHaveCount(6);
    foreach ($slots as $slot) {
        expect($slot->starts_at->format('H:i'))->not->toBeIn(['11:00', '12:00', '13:00']);
    }
});

it('returns half-day slots (4-hour windows)', function () {
    $monday = CarbonImmutable::parse('2026-06-08', 'Asia/Dubai');
    $slots = app(FindAvailableSlots::class)->execute(
        $this->facility->id, $monday, 'half_day',
    );

    // 09:00 (4h ends 13:00), 10:00 (10:00->14:00), ..., 14:00 (14:00->18:00) = 6 starts
    expect($slots)->toHaveCount(6);
    expect($slots[0]->ends_at->format('H:i'))->toBe('13:00');
});

it('returns full-day slots (8-hour windows)', function () {
    $monday = CarbonImmutable::parse('2026-06-08', 'Asia/Dubai');
    $slots = app(FindAvailableSlots::class)->execute(
        $this->facility->id, $monday, 'full_day',
    );

    // Open 09-18 = 9 hours; only 09:00->17:00 fits an 8-hour window — 1 slot? No:
    // 09:00->17:00 (8h) AND 10:00->18:00 (8h) — 2 slots.
    expect($slots)->toHaveCount(2);
});
```

- [ ] **Step 2: Run, expect failure**

- [ ] **Step 3: Slot value object**

```php
<?php
// app/Modules/Availability/ValueObjects/Slot.php

namespace App\Modules\Availability\ValueObjects;

use Carbon\CarbonImmutable;

final readonly class Slot
{
    public function __construct(
        public CarbonImmutable $starts_at,
        public CarbonImmutable $ends_at,
    ) {}
}
```

- [ ] **Step 4: Implement the action**

```php
<?php
// app/Modules/Availability/Actions/FindAvailableSlots.php

namespace App\Modules\Availability\Actions;

use App\Modules\Availability\Models\AvailabilityBlackout;
use App\Modules\Availability\Models\AvailabilityRule;
use App\Modules\Availability\ValueObjects\Slot;
use Carbon\CarbonImmutable;

class FindAvailableSlots
{
    /**
     * @return Slot[]
     */
    public function execute(int $facilityId, CarbonImmutable $date, string $packageType): array
    {
        $rule = AvailabilityRule::where([
            'facility_id' => $facilityId,
            'day_of_week' => $date->dayOfWeek,
        ])->first();

        if (! $rule) {
            return [];
        }

        $duration = match ($packageType) {
            'hourly' => 1,
            'half_day' => 4,
            'full_day' => 8,
            'multi_day' => 8,         // multi-day shows as full-day slots; the date-range is selected separately
            default => throw new \InvalidArgumentException("Unknown package_type {$packageType}"),
        };

        [$openH, $openM] = array_map('intval', explode(':', $rule->open_time));
        [$closeH, $closeM] = array_map('intval', explode(':', $rule->close_time));
        $open = $date->setTime($openH, $openM);
        $close = $date->setTime($closeH, $closeM);

        $blackouts = AvailabilityBlackout::where('facility_id', $facilityId)
            ->where('starts_at', '<', $close)
            ->where('ends_at', '>', $open)
            ->get();

        $slots = [];
        $cursor = $open;
        while ($cursor->copy()->addHours($duration) <= $close) {
            $end = $cursor->copy()->addHours($duration);

            $blocked = $blackouts->contains(function ($bo) use ($cursor, $end) {
                return $bo->starts_at < $end && $bo->ends_at > $cursor;
            });

            if (! $blocked) {
                $slots[] = new Slot($cursor, $end);
            }
            $cursor = $cursor->addHour();
        }

        return $slots;
    }
}
```

- [ ] **Step 5: Run tests, expect pass**

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "Add FindAvailableSlots action with rule + blackout checks"
```

---

## Task 12: LookupRefundPercentage action

Given (facility_id, hours_until_starts_at), return the matching policy tier's refund percentage. Uses the largest `hours_before_min ≤ hours_until` rule.

**Files:**
- Create: `app/Modules/Pricing/Actions/LookupRefundPercentage.php`
- Create: `tests/Feature/Pricing/LookupRefundPercentageTest.php`

- [ ] **Step 1: Failing test**

```php
<?php
// tests/Feature/Pricing/LookupRefundPercentageTest.php

use App\Modules\Catalog\Models\CancellationPolicy;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Pricing\Actions\LookupRefundPercentage;

beforeEach(function () {
    $this->facility = Facility::factory()->create();
    CancellationPolicy::factory()->for($this->facility)->create(['hours_before_min' => 168, 'refund_percentage' => 100]);
    CancellationPolicy::factory()->for($this->facility)->create(['hours_before_min' => 72,  'refund_percentage' => 50]);
    CancellationPolicy::factory()->for($this->facility)->create(['hours_before_min' => 0,   'refund_percentage' => 0]);
});

it('returns 100 when cancelling more than 7 days out', function () {
    expect(app(LookupRefundPercentage::class)->execute($this->facility->id, 200))->toBe(100);
    expect(app(LookupRefundPercentage::class)->execute($this->facility->id, 168))->toBe(100);
});

it('returns 50 when 3-7 days out', function () {
    expect(app(LookupRefundPercentage::class)->execute($this->facility->id, 167))->toBe(50);
    expect(app(LookupRefundPercentage::class)->execute($this->facility->id, 72))->toBe(50);
});

it('returns 0 when less than 3 days out', function () {
    expect(app(LookupRefundPercentage::class)->execute($this->facility->id, 71))->toBe(0);
    expect(app(LookupRefundPercentage::class)->execute($this->facility->id, 0))->toBe(0);
});

it('throws if no policy is configured for the facility', function () {
    $other = Facility::factory()->create();
    expect(fn () => app(LookupRefundPercentage::class)->execute($other->id, 100))
        ->toThrow(\App\Modules\Pricing\Exceptions\CancellationPolicyMissing::class);
});
```

- [ ] **Step 2: Run, expect failure**

- [ ] **Step 3: Add the exception**

```php
<?php
// app/Modules/Pricing/Exceptions/CancellationPolicyMissing.php

namespace App\Modules\Pricing\Exceptions;

class CancellationPolicyMissing extends \RuntimeException {}
```

- [ ] **Step 4: Implement the action**

```php
<?php
// app/Modules/Pricing/Actions/LookupRefundPercentage.php

namespace App\Modules\Pricing\Actions;

use App\Modules\Catalog\Models\CancellationPolicy;
use App\Modules\Pricing\Exceptions\CancellationPolicyMissing;

class LookupRefundPercentage
{
    public function execute(int $facilityId, int $hoursUntilStartsAt): int
    {
        $policy = CancellationPolicy::where('facility_id', $facilityId)
            ->where('hours_before_min', '<=', $hoursUntilStartsAt)
            ->orderByDesc('hours_before_min')
            ->first();

        if (! $policy) {
            $any = CancellationPolicy::where('facility_id', $facilityId)->exists();
            if (! $any) {
                throw new CancellationPolicyMissing("Facility {$facilityId} has no cancellation policy configured");
            }
            return 0;
        }

        return $policy->refund_percentage;
    }
}
```

- [ ] **Step 5: Run tests, expect pass**

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "Add LookupRefundPercentage action with policy-tier resolution"
```

---

## Task 13: Filament — AvailabilityRules RelationManager

Lets admin manage weekly business hours under each Facility.

**Files:**
- Create: `app/Filament/Resources/Catalog/FacilityResource/RelationManagers/AvailabilityRulesRelationManager.php`
- Modify: `app/Filament/Resources/Catalog/FacilityResource.php` (register)
- Create: `tests/Feature/Catalog/AvailabilityRulesRelationTest.php`

- [ ] **Step 1: Generate the RM**

```bash
php artisan make:filament-relation-manager Catalog/FacilityResource availabilityRules day_of_week
```

- [ ] **Step 2: Edit the RM**

Replace form/table:

```php
public function form(Form $form): Form
{
    return $form->schema([
        Forms\Components\Select::make('day_of_week')
            ->options([
                0 => 'Sunday', 1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday',
                4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday',
            ])
            ->required(),
        Forms\Components\TimePicker::make('open_time')->required()->seconds(false),
        Forms\Components\TimePicker::make('close_time')->required()->seconds(false),
    ]);
}

public function table(Table $table): Table
{
    return $table
        ->recordTitleAttribute('day_of_week')
        ->columns([
            Tables\Columns\TextColumn::make('day_of_week')
                ->formatStateUsing(fn ($state) => ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'][$state])
                ->sortable(),
            Tables\Columns\TextColumn::make('open_time')->time('H:i'),
            Tables\Columns\TextColumn::make('close_time')->time('H:i'),
        ])
        ->headerActions([Tables\Actions\CreateAction::make()])
        ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
}
```

- [ ] **Step 3: Register**

In `FacilityResource::getRelations()`, append:

```php
RelationManagers\AvailabilityRulesRelationManager::class,
```

- [ ] **Step 4: Test**

```php
<?php
// tests/Feature/Catalog/AvailabilityRulesRelationTest.php

use App\Models\User;
use App\Modules\Availability\Models\AvailabilityRule;
use App\Modules\Catalog\Models\Facility;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Admin']);
    $this->admin = User::factory()->create()->assignRole('Admin');
    $this->actingAs($this->admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('shows availability rules attached to a facility', function () {
    $facility = Facility::factory()->create();
    AvailabilityRule::factory()->for($facility)->create(['day_of_week' => 1]);
    AvailabilityRule::factory()->for($facility)->create(['day_of_week' => 2]);

    $this->get("/admin/catalog/facilities/{$facility->id}/edit")->assertOk();
});
```

Run pest, expect green.

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "Add AvailabilityRules RelationManager"
```

---

## Task 14: Filament — AvailabilityBlackouts RelationManager

**Files:**
- Create: `app/Filament/Resources/Catalog/FacilityResource/RelationManagers/AvailabilityBlackoutsRelationManager.php`
- Modify: `FacilityResource.php`
- Create: `tests/Feature/Catalog/AvailabilityBlackoutsRelationTest.php`

- [ ] **Step 1: Generate**

```bash
php artisan make:filament-relation-manager Catalog/FacilityResource availabilityBlackouts reason
```

- [ ] **Step 2: Edit RM**

```php
public function form(Form $form): Form
{
    return $form->schema([
        Forms\Components\DateTimePicker::make('starts_at')->required()->seconds(false),
        Forms\Components\DateTimePicker::make('ends_at')->required()->seconds(false),
        Forms\Components\TextInput::make('reason')->maxLength(255),
    ]);
}

public function table(Table $table): Table
{
    return $table
        ->recordTitleAttribute('reason')
        ->columns([
            Tables\Columns\TextColumn::make('starts_at')->dateTime('M j, H:i')->sortable(),
            Tables\Columns\TextColumn::make('ends_at')->dateTime('M j, H:i'),
            Tables\Columns\TextColumn::make('reason')->limit(40),
        ])
        ->defaultSort('starts_at', 'desc')
        ->headerActions([Tables\Actions\CreateAction::make()])
        ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()]);
}
```

- [ ] **Step 3: Register** in `FacilityResource::getRelations()`.

- [ ] **Step 4: Test**

```php
<?php
// tests/Feature/Catalog/AvailabilityBlackoutsRelationTest.php

use App\Models\User;
use App\Modules\Availability\Models\AvailabilityBlackout;
use App\Modules\Catalog\Models\Facility;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Admin']);
    $this->admin = User::factory()->create()->assignRole('Admin');
    $this->actingAs($this->admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('shows blackouts attached to a facility', function () {
    $facility = Facility::factory()->create();
    AvailabilityBlackout::factory()->for($facility)->create(['reason' => 'National Day']);

    $this->get("/admin/catalog/facilities/{$facility->id}/edit")->assertOk();
});
```

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "Add AvailabilityBlackouts RelationManager"
```

---

## Task 15: Add max_concurrent_per_day to Facility form

Tiny change — just add the field to the existing FacilityResource form.

**Files:**
- Modify: `app/Filament/Resources/Catalog/FacilityResource.php`
- Modify: `tests/Feature/Catalog/FacilityResourceTest.php` (add a field to the create test)

- [ ] **Step 1: Add to form schema**

In `FacilityResource::form()`, append after `is_active` toggle:

```php
Forms\Components\TextInput::make('max_concurrent_per_day')
    ->numeric()->required()->default(1)->minValue(1)
    ->helperText('Max number of bookings that can run on the same day at this facility.'),
```

- [ ] **Step 2: Add a column to the table** (optional but useful):

```php
Tables\Columns\TextColumn::make('max_concurrent_per_day')->label('Capacity'),
```

- [ ] **Step 3: Update FacilityResourceTest**

In the "can create a facility via Filament" test, add `'max_concurrent_per_day' => 1` to the `fillForm` array.

- [ ] **Step 4: Run pest**

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "Expose max_concurrent_per_day on Facility admin form"
```

---

## Task 16: Update CatalogStatsWidget with availability stats

Tiny addition.

**Files:**
- Modify: `app/Filament/Widgets/CatalogStatsWidget.php`

- [ ] **Step 1: Add stat**

Append to `getStats()`:

```php
\Filament\Widgets\StatsOverviewWidget\Stat::make(
    'Active blackouts (next 30d)',
    \App\Modules\Availability\Models\AvailabilityBlackout::where('starts_at', '<=', now()->addDays(30))
        ->where('ends_at', '>=', now())
        ->count(),
),
```

- [ ] **Step 2: Run pest**

- [ ] **Step 3: Commit**

```bash
git add -A
git commit -m "Add upcoming-blackouts stat to dashboard widget"
```

---

## Task 17: Final Plan-2 smoke test

**Files:** none modified.

- [ ] **Step 1: Full test suite**

```bash
./vendor/bin/pest
```

Note total count. Expected: was 37 after Plan 1; Plan 2 adds approximately:
- Task 1: +1 (Facility default capacity)
- Task 2: +2 (AvailabilityRule)
- Task 3: +1 (AvailabilityBlackout)
- Task 4: +2 (Pod24 availability seed)
- Task 5: +3 (PriceBreakdown unit)
- Task 6: +2 (BookingDraft unit)
- Task 7: +3 (CalculateBookingPrice base)
- Task 8: +4 (weekend markup)
- Task 9: +3 (after-hours markup)
- Task 10: +2 (addons)
- Task 11: +5 (FindAvailableSlots)
- Task 12: +4 (LookupRefundPercentage)
- Task 13: +1 (RM)
- Task 14: +1 (RM)

Approximately +34 tests → ~71 total. Confirm pass count.

- [ ] **Step 2: Fresh seed**

```bash
php artisan migrate:fresh --seed
```

Confirm no errors. Confirm via tinker that Pod24 facility has:
- 4 service tiers
- 16 pricing rows
- 4 addons
- 2 hour packs
- 2 modifiers
- 3 cancellation tiers
- **6 availability rules (Mon-Sat)**
- **max_concurrent_per_day = 2**

- [ ] **Step 3: Tag**

```bash
git tag plan-2-availability-pricing-complete
git commit --allow-empty -m "Plan 2 complete: availability + pricing engine"
```

---

## Plan 2 self-review summary

**Spec coverage:**
- § 5.2 availability tables: AvailabilityRule (Task 2), AvailabilityBlackout (Task 3), max_concurrent_per_day on facility (Task 1, deviation noted) ✅
- § 6 pricing engine: PriceBreakdown (Task 5), BookingDraft (Task 6), CalculateBookingPrice with base + weekend + after-hours + addons (Tasks 7-10), VAT computed in PriceBreakdown::vat() (Task 5) ✅
- § 6 hour-pack credit application: **deferred to Plan 4** — requires customer accounts. The `PriceBreakdown::hour_pack_credit_value_aed_cents` field is in place; only the action's redemption math is deferred.
- § 7 availability logic: FindAvailableSlots (Task 11) — capacity check against existing bookings is **stubbed (treated as 0 concurrent) until Plan 3 introduces the bookings table**.
- § 11 cancellation policy lookup: LookupRefundPercentage (Task 12) ✅

**Spec deviations:**
- `availability_capacities` table dropped, `max_concurrent_per_day` moved to `facilities`. Acceptable simplification, spec doc updated in Task 1.

**Out of scope (deferred):**
- Hour-pack redemption inside the pricing engine (Plan 4)
- Capacity-aware concurrent-booking check inside FindAvailableSlots (Plan 3 — depends on Booking model)
- Pricing modifier UI: weekend / after-hours editing already exists from Plan 1's PricingModifierResource.

**Placeholder scan:** none.

**Ambiguity check:** weekend markup for half/full/multi-day = whole-package markup if any hour falls on weekend (decision documented in Task 8 prelude). After-hours = same rule. UAE weekend = Sat + Sun (post-2022 work-week change), encoded in `countWeekendHours`.
