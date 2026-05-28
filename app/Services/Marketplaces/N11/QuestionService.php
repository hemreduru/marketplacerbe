<?php

namespace App\Services\Marketplaces\N11;

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
        return ServiceResult::fail('not_implemented', 'N11 soru-cevap API\'si yok.');
    }

    /**
     * @return ServiceResult<array<string, mixed>>
     */
    public function answerQuestion(int $questionId, string $text): ServiceResult
    {
        return ServiceResult::fail('not_implemented', 'N11 soru-cevap API\'si yok.');
    }

    /**
     * @return array{created: int, updated: int, failed: int}
     */
    public function syncQuestions(int $credentialId, ?callable $onProgress = null): array
    {
        return ['created' => 0, 'updated' => 0, 'failed' => 0];
    }
}
