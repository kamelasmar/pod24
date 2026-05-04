<?php

use App\Models\User;
use App\Modules\Availability\Models\AvailabilityRule;
use App\Modules\Catalog\Models\Facility;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Admin']);
    $this->admin = User::factory()->create()->assignRole('Admin');
    $this->actingAs($this->admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('shows availability rules attached to a facility', function () {
    $facility = Facility::factory()->create();
    AvailabilityRule::factory()->for($facility)->create(['day_of_week' => 1]);
    AvailabilityRule::factory()->for($facility)->create(['day_of_week' => 2]);

    $this->get("/admin/catalog/facilities/{$facility->id}/edit")->assertOk();
});
