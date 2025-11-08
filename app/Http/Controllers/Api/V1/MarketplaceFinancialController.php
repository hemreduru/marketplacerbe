<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceCargoInvoice;
use App\Models\MarketplaceCargoInvoiceItem;
use App\Models\MarketplaceOtherFinancial;
use App\Models\MarketplaceSettlement;
use App\Models\UserMarketplaceCredential;
use App\Services\MarketplaceServiceFactory;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MarketplaceFinancialController extends Controller
{
    /**
     * CHE API has 15-day maximum range limit.
     * This method chunks date ranges into 15-day intervals.
     *
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    protected function chunkDateRange(string $startDate, string $endDate): array
    {
        $chunks = [];
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        while ($start < $end) {
            $chunkEnd = (clone $start)->addDays(14); // 15 days (0-14)
            if ($chunkEnd > $end) {
                $chunkEnd = $end;
            }

            $chunks[] = [
                'startDate' => $start->format('Y-m-d'),
                'endDate' => $chunkEnd->format('Y-m-d'),
            ];

            $start = (clone $chunkEnd)->addDay();
        }

        return $chunks;
    }

    /**
     * List settlements with filters.
     */
    public function listSettlements(Request $request): JsonResponse
    {
        $query = MarketplaceSettlement::with('marketplace:id,name,slug')
            ->where('user_id', 1); // TODO: auth()->id()

        // Filter by marketplace
        if ($request->has('marketplace_id')) {
            $query->where('marketplace_id', $request->marketplace_id);
        }

        // Filter by transaction type
        if ($request->has('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        // Date range filter
        if ($request->has('start_date')) {
            $query->whereDate('transaction_date', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->whereDate('transaction_date', '<=', $request->end_date);
        }

        // Filter by order number
        if ($request->has('order_number')) {
            $query->where('order_number', $request->order_number);
        }

        $perPage = min($request->get('per_page', 50), 500);
        $settlements = $query->orderByDesc('transaction_date')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Finansal işlemler başarıyla getirildi',
            'data' => $settlements,
        ]);
    }

    /**
     * Fetch settlements from marketplace API and store locally.
     */
    public function fetchSettlements(Request $request): JsonResponse
    {
        $request->validate([
            'marketplace_id' => 'required|exists:marketplaces,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        try {
            $userId = 1; // TODO: auth()->id()
            $credential = UserMarketplaceCredential::where('user_id', $userId)
                ->where('marketplace_id', $request->marketplace_id)
                ->where('is_active', true)
                ->firstOrFail();

            $service = MarketplaceServiceFactory::make($credential);

            // Chunk date range into 15-day intervals
            $chunks = $this->chunkDateRange($request->start_date, $request->end_date);
            $totalFetched = 0;

            foreach ($chunks as $chunk) {
                $filters = [
                    'startDate' => $chunk['startDate'],
                    'endDate' => $chunk['endDate'],
                    'page' => 0,
                    'size' => 200,
                ];

                $hasMore = true;
                while ($hasMore) {
                    $response = $service->getSettlements($filters);

                    if (empty($response['content'])) {
                        $hasMore = false;
                        continue;
                    }

                    foreach ($response['content'] as $item) {
                        MarketplaceSettlement::updateOrCreate(
                            [
                                'user_id' => $userId,
                                'marketplace_id' => $request->marketplace_id,
                                'marketplace_order_id' => $item['id'] ?? null,
                                'transaction_date' => isset($item['transactionDate'])
                                    ? Carbon::createFromTimestampMs($item['transactionDate'])
                                    : null,
                            ],
                            [
                                'transaction_type' => $item['transactionType'] ?? null,
                                'payment_date' => isset($item['paymentDate'])
                                    ? Carbon::createFromTimestampMs($item['paymentDate'])
                                    : null,
                                'order_number' => $item['orderNumber'] ?? null,
                                'package_id' => $item['packageId'] ?? null,
                                'barcode' => $item['barcode'] ?? null,
                                'credit' => $item['credit'] ?? 0,
                                'debt' => $item['debt'] ?? 0,
                                'commission_amount' => $item['commissionAmount'] ?? 0,
                                'seller_revenue' => $item['sellerRevenue'] ?? 0,
                                'store_id' => $item['storeId'] ?? null,
                                'payment_order_id' => $item['paymentOrderId'] ?? null,
                                'marketplace_data' => $item,
                            ]
                        );

                        $totalFetched++;
                    }

                    // Check if there are more pages
                    if (!empty($response['page']) && $response['page']['totalPages'] > ($filters['page'] + 1)) {
                        $filters['page']++;
                    } else {
                        $hasMore = false;
                    }
                }
            }

            Log::channel('resbe')->info("[FetchSettlements] {$totalFetched} kayıt çekildi");

            return response()->json([
                'success' => true,
                'message' => "{$totalFetched} finansal işlem başarıyla senkronize edildi",
                'data' => [
                    'total_fetched' => $totalFetched,
                    'chunks_processed' => count($chunks),
                ],
            ]);

        } catch (\Exception $e) {
            Log::channel('resbe')->error("[FetchSettlements] Hata: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Finansal işlemler çekilirken hata oluştu: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List other financials (deductions, fees) with filters.
     */
    public function listOtherFinancials(Request $request): JsonResponse
    {
        $query = MarketplaceOtherFinancial::with('marketplace:id,name,slug')
            ->where('user_id', 1); // TODO: auth()->id()

        // Filter by marketplace
        if ($request->has('marketplace_id')) {
            $query->where('marketplace_id', $request->marketplace_id);
        }

        // Filter by transaction type
        if ($request->has('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        // Date range filter
        if ($request->has('start_date')) {
            $query->whereDate('transaction_date', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->whereDate('transaction_date', '<=', $request->end_date);
        }

        // Search in description
        if ($request->has('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        $perPage = min($request->get('per_page', 50), 500);
        $financials = $query->orderByDesc('transaction_date')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Diğer finansal kayıtlar başarıyla getirildi',
            'data' => $financials,
        ]);
    }

    /**
     * Fetch other financials from marketplace API and store locally.
     */
    public function fetchOtherFinancials(Request $request): JsonResponse
    {
        $request->validate([
            'marketplace_id' => 'required|exists:marketplaces,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        try {
            $userId = 1; // TODO: auth()->id()
            $credential = UserMarketplaceCredential::where('user_id', $userId)
                ->where('marketplace_id', $request->marketplace_id)
                ->where('is_active', true)
                ->firstOrFail();

            $service = MarketplaceServiceFactory::make($credential);

            // Chunk date range into 15-day intervals
            $chunks = $this->chunkDateRange($request->start_date, $request->end_date);
            $totalFetched = 0;
            $cargoInvoices = [];

            foreach ($chunks as $chunk) {
                $filters = [
                    'startDate' => $chunk['startDate'],
                    'endDate' => $chunk['endDate'],
                    'page' => 0,
                    'size' => 200,
                ];

                $hasMore = true;
                while ($hasMore) {
                    $response = $service->getOtherFinancials($filters);

                    if (empty($response['content'])) {
                        $hasMore = false;
                        continue;
                    }

                    foreach ($response['content'] as $item) {
                        $financial = MarketplaceOtherFinancial::updateOrCreate(
                            [
                                'user_id' => $userId,
                                'marketplace_id' => $request->marketplace_id,
                                'invoice_serial_number' => $item['receiptId'] ?? null,
                                'transaction_date' => isset($item['transactionDate'])
                                    ? Carbon::createFromTimestampMs($item['transactionDate'])
                                    : null,
                            ],
                            [
                                'transaction_type' => $item['transactionType'] ?? null,
                                'receipt_date' => isset($item['receiptDate'])
                                    ? Carbon::createFromTimestampMs($item['receiptDate'])
                                    : null,
                                'order_number' => $item['orderNumber'] ?? null,
                                'description' => $item['description'] ?? null,
                                'credit' => $item['credit'] ?? 0,
                                'debt' => $item['debt'] ?? 0,
                                'marketplace_data' => $item,
                            ]
                        );

                        // Track cargo invoices for later fetching
                        if ($financial->isCargoInvoice() && !empty($item['receiptId'])) {
                            $cargoInvoices[$item['receiptId']] = $item;
                        }

                        $totalFetched++;
                    }

                    // Check if there are more pages
                    if (!empty($response['page']) && $response['page']['totalPages'] > ($filters['page'] + 1)) {
                        $filters['page']++;
                    } else {
                        $hasMore = false;
                    }
                }
            }

            Log::channel('resbe')->info("[FetchOtherFinancials] {$totalFetched} kayıt çekildi, " . count($cargoInvoices) . " kargo faturası tespit edildi");

            return response()->json([
                'success' => true,
                'message' => "{$totalFetched} kayıt başarıyla senkronize edildi",
                'data' => [
                    'total_fetched' => $totalFetched,
                    'cargo_invoices_found' => count($cargoInvoices),
                    'chunks_processed' => count($chunks),
                ],
            ]);

        } catch (\Exception $e) {
            Log::channel('resbe')->error("[FetchOtherFinancials] Hata: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Kayıtlar çekilirken hata oluştu: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List cargo invoices with filters.
     */
    public function listCargoInvoices(Request $request): JsonResponse
    {
        $query = MarketplaceCargoInvoice::with('marketplace:id,name,slug')
            ->where('user_id', 1); // TODO: auth()->id()

        // Filter by marketplace
        if ($request->has('marketplace_id')) {
            $query->where('marketplace_id', $request->marketplace_id);
        }

        // Date range filter
        if ($request->has('start_date')) {
            $query->whereDate('invoice_date', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->whereDate('invoice_date', '<=', $request->end_date);
        }

        $perPage = min($request->get('per_page', 50), 500);
        $invoices = $query->orderByDesc('invoice_date')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Kargo faturaları başarıyla getirildi',
            'data' => $invoices,
        ]);
    }

    /**
     * Fetch cargo invoice items from marketplace API.
     */
    public function fetchCargoInvoice(Request $request): JsonResponse
    {
        $request->validate([
            'marketplace_id' => 'required|exists:marketplaces,id',
            'invoice_serial_number' => 'required|string',
        ]);

        try {
            $userId = 1; // TODO: auth()->id()
            $credential = UserMarketplaceCredential::where('user_id', $userId)
                ->where('marketplace_id', $request->marketplace_id)
                ->where('is_active', true)
                ->firstOrFail();

            $service = MarketplaceServiceFactory::make($credential);

            // Fetch cargo invoice items
            $response = $service->getCargoInvoiceItems($request->invoice_serial_number);

            if (empty($response)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kargo faturası detayları bulunamadı',
                ], 404);
            }

            // Create or update cargo invoice header
            $invoice = MarketplaceCargoInvoice::updateOrCreate(
                [
                    'user_id' => $userId,
                    'marketplace_id' => $request->marketplace_id,
                    'invoice_serial_number' => $request->invoice_serial_number,
                ],
                [
                    'invoice_date' => isset($response['invoiceDate'])
                        ? Carbon::createFromTimestampMs($response['invoiceDate'])
                        : null,
                    'total_amount' => $response['totalAmount'] ?? 0,
                    'status' => 'active',
                    'marketplace_data' => $response,
                ]
            );

            // Store invoice items
            $itemsStored = 0;
            if (!empty($response['items'])) {
                foreach ($response['items'] as $item) {
                    MarketplaceCargoInvoiceItem::updateOrCreate(
                        [
                            'cargo_invoice_id' => $invoice->id,
                            'order_number' => $item['orderNumber'] ?? null,
                        ],
                        [
                            'amount' => $item['amount'] ?? 0,
                            'description' => $item['description'] ?? null,
                            'marketplace_data' => $item,
                        ]
                    );
                    $itemsStored++;
                }
            }

            Log::channel('resbe')->info("[FetchCargoInvoice] Fatura: {$request->invoice_serial_number}, {$itemsStored} kalem");

            return response()->json([
                'success' => true,
                'message' => "Kargo faturası başarıyla senkronize edildi ({$itemsStored} kalem)",
                'data' => [
                    'invoice' => $invoice->load('items'),
                    'items_stored' => $itemsStored,
                ],
            ]);

        } catch (\Exception $e) {
            Log::channel('resbe')->error("[FetchCargoInvoice] Hata: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Kargo faturası çekilirken hata oluştu: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get financial summary and dashboard data.
     */
    public function getSummary(Request $request): JsonResponse
    {
        $request->validate([
            'marketplace_id' => 'required|exists:marketplaces,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $userId = 1; // TODO: auth()->id()

        // Settlements summary
        $settlements = MarketplaceSettlement::where('user_id', $userId)
            ->where('marketplace_id', $request->marketplace_id)
            ->whereBetween('transaction_date', [$request->start_date, $request->end_date])
            ->select(
                DB::raw('SUM(credit) as total_credit'),
                DB::raw('SUM(debt) as total_debt'),
                DB::raw('SUM(commission_amount) as total_commission'),
                DB::raw('SUM(seller_revenue) as total_revenue')
            )
            ->first();

        // Other financials summary (grouped by type)
        $otherFinancials = MarketplaceOtherFinancial::where('user_id', $userId)
            ->where('marketplace_id', $request->marketplace_id)
            ->whereBetween('transaction_date', [$request->start_date, $request->end_date])
            ->get();

        $platformFees = $otherFinancials->filter->isPlatformFee()->sum('debt');
        $cargoFees = $otherFinancials->filter->isCargoInvoice()->sum('debt');
        $otherDeductions = $otherFinancials->sum('debt') - $platformFees - $cargoFees;

        // Calculate net profit
        $grossSales = $settlements->total_credit ?? 0;
        $totalCommission = $settlements->total_commission ?? 0;
        $netProfit = $grossSales - $totalCommission - $platformFees - $cargoFees - $otherDeductions;

        return response()->json([
            'success' => true,
            'message' => 'Finansal özet başarıyla getirildi',
            'data' => [
                'period' => [
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                ],
                'settlements' => [
                    'gross_sales' => round($grossSales, 2),
                    'total_commission' => round($totalCommission, 2),
                    'seller_revenue' => round($settlements->total_revenue ?? 0, 2),
                ],
                'deductions' => [
                    'platform_fees' => round($platformFees, 2),
                    'cargo_fees' => round($cargoFees, 2),
                    'other_deductions' => round($otherDeductions, 2),
                    'total_deductions' => round($platformFees + $cargoFees + $otherDeductions, 2),
                ],
                'profit' => [
                    'net_profit' => round($netProfit, 2),
                    'profit_margin' => $grossSales > 0 ? round(($netProfit / $grossSales) * 100, 2) : 0,
                ],
            ],
        ]);
    }
}
