<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Verify JWT & OIDC Token from Sphere SSO
 *
 * Validates remote OIDC tokens using Sphere's introspect endpoint
 * or falls back to local JWT validation using HS256 algorithm.
 */
class VerifySphereToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'No token provided',
            ], 401);
        }

        // 1. Attempt OIDC remote token validation first
        $userData = $this->validateOidcToken($token);

        // 2. Fallback to Local JWT validation
        if (!$userData) {
            $userData = $this->validateLocalJwtToken($token);
        }

        if (!$userData) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired token',
            ], 401);
        }

        // Handle role which could be an array or string
        $roleData = $userData['role'] ?? null;
        $roleSlug = is_array($roleData) ? ($roleData['slug'] ?? null) : $roleData;
        $roleLevel = is_array($roleData) ? ($roleData['level'] ?? null) : ($userData['role_level'] ?? null);

        // Handle department which could be an array or string
        $deptData = $userData['department'] ?? null;
        $deptId = is_array($deptData) ? ($deptData['id'] ?? null) : ($userData['department_id'] ?? null);
        $deptCode = is_array($deptData) ? ($deptData['code'] ?? null) : ($userData['department_code'] ?? null);
        $deptName = is_array($deptData) ? ($deptData['name'] ?? null) : ($userData['department_name'] ?? null);

        // Attach full sphere user info to request
        $sphereArr = [
            'id'              => $userData['id'] ?? $userData['sub'] ?? null,
            'email'           => $userData['email'] ?? null,
            'username'        => $userData['username'] ?? ($userData['preferred_username'] ?? null),
            'name'            => $userData['name'] ?? null,
            'role'            => $roleSlug,
            'role_level'      => $roleLevel,
            'department_id'   => $deptId,
            'department_code' => $deptCode,
            'department_name' => $deptName,
        ];

        // Fetch user directly from sphere DB
        $cchUser = \App\Models\CchUser::find($sphereArr['id']);
        if ($cchUser) {
            // Re-sync basic info just to be safe, but actually it's fetched directly so not needed.
            // Role level might be fetched from role relation if needed in controllers
            $sphereArr['role_level'] = $cchUser->role?->level ?? $sphereArr['role_level'];
            $sphereArr['role'] = $cchUser->role?->slug ?? $sphereArr['role'];
            $sphereArr['department_id'] = $cchUser->department_id ?? $sphereArr['department_id'];
        }

        $request->attributes->set('sphere_user', $sphereArr);
        $request->attributes->set('cch_user', $cchUser);

        return $next($request);
    }

    /**
     * Call SPHERE_API_URL/api/oauth/verify-token
     */
    protected function validateOidcToken($token): ?array
    {
        $sphereUrl = env('SPHERE_API_URL');
        if (!$sphereUrl) return null;

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ])->timeout(5)->get($sphereUrl . '/api/oauth/verify-token');

            if ($response->successful()) {
                $data = $response->json();
                return $data['data']['user'] ?? null;
            }
        } catch (\Exception $e) {
            Log::warning('CCH: OIDC Remote verification failed: ' . $e->getMessage());
        }
        return null;
    }

    /**
     * Local JWT HS256 fallback decode
     */
    protected function validateLocalJwtToken($token): ?array
    {
        try {
            $jwtSecret = env('SPHERE_JWT_SECRET');
            if (!$jwtSecret) {
                Log::error('CCH: Sphere JWT secret not configured (SPHERE_JWT_SECRET missing)');
                return null;
            }

            $decoded = JWT::decode($token, new Key($jwtSecret, 'HS256'));

            return [
                'id'              => $decoded->sub ?? $decoded->id ?? null,
                'email'           => $decoded->email ?? null,
                'username'        => $decoded->username ?? null,
                'name'            => $decoded->name ?? null,
                'role'            => $decoded->role ?? null,
                'role_level'      => $decoded->role_level ?? null,
                'department_id'   => $decoded->department_id ?? null,
                'department_code' => $decoded->department_code ?? null,
                'department_name' => $decoded->department_name ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('CCH: Local JWT fallback verification failed: ' . $e->getMessage());
            return null;
        }
    }
}
