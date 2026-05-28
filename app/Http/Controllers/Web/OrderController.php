<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Jobs\SyncTrendyolOrdersJob;
use App\Models\Order;
use App\Services\MarketplaceManager;
use App\Services\Marketplaces\Trendyol\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function __construct(private MarketplaceManager $marketplace) {}

    protected function getService(): ?OrderService
    {
        $credential = $this->marketplace->credentialFor(Auth::user());

        if (! $credential) {
            return null;
        }

        return $this->marketplace->orderService($credential);
    }

    public function index()
    {
        return view('orders.index');
    }

    public function getData(Request $request)
    {
        $query = Order::where('user_id', Auth::id())
            ->with(['items.product', 'marketplace']);

        // Total Records (before filtering)
        $totalRecords = $query->count();

        // 1. Filtering
        // Status
        if ($request->filled('status') && $request->status != 'All') {
            $query->where('status', $request->status);
        }

        // Date Range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('order_date', [$request->start_date, $request->end_date]);
        }

        // Search
        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_first_name', 'like', "%{$search}%")
                    ->orWhere('customer_last_name', 'like', "%{$search}%");
            });
        }

        $filteredRecords = $query->count();

        // 2. Sorting
        $columns = ['order_number', 'order_date', 'customer_first_name', 'total_amount', 'status'];
        if ($request->has('order')) {
            $columnIndex = $request->input('order.0.column');
            $columnName = $columns[$columnIndex] ?? 'order_date';
            $direction = $request->input('order.0.dir');

            // Handle custom sort columns if needed
            if ($columnName == 'customer_first_name') {
                $query->orderBy('customer_first_name', $direction)->orderBy('customer_last_name', $direction);
            } else {
                $query->orderBy($columnName, $direction);
            }
        } else {
            $query->orderBy('order_date', 'desc');
        }

        // 3. Pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        $orders = $query->skip($start)->take($length)->get();

        // 4. Transform Data
        $data = [];
        foreach ($orders as $order) {
            // Order Number
            $orderNumberHtml = '<div class="d-flex flex-column">';
            $orderNumberHtml .= '<span class="fw-bold text-gray-800 fs-6">'.$order->order_number.'</span>';
            $orderNumberHtml .= '</div>';

            // Marketplace
            $marketplaceHtml = '<div class="d-flex align-items-center">';
            // Assuming we don't have logos yet, just use name/icon
            $marketplaceHtml .= '<div class="symbol symbol-35px me-2"><span class="symbol-label bg-light-warning text-warning fw-bold">'.substr($order->marketplace->name, 0, 1).'</span></div>';
            $marketplaceHtml .= '<span class="fw-bold text-gray-800">'.$order->marketplace->name.'</span>';
            $marketplaceHtml .= '</div>';

            // Date
            $dateHtml = '<div class="d-flex flex-column">';
            $dateHtml .= '<span class="fw-bold text-gray-800">'.($order->order_date ? $order->order_date->format('d.m.Y') : '-').'</span>';
            $dateHtml .= '<span class="text-muted fs-7">'.($order->order_date ? $order->order_date->format('H:i') : '').'</span>';
            $dateHtml .= '</div>';

            // Customer (Removed Email)
            $customerHtml = '<div class="d-flex flex-column">';
            $customerHtml .= '<span class="fw-bold text-gray-800">'.$order->customer_first_name.' '.$order->customer_last_name.'</span>';
            $customerHtml .= '</div>';

            // Items Summary (without quantity and SKU)
            $itemsHtml = '<div class="d-flex flex-column gap-2">';
            $totalQty = 0;
            $skuList = [];
            foreach ($order->items as $item) {
                $totalQty += $item->quantity;
                if ($item->merchant_sku) {
                    $skuList[] = $item->merchant_sku;
                }
                $product = $item->product;
                $image = null;
                if ($product && ! empty($product->images)) {
                    $firstImage = $product->images[0];
                    $image = is_array($firstImage) ? ($firstImage['url'] ?? null) : $firstImage;
                }
                $productUrl = $product ? $product->product_url : '#';

                $itemHtml = '<div class="d-flex align-items-center">';
                // Image
                if ($image) {
                    $itemHtml .= '<a href="'.$productUrl.'" target="_blank" class="symbol symbol-35px me-2">';
                    $itemHtml .= '<img src="'.$image.'" alt="'.$item->product_name.'" class="object-fit-cover" />';
                    $itemHtml .= '</a>';
                } else {
                    $itemHtml .= '<div class="symbol symbol-35px me-2 bg-light-secondary d-flex align-items-center justify-content-center">';
                    $itemHtml .= '<i class="ki-duotone ki-picture fs-4 text-gray-500"><span class="path1"></span><span class="path2"></span></i>';
                    $itemHtml .= '</div>';
                }

                // Details (removed quantity and SKU)
                $itemHtml .= '<div class="d-flex flex-column">';
                $itemHtml .= '<a href="'.$productUrl.'" target="_blank" class="text-gray-800 text-hover-primary fs-7 fw-bold line-clamp-1" title="'.$item->product_name.'">'.$item->product_name.'</a>';
                $itemHtml .= '<span class="fs-8 text-muted">'.number_format($item->price, 2).' '.$item->currency_code.'</span>';
                $itemHtml .= '</div>'; // End Details

                $itemHtml .= '</div>'; // End Item
                $itemsHtml .= $itemHtml;
            }
            $itemsHtml .= '</div>';

            // SKU Column
            $skuHtml = '<div class="d-flex flex-column gap-1">';
            foreach ($skuList as $sku) {
                $skuHtml .= '<span class="badge badge-light-info fw-bold">'.$sku.'</span>';
            }
            if (empty($skuList)) {
                $skuHtml .= '<span class="text-muted">-</span>';
            }
            $skuHtml .= '</div>';

            // Quantity Column
            $qtyHtml = '<span class="fw-bold text-gray-800">'.$totalQty.'</span>';

            // Amount (without quantity)
            $amountHtml = '<span class="fw-bold text-gray-800">'.number_format($order->total_amount, 2).' '.$order->currency_code.'</span>';

            // Status (Localized)
            $statusClass = match ($order->status) {
                'Created' => 'badge-light-primary',
                'Picking' => 'badge-light-info',
                'Invoiced' => 'badge-light-warning',
                'Shipped' => 'badge-light-success',
                'Cancelled' => 'badge-light-danger',
                'Delivered' => 'badge-light-success',
                default => 'badge-light-secondary'
            };
            $statusLabel = __('common.'.strtolower($order->status)); // Localize status

            $statusHtml = '<div class="d-flex flex-column align-items-start gap-1">';
            $statusHtml .= '<span class="badge '.$statusClass.'">'.$statusLabel.'</span>';

            if ($order->cargo_tracking_number) {
                $statusHtml .= '<div class="d-flex align-items-center mt-1">';
                $statusHtml .= '<i class="ki-duotone ki-truck fs-8 me-1 text-gray-500"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>';
                $statusHtml .= '<span class="text-gray-600 fs-8">'.$order->cargo_provider_name.'</span>';
                $statusHtml .= '</div>';
                $statusHtml .= '<span class="text-gray-500 fs-9">'.$order->cargo_tracking_number.'</span>';
            }
            $statusHtml .= '</div>';

            // Actions
            $actionsHtml = '<div class="d-flex justify-content-end gap-1">';
            if ($order->status == 'Created') {
                $actionsHtml .= '<button class="btn btn-sm btn-icon btn-light-primary update-status-btn" data-id="'.$order->id.'" data-status="Picking" title="'.__('common.picking').'">';
                $actionsHtml .= '<i class="ki-duotone ki-check fs-2"><span class="path1"></span><span class="path2"></span></i>';
                $actionsHtml .= '</button>';
            }
            if ($order->status == 'Picking') {
                $actionsHtml .= '<button class="btn btn-sm btn-icon btn-light-warning update-status-btn" data-id="'.$order->id.'" data-status="Invoiced" title="'.__('common.invoiced').'">';
                $actionsHtml .= '<i class="ki-duotone ki-file fs-2"><span class="path1"></span><span class="path2"></span></i>';
                $actionsHtml .= '</button>';
            }
            if ($order->cargo_tracking_number) {
                $actionsHtml .= '<button class="btn btn-sm btn-icon btn-light-info get-label-btn" data-tracking="'.$order->cargo_tracking_number.'" title="'.__('common.get_label').'">';
                $actionsHtml .= '<i class="ki-duotone ki-printer fs-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>';
                $actionsHtml .= '</button>';
            }
            $actionsHtml .= '</div>';

            $data[] = [
                $orderNumberHtml,
                $marketplaceHtml,
                $dateHtml,
                $customerHtml,
                $itemsHtml,
                $skuHtml, // New SKU Column
                $qtyHtml, // New Quantity Column
                $amountHtml,
                $statusHtml,
                $actionsHtml,
            ];
        }

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    public function sync()
    {
        $credential = $this->marketplace->credentialFor(Auth::user());

        if ($credential) {
            SyncTrendyolOrdersJob::dispatch($credential->id);

            return response()->json(['success' => true, 'message' => __('common.sync_started')]);
        }

        return response()->json(['success' => false, 'message' => __('common.please_connect_trendyol')], 400);
    }

    public function updateStatus(UpdateOrderStatusRequest $request)
    {
        try {
            if (! config('marketplace.write_enabled')) {
                return response()->json([
                    'success' => true,
                    'message' => __('common.action_simulated'),
                ]);
            }

            $service = $this->getService();
            if (! $service) {
                return response()->json(['success' => false, 'message' => __('common.please_connect_trendyol')], 400);
            }

            $result = $service->updateStatus($request->package_id, $request->status);

            if (! $result->ok) {
                Log::error('Order status update failed: '.$result->errorMessage);

                return response()->json(['success' => false, 'message' => $result->errorMessage], 500);
            }

            return response()->json(['success' => true, 'message' => __('common.status_updated')]);
        } catch (\Exception $e) {
            Log::error('Order status update exception: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => __('common.error_occurred')], 500);
        }
    }

    public function getLabel(Request $request)
    {
        try {
            $request->validate([
                'tracking_number' => 'required|string',
            ]);

            if (! config('marketplace.write_enabled')) {
                return response()->json([
                    'success' => true,
                    'message' => __('common.action_simulated'),
                ]);
            }

            $service = $this->getService();
            if (! $service) {
                return response()->json(['success' => false, 'message' => __('common.please_connect_trendyol')], 400);
            }

            // First create request
            $service->createCommonLabel($request->tracking_number);

            // Then retrieve (might need delay in real world, but for now direct call)
            $result = $service->getCommonLabel($request->tracking_number);

            if (! $result->ok) {
                Log::error('Label creation failed: '.$result->errorMessage);

                return response()->json(['success' => false, 'message' => $result->errorMessage], 500);
            }

            // Assuming content is PDF stream or link. For simplicity returning success for now.
            // In real impl, we would stream download.
            return response()->json(['success' => true, 'message' => __('common.label_created')]);
        } catch (\Exception $e) {
            Log::error('Label creation exception: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => __('common.error_occurred')], 500);
        }
    }
}
