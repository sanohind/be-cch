<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CchUser;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * SSO Authentication Controller for CCH
 *
 * Handles token validation and user sync from Sphere SSO.
 */
class SSOAuthController extends Controller
{
    /**
     * Get current user info (from Sphere token) and sync to cch_users.
     *
     * This endpoint:
     * 1. Validates the Sphere JWT (via VerifySphereToken middleware)
     * 2. Creates/updates the cch_users record (SSO bridge)
     * 3. Returns combined user info (Sphere data + CCH-specific role)
     */
    public function user(Request $request): JsonResponse
    {
        $sphereUser = $request->attributes->get('sphere_user');

        if (!$sphereUser || empty($sphereUser['id'])) {
            return response()->json([
                'success' => false,
                'message' => 'User information not available',
            ], 401);
        }

        // Fetch the user from sphere directly
        $cchUser = CchUser::with(['division'])->find($sphereUser['id']);

        if (!$cchUser) {
            return response()->json([
                'success' => false,
                'message' => 'User not found in Sphere database',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'user' => [
                'id'             => $cchUser->id,
                'sphere_user_id' => $cchUser->id,
                'username'       => $cchUser->username,
                'name'           => $cchUser->name,
                'email'          => $cchUser->email,
                'cch_role'       => $sphereUser['role'] ?? null,
                'is_active'      => $cchUser->is_active,
                'division'       => $cchUser->division ? [
                    'id'   => $cchUser->division->id,
                    'code' => $cchUser->division->code,
                    'name' => $cchUser->division->name,
                    'type' => null,
                ] : null,
                'plant' => null,
                // Sphere department info (for reference)
                'sphere' => [
                    'role'            => $sphereUser['role'],
                    'role_level'      => $sphereUser['role_level'],
                    'department_id'   => $sphereUser['department_id'],
                    'department_code' => $sphereUser['department_code'],
                    'department_name' => $sphereUser['department_name'],
                ],
            ],
        ]);
    }

    /**
     * Verify token validity (lightweight — no DB sync).
     */
    public function verify(Request $request): JsonResponse
    {
        $sphereUser = $request->attributes->get('sphere_user');

        return response()->json([
            'success' => true,
            'message' => 'Token is valid',
            'user_id' => $sphereUser['id'] ?? null,
        ]);
    }
}
