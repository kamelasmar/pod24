<?php

use App\Filament\Resources\Catalog\FacilityResource;
use App\Models\User;
use App\Modules\Catalog\Models\Facility;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Admin']);
    $this->admin = User::factory()->create();
    $this->admin->assignRole('Admin');
    $this->actingAs($this->admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('lists facilities in the admin panel', function () {
    Facility::factory()->count(3)->create();

    $this->get(FacilityResource::getUrl('index'))
        ->assertOk();
});

it('can create a facility via Filament', function () {
    \Livewire\Livewire::test(\App\Filament\Resources\Catalog\FacilityResource\Pages\CreateFacility::class)
        ->fillForm([
            'slug' => 'pod24-portable',
            'name' => ['en' => 'Pod24 Portable'],
            'description' => ['en' => 'A portable pod.'],
            'is_active' => true,
            'sort_order' => 0,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('facilities', ['slug' => 'pod24-portable']);
});
