<?php

namespace App\Livewire;

use App\Modules\Availability\Actions\FindAvailableSlots;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\ServiceTier;
use Carbon\CarbonImmutable;
use Livewire\Component;

class HomeBookingWidget extends Component
{
    public int $facilityId;

    public string $month;            // 'YYYY-MM'

    public ?string $selectedDate = null;

    public ?int $selectedTierId = null;

    public ?string $selectedTime = null;

    public string $packageType = 'hourly';

    public function mount(Facility $facility): void
    {
        $this->facilityId = $facility->id;
        $this->month = now('Asia/Dubai')->format('Y-m');
        $this->selectedTierId = ServiceTier::where('facility_id', $facility->id)
            ->orderBy('sort_order')->value('id');
    }

    public function prevMonth(): void
    {
        $this->month = CarbonImmutable::parse($this->month.'-01', 'Asia/Dubai')
            ->subMonth()->format('Y-m');
        $this->selectedDate = null;
        $this->selectedTime = null;
    }

    public function nextMonth(): void
    {
        $this->month = CarbonImmutable::parse($this->month.'-01', 'Asia/Dubai')
            ->addMonth()->format('Y-m');
        $this->selectedDate = null;
        $this->selectedTime = null;
    }

    public function selectDate(string $date): void
    {
        $this->selectedDate = $date;
        $this->selectedTime = null;
    }

    public function selectTier(int $tierId): void
    {
        $this->selectedTierId = $tierId;
        $this->selectedTime = null;
    }

    public function selectPackage(string $type): void
    {
        $this->packageType = $type;
        $this->selectedTime = null;
    }

    public function selectTime(string $time): void
    {
        $this->selectedTime = $time;
    }

    public function continueToCheckout()
    {
        if (! $this->selectedDate || ! $this->selectedTime || ! $this->selectedTierId) {
            return;
        }

        return $this->redirect(route('book', [
            'date' => $this->selectedDate,
            'time' => $this->selectedTime,
            'tier' => $this->selectedTierId,
            'package' => $this->packageType,
            'step' => 4,
        ]));
    }

    public function getServiceTiersProperty()
    {
        return ServiceTier::where('facility_id', $this->facilityId)
            ->where('is_active', true)
            ->orderBy('sort_order')->get();
    }

    public function getMonthGridProperty(): array
    {
        $first = CarbonImmutable::parse($this->month.'-01', 'Asia/Dubai');
        $start = $first->startOfWeek(CarbonImmutable::MONDAY);
        $end = $first->endOfMonth()->endOfWeek(CarbonImmutable::SUNDAY);
        $today = now('Asia/Dubai')->startOfDay();

        $cells = [];
        $cursor = $start;
        while ($cursor <= $end) {
            $cells[] = [
                'date' => $cursor->toDateString(),
                'day' => $cursor->day,
                'inMonth' => $cursor->format('Y-m') === $this->month,
                'isPast' => $cursor < $today,
                'isToday' => $cursor->isSameDay($today),
            ];
            $cursor = $cursor->addDay();
        }
        return $cells;
    }

    public function getMonthLabelProperty(): string
    {
        return CarbonImmutable::parse($this->month.'-01')->format('F Y');
    }

    public function getAvailableSlotsProperty(): array
    {
        if (! $this->selectedDate) {
            return [];
        }
        return app(FindAvailableSlots::class)->execute(
            $this->facilityId,
            CarbonImmutable::parse($this->selectedDate, 'Asia/Dubai'),
            $this->packageType,
        );
    }

    public function getSelectedTierProperty(): ?ServiceTier
    {
        return $this->selectedTierId
            ? ServiceTier::find($this->selectedTierId)
            : null;
    }

    public function render()
    {
        return view('livewire.home-booking-widget');
    }
}
