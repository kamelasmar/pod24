<?php

use App\Modules\Payments\Webhooks\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle'])
    ->name('stripe.webhook');
