<?php

namespace App\Services\Trendyol;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TrendyolQuestionService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $apiSecret;
    protected string $sellerId;

    public function __construct(string $apiKey, string $apiSecret, string $sellerId, bool $isStage = false)
    {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->sellerId = $sellerId;
        $this->baseUrl = $isStage ? 'https://stageapigw.trendyol.com' : 'https://apigw.trendyol.com';
    }

    /**
     * Get customer questions.
     *
     * @param array $filters (status, startDate, endDate, page, size, etc.)
     * @return array
     */
    public function getQuestions(array $filters = []): array
    {
        $url = sprintf("%s/integration/qna/sellers/%s/questions/filter", $this->baseUrl, $this->sellerId);

        // Default to WAITING_FOR_ANSWER if no status is provided
        if (!isset($filters['status'])) {
            $filters['status'] = 'WAITING_FOR_ANSWER';
        }

        $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
            ->get($url, $filters);

        if ($response->failed()) {
            Log::error("Trendyol Question API Error (getQuestions): " . $response->body());
            return ['error' => true, 'message' => $response->body()];
        }

        return $response->json();
    }

    /**
     * Answer a customer question.
     *
     * @param int $questionId
     * @param string $text
     * @return array
     */
    public function answerQuestion(int $questionId, string $text): array
    {
        $url = sprintf("%s/integration/qna/sellers/%s/questions/%s/answer", $this->baseUrl, $this->sellerId, $questionId);

        $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
            ->post($url, [
                'text' => $text
            ]);

        if ($response->failed()) {
            Log::error("Trendyol Question API Error (answerQuestion): " . $response->body());
            return ['error' => true, 'message' => $response->body()];
        }

        return $response->json();
    }
}
