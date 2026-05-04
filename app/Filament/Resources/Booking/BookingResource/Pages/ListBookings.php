<?php

namespace App\Filament\Resources\Booking\BookingResource\Pages;

use App\Filament\Resources\Booking\BookingResource;
use Filament\Resources\Pages\ListRecords;

class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;
}
