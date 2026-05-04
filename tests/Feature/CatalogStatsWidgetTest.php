<?php

use App\Filament\Widgets\CatalogStatsWidget;
use App\Models\User;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Admin']);
    $this->admin = User::factory()->create()->assignRole('Admin');
    $this->actingAs($this->admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('renders the catalog stats widget', function () {
    $this->seed(\Database\Seeders\Pod24CatalogSeeder::class);
    \Livewire\Livewire::test(CatalogStatsWidget::class)
        ->assertSeeText('Active facilities')
        ->assertSeeText('Service tiers');
});
