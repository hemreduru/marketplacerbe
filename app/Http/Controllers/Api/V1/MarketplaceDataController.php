<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceBrand;
use App\Models\MarketplaceCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketplaceDataController extends Controller
{
    /**
     * List marketplace categories with optional filtering
     */
    public function listCategories(Request $request): JsonResponse
    {
        $query = MarketplaceCategory::with('marketplace:id,name,slug');

        // Filter by marketplace
        if ($request->has('marketplace_id')) {
            $query->where('marketplace_id', $request->marketplace_id);
        }

        // Filter by level
        if ($request->has('level')) {
            $query->where('level', $request->level);
        }

        // Filter by root categories
        if ($request->boolean('roots_only')) {
            $query->roots();
        }

        // Filter by leaf categories (can have products)
        if ($request->boolean('leaves_only')) {
            $query->leaves();
        }

        // Search by name
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Filter by parent category
        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        }

        // Load relationships based on query param
        if ($request->boolean('with_children')) {
            $query->with('children');
        }

        if ($request->boolean('with_parent')) {
            $query->with('parent');
        }

        // Pagination
        $perPage = min($request->get('per_page', 50), 500);
        $categories = $query->orderBy('full_path')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Kategoriler başarıyla getirildi',
            'data' => $categories,
        ]);
    }

    /**
     * Get a single category with full hierarchy
     */
    public function getCategory(int $id): JsonResponse
    {
        $category = MarketplaceCategory::with([
            'marketplace:id,name,slug',
            'parent',
            'children',
        ])->find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori bulunamadı',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Kategori başarıyla getirildi',
            'data' => $category,
        ]);
    }

    /**
     * Get category tree (roots with descendants)
     */
    public function getCategoryTree(Request $request): JsonResponse
    {
        $query = MarketplaceCategory::roots()
            ->with('marketplace:id,name,slug');

        // Filter by marketplace
        if ($request->has('marketplace_id')) {
            $query->where('marketplace_id', $request->marketplace_id);
        }

        // Load full tree
        $query->with('descendants');

        $roots = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'message' => 'Kategori ağacı başarıyla getirildi',
            'data' => $roots,
        ]);
    }

    /**
     * List marketplace brands with optional filtering
     */
    public function listBrands(Request $request): JsonResponse
    {
        $query = MarketplaceBrand::with('marketplace:id,name,slug');

        // Filter by marketplace
        if ($request->has('marketplace_id')) {
            $query->where('marketplace_id', $request->marketplace_id);
        }

        // Search by name
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Pagination
        $perPage = min($request->get('per_page', 50), 500);
        $brands = $query->orderBy('name')->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Markalar başarıyla getirildi',
            'data' => $brands,
        ]);
    }

    /**
     * Get a single brand
     */
    public function getBrand(int $id): JsonResponse
    {
        $brand = MarketplaceBrand::with('marketplace:id,name,slug')->find($id);

        if (!$brand) {
            return response()->json([
                'success' => false,
                'message' => 'Marka bulunamadı',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Marka başarıyla getirildi',
            'data' => $brand,
        ]);
    }
}
