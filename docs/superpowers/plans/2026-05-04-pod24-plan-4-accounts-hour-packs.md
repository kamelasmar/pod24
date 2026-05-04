# Pod24 — Plan 4: Customer Accounts & Hour Packs

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Magic-link customer auth, hour-pack purchase via Stripe Cashier, hour-pack redemption inside the booking wizard's checkout step, and a minimal `/account` dashboard showing booking history and pack balance. Pack expiry job runs nightly.

**Architecture:** Customer auth via `signed URLs` for magic-link login — no password, no scaffolding beyond what already exists for staff users. Hour packs use a **ledger** model (`hour_pack_transactions`): purchases insert positive rows, redemptions insert negative rows, expiries insert zero-out rows. Balance = `SUM(hours)` over un-expired rows. This avoids race conditions on a balance integer. Stripe Cashier (already installed in Plan 3) handles the pack-purchase Checkout flow. Redemption happens at hold creation: if the customer has balance, hours are deducted and the booking's `hour_pack_credit_value_aed_cents` reduces the subtotal.

**Tech stack:** Laravel 11, Stripe Cashier 16, Livewire 3, Filament 3.

**Spec reference:** `docs/superpowers/specs/2026-05-04-pod24-platform-design.md` § 5.4 (users + hour_pack_transactions), § 9 (hour-pack flow), § 14 (hybrid auth).

**Depends on:** Plan 3 complete (`plan-3-public-site-booking-complete`).

---

## File structure for Plan 4

```
pod24/
├── app/Modules/
│   └── Customers/
│       ├── Models/
│       │   └── HourPackTransaction.php
│       ├── Actions/
│       │   ├── PurchaseHourPack.php
│       │   ├── RedeemHourPackHours.php       (called from CreateBookingHold)
│       │   ├── HourPackBalance.php           (read action — SUM(hours) under un-expired rows)
│       │   └── ExpireHourPacks.php
│       └── Mail/
│           └── MagicLinkMail.php
├── app/Livewire/
│   ├── AccountDashboard.php
│   ├── HourPackPurchaseList.php
│   └── MagicLinkRequest.php
├── app/Http/Controllers/Auth/
│   └── MagicLinkController.php
├── app/Filament/Resources/
│   └── Customers/
│       └── HourPackTransactionResource.php   (admin-only ledger view)
└── tests/Feature/{Customers,Auth}/
```

---

## Task 1: User model — add fields needed for customers

**Files:**
- Create: `database/migrations/...add_customer_fields_to_users_table.php`
- Modify: `app/Models/User.php` (cashier trait + new fillables)
- Modify: `database/factories/UserFactory.php`

The User model is currently scoped for Filament admin (just email/name/password). Customers will use the same table; add `phone`, `timezone`, and the marketing_consent_at fields. Cashier needs `stripe_id`, `pm_type`, `pm_last_four`, `trial_ends_at` columns — pull these in via Cashier's published migration.

- [ ] **Step 1: Publish Cashier's migration**

```bash
php artisan vendor:publish --tag="cashier-migrations"
```

This adds two migrations: one for subscriptions and one for adding columns to users. Review them — the user-columns migration adds `stripe_id`, `pm_type`, `pm_last_four`, `trial_ends_at`. We don't need subscriptions yet (HourPacks are one-shot), so DELETE the subscriptions migration that Cashier publishes. Keep only the users-table additions.

- [ ] **Step 2: Add an additional migration for our customer fields**

```bash
php artisan make:migration add_customer_fields_to_users_table
```

```php
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable();
            $table->string('timezone')->default('Asia/Dubai');
            $table->timestamp('marketing_consent_at')->nullable();
        });
    }
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'timezone', 'marketing_consent_at']);
        });
    }
};
```

- [ ] **Step 3: Update User model**

Add `Billable` trait from Cashier:

```php
use Laravel\Cashier\Billable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, HasRoles, Billable;
    // ...existing canAccessPanel + casts...
}
```

Add to `$fillable`: `'phone', 'timezone', 'marketing_consent_at'`.

Add to casts: `'marketing_consent_at' => 'datetime'`.

- [ ] **Step 4: Update UserFactory**

Add to `definition()` return array:

```php
'phone' => null,
'timezone' => 'Asia/Dubai',
'marketing_consent_at' => null,
```

- [ ] **Step 5: Migrate + run tests**

```bash
php artisan migrate
./vendor/bin/pest
```

All 105 existing tests should still pass.

- [ ] **Step 6: Commit** "Add customer fields and Billable trait to User"

---

## Task 2: Magic-link auth (passwordless customer login)

The simplest possible passwordless login: customer enters email, system emails a signed URL valid for 30 minutes that logs them in on click.

**Files:**
- Create: `app/Http/Controllers/Auth/MagicLinkController.php`
- Create: `app/Livewire/MagicLinkRequest.php`
- Create: `resources/views/livewire/magic-link-request.blade.php`
- Create: `resources/views/auth/magic-link-sent.blade.php`
- Create: `app/Modules/Customers/Mail/MagicLinkMail.php`
- Create: `resources/views/mail/magic-link.blade.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/Auth/MagicLinkAuthTest.php`

- [ ] **Step 1: Failing test**

```php
<?php
// tests/Feature/Auth/MagicLinkAuthTest.php

use App\Models\User;
use App\Modules\Customers\Mail\MagicLinkMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

it('sends a signed login link to a customer email', function () {
    Mail::fake();
    $user = User::factory()->create(['email' => 'guest@example.com']);

    $this->post('/login/magic-link', ['email' => 'guest@example.com'])
        ->assertRedirect('/login/sent');

    Mail::assertSent(MagicLinkMail::class, fn ($mail) => $mail->hasTo('guest@example.com'));
});

it('creates a user lazily if the email does not exist yet', function () {
    Mail::fake();

    $this->post('/login/magic-link', ['email' => 'newcustomer@example.com'])
        ->assertRedirect('/login/sent');

    expect(User::where('email', 'newcustomer@example.com')->exists())->toBeTrue();
});

it('logs the user in when they click a valid signed link', function () {
    $user = User::factory()->create();
    $url = URL::temporarySignedRoute('login.magic-link.consume', now()->addMinutes(30), ['user' => $user->id]);

    $this->get($url)->assertRedirect('/account');
    $this->assertAuthenticatedAs($user);
});

it('rejects expired links', function () {
    $user = User::factory()->create();
    $url = URL::temporarySignedRoute('login.magic-link.consume', now()->subMinute(), ['user' => $user->id]);

    $this->get($url)->assertStatus(403);
});

it('rejects tampered links', function () {
    $user = User::factory()->create();
    $url = URL::temporarySignedRoute('login.magic-link.consume', now()->addMinutes(30), ['user' => $user->id]);
    // Strip the signature param
    $tampered = preg_replace('/&signature=[^&]+$/', '', $url) . '&signature=fake';

    $this->get($tampered)->assertStatus(403);
});
```

- [ ] **Step 2: Routes**

```php
// routes/web.php
use App\Http\Controllers\Auth\MagicLinkController;
use App\Livewire\MagicLinkRequest;

Route::get('/login', MagicLinkRequest::class)->name('login');
Route::post('/login/magic-link', [MagicLinkController::class, 'request'])->name('login.magic-link.request');
Route::get('/login/sent', fn () => view('auth.magic-link-sent'))->name('login.magic-link.sent');
Route::get('/login/m/{user}', [MagicLinkController::class, 'consume'])
    ->middleware('signed')
    ->name('login.magic-link.consume');
Route::post('/logout', function () { auth()->logout(); return redirect('/'); })->name('logout');
```

- [ ] **Step 3: MagicLinkController**

```php
<?php
// app/Http/Controllers/Auth/MagicLinkController.php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Modules\Customers\Mail\MagicLinkMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class MagicLinkController
{
    public function request(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $user = User::firstOrCreate(
            ['email' => $request->email],
            ['name' => '', 'password' => '', 'email_verified_at' => null],
        );

        $url = URL::temporarySignedRoute(
            'login.magic-link.consume',
            now()->addMinutes(30),
            ['user' => $user->id],
        );

        Mail::to($user->email)->send(new MagicLinkMail($url));

        return redirect()->route('login.magic-link.sent');
    }

    public function consume(Request $request, User $user)
    {
        if (! $request->hasValidSignature()) {
            abort(403);
        }

        auth()->login($user);
        $user->forceFill(['email_verified_at' => $user->email_verified_at ?? now()])->save();

        return redirect('/account');
    }
}
```

- [ ] **Step 4: MagicLinkMail**

```php
<?php
// app/Modules/Customers/Mail/MagicLinkMail.php

namespace App\Modules\Customers\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MagicLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $url) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your Pod24 login link');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.magic-link');
    }
}
```

- [ ] **Step 5: Templates**

`resources/views/mail/magic-link.blade.php`:

```blade
<x-mail::message>
# Sign in to Pod24

Click the link below to sign in. The link expires in 30 minutes.

<x-mail::button :url="$url">Sign in</x-mail::button>

If you didn't request this, ignore the email.

Pod24 — twofour54
</x-mail::message>
```

`resources/views/auth/magic-link-sent.blade.php`:

```blade
@extends('pod24.layouts.public')
@section('content')
<section class="container mx-auto py-24 px-4 max-w-md text-center">
<h1 class="text-3xl font-bold mb-4">Check your email</h1>
<p class="text-pod-muted">We've sent you a sign-in link. It's valid for 30 minutes.</p>
</section>
@endsection
```

- [ ] **Step 6: Livewire login form**

```php
<?php
// app/Livewire/MagicLinkRequest.php

namespace App\Livewire;

use Livewire\Component;

class MagicLinkRequest extends Component
{
    public string $email = '';

    public function render()
    {
        return view('livewire.magic-link-request')->extends('pod24.layouts.public');
    }
}
```

`resources/views/livewire/magic-link-request.blade.php`:

```blade
@section('content')
<section class="container mx-auto py-24 px-4 max-w-md">
<h1 class="text-3xl font-bold mb-6">Sign in to Pod24</h1>
<form action="{{ route('login.magic-link.request') }}" method="POST">
@csrf
<input type="email" name="email" required placeholder="Your email"
       class="w-full border border-pod-border p-3 rounded mb-4" wire:model="email">
<button type="submit" class="w-full bg-pod-accent text-pod-ink-deep p-3 rounded font-bold">
Send sign-in link
</button>
</form>
</section>
@endsection
```

- [ ] **Step 7: Run pest, expect green**

- [ ] **Step 8: Commit** "Add magic-link auth for customers"

---

## Task 3: HourPackTransaction model (ledger)

**Files:**
- Create: `app/Modules/Customers/Models/HourPackTransaction.php`
- Create: `database/migrations/...create_hour_pack_transactions_table.php`
- Create: `database/factories/HourPackTransactionFactory.php`
- Create: `tests/Feature/Customers/HourPackTransactionTest.php`

- [ ] **Step 1: Failing test**

```php
<?php

use App\Models\User;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Customers\Models\HourPackTransaction;

it('records a positive purchase and a negative redemption', function () {
    $user = User::factory()->create();
    $facility = Facility::factory()->create();

    HourPackTransaction::create([
        'customer_id' => $user->id,
        'facility_id' => $facility->id,
        'hours' => 10,
        'type' => 'purchase',
        'expires_at' => now()->addYear(),
    ]);

    HourPackTransaction::create([
        'customer_id' => $user->id,
        'facility_id' => $facility->id,
        'hours' => -2,
        'type' => 'redeem',
    ]);

    expect(HourPackTransaction::where('customer_id', $user->id)->sum('hours'))->toBe(8);
});
```

- [ ] **Step 2: Run, expect failure**

- [ ] **Step 3: Migration**

```bash
php artisan make:migration create_hour_pack_transactions_table
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hour_pack_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('facility_id')->constrained()->restrictOnDelete();
            $table->integer('hours'); // signed: positive=credit, negative=debit
            $table->enum('type', ['purchase', 'redeem', 'expire', 'admin_adjust']);
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->string('stripe_charge_id')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['customer_id', 'facility_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hour_pack_transactions');
    }
};
```

- [ ] **Step 4: Model**

```php
<?php
// app/Modules/Customers/Models/HourPackTransaction.php

namespace App\Modules\Customers\Models;

use App\Models\User;
use App\Modules\Booking\Models\Booking;
use App\Modules\Catalog\Models\Facility;
use App\Support\HasModuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HourPackTransaction extends Model
{
    use HasFactory, HasModuleFactory {
        HasModuleFactory::newFactory insteadof HasFactory;
    }

    public $timestamps = false;       // only created_at, no updated_at

    protected $fillable = [
        'customer_id', 'facility_id', 'hours', 'type',
        'booking_id', 'stripe_charge_id', 'expires_at', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'hours' => 'integer',
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo { return $this->belongsTo(User::class, 'customer_id'); }
    public function facility(): BelongsTo { return $this->belongsTo(Facility::class); }
    public function booking(): BelongsTo { return $this->belongsTo(Booking::class); }
}
```

- [ ] **Step 5: Factory**

```php
<?php
// database/factories/HourPackTransactionFactory.php

namespace Database\Factories;

use App\Models\User;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Customers\Models\HourPackTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class HourPackTransactionFactory extends Factory
{
    protected $model = HourPackTransaction::class;

    public function definition(): array
    {
        return [
            'customer_id' => User::factory(),
            'facility_id' => Facility::factory(),
            'hours' => 10,
            'type' => 'purchase',
            'expires_at' => now()->addYear(),
        ];
    }
}
```

- [ ] **Step 6: Run pest, expect pass**

- [ ] **Step 7: Commit** "Add HourPackTransaction ledger model"

---

## Task 4: HourPackBalance read action

**Files:**
- Create: `app/Modules/Customers/Actions/HourPackBalance.php`
- Create: `tests/Feature/Customers/HourPackBalanceTest.php`

- [ ] **Step 1: Failing test**

```php
<?php

use App\Models\User;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Customers\Actions\HourPackBalance;
use App\Modules\Customers\Models\HourPackTransaction;

it('sums hours for a customer-facility pair across un-expired rows', function () {
    $user = User::factory()->create();
    $facility = Facility::factory()->create();

    HourPackTransaction::factory()->create([
        'customer_id' => $user->id, 'facility_id' => $facility->id,
        'hours' => 10, 'type' => 'purchase', 'expires_at' => now()->addMonth(),
    ]);
    HourPackTransaction::factory()->create([
        'customer_id' => $user->id, 'facility_id' => $facility->id,
        'hours' => -3, 'type' => 'redeem', 'expires_at' => null,
    ]);

    expect(app(HourPackBalance::class)->forCustomer($user->id, $facility->id))->toBe(7);
});

it('excludes expired rows', function () {
    $user = User::factory()->create();
    $facility = Facility::factory()->create();

    HourPackTransaction::factory()->create([
        'customer_id' => $user->id, 'facility_id' => $facility->id,
        'hours' => 10, 'type' => 'purchase', 'expires_at' => now()->subDay(),
    ]);

    expect(app(HourPackBalance::class)->forCustomer($user->id, $facility->id))->toBe(0);
});
```

- [ ] **Step 2: Implement**

```php
<?php
// app/Modules/Customers/Actions/HourPackBalance.php

namespace App\Modules\Customers\Actions;

use App\Modules\Customers\Models\HourPackTransaction;

class HourPackBalance
{
    public function forCustomer(int $customerId, int $facilityId): int
    {
        return (int) HourPackTransaction::where('customer_id', $customerId)
            ->where('facility_id', $facilityId)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->sum('hours');
    }
}
```

- [ ] **Step 3: Pest pass**

- [ ] **Step 4: Commit** "Add HourPackBalance action"

---

## Task 5: PurchaseHourPack action via Stripe Checkout

**Files:**
- Create: `app/Modules/Customers/Actions/PurchaseHourPack.php`
- Create: `tests/Feature/Customers/PurchaseHourPackTest.php`

- [ ] **Step 1: Failing test (closure-injectable like CreatePaymentIntent)**

```php
<?php

use App\Models\User;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\HourPack;
use App\Modules\Customers\Actions\PurchaseHourPack;

it('creates a Stripe Checkout session for an hour pack purchase', function () {
    $user = User::factory()->create();
    $facility = Facility::factory()->create();
    $pack = HourPack::factory()->for($facility)->create([
        'hours' => 10,
        'price_aed_cents' => 228600,
    ]);

    $stub = function (array $params) use ($pack) {
        expect($params['line_items'][0]['price_data']['unit_amount'])->toBe($pack->price_aed_cents);
        expect($params['metadata']['hour_pack_id'])->toBe((string) $pack->id);
        return (object) ['id' => 'cs_test_xyz', 'url' => 'https://checkout.stripe.com/cs_test_xyz'];
    };

    $action = new PurchaseHourPack($stub);
    $session = $action->execute($user, $pack);

    expect($session['url'])->toBe('https://checkout.stripe.com/cs_test_xyz');
});
```

- [ ] **Step 2: Implement**

```php
<?php
// app/Modules/Customers/Actions/PurchaseHourPack.php

namespace App\Modules\Customers\Actions;

use App\Models\User;
use App\Modules\Catalog\Models\HourPack;

class PurchaseHourPack
{
    /** @var callable */
    private $createSession;

    public function __construct(?callable $createSession = null)
    {
        $this->createSession = $createSession ?? function (array $params) {
            \Stripe\Stripe::setApiKey(config('stripe.secret'));
            return \Stripe\Checkout\Session::create($params);
        };
    }

    public function execute(User $customer, HourPack $pack): array
    {
        $session = ($this->createSession)([
            'mode' => 'payment',
            'customer_email' => $customer->email,
            'line_items' => [[
                'price_data' => [
                    'currency' => 'aed',
                    'unit_amount' => $pack->price_aed_cents,
                    'product_data' => [
                        'name' => $pack->getTranslation('name', 'en'),
                    ],
                ],
                'quantity' => 1,
            ]],
            'metadata' => [
                'customer_id' => (string) $customer->id,
                'hour_pack_id' => (string) $pack->id,
            ],
            'success_url' => route('account.dashboard') . '?pack_purchased=1',
            'cancel_url' => route('account.dashboard') . '?pack_cancelled=1',
        ]);

        return [
            'session_id' => $session->id,
            'url' => $session->url,
        ];
    }
}
```

- [ ] **Step 3: Pest pass**

- [ ] **Step 4: Commit** "Add PurchaseHourPack action with Stripe Checkout session"

---

## Task 6: Stripe webhook — checkout.session.completed handler for hour packs

**Files:**
- Modify: `app/Modules/Payments/Webhooks/StripeWebhookController.php`
- Create: `tests/Feature/Payments/HourPackWebhookTest.php`

- [ ] **Step 1: Failing test**

```php
<?php

use App\Models\User;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\HourPack;
use App\Modules\Customers\Models\HourPackTransaction;

it('credits hours when checkout.session.completed fires for a pack purchase', function () {
    config(['stripe.webhook_secret' => 'whsec_test']);

    $user = User::factory()->create();
    $facility = Facility::factory()->create();
    $pack = HourPack::factory()->for($facility)->create(['hours' => 10, 'expiry_days' => 365]);

    $payload = json_encode([
        'id' => 'evt_test',
        'type' => 'checkout.session.completed',
        'data' => ['object' => [
            'id' => 'cs_test_xyz',
            'metadata' => [
                'customer_id' => (string) $user->id,
                'hour_pack_id' => (string) $pack->id,
            ],
            'payment_intent' => 'pi_pack_xyz',
        ]],
    ]);

    $timestamp = time();
    $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", 'whsec_test');
    $header = "t={$timestamp},v1={$signature}";

    $this->postJson('/webhooks/stripe', json_decode($payload, true), [
        'Stripe-Signature' => $header,
    ])->assertOk();

    $tx = HourPackTransaction::where('customer_id', $user->id)->first();
    expect($tx)->not->toBeNull();
    expect($tx->hours)->toBe(10);
    expect($tx->type)->toBe('purchase');
    expect($tx->expires_at->diffInDays(now()))->toBeBetween(364, 366);
});
```

- [ ] **Step 2: Update webhook controller — add a branch**

In `StripeWebhookController::handle()`, after the existing `payment_intent.succeeded` block, add:

```php
if ($event->type === 'checkout.session.completed') {
    $session = $event->data->object;
    $customerId = $session->metadata->customer_id ?? null;
    $packId = $session->metadata->hour_pack_id ?? null;
    $piId = $session->payment_intent ?? null;

    if ($customerId && $packId) {
        $pack = \App\Modules\Catalog\Models\HourPack::find($packId);
        if ($pack) {
            \App\Modules\Customers\Models\HourPackTransaction::create([
                'customer_id' => (int) $customerId,
                'facility_id' => $pack->facility_id,
                'hours' => $pack->hours,
                'type' => 'purchase',
                'stripe_charge_id' => $piId,
                'expires_at' => now()->addDays($pack->expiry_days),
                'notes' => "Pack purchase: {$pack->getTranslation('name', 'en')}",
            ]);
        }
    }
}
```

- [ ] **Step 3: Pest pass**

- [ ] **Step 4: Commit** "Handle checkout.session.completed webhook for pack purchases"

---

## Task 7: Hour-pack redemption inside CreateBookingHold

When a logged-in customer with a positive balance creates a booking, deduct hours from their balance and credit the booking's `hour_pack_credit_value_aed_cents`. Use the **base hourly rate of the Recording Only tier** as the credit value per spec § 6.

**Files:**
- Modify: `app/Modules/Pricing/Actions/CalculateBookingPrice.php`
- Modify: `app/Modules/Pricing/ValueObjects/BookingDraft.php`
- Modify: `app/Modules/Booking/Actions/CreateBookingHold.php`
- Create: `tests/Feature/Pricing/HourPackRedemptionTest.php`
- Create: `app/Modules/Customers/Actions/RedeemHourPackHours.php`

- [ ] **Step 1: Add `requestedPackHours` field to BookingDraft**

```php
public function __construct(
    public int $facility_id,
    public int $service_tier_id,
    public string $package_type,
    public CarbonImmutable $starts_at,
    public CarbonImmutable $ends_at,
    public array $addons = [],
    public int $requestedPackHours = 0,    // hours to redeem from customer's pack balance
    public ?int $customer_id = null,
) {}
```

- [ ] **Step 2: Failing test**

```php
<?php
// tests/Feature/Pricing/HourPackRedemptionTest.php

use App\Models\User;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\FacilityPricing;
use App\Modules\Catalog\Models\ServiceTier;
use App\Modules\Customers\Models\HourPackTransaction;
use App\Modules\Pricing\Actions\CalculateBookingPrice;
use App\Modules\Pricing\ValueObjects\BookingDraft;
use Carbon\CarbonImmutable;

it('credits AED equivalent of redeemed pack hours at Recording Only base rate', function () {
    $facility = Facility::factory()->create();
    $recordingTier = ServiceTier::factory()->for($facility)->create([
        'name' => 'Recording Only', 'base_hourly_rate_aed_cents' => 25400,
    ]);
    $liveTier = ServiceTier::factory()->for($facility)->create([
        'name' => 'Live Mix', 'base_hourly_rate_aed_cents' => 35400,
    ]);
    FacilityPricing::create([
        'facility_id' => $facility->id, 'service_tier_id' => $liveTier->id,
        'package_type' => 'hourly', 'hours' => 1, 'price_aed_cents' => 35400,
    ]);

    $user = User::factory()->create();
    HourPackTransaction::create([
        'customer_id' => $user->id, 'facility_id' => $facility->id,
        'hours' => 10, 'type' => 'purchase', 'expires_at' => now()->addYear(),
    ]);

    $draft = new BookingDraft(
        facility_id: $facility->id,
        service_tier_id: $liveTier->id,
        package_type: 'hourly',
        starts_at: CarbonImmutable::parse('2026-06-08 10:00:00', 'Asia/Dubai'),
        ends_at:   CarbonImmutable::parse('2026-06-08 12:00:00', 'Asia/Dubai'),
        requestedPackHours: 2,
        customer_id: $user->id,
    );

    $breakdown = app(CalculateBookingPrice::class)->execute($draft);

    // base = 35400 × 2 = 70800
    // credit value = 25400 × 2 = 50800 (Recording Only base rate, not Live Mix)
    expect($breakdown->base_aed_cents)->toBe(70800);
    expect($breakdown->hour_pack_credit_value_aed_cents)->toBe(50800);
    expect($breakdown->subtotal())->toBe(70800 - 50800);  // = 20000
});

it('caps redeemed hours at the customer balance', function () {
    $facility = Facility::factory()->create();
    $recordingTier = ServiceTier::factory()->for($facility)->create([
        'name' => 'Recording Only', 'base_hourly_rate_aed_cents' => 25400,
    ]);
    FacilityPricing::create([
        'facility_id' => $facility->id, 'service_tier_id' => $recordingTier->id,
        'package_type' => 'hourly', 'hours' => 1, 'price_aed_cents' => 25400,
    ]);

    $user = User::factory()->create();
    HourPackTransaction::create([
        'customer_id' => $user->id, 'facility_id' => $facility->id,
        'hours' => 1, 'type' => 'purchase', 'expires_at' => now()->addYear(),
    ]);

    $draft = new BookingDraft(
        facility_id: $facility->id,
        service_tier_id: $recordingTier->id,
        package_type: 'hourly',
        starts_at: CarbonImmutable::parse('2026-06-08 10:00:00', 'Asia/Dubai'),
        ends_at:   CarbonImmutable::parse('2026-06-08 13:00:00', 'Asia/Dubai'),  // 3 hours
        requestedPackHours: 5,    // requested more than balance
        customer_id: $user->id,
    );

    $breakdown = app(CalculateBookingPrice::class)->execute($draft);

    // Only 1 hour can be credited (balance limit)
    expect($breakdown->hour_pack_credit_value_aed_cents)->toBe(25400);
});
```

- [ ] **Step 3: Update CalculateBookingPrice**

Add a `hourPackCredit()` private helper that computes the credit value, and include it in `execute()`:

```php
public function execute(BookingDraft $draft): PriceBreakdown
{
    $base = $this->base($draft);
    return new PriceBreakdown(
        base_aed_cents: $base,
        weekend_markup_aed_cents: $this->weekendMarkup($draft, $base),
        after_hours_markup_aed_cents: $this->afterHoursMarkup($draft, $base),
        addons_aed_cents: $this->addons($draft),
        hour_pack_credit_value_aed_cents: $this->hourPackCredit($draft),
    );
}

private function hourPackCredit(BookingDraft $draft): int
{
    if ($draft->requestedPackHours <= 0 || ! $draft->customer_id) {
        return 0;
    }

    $balance = app(\App\Modules\Customers\Actions\HourPackBalance::class)
        ->forCustomer($draft->customer_id, $draft->facility_id);

    $hoursToCredit = min($draft->requestedPackHours, $balance, $draft->totalHours());

    if ($hoursToCredit <= 0) {
        return 0;
    }

    $recordingTier = \App\Modules\Catalog\Models\ServiceTier::where([
        'facility_id' => $draft->facility_id,
        'name' => 'Recording Only',
    ])->first();

    if (! $recordingTier) {
        return 0;
    }

    return $recordingTier->base_hourly_rate_aed_cents * $hoursToCredit;
}
```

- [ ] **Step 4: RedeemHourPackHours action** (called after CreateBookingHold to record the redemption transaction)

```php
<?php
// app/Modules/Customers/Actions/RedeemHourPackHours.php

namespace App\Modules\Customers\Actions;

use App\Modules\Booking\Models\Booking;
use App\Modules\Customers\Models\HourPackTransaction;

class RedeemHourPackHours
{
    public function execute(Booking $booking, int $hoursToRedeem): void
    {
        if ($hoursToRedeem <= 0 || ! $booking->customer_id) {
            return;
        }
        HourPackTransaction::create([
            'customer_id' => $booking->customer_id,
            'facility_id' => $booking->facility_id,
            'hours' => -$hoursToRedeem,
            'type' => 'redeem',
            'booking_id' => $booking->id,
            'notes' => 'Booking ' . $booking->ulid,
        ]);
    }
}
```

- [ ] **Step 5: Wire into CreateBookingHold**

In `CreateBookingHold::execute()`, after the booking is created and addons are inserted, add:

```php
if ($draft->customer_id && $draft->requestedPackHours > 0) {
    $balance = app(\App\Modules\Customers\Actions\HourPackBalance::class)
        ->forCustomer($draft->customer_id, $draft->facility_id);
    $hoursToRedeem = min($draft->requestedPackHours, $balance, $draft->totalHours());
    if ($hoursToRedeem > 0) {
        $booking->update(['hour_pack_credits_used' => $hoursToRedeem]);
        app(\App\Modules\Customers\Actions\RedeemHourPackHours::class)
            ->execute($booking, $hoursToRedeem);
    }
}
```

- [ ] **Step 6: Pest pass**

- [ ] **Step 7: Commit** "Add hour-pack redemption to pricing engine and CreateBookingHold"

---

## Task 8: ExpireHourPacks scheduled job

**Files:**
- Create: `app/Modules/Customers/Actions/ExpireHourPacks.php`
- Create: `app/Console/Commands/ExpireHourPacksCommand.php`
- Modify: `routes/console.php`
- Create: `tests/Feature/Customers/ExpireHourPacksTest.php`

- [ ] **Step 1: Failing test**

```php
<?php

use App\Models\User;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Customers\Actions\ExpireHourPacks;
use App\Modules\Customers\Models\HourPackTransaction;

it('inserts expire rows that zero out un-expired purchase rows past their expires_at', function () {
    $user = User::factory()->create();
    $facility = Facility::factory()->create();

    HourPackTransaction::create([
        'customer_id' => $user->id, 'facility_id' => $facility->id,
        'hours' => 10, 'type' => 'purchase',
        'expires_at' => now()->subDay(),
    ]);

    $count = app(ExpireHourPacks::class)->execute();

    expect($count)->toBe(1);
    $expireRow = HourPackTransaction::where('type', 'expire')->first();
    expect($expireRow->hours)->toBe(-10);
});

it('does not double-expire an already-expired pack', function () {
    $user = User::factory()->create();
    $facility = Facility::factory()->create();

    $purchase = HourPackTransaction::create([
        'customer_id' => $user->id, 'facility_id' => $facility->id,
        'hours' => 10, 'type' => 'purchase',
        'expires_at' => now()->subDay(),
    ]);
    HourPackTransaction::create([
        'customer_id' => $user->id, 'facility_id' => $facility->id,
        'hours' => -10, 'type' => 'expire',
        'notes' => 'Linked purchase ' . $purchase->id,
    ]);

    $count = app(ExpireHourPacks::class)->execute();
    expect($count)->toBe(0);
});
```

- [ ] **Step 2: Implement**

```php
<?php
// app/Modules/Customers/Actions/ExpireHourPacks.php

namespace App\Modules\Customers\Actions;

use App\Modules\Customers\Models\HourPackTransaction;
use Illuminate\Support\Facades\DB;

class ExpireHourPacks
{
    public function execute(): int
    {
        // Find purchase rows past expiry that haven't been expired yet
        $expired = HourPackTransaction::where('type', 'purchase')
            ->where('expires_at', '<', now())
            ->get()
            ->filter(function ($purchase) {
                $alreadyExpired = HourPackTransaction::where('type', 'expire')
                    ->where('notes', 'like', "%purchase {$purchase->id}%")
                    ->exists();
                return ! $alreadyExpired;
            });

        DB::transaction(function () use ($expired) {
            foreach ($expired as $purchase) {
                HourPackTransaction::create([
                    'customer_id' => $purchase->customer_id,
                    'facility_id' => $purchase->facility_id,
                    'hours' => -$purchase->hours,
                    'type' => 'expire',
                    'notes' => "Linked purchase {$purchase->id}",
                ]);
            }
        });

        return $expired->count();
    }
}
```

- [ ] **Step 3: Command + schedule**

```bash
php artisan make:command ExpireHourPacksCommand
```

```php
<?php
// app/Console/Commands/ExpireHourPacksCommand.php

namespace App\Console\Commands;

use App\Modules\Customers\Actions\ExpireHourPacks;
use Illuminate\Console\Command;

class ExpireHourPacksCommand extends Command
{
    protected $signature = 'pod24:expire-hour-packs';
    protected $description = 'Expire un-expired hour-pack purchase rows past their expires_at';

    public function handle(ExpireHourPacks $action): int
    {
        $count = $action->execute();
        $this->info("Expired {$count} pack(s).");
        return self::SUCCESS;
    }
}
```

In `routes/console.php`:

```php
Schedule::command('pod24:expire-hour-packs')->dailyAt('02:00');
```

- [ ] **Step 4: Pest pass**

- [ ] **Step 5: Commit** "Add ExpireHourPacks action with daily scheduler"

---

## Task 9: /account dashboard

**Files:**
- Create: `app/Livewire/AccountDashboard.php`
- Create: `resources/views/livewire/account-dashboard.blade.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/Customers/AccountDashboardTest.php`

- [ ] **Step 1: Component**

```php
<?php
// app/Livewire/AccountDashboard.php

namespace App\Livewire;

use App\Modules\Booking\Models\Booking;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Customers\Actions\HourPackBalance;
use Livewire\Component;

class AccountDashboard extends Component
{
    public function render()
    {
        $user = auth()->user();
        $bookings = Booking::where('customer_id', $user->id)
            ->orderByDesc('starts_at')->limit(20)->get();
        $balanceAction = app(HourPackBalance::class);
        $balances = Facility::all()->mapWithKeys(fn ($f) =>
            [$f->slug => ['name' => $f->getTranslation('name', 'en'), 'hours' => $balanceAction->forCustomer($user->id, $f->id)]]
        );

        return view('livewire.account-dashboard', [
            'user' => $user,
            'bookings' => $bookings,
            'balances' => $balances,
        ])->extends('pod24.layouts.public');
    }
}
```

- [ ] **Step 2: View**

```blade
{{-- resources/views/livewire/account-dashboard.blade.php --}}
@section('content')
<section class="container mx-auto py-12 px-4 max-w-4xl">
<div class="flex justify-between items-center mb-8">
    <h1 class="text-3xl font-bold">Welcome, {{ $user->name ?: $user->email }}</h1>
    <form action="{{ route('logout') }}" method="POST">@csrf
        <button class="text-pod-muted underline">Sign out</button>
    </form>
</div>

<h2 class="text-xl font-bold mb-4">Hour pack balance</h2>
<div class="grid md:grid-cols-2 gap-4 mb-12">
    @forelse ($balances as $slug => $b)
        <div class="border border-pod-border rounded p-4">
            <div class="text-sm text-pod-muted">{{ $b['name'] }}</div>
            <div class="text-2xl font-bold">{{ $b['hours'] }} hours</div>
        </div>
    @empty
        <p class="text-pod-muted">No active packs.</p>
    @endforelse
</div>

<h2 class="text-xl font-bold mb-4">Your bookings</h2>
<div class="space-y-2">
    @forelse ($bookings as $booking)
        <div class="border border-pod-border rounded p-4 flex justify-between">
            <div>
                <div class="font-bold">{{ $booking->starts_at->format('l, F j, H:i') }}</div>
                <div class="text-sm text-pod-muted">{{ $booking->ulid }} · {{ ucfirst(str_replace('_', ' ', $booking->status->value)) }}</div>
            </div>
            <div class="text-right">
                AED {{ number_format($booking->total_aed_cents / 100, 2) }}
            </div>
        </div>
    @empty
        <p class="text-pod-muted">No bookings yet. <a href="{{ route('book') }}" class="underline">Book your first session →</a></p>
    @endforelse
</div>
</section>
@endsection
```

- [ ] **Step 3: Route**

```php
Route::get('/account', \App\Livewire\AccountDashboard::class)
    ->middleware('auth')
    ->name('account.dashboard');
```

- [ ] **Step 4: Failing test**

```php
<?php
// tests/Feature/Customers/AccountDashboardTest.php

use App\Models\User;
use App\Modules\Booking\Models\Booking;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\ServiceTier;
use App\Modules\Customers\Models\HourPackTransaction;

it('redirects unauthenticated visitors to /login', function () {
    $this->get('/account')->assertRedirect('/login');
});

it('shows the customer their bookings and balance', function () {
    $user = User::factory()->create();
    $facility = Facility::factory()->create();
    $tier = ServiceTier::factory()->for($facility)->create();

    Booking::factory()->for($facility)->for($tier, 'serviceTier')->create([
        'customer_id' => $user->id,
        'contact_email' => $user->email,
    ]);

    HourPackTransaction::create([
        'customer_id' => $user->id, 'facility_id' => $facility->id,
        'hours' => 10, 'type' => 'purchase', 'expires_at' => now()->addYear(),
    ]);

    $this->actingAs($user)
        ->get('/account')
        ->assertOk()
        ->assertSee('10 hours')
        ->assertSee($user->email);
});
```

- [ ] **Step 5: Pest pass**

- [ ] **Step 6: Commit** "Add /account dashboard with bookings and pack balance"

---

## Task 10: HourPackPurchaseList Livewire (browse + buy packs)

**Files:**
- Create: `app/Livewire/HourPackPurchaseList.php`
- Create: `resources/views/livewire/hour-pack-purchase-list.blade.php`
- Modify: `routes/web.php`
- Create: `tests/Feature/Customers/HourPackPurchaseListTest.php`

- [ ] **Step 1: Component**

```php
<?php

namespace App\Livewire;

use App\Modules\Catalog\Models\HourPack;
use App\Modules\Customers\Actions\PurchaseHourPack;
use Livewire\Component;

class HourPackPurchaseList extends Component
{
    public function buy(int $packId)
    {
        $pack = HourPack::findOrFail($packId);
        $session = app(PurchaseHourPack::class)->execute(auth()->user(), $pack);
        return $this->redirect($session['url']);
    }

    public function render()
    {
        return view('livewire.hour-pack-purchase-list', [
            'packs' => HourPack::where('is_active', true)->get(),
        ])->extends('pod24.layouts.public');
    }
}
```

- [ ] **Step 2: View**

```blade
@section('content')
<section class="container mx-auto py-12 px-4 max-w-4xl">
<h1 class="text-3xl font-bold mb-8">Pre-paid hour packs</h1>
<div class="grid md:grid-cols-2 gap-4">
    @foreach ($packs as $pack)
        <div class="border border-pod-border rounded p-6">
            <h2 class="text-xl font-bold">{{ $pack->getTranslation('name', 'en') }}</h2>
            <p class="text-pod-muted">{{ $pack->getTranslation('description', 'en') }}</p>
            <div class="text-3xl font-bold my-4">AED {{ number_format($pack->price_aed_cents / 100, 0) }}</div>
            <button wire:click="buy({{ $pack->id }})"
                    class="bg-pod-accent text-pod-ink-deep px-6 py-3 rounded font-bold">
                Buy now →
            </button>
        </div>
    @endforeach
</div>
</section>
@endsection
```

- [ ] **Step 3: Route**

```php
Route::get('/account/packs', \App\Livewire\HourPackPurchaseList::class)
    ->middleware('auth')
    ->name('account.packs');
```

- [ ] **Step 4: Test**

```php
<?php

use App\Models\User;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\HourPack;

it('shows active packs to a logged-in user', function () {
    $user = User::factory()->create();
    $facility = Facility::factory()->create();
    HourPack::factory()->for($facility)->create([
        'name' => ['en' => '10-Hour Pack'],
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->get('/account/packs')
        ->assertOk()
        ->assertSee('10-Hour Pack');
});
```

- [ ] **Step 5: Pest pass**

- [ ] **Step 6: Commit** "Add HourPackPurchaseList Livewire page"

---

## Task 11: Plan-4 final smoke test + tag

- [ ] **Step 1: Full pest suite**

```bash
./vendor/bin/pest
```

Expected: was 105, +20-ish from this plan → ~125 tests.

- [ ] **Step 2: Fresh DB + seed**

```bash
php artisan migrate:fresh --seed
```

- [ ] **Step 3: Manual check**

- `/login` → enter email → `/login/sent` → check log for the magic link → click → land on `/account`
- `/account/packs` → see 10-hour and 20-hour packs → click buy → Stripe Checkout (don't actually pay; just verify the redirect URL is a stripe.com URL)

- [ ] **Step 4: Tag**

```bash
git tag plan-4-accounts-hour-packs-complete
git commit --allow-empty -m "Plan 4 complete: accounts + hour packs"
```

---

## Plan 4 self-review summary

**Spec coverage:**
- § 5.4 users + hour_pack_transactions: Tasks 1, 3 ✅
- § 9 hour-pack flow: Tasks 5, 6, 7, 10 ✅
- § 14 hybrid auth (magic-link): Task 2 ✅
- Pack expiry job: Task 8 ✅
- Account dashboard: Task 9 ✅

**Out of scope (deferred):**
- Hour-pack low-balance email — Plan 6 (marketing automation)
- Profile editing UI on /account — Plan 5
- Saved addresses — Plan 5
- Customer-side reschedule UI — Plan 5

**Placeholder scan:** none.

**Ambiguity check:**
- Hour-pack credit value uses Recording Only base rate (spec § 6) — even when redeeming on a higher tier, customer pays the tier-uplift in cash. Documented in Task 7.
- Customer can request more hours than balance — capped at balance. Test in Task 7 covers this.
