<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductAdditionalExpense;
use App\Services\ProfitCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ProfitController extends Controller
{
    protected ProfitCalculationService $profitService;

    public function __construct(ProfitCalculationService $profitService)
    {
        $this->profitService = $profitService;
    }

    /**
     * Calculate profit for a specific product.
     *
     * POST /api/v1/profit/calculate
     */
    public function calculate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'marketplace_id' => 'required|exists:marketplaces,id',
            'sale_price' => 'nullable|numeric|min:0',
            'purchase_cost' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __('api.validation_error'),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $product = Product::findOrFail($request->product_id);

            $options = [];
            if ($request->filled('sale_price')) {
                $options['sale_price'] = $request->sale_price;
            }
            if ($request->filled('purchase_cost')) {
                $options['purchase_cost'] = $request->purchase_cost;
            }
            if ($request->filled('shipping_cost')) {
                $options['shipping_cost'] = $request->shipping_cost;
            }

            $profitData = $this->profitService->calculateProductProfit(
                $product,
                $request->marketplace_id,
                $options
            );

            Log::info('Profit calculated', [
                'product_id' => $request->product_id,
                'marketplace_id' => $request->marketplace_id,
                'net_profit' => $profitData['net_profit'],
            ]);

            return response()->json([
                'success' => true,
                'message' => __('api.profit.calculate_success'),
                'data' => $profitData,
            ]);
        } catch (\Exception $e) {
            Log::error('Profit calculation failed', [
                'product_id' => $request->product_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('api.profit.calculate_failed'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Calculate profit for multiple products (bulk).
     *
     * POST /api/v1/profit/bulk-calculate
     */
    public function bulkCalculate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'exists:products,id',
            'marketplace_id' => 'required|exists:marketplaces,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __('api.validation_error'),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $profitData = $this->profitService->calculateBulkProfit(
                $request->product_ids,
                $request->marketplace_id
            );

            return response()->json([
                'success' => true,
                'message' => __('api.profit.bulk_calculate_success'),
                'data' => $profitData,
            ]);
        } catch (\Exception $e) {
            Log::error('Bulk profit calculation failed', [
                'product_count' => count($request->product_ids),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('api.profit.bulk_calculate_failed'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user's profit summary.
     *
     * GET /api/v1/profit/summary
     */
    public function summary(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'marketplace_id' => 'nullable|exists:marketplaces,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __('api.validation_error'),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $filters = [
                'marketplace_id' => $request->input('marketplace_id', 1),
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ];

            $summary = $this->profitService->getUserProfitSummary($request->user_id, $filters);

            return response()->json([
                'success' => true,
                'message' => __('api.profit.summary_success'),
                'data' => $summary,
            ]);
        } catch (\Exception $e) {
            Log::error('Profit summary failed', [
                'user_id' => $request->user_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('api.profit.summary_failed'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * List additional expenses.
     *
     * GET /api/v1/profit/expenses
     */
    public function listExpenses(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'product_id' => 'nullable|exists:products,id',
            'marketplace_id' => 'nullable|exists:marketplaces,id',
            'expense_type' => 'nullable|string',
            'per_page' => 'nullable|integer|min:1|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __('api.validation_error'),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $query = ProductAdditionalExpense::where('user_id', $request->user_id)
                ->where('is_active', true);

            if ($request->filled('product_id')) {
                $query->where('product_id', $request->product_id);
            }

            if ($request->filled('marketplace_id')) {
                $query->where('marketplace_id', $request->marketplace_id);
            }

            if ($request->filled('expense_type')) {
                $query->where('expense_type', $request->expense_type);
            }

            $perPage = $request->input('per_page', 50);
            $expenses = $query->with(['product', 'marketplace'])
                ->orderBy('expense_date', 'desc')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => __('api.profit.expenses_list_success'),
                'data' => $expenses->items(),
                'meta' => [
                    'current_page' => $expenses->currentPage(),
                    'per_page' => $expenses->perPage(),
                    'total' => $expenses->total(),
                    'last_page' => $expenses->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Expenses list failed', [
                'user_id' => $request->user_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('api.profit.expenses_list_failed'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add additional expense.
     *
     * POST /api/v1/profit/expenses
     */
    public function addExpense(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'product_id' => 'required|exists:products,id',
            'marketplace_id' => 'nullable|exists:marketplaces,id',
            'expense_type' => 'required|string|in:packaging,advertising,storage,shipping_material,extra_service,other',
            'title' => 'required|string|max:100',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'expense_date' => 'required|date',
            'allocation_type' => 'required|in:per_product,per_marketplace,global',
            'receipt_number' => 'nullable|string',
            'is_recurring' => 'nullable|boolean',
            'recurrence_period' => 'nullable|string|in:monthly,quarterly,yearly',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __('api.validation_error'),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $expense = ProductAdditionalExpense::create([
                'user_id' => $request->user_id,
                'product_id' => $request->product_id,
                'marketplace_id' => $request->marketplace_id,
                'expense_type' => $request->expense_type,
                'title' => $request->title,
                'description' => $request->description,
                'amount' => $request->amount,
                'currency' => $request->input('currency', 'TRY'),
                'expense_date' => $request->expense_date,
                'allocation_type' => $request->allocation_type,
                'receipt_number' => $request->receipt_number,
                'is_recurring' => $request->input('is_recurring', false),
                'recurrence_period' => $request->recurrence_period,
                'is_active' => true,
            ]);

            Log::info('Additional expense created', [
                'expense_id' => $expense->id,
                'product_id' => $request->product_id,
                'amount' => $request->amount,
            ]);

            return response()->json([
                'success' => true,
                'message' => __('api.profit.expense_create_success'),
                'data' => $expense,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Expense creation failed', [
                'product_id' => $request->product_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('api.profit.expense_create_failed'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update additional expense.
     *
     * PUT /api/v1/profit/expenses/{id}
     */
    public function updateExpense(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:100',
            'description' => 'sometimes|string',
            'amount' => 'sometimes|numeric|min:0',
            'expense_date' => 'sometimes|date',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => __('api.validation_error'),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $expense = ProductAdditionalExpense::findOrFail($id);
            $expense->update($request->only([
                'title',
                'description',
                'amount',
                'expense_date',
                'is_active',
            ]));

            return response()->json([
                'success' => true,
                'message' => __('api.profit.expense_update_success'),
                'data' => $expense,
            ]);
        } catch (\Exception $e) {
            Log::error('Expense update failed', [
                'expense_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('api.profit.expense_update_failed'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete additional expense.
     *
     * DELETE /api/v1/profit/expenses/{id}
     */
    public function deleteExpense(int $id): JsonResponse
    {
        try {
            $expense = ProductAdditionalExpense::findOrFail($id);
            $expense->delete();

            return response()->json([
                'success' => true,
                'message' => __('api.profit.expense_delete_success'),
            ]);
        } catch (\Exception $e) {
            Log::error('Expense deletion failed', [
                'expense_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => __('api.profit.expense_delete_failed'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

