<?php

use App\Models\User;
use App\Modules\Booking\Models\Booking;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Admin']);
    $this->admin = User::factory()->create()->assignRole('Admin');
    $this->actingAs($this->admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('renders the booking admin inbox', function () {
    Booking::factory()->count(3)->create();

    $this->get('/admin/booking/bookings')->assertOk();
});
