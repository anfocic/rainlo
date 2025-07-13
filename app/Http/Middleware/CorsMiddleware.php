<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware
{

    public function handle(Request $request, Closure $next): Response
    {
        // Handle preflight OPTIONS requests
        if ($request->getMethod() === 'OPTIONS') {
            return $this->handlePreflightRequest($request);
        }

        $response = $next($request);

        return $this->addCorsHeaders($request, $response);
    }

    /**
     * Handle preflight OPTIONS requests
     */
    private function handlePreflightRequest(Request $request): Response
    {
        $origin = $request->header('Origin');

        if (!$this->isOriginAllowed($origin)) {
            return response('', 403);
        }

        return response('', 200)
            ->header('Access-Control-Allow-Origin', $origin)
            ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS')
            ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin, X-CSRF-TOKEN')
            ->header('Access-Control-Allow-Credentials', 'true')
            ->header('Access-Control-Max-Age', '86400');
    }

    /**
     * Add CORS headers to the response
     */
    private function addCorsHeaders(Request $request, Response $response): Response
    {
        $origin = $request->header('Origin');

        if ($this->isOriginAllowed($origin)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Access-Control-Expose-Headers', 'Content-Length, Content-Type');
        }

        return $response;
    }

    /**
     * Check if the origin is allowed
     */
    private function isOriginAllowed(?string $origin): bool
    {
        if (!$origin) {
            return false;
        }

        $allowedOrigins = [
            'https://rainlo.app',
            'http://localhost:3000',
            'http://localhost:5173',
        ];

        return in_array($origin, $allowedOrigins);
    }
}
