# FAZ 4 — ANALİTİK 2.0 + REPRICER + REKLAM YÖNETİMİ

**Hedef:** Tüm raporlar (Bölüm 10) tam, kural tabanlı repricer (ML değil), reklam ROI analizi, rakip takibi.

**Spec Ref:** Bölüm 10 (tüm raporlar), Bölüm 13 Faz 4.

**Başarı Kriteri:**
- Tüm raporlar (sipariş, stok, iade, pazaryeri karşılaştırma, KDV, reklam) export edilebilir (CSV/Excel/PDF)
- Kural tabanlı repricer test mağazada fiyat değiştirip aynı dakika tekrar çağırmıyor (15dk respekt)
- Trendyol Ads + HB Sponsor entegrasyonu ROAS/ACoS hesaplamaları
- Rakip takibi (buybox kayıp/kazanç bildirimi)

---

## PR #4.1 — `feat: order report + bulk actions`

**Spec Ref:** Bölüm 10.3

**Hedef:** Sipariş raporu (tarih, müşteri, adres, ürün adedi, tutar, statü, kargo, net kâr) + toplu işlemler (fatura, kargo, statü güncelleme)

### 1. Hazırlık

#### 1.1 Branch aç
```bash
git checkout -b feat/cirotik-pr-4-1-order-report-bulk-actions
```

#### 1.2 Baseline kontrol
```bash
php artisan test --compact
vendor/bin/pint --format agent
```

### 2. Veri Modeli & Migration

#### 2.1 `orders` tablosuna eksik alanlar ekle (varsa)
**Migration:** `2026_XX_XX_add_order_report_fields_to_orders.php`

```php
Schema::table('orders', function (Blueprint $table) {
    $table->string('shipping_city')->nullable()->after('customer_last_name');
    $table->string('shipping_country', 2)->default('TR')->after('shipping_city');
    $table->string('shipping_tracking_number')->nullable()->after('shipping_country');
    $table->unsignedBigInteger('user_marketplace_credential_id')->nullable()->after('user_id');
    $table->foreign('user_marketplace_credential_id')->references('id')->on('user_marketplace_credentials');
    $table->index(['user_id', 'order_date'], 'orders_user_date_idx');
});
```

**Not:** `user_marketplace_credential_id` zaten PR #2.5'te eklendi, migration'ı kontrol et.

### 3. Service Layer

#### 3.1 `app/Services/Reports/OrderReportService.php`

```php
<?php

namespace App\Services\Reports;

use App\Models\Order;
use App\Services\Calculations\ProfitCalculator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class OrderReportService
{
    public function __construct(private ProfitCalculator $profitCalculator) {}

    /**
     * Order report data with filters
     *
     * @param int $userId
     * @param array $filters [
     *   'date_from' => '2026-01-01',
     *   'date_to' => '2026-01-31',
     *   'marketplace' => 'trendyol',
     *   'status' => 'created',
     *   'city' => 'Istanbul',
     * ]
     * @return array
     */
    public function getReportData(int $userId, array $filters = []): array
    {
        $query = Order::where('user_id', $userId)
            ->with(['items.product', 'credential'])
            ->select([
                'orders.*',
                DB::raw('SUM(order_items.quantity) as total_items'),
                DB::raw('COUNT(order_items.id) as item_count'),
            ])
            ->leftJoin('order_items', 'orders.id', '=', 'order_items.order_id')
            ->groupBy('orders.id');

        // Apply filters
        $query = $this->applyFilters($query, $filters);

        return $query->get()->map(function ($order) {
            return $this->formatOrderRow($order);
        })->toArray();
    }

    /**
     * Get summary stats for order report
     */
    public function getSummary(int $userId, array $filters = []): array
    {
        $orders = $this->getReportData($userId, $filters);

        return [
            'total_orders' => count($orders),
            'total_revenue' => array_sum(array_column($orders, 'total_amount')),
            'total_items' => array_sum(array_column($orders, 'total_items')),
            'total_net_profit' => array_sum(array_column($orders, 'net_profit')),
            'avg_order_value' => count($orders) > 0 
                ? array_sum(array_column($orders, 'total_amount')) / count($orders) 
                : 0,
        ];
    }

    /**
     * Export to CSV
     */
    public function exportToCsv(int $userId, array $filters = []): string
    {
        $orders = $this->getReportData($userId, $filters);
        
        $csv = fopen('php://temp', 'w');
        
        // Header
        fputcsv($csv, [
            'Sipariş #', 'Pazaryeri', 'Tarih', 'Müşteri', 'Şehir', 
            'Ürün Adedi', 'Tutar', 'Net Kâr', 'Statü', 'Kargo'
        ]);
        
        // Data
        foreach ($orders as $order) {
            fputcsv($csv, [
                $order['order_number'],
                $order['marketplace_name'],
                $order['order_date'],
                $order['customer_name'],
                $order['shipping_city'],
                $order['total_items'],
                number_format($order['total_amount'], 2, ',', '.') . ' ₺',
                number_format($order['net_profit'], 2, ',', '.') . ' ₺',
                $order['status_label'],
                $order['shipping_tracking_number'] ?? '—',
            ]);
        }
        
        rewind($csv);
        $output = stream_get_contents($csv);
        fclose($csv);
        
        return $output;
    }

    private function applyFilters(Builder $query, array $filters): Builder
    {
        if (!empty($filters['date_from'])) {
            $query->whereDate('orders.order_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('orders.order_date', '<=', $filters['date_to']);
        }

        if (!empty($filters['marketplace'])) {
            $query->whereHas('credential', function ($q) use ($filters) {
                $q->whereHas('marketplace', function ($mq) use ($filters) {
                    $mq->where('slug', $filters['marketplace']);
                });
            });
        }

        if (!empty($filters['status'])) {
            $query->where('orders.status', $filters['status']);
        }

        if (!empty($filters['city'])) {
            $query->where('orders.shipping_city', 'like', '%' . $filters['city'] . '%');
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('orders.order_number', 'like', "%{$search}%")
                    ->orWhere('orders.customer_first_name', 'like', "%{$search}%")
                    ->orWhere('orders.customer_last_name', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    private function formatOrderRow(Order $order): array
    {
        // Calculate net profit for order
        $netProfit = 0;
        foreach ($order->items as $item) {
            if ($item->product && $order->credential) {
                $breakdown = $this->profitCalculator->forOrderItem($item);
                $netProfit += $breakdown->netProfit;
            }
        }

        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'marketplace_name' => $order->credential?->marketplace?->name ?? '—',
            'order_date' => $order->order_date->format('d.m.Y H:i'),
            'customer_name' => trim($order->customer_first_name . ' ' . $order->customer_last_name),
            'shipping_city' => $order->shipping_city ?? '—',
            'shipping_country' => $order->shipping_country ?? 'TR',
            'total_items' => $order->total_items ?? 0,
            'item_count' => $order->item_count ?? 0,
            'total_amount' => (float) $order->total_amount,
            'net_profit' => $netProfit,
            'status' => $order->status,
            'status_label' => __('order.status.' . $order->status),
            'shipping_tracking_number' => $order->shipping_tracking_number,
        ];
    }
}
```

### 4. Controller

#### 4.1 `app/Http/Controllers/Web/OrderReportController.php`

```php
<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Reports\OrderReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderReportController extends Controller
{
    public function __construct(private OrderReportService $service) {}

    /**
     * Order report page
     */
    public function index(Request $request)
    {
        $filters = [
            'date_from' => $request->input('date_from', now()->startOfMonth()->format('Y-m-d')),
            'date_to' => $request->input('date_to', now()->format('Y-m-d')),
            'marketplace' => $request->input('marketplace'),
            'status' => $request->input('status'),
            'city' => $request->input('city'),
        ];

        $summary = $this->service->getSummary(Auth::id(), $filters);

        return view('reports.order-report', compact('summary', 'filters'));
    }

    /**
     * DataTables server-side data
     */
    public function getData(Request $request)
    {
        $filters = [
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'marketplace' => $request->input('marketplace'),
            'status' => $request->input('status'),
            'city' => $request->input('city'),
            'search' => $request->input('search.value'),
        ];

        $orders = $this->service->getReportData(Auth::id(), $filters);

        $totalRecords = count($orders);
        $filteredRecords = $totalRecords;

        // Sorting
        if ($request->has('order')) {
            $columnIndex = $request->input('order.0.column');
            $direction = $request->input('order.0.dir', 'asc');
            
            $columns = ['order_number', 'marketplace_name', 'order_date', 'customer_name', 'total_amount', 'net_profit', 'status'];
            $sortColumn = $columns[$columnIndex] ?? 'order_date';
            
            usort($orders, function ($a, $b) use ($sortColumn, $direction) {
                $result = $a[$sortColumn] <=> $b[$sortColumn];
                return $direction === 'desc' ? -$result : $result;
            });
        }

        // Pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 25);
        $paginatedOrders = array_slice($orders, $start, $length);

        // Format for DataTables
        $data = array_map(function ($order) {
            return [
                'order_number' => $order['order_number'],
                'marketplace_name' => $order['marketplace_name'],
                'order_date' => $order['order_date'],
                'customer_name' => $order['customer_name'],
                'shipping_city' => $order['shipping_city'],
                'total_items' => $order['total_items'],
                'total_amount' => '<span class="fw-bold">@money(' . $order['total_amount'] . ')</span>',
                'net_profit' => '<span class="' . ($order['net_profit'] >= 0 ? 'text-success' : 'text-danger') . '">@money(' . $order['net_profit'] . ')</span>',
                'status' => '<span class="badge badge-light-' . $this->getStatusBadgeClass($order['status']) . '">' . $order['status_label'] . '</span>',
                'shipping_tracking_number' => $order['shipping_tracking_number'] ?? '—',
                'actions' => $this->getActionsHtml($order),
                'DT_RowId' => 'order-' . $order['id'],
            ];
        }, $paginatedOrders);

        return response()->json([
            'draw' => $request->input('draw', 1),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
            'summary' => $this->service->getSummary(Auth::id(), $filters),
        ]);
    }

    /**
     * Export to CSV
     */
    public function export(Request $request)
    {
        $filters = [
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'marketplace' => $request->input('marketplace'),
            'status' => $request->input('status'),
            'city' => $request->input('city'),
        ];

        $csv = $this->service->exportToCsv(Auth::id(), $filters);
        $filename = 'siparis-raporu-' . now()->format('Y-m-d') . '.csv';

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    private function getStatusBadgeClass(string $status): string
    {
        return match($status) {
            'created', 'pending' => 'warning',
            'delivered', 'completed' => 'success',
            'cancelled' => 'danger',
            default => 'secondary',
        };
    }

    private function getActionsHtml(array $order): string
    {
        return view('partials.datatables.order-actions', ['order' => $order])->render();
    }
}
```

### 5. Routes

#### 5.1 `routes/web.php` içine ekle

```php
use App\Http\Controllers\Web\OrderReportController;

Route::middleware(['auth'])->group(function () {
    // ... mevcut routes
    
    // Order Report
    Route::get('/reports/orders', [OrderReportController::class, 'index'])->name('reports.orders');
    Route::get('/reports/orders/data', [OrderReportController::class, 'getData'])->name('reports.orders.data');
    Route::get('/reports/orders/export', [OrderReportController::class, 'export'])->name('reports.orders.export');
});
```

### 6. Blade View

#### 6.1 `resources/views/reports/order-report.blade.php`

```blade
@extends('layouts.master')

@section('title', __('reports.order_report'))

@section('content')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">{{ __('reports.order_report') }}</h3>
        <div class="card-toolbar">
            <a href="{{ route('reports.orders.export', request()->all()) }}" class="btn btn-light-primary">
                <i class="ki-duotone ki-excel fs-2">
                    <span class="path1"></span><span class="path2"></span>
                </i>
                {{ __('common.export_csv') }}
            </a>
        </div>
    </div>
    
    <div class="card-body">
        <!-- Filters -->
        <form method="GET" action="{{ route('reports.orders') }}" class="mb-6">
            <div class="row g-4">
                <div class="col-md-3">
                    <label class="form-label">{{ __('common.date_from') }}</label>
                    <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('common.date_to') }}</label>
                    <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('common.marketplace') }}</label>
                    <select name="marketplace" class="form-select">
                        <option value="">{{ __('common.all') }}</option>
                        @foreach(['trendyol', 'hepsiburada', 'n11', 'pazarama', 'amazon'] as $mp)
                            <option value="{{ $mp }}" {{ $filters['marketplace'] === $mp ? 'selected' : '' }}>
                                {{ __('marketplace.' . $mp) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('common.status') }}</label>
                    <select name="status" class="form-select">
                        <option value="">{{ __('common.all') }}</option>
                        @foreach(['created', 'delivered', 'cancelled', 'returned'] as $status)
                            <option value="{{ $status }}" {{ $filters['status'] === $status ? 'selected' : '' }}>
                                {{ __('order.status.' . $status) }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="row mt-4">
                <div class="col-md-6">
                    <input type="text" name="city" class="form-control" placeholder="{{ __('common.city') }}" value="{{ $filters['city'] }}">
                </div>
                <div class="col-md-6 text-end">
                    <button type="submit" class="btn btn-primary">{{ __('common.filter') }}</button>
                    <a href="{{ route('reports.orders') }}" class="btn btn-light">{{ __('common.reset') }}</a>
                </div>
            </div>
        </form>

        <!-- Summary Cards -->
        <div class="row g-4 mb-6">
            <div class="col-md-3">
                <div class="card card-flush h-100">
                    <div class="card-body pt-6">
                        <div class="d-flex flex-stack">
                            <div>
                                <div class="fs-2 fw-bold text-gray-800">{{ $summary['total_orders'] }}</div>
                                <div class="text-gray-500 fs-7">{{ __('reports.total_orders') }}</div>
                            </div>
                            <div class="symbol symbol-40px">
                                <span class="symbol-label bg-light-primary">
                                    <i class="ki-duotone ki-shopping-cart fs-1 text-primary"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-flush h-100">
                    <div class="card-body pt-6">
                        <div class="d-flex flex-stack">
                            <div>
                                <div class="fs-2 fw-bold text-gray-800">@money($summary['total_revenue'])</div>
                                <div class="text-gray-500 fs-7">{{ __('reports.total_revenue') }}</div>
                            </div>
                            <div class="symbol symbol-40px">
                                <span class="symbol-label bg-light-success">
                                    <i class="ki-duotone ki-currency-dollar fs-1 text-success"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-flush h-100">
                    <div class="card-body pt-6">
                        <div class="d-flex flex-stack">
                            <div>
                                <div class="fs-2 fw-bold {{ $summary['total_net_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                                    @money($summary['total_net_profit'])
                                </div>
                                <div class="text-gray-500 fs-7">{{ __('reports.total_net_profit') }}</div>
                            </div>
                            <div class="symbol symbol-40px">
                                <span class="symbol-label bg-light-{{ $summary['total_net_profit'] >= 0 ? 'success' : 'danger' }}">
                                    <i class="ki-duotone ki-chart-line-up fs-1 text-{{ $summary['total_net_profit'] >= 0 ? 'success' : 'danger' }}"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-flush h-100">
                    <div class="card-body pt-6">
                        <div class="d-flex flex-stack">
                            <div>
                                <div class="fs-2 fw-bold text-gray-800">@money($summary['avg_order_value'])</div>
                                <div class="text-gray-500 fs-7">{{ __('reports.avg_order_value') }}</div>
                            </div>
                            <div class="symbol symbol-40px">
                                <span class="symbol-label bg-light-info">
                                    <i class="ki-duotone ki-calculator fs-1 text-info"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- DataTable -->
        <table class="table table-row-bordered table-row-gray-300 align-middle gs-6 gy-4" id="orderReportTable">
            <thead>
                <tr class="fw-bold text-muted bg-light">
                    <th>{{ __('order.order_number') }}</th>
                    <th>{{ __('common.marketplace') }}</th>
                    <th>{{ __('order.order_date') }}</th>
                    <th>{{ __('order.customer') }}</th>
                    <th>{{ __('order.shipping_city') }}</th>
                    <th class="text-end">{{ __('order.total_items') }}</th>
                    <th class="text-end">{{ __('order.total_amount') }}</th>
                    <th class="text-end">{{ __('reports.net_profit') }}</th>
                    <th>{{ __('common.status') }}</th>
                    <th>{{ __('order.tracking_number') }}</th>
                    <th class="text-end">{{ __('common.actions') }}</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    $('#orderReportTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("reports.orders.data") }}',
            data: function(d) {
                d.date_from = '{{ $filters["date_from"] }}';
                d.date_to = '{{ $filters["date_to"] }}';
                d.marketplace = '{{ $filters["marketplace"] }}';
                d.status = '{{ $filters["status"] }}';
                d.city = '{{ $filters["city"] }}';
            }
        },
        columns: [
            { data: 'order_number' },
            { data: 'marketplace_name' },
            { data: 'order_date' },
            { data: 'customer_name' },
            { data: 'shipping_city' },
            { data: 'total_items', className: 'text-end' },
            { data: 'total_amount', className: 'text-end' },
            { data: 'net_profit', className: 'text-end' },
            { data: 'status' },
            { data: 'shipping_tracking_number' },
            { data: 'actions', className: 'text-end', orderable: false }
        ],
        order: [[2, 'desc']],
        pageLength: 25,
        language: {
            url: '{{ asset("lang/tr/datatable.json") }}'
        }
    });
});
</script>
@endpush
```

#### 6.2 `resources/views/partials/datatables/order-actions.blade.php`

```blade
<div class="d-flex justify-content-end gap-2">
    <a href="#" class="btn btn-sm btn-icon btn-bg-light btn-active-color-primary" title="Detay">
        <i class="ki-duotone ki-eye fs-1">
            <span class="path1"></span><span class="path2"></span><span class="path3"></span>
        </i>
    </a>
    @if($order['status'] === 'created')
        <button class="btn btn-sm btn-icon btn-bg-light btn-active-color-primary bulk-invoice" data-order-id="{{ $order['id'] }}" title="Fatura Kes">
            <i class="ki-duotone ki-document fs-1">
                <span class="path1"></span><span class="path2"></span>
            </i>
        </button>
    @endif
</div>
```

### 7. Translations

#### 7.1 `lang/tr/reports.php`

```php
<?php

return [
    'order_report' => 'Sipariş Raporu',
    'stock_report' => 'Stok Raporu',
    'return_analysis' => 'İade Analiz Raporu',
    'marketplace_comparison' => 'Pazaryeri Karşılaştırma',
    'vat_report' => 'KDV & Vergi Raporu',
    'ad_performance' => 'Reklam Performans Raporu',
    'reconciliation' => 'Mutabakat Raporu',
    'total_orders' => 'Toplam Sipariş',
    'total_revenue' => 'Toplam Ciro',
    'total_net_profit' => 'Toplam Net Kâr',
    'avg_order_value' => 'Ort. Sipariş Değeri',
    'net_profit' => 'Net Kâr',
];
```

#### 7.2 `lang/en/reports.php`

```php
<?php

return [
    'order_report' => 'Order Report',
    'stock_report' => 'Stock Report',
    'return_analysis' => 'Return Analysis Report',
    'marketplace_comparison' => 'Marketplace Comparison',
    'vat_report' => 'VAT & Tax Report',
    'ad_performance' => 'Ad Performance Report',
    'reconciliation' => 'Reconciliation Report',
    'total_orders' => 'Total Orders',
    'total_revenue' => 'Total Revenue',
    'total_net_profit' => 'Total Net Profit',
    'avg_order_value' => 'Avg. Order Value',
    'net_profit' => 'Net Profit',
];
```

### 8. Tests

#### 8.1 `tests/Feature/Reports/OrderReportTest.php`

```php
<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\UserMarketplaceCredential;
use App\Models\Marketplace;
use App\Services\Reports\OrderReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->marketplace = Marketplace::where('slug', 'trendyol')->first();
    $this->credential = UserMarketplaceCredential::factory()->create([
        'user_id' => $this->user->id,
        'marketplace_id' => $this->marketplace->id,
    ]);
});

it('generates order report data', function () {
    $product = Product::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $this->user->id,
        'user_marketplace_credential_id' => $this->credential->id,
        'order_date' => now()->subDays(5),
        'total_amount' => 1000,
    ]);
    
    OrderItem::factory()->count(3)->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $service = app(OrderReportService::class);
    $data = $service->getReportData($this->user->id);

    expect($data)->toHaveCount(1)
        ->and($data[0]['order_number'])->toBe($order->order_number)
        ->and($data[0]['total_items'])->toBe(6); // 3 items × 2 qty
});

it('filters orders by date range', function () {
    Order::factory()->create([
        'user_id' => $this->user->id,
        'order_date' => now()->subDays(10),
    ]);
    
    Order::factory()->create([
        'user_id' => $this->user->id,
        'order_date' => now()->subDays(2),
    ]);

    $service = app(OrderReportService::class);
    $data = $service->getReportData($this->user->id, [
        'date_from' => now()->subDays(5)->format('Y-m-d'),
        'date_to' => now()->format('Y-m-d'),
    ]);

    expect($data)->toHaveCount(1);
});

it('calculates summary stats', function () {
    Order::factory()->count(5)->create([
        'user_id' => $this->user->id,
        'total_amount' => 100,
    ]);

    $service = app(OrderReportService::class);
    $summary = $service->getSummary($this->user->id);

    expect($summary['total_orders'])->toBe(5)
        ->and($summary['total_revenue'])->toBe(500.0);
});

it('exports to CSV', function () {
    Order::factory()->create([
        'user_id' => $this->user->id,
        'order_number' => 'TEST123',
    ]);

    $service = app(OrderReportService::class);
    $csv = $service->exportToCsv($this->user->id);

    expect($csv)->toContain('TEST123')
        ->and($csv)->toContain('Sipariş #');
});

it('isolates user data', function () {
    $otherUser = User::factory()->create();
    
    Order::factory()->create(['user_id' => $this->user->id]);
    Order::factory()->create(['user_id' => $otherUser->id]);

    $service = app(OrderReportService::class);
    $data = $service->getReportData($this->user->id);

    expect($data)->toHaveCount(1);
});

it('renders order report page', function () {
    $response = $this->actingAs($this->user)
        ->get(route('reports.orders'));

    $response->assertOk()
        ->assertSee('Sipariş Raporu');
});

it('returns datatables json', function () {
    Order::factory()->count(3)->create([
        'user_id' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson(route('reports.orders.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ]));

    $response->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure(['draw', 'recordsTotal', 'recordsFiltered', 'data', 'summary']);
});

it('exports CSV file', function () {
    Order::factory()->create(['user_id' => $this->user->id]);

    $response = $this->actingAs($this->user)
        ->get(route('reports.orders.export'));

    $response->assertOk()
        ->assertHeader('content-type', 'text/csv');
});
```

### 9. Kapanış Ritüeli

```bash
# 1. Code formatting
vendor/bin/pint --dirty --format agent

# 2. Run tests
php artisan test --compact --filter=OrderReportTest

# 3. Run all tests (baseline)
php artisan test --compact

# 4. PHPStan
vendor/bin/phpstan analyse

# 5. Migration test
php artisan migrate:fresh
php artisan migrate:rollback
php artisan migrate

# 6. Update implementation plan
# docs/CIROTIK_IMPLEMENTATION_PLAN.md'de PR #4.1 satırını [x] olarak işaretle

# 7. Commit
git add .
git commit -m "feat: order report with filters, summary, and CSV export"
git push -u origin feat/cirotik-pr-4-1-order-report-bulk-actions
```

---

## PR #4.2 — `feat: stock report + PO list`

**Spec Ref:** Bölüm 10.4

**Hedef:** Stok raporu (SKU, ürün, kategori, mevcut stok, listelenen stok, son satış tarihi, satış hızı, stok bitme tahmini) + toplu satın alma listesi (PO)

### Görevler

1. **Service:** `app/Services/Reports/StockReportService.php`
   - `getReportData(userId, filters)` — master_products + marketplace_listings JOIN
   - `getStockOutPrediction(userId)` — son 30 gün satış hızına göre tahmin
   - `generatePurchaseOrder(userId, filters)` — PO listesi (CSV)

2. **Controller:** `app/Http/Controllers/Web/StockReportController.php`
   - `index()` — sayfa render
   - `getData()` — DataTables server-side
   - `export()` — CSV export
   - `purchaseOrder()` — PO listesi

3. **View:** `resources/views/reports/stock-report.blade.php`
   - Filtreler: "Kritik altı", "0 olan", "1 yıldır satılmayan (dead stock)"
   - Sütunlar: SKU, Ürün, Kategori, Mevcut Stok, Listelenen Stok (her pazaryeri için ayrı), Son satış tarihi, Satış hızı/gün, Stok bitme tahmini
   - Aksiyon: "Toplu satın alma listesi oluştur" butonu

4. **Migration (varsa):** `master_products` tablosuna `sales_velocity` (float), `last_sold_at` (datetime) alanları

5. **Routes:** `/reports/stock`, `/reports/stock/data`, `/reports/stock/export`, `/reports/stock/po`

6. **Translations:** `lang/{tr,en}/reports.php` güncelle

7. **Tests:** `tests/Feature/Reports/StockReportTest.php` (8+ senaryo)

---

## PR #4.3 — `feat: return analysis report`

**Spec Ref:** Bölüm 10.5

**Hedef:** İade analiz raporu (SKU, ürün, satış adet, iade adet, iade oranı, en sık iade nedenleri, iade maliyeti, net gerçek kâr)

### Görevler

1. **Service:** `app/Services/Reports/ReturnAnalysisService.php`
   - `getReportData(userId, filters)` — claims + orders JOIN
   - `getReturnReasons(userId, sku)` — top 3 iade nedeni
   - `getReturnCost(userId, filters)` — toplam iade maliyeti

2. **Controller:** `app/Http/Controllers/Web/ReturnAnalysisController.php`
   - `index()`, `getData()`, `export()`

3. **View:** `resources/views/reports/return-analysis.blade.php`
   - Sütunlar: SKU, Ürün, Satış adet, İade adet, İade oranı, En sık iade nedenleri (top 3), İade maliyeti toplamı, "Net" gerçek kâr
   - Drill-down: İade nedeni gruplama (beden, kalite, görsel uyumsuzluğu, vs.)

4. **Routes:** `/reports/returns`, `/reports/returns/data`, `/reports/returns/export`

5. **Translations:** `lang/{tr,en}/reports.php` güncelle

6. **Tests:** `tests/Feature/Reports/ReturnAnalysisTest.php` (6+ senaryo)

---

## PR #4.4 — `feat: marketplace comparison pivot`

**Spec Ref:** Bölüm 10.6

**Hedef:** "Aynı SKU farklı pazaryerlerinde nasıl performe ediyor?" pivot tablosu

### Görevler

1. **Service:** `app/Services/Reports/MarketplaceComparisonService.php`
   - `getPivotData(userId, filters)` — SKU × Pazaryeri pivot

2. **Controller:** `app/Http/Controllers/Web/MarketplaceComparisonController.php`
   - `index()`, `getData()`, `export()`

3. **View:** `resources/views/reports/marketplace-comparison.blade.php`
   - Pivot tablo: Satırlar SKU, Sütunlar Trendyol Satış / HB Satış / N11 Satış / TY Kâr / HB Kâr / N11 Kâr
   - Mini grafikler (margin, hız)

4. **Routes:** `/reports/marketplace-comparison`, `/reports/marketplace-comparison/data`, `/reports/marketplace-comparison/export`

5. **Translations:** `lang/{tr,en}/reports.php` güncelle

6. **Tests:** `tests/Feature/Reports/MarketplaceComparisonTest.php` (5+ senaryo)

---

## PR #4.5 — `feat: VAT/tax monthly report (accountant export)`

**Spec Ref:** Bölüm 10.7

**Hedef:** Muhasebeciye verilen aylık KDV & vergi dosyası

### Görevler

1. **Service:** `app/Services/Reports/VatReportService.php`
   - `getMonthlyReport(userId, yearMonth)` — NetVatLiability + detaylar
   - `exportToExcel(userId, yearMonth)` — Excel export (her ürün için ayrı satır)

2. **Controller:** `app/Http/Controllers/Web/VatReportController.php`
   - `index()`, `getData()`, `export()`

3. **View:** `resources/views/reports/vat-report.blade.php`
   - Ay seçici
   - Çıktı: Toplam satış KDV (devlete borç), Alış KDV (alacak), Komisyon KDV (alacak), Kargo KDV (alacak), Net KDV (borç/alacak)
   - Excel export butonu

4. **Routes:** `/reports/vat`, `/reports/vat/data`, `/reports/vat/export`

5. **Translations:** `lang/{tr,en}/reports.php` güncelle

6. **Tests:** `tests/Feature/Reports/VatReportTest.php` (6+ senaryo)

---

## PR #4.6 — `feat: ad performance report (TY Ads + HB Sponsor)`

**Spec Ref:** Bölüm 10.8, Bölüm 9.13

**Hedef:** Reklam performans raporu (kampanya adı, pazaryeri, harcama, atfedilen ciro, ROAS, ACoS, net kâr katkısı)

### Görevler

1. **Migration:** `manual_ad_costs` tablosu
   ```php
   Schema::create('manual_ad_costs', function (Blueprint $table) {
       $table->id();
       $table->unsignedBigInteger('user_id');
       $table->unsignedBigInteger('user_marketplace_credential_id');
       $table->string('campaign_name');
       $table->string('campaign_id')->nullable();
       $table->date('date');
       $table->decimal('spend', 15, 4);
       $table->decimal('attributed_revenue', 15, 4)->default(0);
       $table->timestamps();
       
       $table->foreign('user_id')->references('id')->on('users');
       $table->foreign('user_marketplace_credential_id')->references('id')->on('user_marketplace_credentials');
       $table->unique(['user_marketplace_credential_id', 'campaign_id', 'date'], 'ad_costs_unique');
   });
   ```

2. **Service:** `app/Services/Reports/AdPerformanceService.php`
   - `getReportData(userId, filters)` — manual_ad_costs + ProfitCalculator
   - `calculateROAS(spend, revenue)` — ROAS
   - `calculateACoS(spend, revenue)` — ACoS

3. **Controller:** `app/Http/Controllers/Web/AdPerformanceController.php`
   - `index()`, `getData()`, `export()`

4. **View:** `resources/views/reports/ad-performance.blade.php`
   - Sütunlar: Kampanya adı, Pazaryeri, Period, Harcama, Atfedilen ciro, ROAS, ACoS, Net Kâr katkısı
   - "Bu kampanya kâr getiriyor mu?" sorusunu açıkça cevaplar (kırmızı/yeşil işaretler)

5. **Routes:** `/reports/ads`, `/reports/ads/data`, `/reports/ads/export`

6. **Translations:** `lang/{tr,en}/reports.php` güncelle

7. **Tests:** `tests/Feature/Reports/AdPerformanceTest.php` (6+ senaryo)

---

## PR #4.7 — `feat: sales geography + hourly heatmap + cohort + LTM trend`

**Spec Ref:** Bölüm 10.10–10.14

**Hedef:** Satış coğrafyası, saat & gün performansı, cohort analizi, kâr trendi (LTM, YTD)

### Görevler

1. **Service:** `app/Services/Reports/AdvancedAnalyticsService.php`
   - `getGeographyData(userId, filters)` — şehir/bölge sipariş yoğunluğu
   - `getHourlyHeatmap(userId, filters)` — 7×24 ısı haritası
   - `getCohortData(userId, filters)` — yeni listing performansı
   - `getLtmTrend(userId)` — son 12 ay kâr trendi

2. **Controller:** `app/Http/Controllers/Web/AdvancedAnalyticsController.php`
   - `geography()`, `heatmap()`, `cohort()`, `trend()`

3. **Views:**
   - `resources/views/reports/geography.blade.php` — harita + top 10 şehir
   - `resources/views/reports/heatmap.blade.php` — 7×24 ısı haritası
   - `resources/views/reports/cohort.blade.php` — cohort tablosu
   - `resources/views/reports/trend.blade.php` — LTM/YTD grafiği

4. **Routes:** `/reports/geography`, `/reports/heatmap`, `/reports/cohort`, `/reports/trend`

5. **Translations:** `lang/{tr,en}/reports.php` güncelle

6. **Tests:** `tests/Feature/Reports/AdvancedAnalyticsTest.php` (8+ senaryo)

---

## PR #4.8 — `feat: competitor tracker (Trendyol buybox)`

**Spec Ref:** Bölüm 11.1 `buybox_loss`

**Hedef:** Rakip takibi (buybox kayıp/kazanç bildirimi)

### Görevler

1. **Migration:** `competitor_prices` tablosu
   ```php
   Schema::create('competitor_prices', function (Blueprint $table) {
       $table->id();
       $table->unsignedBigInteger('master_product_id');
       $table->unsignedBigInteger('user_marketplace_credential_id');
       $table->string('competitor_name');
       $table->string('competitor_sku')->nullable();
       $table->decimal('price', 15, 4);
       $table->boolean('is_buybox_winner')->default(false);
       $table->timestamp('checked_at');
       $table->timestamps();
       
       $table->foreign('master_product_id')->references('id')->on('master_products');
       $table->foreign('user_marketplace_credential_id')->references('id')->on('user_marketplace_credentials');
       $table->index(['master_product_id', 'checked_at'], 'competitor_prices_product_date_idx');
   });
   ```

2. **Service:** `app/Services/Marketplaces/Trendyol/BuyboxService.php`
   - `checkBuybox(credential, productId)` — Trendyol API çağrısı
   - `recordCompetitorPrice(masterProductId, competitorData)` — kayıt

3. **Job:** `app/Jobs/CheckBuyboxJob.php`
   - Her 15 dakikada bir (Trendyol cooldown)
   - Buybox kaybı varsa `buybox_loss` notification dispatch

4. **Notification:** `app/Notifications/BuyboxLossNotification.php`
   - Mail + in-app

5. **Routes:** `/competitors`, `/competitors/data`

6. **Translations:** `lang/{tr,en}/competitors.php`

7. **Tests:** `tests/Feature/CompetitorTrackerTest.php` (6+ senaryo)

---

## PR #4.9 — `feat: rule-based repricer`

**Spec Ref:** Bölüm 13 Faz 4

**Hedef:** Kural tabanlı repricer (min/max fiyat, rakip baz, hedef marj kuralları)

### Görevler

1. **Migration:** `repricer_rules` tablosu
   ```php
   Schema::create('repricer_rules', function (Blueprint $table) {
       $table->id();
       $table->unsignedBigInteger('user_id');
       $table->unsignedBigInteger('master_product_id')->nullable(); // null = tüm ürünler
       $table->enum('rule_type', ['competitor_based', 'margin_based', 'fixed_price']);
       $table->json('rule_config'); // {min_price, max_price, target_margin, competitor_offset}
       $table->boolean('is_active')->default(true);
       $table->timestamp('last_applied_at')->nullable();
       $table->timestamps();
       
       $table->foreign('user_id')->references('id')->on('users');
       $table->foreign('master_product_id')->references('id')->on('master_products');
   });
   ```

2. **Service:** `app/Services/Repricing/RepricerService.php`
   - `applyRules(userId)` — kuralları uygula
   - `calculateNewPrice(rule, currentPrice, competitorPrices)` — yeni fiyat hesapla
   - `dispatchPriceUpdate(masterProduct, newPrice)` — SyncDispatchEntry oluştur

3. **Job:** `app/Jobs/ApplyRepricerRulesJob.php`
   - Her 15 dakikada bir (Trendyol cooldown)
   - `withoutOverlapping()` ile duplicate önleme

4. **Controller:** `app/Http/Controllers/Web/RepricerController.php`
   - `index()`, `store()`, `update()`, `delete()`, `apply()`

5. **View:** `resources/views/repricer/index.blade.php`
   - Kural listesi, yeni kural ekleme formu

6. **Routes:** `/repricer`, `/repricer/rules`, `/repricer/apply`

7. **Translations:** `lang/{tr,en}/repricer.php`

8. **Tests:** `tests/Feature/RepricerTest.php` (8+ senaryo)
   - 15dk cooldown respekt ediyor mu?
   - Min/max fiyat sınırları
   - Rakip bazlı kural

---

## PR #4.10 — `feat: UPS + DHL cargo (e-export)`

**Spec Ref:** Bölüm 8.1 (UPS REST), Bölüm 13 Faz 4

**Hedef:** UPS ve DHL kargo entegrasyonu (e-ihracat için)

### Görevler

1. **Service:** `app/Services/Cargo/Ups/UpsService.php`
   - REST API (SOAP değil)
   - `createShipment()`, `cancelShipment()`, `getLabel()`, `track()`
   - `implements CargoProvider`

2. **Service:** `app/Services/Cargo/Dhl/DhlService.php`
   - REST API
   - `createShipment()`, `cancelShipment()`, `getLabel()`, `track()`
   - `implements CargoProvider`

3. **Config:** `config/cargo.php` güncelle
   - UPS + DHL tanımı (enabled=false)

4. **Tests:** `tests/Feature/Cargo/UpsDhlTest.php` (4+ senaryo)

---

## FAZ 4 KAPANİŞ

- [ ] Tüm raporlar export edilebilir (CSV/Excel/PDF)
- [ ] Repricer test mağazada fiyat değiştirip aynı dakika tekrar çağırmıyor (15dk respekt)
- [ ] Buybox takibi aktif
- [ ] `php artisan test --compact` yeşil (~250+ test)
- [ ] PHPStan level 6 hatasız
- [ ] `.env.example` güncel
- [ ] `lang/{en,tr}/reports.php` tam

---

## EK — ÖNCELİK SIRASI

Faz 4 PR'larını bu sırayla uygula:

1. **PR #4.1** — Order Report + Bulk Actions (temel, diğer raporlar için şablon)
2. **PR #4.2** — Stock Report + PO List (stok yönetimi kritik)
3. **PR #4.3** — Return Analysis (iade maliyeti önemli)
4. **PR #4.5** — VAT Report (muhasebe için acil)
5. **PR #4.4** — Marketplace Comparison (stratejik karar)
6. **PR #4.7** — Advanced Analytics (coğrafya, heatmap, cohort, trend)
7. **PR #4.8** — Competitor Tracker (buybox)
8. **PR #4.9** — Repricer (otomasyon)
9. **PR #4.6** — Ad Performance (reklam entegrasyonu)
10. **PR #4.10** — UPS + DHL (e-ihracat, opsiyonel)

---

*FAZ 4 — Cirotik Implementation Roadmap — 2026-05-29*
