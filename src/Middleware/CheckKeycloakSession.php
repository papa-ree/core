<?php

namespace Bale\Core\Middleware;

use Bale\Core\Services\KeycloakService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

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
            $token = session()->get('keycloak_access_token');

            try {
                $status = $this->keycloakService->checkLoginStatus($token);

                if (!isset($status['active']) || !$status['active']) {
                    return $this->logoutUser();
                }
            } catch (\Exception $e) {
                if (str_contains($e->getMessage(), '401') || str_contains($e->getMessage(), '403')) {
                    return $this->logoutUser();
                }
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
}
