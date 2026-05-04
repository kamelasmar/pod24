<?php

namespace App\Livewire;

use App\Modules\Availability\Actions\FindAvailableSlots;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\ServiceTier;
use Carbon\CarbonImmutable;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * 6-step in-studio booking wizard.
 *  1. facility (auto-selected when only one is active)
 *  2. service tier
 *  3. date + package + time slot
 *  4. add-ons (optional)
 *  5. contact + auth
 *  6. payment (Stripe Payment Element)
 *
 * The address step that used to gate non-AD addresses to /quote/offsite was
 * removed: Pod24 is a fixed studio at Yas Creative Hub. Off-site/remote
 * filming requests have their own flow at /quote/offsite (Plan 5).
 */
class BookingWizard extends Component
{
    #[Url]
    public int $step = 1;

    #[Url(as: 'tier')]
    public ?int $serviceTierId = null;

    #[Url(as: 'package')]
    public ?string $packageType = 'hourly';

    #[Url]
    public ?string $date = null;

    #[Url]
    public ?string $time = null;

    public ?int $facilityId = null;

    public array $selectedAddons = [];
    public string $contactName = '';
    public string $contactEmail = '';
    public string $contactPhone = '';
    public bool $marketingConsent = true;
    public ?string $clientSecret = null;
    public ?string $bookingUlid = null;

    public function mount(): void
    {
        $this->facilityId = Facility::where('slug', 'pod24-portable')->value('id');

        // Sanitize caller-controlled tier query param.
        if ($this->serviceTierId) {
            $valid = ServiceTier::where('id', $this->serviceTierId)
                ->where('facility_id', $this->facilityId)
                ->exists();
            if (! $valid) {
                $this->serviceTierId = null;
                $this->step = 2;
            }
        }
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

    public function getServiceTiersProperty()
    {
        return ServiceTier::where('facility_id', $this->facilityId)->orderBy('sort_order')->get();
    }

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
        $this->step = 6;
        $this->createHoldAndPaymentIntent();
    }

    private function createHoldAndPaymentIntent(): void
    {
        $draft = new \App\Modules\Pricing\ValueObjects\BookingDraft(
            facility_id: $this->facilityId,
            service_tier_id: $this->serviceTierId,
            package_type: $this->packageType,
            starts_at: CarbonImmutable::parse($this->date . ' ' . $this->time, 'Asia/Dubai'),
            ends_at: CarbonImmutable::parse($this->date . ' ' . $this->time, 'Asia/Dubai')->addHours($this->packageDuration()),
            addons: $this->selectedAddons,
        );

        try {
            $booking = app(\App\Modules\Booking\Actions\CreateBookingHold::class)->execute(
                draft: $draft,
                contact: ['name' => $this->contactName, 'email' => $this->contactEmail, 'phone' => $this->contactPhone],
                address: null,    // null → CreateBookingHold uses the facility's address (the studio)
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
        return view('livewire.booking-wizard')->extends('pod24.layouts.public');
    }
}
