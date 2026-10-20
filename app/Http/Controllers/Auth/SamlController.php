<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Saml\SamlService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SamlController extends Controller
{
    public function metadata(SamlService $saml): Response
    {
        $metadata = $saml->getMetadata();

        return response($metadata, 200, [
            'Content-Type' => 'text/xml; charset=UTF-8',
        ]);
    }

    public function login(Request $request, SamlService $saml): RedirectResponse
    {
        $returnTo = $request->query('returnTo', url('/'));
        $targetUrl = is_string($returnTo) ? $returnTo : url('/');

        $loginUrl = $saml->login($targetUrl);

        if ($loginUrl === null) {
            return redirect()->to($targetUrl);
        }

        return redirect()->away($loginUrl);
    }

    public function acs(Request $request, SamlService $saml): RedirectResponse
    {
        $samlUser = $saml->processAcs();

        /** @var User $user */
        $user = User::query()->firstOrCreate(
            ['email' => $samlUser->getEmail()],
            [
                'name' => $samlUser->getName(),
                'password' => Hash::make(Str::random(32)),
            ]
        );

        if ($user->name !== $samlUser->getName() && $samlUser->getName() !== '') {
            $user->name = $samlUser->getName();
            $user->save();
        }

        Auth::login($user);

        $relayState = $request->input('RelayState');
        $targetUrl = is_string($relayState) && $relayState !== '' ? $relayState : '/';

        return redirect()->intended($targetUrl);
    }

    public function sls(Request $request, SamlService $saml): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $redirectUrl = $saml->processSls() ?: url('/');

        return redirect()->away($redirectUrl);
    }
}
