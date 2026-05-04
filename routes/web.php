<?php

use App\Http\Controllers\Auth\MagicLinkController;
use App\Livewire\AccountDashboard;
use App\Livewire\BookingConfirmed;
use App\Livewire\BookingWizard;
use App\Livewire\CorporateInquiryWizard;
use App\Livewire\HourPackPurchaseList;
use App\Livewire\MagicLinkRequest;
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

Route::get('/book', BookingWizard::class)->name('book');
Route::get('/book/confirmed', BookingConfirmed::class)->name('book.confirmed');
Route::get('/quote/offsite', CorporateInquiryWizard::class)->name('quote.offsite');

Route::get('/login', MagicLinkRequest::class)->name('login');
Route::post('/login/magic-link', [MagicLinkController::class, 'request'])->name('login.magic-link.request');
Route::get('/login/sent', fn () => view('auth.magic-link-sent'))->name('login.magic-link.sent');
Route::get('/login/m/{user}', [MagicLinkController::class, 'consume'])
    ->middleware('signed')
    ->name('login.magic-link.consume');
Route::post('/logout', function () { auth()->logout(); return redirect('/'); })->name('logout');

Route::get('/account', AccountDashboard::class)->middleware('auth')->name('account.dashboard');
Route::get('/account/packs', HourPackPurchaseList::class)->middleware('auth')->name('account.packs');

Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle'])
    ->name('stripe.webhook');
