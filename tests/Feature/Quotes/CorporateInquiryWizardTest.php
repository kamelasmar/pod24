<?php

use App\Livewire\CorporateInquiryWizard;
use App\Modules\Quotes\Models\Quote;
use Livewire\Livewire;

it('mounts on step 1 with no event type selected', function () {
    Livewire::test(CorporateInquiryWizard::class)
        ->assertSet('step', 1)
        ->assertSet('eventType', null);
});

it('selectEventType advances to step 2 and stores the type', function () {
    Livewire::test(CorporateInquiryWizard::class)
        ->call('selectEventType', 'conference')
        ->assertSet('step', 2)
        ->assertSet('eventType', 'conference');
});

it('rejects an unknown event type', function () {
    Livewire::test(CorporateInquiryWizard::class)
        ->call('selectEventType', 'invalid_value')
        ->assertSet('step', 1)
        ->assertSet('eventType', null);
});

it('toggleService adds and removes services from the list', function () {
    Livewire::test(CorporateInquiryWizard::class)
        ->call('toggleService', 'episode_editing')
        ->assertSet('serviceInterests', ['episode_editing'])
        ->call('toggleService', 'subtitles_ar')
        ->assertSet('serviceInterests', ['episode_editing', 'subtitles_ar'])
        ->call('toggleService', 'episode_editing')
        ->assertSet('serviceInterests', ['subtitles_ar']);
});

it('rejects unknown service keys silently', function () {
    Livewire::test(CorporateInquiryWizard::class)
        ->call('toggleService', 'something_made_up')
        ->assertSet('serviceInterests', []);
});

it('nextFromScope requires location preference', function () {
    Livewire::test(CorporateInquiryWizard::class)
        ->set('step', 2)
        ->set('eventType', 'conference')
        ->call('nextFromScope')
        ->assertHasErrors(['locationPreference'])
        ->assertSet('step', 2);
});

it('nextFromScope advances to step 3 when location is set', function () {
    Livewire::test(CorporateInquiryWizard::class)
        ->set('step', 2)
        ->set('eventType', 'conference')
        ->set('locationPreference', 'on_location')
        ->call('nextFromScope')
        ->assertSet('step', 3);
});

it('back goes one step earlier', function () {
    Livewire::test(CorporateInquiryWizard::class)
        ->set('step', 3)
        ->call('back')
        ->assertSet('step', 2)
        ->call('back')
        ->assertSet('step', 1);
});

it('submit validates contact + persists a Quote row + advances to success state', function () {
    Livewire::test(CorporateInquiryWizard::class)
        ->set('eventType', 'brand_series')
        ->set('locationPreference', 'studio')
        ->set('attendeesEstimate', '50-200')
        ->set('daysEstimate', '2-3 days')
        ->set('preferredDates', 'mid-October 2026')
        ->set('serviceInterests', ['episode_editing', 'subtitles_ar', 'distribution'])
        ->set('contactName', 'Asma Khaled')
        ->set('contactCompany', 'Acme Media')
        ->set('contactEmail', 'asma@acme.example')
        ->set('contactPhone', '+971 50 000 0000')
        ->set('message', 'Branded series, ten episodes, AR/EN.')
        ->call('submit')
        ->assertSet('step', 4)
        ->assertNotSet('submittedUlid', null);

    expect(Quote::count())->toBe(1);

    $quote = Quote::first();
    expect($quote->type)->toBe('corporate');
    expect($quote->status)->toBe('new');
    expect($quote->event_type)->toBe('brand_series');
    expect($quote->location_preference)->toBe('studio');
    expect($quote->service_interests)->toBe(['episode_editing', 'subtitles_ar', 'distribution']);
    expect($quote->contact_email)->toBe('asma@acme.example');
    expect($quote->ulid)->toMatch('/^[0-9A-HJKMNP-TV-Z]{26}$/');
});

it('submit rejects invalid contact email', function () {
    Livewire::test(CorporateInquiryWizard::class)
        ->set('eventType', 'conference')
        ->set('locationPreference', 'studio')
        ->set('contactName', 'Test User')
        ->set('contactEmail', 'not-an-email')
        ->call('submit')
        ->assertHasErrors(['contactEmail']);

    expect(Quote::count())->toBe(0);
});

it('submit drops bogus service keys before persisting', function () {
    Livewire::test(CorporateInquiryWizard::class)
        ->set('eventType', 'conference')
        ->set('locationPreference', 'on_location')
        ->set('serviceInterests', ['episode_editing', 'haxx0r', 'jingle'])
        ->set('contactName', 'Test')
        ->set('contactEmail', 'a@b.example')
        ->call('submit');

    $quote = Quote::first();
    expect($quote->service_interests)->toBe(['episode_editing', 'jingle']);
});

it('renders the /quote/offsite wizard', function () {
    $this->withoutVite()->get('/quote/offsite')
        ->assertOk()
        ->assertSee('What are you producing?');
});
