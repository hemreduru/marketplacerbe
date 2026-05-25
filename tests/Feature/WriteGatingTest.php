<?php

use App\Models\Question;
use Illuminate\Support\Facades\Http;

test('answering a question is simulated and never reaches the marketplace when writes are disabled', function () {
    config(['marketplace.write_enabled' => false]);
    Http::preventStrayRequests();

    [$user, $credential] = userWithTrendyol();
    $question = Question::factory()->create([
        'user_marketplace_credential_id' => $credential->id,
        'remote_id' => '555',
        'status' => 'WAITING_FOR_ANSWER',
    ]);

    $this->actingAs($user)
        ->post(route('questions.answer'), [
            'question_id' => '555',
            'answer' => 'This is a simulated answer that is long enough.',
        ])
        ->assertOk()
        ->assertJson(['success' => true, 'message' => __('common.action_simulated')]);

    // No HTTP call to the marketplace, and the stored question is untouched.
    Http::assertNothingSent();
    expect($question->fresh()->status)->toBe('WAITING_FOR_ANSWER');
    expect($question->fresh()->answer_text)->toBeNull();
});

test('updating an order status is simulated when writes are disabled', function () {
    config(['marketplace.write_enabled' => false]);
    Http::preventStrayRequests();

    [$user] = userWithTrendyol();

    $this->actingAs($user)
        ->post(route('orders.status'), [
            'package_id' => 12345,
            'status' => 'Picking',
        ])
        ->assertOk()
        ->assertJson(['success' => true, 'message' => __('common.action_simulated')]);

    Http::assertNothingSent();
});

test('answering a question reaches the marketplace and updates the record when writes are enabled', function () {
    config(['marketplace.write_enabled' => true]);

    Http::fake([
        '*/integration/qna/sellers/*/questions/*/answer' => Http::response(['id' => 1], 200),
    ]);

    [$user, $credential] = userWithTrendyol();
    $question = Question::factory()->create([
        'user_marketplace_credential_id' => $credential->id,
        'remote_id' => '777',
        'status' => 'WAITING_FOR_ANSWER',
    ]);

    $this->actingAs($user)
        ->post(route('questions.answer'), [
            'question_id' => '777',
            'answer' => 'A genuine answer that is sufficiently long.',
        ])
        ->assertOk()
        ->assertJson(['success' => true]);

    Http::assertSent(fn ($request) => str_contains($request->url(), '/questions/777/answer'));

    expect($question->fresh()->status)->toBe('ANSWERED');
    expect($question->fresh()->answer_text)->toBe('A genuine answer that is sufficiently long.');
});
