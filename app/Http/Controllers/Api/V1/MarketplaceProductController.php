<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkPushProductRequest;
use App\Http\Requests\BulkSyncRequest;
use App\Http\Requests\PullProductRequest;
use App\Http\Requests\PushProductRequest;
use App\Http\Traits\ApiResponseTrait;
use App\Models\MarketplaceProduct;
use App\Models\Product;
use App\Models\UserMarketplaceCredential;
use App\Services\MarketplaceServiceFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MarketplaceProductController extends Controller
{
    use ApiResponseTrait;

    /**
     * Display a listing of marketplace products.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $userId = Auth::id();

            $query = MarketplaceProduct::with(['product', 'marketplace'])
                ->where('user_id', $userId);

            // Filter by marketplace
            if ($request->has('marketplace_id')) {
                $query->where('marketplace_id', $request->marketplace_id);
            }

            // Filter by product
            if ($request->has('product_id')) {
                $query->where('product_id', $request->product_id);
            }

            // Filter by status
            if ($request->has('marketplace_status')) {
                $query->where('marketplace_status', $request->marketplace_status);
            }

            // Filter by approved
            if ($request->has('approved')) {
                $query->where('approved', filter_var($request->approved, FILTER_VALIDATE_BOOLEAN));
            }

            // Order by
            $orderBy = $request->get('order_by', 'last_sync_at');
            $orderDir = $request->get('order_dir', 'desc');
            $query->orderBy($orderBy, $orderDir);

            // Pagination
            $perPage = $request->get('per_page', 50);
            $marketplaceProducts = $query->paginate($perPage);

            return $this->paginatedResponse(
                $marketplaceProducts,
                __('api.marketplace_product.list_success')
            );
        } catch (\Exception $e) {
            return $this->serverErrorResponse(
                __('api.error'),
                $e
            );
        }
    }

    /**
     * Display the specified marketplace product.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $userId = Auth::id();

            $marketplaceProduct = MarketplaceProduct::with(['product', 'marketplace'])
                ->where('user_id', $userId)
                ->find($id);

            if (!$marketplaceProduct) {
                return $this->notFoundResponse(
                    __('api.marketplace_product.not_found')
                );
            }

            return $this->successResponse(
                $marketplaceProduct,
                __('api.marketplace_product.show_success')
            );
        } catch (\Exception $e) {
            return $this->serverErrorResponse(
                __('api.error'),
                $e
            );
        }
    }

    /**
     * Push product to marketplace.
     *
     * @param PushProductRequest $request
     * @return JsonResponse
     */
    public function push(PushProductRequest $request): JsonResponse
    {
        try {
            $userId = Auth::id();

            // Get product
            $product = Product::where('user_id', $userId)
                ->find($request->product_id);

            if (!$product) {
                return $this->notFoundResponse(
                    __('api.product.not_found')
                );
            }

            // Get credential
            $credential = UserMarketplaceCredential::where('user_id', $userId)
                ->where('marketplace_id', $request->marketplace_id)
                ->where('is_active', true)
                ->first();

            if (!$credential) {
                return $this->notFoundResponse(
                    __('api.credential.not_found')
                );
            }

            // Check if already synced
            $exists = MarketplaceProduct::where('user_id', $userId)
                ->where('product_id', $product->id)
                ->where('marketplace_id', $request->marketplace_id)
                ->exists();

            if ($exists && !$request->force) {
                Log::warning("Kullanici ID:{$userId} - Urun ID:{$product->id} - SKU:{$product->sku} - Pazaryeri ID:{$request->marketplace_id} zaten senkronize");
                return $this->errorResponse(
                    __('api.marketplace_product.already_synced'),
                    409
                );
            }

            // Create marketplace service
            $service = MarketplaceServiceFactory::make($credential);

            // Push to marketplace
            $response = $service->createProduct($product);

            // Store marketplace product record
            $marketplaceProduct = MarketplaceProduct::updateOrCreate(
                [
                    'user_id' => $userId,
                    'product_id' => $product->id,
                    'marketplace_id' => $request->marketplace_id,
                ],
                [
                    'marketplace_product_id' => $response['id'] ?? null,
                    'marketplace_sku' => $response['barcode'] ?? $product->barcode,
                    'stock_code' => $response['stockCode'] ?? $product->sku,
                    'approved' => $response['approved'] ?? false,
                    'marketplace_status' => $response['status'] ?? 'pending',
                    'last_sync_at' => now(),
                    'marketplace_data' => $response,
                ]
            );

            Log::info("Kullanici ID:{$userId} - Urun ID:{$product->id} - SKU:{$product->sku} - {$credential->marketplace->name} pazaryerine gonderildi");

            return $this->successResponse(
                $marketplaceProduct->load(['product', 'marketplace']),
                __('api.marketplace_product.push_success')
            );
        } catch (\Exception $e) {
            $userId = Auth::id();
            Log::error("Kullanici ID:{$userId} - Urun pazaryerine gonderilemedi - " . $e->getMessage());
            return $this->errorResponse(
                __('api.marketplace_product.push_failed') . ': ' . $e->getMessage(),
                400
            );
        }
    }

    /**
     * Pull products from marketplace.
     *
     * @param PullProductRequest $request
     * @return JsonResponse
     */
    public function pull(PullProductRequest $request): JsonResponse
    {
        try {
            $userId = Auth::id();

            // Get credential
            $credential = UserMarketplaceCredential::where('user_id', $userId)
                ->where('marketplace_id', $request->marketplace_id)
                ->where('is_active', true)
                ->first();

            if (!$credential) {
                return $this->notFoundResponse(
                    __('api.credential.not_found')
                );
            }

            // Create marketplace service
            $service = MarketplaceServiceFactory::make($credential);

            // Fetch products from marketplace
            $filters = [
                'page' => $request->get('page', 0),
                'size' => $request->get('size', 50),
            ];

            if ($request->has('approved')) {
                $filters['approved'] = filter_var($request->approved, FILTER_VALIDATE_BOOLEAN);
            }

            $response = $service->getProducts($filters);
            $products = $response['content'] ?? [];

            $imported = 0;
            $updated = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($products as $marketplaceProductData) {
                try {
                    // Find or create product by barcode
                    $barcode = $marketplaceProductData['barcode'] ?? null;

                    if (!$barcode) {
                        $errors[] = 'Product without barcode: ' . ($marketplaceProductData['title'] ?? 'unknown');
                        continue;
                    }

                    $product = Product::firstOrCreate(
                        [
                            'user_id' => $userId,
                            'barcode' => $barcode,
                        ],
                        [
                            'sku' => $marketplaceProductData['stockCode'] ?? $barcode,
                            'name' => $marketplaceProductData['title'] ?? '',
                            'brand' => $marketplaceProductData['brand'] ?? null,
                            'stock_quantity' => $marketplaceProductData['quantity'] ?? 0,
                            'base_price' => $marketplaceProductData['listPrice'] ?? 0,
                            'sale_price' => $marketplaceProductData['salePrice'] ?? 0,
                            'vat_rate' => $marketplaceProductData['vatRate'] ?? config('marketplace.default_vat_rate'),
                            'currency' => 'TRY',
                            'is_active' => true,
                        ]
                    );

                    $wasRecentlyCreated = $product->wasRecentlyCreated;

                    // Create or update marketplace product
                    MarketplaceProduct::updateOrCreate(
                        [
                            'user_id' => $userId,
                            'product_id' => $product->id,
                            'marketplace_id' => $request->marketplace_id,
                        ],
                        [
                            'marketplace_product_id' => $marketplaceProductData['id'] ?? null,
                            'marketplace_sku' => $marketplaceProductData['barcode'],
                            'stock_code' => $marketplaceProductData['stockCode'] ?? null,
                            'product_code' => $marketplaceProductData['productCode'] ?? null,
                            'approved' => $marketplaceProductData['approved'] ?? false,
                            'marketplace_status' => $marketplaceProductData['onSale'] ? 'approved' : 'pending',
                            'last_sync_at' => now(),
                            'marketplace_data' => $marketplaceProductData,
                        ]
                    );

                    if ($wasRecentlyCreated) {
                        $imported++;
                    } else {
                        $updated++;
                    }
                } catch (\Exception $e) {
                    $errors[] = 'Error processing product: ' . $e->getMessage();
                }
            }

            DB::commit();

            Log::info("Kullanici ID:{$userId} - {$credential->marketplace->name} pazaryerinden urun cekildi - Yeni: {$imported} - Guncellenen: {$updated}");

            return $this->successResponse(
                [
                    'imported' => $imported,
                    'updated' => $updated,
                    'total_fetched' => count($products),
                    'errors' => $errors,
                    'pagination' => [
                        'page' => $response['page'] ?? 0,
                        'size' => $response['size'] ?? 0,
                        'total_pages' => $response['totalPages'] ?? 0,
                        'total_elements' => $response['totalElements'] ?? 0,
                    ],
                ],
                __('api.marketplace_product.pull_success')
            );
        } catch (\Exception $e) {
            DB::rollBack();
            $userId = Auth::id();
            Log::error("Kullanici ID:{$userId} - Pazaryerinden urun cekilemedi - " . $e->getMessage());
            return $this->errorResponse(
                __('api.marketplace_product.pull_failed') . ': ' . $e->getMessage(),
                400
            );
        }
    }

    /**
     * Sync product stock and price.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function sync(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'sync_stock' => 'boolean',
            'sync_price' => 'boolean',
        ]);

        try {
            $userId = Auth::id();

            $marketplaceProduct = MarketplaceProduct::with(['product', 'marketplace'])
                ->where('user_id', $userId)
                ->find($id);

            if (!$marketplaceProduct) {
                return $this->notFoundResponse(
                    __('api.marketplace_product.not_found')
                );
            }

            // Get credential
            $credential = UserMarketplaceCredential::where('user_id', $userId)
                ->where('marketplace_id', $marketplaceProduct->marketplace_id)
                ->where('is_active', true)
                ->first();

            if (!$credential) {
                return $this->notFoundResponse(
                    __('api.credential.not_found')
                );
            }

            $service = MarketplaceServiceFactory::make($credential);
            $syncResults = [];

            // Sync stock
            if ($request->get('sync_stock', true)) {
                try {
                    $result = $service->updateStock(
                        $marketplaceProduct->product->barcode,
                        $marketplaceProduct->product->stock_quantity
                    );
                    $syncResults['stock'] = 'success';
                    Log::info("Kullanici ID:{$userId} - Pazaryeri Urun ID:{$marketplaceProduct->id} - {$marketplaceProduct->marketplace->name} stok senkronize edildi - Miktar: {$marketplaceProduct->product->stock_quantity}");
                } catch (\Exception $e) {
                    $syncResults['stock'] = 'failed: ' . $e->getMessage();
                    Log::error("Kullanici ID:{$userId} - Pazaryeri Urun ID:{$marketplaceProduct->id} - {$marketplaceProduct->marketplace->name} stok senkronize edilemedi - " . $e->getMessage());
                }
            }

            // Sync price
            if ($request->get('sync_price', true)) {
                try {
                    $result = $service->updatePrice(
                        $marketplaceProduct->product->barcode,
                        $marketplaceProduct->product->sale_price
                    );
                    $syncResults['price'] = 'success';
                    Log::info("Kullanici ID:{$userId} - Pazaryeri Urun ID:{$marketplaceProduct->id} - {$marketplaceProduct->marketplace->name} fiyat senkronize edildi - Fiyat: {$marketplaceProduct->product->sale_price}");
                } catch (\Exception $e) {
                    $syncResults['price'] = 'failed: ' . $e->getMessage();
                    Log::error("Kullanici ID:{$userId} - Pazaryeri Urun ID:{$marketplaceProduct->id} - {$marketplaceProduct->marketplace->name} fiyat senkronize edilemedi - " . $e->getMessage());
                }
            }

            // Update last sync time
            $marketplaceProduct->update([
                'last_sync_at' => now(),
            ]);

            return $this->successResponse(
                [
                    'marketplace_product' => $marketplaceProduct,
                    'sync_results' => $syncResults,
                ],
                __('api.marketplace_product.sync_success')
            );
        } catch (\Exception $e) {
            $userId = Auth::id();
            Log::error("Kullanici ID:{$userId} - Senkronizasyon basarisiz - " . $e->getMessage());
            return $this->errorResponse(
                __('api.marketplace_product.sync_failed') . ': ' . $e->getMessage(),
                400
            );
        }
    }

    /**
     * Bulk push multiple products to marketplace.
     *
     * @param BulkPushProductRequest $request
     * @return JsonResponse
     */
    public function bulkPush(BulkPushProductRequest $request): JsonResponse
    {
        try {
            $userId = Auth::id();

            // Get credential
            $credential = UserMarketplaceCredential::with('marketplace')
                ->where('user_id', $userId)
                ->find($request->marketplace_credential_id);

            if (!$credential) {
                return $this->notFoundResponse(__('api.credential.not_found'));
            }

            // Get products
            $products = Product::where('user_id', $userId)
                ->whereIn('id', $request->product_ids)
                ->get();

            if ($products->isEmpty()) {
                return $this->notFoundResponse(__('api.product.not_found'));
            }

            // Initialize service
            $service = MarketplaceServiceFactory::make($credential);

            $results = [
                'successful' => [],
                'failed' => [],
                'skipped' => [],
            ];

            DB::beginTransaction();

            foreach ($products as $product) {
                try {
                    // Check if already synced
                    $existingSync = MarketplaceProduct::where('product_id', $product->id)
                        ->where('marketplace_id', $credential->marketplace_id)
                        ->where('user_id', $userId)
                        ->first();

                    if ($existingSync) {
                        Log::warning("Kullanici ID:{$userId} - Urun ID:{$product->id} - SKU:{$product->sku} - Pazaryeri ID:{$credential->marketplace_id} zaten senkronize");
                        $results['skipped'][] = [
                            'product_id' => $product->id,
                            'sku' => $product->sku,
                            'reason' => __('api.marketplace_product.already_synced'),
                        ];
                        continue;
                    }

                    // Push to marketplace
                    $response = $service->createProduct($product);

                    // Store marketplace product relationship
                    $marketplaceProduct = MarketplaceProduct::create([
                        'user_id' => $userId,
                        'product_id' => $product->id,
                        'marketplace_id' => $credential->marketplace_id,
                        'marketplace_product_id' => $response['id'] ?? null,
                        'marketplace_sku' => $response['sku'] ?? $product->sku,
                        'marketplace_barcode' => $response['barcode'] ?? $product->barcode,
                        'marketplace_status' => $response['status'] ?? 'pending',
                        'approved' => $response['approved'] ?? false,
                        'last_sync_at' => now(),
                        'metadata' => $response,
                    ]);

                    Log::info("Kullanici ID:{$userId} - Urun ID:{$product->id} - SKU:{$product->sku} - {$credential->marketplace->name} pazaryerine gonderildi");

                    $results['successful'][] = [
                        'product_id' => $product->id,
                        'sku' => $product->sku,
                        'marketplace_product_id' => $marketplaceProduct->id,
                    ];
                } catch (\Exception $e) {
                    Log::error("Kullanici ID:{$userId} - Urun ID:{$product->id} - SKU:{$product->sku} pazaryerine gonderilemedi - " . $e->getMessage());
                    $results['failed'][] = [
                        'product_id' => $product->id,
                        'sku' => $product->sku,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            DB::commit();

            Log::info("Kullanici ID:{$userId} - Toplu push - Basarili: " . count($results['successful']) . " - Basarisiz: " . count($results['failed']) . " - Atlandi: " . count($results['skipped']));

            return $this->successResponse(
                $results,
                __('api.marketplace_product.bulk_push_success')
            );
        } catch (\Exception $e) {
            DB::rollBack();
            $userId = Auth::id();
            Log::error("Kullanici ID:{$userId} - Toplu push basarisiz - " . $e->getMessage());
            return $this->errorResponse(
                __('api.marketplace_product.bulk_push_failed') . ': ' . $e->getMessage(),
                400
            );
        }
    }

    /**
     * Bulk sync stock and/or price for multiple marketplace products.
     *
     * @param BulkSyncRequest $request
     * @return JsonResponse
     */
    public function bulkSync(BulkSyncRequest $request): JsonResponse
    {
        try {
            $userId = Auth::id();

            // Get marketplace products
            $marketplaceProducts = MarketplaceProduct::with(['product', 'marketplace', 'credential'])
                ->where('user_id', $userId)
                ->whereIn('id', $request->marketplace_product_ids)
                ->get();

            if ($marketplaceProducts->isEmpty()) {
                return $this->notFoundResponse(__('api.marketplace_product.not_found'));
            }

            $results = [
                'successful' => [],
                'failed' => [],
            ];

            $syncType = $request->sync_type;

            foreach ($marketplaceProducts as $marketplaceProduct) {
                try {
                    // Get credential for this marketplace product
                    $credential = UserMarketplaceCredential::where('user_id', $userId)
                        ->where('marketplace_id', $marketplaceProduct->marketplace_id)
                        ->first();

                    if (!$credential) {
                        throw new \Exception("Credential not found for marketplace");
                    }

                    // Initialize service
                    $service = MarketplaceServiceFactory::make($credential);

                    $syncResults = [];

                    // Sync stock
                    if (in_array($syncType, ['stock', 'both'])) {
                        try {
                            $barcode = $marketplaceProduct->marketplace_barcode ?? $marketplaceProduct->product->barcode;
                            if (!$barcode) {
                                throw new \Exception("Barcode not found for product");
                            }

                            $stockResponse = $service->updateStock(
                                $barcode,
                                $marketplaceProduct->product->stock_quantity
                            );

                            $syncResults['stock'] = 'success';
                            Log::info("Kullanici ID:{$userId} - Pazaryeri Urun ID:{$marketplaceProduct->id} - {$marketplaceProduct->marketplace->name} stok senkronize edildi - Miktar: {$marketplaceProduct->product->stock_quantity}");
                        } catch (\Exception $e) {
                            $syncResults['stock'] = 'failed';
                            $syncResults['stock_error'] = $e->getMessage();
                            Log::error("Kullanici ID:{$userId} - Pazaryeri Urun ID:{$marketplaceProduct->id} - {$marketplaceProduct->marketplace->name} stok senkronize edilemedi - " . $e->getMessage());
                        }
                    }

                    // Sync price
                    if (in_array($syncType, ['price', 'both'])) {
                        try {
                            $barcode = $marketplaceProduct->marketplace_barcode ?? $marketplaceProduct->product->barcode;
                            if (!$barcode) {
                                throw new \Exception("Barcode not found for product");
                            }

                            $priceResponse = $service->updatePrice(
                                $barcode,
                                $marketplaceProduct->product->sale_price
                            );

                            $syncResults['price'] = 'success';
                            Log::info("Kullanici ID:{$userId} - Pazaryeri Urun ID:{$marketplaceProduct->id} - {$marketplaceProduct->marketplace->name} fiyat senkronize edildi - Fiyat: {$marketplaceProduct->product->sale_price}");
                        } catch (\Exception $e) {
                            $syncResults['price'] = 'failed';
                            $syncResults['price_error'] = $e->getMessage();
                            Log::error("Kullanici ID:{$userId} - Pazaryeri Urun ID:{$marketplaceProduct->id} - {$marketplaceProduct->marketplace->name} fiyat senkronize edilemedi - " . $e->getMessage());
                        }
                    }

                    // Update last sync time
                    $marketplaceProduct->update([
                        'last_sync_at' => now(),
                    ]);

                    if (isset($syncResults['stock']) && $syncResults['stock'] === 'success' ||
                        isset($syncResults['price']) && $syncResults['price'] === 'success') {
                        $results['successful'][] = [
                            'marketplace_product_id' => $marketplaceProduct->id,
                            'product_sku' => $marketplaceProduct->product->sku,
                            'sync_results' => $syncResults,
                        ];
                    } else {
                        $results['failed'][] = [
                            'marketplace_product_id' => $marketplaceProduct->id,
                            'product_sku' => $marketplaceProduct->product->sku,
                            'sync_results' => $syncResults,
                        ];
                    }
                } catch (\Exception $e) {
                    Log::error("Kullanici ID:{$userId} - Pazaryeri Urun ID:{$marketplaceProduct->id} senkronize edilemedi - " . $e->getMessage());
                    $results['failed'][] = [
                        'marketplace_product_id' => $marketplaceProduct->id,
                        'product_sku' => $marketplaceProduct->product->sku,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            Log::info("Kullanici ID:{$userId} - Toplu sync - Basarili: " . count($results['successful']) . " - Basarisiz: " . count($results['failed']));

            return $this->successResponse(
                $results,
                __('api.marketplace_product.bulk_sync_success')
            );
        } catch (\Exception $e) {
            $userId = Auth::id();
            Log::error("Kullanici ID:{$userId} - Toplu sync basarisiz - " . $e->getMessage());
            return $this->errorResponse(
                __('api.marketplace_product.bulk_sync_failed') . ': ' . $e->getMessage(),
                400
            );
        }
    }
}
