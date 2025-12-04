<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
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
    public function update(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors()
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

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
                    'data' => $user
                ]);
            }

            return back()->with('success', __('settings.profile_updated'));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update profile: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('common.error_occurred')
                ], 500);
            }

            return back()->with('error', __('common.error_occurred'));
        }
    }

    /**
     * Update user password
     */
    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8',
            'new_password_confirmation' => 'required|string|same:new_password',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors()
                ], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        // Check if current password is correct
        if (!Hash::check($request->current_password, $user->password)) {
            $errorMessage = __('settings.current_password_incorrect');
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'errors' => ['current_password' => [$errorMessage]]
                ], 422);
            }
            return back()->withErrors(['current_password' => $errorMessage]);
        }

        DB::beginTransaction();

        try {
            $user->update([
                'password' => Hash::make($request->new_password),
            ]);

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => __('settings.password_updated')
                ]);
            }

            return back()->with('success', __('settings.password_updated'));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update password: ' . $e->getMessage());

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('common.error_occurred')
                ], 500);
            }

            return back()->with('error', __('common.error_occurred'));
        }
    }
}
