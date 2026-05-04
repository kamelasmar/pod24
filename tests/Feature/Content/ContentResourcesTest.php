<?php

use App\Models\User;
use App\Modules\Content\Models\FaqItem;
use App\Modules\Content\Models\Testimonial;
use App\Modules\Content\Models\UseCase;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'Admin']);
    $this->admin = User::factory()->create()->assignRole('Admin');
    $this->actingAs($this->admin);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('lists FAQ items', function () {
    FaqItem::factory()->count(3)->create();

    $this->get('/admin/content/faq-items')->assertOk();
});

it('lists testimonials', function () {
    Testimonial::factory()->count(3)->create();

    $this->get('/admin/content/testimonials')->assertOk();
});

it('lists use cases', function () {
    UseCase::factory()->count(3)->create();

    $this->get('/admin/content/use-cases')->assertOk();
});
