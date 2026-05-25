<?php

namespace App\Services\Trendyol;

use App\Models\Claim;
use App\Services\Contracts\ClaimServiceContract;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TrendyolClaimService implements ClaimServiceContract
{
    protected string $baseUrl;

    public function __construct(
        protected string $apiKey,
        protected string $apiSecret,
        protected string $sellerId,
        bool $isStage = false,
    ) {
        $this->baseUrl = $isStage ? 'https://stageapigw.trendyol.com' : 'https://apigw.trendyol.com';
    }

    public function getClaims(array $filters = []): array
    {
        $url = sprintf('%s/integration/order/sellers/%s/claims', $this->baseUrl, $this->sellerId);

        $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)->get($url, $filters);

        if ($response->failed()) {
            Log::error('Trendyol Claim API Error (getClaims): '.$response->body());

            return ['error' => true, 'message' => $response->body()];
        }

        return $response->json();
    }

    public function syncClaims(int $credentialId, ?callable $onProgress = null): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'failed' => 0];
        $page = 0;
        $size = 50;

        do {
            $response = $this->getClaims(['page' => $page, 'size' => $size]);

            if (isset($response['error'])) {
                throw new \Exception($response['message']);
            }

            $content = $response['content'] ?? [];
            $totalPages = (int) ($response['totalPages'] ?? 1);

            foreach ($content as $item) {
                try {
                    $claim = Claim::updateOrCreate(
                        [
                            'user_marketplace_credential_id' => $credentialId,
                            'remote_id' => (string) ($item['id'] ?? ''),
                        ],
                        [
                            'order_number' => $item['orderNumber'] ?? null,
                            'status' => $item['items'][0]['claimItems'][0]['claimItemStatus']['name'] ?? ($item['status'] ?? 'Created'),
                            'customer_name' => trim(($item['customerFirstName'] ?? '').' '.($item['customerLastName'] ?? '')) ?: null,
                            'item_count' => count($item['items'] ?? []),
                            'claim_date' => isset($item['claimDate']) ? Carbon::createFromTimestampMs($item['claimDate']) : null,
                            'raw_data' => $item,
                        ]
                    );

                    $claim->wasRecentlyCreated ? $stats['created']++ : $stats['updated']++;
                } catch (\Exception $e) {
                    $stats['failed']++;
                }
            }

            if ($onProgress) {
                $onProgress($page, $totalPages, "Claims page {$page}", $stats);
            }

            $page++;
        } while ($page < $totalPages);

        return $stats;
    }

    public function approveClaimItems(string $claimId, array $claimItemIds): array
    {
        $url = sprintf('%s/integration/order/sellers/%s/claims/%s/items/approve', $this->baseUrl, $this->sellerId, $claimId);

        $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
            ->put($url, ['claimLineItemIdList' => $claimItemIds]);

        if ($response->failed()) {
            Log::error('Trendyol Claim API Error (approveClaimItems): '.$response->body());

            return ['error' => true, 'message' => $response->body()];
        }

        return $response->json() ?: ['success' => true];
    }
}
