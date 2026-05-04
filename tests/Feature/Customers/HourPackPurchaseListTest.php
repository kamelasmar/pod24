<?php

use App\Models\User;
use App\Modules\Catalog\Models\Facility;
use App\Modules\Catalog\Models\HourPack;

beforeEach(function () {
    $this->withoutVite();
});

it('shows active packs to a logged-in user', function () {
    $user = User::factory()->create();
    $facility = Facility::factory()->create();
    HourPack::factory()->for($facility)->create([
        'name' => ['en' => '10-Hour Pack'],
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->get('/account/packs')
        ->assertOk()
        ->assertSee('10-Hour Pack');
});
