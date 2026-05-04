<?php

use App\Livewire\BookingWizard;
use App\Modules\Booking\Models\Booking;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\ServiceTier;
use App\Modules\Payments\Actions\CreatePaymentIntent;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(\Database\Seeders\Pod24CatalogSeeder::class);
    $this->facility = Facility::where('slug', 'pod24-portable')->first();
    $this->tier = ServiceTier::where('facility_id', $this->facility->id)->first();

    // Replace Stripe with a stub
    $this->app->bind(CreatePaymentIntent::class, fn () => new CreatePaymentIntent(
        fn ($params) => (object) ['id' => 'pi_test', 'client_secret' => 'pi_test_secret']
    ));
});

it('creates a hold + payment intent on submitContact', function () {
    Livewire::test(BookingWizard::class)
        ->set('step', 6)
        ->set('serviceTierId', $this->tier->id)
        ->set('packageType', 'hourly')
        ->set('date', '2026-06-08')
        ->set('time', '10:00')
        ->set('address', ['city' => 'Abu Dhabi', 'country' => 'AE'])
        ->set('contactName', 'Test Guest')
        ->set('contactEmail', 'g@example.com')
        ->call('submitContact')
        ->assertSet('step', 7)
        ->assertSet('clientSecret', 'pi_test_secret');

    expect(Booking::count())->toBe(1);
    expect(Booking::first()->status->value)->toBe('pending_payment');
});
