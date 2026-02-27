<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Socialite\Facades\Socialite;

Route::group(['middleware' => ['web']], function () {

    // Login Route ============================================================================
    Route::post('/logout', function () {
        $token = session()->get('keycloak_id_token');

        // Logout of your app.
        Auth::logout();
        Session::flush(); // Clear the session data
        Session::regenerate(); // Regenerate the session ID to prevent session fixation attacks

        // The URL the user is redirected to after logout.
        $redirectUri = Config::get('app.url');
        $url = Socialite::driver('keycloak')->getLogoutUrl();

        $params = [
            'id_token_hint' => $token, // Ambil id_token dari session
            'post_logout_redirect_uri' => $redirectUri, // URL redirect setelah logout
        ];

        $url .= '?' . http_build_query($params);

        return redirect($url);
    })->name('logout');

    Route::get('/login', function () {
        if (Auth::check()) {
            return redirect('/');
        }

        // Jika belum login di Laravel, redirect ke login.silent (biar Keycloak yang tentukan)
        return redirect()->route('login.silent');
    });

    Route::get('/login/silent', function () {
        return Socialite::driver('keycloak')->redirect();
    })->name('login.silent');

    Route::get('/force-login', function () {
        return Socialite::driver('keycloak')
            ->with(['prompt' => 'login'])
            ->redirect();
    })->name('force.login');

    Route::get('/login/keycloak/callback', function () {

        try {
            $user = Socialite::driver('keycloak')->user();

            // Buat login ke aplikasi Laravel, bisa pakai email / ID dari Keycloak
            $authUser = User::firstOrCreate([
                'nip' => $user->getNickname(),
            ], [
                'uuid' => $user->getId(),
                'name' => $user->getName(),
                'username' => $user->getNickname(),
                'email' => $user->getEmail(),
                'password' => bcrypt(Str::random(16)), // password random
            ]);

            // jika user baru maka set role sebagai guest
            if (!$authUser->getRoleNames()->first()) {
                $authUser->syncRoles('guest');
            }

            Auth::login($authUser, true);

            session(['keycloak_id_token' => $user->accessTokenResponseBody['id_token']]);
            return redirect('/dashboard-selector');
        } catch (\Exception $e) {
            // Silent login gagal (karena user belum login di Keycloak)
            return redirect()->route('force.login'); // misalnya redirect ke login normal
        }
    });
    // End Login Route =======================================================================================

    Route::group(['middleware' => ['recaptcha']], function () {

        $limiter = config('fortify.limiters.login');

        Route::post('/entrance.gate', [AuthenticatedSessionController::class, 'store'])
            ->middleware(array_filter([
                'guest:' . config('fortify.guard'),
                $limiter ? 'throttle:' . $limiter : null,
                'recaptcha',
            ]))->name('login.store');
    });
});