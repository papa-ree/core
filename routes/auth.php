<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

Route::group(['middleware' => ['web']], function () {

    // Logout lokal saja: sesi Keycloak dibiarkan, tidak ada sinkronisasi.
    Route::post('/logout', function () {
        Auth::logout();
        Session::flush();
        Session::regenerate();

        return redirect('/');
    })->name('logout');

    // Login utama = SSO Keycloak. Halaman error hanya muncul saat callback gagal.
    Route::get('/login', function () {
        if (Auth::check()) {
            return redirect('/');
        }

        if (session()->has('sso_error')) {
            return view('core::auth.sso-error');
        }

        return Socialite::driver('keycloak')->redirect();
    })->name('login');

    // Menonaktifkan sisa route POST /login milik Fortify.
    Route::post('/login', fn () => redirect()->route('login'));

    Route::get('/force-login', function () {
        return Socialite::driver('keycloak')
            ->with(['prompt' => 'login'])
            ->redirect();
    })->name('force.login');

    Route::get('/login/keycloak/callback', function () {
        try {
            $user = Socialite::driver('keycloak')->user();

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
            if (! $authUser->getRoleNames()->first()) {
                $authUser->syncRoles('guest');
            }

            // Nomor HP dari Keycloak. Gagal simpan profil tidak boleh menggagalkan login.
            try {
                $profile = $authUser->profile()->firstOrNew();
                if (($phone = $user->user['phone'] ?? null) !== null) {
                    $profile->phone = $phone;
                }
                $profile->save();
            } catch (Throwable $e) {
                Log::error('SSO profile_sync gagal: '.$e->getMessage());
            }

            Auth::login($authUser, true);

            $tokenResponse = $user->accessTokenResponseBody;

            session([
                'keycloak_id_token' => $tokenResponse['id_token'] ?? null,
                'keycloak_access_token' => $tokenResponse['access_token'] ?? null,
                'keycloak_refresh_token' => $tokenResponse['refresh_token'] ?? null,
                'keycloak_token_expires_at' => now()->addSeconds($tokenResponse['expires_in'] ?? 300)->timestamp,
            ]);

            $redirectTo = session()->pull('sso_redirect_back', route('dashboard'));

            return redirect()->to($redirectTo);
        } catch (InvalidStateException $e) {
            // User membatalkan / me-refresh halaman: cukup catat ke file, jangan ke Slack.
            Log::channel('single')->warning('SSO invalid_state: '.$e->getMessage());

            return redirect()->route('login')->with('sso_error', true);
        } catch (Throwable $e) {
            Log::error('SSO callback gagal: '.$e->getMessage());

            return redirect()->route('login')->with('sso_error', true);
        }
    });

    // Halaman login lokal (GET) — hanya bisa diakses dari IP allowlist.
    Route::get('/entrance.gate', function () {
        if (auth()->check()) {
            return redirect('/');
        }

        return view('core::auth.login');
    })->middleware('entrance_gate')->name('entrance.gate');

    // Halaman lockout — ditampilkan saat user ter-blokir
    // Hanya bisa diakses jika ada data lockout di session (di-set oleh middleware CheckLoginRateLimit)
    Route::get('/entrance.gate/blocked', function () {
        if (! session()->has('lockout_seconds')) {
            return redirect()->route('entrance.gate');
        }

        return view('core::auth.lockout');
    })->middleware('entrance_gate')->name('login.lockout');

    // POST /entrance.gate — form submit dengan recaptcha & rate limit
    Route::post('/entrance.gate', [AuthenticatedSessionController::class, 'store'])
        ->middleware(array_filter([
            'entrance_gate',
            'guest:'.config('fortify.guard'),
            config('fortify.limiters.login') ? 'throttle:'.config('fortify.limiters.login') : null,
            'check_login_rate_limit',
            'recaptcha',
        ]))->name('entrance.gate.store');
});
