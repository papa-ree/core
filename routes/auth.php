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
            // Menggunakan 'username' karena 'nip' tidak ada di database
            $authUser = User::firstOrCreate([
                'username' => $user->getNickname(),
            ], [
                'uuid' => $user->getId(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'password' => bcrypt(Str::random(16)), // password random
            ]);

            // jika user baru maka set role sebagai guest
            if (!$authUser->getRoleNames()->first()) {
                $authUser->syncRoles('guest');
            }

            Auth::login($authUser, true);

            session([
                'keycloak_id_token' => $user->accessTokenResponseBody['id_token'],
                'keycloak_access_token' => $user->token,
            ]);
            return redirect()->route('dashboard');
        } catch (\Exception $e) {
            // Log error untuk debug, bantu identifikasi jika error bukan sekedar silent login gagal
            \Log::error('Keycloak Login Error: ' . $e->getMessage(), [
                'exception' => $e
            ]);

            // Cek apakah ini silent login failure (sering terjadi saat InvalidStateException atau user batal)
            // Jika sudah ada prompt=login dan masih error, kemungkinan besar error sistem bukan sekedar sesi
            if (request()->has('prompt') || request()->has('error')) {
                 return redirect()->route('login')->with('error', 'Authentication failed: ' . $e->getMessage());
            }

            // Silent login gagal (karena user belum login di Keycloak)
            return redirect()->route('force.login'); // misalnya redirect ke login normal
        }
    });
    // End Login Route =======================================================================================

    // Halaman login (GET) — tidak perlu recaptcha
    Route::get('/entrance.gate', function () {
        if (auth()->check()) {
            return redirect('/');
        }
        return view('core::auth.login');
    })->name('login');

    // Halaman lockout — ditampilkan saat user ter-blokir
    // Hanya bisa diakses jika ada data lockout di session (di-set oleh middleware CheckLoginRateLimit)
    Route::get('/entrance.gate/blocked', function () {
        if (!session()->has('lockout_seconds')) {
            return redirect()->route('login');
        }
        return view('core::auth.lockout');
    })->name('login.lockout');

    // POST /entrance.gate — form submit dengan recaptcha & rate limit
    Route::post('/entrance.gate', [AuthenticatedSessionController::class, 'store'])
        ->middleware(array_filter([
            'guest:' . config('fortify.guard'),
            config('fortify.limiters.login') ? 'throttle:' . config('fortify.limiters.login') : null,
            'check_login_rate_limit',
            'recaptcha',
        ]))->name('login.store');
});