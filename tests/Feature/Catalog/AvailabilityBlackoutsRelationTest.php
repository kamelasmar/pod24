<?php

use App\Models\User;
use App\Modules\Availability\Models\AvailabilityBlackout;
use App\Modules\Catalog\Models\Facility;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Admin']);
    $this->admin = User::factory()->create()->assignRole('Admin');
    $this->actingAs($this->admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('shows availability blackouts attached to a facility', function () {
    $facility = Facility::factory()->create();
    AvailabilityBlackout::factory()->for($facility)->create();

    $this->get("/admin/catalog/facilities/{$facility->id}/edit")->assertOk();
});
