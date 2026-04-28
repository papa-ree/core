<?php

namespace Bale\Core\Middleware;

use Bale\Core\Services\KeycloakService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class CheckKeycloakSession
{
    protected $keycloakService;

    public function __construct(KeycloakService $keycloakService)
    {
        $this->keycloakService = $keycloakService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // 1. Jika User SUDAH Login: Cek apakah session di Keycloak masih aktif
        if (Auth::check() && session()->has('keycloak_access_token')) {
            // Jika sso_session_sync dimatikan, lewati pengecekan status login
            if (!config('core.sso_session_sync', true)) {
                return $next($request);
            }

            $status = $this->checkKeycloakSession();

            if (isset($status['active']) && !$status['active']) {
                return $this->logoutUser();
            }
        } 
        
        // 2. Jika User BELUM Login: Coba Auto-Login (Silent SSO Check)
        // Hanya dilakukan sekali per session (sso_checked) untuk menghindari loop
        elseif (
            !Auth::check() && 
            !session()->has('sso_checked') && 
            $request->isMethod('get') && 
            !$request->ajax() &&
            !$request->is('login*', 'logout*', 'entrance.gate*', 'login/keycloak/callback*')
        ) {
            // Simpan URL asal agar bisa kembali setelah auto-login
            session(['sso_redirect_back' => $request->fullUrl()]);
            
            return redirect()->route('login.sso-check');
        }

        return $next($request);
    }

    /**
     * Logout user and clear session.
     */
    protected function logoutUser()
    {
        Auth::logout();
        Session::flush();
        Session::regenerate();

        return redirect()->route('login');
    }

    /**
     * Cek status sesi di Keycloak.
     */
    public function checkKeycloakSession()
    {
        $accessToken = Session::get('keycloak_access_token');

        // Backward compat: kalau session lama belum punya access_token,
        // anggap session expired supaya user re-login.
        if (!$accessToken) {
            $this->logoutUser();
            return ['active' => false];
        }

        // Cache hasil 30 detik per session ID supaya navigasi tidak hit Keycloak berulang.
        $cacheKey = 'kc:session:' . Session::getId();
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // Refresh access_token kalau sudah expired sebelum introspect.
        $expiresAt = Session::get('keycloak_token_expires_at');
        if ($expiresAt && now()->timestamp >= $expiresAt) {
            $refreshed = $this->refreshKeycloakToken();
            if (!$refreshed) {
                $this->logoutUser();
                $result = ['active' => false];
                Cache::put($cacheKey, $result, 30);
                return $result;
            }
            $accessToken = Session::get('keycloak_access_token');
        }

        try {
            $res = $this->keycloakService->checkLoginStatus($accessToken);
            Cache::put($cacheKey, $res, 30);
            return $res;
        } catch (\Exception $e) {
            // Jangan langsung logout kalau Keycloak unreachable — bisa false positive
            // saat jaringan flaky. Logout hanya saat introspect tegas return active=false.
            Log::warning('Keycloak introspect gagal: ' . $e->getMessage());
            return ['active' => true, 'error' => $e->getMessage()];
        }
    }

    /**
     * Refresh access_token pakai refresh_token yang tersimpan.
     * Return true kalau sukses, false kalau refresh_token juga invalid.
     */
    protected function refreshKeycloakToken(): bool
    {
        $refreshToken = Session::get('keycloak_refresh_token');
        if (!$refreshToken) {
            return false;
        }

        $baseUrl = env('KEYCLOAK_BASE_URL');
        $realm = env('KEYCLOAK_REALM');

        try {
            $response = Http::asForm()->post(
                "{$baseUrl}/realms/{$realm}/protocol/openid-connect/token",
                [
                    'grant_type' => 'refresh_token',
                    'client_id' => env('KEYCLOAK_CLIENT_ID'),
                    'client_secret' => env('KEYCLOAK_CLIENT_SECRET'),
                    'refresh_token' => $refreshToken,
                ]
            );

            if ($response->failed()) {
                Log::info('Keycloak refresh token gagal: ' . $response->body());
                return false;
            }

            $data = $response->json();
            Session::put('keycloak_access_token', $data['access_token']);
            Session::put('keycloak_refresh_token', $data['refresh_token'] ?? $refreshToken);
            Session::put('keycloak_token_expires_at', now()->addSeconds($data['expires_in'] ?? 300)->timestamp);

            Cache::forget('kc:session:' . Session::getId());

            return true;
        } catch (\Exception $e) {
            Log::warning('Keycloak refresh token exception: ' . $e->getMessage());
            return false;
        }
    }
}
