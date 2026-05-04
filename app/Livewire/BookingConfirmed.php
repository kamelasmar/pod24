<?php

namespace App\Livewire;

use App\Modules\Booking\Models\Booking;
use Livewire\Attributes\Url;
use Livewire\Component;

class BookingConfirmed extends Component
{
    #[Url]
    public string $ulid = '';

    public function render()
    {
        $booking = Booking::where('ulid', $this->ulid)->first();
        return view('livewire.booking-confirmed', ['booking' => $booking])
            ->extends('pod24.layouts.public');
    }
}
