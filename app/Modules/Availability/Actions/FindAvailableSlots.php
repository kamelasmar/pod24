<?php

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
            'multi_day' => 8,
            default => throw new \InvalidArgumentException("Unknown package_type {$packageType}"),
        };

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
