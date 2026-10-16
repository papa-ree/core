<?php

namespace Bale\Core\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ambil IP klien asli dari header `CF-Connecting-IP`.
 *
 * Topologi produksi memakai Cloudflare Tunnel:
 *   Cloudflare edge -> cloudflared (host) -> Traefik -> FrankenPHP/Caddy
 * sehingga peer TCP PHP adalah Traefik (IP jaringan Docker), bukan edge
 * Cloudflare. Header `CF-Connecting-IP` di-set oleh edge Cloudflare dan
 * diteruskan oleh cloudflared + Traefik.
 *
 * Header ini HANYA dipercaya bila request benar-benar berasal dari proxy
 * internal terdaftar (config `trustedproxy.proxies`) — supaya klien yang
 * bisa menjangkau container secara langsung tidak bisa memalsukan IP-nya.
 *
 * Nilainya di-set ke `X-Forwarded-For` (menggantikan, bukan menambah) —
 * BUKAN ke `REMOTE_ADDR`. Alasannya: `REMOTE_ADDR` tetap harus menunjuk ke
 * proxy internal supaya Laravel masih mempercayai `X-Forwarded-Proto` /
 * `X-Forwarded-Host` (deteksi HTTPS & host). Symfony lalu mengembalikan
 * `CF-Connecting-IP` sebagai IP klien melalui mekanisme trusted-proxy.
 *
 * Middleware ini harus berjalan SETELAH `TrustProxies` (daftar global).
 */
class TrustCloudflareProxies
{
    public function handle(Request $request, Closure $next): Response
    {
        $realIp = trim((string) $request->headers->get('CF-Connecting-IP', ''));

        if ($realIp !== ''
            && filter_var($realIp, FILTER_VALIDATE_IP) !== false
            && $request->isFromTrustedProxy()
        ) {
            $request->headers->set('X-Forwarded-For', $realIp);
        }

        return $next($request);
    }
}
