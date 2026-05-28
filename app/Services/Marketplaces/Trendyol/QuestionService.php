<?php

namespace App\Services\Marketplaces\Trendyol;

use App\Models\Question;
use App\Support\ServiceResult;
use Carbon\Carbon;

class QuestionService
{
    public function __construct(protected Client $client) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return ServiceResult<array<string, mixed>>
     */
    public function getQuestions(array $filters = []): ServiceResult
    {
        $path = '/integration/qna/sellers/'.$this->client->getSellerId().'/questions/filter';

        if (! isset($filters['status'])) {
            $filters['status'] = 'WAITING_FOR_ANSWER';
        }

        return $this->client->get($path, $filters);
    }

    /**
     * @return ServiceResult<array<string, mixed>>
     */
    public function answerQuestion(int $questionId, string $text): ServiceResult
    {
        $path = '/integration/qna/sellers/'.$this->client->getSellerId()
            .'/questions/'.$questionId.'/answer';

        return $this->client->post($path, ['text' => $text]);
    }

    /**
     * @return array{created: int, updated: int, failed: int}
     */
    public function syncQuestions(int $credentialId, ?callable $onProgress = null): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'failed' => 0];
        $statuses = ['WAITING_FOR_ANSWER', 'ANSWERED', 'REPORTED', 'REJECTED'];
        $size = 50;

        $startDate = Carbon::now()->subYears(2)->getTimestampMs();
        $endDate = Carbon::now()->getTimestampMs();

        foreach ($statuses as $status) {
            $page = 0;

            do {
                $result = $this->getQuestions([
                    'status' => $status,
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                    'page' => $page,
                    'size' => $size,
                    'orderByDirection' => 'DESC',
                ]);

                if (! $result->ok) {
                    throw new \RuntimeException($result->errorMessage ?? 'Trendyol question API hatası');
                }

                $content = $result->data['content'] ?? [];
                $totalPages = (int) ($result->data['totalPages'] ?? 1);

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
                                'question_date' => isset($item['creationDate'])
                                    ? Carbon::createFromTimestampMs($item['creationDate'])
                                    : null,
                                'answered_date' => isset($item['answer']['creationDate'])
                                    ? Carbon::createFromTimestampMs($item['answer']['creationDate'])
                                    : null,
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
