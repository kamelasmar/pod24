<?php

use App\Models\User;
use App\Modules\Customers\Mail\MagicLinkMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

it('sends a signed login link to a customer email', function () {
    Mail::fake();
    $user = User::factory()->create(['email' => 'guest@example.com']);

    $this->post('/login/magic-link', ['email' => 'guest@example.com'])
        ->assertRedirect('/login/sent');

    Mail::assertSent(MagicLinkMail::class, fn ($mail) => $mail->hasTo('guest@example.com'));
});

it('creates a user lazily if the email does not exist yet', function () {
    Mail::fake();

    $this->post('/login/magic-link', ['email' => 'newcustomer@example.com'])
        ->assertRedirect('/login/sent');

    expect(User::where('email', 'newcustomer@example.com')->exists())->toBeTrue();
});

it('logs the user in when they click a valid signed link', function () {
    $user = User::factory()->create();
    $url = URL::temporarySignedRoute('login.magic-link.consume', now()->addMinutes(30), ['user' => $user->id]);

    $this->get($url)->assertRedirect('/account');
    $this->assertAuthenticatedAs($user);
});

it('rejects expired links', function () {
    $user = User::factory()->create();
    $url = URL::temporarySignedRoute('login.magic-link.consume', now()->subMinute(), ['user' => $user->id]);

    $this->get($url)->assertStatus(403);
});

it('rejects tampered links', function () {
    $user = User::factory()->create();
    $url = URL::temporarySignedRoute('login.magic-link.consume', now()->addMinutes(30), ['user' => $user->id]);
    // Strip the signature param
    $tampered = preg_replace('/&signature=[^&]+$/', '', $url) . '&signature=fake';

    $this->get($tampered)->assertStatus(403);
});
