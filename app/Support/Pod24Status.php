<?php

namespace App\Support;

use App\Modules\Availability\Models\AvailabilityRule;
use Carbon\Carbon;

final class Pod24Status
{
    public bool $isOpen = false;
    public string $label = 'Studio closed';

    public static function current(): self
    {
        $self = new self;

        if (env('POD24_FORCE_STUDIO_OPEN')) {
            $self->isOpen = true;
            $self->label = 'Open now · book a slot';
            return $self;
        }

        $now = Carbon::now('Asia/Dubai');
        $todayRule = AvailabilityRule::where('day_of_week', $now->dayOfWeek)->first();
        $fmt = fn ($t) => $t ? substr((string) $t, 0, 5) : null;

        if ($todayRule) {
            [$openH] = array_map('intval', explode(':', $todayRule->open_time));
            [$closeH] = array_map('intval', explode(':', $todayRule->close_time));
            $hour = (int) $now->format('G');
            if ($hour >= $openH && $hour < $closeH) {
                $self->isOpen = true;
                $self->label = 'Open until '.$fmt($todayRule->close_time);
                return $self;
            }
            if ($hour < $openH) {
                $self->label = 'Opens today at '.$fmt($todayRule->open_time);
                return $self;
            }
        }

        for ($i = 1; $i <= 7; $i++) {
            $candidate = $now->copy()->addDays($i);
            $next = AvailabilityRule::where('day_of_week', $candidate->dayOfWeek)->first();
            if ($next) {
                $dayLabel = $i === 1 ? 'tomorrow' : $candidate->format('l');
                $self->label = 'Opens '.$dayLabel.' at '.$fmt($next->open_time);
                return $self;
            }
        }

        return $self;
    }
}
