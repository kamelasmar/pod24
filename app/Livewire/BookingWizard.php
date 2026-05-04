<?php

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

    public array $selectedAddons = [];   // [['addon_id' => int, 'qty' => int], ...]
    public string $contactName = '';
    public string $contactEmail = '';
    public string $contactPhone = '';
    public bool $marketingConsent = true;       // pre-checked per spec § 13.2
    public ?string $clientSecret = null;
    public ?string $bookingUlid = null;

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
