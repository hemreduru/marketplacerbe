<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Http\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    use ApiResponseTrait;

    /**
     * List all active languages.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $languages = Language::active()
                ->ordered()
                ->get();

            return $this->successResponse(
                $languages,
                __('api.language.list_success')
            );
        } catch (\Exception $e) {
            return $this->serverErrorResponse(
                __('api.error'),
                $e
            );
        }
    }

    /**
     * Get a single language.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $language = Language::findOrFail($id);

            return $this->successResponse(
                $language,
                __('api.language.show_success')
            );
        } catch (\Exception $e) {
            return $this->notFoundResponse(
                __('api.language.not_found')
            );
        }
    }
}
