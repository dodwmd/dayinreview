<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class GoogleController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     */
    public function redirectToGoogle(): RedirectResponse|SymfonyRedirectResponse
    {
        // Scope configuration needs to be updated in config/services.php
        return Socialite::driver('google')
            ->redirect();
    }

    /**
     * Obtain the user information from Google.
     */
    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            $token = $googleUser->accessTokenResponseBody['access_token'] ?? null;

            // If user is authenticated, link their account with YouTube
            if (Auth::check()) {
                /** @var \App\Models\User $user */
                $user = Auth::user();
                $user->youtube_token = $token;
                $user->save();

                return redirect()->route('profile.edit')
                    ->with('status', 'youtube-connected');
            }

            // Find existing user or create a new one
            /** @var \App\Models\User|null $user */
            $user = User::query()->where('email', $googleUser->getEmail())->first();

            if (! $user) {
                /** @var \App\Models\User $user */
                $user = new User;
                $user->name = $googleUser->getName();
                $user->email = $googleUser->getEmail();
                $user->password = bcrypt(Str::random(16));
                $user->youtube_token = $token;
                $user->save();
            } else {
                $user->youtube_token = $token;
                $user->save();
            }

            Auth::login($user);

            return redirect()->route('dashboard');
        } catch (\Exception $e) {
            return redirect()->route('login')
                ->withErrors(['google' => 'Google authentication failed: '.$e->getMessage()]);
        }
    }

    /**
     * Disconnect YouTube account from user profile.
     */
    public function disconnectYouTube(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user) {
            $user->youtube_token = null;
            $user->save();
        }

        return redirect()->route('profile.edit')
            ->with('status', 'youtube-disconnected');
    }
}
