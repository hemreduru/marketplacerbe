<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\MarketplaceProduct;
use App\Models\Product;
use App\Models\UserMarketplaceCredential;
use App\Services\MarketplaceServiceFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MarketplaceOrderController extends Controller
{
    use ApiResponseTrait;

    /**
     * Display a listing of marketplace orders.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $userId = Auth::id();

            $query = MarketplaceOrder::with(['marketplace', 'items'])
                ->where('user_id', $userId);

            // Filter by marketplace
            if ($request->has('marketplace_id')) {
                $query->where('marketplace_id', $request->marketplace_id);
            }

            // Filter by status
            if ($request->has('order_status')) {
                $query->where('order_status', $request->order_status);
            }

            // Filter by date range
            if ($request->has('start_date')) {
                $query->whereDate('order_date', '>=', $request->start_date);
            }

            if ($request->has('end_date')) {
                $query->whereDate('order_date', '<=', $request->end_date);
            }

            // Search by order number or tracking number
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('marketplace_order_number', 'like', "%{$search}%")
                        ->orWhere('package_number', 'like', "%{$search}%")
                        ->orWhere('tracking_number', 'like', "%{$search}%")
                        ->orWhere('invoice_number', 'like', "%{$search}%");
                });
            }

            $orders = $query->orderBy('order_date', 'desc')
                ->paginate($request->per_page ?? 50);

            Log::info("Kullanici ID:{$userId} - {$orders->total()} siparis listelendi");

            return $this->successResponse(
                $orders,
                __('api.order.list_success')
            );
        } catch (\Exception $e) {
            return $this->serverErrorResponse(
                __('api.error'),
                $e
            );
        }
    }

    /**
     * Display the specified order.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $userId = Auth::id();

            $order = MarketplaceOrder::with(['marketplace', 'items.product', 'items.marketplaceProduct'])
                ->where('user_id', $userId)
                ->find($id);

            if (!$order) {
                return $this->notFoundResponse(
                    __('api.order.not_found')
                );
            }

            Log::info("Kullanici ID:{$userId} - Siparis ID:{$order->id} - Order No:{$order->marketplace_order_number} goruntulendi");

            return $this->successResponse(
                $order,
                __('api.order.show_success')
            );
        } catch (\Exception $e) {
            return $this->serverErrorResponse(
                __('api.error'),
                $e
            );
        }
    }

    /**
     * Fetch orders from marketplace.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function fetch(Request $request): JsonResponse
    {
        $request->validate([
            'marketplace_id' => 'required|exists:marketplaces,id',
            'page' => 'nullable|integer|min:0',
            'size' => 'nullable|integer|min:1|max:200',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|string',
        ]);

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

            Log::info("Kullanici ID:{$userId} - Pazaryeri ID:{$request->marketplace_id} - Siparis cekme basladi");

            // Initialize service
            $service = MarketplaceServiceFactory::make($credential);

            // Fetch orders from marketplace
            $filters = [
                'page' => $request->page ?? 0,
                'size' => $request->size ?? 50,
            ];

            if ($request->has('start_date')) {
                $filters['startDate'] = strtotime($request->start_date) * 1000; // Trendyol uses milliseconds
            }

            if ($request->has('end_date')) {
                $filters['endDate'] = strtotime($request->end_date) * 1000;
            }

            if ($request->has('status')) {
                $filters['status'] = $request->status;
            }

            $response = $service->getOrders($filters);

            // Process and store orders
            $importedCount = 0;
            $updatedCount = 0;
            $errors = [];

            if (isset($response['content']) && is_array($response['content'])) {
                foreach ($response['content'] as $orderData) {
                    try {
                        $result = $this->storeOrder($userId, $request->marketplace_id, $orderData);
                        if ($result === 'created') {
                            $importedCount++;
                        } else {
                            $updatedCount++;
                        }
                    } catch (\Exception $e) {
                        $errors[] = [
                            'order_number' => $orderData['orderNumber'] ?? 'unknown',
                            'error' => $e->getMessage(),
                        ];
                    }
                }
            }

            $totalOrders = count($response['content'] ?? []);
            $errorCount = count($errors);

            Log::info("Kullanici ID:{$userId} - Pazaryeri ID:{$request->marketplace_id} - {$totalOrders} siparis cekildi: {$importedCount} yeni, {$updatedCount} guncelleme, {$errorCount} hata");

            return $this->successResponse(
                [
                    'marketplace_id' => $request->marketplace_id,
                    'marketplace_name' => $credential->marketplace->name,
                    'total_orders' => $totalOrders,
                    'imported_count' => $importedCount,
                    'updated_count' => $updatedCount,
                    'error_count' => $errorCount,
                    'errors' => $errors,
                    'fetched_at' => now()->toDateTimeString(),
                ],
                __('api.order.fetch_success')
            );
        } catch (\Exception $e) {
            Log::error("Kullanici ID:{$userId} - Pazaryeri ID:{$request->marketplace_id} - Siparis cekme basarisiz - " . $e->getMessage());

            return $this->errorResponse(
                __('api.order.fetch_failed') . ': ' . $e->getMessage(),
                400
            );
        }
    }

    /**
     * Update order status.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|string|in:created,picking,invoiced,shipped,delivered,cancelled',
        ]);

        try {
            $userId = Auth::id();

            $order = MarketplaceOrder::where('user_id', $userId)->find($id);

            if (!$order) {
                return $this->notFoundResponse(
                    __('api.order.not_found')
                );
            }

            // Get credential
            $credential = UserMarketplaceCredential::where('user_id', $userId)
                ->where('marketplace_id', $order->marketplace_id)
                ->where('is_active', true)
                ->first();

            if (!$credential) {
                return $this->notFoundResponse(
                    __('api.credential.not_found')
                );
            }

            Log::info("Kullanici ID:{$userId} - Siparis ID:{$order->id} - Order No:{$order->marketplace_order_number} - Durum guncelleme basladi: {$request->status}");

            // Initialize service
            $service = MarketplaceServiceFactory::make($credential);

            // Update status on marketplace
            $response = $service->updateOrderStatus(
                $order->marketplace_order_id,
                $request->status
            );

            // Update local status
            $order->update([
                'order_status' => $request->status,
                'last_sync_at' => now(),
            ]);

            Log::info("Kullanici ID:{$userId} - Siparis ID:{$order->id} - Order No:{$order->marketplace_order_number} - Durum guncellendi: {$request->status}");

            return $this->successResponse(
                $order->fresh(['marketplace', 'items']),
                __('api.order.update_status_success')
            );
        } catch (\Exception $e) {
            Log::error("Kullanici ID:{$userId} - Siparis ID:{$id} - Durum guncelleme basarisiz - " . $e->getMessage());

            return $this->errorResponse(
                __('api.order.update_status_failed') . ': ' . $e->getMessage(),
                400
            );
        }
    }

    /**
     * Update tracking number.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateTracking(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'tracking_number' => 'required|string',
        ]);

        try {
            $userId = Auth::id();

            $order = MarketplaceOrder::where('user_id', $userId)->find($id);

            if (!$order) {
                return $this->notFoundResponse(
                    __('api.order.not_found')
                );
            }

            // Get credential
            $credential = UserMarketplaceCredential::where('user_id', $userId)
                ->where('marketplace_id', $order->marketplace_id)
                ->where('is_active', true)
                ->first();

            if (!$credential) {
                return $this->notFoundResponse(
                    __('api.credential.not_found')
                );
            }

            Log::info("Kullanici ID:{$userId} - Siparis ID:{$order->id} - Order No:{$order->marketplace_order_number} - Kargo takip guncelleme basladi: {$request->tracking_number}");

            // Initialize service
            $service = MarketplaceServiceFactory::make($credential);

            // Update tracking on marketplace
            $response = $service->updateTrackingNumber(
                $order->marketplace_order_id,
                $request->tracking_number
            );

            // Update local tracking
            $order->update([
                'tracking_number' => $request->tracking_number,
                'shipped_at' => $order->shipped_at ?? now(),
                'order_status' => 'shipped',
                'last_sync_at' => now(),
            ]);

            Log::info("Kullanici ID:{$userId} - Siparis ID:{$order->id} - Order No:{$order->marketplace_order_number} - Kargo takip guncellendi: {$request->tracking_number}");

            return $this->successResponse(
                $order->fresh(['marketplace', 'items']),
                __('api.order.update_tracking_success')
            );
        } catch (\Exception $e) {
            Log::error("Kullanici ID:{$userId} - Siparis ID:{$id} - Kargo takip guncelleme basarisiz - " . $e->getMessage());

            return $this->errorResponse(
                __('api.order.update_tracking_failed') . ': ' . $e->getMessage(),
                400
            );
        }
    }

    /**
     * Send invoice.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function sendInvoice(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'invoice_number' => 'required|string',
            'invoice_link' => 'required|url',
        ]);

        try {
            $userId = Auth::id();

            $order = MarketplaceOrder::where('user_id', $userId)->find($id);

            if (!$order) {
                return $this->notFoundResponse(
                    __('api.order.not_found')
                );
            }

            // Get credential
            $credential = UserMarketplaceCredential::where('user_id', $userId)
                ->where('marketplace_id', $order->marketplace_id)
                ->where('is_active', true)
                ->first();

            if (!$credential) {
                return $this->notFoundResponse(
                    __('api.credential.not_found')
                );
            }

            Log::info("Kullanici ID:{$userId} - Siparis ID:{$order->id} - Order No:{$order->marketplace_order_number} - Fatura gonderme basladi: {$request->invoice_number}");

            // Initialize service
            $service = MarketplaceServiceFactory::make($credential);

            // Send invoice to marketplace
            $response = $service->sendInvoice(
                $order->marketplace_order_id,
                $request->invoice_number,
                $request->invoice_link
            );

            // Update local invoice info
            $order->update([
                'invoice_number' => $request->invoice_number,
                'invoice_link' => $request->invoice_link,
                'invoiced_at' => now(),
                'order_status' => 'invoiced',
                'last_sync_at' => now(),
            ]);

            Log::info("Kullanici ID:{$userId} - Siparis ID:{$order->id} - Order No:{$order->marketplace_order_number} - Fatura gonderildi: {$request->invoice_number}");

            return $this->successResponse(
                $order->fresh(['marketplace', 'items']),
                __('api.order.send_invoice_success')
            );
        } catch (\Exception $e) {
            Log::error("Kullanici ID:{$userId} - Siparis ID:{$id} - Fatura gonderme basarisiz - " . $e->getMessage());

            return $this->errorResponse(
                __('api.order.send_invoice_failed') . ': ' . $e->getMessage(),
                400
            );
        }
    }

    /**
     * Store or update order from marketplace data.
     *
     * @param int $userId
     * @param int $marketplaceId
     * @param array $orderData
     * @return string 'created' or 'updated'
     */
    protected function storeOrder(int $userId, int $marketplaceId, array $orderData): string
    {
        DB::beginTransaction();

        try {
            // Check if order exists
            $order = MarketplaceOrder::where('marketplace_id', $marketplaceId)
                ->where('marketplace_order_id', $orderData['orderNumber'] ?? null)
                ->first();

            $isNew = !$order;

            $orderAttributes = [
                'user_id' => $userId,
                'marketplace_id' => $marketplaceId,
                'marketplace_order_id' => $orderData['orderNumber'] ?? null,
                'marketplace_order_number' => $orderData['orderNumber'] ?? null,
                'package_number' => $orderData['packageNumber'] ?? null,
                'customer_name' => $orderData['customerFirstName'] . ' ' . $orderData['customerLastName'] ?? null,
                'customer_email' => $orderData['customerEmail'] ?? null,
                'order_status' => $this->mapOrderStatus($orderData['status'] ?? 'created'),
                'shipment_status' => $orderData['shipmentPackageStatus'] ?? null,
                'total_price' => $orderData['totalPrice'] ?? 0,
                'gross_amount' => $orderData['grossAmount'] ?? 0,
                'discount_amount' => $orderData['totalDiscount'] ?? 0,
                'tax_amount' => $orderData['taxNumber'] ?? 0,
                'currency' => $orderData['currencyCode'] ?? 'TRY',
                'shipping_company' => $orderData['cargoProviderName'] ?? null,
                'tracking_number' => $orderData['cargoTrackingNumber'] ?? null,
                'shipping_address' => $orderData['shipmentAddress']['address'] ?? null,
                'shipping_city' => $orderData['shipmentAddress']['city'] ?? null,
                'shipping_district' => $orderData['shipmentAddress']['district'] ?? null,
                'order_date' => isset($orderData['orderDate']) ? date('Y-m-d H:i:s', $orderData['orderDate'] / 1000) : null,
                'shipped_at' => isset($orderData['estimatedDeliveryDate']) ? date('Y-m-d H:i:s', $orderData['estimatedDeliveryDate'] / 1000) : null,
                'last_sync_at' => now(),
                'marketplace_data' => $orderData,
            ];

            if ($order) {
                $order->update($orderAttributes);
            } else {
                $order = MarketplaceOrder::create($orderAttributes);
            }

            // Process order items (lines)
            if (isset($orderData['lines']) && is_array($orderData['lines'])) {
                foreach ($orderData['lines'] as $lineData) {
                    $this->storeOrderItem($order->id, $lineData);
                }
            }

            DB::commit();

            return $isNew ? 'created' : 'updated';
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Store or update order item.
     *
     * @param int $orderId
     * @param array $lineData
     * @return MarketplaceOrderItem
     */
    protected function storeOrderItem(int $orderId, array $lineData): MarketplaceOrderItem
    {
        // Try to find matching product by barcode
        $product = null;
        $marketplaceProduct = null;

        if (isset($lineData['barcode'])) {
            $marketplaceProduct = MarketplaceProduct::where('marketplace_sku', $lineData['barcode'])
                ->orWhere('barcode', $lineData['barcode'])
                ->first();

            if ($marketplaceProduct) {
                $product = $marketplaceProduct->product;
            } else {
                $product = Product::where('barcode', $lineData['barcode'])->first();
            }
        }

        $itemAttributes = [
            'marketplace_order_id' => $orderId,
            'product_id' => $product?->id,
            'marketplace_product_id' => $marketplaceProduct?->id,
            'marketplace_item_id' => $lineData['orderLineId'] ?? null,
            'marketplace_sku' => $lineData['merchantSku'] ?? null,
            'barcode' => $lineData['barcode'] ?? null,
            'product_name' => $lineData['productName'] ?? 'Unknown Product',
            'product_color' => $lineData['productColor'] ?? null,
            'product_size' => $lineData['productSize'] ?? null,
            'quantity' => $lineData['quantity'] ?? 1,
            'unit_price' => $lineData['price'] ?? 0,
            'total_price' => $lineData['amount'] ?? 0,
            'discount' => $lineData['discount'] ?? 0,
            'vat_amount' => $lineData['vatAmount'] ?? 0,
            'vat_rate' => $lineData['vatRate'] ?? 18,
            'currency' => $lineData['currencyCode'] ?? 'TRY',
            'commission_amount' => $lineData['commission'] ?? null,
            'item_status' => $lineData['status'] ?? null,
            'marketplace_data' => $lineData,
        ];

        // Check if item exists
        $item = MarketplaceOrderItem::where('marketplace_order_id', $orderId)
            ->where('marketplace_item_id', $lineData['orderLineId'] ?? null)
            ->first();

        if ($item) {
            $item->update($itemAttributes);
            return $item;
        }

        return MarketplaceOrderItem::create($itemAttributes);
    }

    /**
     * Map marketplace order status to our standard status.
     *
     * @param string $marketplaceStatus
     * @return string
     */
    protected function mapOrderStatus(string $marketplaceStatus): string
    {
        $statusMap = [
            'Created' => 'created',
            'Picking' => 'picking',
            'Invoiced' => 'invoiced',
            'Shipped' => 'shipped',
            'Delivered' => 'delivered',
            'Cancelled' => 'cancelled',
            'UnSupplied' => 'cancelled',
        ];

        return $statusMap[$marketplaceStatus] ?? strtolower($marketplaceStatus);
    }
}
