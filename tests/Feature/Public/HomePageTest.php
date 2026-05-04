<?php

beforeEach(function () {
    $this->withoutVite();
    $this->seed(\Database\Seeders\Pod24ContentSeeder::class);
    $this->seed(\Database\Seeders\Pod24CatalogSeeder::class);
});

it('renders the home page with HTTP 200', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Pod24');
});

it('shows the cyan accent in the rendered output', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('pod-accent', false);
});
