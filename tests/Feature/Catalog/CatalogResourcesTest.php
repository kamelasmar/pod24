<?php

use App\Models\User;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Admin']);
    $this->admin = User::factory()->create()->assignRole('Admin');
    $this->actingAs($this->admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    // Seed real catalog data so the index pages exercise their column renderers.
    // Empty index pages can hide closure-evaluation bugs in formatStateUsing.
    $this->seed(\Database\Seeders\Pod24CatalogSeeder::class);
});

it('lists addons (with data)', function () {
    $this->get('/admin/catalog/addons')->assertOk();
});

it('lists hour packs (with data)', function () {
    $this->get('/admin/catalog/hour-packs')->assertOk();
});

it('lists pricing modifiers (with data)', function () {
    $this->get('/admin/catalog/pricing-modifiers')->assertOk();
});

it('lists cancellation policies (with data)', function () {
    $this->get('/admin/catalog/cancellation-policies')->assertOk();
});

it('lists facilities (with data)', function () {
    $this->get('/admin/catalog/facilities')->assertOk();
});
