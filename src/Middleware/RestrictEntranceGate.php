<?php

namespace Bale\Core\Middleware;

use Bale\Core\Services\EntranceGateService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Batasi halaman form login (`entrance.gate`) hanya untuk IP yang diizinkan.
 *
 * IP di luar allowlist menerima 404 (bukan 403) agar keberadaan halaman
 * login tidak terbocorkan.
 */
class RestrictEntranceGate
{
    public function __construct(protected EntranceGateService $service) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->service->isAllowed($request->ip())) {
            abort(404);
        }

        return $next($request);
    }
}
