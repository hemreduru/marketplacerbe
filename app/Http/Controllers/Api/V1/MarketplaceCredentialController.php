<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCredentialRequest;
use App\Http\Requests\UpdateCredentialRequest;
use App\Http\Traits\ApiResponseTrait;
use App\Models\Marketplace;
use App\Models\UserMarketplaceCredential;
use App\Services\MarketplaceServiceFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MarketplaceCredentialController extends Controller
{
    use ApiResponseTrait;

    /**
     * Display a listing of user's marketplace credentials.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $userId = Auth::id() ?? 3; // Fallback to test user for now

            $query = UserMarketplaceCredential::with('marketplace')
                ->where('user_id', $userId);

            // Filter by marketplace
            if ($request->has('marketplace_id')) {
                $query->where('marketplace_id', $request->marketplace_id);
            }

            // Filter by active status
            if ($request->has('is_active')) {
                $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
            }

            $credentials = $query->get();

            return $this->successResponse(
                $credentials,
                __('api.credential.list_success')
            );
        } catch (\Exception $e) {
            return $this->serverErrorResponse(
                __('api.error'),
                $e
            );
        }
    }

    /**
     * Store a newly created credential.
     *
     * @param StoreCredentialRequest $request
     * @return JsonResponse
     */
    public function store(StoreCredentialRequest $request): JsonResponse
    {
        try {
            $userId = Auth::id() ?? 3; // Fallback to test user for now

            // Check if credential already exists
            $exists = UserMarketplaceCredential::where('user_id', $userId)
                ->where('marketplace_id', $request->marketplace_id)
                ->exists();

            if ($exists) {
                Log::warning("Kullanici ID:{$userId} - Pazaryeri ID:{$request->marketplace_id} icin credential zaten mevcut");
                return $this->errorResponse(
                    __('api.credential.already_exists'),
                    409
                );
            }

            $credential = UserMarketplaceCredential::create([
                'user_id' => $userId,
                'marketplace_id' => $request->marketplace_id,
                'api_key' => $request->api_key,
                'api_secret' => $request->api_secret,
                'additional_credentials' => $request->additional_credentials ?? [],
                'is_active' => $request->is_active ?? true,
            ]);

            $credential->load('marketplace');

            Log::info("Kullanici ID:{$userId} - Credential ID:{$credential->id} - {$credential->marketplace->name} olusturuldu");

            return $this->createdResponse(
                $credential,
                __('api.credential.create_success')
            );
        } catch (\Exception $e) {
            return $this->serverErrorResponse(
                __('api.error'),
                $e
            );
        }
    }

    /**
     * Display the specified credential.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $userId = Auth::id() ?? 3; // Fallback to test user for now

            $credential = UserMarketplaceCredential::with('marketplace')
                ->where('user_id', $userId)
                ->find($id);

            if (!$credential) {
                return $this->notFoundResponse(
                    __('api.credential.not_found')
                );
            }

            return $this->successResponse(
                $credential,
                __('api.credential.show_success')
            );
        } catch (\Exception $e) {
            return $this->serverErrorResponse(
                __('api.error'),
                $e
            );
        }
    }

    /**
     * Update the specified credential.
     *
     * @param UpdateCredentialRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateCredentialRequest $request, int $id): JsonResponse
    {
        try {
            $userId = Auth::id() ?? 3; // Fallback to test user for now

            $credential = UserMarketplaceCredential::where('user_id', $userId)
                ->find($id);

            if (!$credential) {
                return $this->notFoundResponse(
                    __('api.credential.not_found')
                );
            }

            $credential->update($request->only([
                'api_key',
                'api_secret',
                'additional_credentials',
                'is_active',
            ]));

            $credential->load('marketplace');

            Log::info("Kullanici ID:{$userId} - Credential ID:{$credential->id} - {$credential->marketplace->name} guncellendi");

            return $this->successResponse(
                $credential,
                __('api.credential.update_success')
            );
        } catch (\Exception $e) {
            return $this->serverErrorResponse(
                __('api.error'),
                $e
            );
        }
    }

    /**
     * Remove the specified credential.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $userId = Auth::id() ?? 3; // Fallback to test user for now

            $credential = UserMarketplaceCredential::where('user_id', $userId)
                ->find($id);

            if (!$credential) {
                return $this->notFoundResponse(
                    __('api.credential.not_found')
                );
            }

            $marketplaceName = $credential->marketplace->name;
            $credentialId = $credential->id;
            $credential->delete();

            Log::info("Kullanici ID:{$userId} - Credential ID:{$credentialId} - {$marketplaceName} silindi");

            return $this->successResponse(
                null,
                __('api.credential.delete_success')
            );
        } catch (\Exception $e) {
            return $this->serverErrorResponse(
                __('api.error'),
                $e
            );
        }
    }

    /**
     * Test marketplace connection.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function test(int $id): JsonResponse
    {
        try {
            $userId = Auth::id() ?? 3; // Fallback to test user for now

            $credential = UserMarketplaceCredential::with('marketplace')
                ->where('user_id', $userId)
                ->find($id);

            if (!$credential) {
                return $this->notFoundResponse(
                    __('api.credential.not_found')
                );
            }

            // Check if marketplace service is implemented
            if (!MarketplaceServiceFactory::isImplemented($credential->marketplace->code)) {
                return $this->errorResponse(
                    __('api.marketplace.not_implemented'),
                    501
                );
            }

            // Try to create service and make a simple API call
            $service = MarketplaceServiceFactory::make($credential);

            // Test with getting brands (lightweight call)
            $result = $service->getBrands();

            Log::info("Kullanici ID:{$userId} - Credential ID:{$credential->id} - {$credential->marketplace->name} test basarili");

            return $this->successResponse(
                [
                    'marketplace' => $credential->marketplace->name,
                    'connection' => 'success',
                    'test_result' => $result,
                ],
                __('api.credential.test_success')
            );
        } catch (\Exception $e) {
            $userId = Auth::id() ?? 3;
            Log::error("Kullanici ID:{$userId} - Credential ID:{$id} test basarisiz - " . $e->getMessage());
            return $this->errorResponse(
                __('api.credential.test_failed') . ': ' . $e->getMessage(),
                400
            );
        }
    }
}
