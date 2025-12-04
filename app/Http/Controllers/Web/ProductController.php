<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Marketplace;
use App\Models\UserMarketplaceCredential;
use App\Services\Trendyol\TrendyolProductService;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Product::whereHas('credential', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });

        // Fetch all products for client-side DataTable
        $products = $query->orderBy('created_at', 'desc')->get();

        // Get unique brands and categories for filter dropdowns
        $brands = Product::whereHas('credential', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->distinct()->pluck('brand')->filter()->values();

        $categories = Product::whereHas('credential', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->distinct()->pluck('category_name')->filter()->values();

        return view('products.index', compact('products', 'brands', 'categories'));
    }

    public function getData(Request $request)
    {
        $user = Auth::user();
        $query = Product::whereHas('credential', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });

        // Total Records (before filtering)
        $totalRecords = $query->count();

        // Filtering
        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->filled('brand')) {
            $query->where('brand', $request->brand);
        }

        if ($request->filled('category')) {
            $categories = $request->category;
            if (is_array($categories)) {
                $query->whereIn('category_name', $categories);
            } else {
                $query->where('category_name', $categories);
            }
        }

        if ($request->filled('price_min')) {
            $query->where('price', '>=', $request->price_min);
        }

        if ($request->filled('price_max')) {
            $query->where('price', '<=', $request->price_max);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('stock_less_than')) {
            $query->where('stock', '<', $request->stock_less_than)->where('stock', '>', 0);
        }

        if ($request->filled('no_stock') && $request->no_stock == 1) {
            $query->where('stock', '<=', 0);
        }

        if ($request->filled('has_stock') && $request->has_stock == 1) {
            $query->where('stock', '>', 0);
        }

        $filteredRecords = $query->count();

        // Sorting
        $columns = ['title', 'sku', 'brand', 'category_name', 'price', 'stock', 'status'];
        if ($request->has('order')) {
            $columnIndex = $request->input('order.0.column');
            $columnName = $columns[$columnIndex] ?? 'created_at';
            $direction = $request->input('order.0.dir');
            $query->orderBy($columnName, $direction);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        $products = $query->skip($start)->take($length)->get();

        // Transform data
        $data = [];
        foreach ($products as $product) {
            // Product Column (Image + Title)
            $imageUrl = $product->images[0]['url'] ?? $product->images[0] ?? '';
            $productUrl = $product->product_url ?? ('https://www.trendyol.com/sr?q=' . $product->barcode);

            $productHtml = '
                <div class="d-flex align-items-center">
                    <a href="' . $productUrl . '" target="_blank" class="symbol symbol-50px me-5">
                        <span class="symbol-label" style="background-image:url(' . $imageUrl . ');"></span>
                    </a>
                    <div class="d-flex flex-column">
                        <a href="' . $productUrl . '" target="_blank" class="text-gray-800 text-hover-primary mb-1">' . $product->title . '</a>
                    </div>
                </div>';

            // Price Column
            $priceHtml = '<span class="text-gray-800 fw-bold">' . number_format($product->price, 2) . ' ' . $product->currency . '</span>';
            if ($product->list_price > $product->price) {
                $priceHtml .= '<span class="text-muted text-decoration-line-through fs-7 ms-2">' . number_format($product->list_price, 2) . '</span>';
            }

            // Stock Column
            $stockHtml = $product->stock > 0
                ? '<span class="badge badge-light-success">' . $product->stock . '</span>'
                : '<span class="badge badge-light-danger">' . __('common.no_stock') . '</span>';

            // Status Column
            $statusHtml = $product->status == 'active'
                ? '<span class="badge badge-light-success">' . __('common.active') . '</span>'
                : '<span class="badge badge-light-danger">' . __('common.inactive') . '</span>';

            $data[] = [
                $productHtml,
                $product->sku,
                $product->brand,
                $product->category_name,
                $priceHtml,
                $stockHtml,
                $statusHtml
            ];
        }

        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);
    }

    public function sync()
    {
        $user = Auth::user();
        $trendyol = Marketplace::where('slug', 'trendyol')->first();
        $credential = UserMarketplaceCredential::where('user_id', $user->id)
            ->where('marketplace_id', $trendyol->id)
            ->first();

        if (!$credential) {
            return response()->json(['success' => false, 'message' => __('common.please_connect_trendyol')]);
        }

        try {
            $service = new TrendyolProductService(
                $credential->api_key,
                $credential->api_secret,
                $credential->additional_credentials['seller_id'] ?? '',
                false
            );

            $service->syncProducts($credential->id);

            return response()->json(['success' => true, 'message' => __('common.sync_completed')]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
