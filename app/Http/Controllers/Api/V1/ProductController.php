<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Traits\ApiResponseTrait;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    use ApiResponseTrait;

    /**
     * Display a listing of products.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $userId = Auth::id();

            $query = Product::where('user_id', $userId);

            // Search
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%")
                      ->orWhere('barcode', 'like', "%{$search}%");
                });
            }

            // Filter by active status
            if ($request->has('is_active')) {
                $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
            }

            // Filter by brand
            if ($request->has('brand')) {
                $query->where('brand', $request->brand);
            }

            // Order by
            $orderBy = $request->get('order_by', 'created_at');
            $orderDir = $request->get('order_dir', 'desc');
            $query->orderBy($orderBy, $orderDir);

            // Pagination
            $perPage = $request->get('per_page', 50);
            $products = $query->paginate($perPage);

            return $this->paginatedResponse(
                $products,
                __('api.product.list_success')
            );
        } catch (\Exception $e) {
            return $this->serverErrorResponse(
                __('api.error'),
                $e
            );
        }
    }

    /**
     * Store a newly created product.
     *
     * @param StoreProductRequest $request
     * @return JsonResponse
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        try {
            $userId = Auth::id();

            // Check if SKU already exists
            $exists = Product::where('user_id', $userId)
                ->where('sku', $request->sku)
                ->exists();

            if ($exists) {
                Log::warning("Kullanici ID:{$userId} - SKU:{$request->sku} zaten mevcut");
                return $this->errorResponse(
                    __('api.product.sku_exists'),
                    409
                );
            }

            $product = Product::create([
                'user_id' => $userId,
                'sku' => $request->sku,
                'name' => $request->name,
                'description' => $request->description,
                'brand' => $request->brand,
                'barcode' => $request->barcode,
                'stock_quantity' => $request->stock_quantity ?? 0,
                'base_price' => $request->base_price,
                'sale_price' => $request->sale_price ?? $request->base_price,
                'vat_rate' => $request->vat_rate ?? config('marketplace.default_vat_rate'),
                'currency' => $request->currency ?? config('marketplace.default_currency'),
                'weight' => $request->weight,
                'dimensional_weight' => $request->dimensional_weight,
                'images' => $request->images ?? [],
                'attributes' => $request->attributes ?? [],
                'is_active' => $request->is_active ?? true,
            ]);

            Log::info("Kullanici ID:{$userId} - Urun ID:{$product->id} - SKU:{$product->sku} olusturuldu");

            return $this->createdResponse(
                $product,
                __('api.product.create_success')
            );
        } catch (\Exception $e) {
            return $this->serverErrorResponse(
                __('api.error'),
                $e
            );
        }
    }

    /**
     * Display the specified product.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $userId = Auth::id();

            $product = Product::with('marketplaceProducts.marketplace')
                ->where('user_id', $userId)
                ->find($id);

            if (!$product) {
                return $this->notFoundResponse(
                    __('api.product.not_found')
                );
            }

            return $this->successResponse(
                $product,
                __('api.product.show_success')
            );
        } catch (\Exception $e) {
            return $this->serverErrorResponse(
                __('api.error'),
                $e
            );
        }
    }

    /**
     * Update the specified product.
     *
     * @param UpdateProductRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        try {
            $userId = Auth::id();

            $product = Product::where('user_id', $userId)
                ->find($id);

            if (!$product) {
                return $this->notFoundResponse(
                    __('api.product.not_found')
                );
            }

            // Check SKU uniqueness if changed
            if ($request->has('sku') && $request->sku !== $product->sku) {
                $exists = Product::where('user_id', $userId)
                    ->where('sku', $request->sku)
                    ->where('id', '!=', $id)
                    ->exists();

                if ($exists) {
                    Log::warning("Kullanici ID:{$userId} - Urun guncelleme - SKU:{$request->sku} zaten mevcut");
                    return $this->errorResponse(
                        __('api.product.already_exists'),
                        409
                    );
                }
            }

            $product->update($request->only([
                'sku',
                'name',
                'description',
                'brand',
                'barcode',
                'stock_quantity',
                'base_price',
                'sale_price',
                'vat_rate',
                'currency',
                'weight',
                'dimensional_weight',
                'images',
                'attributes',
                'is_active',
            ]));

            Log::info("Kullanici ID:{$userId} - Urun ID:{$product->id} - SKU:{$product->sku} guncellendi");

            return $this->successResponse(
                $product,
                __('api.product.update_success')
            );
        } catch (\Exception $e) {
            return $this->serverErrorResponse(
                __('api.error'),
                $e
            );
        }
    }

    /**
     * Remove the specified product (soft delete).
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $userId = Auth::id();

            $product = Product::where('user_id', $userId)
                ->find($id);

            if (!$product) {
                return $this->notFoundResponse(
                    __('api.product.not_found')
                );
            }

            $sku = $product->sku;
            $productId = $product->id;
            $product->delete();

            Log::info("Kullanici ID:{$userId} - Urun ID:{$productId} - SKU:{$sku} soft delete yapildi");

            return $this->successResponse(
                null,
                __('api.product.delete_success')
            );
        } catch (\Exception $e) {
            return $this->serverErrorResponse(
                __('api.error'),
                $e
            );
        }
    }

    /**
     * Restore a soft deleted product.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function restore(int $id): JsonResponse
    {
        try {
            $userId = Auth::id();

            $product = Product::onlyTrashed()
                ->where('user_id', $userId)
                ->find($id);

            if (!$product) {
                return $this->notFoundResponse(
                    __('api.product.not_found')
                );
            }

            $product->restore();

            Log::info("Kullanici ID:{$userId} - Urun ID:{$product->id} - SKU:{$product->sku} restore edildi");

            return $this->successResponse(
                $product,
                __('api.product.restore_success')
            );
        } catch (\Exception $e) {
            return $this->serverErrorResponse(
                __('api.error'),
                $e
            );
        }
    }

    /**
     * Bulk create products.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkStore(Request $request): JsonResponse
    {
        $request->validate([
            'products' => 'required|array|min:1|max:100',
            'products.*.sku' => 'required|string|max:255',
            'products.*.name' => 'required|string|max:500',
            'products.*.base_price' => 'required|numeric|min:0',
        ]);

        try {
            $userId = Auth::id();
            $products = [];
            $errors = [];

            DB::beginTransaction();

            foreach ($request->products as $index => $productData) {
                try {
                    // Check if SKU exists
                    $exists = Product::where('user_id', $userId)
                        ->where('sku', $productData['sku'])
                        ->exists();

                    if ($exists) {
                        Log::warning("Kullanici ID:{$userId} - Toplu urun ekleme - SKU:{$productData['sku']} zaten mevcut");
                        $errors[] = [
                            'index' => $index,
                            'sku' => $productData['sku'],
                            'error' => 'SKU already exists',
                        ];
                        continue;
                    }

                    $product = Product::create([
                        'user_id' => $userId,
                        'sku' => $productData['sku'],
                        'name' => $productData['name'],
                        'description' => $productData['description'] ?? null,
                        'brand' => $productData['brand'] ?? null,
                        'barcode' => $productData['barcode'] ?? null,
                        'stock_quantity' => $productData['stock_quantity'] ?? 0,
                        'base_price' => $productData['base_price'],
                        'sale_price' => $productData['sale_price'] ?? $productData['base_price'],
                        'vat_rate' => $productData['vat_rate'] ?? config('marketplace.default_vat_rate'),
                        'currency' => $productData['currency'] ?? config('marketplace.default_currency'),
                        'weight' => $productData['weight'] ?? null,
                        'dimensional_weight' => $productData['dimensional_weight'] ?? null,
                        'images' => $productData['images'] ?? [],
                        'attributes' => $productData['attributes'] ?? [],
                        'is_active' => $productData['is_active'] ?? true,
                    ]);

                    $products[] = $product;
                } catch (\Exception $e) {
                    $errors[] = [
                        'index' => $index,
                        'sku' => $productData['sku'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ];
                }
            }

            DB::commit();

            Log::info("Kullanici ID:{$userId} - Toplu urun ekleme - Basarili: " . count($products) . " - Basarisiz: " . count($errors));

            return $this->successResponse(
                [
                    'created' => $products,
                    'created_count' => count($products),
                    'errors' => $errors,
                    'error_count' => count($errors),
                ],
                __('api.product.bulk_create_success', ['count' => count($products)])
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverErrorResponse(
                __('api.error'),
                $e
            );
        }
    }
}
