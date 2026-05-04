<?php

use App\Models\User;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\ServiceTier;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Admin']);
    $this->admin = User::factory()->create()->assignRole('Admin');
    $this->actingAs($this->admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('shows service tiers attached to a facility', function () {
    $facility = Facility::factory()->create();
    ServiceTier::factory()->count(3)->for($facility)->create();

    $this->get("/admin/catalog/facilities/{$facility->id}/edit")
        ->assertOk()
        ->assertSee($facility->getTranslation('name', 'en'));
});
