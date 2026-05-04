<?php

namespace App\Modules\Customers\Actions;

use App\Modules\Customers\Models\HourPackTransaction;
use Illuminate\Support\Facades\DB;

class ExpireHourPacks
{
    public function execute(): int
    {
        // Find purchase rows past expiry that haven't been expired yet
        $expired = HourPackTransaction::where('type', 'purchase')
            ->where('expires_at', '<', now())
            ->get()
            ->filter(function ($purchase) {
                $alreadyExpired = HourPackTransaction::where('type', 'expire')
                    ->where('notes', 'like', "%purchase {$purchase->id}%")
                    ->exists();
                return ! $alreadyExpired;
            });

        DB::transaction(function () use ($expired) {
            foreach ($expired as $purchase) {
                HourPackTransaction::create([
                    'customer_id' => $purchase->customer_id,
                    'facility_id' => $purchase->facility_id,
                    'hours' => -$purchase->hours,
                    'type' => 'expire',
                    'notes' => "Linked purchase {$purchase->id}",
                ]);
            }
        });

        return $expired->count();
    }
}
