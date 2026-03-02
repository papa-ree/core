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
        if (Auth::check() && session()->has('keycloak_access_token')) {
            $token = session()->get('keycloak_access_token');

            try {
                $status = $this->keycloakService->checkLoginStatus($token);

                if (!isset($status['active']) || !$status['active']) {
                    return $this->logoutUser();
                }
            } catch (\Exception $e) {
                // If there's an error reaching Keycloak, we might want to log it but let the user continue,
                // or logout for security. In this case, we logout to be safe if Keycloak confirms session invalidity.
                if (str_contains($e->getMessage(), '401') || str_contains($e->getMessage(), '403')) {
                    return $this->logoutUser();
                }
            }
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
