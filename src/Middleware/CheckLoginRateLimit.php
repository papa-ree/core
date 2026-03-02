<?php

namespace Bale\Core\Middleware;

use Bale\Core\Services\LoginSecurityService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckLoginRateLimit
{
    public function __construct(protected LoginSecurityService $security)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $username = $request->input('username', '');
        $ip = $request->ip();

        // Priority 1: Check IP block
        $ipCheck = $this->security->checkIpBlock($ip);
        if ($ipCheck['blocked']) {
            return redirect()->route('login.lockout')
                ->with('lockout_seconds', $ipCheck['seconds_remaining'])
                ->with('lockout_type', 'ip_block')
                ->with('lockout_reason', $ipCheck['reason']);
        }

        if (!$username) {
            return $next($request);
        }

        // Priority 2: Check account lock
        $accountCheck = $this->security->checkAccountLock($username);
        if ($accountCheck['locked']) {
            return redirect()->route('login.lockout')
                ->with('lockout_seconds', $accountCheck['seconds_remaining'])
                ->with('lockout_type', 'account_lock')
                ->with('lockout_reason', 'Akun Anda dikunci sementara karena terlalu banyak percobaan gagal.');
        }

        // Priority 3: Check rate limiter (attempt count >= 3 means in-lock window)
        $attempts = $this->security->getAttemptCount($username, $ip);
        if ($attempts >= 3) {
            $lockSeconds = $this->getLockSecondsFromCache($username, $ip);
            if ($lockSeconds > 0) {
                return redirect()->route('login.lockout')
                    ->with('lockout_seconds', $lockSeconds)
                    ->with('lockout_type', 'rate_limit')
                    ->with('lockout_reason', 'Terlalu banyak percobaan gagal. Silakan coba beberapa saat lagi.');
            }
        }

        return $next($request);
    }

    protected function getLockSecondsFromCache(string $username, string $ip): int
    {
        $lockKey = 'login_attempt:lock:' . md5($username . ':' . $ip);
        return (int) \Illuminate\Support\Facades\Cache::get($lockKey, 0);
    }
}
