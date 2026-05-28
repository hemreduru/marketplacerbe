<?php

namespace App\Services\Marketplaces\Hepsiburada;

use App\Support\ServiceResult;

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
        return ['created' => 0, 'updated' => 0, 'failed' => 0];
    }
}
