<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\ActiveVisitor;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

class TrackActiveVisitor
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Don't track admin routes, API routes, or migration route
        if ($request->is('admin*') || $request->is('livewire*') || $request->is('api*') || $request->is('run-migrations*')) {
            return $next($request);
        }

        try {
            $sessionId = Session::getId();
            $lastUpdate = Session::get('last_activity_update');

            // Debounce: Only update DB once every minute per session
            if (!$lastUpdate || now()->diffInSeconds($lastUpdate) > 60) {
                $ip = $request->ip();
                
                // Exclude localhost
                if ($ip === '127.0.0.1' || $ip === '::1') {
                    $ip = '8.8.8.8'; // Mock IP for local testing
                }

                // Get location (Cached for 24 hours per IP)
                $location = Cache::remember('ip_location_' . $ip, 86400, function () use ($ip) {
                    try {
                        $response = Http::timeout(2)->get("http://ip-api.com/json/{$ip}?fields=city,country,status");
                        if ($response->successful() && $response->json('status') === 'success') {
                            return [
                                'city' => $response->json('city'),
                                'country' => $response->json('country'),
                            ];
                        }
                    } catch (\Exception $e) {
                        // Ignore errors
                    }
                    return ['city' => 'Unknown', 'country' => 'Unknown'];
                });

                ActiveVisitor::updateOrCreate(
                    ['session_id' => $sessionId],
                    [
                        'ip_address' => $ip,
                        'city' => $location['city'],
                        'country' => $location['country'],
                        'url' => $request->fullUrl(),
                        'last_activity' => now(),
                    ]
                );

                Session::put('last_activity_update', now());

                // Garbage Collection: Delete visitors inactive for more than 2 hours (5% chance)
                if (rand(1, 100) <= 5) {
                    ActiveVisitor::where('last_activity', '<', now()->subHours(2))->delete();
                }
            }
        } catch (\Exception $e) {
            // Fail gracefully if table doesn't exist or DB error occurs
            // We don't want tracking to bring down the whole app
        }

        return $next($request);
    }
}
