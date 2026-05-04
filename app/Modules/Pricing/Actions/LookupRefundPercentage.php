<?php

namespace App\Modules\Pricing\Actions;

use App\Modules\Catalog\Models\CancellationPolicy;
use App\Modules\Pricing\Exceptions\CancellationPolicyMissing;

class LookupRefundPercentage
{
    public function execute(int $facilityId, int $hoursUntilStartsAt): int
    {
        $policy = CancellationPolicy::where('facility_id', $facilityId)
            ->where('hours_before_min', '<=', $hoursUntilStartsAt)
            ->orderByDesc('hours_before_min')
            ->first();

        if (! $policy) {
            $any = CancellationPolicy::where('facility_id', $facilityId)->exists();
            if (! $any) {
                throw new CancellationPolicyMissing("Facility {$facilityId} has no cancellation policy configured");
            }
            return 0;
        }

        return $policy->refund_percentage;
    }
}
