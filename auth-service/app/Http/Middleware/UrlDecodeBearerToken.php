<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UrlDecodeBearerToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Authorization');
        if ($header && str_starts_with($header, 'Bearer ')) {
            $token = substr($header, 7);
            // If the token is URL encoded (contains %7C which is |), decode it
            if (str_contains($token, '%7C') || str_contains($token, '%7c')) {
                $request->headers->set('Authorization', 'Bearer ' . urldecode($token));
            }
        }

        return $next($request);
    }
}
