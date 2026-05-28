<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MasterProduct;
use App\Services\Marketplaces\MarketplaceCapability;
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
}
