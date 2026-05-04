<?php

use App\Modules\Catalog\Models\Facility;
use App\Modules\Content\Models\FaqItem;
use App\Modules\Content\Models\Testimonial;
use App\Modules\Content\Models\UseCase;
use App\Modules\Payments\Webhooks\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('pod24.home', [
        'pod24Facility' => Facility::where('slug', 'pod24-portable')->firstOrFail(),
        'faqItems' => FaqItem::where('is_published', true)->orderBy('sort_order')->get(),
        'testimonials' => Testimonial::where('is_published', true)->get(),
        'useCases' => UseCase::where('is_published', true)->orderBy('sort_order')->get(),
    ]);
})->name('home');

Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle'])
    ->name('stripe.webhook');
