<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Ensure the response is an instance of Illuminate\Http\Response or similar
        // before attaching headers, just in case it's a binary file download, etc.
        if (method_exists($response, 'header')) {
            $response->header('X-Frame-Options', 'SAMEORIGIN'); // Mencegah Clickjacking
            $response->header('X-XSS-Protection', '1; mode=block'); // Proteksi dasar XSS di browser lama
            $response->header('X-Content-Type-Options', 'nosniff'); // Mencegah MIME-sniffing
            $response->header('Referrer-Policy', 'strict-origin-when-cross-origin');
            $response->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
