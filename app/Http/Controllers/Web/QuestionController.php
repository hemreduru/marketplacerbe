<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendQuestionAnswerRequest;
use App\Models\MarketplaceSyncLog;
use App\Services\MarketplaceManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class QuestionController extends Controller
{
    public function __construct(private MarketplaceManager $marketplace) {}

    public function index(Request $request)
    {
        $credential = $this->marketplace->credentialFor(Auth::user());

        if (! $credential) {
            return redirect()->route('marketplace.settings')->with('error', __('common.please_connect_trendyol'));
        }

        $status = $request->input('status', 'WAITING_FOR_ANSWER');
        $page = max(0, (int) $request->input('page', 0));
        $size = 20;

        $query = $credential->questions()
            ->where('status', $status)
            ->orderByDesc('question_date');

        $totalElements = $query->count();
        $totalPages = (int) ceil($totalElements / $size);

        $questions = $query->skip($page * $size)->take($size)->get()
            ->map(fn ($question) => $question->toViewArray())
            ->all();

        $marketplaceName = 'Trendyol';

        if ($request->ajax() && $request->has('partial')) {
            return view('questions.list', compact('questions', 'status', 'page', 'totalPages', 'totalElements', 'marketplaceName'));
        }

        return view('questions.index', compact('questions', 'status', 'page', 'totalPages', 'totalElements', 'marketplaceName'));
    }

    /**
     * Pull the latest questions from the marketplace into local storage.
     */
    public function sync()
    {
        $credential = $this->marketplace->credentialFor(Auth::user());

        if (! $credential) {
            return response()->json(['success' => false, 'message' => __('common.please_connect_trendyol')], 400);
        }

        $log = MarketplaceSyncLog::start($credential->id, 'question');

        try {
            $stats = $this->marketplace->questionService($credential)->syncQuestions($credential->id);

            $credential->update(['last_sync_at' => now()]);
            $log->succeed($stats);

            return response()->json(['success' => true, 'message' => __('common.sync_completed')]);
        } catch (\Exception $e) {
            $log->fail($e->getMessage());
            Log::error('Question sync exception: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => __('common.error_occurred')], 500);
        }
    }

    public function answer(SendQuestionAnswerRequest $request)
    {
        $credential = $this->marketplace->credentialFor(Auth::user());

        if (! $credential) {
            return response()->json(['success' => false, 'message' => __('common.please_connect_trendyol')], 400);
        }

        // Gate live writes: never reach the marketplace unless explicitly enabled.
        if (! config('marketplace.write_enabled')) {
            return response()->json([
                'success' => true,
                'message' => __('common.action_simulated'),
                'data' => ['text' => $request->answer],
            ]);
        }

        try {
            $result = $this->marketplace->questionService($credential)
                ->answerQuestion((int) $request->question_id, $request->answer);

            if (! $result->ok) {
                Log::error('Question answer failed: '.$result->errorMessage);

                return response()->json(['success' => false, 'message' => $result->errorMessage], 500);
            }

            $credential->questions()
                ->where('remote_id', (string) $request->question_id)
                ->update([
                    'status' => 'ANSWERED',
                    'answer_text' => $request->answer,
                    'answered_date' => now(),
                ]);

            return response()->json(['success' => true, 'message' => __('common.answer_sent')]);
        } catch (\Exception $e) {
            Log::error('Question answer exception: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => __('common.error_occurred')], 500);
        }
    }
}
