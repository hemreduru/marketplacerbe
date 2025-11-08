<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\UserSetting;
use App\Models\Language;
use App\Http\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UserSettingController extends Controller
{
    use ApiResponseTrait;

    /**
     * Get user settings.
     */
    public function show(Request $request): JsonResponse
    {
        try {
            $userId = Auth::id();

            $settings = UserSetting::with('preferredLanguage')
                ->where('user_id', $userId)
                ->first();

            if (!$settings) {
                // Create default settings if not exists
                $defaultLanguage = Language::where('code', 'tr')->first();

                $settings = UserSetting::create([
                    'user_id' => $userId,
                    'preferred_language_id' => $defaultLanguage->id,
                    'theme' => 'system',
                    'dark_mode' => false,
                ]);

                $settings->load('preferredLanguage');

                Log::info("Kullanici ID:{$userId} - Varsayilan ayarlar olusturuldu");
            }

            return $this->successResponse(
                $settings,
                __('api.settings.show_success')
            );
        } catch (\Exception $e) {
            Log::error("Kullanici ID:{$userId} - Ayarlar getirilemedi: {$e->getMessage()}");
            return $this->serverErrorResponse(
                __('api.error'),
                $e
            );
        }
    }

    /**
     * Update user settings.
     */
    public function update(Request $request): JsonResponse
    {
        try {
            $userId = Auth::id();

            $validator = Validator::make($request->all(), [
                'preferred_language_id' => 'nullable|exists:languages,id',
                'theme' => 'nullable|in:light,dark,system',
                'dark_mode' => 'nullable|boolean',
                'additional_settings' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $settings = UserSetting::where('user_id', $userId)->first();

            if (!$settings) {
                $defaultLanguage = Language::where('code', 'tr')->first();

                $settings = UserSetting::create([
                    'user_id' => $userId,
                    'preferred_language_id' => $defaultLanguage->id,
                    'theme' => 'system',
                    'dark_mode' => false,
                ]);
            }

            $settings->update($request->only([
                'preferred_language_id',
                'theme',
                'dark_mode',
                'additional_settings',
            ]));

            $settings->load('preferredLanguage');

            Log::info("Kullanici ID:{$userId} - Ayarlar guncellendi");

            return $this->successResponse(
                $settings,
                __('api.settings.update_success')
            );
        } catch (\Exception $e) {
            Log::error("Kullanici ID:{$userId} - Ayarlar guncellenemedi: {$e->getMessage()}");
            return $this->serverErrorResponse(
                __('api.error'),
                $e
            );
        }
    }

    /**
     * Update theme.
     */
    public function updateTheme(Request $request): JsonResponse
    {
        try {
            $userId = Auth::id();

            $validator = Validator::make($request->all(), [
                'theme' => 'required|in:light,dark,system',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $settings = UserSetting::where('user_id', $userId)->first();

            if (!$settings) {
                $defaultLanguage = Language::where('code', 'tr')->first();

                $settings = UserSetting::create([
                    'user_id' => $userId,
                    'preferred_language_id' => $defaultLanguage->id,
                    'theme' => $request->theme,
                    'dark_mode' => false,
                ]);
            } else {
                $settings->update(['theme' => $request->theme]);
            }

            Log::info("Kullanici ID:{$userId} - Tema degistirildi: {$request->theme}");

            return $this->successResponse(
                ['theme' => $settings->theme],
                __('api.settings.theme_updated')
            );
        } catch (\Exception $e) {
            Log::error("Kullanici ID:{$userId} - Tema degistirilemedi: {$e->getMessage()}");
            return $this->serverErrorResponse(
                __('api.error'),
                $e
            );
        }
    }

    /**
     * Update language.
     */
    public function updateLanguage(Request $request): JsonResponse
    {
        try {
            $userId = Auth::id();

            $validator = Validator::make($request->all(), [
                'language_id' => 'required|exists:languages,id',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator->errors());
            }

            $settings = UserSetting::where('user_id', $userId)->first();
            $language = Language::find($request->language_id);

            if (!$settings) {
                $settings = UserSetting::create([
                    'user_id' => $userId,
                    'preferred_language_id' => $request->language_id,
                    'theme' => 'system',
                    'dark_mode' => false,
                ]);
            } else {
                $settings->update(['preferred_language_id' => $request->language_id]);
            }

            $settings->load('preferredLanguage');

            Log::info("Kullanici ID:{$userId} - Dil degistirildi: {$language->code}");

            return $this->successResponse(
                [
                    'language' => $settings->preferredLanguage,
                ],
                __('api.settings.language_updated')
            );
        } catch (\Exception $e) {
            Log::error("Kullanici ID:{$userId} - Dil degistirilemedi: {$e->getMessage()}");
            return $this->serverErrorResponse(
                __('api.error'),
                $e
            );
        }
    }
}
