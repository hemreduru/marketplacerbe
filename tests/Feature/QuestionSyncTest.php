<?php

use App\Models\Question;
use Illuminate\Support\Facades\Http;

test('question sync stores questions pulled from the marketplace', function () {
    [$user, $credential] = userWithTrendyol();

    Http::fake([
        '*/integration/qna/sellers/*/questions/filter*' => Http::sequence()
            ->push(['content' => [[
                'id' => 9001,
                'text' => 'Is this product original?',
                'status' => 'WAITING_FOR_ANSWER',
                'productName' => 'Sample Product',
                'creationDate' => now()->getTimestampMs(),
            ]], 'totalPages' => 1])
            ->push(['content' => [], 'totalPages' => 1])
            ->push(['content' => [], 'totalPages' => 1])
            ->push(['content' => [], 'totalPages' => 1]),
    ]);

    $this->actingAs($user)
        ->post(route('questions.sync'))
        ->assertOk()
        ->assertJson(['success' => true]);

    expect(Question::where('user_marketplace_credential_id', $credential->id)->count())->toBe(1);

    $question = Question::first();
    expect($question->remote_id)->toBe('9001');
    expect($question->question_text)->toBe('Is this product original?');
    expect($question->status)->toBe('WAITING_FOR_ANSWER');
});

test('questions index reads from the database', function () {
    [$user, $credential] = userWithTrendyol();

    Question::factory()->count(2)->create([
        'user_marketplace_credential_id' => $credential->id,
        'status' => 'WAITING_FOR_ANSWER',
    ]);
    Question::factory()->answered()->create([
        'user_marketplace_credential_id' => $credential->id,
    ]);

    $this->actingAs($user)
        ->get(route('questions.index', ['status' => 'WAITING_FOR_ANSWER']))
        ->assertOk();
});
