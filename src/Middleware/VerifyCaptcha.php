<?php

namespace Bale\Core\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Lunaweb\RecaptchaV3\Facades\RecaptchaV3;

class VerifyCaptcha
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'g-recaptcha-response' => 'required',
        ]);

        // Verifikasi reCAPTCHA
        $recaptchaScore = RecaptchaV3::verify($request->input('g-recaptcha-response'), 'login');
        if (!$recaptchaScore || $recaptchaScore < 0.5) {
            throw ValidationException::withMessages([
                'g-recaptcha-response' => ['reCAPTCHA verification failed or score too low.'],
            ]);
        }

        return $next($request);
    }
}
