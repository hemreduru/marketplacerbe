<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\UserSetting;
use App\Models\Language;

class SettingsController extends Controller
{
    /**
     * Show settings page
     */
    public function index()
    {
        $user = Auth::user();
        $settings = UserSetting::where('user_id', $user->id)->first();
        $languages = Language::where('is_active', true)->get();

        return view('profile.settings', [
            'user' => $user,
            'settings' => $settings,
            'languages' => $languages
        ]);
    }

    /**
     * Update user settings (AJAX)
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'preferred_language_id' => 'required|exists:languages,id',
            'theme' => 'required|in:light,dark,system',
            'dark_mode' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __('validation.failed'),
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Update settings
            $settings = UserSetting::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'preferred_language_id' => $request->preferred_language_id,
                    'theme' => $request->theme,
                    'dark_mode' => $request->dark_mode ?? false,
                ]
            );

            // Update session locale
            $language = Language::find($request->preferred_language_id);
            if ($language) {
                session(['app_locale' => $language->code]);
                app()->setLocale($language->code);
            }

            // Update session theme
            session(['app_dark_mode' => $request->dark_mode ?? false]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('settings.updated_successfully'),
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update settings: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('common.error_occurred')
            ], 500);
        }
    }

    /**
     * Update language preference (AJAX)
     * @deprecated Use update() method instead
     */
    public function updateLanguage(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'language_id' => 'required|exists:languages,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $settings = UserSetting::updateOrCreate(
                ['user_id' => $user->id],
                ['preferred_language_id' => $request->language_id]
            );

            $language = Language::find($request->language_id);
            if ($language) {
                session(['app_locale' => $language->code]);
                app()->setLocale($language->code);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('settings.language_updated'),
                'data' => ['language' => $language->code]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update language: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('common.error_occurred')
            ], 500);
        }
    }

    /**
     * Update theme preference (AJAX)
     * @deprecated Use update() method instead
     */
    public function updateTheme(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'dark_mode' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $settings = UserSetting::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'dark_mode' => $request->dark_mode,
                    'theme' => $request->dark_mode ? 'dark' : 'light',
                    // Ensure preferred_language_id is set if creating new
                    'preferred_language_id' => DB::raw('COALESCE(preferred_language_id, (SELECT id FROM languages WHERE code = "tr" LIMIT 1))')
                ]
            );

            session(['app_dark_mode' => $request->dark_mode]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => __('settings.theme_updated'),
                'data' => ['dark_mode' => $request->dark_mode]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update theme: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('common.error_occurred')
            ], 500);
        }
    }
}
