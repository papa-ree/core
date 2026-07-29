<?php

namespace Bale\Core\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrustCloudflareProxies
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->server->has('HTTP_CF_CONNECTING_IP')) {
            $realIp = $request->server->get('HTTP_CF_CONNECTING_IP');

            $request->server->set('REMOTE_ADDR', $realIp);
            $request->headers->set('X-Forwarded-For', $realIp);
        }

        return $next($request);
    }
}
