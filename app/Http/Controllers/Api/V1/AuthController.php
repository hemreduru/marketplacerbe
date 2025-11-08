<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Register a new user.
     *
     * POST /api/v1/auth/register
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'username' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __('api.validation_error'),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'username' => $request->username,
                'password' => Hash::make($request->password),
            ]);

            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => __('api.auth.register_success'),
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'username' => $user->username,
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer',
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.auth.register_failed'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Login user and create token.
     *
     * POST /api/v1/auth/login
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __('api.validation_error'),
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => __('api.auth.invalid_credentials'),
            ], 401);
        }

        // Revoke all old tokens
        $user->tokens()->delete();

        // Create new token
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => __('api.auth.login_success'),
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'username' => $user->username,
                ],
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    /**
     * Logout user (revoke token).
     *
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            // Revoke current token
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'success' => true,
                'message' => __('api.auth.logout_success'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.auth.logout_failed'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get authenticated user info.
     *
     * GET /api/v1/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            return response()->json([
                'success' => true,
                'message' => __('api.auth.user_info_success'),
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'username' => $user->username,
                    'email_verified_at' => $user->email_verified_at,
                    'created_at' => $user->created_at,
                    'stats' => [
                        'products' => $user->products()->count(),
                        'marketplace_products' => $user->marketplaceProducts()->count(),
                        'marketplace_credentials' => $user->marketplaceCredentials()->count(),
                        'orders' => $user->marketplaceOrders()->count(),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.auth.user_info_failed'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Refresh token (revoke old and create new).
     *
     * POST /api/v1/auth/refresh
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Revoke all old tokens
            $user->tokens()->delete();

            // Create new token
            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => __('api.auth.token_refreshed'),
                'data' => [
                    'token' => $token,
                    'token_type' => 'Bearer',
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.auth.refresh_failed'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Revoke all tokens for user.
     *
     * POST /api/v1/auth/revoke-all
     */
    public function revokeAll(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $count = $user->tokens()->count();
            $user->tokens()->delete();

            return response()->json([
                'success' => true,
                'message' => __('api.auth.tokens_revoked'),
                'data' => [
                    'revoked_count' => $count,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('api.auth.revoke_failed'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
