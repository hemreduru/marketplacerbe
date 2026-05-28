<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    /**
     * Show user profile
     */
    public function show()
    {
        return redirect()->route('settings');
    }

    /**
     * Update user profile
     */
    public function update(UpdateProfileRequest $request)
    {
        $user = Auth::user();

        DB::beginTransaction();

        try {
            $user->update([
                'name' => $request->name,
                'email' => $request->email,
            ]);

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __('settings.profile_updated'),
                    'data' => $user,
                ]);
            }

            return back()->with('success', __('settings.profile_updated'));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update profile: '.$e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('common.error_occurred'),
                ], 500);
            }

            return back()->with('error', __('common.error_occurred'));
        }
    }

    public function updatePassword(UpdatePasswordRequest $request)
    {
        $user = Auth::user();

        // Check if current password is correct
        if (! Hash::check($request->current_password, $user->password)) {
            $errorMessage = __('settings.current_password_incorrect');
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'errors' => ['current_password' => [$errorMessage]],
                ], 422);
            }

            return back()->withErrors(['current_password' => $errorMessage]);
        }

        DB::beginTransaction();

        try {
            $user->update([
                'password' => Hash::make($request->password),
            ]);

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __('settings.password_updated'),
                ]);
            }

            return back()->with('success', __('settings.password_updated'));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update password: '.$e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('common.error_occurred'),
                ], 500);
            }

            return back()->with('error', __('common.error_occurred'));
        }
    }
}
