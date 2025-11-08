<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use App\Models\Marketplace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MarketplaceController extends Controller
{
    use ApiResponseTrait;

    /**
     * Display a listing of marketplaces.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Marketplace::query();

            // Filter by active status
            if ($request->has('is_active')) {
                $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
            }

            // Search by name or code
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%");
                });
            }

            // Order by
            $orderBy = $request->get('order_by', 'name');
            $orderDir = $request->get('order_dir', 'asc');
            $query->orderBy($orderBy, $orderDir);

            $marketplaces = $query->get();

            return $this->successResponse(
                $marketplaces,
                __('api.marketplace.list_success')
            );
        } catch (\Exception $e) {
            return $this->serverErrorResponse(
                __('api.error'),
                $e
            );
        }
    }

    /**
     * Display the specified marketplace.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $marketplace = Marketplace::find($id);

            if (!$marketplace) {
                return $this->notFoundResponse(
                    __('api.marketplace.not_found')
                );
            }

            return $this->successResponse(
                $marketplace,
                __('api.marketplace.show_success')
            );
        } catch (\Exception $e) {
            return $this->serverErrorResponse(
                __('api.error'),
                $e
            );
        }
    }

    /**
     * Get marketplace statistics.
     *
     * @param int $id
     * @param Request $request
     * @return JsonResponse
     */
    public function stats(int $id, Request $request): JsonResponse
    {
        try {
            $marketplace = Marketplace::find($id);

            if (!$marketplace) {
                return $this->notFoundResponse(
                    __('api.marketplace.not_found')
                );
            }

            $userId = Auth::id();

            $stats = [
                'marketplace' => $marketplace->only(['id', 'name', 'code', 'is_active']),
                'credentials_count' => $marketplace->userCredentials()->where('user_id', $userId)->count(),
                'products_count' => $marketplace->marketplaceProducts()->where('user_id', $userId)->count(),
                'sync_logs_count' => $marketplace->syncLogs()->where('user_id', $userId)->count(),
                'last_sync' => $marketplace->syncLogs()->where('user_id', $userId)->latest()->first()?->created_at,
            ];

            return $this->successResponse(
                $stats,
                __('api.success')
            );
        } catch (\Exception $e) {
            return $this->serverErrorResponse(
                __('api.error'),
                $e
            );
        }
    }
}
