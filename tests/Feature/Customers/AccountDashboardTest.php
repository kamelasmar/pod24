<?php

use App\Models\User;
use App\Modules\Booking\Models\Booking;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\ServiceTier;
use App\Modules\Customers\Models\HourPackTransaction;

beforeEach(function () {
    $this->withoutVite();
});

it('redirects unauthenticated visitors to /login', function () {
    $this->get('/account')->assertRedirect('/login');
});

it('shows the customer their bookings and balance', function () {
    $user = User::factory()->create(['name' => '', 'email' => 'guest@example.com']);
    $facility = Facility::factory()->create();
    $tier = ServiceTier::factory()->for($facility)->create();

    Booking::factory()->for($facility)->for($tier, 'serviceTier')->create([
        'customer_id' => $user->id,
        'contact_email' => $user->email,
    ]);

    HourPackTransaction::create([
        'customer_id' => $user->id, 'facility_id' => $facility->id,
        'hours' => 10, 'type' => 'purchase', 'expires_at' => now()->addYear(),
    ]);

    $this->actingAs($user)
        ->get('/account')
        ->assertOk()
        ->assertSee('10 hours')
        ->assertSee($user->email);
});
