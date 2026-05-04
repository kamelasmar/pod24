<?php

namespace App\Providers;

use App\Modules\Booking\Events\BookingConfirmed;
use App\Modules\Booking\Listeners\SendBookingConfirmedEmail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(BookingConfirmed::class, SendBookingConfirmedEmail::class);
    }
}
