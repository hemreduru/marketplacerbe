<?php

namespace App\Services;

use App\Models\MarketplaceProduct;
use App\Models\MarketplaceSettlement;
use App\Models\MarketplaceOtherFinancial;
use App\Models\Product;
use App\Models\ProductAdditionalExpense;
use Carbon\Carbon;

class ProfitCalculationService
{
    /**
     * Calculate profit for a specific product on a marketplace.
     *
     * @param Product $product
     * @param int $marketplaceId
     * @param array $options - custom values to override (sale_price, purchase_cost, etc.)
     * @return array
     */
    public function calculateProductProfit(Product $product, int $marketplaceId, array $options = []): array
    {
        // Get marketplace product data
        $marketplaceProduct = MarketplaceProduct::where('product_id', $product->id)
            ->where('marketplace_id', $marketplaceId)
            ->first();

        // Base values
        $salePrice = $options['sale_price'] ?? $product->sale_price;
        $purchaseCost = $options['purchase_cost'] ?? $product->base_price;

        // Commission calculation
        $commissionRate = $this->getMarketplaceCommissionRate($marketplaceId);
        $commissionAmount = $salePrice * ($commissionRate / 100);

        // Additional expenses (specific to this product + allocated shares)
        $additionalExpenses = $this->calculateAdditionalExpenses($product->id, $marketplaceId);

        // Platform fees (from settlements if available)
        $platformFees = $this->getPlatformFees($product->id, $marketplaceId);

        // Shipping cost (can be overridden)
        $shippingCost = $options['shipping_cost'] ?? 0;

        // Calculate totals
        $totalExpenses = $purchaseCost + $commissionAmount + $additionalExpenses + $platformFees + $shippingCost;
        $netProfit = $salePrice - $totalExpenses;
        $profitRate = $salePrice > 0 ? ($netProfit / $salePrice) * 100 : 0;
        $marginRate = $purchaseCost > 0 ? ($netProfit / $purchaseCost) * 100 : 0;

        return [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'marketplace_id' => $marketplaceId,
            'marketplace_product_id' => $marketplaceProduct?->id,
            'sale_price' => round($salePrice, 2),
            'purchase_cost' => round($purchaseCost, 2),
            'breakdown' => [
                'commission' => [
                    'rate' => $commissionRate,
                    'amount' => round($commissionAmount, 2),
                ],
                'additional_expenses' => round($additionalExpenses, 2),
                'platform_fees' => round($platformFees, 2),
                'shipping_cost' => round($shippingCost, 2),
            ],
            'total_expenses' => round($totalExpenses, 2),
            'net_profit' => round($netProfit, 2),
            'profit_rate' => round($profitRate, 2), // % of sale price
            'margin_rate' => round($marginRate, 2), // % of purchase cost
            'is_profitable' => $netProfit > 0,
        ];
    }

    /**
     * Calculate profit for multiple products.
     *
     * @param array $productIds
     * @param int $marketplaceId
     * @return array
     */
    public function calculateBulkProfit(array $productIds, int $marketplaceId): array
    {
        $results = [];
        $products = Product::whereIn('id', $productIds)->get();

        foreach ($products as $product) {
            $results[] = $this->calculateProductProfit($product, $marketplaceId);
        }

        // Summary
        $totalSales = array_sum(array_column($results, 'sale_price'));
        $totalExpenses = array_sum(array_column($results, 'total_expenses'));
        $totalProfit = array_sum(array_column($results, 'net_profit'));
        $avgProfitRate = $totalSales > 0 ? ($totalProfit / $totalSales) * 100 : 0;

        return [
            'products' => $results,
            'summary' => [
                'total_products' => count($results),
                'total_sales' => round($totalSales, 2),
                'total_expenses' => round($totalExpenses, 2),
                'total_profit' => round($totalProfit, 2),
                'average_profit_rate' => round($avgProfitRate, 2),
                'profitable_count' => count(array_filter($results, fn($r) => $r['is_profitable'])),
            ],
        ];
    }

    /**
     * Calculate additional expenses for a product on a marketplace.
     *
     * @param int $productId
     * @param int $marketplaceId
     * @return float
     */
    protected function calculateAdditionalExpenses(int $productId, int $marketplaceId): float
    {
        $total = 0;

        // 1. Specific product expenses
        $productExpenses = ProductAdditionalExpense::forProduct($productId)
            ->where(function ($query) use ($marketplaceId) {
                $query->whereNull('marketplace_id')
                    ->orWhere('marketplace_id', $marketplaceId);
            })
            ->sum('amount');

        $total += $productExpenses;

        // 2. Marketplace-level expenses (allocated per product)
        $marketplaceExpenses = ProductAdditionalExpense::where('allocation_type', 'per_marketplace')
            ->where('marketplace_id', $marketplaceId)
            ->where('is_active', true)
            ->get();

        if ($marketplaceExpenses->count() > 0) {
            // Get total products in this marketplace
            $productCount = MarketplaceProduct::where('marketplace_id', $marketplaceId)->count();
            if ($productCount > 0) {
                $allocatedAmount = $marketplaceExpenses->sum('amount') / $productCount;
                $total += $allocatedAmount;
            }
        }

        // 3. Global expenses (allocated per all products)
        $globalExpenses = ProductAdditionalExpense::global()->get();
        if ($globalExpenses->count() > 0) {
            // Get total products across all marketplaces
            $totalProducts = MarketplaceProduct::count();
            if ($totalProducts > 0) {
                $allocatedAmount = $globalExpenses->sum('amount') / $totalProducts;
                $total += $allocatedAmount;
            }
        }

        return $total;
    }

    /**
     * Get platform fees from financial data.
     *
     * @param int $productId
     * @param int $marketplaceId
     * @return float
     */
    protected function getPlatformFees(int $productId, int $marketplaceId): float
    {
        // Get product barcode
        $product = Product::find($productId);
        if (!$product || !$product->barcode) {
            return 0;
        }

        // Get platform fees from other_financials
        $fees = MarketplaceOtherFinancial::where('marketplace_id', $marketplaceId)
            ->whereHas('marketplace', function ($query) use ($product) {
                // This is a simplified approach - in real scenario you'd match by order/barcode
            })
            ->get()
            ->filter(fn($f) => $f->isPlatformFee())
            ->sum('debt');

        return $fees;
    }

    /**
     * Get commission rate for a marketplace.
     * In real scenario, this would come from a config or database table.
     *
     * @param int $marketplaceId
     * @return float
     */
    protected function getMarketplaceCommissionRate(int $marketplaceId): float
    {
        // Default commission rates per marketplace
        // TODO: Move to database or config
        $commissionRates = [
            1 => 10.0, // Trendyol: 10%
            2 => 12.0, // Hepsiburada: 12%
            3 => 8.0,  // N11: 8%
        ];

        return $commissionRates[$marketplaceId] ?? 10.0;
    }

    /**
     * Get profit summary for a user across all products.
     *
     * @param int $userId
     * @param array $filters - marketplace_id, start_date, end_date
     * @return array
     */
    public function getUserProfitSummary(int $userId, array $filters = []): array
    {
        $query = Product::where('user_id', $userId);

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $query->whereBetween('created_at', [
                Carbon::parse($filters['start_date']),
                Carbon::parse($filters['end_date']),
            ]);
        }

        $products = $query->get();
        $marketplaceId = $filters['marketplace_id'] ?? 1; // Default to first marketplace

        $profitData = [];
        foreach ($products as $product) {
            $profitData[] = $this->calculateProductProfit($product, $marketplaceId);
        }

        $totalSales = array_sum(array_column($profitData, 'sale_price'));
        $totalExpenses = array_sum(array_column($profitData, 'total_expenses'));
        $totalProfit = array_sum(array_column($profitData, 'net_profit'));

        return [
            'user_id' => $userId,
            'marketplace_id' => $marketplaceId,
            'period' => [
                'start_date' => $filters['start_date'] ?? null,
                'end_date' => $filters['end_date'] ?? null,
            ],
            'summary' => [
                'total_products' => count($profitData),
                'total_sales' => round($totalSales, 2),
                'total_expenses' => round($totalExpenses, 2),
                'total_profit' => round($totalProfit, 2),
                'average_profit_rate' => $totalSales > 0 ? round(($totalProfit / $totalSales) * 100, 2) : 0,
                'profitable_products' => count(array_filter($profitData, fn($p) => $p['is_profitable'])),
                'unprofitable_products' => count(array_filter($profitData, fn($p) => !$p['is_profitable'])),
            ],
            'products' => $profitData,
        ];
    }
}
