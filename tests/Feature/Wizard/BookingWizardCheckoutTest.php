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

    $this->app->bind(CreatePaymentIntent::class, fn () => new CreatePaymentIntent(
        fn ($params) => (object) ['id' => 'pi_test', 'client_secret' => 'pi_test_secret']
    ));
});

it('creates a hold + payment intent on submitContact and lands on step 5 (payment)', function () {
    Livewire::test(BookingWizard::class)
        ->set('step', 4)
        ->set('serviceTierId', $this->tier->id)
        ->set('packageType', 'hourly')
        ->set('date', '2026-06-08')
        ->set('time', '10:00')
        ->set('contactName', 'Test Guest')
        ->set('contactEmail', 'g@example.com')
        ->call('submitContact')
        ->assertSet('step', 5)
        ->assertSet('clientSecret', 'pi_test_secret');

    expect(Booking::count())->toBe(1);

    $booking = Booking::first();
    expect($booking->status->value)->toBe('pending_payment');
    expect($booking->address['building'])->toBe('Yas Creative Hub');
    expect($booking->address['city'])->toBe('Abu Dhabi');
});
