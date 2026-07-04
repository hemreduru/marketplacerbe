<?php

namespace App\Services\Marketplaces\Hepsiburada;

use App\Models\Question;
use App\Support\ServiceResult;
use Carbon\Carbon;

/**
 * Hepsiburada müşteri soruları senkronizasyonu.
 */
class QuestionService
{
    public function __construct(protected Client $client) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return ServiceResult<array<string, mixed>>
     */
    public function getQuestions(array $filters = []): ServiceResult
    {
        return $this->client->get('/qna/api/questions', $filters);
    }

    /**
     * @return ServiceResult<array<string, mixed>>
     */
    public function answerQuestion(int $questionId, string $text): ServiceResult
    {
        return $this->client->post('/qna/api/questions/'.$questionId.'/answer', ['text' => $text]);
    }

    /**
     * @return array{created: int, updated: int, failed: int}
     */
    public function syncQuestions(int $credentialId, ?callable $onProgress = null): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'failed' => 0];
        $page = 0;
        $size = 50;

        do {
            $result = $this->getQuestions(['page' => $page, 'size' => $size]);

            if (! $result->ok) {
                break;
            }

            $content = $result->data['content'] ?? $result->data['data'] ?? [];

            if (empty($content)) {
                break;
            }

            foreach ($content as $item) {
                try {
                    $question = Question::updateOrCreate(
                        [
                            'user_marketplace_credential_id' => $credentialId,
                            'remote_id' => (string) ($item['id'] ?? $item['number'] ?? ''),
                        ],
                        [
                            'question_text' => $item['content'] ?? $item['text'] ?? '',
                            'answer_text' => $item['answer']['content'] ?? $item['answer']['text'] ?? null,
                            'status' => $item['status'] ?? 'WAITING_FOR_ANSWER',
                            'product_name' => $item['product']['name'] ?? $item['productName'] ?? null,
                            'question_date' => $this->parseDate($item['createdAt'] ?? $item['creationDate'] ?? null),
                            'answered_date' => $this->parseDate($item['answer']['createdAt'] ?? null),
                            'raw_data' => $item,
                        ]
                    );

                    $question->wasRecentlyCreated ? $stats['created']++ : $stats['updated']++;
                } catch (\Exception $e) {
                    $stats['failed']++;
                }
            }

            if ($onProgress) {
                $onProgress($page, null, "Questions page {$page}", $stats);
            }

            $page++;
        } while (count($content) === $size);

        return $stats;
    }

    protected function parseDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return Carbon::createFromTimestampMs((int) $value);
        }

        try {
            return Carbon::parse((string) $value);
        } catch (\Exception) {
            return null;
        }
    }
}
