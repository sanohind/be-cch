<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CorsMiddleware
{
    /**
     * Allowed origins — tambahkan semua domain frontend yang boleh akses be-cch.
     */
    private function getAllowedOrigins(): array
    {
        $origins = [
            'http://localhost:5176',
            'http://localhost:5177',
            'http://localhost:5175',
            'http://localhost:5173',
        ];

        // Tambahkan FRONTEND_URL dari .env (production)
        $frontendUrl = env('FRONTEND_URL');
        if ($frontendUrl && !in_array($frontendUrl, $origins)) {
            $origins[] = rtrim($frontendUrl, '/');
        }

        // Tambahkan FRONTEND_URL_2 jika ada
        $frontendUrl2 = env('FRONTEND_URL_2');
        if ($frontendUrl2 && !in_array($frontendUrl2, $origins)) {
            $origins[] = rtrim($frontendUrl2, '/');
        }

        return $origins;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $allowedOrigins = $this->getAllowedOrigins();
        $origin = $request->headers->get('Origin', '');

        // Cek apakah origin diizinkan
        $isAllowed = in_array(rtrim($origin, '/'), $allowedOrigins);

        // Handle preflight OPTIONS request
        if ($request->isMethod('OPTIONS')) {
            $response = response('', 204);
            if ($isAllowed) {
                $response->headers->set('Access-Control-Allow-Origin', $origin);
                $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
                $response->headers->set('Access-Control-Allow-Headers', 'Authorization, Content-Type, Accept, X-Requested-With');
                $response->headers->set('Access-Control-Allow-Credentials', 'true');
                $response->headers->set('Access-Control-Max-Age', '86400');
            }
            return $response;
        }

        // Handle normal request
        $response = $next($request);

        if ($isAllowed) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Authorization, Content-Type, Accept, X-Requested-With');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        }

        return $response;
    }
}
