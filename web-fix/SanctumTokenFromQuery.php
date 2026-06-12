<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SanctumTokenFromQuery
{
    /**
     * Allow API test runners to pass Sanctum token via query when Authorization header is omitted.
     */
    public function handle(Request $request, Closure $next)
    {
        if (! $request->bearerToken()) {
            $token = $request->query('token')
                ?? $request->query('api_token')
                ?? $request->header('X-Api-Token');

            if (is_string($token) && $token !== '') {
                $request->headers->set('Authorization', 'Bearer ' . $token);
            }
        }

        return $next($request);
    }
}
