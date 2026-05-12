# Pod24 — Plan 5: Quotes pipeline + Cancel/Reschedule + File delivery

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Operationalize three flows that exist only in the data model today: (1) corporate quote pipeline (Filament admin inbox + status workflow + manual convert-to-booking), (2) customer-facing cancel/reschedule with Stripe refunds, (3) file delivery via signed S3 URLs.

**Architecture:** Stays inside existing modules. Quotes module gets a Filament resource + admin email notification. Booking module gets `CancelBooking` + `RescheduleBooking` actions + `BookingFile` model + matching admin/customer surfaces. S3 via Laravel's existing flysystem driver — already in the Laravel skeleton, just needs env vars wired.

**Tech stack:** Laravel 11 / Filament 3 / Stripe SDK refund API / `league/flysystem-aws-s3-v3` / Livewire 3.

**Spec reference:** § 10 (B2B/offsite quote flow), § 11 (Cancellation & reschedule), § 12 (File delivery).

**Depends on:** Plan 4 complete (`plan-4-accounts-hour-packs-complete`).

---

## Pre-flight file structure

```
pod24/
├── app/
│   ├── Filament/Resources/Quotes/
│   │   └── QuoteResource.php (+ Pages, RelationManagers)
│   ├── Modules/
│   │   ├── Booking/
│   │   │   ├── Models/BookingFile.php           (new)
│   │   │   ├── Actions/
│   │   │   │   ├── CancelBooking.php             (new)
│   │   │   │   └── RescheduleBooking.php         (new)
│   │   │   ├── Events/
│   │   │   │   ├── BookingCancelled.php          (new)
│   │   │   │   └── BookingFilesReady.php         (new)
│   │   │   ├── Mail/
│   │   │   │   ├── BookingCancelledMail.php      (new)
│   │   │   │   └── BookingFilesReadyMail.php     (new)
│   │   │   └── Listeners/
│   │   │       ├── SendBookingCancelledEmail.php (new)
│   │   │       └── SendBookingFilesReadyEmail.php (new)
│   │   ├── Quotes/
│   │   │   ├── Mail/QuoteSubmittedMail.php       (new — admin notification)
│   │   │   └── Actions/ConvertQuoteToBooking.php (new)
│   │   └── Payments/
│   │       └── Actions/IssueRefund.php           (new — Stripe refund wrapper)
│   └── Livewire/
│       └── BookingActions.php                    (new — customer cancel/reschedule UI)
└── database/migrations/
    └── *_create_booking_files_table.php
```

---

## Phase A — Quotes pipeline polish

### Task 1: QuoteResource Filament admin

**Files:**
- Create: `app/Filament/Resources/Quotes/QuoteResource.php` + Pages
- Modify: `app/Providers/Filament/AdminPanelProvider.php` (add `discoverResources` for the new namespace)
- Create: `tests/Feature/Quotes/QuoteResourceTest.php`

- [ ] **Step 1: Generate the resource**

```bash
php artisan make:filament-resource Quotes/Quote --model-namespace="App\Modules\Quotes\Models\Quote" --generate
```

Move/fix double-nesting if Filament's generator nests under `Quotes/Quotes/...`.

- [ ] **Step 2: Replace form/table**

```php
// app/Filament/Resources/Quotes/QuoteResource.php
public static function form(Form $form): Form
{
    return $form->schema([
        Forms\Components\TextInput::make('ulid')->disabled(),
        Forms\Components\TextInput::make('contact_name')->required(),
        Forms\Components\TextInput::make('contact_email')->required()->email(),
        Forms\Components\TextInput::make('contact_phone'),
        Forms\Components\TextInput::make('contact_company'),
        Forms\Components\Select::make('event_type')->options([
            'conference' => 'Conference activation',
            'brand_series' => 'Branded series',
            'offsite' => 'Corporate offsite',
            'takeover' => 'Studio takeover',
            'other' => 'Other',
        ])->required(),
        Forms\Components\Select::make('status')->options([
            'new' => 'New',
            'contacted' => 'Contacted',
            'quoted' => 'Quoted',
            'won' => 'Won',
            'lost' => 'Lost',
        ])->required(),
        Forms\Components\TextInput::make('attendees_estimate'),
        Forms\Components\TextInput::make('days_estimate'),
        Forms\Components\TextInput::make('location_preference'),
        Forms\Components\TagsInput::make('service_interests'),
        Forms\Components\TextInput::make('preferred_dates'),
        Forms\Components\Textarea::make('message')->rows(4),
    ]);
}

public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('ulid')->limit(8)->copyable(),
            Tables\Columns\TextColumn::make('contact_name')->searchable(),
            Tables\Columns\TextColumn::make('contact_company')->searchable(),
            Tables\Columns\TextColumn::make('event_type')->badge(),
            Tables\Columns\BadgeColumn::make('status')->colors([
                'gray' => 'new',
                'warning' => 'contacted',
                'primary' => 'quoted',
                'success' => 'won',
                'danger' => 'lost',
            ])->sortable(),
            Tables\Columns\TextColumn::make('created_at')->dateTime('M j, H:i')->sortable(),
        ])
        ->filters([
            Tables\Filters\SelectFilter::make('status')->options([
                'new' => 'New', 'contacted' => 'Contacted', 'quoted' => 'Quoted',
                'won' => 'Won', 'lost' => 'Lost',
            ]),
        ])
        ->defaultSort('created_at', 'desc')
        ->actions([Tables\Actions\EditAction::make()]);
}
```

- [ ] **Step 3: Register discoverResources**

In `AdminPanelProvider::panel()`:
```php
->discoverResources(in: app_path('Filament/Resources/Quotes'), for: 'App\\Filament\\Resources\\Quotes')
```

- [ ] **Step 4: Smoke test**

```php
// tests/Feature/Quotes/QuoteResourceTest.php
beforeEach(function () {
    Role::firstOrCreate(['name' => 'Admin']);
    $this->actingAs(User::factory()->create()->assignRole('Admin'));
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('lists quotes in the admin', function () {
    Quote::factory()->count(3)->create();
    $this->get('/admin/quotes/quotes')->assertOk();
});
```

- [ ] **Step 5: Commit** "Add Filament QuoteResource with status pipeline"

### Task 2: QuoteSubmittedMail — admin notification on new quote

**Files:**
- Create: `app/Modules/Quotes/Mail/QuoteSubmittedMail.php`
- Create: `app/Modules/Quotes/Events/QuoteSubmitted.php`
- Create: `app/Modules/Quotes/Listeners/SendQuoteSubmittedEmail.php`
- Create: `resources/views/mail/quote-submitted.blade.php`
- Modify: `app/Livewire/CorporateInquiryWizard.php` — dispatch event after Quote::create
- Modify: `app/Providers/AppServiceProvider.php` — register listener
- Create: `tests/Feature/Quotes/QuoteSubmittedEmailTest.php`

- [ ] **Step 1: Event**

```php
namespace App\Modules\Quotes\Events;

use App\Modules\Quotes\Models\Quote;
use Illuminate\Foundation\Events\Dispatchable;

class QuoteSubmitted
{
    use Dispatchable;
    public function __construct(public Quote $quote) {}
}
```

- [ ] **Step 2: Mailable** — `markdown: 'mail.quote-submitted'`

```php
public function envelope(): Envelope
{
    return new Envelope(
        subject: "New corporate inquiry — {$this->quote->contact_company} · {$this->quote->event_type}",
    );
}
```

- [ ] **Step 3: View** at `resources/views/mail/quote-submitted.blade.php`

```blade
<x-mail::message>
# New corporate inquiry

**From:** {{ $quote->contact_name }} ({{ $quote->contact_company ?: 'no company' }})
**Email:** {{ $quote->contact_email }}
**Phone:** {{ $quote->contact_phone ?: 'not provided' }}

**Event type:** {{ $quote->event_type }}
**Audience:** {{ $quote->attendees_estimate ?: 'not specified' }}
**Length:** {{ $quote->days_estimate ?: 'not specified' }}
**Location:** {{ $quote->location_preference ?: 'not specified' }}
**Preferred dates:** {{ $quote->preferred_dates ?: 'not specified' }}

**Services of interest:**
@foreach ($quote->service_interests ?? [] as $s)
- {{ $s }}
@endforeach

@if ($quote->message)
**Message:**
{{ $quote->message }}
@endif

<x-mail::button :url="config('app.url') . '/admin/quotes/quotes/' . $quote->id . '/edit'">
View in admin
</x-mail::button>

Reference: {{ $quote->ulid }}
</x-mail::message>
```

- [ ] **Step 4: Listener** — `SendQuoteSubmittedEmail implements ShouldQueue`, mails to `config('mail.from.address')` (the same inbox `pod24@twofour54.com`).

- [ ] **Step 5: Wire event dispatch in CorporateInquiryWizard**

After `Quote::create([...])` in `submit()`:
```php
\App\Modules\Quotes\Events\QuoteSubmitted::dispatch($quote);
```

- [ ] **Step 6: Register listener in AppServiceProvider::boot()**

```php
Event::listen(QuoteSubmitted::class, SendQuoteSubmittedEmail::class);
```

- [ ] **Step 7: Test**

```php
it('sends admin notification email when corporate inquiry submitted', function () {
    Mail::fake();
    Livewire::test(CorporateInquiryWizard::class)
        ->set('eventType', 'conference')
        ->set('locationPreference', 'studio')
        ->set('contactName', 'Test')
        ->set('contactEmail', 't@example.com')
        ->call('submit');

    Mail::assertSent(QuoteSubmittedMail::class);
});
```

- [ ] **Step 8: Commit** "Notify admin via email on new corporate inquiry"

### Task 3: ConvertQuoteToBooking action

Triggered from Filament's QuoteResource as a row action. Takes a won quote and creates a Booking with sensible defaults (admin can edit it after).

**Files:**
- Create: `app/Modules/Quotes/Actions/ConvertQuoteToBooking.php`
- Modify: `QuoteResource.php` — add `Action::make('convert')` in row actions
- Create: `tests/Feature/Quotes/ConvertQuoteToBookingTest.php`

- [ ] **Step 1: Action**

```php
namespace App\Modules\Quotes\Actions;

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Quotes\Models\Quote;

class ConvertQuoteToBooking
{
    public function execute(Quote $quote, array $overrides = []): Booking
    {
        $facility = Facility::where('slug', 'pod24-portable')->firstOrFail();
        $tier = $facility->serviceTiers()->orderBy('sort_order')->firstOrFail();

        $booking = Booking::create(array_merge([
            'facility_id' => $facility->id,
            'service_tier_id' => $tier->id,
            'package_type' => 'hourly',
            'starts_at' => now()->addWeeks(2),
            'ends_at' => now()->addWeeks(2)->addHours(2),
            'total_hours' => 2,
            'status' => BookingStatus::PendingPayment->value,
            'contact_name' => $quote->contact_name,
            'contact_email' => $quote->contact_email,
            'contact_phone' => $quote->contact_phone,
            'address' => $facility->address,
            'subtotal_aed_cents' => 0,
            'vat_aed_cents' => 0,
            'total_aed_cents' => 0,
        ], $overrides));

        $quote->update(['status' => 'won']);

        return $booking;
    }
}
```

- [ ] **Step 2: Filament action**

In QuoteResource::table:
```php
->actions([
    Tables\Actions\EditAction::make(),
    Tables\Actions\Action::make('convert')
        ->label('Convert to booking')
        ->icon('heroicon-o-arrow-right-circle')
        ->color('success')
        ->visible(fn ($record) => in_array($record->status, ['quoted', 'won']))
        ->requiresConfirmation()
        ->action(function ($record) {
            $booking = app(\App\Modules\Quotes\Actions\ConvertQuoteToBooking::class)->execute($record);
            return redirect("/admin/booking/bookings/{$booking->id}/edit");
        }),
])
```

- [ ] **Step 3: Test** — quote in 'quoted' status, run convert, asserts Booking row created and quote status='won'

- [ ] **Step 4: Commit** "Add ConvertQuoteToBooking action + Filament row action"

---

## Phase B — Cancel & Reschedule

### Task 4: IssueRefund action (Stripe refund wrapper)

**Files:**
- Create: `app/Modules/Payments/Actions/IssueRefund.php`
- Create: `tests/Feature/Payments/IssueRefundTest.php`

- [ ] **Step 1: Action — closure-injectable for tests (matches CreatePaymentIntent pattern)**

```php
namespace App\Modules\Payments\Actions;

use App\Modules\Booking\Models\Booking;

class IssueRefund
{
    /** @var callable */
    private $createRefund;

    public function __construct(?callable $createRefund = null)
    {
        $this->createRefund = $createRefund ?? function (array $params) {
            \Stripe\Stripe::setApiKey(config('stripe.secret'));
            return \Stripe\Refund::create($params);
        };
    }

    public function execute(Booking $booking, int $amountAedCents): object
    {
        if (! $booking->stripe_payment_intent_id) {
            throw new \RuntimeException("Booking {$booking->ulid} has no Stripe PaymentIntent.");
        }
        if ($amountAedCents <= 0) {
            throw new \InvalidArgumentException("Refund amount must be > 0");
        }

        return ($this->createRefund)([
            'payment_intent' => $booking->stripe_payment_intent_id,
            'amount' => $amountAedCents,
            'metadata' => ['booking_ulid' => $booking->ulid],
        ]);
    }
}
```

- [ ] **Step 2: Test** — uses closure stub, asserts amount passed correctly + throws on no PI.

- [ ] **Step 3: Commit** "Add IssueRefund action with closure-injectable Stripe client"

### Task 5: CancelBooking action

Looks up refund percentage from cancellation policy, issues Stripe refund, updates booking status, fires event.

**Files:**
- Create: `app/Modules/Booking/Actions/CancelBooking.php`
- Create: `app/Modules/Booking/Events/BookingCancelled.php`
- Create: `tests/Feature/Booking/CancelBookingTest.php`

- [ ] **Step 1: Event**

```php
namespace App\Modules\Booking\Events;
use App\Modules\Booking\Models\Booking;
use Illuminate\Foundation\Events\Dispatchable;

class BookingCancelled {
    use Dispatchable;
    public function __construct(public Booking $booking, public int $refundAmountAedCents) {}
}
```

- [ ] **Step 2: Action**

```php
namespace App\Modules\Booking\Actions;

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Events\BookingCancelled;
use App\Modules\Booking\Models\Booking;
use App\Modules\Payments\Actions\IssueRefund;
use App\Modules\Pricing\Actions\LookupRefundPercentage;
use Illuminate\Support\Facades\DB;

class CancelBooking
{
    public function execute(Booking $booking, string $cancelledBy = 'customer'): array
    {
        if (! in_array($booking->status, [BookingStatus::Confirmed, BookingStatus::PendingPayment])) {
            throw new \RuntimeException("Booking {$booking->ulid} cannot be cancelled from {$booking->status->value}");
        }

        $hoursUntil = (int) max(0, now()->diffInHours($booking->starts_at, false));
        $percentage = app(LookupRefundPercentage::class)
            ->execute($booking->facility_id, $hoursUntil);

        $refundAmount = (int) round($booking->total_aed_cents * $percentage / 100);

        DB::transaction(function () use ($booking, $cancelledBy, $refundAmount) {
            $booking->update([
                'status' => BookingStatus::Cancelled->value,
                'cancelled_at' => now(),
                'cancelled_by' => $cancelledBy,
                'refund_amount_aed_cents' => $refundAmount,
            ]);

            if ($refundAmount > 0 && $booking->stripe_payment_intent_id) {
                app(IssueRefund::class)->execute($booking->fresh(), $refundAmount);
            }
        });

        BookingCancelled::dispatch($booking->fresh(), $refundAmount);

        return [
            'refund_percentage' => $percentage,
            'refund_amount_aed_cents' => $refundAmount,
        ];
    }
}
```

- [ ] **Step 3: Tests** — 4 cases: cancel 7d+ out gives 100%, 3-7d gives 50%, <3d gives 0%, can't cancel completed booking.

- [ ] **Step 4: Commit** "Add CancelBooking action with policy-driven refund"

### Task 6: RescheduleBooking action

Per spec § 11: only allowed if new slot's total price equals original. Otherwise customer must cancel + rebook.

**Files:**
- Create: `app/Modules/Booking/Actions/RescheduleBooking.php`
- Create: `tests/Feature/Booking/RescheduleBookingTest.php`

- [ ] **Step 1: Action**

```php
namespace App\Modules\Booking\Actions;

use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Booking\Models\Booking;
use App\Modules\Pricing\Actions\CalculateBookingPrice;
use App\Modules\Pricing\ValueObjects\BookingDraft;
use Carbon\CarbonImmutable;

class RescheduleBooking
{
    public function execute(Booking $booking, CarbonImmutable $newStartsAt): Booking
    {
        if ($booking->status !== BookingStatus::Confirmed) {
            throw new \RuntimeException("Only confirmed bookings can be rescheduled");
        }

        $newEndsAt = $newStartsAt->addHours($booking->total_hours);

        $draft = new BookingDraft(
            facility_id: $booking->facility_id,
            service_tier_id: $booking->service_tier_id,
            package_type: $booking->package_type,
            starts_at: $newStartsAt,
            ends_at: $newEndsAt,
        );
        $newPrice = app(CalculateBookingPrice::class)->execute($draft);

        if ($newPrice->total() !== $booking->total_aed_cents) {
            throw new \RuntimeException(
                "Reschedule rejected: new slot's price ({$newPrice->total()}) differs from original ({$booking->total_aed_cents}). Cancel and rebook instead."
            );
        }

        $booking->update([
            'starts_at' => $newStartsAt,
            'ends_at' => $newEndsAt,
        ]);

        return $booking->fresh();
    }
}
```

- [ ] **Step 2: Tests** — 3 cases: same-price reschedule succeeds, price-changed reschedule throws, hold/pending bookings can't reschedule.

- [ ] **Step 3: Commit** "Add RescheduleBooking action with price-equality guard"

### Task 7: BookingActions Livewire — customer-facing cancel/reschedule on /account

**Files:**
- Create: `app/Livewire/BookingActions.php`
- Create: `resources/views/livewire/booking-actions.blade.php`
- Modify: `resources/views/livewire/account-dashboard.blade.php` — render `<livewire:booking-actions :booking="$booking" />` per row
- Create: `tests/Feature/Booking/BookingActionsTest.php`

- [ ] **Step 1: Component**

```php
namespace App\Livewire;

use App\Modules\Booking\Actions\CancelBooking;
use App\Modules\Booking\Models\Booking;
use App\Modules\Pricing\Actions\LookupRefundPercentage;
use Livewire\Component;

class BookingActions extends Component
{
    public Booking $booking;
    public bool $confirmingCancel = false;

    public function cancel(): void
    {
        $result = app(CancelBooking::class)->execute($this->booking, 'customer');
        $this->confirmingCancel = false;
        $this->dispatch('booking-cancelled', refund: $result['refund_amount_aed_cents']);
    }

    public function getRefundPreviewProperty(): array
    {
        $hours = (int) max(0, now()->diffInHours($this->booking->starts_at, false));
        $pct = app(LookupRefundPercentage::class)->execute($this->booking->facility_id, $hours);
        return [
            'percentage' => $pct,
            'amount_aed' => round($this->booking->total_aed_cents * $pct / 100 / 100, 2),
            'hours_until' => $hours,
        ];
    }

    public function render() { return view('livewire.booking-actions'); }
}
```

- [ ] **Step 2: View**

```blade
<div>
    @if ($booking->status->value === 'confirmed' && $booking->starts_at > now())
        @if ($confirmingCancel)
            @php $r = $this->refundPreview; @endphp
            <div class="bg-pod-surface border border-pod-border rounded-lg p-4 mt-3 text-sm">
                <div class="font-bold text-pod-ink-deep mb-1">Confirm cancellation</div>
                <div class="text-pod-muted mb-3">
                    {{ $r['hours_until'] }} hours until your session.
                    Refund: <strong>{{ $r['percentage'] }}%</strong> = AED {{ number_format($r['amount_aed'], 2) }}.
                </div>
                <div class="flex gap-2">
                    <button wire:click="cancel" class="bg-red-600 text-white px-4 py-2 rounded text-sm font-bold">Confirm cancel</button>
                    <button wire:click="$set('confirmingCancel', false)" class="px-4 py-2 rounded text-sm border border-pod-border">Keep booking</button>
                </div>
            </div>
        @else
            <button wire:click="$set('confirmingCancel', true)" class="text-xs text-pod-muted hover:text-red-600 underline mt-1">Cancel booking</button>
        @endif
    @endif
</div>
```

- [ ] **Step 3: Wire into AccountDashboard view** — pass each `$booking` to the component.

- [ ] **Step 4: Test** — sign in as user, render dashboard, click cancel → confirm → assert booking status='cancelled' and refund recorded.

- [ ] **Step 5: Commit** "Add customer-facing cancel button with refund preview"

### Task 8: BookingCancelledMail listener

**Files:**
- Create: `app/Modules/Booking/Mail/BookingCancelledMail.php`
- Create: `app/Modules/Booking/Listeners/SendBookingCancelledEmail.php`
- Create: `resources/views/mail/booking-cancelled.blade.php`
- Modify: `app/Providers/AppServiceProvider.php` — register listener
- Create: `tests/Feature/Booking/BookingCancelledEmailTest.php`

(Follows the same `markdown:` mailable pattern as BookingConfirmedMail.)

- [ ] **Step 1-5:** create files, register listener, write test, commit. Mail goes to `$booking->contact_email`, subject "Your Pod24 booking is cancelled — refund of AED X processed".

- [ ] **Step 6: Commit** "Send confirmation email when booking is cancelled"

---

## Phase C — File delivery

### Task 9: BookingFile model + migration

**Files:**
- Create: `app/Modules/Booking/Models/BookingFile.php`
- Create: `database/migrations/*_create_booking_files_table.php`
- Create: `database/factories/BookingFileFactory.php`
- Create: `tests/Feature/Booking/BookingFileTest.php`

- [ ] **Step 1: Migration**

```php
Schema::create('booking_files', function (Blueprint $table) {
    $table->id();
    $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
    $table->string('original_filename');
    $table->string('storage_path');         // s3 key
    $table->string('storage_disk')->default('s3');
    $table->unsignedBigInteger('size_bytes');
    $table->string('mime_type');
    $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
    $table->index('booking_id');
});
```

- [ ] **Step 2: Model** — belongsTo Booking, getDownloadUrlAttribute returns `Storage::disk($this->storage_disk)->temporaryUrl($this->storage_path, now()->addDay())`.

- [ ] **Step 3: Booking model** — add `files(): HasMany` relation.

- [ ] **Step 4: Test** — 1 test for creation + relation.

- [ ] **Step 5: Commit** "Add BookingFile model"

### Task 10: S3 disk configuration

**Files:**
- Modify: `.env.example` — add AWS_* keys + a `FILESYSTEM_DELIVERY_DISK=s3` switch
- Modify: `config/filesystems.php` — verify s3 disk is configured (it is by default in Laravel 11)
- Modify: `deploy/README.md` — note S3 setup steps

- [ ] **Step 1: .env.example**

```ini
# File delivery (raw footage downloads)
FILESYSTEM_DELIVERY_DISK=s3
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=me-central-1
AWS_BUCKET=pod24-delivery
AWS_USE_PATH_STYLE_ENDPOINT=false
```

- [ ] **Step 2: composer require league/flysystem-aws-s3-v3** (run locally + commit composer.lock)

- [ ] **Step 3: README — append file delivery setup**

Brief note: create S3 bucket `pod24-delivery` in `me-central-1`, IAM user with `s3:PutObject`/`GetObject` scope, paste creds into `.env`.

- [ ] **Step 4: Commit** "Configure S3 disk for file delivery"

### Task 11: Filament booking file upload action

**Files:**
- Modify: `app/Filament/Resources/Booking/BookingResource.php` — add `RelationManager` or inline action for file upload
- Create: `app/Filament/Resources/Booking/BookingResource/RelationManagers/FilesRelationManager.php`
- Create: `app/Modules/Booking/Events/BookingFilesReady.php`
- Create: `tests/Feature/Booking/BookingFilesRelationTest.php`

- [ ] **Step 1: Generate**

```bash
php artisan make:filament-relation-manager Booking/BookingResource files original_filename
```

- [ ] **Step 2: Edit form/table**

```php
public function form(Form $form): Form
{
    return $form->schema([
        Forms\Components\FileUpload::make('storage_path')
            ->disk(config('filesystems.default') === 'local' ? 'public' : 's3')
            ->directory(fn ($livewire) => "bookings/{$livewire->ownerRecord->ulid}")
            ->preserveFilenames()
            ->required(),
    ]);
}
```

(Filament's `FileUpload` writes to the disk + saves the key in the column.)

After-create hook: capture `original_filename`, `size_bytes`, `mime_type`, `uploaded_by_user_id`.

- [ ] **Step 3: "Mark as ready" action** in BookingResource form/page — dispatches `BookingFilesReady::dispatch($booking)`. Sets a `files_ready_at` timestamp on Booking (new column — add migration).

- [ ] **Step 4: Test** — admin uploads file via the relation manager, asserts BookingFile row exists with metadata.

- [ ] **Step 5: Commit** "Add file upload relation manager to BookingResource"

### Task 12: Customer file download page

**Files:**
- Create: `app/Livewire/BookingFiles.php`
- Create: `resources/views/livewire/booking-files.blade.php`
- Modify: `routes/web.php` — `/account/bookings/{ulid}/files`
- Modify: `app/Livewire/AccountDashboard.php` view — add "Download files" link on confirmed bookings with `files_ready_at != null`
- Create: `tests/Feature/Booking/BookingFilesPageTest.php`

- [ ] **Step 1: Component** — authorize that auth user owns this booking. Render list of files with signed-URL links.

```php
public function render()
{
    abort_unless($this->booking->customer_id === auth()->id(), 403);
    return view('livewire.booking-files', [
        'booking' => $this->booking,
        'files' => $this->booking->files,
    ])->extends('pod24.layouts.public');
}
```

- [ ] **Step 2: View** — list of files with size, mime icon, download button (links to signed URL).

- [ ] **Step 3: Test** — non-owner gets 403, owner sees list with N files.

- [ ] **Step 4: Commit** "Add customer-facing file download page with signed URLs"

### Task 13: BookingFilesReady email listener

**Files:**
- Create: `app/Modules/Booking/Mail/BookingFilesReadyMail.php`
- Create: `app/Modules/Booking/Listeners/SendBookingFilesReadyEmail.php`
- Create: `resources/views/mail/booking-files-ready.blade.php`
- Modify: `AppServiceProvider::boot()` — register listener
- Create: `tests/Feature/Booking/BookingFilesReadyEmailTest.php`

Standard mailable + listener pattern. Email links to `/account/bookings/{ulid}/files`. Listener implements `ShouldQueue`.

- [ ] Steps 1-5 + commit

### Task 14: Final smoke test + tag

```bash
./vendor/bin/pest
php artisan migrate:fresh --seed
git tag plan-5-quotes-cancel-files-complete
git commit --allow-empty -m "Plan 5 complete: quotes pipeline + cancel/reschedule + file delivery"
```

---

## Plan 5 self-review

**Spec coverage:**
- § 10 quote pipeline (admin inbox + statuses + convert action + email notification): Tasks 1-3 ✅
- § 11 cancellation (policy-driven refund + Stripe refund API + customer UI + email): Tasks 4-5, 7-8 ✅
- § 11 reschedule (price-equality guard): Task 6 ✅
- § 12 file delivery (BookingFile model + S3 + admin upload + signed customer URLs + email): Tasks 9-13 ✅

**Out of scope (Plan 6 territory):**
- Mailchimp + Hubspot lifecycle automation for cancelled/refunded events
- Newsletter signup form
- All other marketing-automation event dispatches

**Risks:**
- S3 setup needs real AWS creds — without them, dev/test runs against `public` local disk (Filament's `FileUpload` is disk-aware, so this auto-degrades).
- Stripe refund API can fail (insufficient funds, expired payment); we don't queue retries here — let Stripe surface the error and admin handles manually.
- Reschedule price-equality is strict; UI on the customer side should explain this clearly. Plan 5 ships admin-side only for reschedule (per Phase B scope). Customer reschedule UI can be a Plan 6 add-on or never (cancel+rebook is just as good).

**Expected test count after Plan 5:** ~140 → ~165 (Quote resource +1, Quote email +1, Convert action +1, IssueRefund +2, CancelBooking +4, RescheduleBooking +3, BookingActions +2, BookingCancelledEmail +1, BookingFile +1, Files relation +1, BookingFiles page +2, BookingFilesReady email +1).
