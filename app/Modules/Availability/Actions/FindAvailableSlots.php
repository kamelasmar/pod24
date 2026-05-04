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
            'multi_day' => 8,         // multi-day shows as full-day slots; the date-range is selected separately
            default => throw new \InvalidArgumentException("Unknown package_type {$packageType}"),
        };

        [$openH, $openM] = array_map('intval', explode(':', $rule->open_time));
        [$closeH, $closeM] = array_map('intval', explode(':', $rule->close_time));
        $open = $date->setTime($openH, $openM);
        $close = $date->setTime($closeH, $closeM);
        $tz = $open->getTimezone();

        // Blackouts are stored as wall-clock timestamps; re-interpret them in the booking's
        // timezone so comparisons line up regardless of APP_TIMEZONE.
        $blackouts = AvailabilityBlackout::where('facility_id', $facilityId)->get()
            ->map(function ($bo) use ($tz) {
                $start = CarbonImmutable::parse($bo->starts_at->format('Y-m-d H:i:s'), $tz);
                $end = CarbonImmutable::parse($bo->ends_at->format('Y-m-d H:i:s'), $tz);
                return ['starts_at' => $start, 'ends_at' => $end];
            })
            ->filter(fn ($bo) => $bo['starts_at'] < $close && $bo['ends_at'] > $open)
            ->values();

        $slots = [];
        $cursor = $open;
        while ($cursor->copy()->addHours($duration) <= $close) {
            $end = $cursor->copy()->addHours($duration);

            $blocked = $blackouts->contains(function ($bo) use ($cursor, $end) {
                return $bo['starts_at'] < $end && $bo['ends_at'] > $cursor;
            });

            if (! $blocked) {
                $slots[] = new Slot($cursor, $end);
            }
            $cursor = $cursor->addHour();
        }

        return $slots;
    }
}
