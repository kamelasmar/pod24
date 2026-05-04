<?php

namespace App\Modules\Availability\Actions;

use App\Modules\Availability\Models\AvailabilityBlackout;
use App\Modules\Availability\Models\AvailabilityRule;
use App\Modules\Availability\ValueObjects\Slot;
use App\Modules\Booking\Enums\BookingStatus;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\FacilityPricing;
use Carbon\CarbonImmutable;

class FindAvailableSlots
{
    /**
     * Return open slot windows of `$durationHours` consecutive hours on `$date`.
     *
     * @param  int|string  $duration  Hours wanted (1-8) for `hourly`, OR the literal
     *                                string `'multi_day'` to find a day's full open
     *                                window (returned as a single slot).
     * @return Slot[]
     */
    public function execute(int $facilityId, CarbonImmutable $date, int|string $duration = 1): array
    {
        $rule = AvailabilityRule::where([
            'facility_id' => $facilityId,
            'day_of_week' => $date->dayOfWeek,
        ])->first();

        if (! $rule) {
            return [];
        }

        $durationHours = is_int($duration)
            ? max(FacilityPricing::HOURLY_MIN, min(FacilityPricing::HOURLY_MAX, $duration))
            : $this->fullDayHours($rule);

        if ($durationHours <= 0) {
            return [];
        }

        $facility = Facility::find($facilityId);
        $capacity = $facility->max_concurrent_per_day;
        $dayStart = $date->startOfDay();
        $dayEnd = $date->endOfDay();

        $occupying = \App\Modules\Booking\Models\Booking::where('facility_id', $facilityId)
            ->whereIn('status', BookingStatus::occupyingValues())
            ->where('starts_at', '<', $dayEnd)
            ->where('ends_at', '>', $dayStart)
            ->count();

        if ($occupying >= $capacity) {
            return [];
        }

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
        while ($cursor->copy()->addHours($durationHours) <= $close) {
            $end = $cursor->copy()->addHours($durationHours);

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

    private function fullDayHours(AvailabilityRule $rule): int
    {
        [$oh] = array_map('intval', explode(':', $rule->open_time));
        [$ch] = array_map('intval', explode(':', $rule->close_time));
        return max(0, $ch - $oh);
    }
}
