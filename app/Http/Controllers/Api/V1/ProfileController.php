<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    use ApiResponseTrait;

    /**
     * Get authenticated user profile.
     */
    public function show(): JsonResponse
    {
        try {
            $user = Auth::user();

            return $this->successResponse(
                $user,
                __('api.profile.show_success')
            );
        } catch (\Exception $e) {
            Log::error('Profile show error', [
                'message' => $e->getMessage(),
                'user_id' => Auth::id(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return $this->serverErrorResponse(
                __('api.profile.show_failed'),
                $e
            );
        }
    }

    /**
     * Update user profile information.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email,' . Auth::id(),
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse(
                    __('api.profile.validation_failed'),
                    $validator->errors()
                );
            }

            $user = Auth::user();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->save();

            Log::info('Profile updated successfully', [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ]);

            return $this->successResponse(
                $user,
                __('api.profile.update_success')
            );
        } catch (\Exception $e) {
            Log::error('Profile update error', [
                'message' => $e->getMessage(),
                'user_id' => Auth::id(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return $this->serverErrorResponse(
                __('api.profile.update_failed'),
                $e
            );
        }
    }

    /**
     * Update user password.
     */
    public function updatePassword(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse(
                    __('api.profile.validation_failed'),
                    $validator->errors()
                );
            }

            $user = Auth::user();

            // Check current password
            if (!Hash::check($request->current_password, $user->password)) {
                return $this->errorResponse(
                    __('api.profile.current_password_incorrect'),
                    400
                );
            }

            // Update password
            $user->password = Hash::make($request->new_password);
            $user->save();

            Log::info('Password updated successfully', [
                'user_id' => $user->id
            ]);

            return $this->successResponse(
                null,
                __('api.profile.password_update_success')
            );
        } catch (\Exception $e) {
            Log::error('Password update error', [
                'message' => $e->getMessage(),
                'user_id' => Auth::id(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return $this->serverErrorResponse(
                __('api.profile.password_update_failed'),
                $e
            );
        }
    }
}
