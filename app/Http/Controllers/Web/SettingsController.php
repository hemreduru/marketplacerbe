<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\UserSetting;

class SettingsController extends Controller
{
    /**
     * Show settings page
     */
    public function index()
    {
        $user = Auth::user();
        $settings = UserSetting::where('user_id', $user->id)->first();
        
        // Get available languages
        $languages = \App\Models\Language::where('is_active', true)->get();
        
        return view('profile.settings', [
            'user' => $user,
            'settings' => $settings,
            'languages' => $languages
        ]);
    }

    /**
     * Update user settings
     */
    public function update(Request $request)
    {
        $user = Auth::user();
        
        $validator = Validator::make($request->all(), [
            'preferred_language_id' => 'nullable|exists:languages,id',
            'timezone' => 'nullable|string|max:50',
            'date_format' => 'nullable|string|max:20',
            'theme' => 'nullable|in:light,dark',
            'dark_mode' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $settings = UserSetting::updateOrCreate(
            ['user_id' => $user->id],
            [
                'preferred_language_id' => $request->preferred_language_id,
                'timezone' => $request->timezone ?? 'UTC',
                'date_format' => $request->date_format ?? 'Y-m-d',
                'theme' => $request->theme ?? 'light',
                'dark_mode' => $request->dark_mode ?? false,
            ]
        );

        // Update session locale if language changed
        if ($request->preferred_language_id) {
            $language = \App\Models\Language::find($request->preferred_language_id);
            if ($language) {
                session(['app_locale' => $language->code]);
                app()->setLocale($language->code);
            }
        }

        // Update session theme
        session(['app_dark_mode' => $request->dark_mode ?? false]);

        return back()->with('success', __('common.settings_updated'));
    }

    /**
     * Update language preference (AJAX)
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
                'message' => $validator->errors()->first()
            ], 400);
        }

        $settings = UserSetting::updateOrCreate(
            ['user_id' => $user->id],
            ['preferred_language_id' => $request->language_id]
        );

        $language = \App\Models\Language::find($request->language_id);
        if ($language) {
            session(['app_locale' => $language->code]);
            app()->setLocale($language->code);
        }

        return response()->json([
            'success' => true,
            'message' => __('common.language_updated'),
            'data' => ['language' => $language->code]
        ]);
    }

    /**
     * Update theme preference (AJAX)
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
                'message' => $validator->errors()->first()
            ], 400);
        }

        $settings = UserSetting::updateOrCreate(
            ['user_id' => $user->id],
            [
                'dark_mode' => $request->dark_mode,
                'theme' => $request->dark_mode ? 'dark' : 'light'
            ]
        );

        session(['app_dark_mode' => $request->dark_mode]);

        return response()->json([
            'success' => true,
            'message' => __('common.theme_updated'),
            'data' => ['dark_mode' => $request->dark_mode]
        ]);
    }
}
