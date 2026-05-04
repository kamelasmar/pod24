<?php

use App\Models\User;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Admin']);
    $this->admin = User::factory()->create()->assignRole('Admin');
    $this->actingAs($this->admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('lists addons', function () {
    $this->get('/admin/catalog/addons')->assertOk();
});

it('lists hour packs', function () {
    $this->get('/admin/catalog/hour-packs')->assertOk();
});

it('lists pricing modifiers', function () {
    $this->get('/admin/catalog/pricing-modifiers')->assertOk();
});

it('lists cancellation policies', function () {
    $this->get('/admin/catalog/cancellation-policies')->assertOk();
});
