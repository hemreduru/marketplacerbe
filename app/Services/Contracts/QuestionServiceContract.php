<?php

namespace App\Services\Contracts;

interface QuestionServiceContract
{
    /**
     * Fetch a page of customer questions from the marketplace.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getQuestions(array $filters = []): array;

    /**
     * Submit an answer to a customer question on the marketplace.
     *
     * @return array<string, mixed>
     */
    public function answerQuestion(int $questionId, string $text): array;

    /**
     * Pull customer questions for the given credential into local storage.
     *
     * @return array{created: int, updated: int, failed: int}
     */
    public function syncQuestions(int $credentialId, ?callable $onProgress = null): array;
}
