<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Modules\Customers\Mail\MagicLinkMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class MagicLinkController
{
    public function request(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $user = User::firstOrCreate(
            ['email' => $request->email],
            ['name' => '', 'password' => '', 'email_verified_at' => null],
        );

        $url = URL::temporarySignedRoute(
            'login.magic-link.consume',
            now()->addMinutes(30),
            ['user' => $user->id],
        );

        Mail::to($user->email)->send(new MagicLinkMail($url));

        return redirect()->route('login.magic-link.sent');
    }

    public function consume(Request $request, User $user)
    {
        if (! $request->hasValidSignature()) {
            abort(403);
        }

        auth()->login($user);
        $user->forceFill(['email_verified_at' => $user->email_verified_at ?? now()])->save();

        return redirect('/account');
    }
}
