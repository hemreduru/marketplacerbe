<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateMarketplaceSettingsRequest;
use App\Jobs\SyncTrendyolClaimsJob;
use App\Jobs\SyncTrendyolFinancialsJob;
use App\Jobs\SyncTrendyolOrdersJob;
use App\Jobs\SyncTrendyolProductsJob;
use App\Jobs\SyncTrendyolQuestionsJob;
use App\Models\Marketplace;
use App\Models\UserMarketplaceCredential;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MarketplaceSettingsController extends Controller
{
    /**
     * Show marketplace settings page
     */
    public function index()
    {
        $user = Auth::user();
        $marketplaces = Marketplace::where('is_active', true)->get();
        $credentials = UserMarketplaceCredential::where('user_id', $user->id)->get()->keyBy('marketplace_id');

        return view('marketplace.settings', [
            'user' => $user,
            'marketplaces' => $marketplaces,
            'credentials' => $credentials,
        ]);
    }

    /**
     * Update marketplace credentials (AJAX)
     */
    public function update(UpdateMarketplaceSettingsRequest $request)
    {
        try {
            DB::beginTransaction();

            $user = Auth::user();
            $validated = $request->validated();

            // Guard: yalnızca aktif pazaryerine credential girilebilir.
            // Pasif pazaryeri UI'da gizli olsa da doğrudan istek gelebilir; sunucu tarafında reddet.
            $marketplace = Marketplace::find($validated['marketplace_id']);
            if (! $marketplace || ! $marketplace->is_active) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => __('settings.marketplace_inactive'),
                ], 422);
            }

            // Guard: Marketplace Limit Enforcement
            $exists = UserMarketplaceCredential::where('user_id', $user->id)
                ->where('marketplace_id', $validated['marketplace_id'])
                ->exists();

            if (! $exists) {
                $currentCount = UserMarketplaceCredential::where('user_id', $user->id)->count();
                $limit = $user->getSubscriptionLimit('marketplaces');

                if ($limit !== -1 && $currentCount >= $limit) {
                    DB::rollBack();

                    return response()->json([
                        'success' => false,
                        'message' => __('subscription.marketplace_limit_reached', ['limit' => $limit]),
                    ], 422);
                }
            }

            $credential = UserMarketplaceCredential::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'marketplace_id' => $validated['marketplace_id'],
                ],
                [
                    'api_key' => $validated['api_key'],
                    'api_secret' => $validated['api_secret'],
                    'additional_credentials' => $validated['additional_credentials'] ?? [],
                    'is_active' => true,
                ]
            );

            DB::commit();

            if ($marketplace->slug === 'trendyol') {
                SyncTrendyolProductsJob::dispatch($credential->id)->onQueue('sync');
                SyncTrendyolOrdersJob::dispatch($credential->id)->onQueue('sync');
                SyncTrendyolFinancialsJob::dispatch($credential->id)->onQueue('sync');
                SyncTrendyolQuestionsJob::dispatch($credential->id)->onQueue('sync');
                SyncTrendyolClaimsJob::dispatch($credential->id)->onQueue('sync');
            }

            return response()->json([
                'success' => true,
                'message' => __('settings.credentials_updated'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update marketplace credentials: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => __('common.error_occurred'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
