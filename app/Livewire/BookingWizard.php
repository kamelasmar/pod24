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
