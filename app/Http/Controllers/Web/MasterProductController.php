<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MasterProduct;
use App\Services\Marketplaces\MarketplaceCapability;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MasterProductController extends Controller
{
    public function show(int $id)
    {
        $user = Auth::user();

        $product = MasterProduct::with([
            'listings' => function ($q) {
                $q->with('credential.marketplace');
            },
        ])
            ->where('user_id', $user->id)
            ->findOrFail($id);

        $marketplaceCodes = MarketplaceCapability::all();

        return view('master-products.show', [
            'product' => $product,
            'marketplaceCodes' => $marketplaceCodes,
        ]);
    }

    /**
     * COGS eksik (cost_price = 0) SKU'ları toplu maliyet girişi için listeler.
     * Maliyet girilene kadar net kâr yapay yüksek görünür — bu ekran onu kapatır.
     */
    public function costEntry()
    {
        $products = MasterProduct::where('user_id', Auth::id())
            ->where('cost_price', 0)
            ->orderBy('title')
            ->paginate(50);

        return view('master-products.cost-entry', ['products' => $products]);
    }

    /**
     * Toplu cost_price kaydı — yalnızca kullanıcının kendi ürünleri güncellenir.
     */
    public function updateCosts(Request $request)
    {
        $validated = $request->validate([
            'costs' => ['required', 'array'],
            'costs.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $updated = 0;
        foreach ($validated['costs'] as $id => $cost) {
            if ($cost === null || $cost === '') {
                continue;
            }

            $updated += MasterProduct::where('user_id', Auth::id())
                ->whereKey($id)
                ->update(['cost_price' => $cost]);
        }

        return redirect()->route('cost-entry')
            ->with('success', __('master_products.costs_saved', ['count' => $updated]));
    }
}
