<?php

namespace App\Modules\Customers\Actions;

use App\Modules\Customers\Models\HourPackTransaction;

class HourPackBalance
{
    public function forCustomer(int $customerId, int $facilityId): int
    {
        return (int) HourPackTransaction::where('customer_id', $customerId)
            ->where('facility_id', $facilityId)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->sum('hours');
    }
}
