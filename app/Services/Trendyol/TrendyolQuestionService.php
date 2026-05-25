<?php

namespace App\Services\Trendyol;

use App\Models\Question;
use App\Services\Contracts\QuestionServiceContract;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TrendyolQuestionService implements QuestionServiceContract
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
     * @param  array  $filters  (status, startDate, endDate, page, size, etc.)
     */
    public function getQuestions(array $filters = []): array
    {
        $url = sprintf('%s/integration/qna/sellers/%s/questions/filter', $this->baseUrl, $this->sellerId);

        // Default to WAITING_FOR_ANSWER if no status is provided
        if (! isset($filters['status'])) {
            $filters['status'] = 'WAITING_FOR_ANSWER';
        }

        $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
            ->get($url, $filters);

        if ($response->failed()) {
            Log::error('Trendyol Question API Error (getQuestions): '.$response->body());

            return ['error' => true, 'message' => $response->body()];
        }

        return $response->json();
    }

    /**
     * Answer a customer question.
     */
    public function answerQuestion(int $questionId, string $text): array
    {
        $url = sprintf('%s/integration/qna/sellers/%s/questions/%s/answer', $this->baseUrl, $this->sellerId, $questionId);

        $response = Http::withBasicAuth($this->apiKey, $this->apiSecret)
            ->post($url, [
                'text' => $text,
            ]);

        if ($response->failed()) {
            Log::error('Trendyol Question API Error (answerQuestion): '.$response->body());

            return ['error' => true, 'message' => $response->body()];
        }

        return $response->json();
    }

    /**
     * Pull customer questions (all statuses) into the local questions table.
     *
     * @return array{created: int, updated: int, failed: int}
     */
    public function syncQuestions(int $credentialId, ?callable $onProgress = null): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'failed' => 0];
        $statuses = ['WAITING_FOR_ANSWER', 'ANSWERED', 'REPORTED', 'REJECTED'];
        $size = 50;

        // The Trendyol QnA filter returns an empty/narrow window unless an
        // explicit date range is supplied, so default to the last two years.
        $startDate = Carbon::now()->subYears(2)->getTimestampMs();
        $endDate = Carbon::now()->getTimestampMs();

        foreach ($statuses as $status) {
            $page = 0;

            do {
                $response = $this->getQuestions([
                    'status' => $status,
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                    'page' => $page,
                    'size' => $size,
                    'orderByDirection' => 'DESC',
                ]);

                if (isset($response['error'])) {
                    throw new \Exception($response['message']);
                }

                $content = $response['content'] ?? [];
                $totalPages = (int) ($response['totalPages'] ?? 1);

                foreach ($content as $item) {
                    try {
                        $question = Question::updateOrCreate(
                            [
                                'user_marketplace_credential_id' => $credentialId,
                                'remote_id' => (string) ($item['id'] ?? ''),
                            ],
                            [
                                'question_text' => $item['text'] ?? '',
                                'answer_text' => $item['answer']['text'] ?? null,
                                'status' => $item['status'] ?? $status,
                                'product_name' => $item['productName'] ?? null,
                                'question_date' => isset($item['creationDate']) ? Carbon::createFromTimestampMs($item['creationDate']) : null,
                                'answered_date' => isset($item['answer']['creationDate']) ? Carbon::createFromTimestampMs($item['answer']['creationDate']) : null,
                                'raw_data' => $item,
                            ]
                        );

                        $question->wasRecentlyCreated ? $stats['created']++ : $stats['updated']++;
                    } catch (\Exception $e) {
                        $stats['failed']++;
                    }
                }

                if ($onProgress) {
                    $onProgress($page, $totalPages, "Status {$status}, page {$page}", $stats);
                }

                $page++;
            } while ($page < $totalPages);
        }

        return $stats;
    }
}
