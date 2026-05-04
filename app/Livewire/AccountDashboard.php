<?php

namespace App\Livewire;

use App\Modules\Booking\Models\Booking;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Customers\Actions\HourPackBalance;
use Livewire\Component;

class AccountDashboard extends Component
{
    public function render()
    {
        $user = auth()->user();
        $bookings = Booking::where('customer_id', $user->id)
            ->orderByDesc('starts_at')->limit(20)->get();
        $balanceAction = app(HourPackBalance::class);
        $balances = Facility::all()->mapWithKeys(fn ($f) =>
            [$f->slug => ['name' => $f->getTranslation('name', 'en'), 'hours' => $balanceAction->forCustomer($user->id, $f->id)]]
        );

        return view('livewire.account-dashboard', [
            'user' => $user,
            'bookings' => $bookings,
            'balances' => $balances,
        ])->extends('pod24.layouts.public');
    }
}
