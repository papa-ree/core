<?php

namespace Bale\Core\Services;

use Bale\Core\Models\FailedLoginAttempt;
use Bale\Core\Models\IpBlockList;
use Bale\Core\Models\SecurityEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LoginSecurityService
{
    /**
     * Progressive lock policy: attempt => [action, seconds]
     */
    protected array $policy = [
        1 => ['action' => 'delay', 'seconds' => 1],
        2 => ['action' => 'delay', 'seconds' => 3],
        3 => ['action' => 'temporary_lock', 'seconds' => 90],
        4 => ['action' => 'extended_lock', 'seconds' => 86400],
    ];

    /**
     * Rate limiter cache key prefix
     */
    protected string $cachePrefix = 'login_attempt:';

    // =========================================================================
    //  PRE-AUTH CHECKS
    // =========================================================================

    /**
     * [Priority 1] Check if IP is temporarily blocked.
     *
     * @return array{blocked: bool, seconds_remaining: int, reason: string}
     */
    public function checkIpBlock(string $ip): array
    {
        $record = IpBlockList::where('ip_address', $ip)
            ->where('blocked_until', '>', now())
            ->first();

        if (!$record) {
            return ['blocked' => false, 'seconds_remaining' => 0, 'reason' => ''];
        }

        return [
            'blocked' => true,
            'seconds_remaining' => (int) now()->diffInSeconds(Carbon::parse($record->blocked_until)),
            'reason' => $record->reason ?? 'Alamat IP Anda sementara diblokir.',
        ];
    }

    /**
     * [Priority 2] Check if user account is locked.
     *
     * @return array{locked: bool, seconds_remaining: int}
     */
    public function checkAccountLock(string $username): array
    {
        $user = DB::table('users')
            ->where('username', $username)
            ->whereNotNull('locked_until')
            ->where('locked_until', '>', now())
            ->select('locked_until')
            ->first();

        if (!$user) {
            return ['locked' => false, 'seconds_remaining' => 0];
        }

        return [
            'locked' => true,
            'seconds_remaining' => (int) abs(now()->diffInSeconds($user->locked_until, false)),
        ];
    }

    /**
     * [Priority 3] Get current attempt count for a username+IP combination.
     */
    public function getAttemptCount(string $username, string $ip): int
    {
        $key = $this->buildCacheKey($username, $ip);
        return (int) Cache::get($key, 0);
    }

    /**
     * Get full lockout info for the login view (combined check).
     *
     * @return array{locked: bool, seconds_remaining: int, reason: string, type: string}
     */
    public function getLockoutInfo(string $username, string $ip): array
    {
        // 1. IP block
        $ipCheck = $this->checkIpBlock($ip);
        if ($ipCheck['blocked']) {
            return [
                'locked' => true,
                'seconds_remaining' => $ipCheck['seconds_remaining'],
                'reason' => $ipCheck['reason'],
                'type' => 'ip_block',
            ];
        }

        // 2. Account lock
        $accountCheck = $this->checkAccountLock($username);
        if ($accountCheck['locked']) {
            return [
                'locked' => true,
                'seconds_remaining' => $accountCheck['seconds_remaining'],
                'reason' => 'Akun Anda dikunci sementara karena terlalu banyak percobaan gagal.',
                'type' => 'account_lock',
            ];
        }

        // 3. Rate limiter (check if currently in a lock window)
        $attempts = $this->getAttemptCount($username, $ip);
        if ($attempts >= 3) {
            $lockSeconds = $this->getLockSecondsRemaining($username, $ip);
            if ($lockSeconds > 0) {
                return [
                    'locked' => true,
                    'seconds_remaining' => $lockSeconds,
                    'reason' => 'Terlalu banyak percobaan gagal. Silakan coba beberapa saat lagi.',
                    'type' => 'rate_limit',
                ];
            }
        }

        return ['locked' => false, 'seconds_remaining' => 0, 'reason' => '', 'type' => ''];
    }

    // =========================================================================
    //  ON SUCCESS
    // =========================================================================

    /**
     * Called after successful authentication.
     * Clear all rate limiter state for this username+IP.
     */
    public function onLoginSuccess(string $username, string $ip): void
    {
        Cache::forget($this->buildCacheKey($username, $ip));
        Cache::forget($this->buildLockKey($username, $ip));

        // Clear account lock
        DB::table('users')
            ->where('username', $username)
            ->whereNotNull('locked_until')
            ->update(['locked_until' => null]);
    }

    // =========================================================================
    //  ON FAILURE
    // =========================================================================

    /**
     * Called after a failed authentication attempt.
     * Increments counter, logs attempt, evaluates distributed attack, applies lock.
     * Returns the current attempt count (after increment).
     */
    public function onLoginFailure(string $username, string $ip, ?string $tenantId, Request $request): int
    {
        // 1. Log the failed attempt
        $this->logFailedAttempt($username, $ip, $tenantId, $request);

        // 2. Increment attempt counter
        $attempts = $this->incrementAttemptCount($username, $ip);

        // 3. Evaluate distributed attack patterns
        $this->detectDistributedAttack($username, $ip, $tenantId);

        // 4. Apply progressive delay or lock
        $this->applyProgressivePolicy($username, $ip, $attempts);

        return $attempts;
    }

    // =========================================================================
    //  PROGRESSIVE POLICY
    // =========================================================================

    protected function incrementAttemptCount(string $username, string $ip): int
    {
        $key = $this->buildCacheKey($username, $ip);
        $attempts = (int) Cache::get($key, 0) + 1;

        // Store for 24 hours (max lock duration)
        Cache::put($key, $attempts, now()->addDay());

        return $attempts;
    }

    protected function applyProgressivePolicy(string $username, string $ip, int $attempts): void
    {
        // Clamp to max policy key
        $policyKey = min($attempts, max(array_keys($this->policy)));
        $rule = $this->policy[$policyKey];

        if ($rule['action'] === 'delay') {
            // Delays are handled server-side (sleep) or simply noted
            sleep($rule['seconds']);
        }

        if ($rule['action'] === 'temporary_lock') {
            $seconds = $rule['seconds'];
            $lockKey = $this->buildLockKey($username, $ip);
            $expiryAt = now()->addSeconds($seconds);
            Cache::put($lockKey . ':expires_at', $expiryAt->toIso8601String(), $expiryAt);

            // Also lock the account in DB
            DB::table('users')
                ->where('username', $username)
                ->update(['locked_until' => $expiryAt]);
        }

        if ($rule['action'] === 'extended_lock') {
            $seconds = $rule['seconds'];
            $lockKey = $this->buildLockKey($username, $ip);
            $expiryAt = now()->addSeconds($seconds);
            Cache::put($lockKey . ':expires_at', $expiryAt->toIso8601String(), $expiryAt);

            DB::table('users')
                ->where('username', $username)
                ->update(['locked_until' => $expiryAt]);
        }
    }

    // =========================================================================
    //  DISTRIBUTED ATTACK DETECTION
    // =========================================================================

    /**
     * Detect distributed attack patterns and apply countermeasures.
     */
    public function detectDistributedAttack(string $username, string $ip, ?string $tenantId): void
    {
        $window = now()->subSeconds(60);

        // Scenario A: One IP targeting many usernames (credential stuffing)
        $uniqueUsernamesFromIp = FailedLoginAttempt::where('ip_address', $ip)
            ->where('attempted_at', '>=', $window)
            ->distinct('username')
            ->count('username');

        if ($uniqueUsernamesFromIp >= 20) {
            $this->blockIp($ip, 900, 'Serangan credential stuffing terdeteksi dari IP ini.');
            $this->logSecurityEvent('CREDENTIAL_STUFFING_DETECTED', 'HIGH', $ip, $username, $tenantId, [
                'unique_usernames' => $uniqueUsernamesFromIp,
                'window_seconds' => 60,
            ]);
        }

        // Scenario B: One username targeted from many IPs (account enumeration)
        $uniqueIpsForUsername = FailedLoginAttempt::where('username', $username)
            ->where('attempted_at', '>=', $window)
            ->distinct('ip_address')
            ->count('ip_address');

        if ($uniqueIpsForUsername >= 5) {
            DB::table('users')
                ->where('username', $username)
                ->update(['locked_until' => now()->addSeconds(300)]);

            $this->logSecurityEvent('DISTRIBUTED_ACCOUNT_ATTACK', 'CRITICAL', $ip, $username, $tenantId, [
                'unique_ips' => $uniqueIpsForUsername,
                'window_seconds' => 60,
            ]);
        }

        // Scenario C: Repeated offender IP (3+ blocks in 24h)
        $blocksIn24h = IpBlockList::where('ip_address', $ip)
            ->where('updated_at', '>=', now()->subDay())
            ->value('block_count_24h');

        if ($blocksIn24h !== null && $blocksIn24h >= 3) {
            $this->blockIp($ip, 86400, 'IP diblokir 24 jam karena pola berulang.');
            $this->logSecurityEvent('REPEATED_OFFENDER_IP', 'CRITICAL', $ip, $username, $tenantId, [
                'blocks_in_24h' => $blocksIn24h,
            ]);
        }
    }

    // =========================================================================
    //  HELPERS
    // =========================================================================

    protected function blockIp(string $ip, int $seconds, string $reason): void
    {
        $blockedUntil = now()->addSeconds($seconds);
        $exists = DB::table('ip_block_list')->where('ip_address', $ip)->exists();

        if ($exists) {
            DB::table('ip_block_list')
                ->where('ip_address', $ip)
                ->update([
                    'reason' => $reason,
                    'blocked_until' => $blockedUntil,
                    'block_count_24h' => DB::raw('block_count_24h + 1'),
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('ip_block_list')->insert([
                'ip_address' => $ip,
                'reason' => $reason,
                'blocked_until' => $blockedUntil,
                'block_count_24h' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    protected function logFailedAttempt(string $username, string $ip, ?string $tenantId, Request $request): void
    {
        FailedLoginAttempt::create([
            'tenant_id' => $tenantId,
            'username' => $username,
            'ip_address' => $ip,
            'user_agent' => $request->userAgent(),
            'attempted_at' => now(),
        ]);
    }

    protected function logSecurityEvent(
        string $eventType,
        string $severity,
        string $ip,
        string $username,
        ?string $tenantId,
        array $payload = []
    ): void {
        SecurityEvent::create([
            'event_type' => $eventType,
            'severity' => $severity,
            'ip_address' => $ip,
            'username' => $username,
            'tenant_id' => $tenantId,
            'payload' => $payload,
            'created_at' => now(),
        ]);
    }

    /**
     * Remaining lock seconds for username+IP from cache.
     */
    public function getLockSecondsRemaining(string $username, string $ip): int
    {
        $lockKey = $this->buildLockKey($username, $ip);
        $expireKey = $lockKey . ':expires_at';

        $expiresAt = Cache::get($expireKey);

        if ($expiresAt) {
            $diff = (int) now()->diffInSeconds(Carbon::parse($expiresAt));
            return max(0, $diff);
        }

        return 0;
    }

    protected function buildCacheKey(string $username, string $ip): string
    {
        return $this->cachePrefix . md5($username . ':' . $ip);
    }

    protected function buildLockKey(string $username, string $ip): string
    {
        return $this->cachePrefix . 'lock:' . md5($username . ':' . $ip);
    }
}
