<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Marketplace;
use App\Models\MarketplaceClaim;
use App\Models\MarketplaceClaimItem;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceProduct;
use App\Models\Product;
use App\Services\MarketplaceServiceFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MarketplaceClaimController extends Controller
{
    /**
     * Display a listing of claims.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = MarketplaceClaim::with(['marketplace', 'items', 'order']);

            // Filter by marketplace
            if ($request->filled('marketplace_id')) {
                $query->where('marketplace_id', $request->marketplace_id);
            }

            // Filter by claim status
            if ($request->filled('claim_status')) {
                $query->where('claim_status', $request->claim_status);
            }

            // Filter by claim type
            if ($request->filled('claim_type')) {
                $query->where('claim_type', $request->claim_type);
            }

            // Filter by date range
            if ($request->filled('start_date')) {
                $query->where('claim_date', '>=', $request->start_date);
            }

            if ($request->filled('end_date')) {
                $query->where('claim_date', '<=', $request->end_date);
            }

            // Search by claim ID or customer
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('marketplace_claim_id', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_email', 'like', "%{$search}%");
                });
            }

            // Order by claim date
            $query->orderBy('claim_date', 'desc');

            // Pagination
            $perPage = $request->get('per_page', 20);
            $claims = $query->paginate($perPage);

            Log::info("İade listesi başarıyla getirildi", ['count' => $claims->total()]);

            return response()->json([
                'success' => true,
                'message' => __('api.claim.list_success'),
                'data' => $claims,
            ]);
        } catch (\Exception $e) {
            Log::error("İade listesi getirme hatası: {$e->getMessage()}");

            return response()->json([
                'success' => false,
                'message' => __('api.claim.fetch_failed'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified claim.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $claim = MarketplaceClaim::with(['marketplace', 'items.product', 'items.marketplaceProduct', 'order'])
                ->findOrFail($id);

            Log::info("İade detayı getirildi", ['claim_id' => $id]);

            return response()->json([
                'success' => true,
                'message' => __('api.claim.show_success'),
                'data' => $claim,
            ]);
        } catch (\Exception $e) {
            Log::error("İade detayı getirme hatası: {$e->getMessage()}", ['claim_id' => $id]);

            return response()->json([
                'success' => false,
                'message' => __('api.claim.fetch_failed'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Fetch claims from marketplace and store them.
     */
    public function fetch(Request $request): JsonResponse
    {
        $request->validate([
            'marketplace_id' => 'required|exists:marketplaces,id',
            'page' => 'nullable|integer|min:0',
            'size' => 'nullable|integer|min:1|max:200',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'status' => 'nullable|string',
        ]);

        try {
            $marketplace = Marketplace::findOrFail($request->marketplace_id);
            $credential = $marketplace->credential;
            $service = MarketplaceServiceFactory::make($credential);

            $filters = [
                'page' => $request->get('page', 0),
                'size' => $request->get('size', 50),
            ];

            if ($request->filled('start_date')) {
                $filters['startDate'] = strtotime($request->start_date) * 1000;
            }

            if ($request->filled('end_date')) {
                $filters['endDate'] = strtotime($request->end_date) * 1000;
            }

            if ($request->filled('status')) {
                $filters['status'] = $request->status;
            }

            Log::info("İade verisi çekiliyor", ['marketplace' => $marketplace->name, 'filters' => $filters]);

            $response = $service->getClaims($filters);

            if (!isset($response['content']) || !is_array($response['content'])) {
                Log::error("API'den geçersiz yanıt alındı", ['response' => $response]);
                throw new \Exception('Invalid API response format');
            }

            $claims = $response['content'];
            $storedCount = 0;
            $updatedCount = 0;
            $errors = [];

            DB::beginTransaction();

            try {
                foreach ($claims as $claimData) {
                    try {
                        $result = $this->storeClaim($marketplace, $claimData, $service);
                        if ($result['action'] === 'created') {
                            $storedCount++;
                        } else {
                            $updatedCount++;
                        }
                    } catch (\Exception $e) {
                        $errors[] = [
                            'claim_id' => $claimData['id'] ?? 'unknown',
                            'error' => $e->getMessage(),
                        ];
                        Log::error("İade kaydedilirken hata", ['claim_id' => $claimData['id'] ?? 'unknown', 'error' => $e->getMessage()]);
                    }
                }

                DB::commit();

                Log::info("İade senkronizasyonu tamamlandı", ['stored' => $storedCount, 'updated' => $updatedCount, 'errors' => count($errors)]);

                return response()->json([
                    'success' => true,
                    'message' => __('api.claim.fetch_success'),
                    'data' => [
                        'total' => count($claims),
                        'stored' => $storedCount,
                        'updated' => $updatedCount,
                        'errors' => $errors,
                        'pagination' => [
                            'page' => $response['page'] ?? 0,
                            'size' => $response['size'] ?? 50,
                            'totalPages' => $response['totalPages'] ?? 1,
                            'totalElements' => $response['totalElements'] ?? count($claims),
                        ],
                    ],
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error("İade çekme hatası: {$e->getMessage()}");

            return response()->json([
                'success' => false,
                'message' => __('api.claim.fetch_failed'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Approve a claim.
     */
    public function approve(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'seller_note' => 'nullable|string|max:1000',
        ]);

        try {
            $claim = MarketplaceClaim::findOrFail($id);
            $marketplace = $claim->marketplace;
            $credential = $marketplace->credential;
            $service = MarketplaceServiceFactory::make($credential);

            Log::info("İade onaylanıyor", ['claim_id' => $id, 'marketplace_claim_id' => $claim->marketplace_claim_id]);

            $response = $service->approveClaim($claim->marketplace_claim_id);

            // Update claim status
            $claim->update([
                'claim_status' => 'Approved',
                'approved_at' => now(),
                'seller_note' => $request->get('seller_note', $claim->seller_note),
            ]);

            Log::info("İade onaylandı", ['claim_id' => $id]);

            return response()->json([
                'success' => true,
                'message' => __('api.claim.approve_success'),
                'data' => $claim->fresh(),
            ]);
        } catch (\Exception $e) {
            Log::error("İade onaylama hatası: {$e->getMessage()}", ['claim_id' => $id]);

            return response()->json([
                'success' => false,
                'message' => __('api.claim.approve_failed'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject a claim.
     */
    public function reject(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'reason' => 'required|string',
            'seller_note' => 'nullable|string|max:1000',
        ]);

        try {
            $claim = MarketplaceClaim::findOrFail($id);
            $marketplace = $claim->marketplace;
            $credential = $marketplace->credential;
            $service = MarketplaceServiceFactory::make($credential);

            Log::info("İade reddediliyor", ['claim_id' => $id, 'marketplace_claim_id' => $claim->marketplace_claim_id, 'reason' => $request->reason]);

            $response = $service->rejectClaim($claim->marketplace_claim_id, $request->reason);

            // Update claim status
            $claim->update([
                'claim_status' => 'Rejected',
                'rejected_at' => now(),
                'seller_note' => $request->get('seller_note', $claim->seller_note),
            ]);

            Log::info("İade reddedildi", ['claim_id' => $id]);

            return response()->json([
                'success' => true,
                'message' => __('api.claim.reject_success'),
                'data' => $claim->fresh(),
            ]);
        } catch (\Exception $e) {
            Log::error("İade reddetme hatası: {$e->getMessage()}", ['claim_id' => $id]);

            return response()->json([
                'success' => false,
                'message' => __('api.claim.reject_failed'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store claim in database.
     *
     * @param Marketplace $marketplace
     * @param array $claimData
     * @param mixed $service
     * @return array
     */
    private function storeClaim(Marketplace $marketplace, array $claimData, $service): array
    {
        // Find or create claim
        $claim = MarketplaceClaim::updateOrCreate(
            [
                'marketplace_id' => $marketplace->id,
                'marketplace_claim_id' => $claimData['id'],
            ],
            [
                'user_id' => $marketplace->user_id,
                'marketplace_order_id_value' => $claimData['orderNumber'] ?? null,
                'package_number' => $claimData['packageNumber'] ?? null,
                'claim_type' => $claimData['claimType'] ?? 'Return',
                'claim_status' => $claimData['status'] ?? 'Created',
                'claim_reason' => $claimData['claimReason'] ?? null,
                'customer_note' => $claimData['customerNote'] ?? null,
                'customer_name' => $claimData['customerName'] ?? null,
                'customer_email' => $claimData['customerEmail'] ?? null,
                'customer_phone' => $claimData['customerPhone'] ?? null,
                'claim_amount' => $claimData['claimAmount'] ?? 0,
                'currency' => $claimData['currency'] ?? 'TRY',
                'claim_date' => isset($claimData['claimDate']) ? date('Y-m-d H:i:s', $claimData['claimDate'] / 1000) : now(),
                'marketplace_raw_data' => $claimData,
            ]
        );

        $action = $claim->wasRecentlyCreated ? 'created' : 'updated';

        // Try to link with existing order
        if ($claim->marketplace_order_id_value && !$claim->marketplace_order_id) {
            $order = MarketplaceOrder::where('marketplace_id', $marketplace->id)
                ->where('marketplace_order_id', $claim->marketplace_order_id_value)
                ->first();

            if ($order) {
                $claim->update(['marketplace_order_id' => $order->id]);
            }
        }

        // Fetch and store claim items
        try {
            $itemsResponse = $service->getClaimItems($claimData['id']);

            if (isset($itemsResponse['claimItems']) && is_array($itemsResponse['claimItems'])) {
                foreach ($itemsResponse['claimItems'] as $itemData) {
                    $this->storeClaimItem($claim, $itemData);
                }
            }
        } catch (\Exception $e) {
            Log::warning("İade kalemleri alınamadı", ['claim_id' => $claimData['id'], 'error' => $e->getMessage()]);
        }

        return ['claim' => $claim, 'action' => $action];
    }

    /**
     * Store claim item in database.
     *
     * @param MarketplaceClaim $claim
     * @param array $itemData
     * @return MarketplaceClaimItem
     */
    private function storeClaimItem(MarketplaceClaim $claim, array $itemData): MarketplaceClaimItem
    {
        $barcode = $itemData['barcode'] ?? null;

        // Try to find product by barcode
        $product = null;
        $marketplaceProduct = null;

        if ($barcode) {
            $product = Product::where('barcode', $barcode)->first();
            $marketplaceProduct = MarketplaceProduct::where('marketplace_id', $claim->marketplace_id)
                ->where('barcode', $barcode)
                ->first();
        }

        // Try to find related order item
        $orderItemId = null;
        if ($claim->marketplace_order_id && $barcode) {
            $orderItem = MarketplaceOrderItem::whereHas('order', function ($q) use ($claim) {
                $q->where('id', $claim->marketplace_order_id);
            })->where('barcode', $barcode)->first();

            if ($orderItem) {
                $orderItemId = $orderItem->id;
            }
        }

        return MarketplaceClaimItem::updateOrCreate(
            [
                'marketplace_claim_id' => $claim->id,
                'marketplace_item_id' => $itemData['id'] ?? null,
            ],
            [
                'product_id' => $product?->id,
                'marketplace_product_id' => $marketplaceProduct?->id,
                'marketplace_order_item_id' => $orderItemId,
                'barcode' => $barcode,
                'product_name' => $itemData['productName'] ?? 'Unknown Product',
                'product_sku' => $itemData['merchantSku'] ?? null,
                'variant_info' => $itemData['variantInfo'] ?? null,
                'quantity_claimed' => $itemData['quantity'] ?? 1,
                'quantity_approved' => $itemData['approvedQuantity'] ?? 0,
                'unit_price' => $itemData['unitPrice'] ?? 0,
                'total_amount' => ($itemData['unitPrice'] ?? 0) * ($itemData['quantity'] ?? 1),
                'refund_amount' => $itemData['refundAmount'] ?? 0,
                'currency' => $itemData['currency'] ?? 'TRY',
                'item_condition' => $itemData['condition'] ?? null,
                'claim_reason' => $itemData['claimReason'] ?? null,
                'customer_complaint' => $itemData['customerComplaint'] ?? null,
                'resolution' => $itemData['resolution'] ?? null,
                'marketplace_raw_data' => $itemData,
            ]
        );
    }
}
