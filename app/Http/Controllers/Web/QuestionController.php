<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendQuestionAnswerRequest;
use App\Models\Marketplace;
use App\Models\UserMarketplaceCredential;
use App\Services\Trendyol\TrendyolQuestionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class QuestionController extends Controller
{
    protected function getService()
    {
        $user = Auth::user();
        $trendyol = Marketplace::where('slug', 'trendyol')->first();
        $credential = UserMarketplaceCredential::where('user_id', $user->id)
            ->where('marketplace_id', $trendyol->id)
            ->first();

        if (!$credential) {
            return null;
        }

        return new TrendyolQuestionService(
            $credential->api_key,
            $credential->api_secret,
            $credential->additional_credentials['seller_id'] ?? '',
            false // Assuming prod for now, or check config
        );
    }

    public function index(Request $request)
    {
        $service = $this->getService();
        if (!$service) {
            return redirect()->route('marketplace.settings')->with('error', __('common.please_connect_trendyol'));
        }

        $status = $request->input('status', 'WAITING_FOR_ANSWER');
        $page = $request->input('page', 0);
        $size = 20;

        $response = $service->getQuestions([
            'status' => $status,
            'page' => $page,
            'size' => $size,
            'orderByDirection' => 'DESC'
        ]);

        $questions = $response['content'] ?? [];
        $totalElements = $response['totalElements'] ?? 0;
        $totalPages = $response['totalPages'] ?? 0;
        $marketplaceName = 'Trendyol';

        if ($request->ajax() && $request->has('partial')) {
            return view('questions.list', compact('questions', 'status', 'page', 'totalPages', 'totalElements', 'marketplaceName'));
        }

        return view('questions.index', compact('questions', 'status', 'page', 'totalPages', 'totalElements', 'marketplaceName'));
    }

    public function answer(SendQuestionAnswerRequest $request)
    {
        try {
            // Mock response in debug mode
            if (config('app.debug')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Debug Mode: Answer simulated successfully.',
                    'data' => ['text' => $request->answer]
                ]);
            }

            $service = $this->getService();
            if (!$service) {
                return response()->json(['success' => false, 'message' => __('common.please_connect_trendyol')], 400);
            }

            $result = $service->answerQuestion($request->question_id, $request->answer);

            if (isset($result['error'])) {
                Log::error('Question answer failed: ' . $result['message']);
                return response()->json(['success' => false, 'message' => $result['message']], 500);
            }

            return response()->json(['success' => true, 'message' => __('common.answer_sent')]);
        } catch (\Exception $e) {
            Log::error('Question answer exception: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => __('common.error_occurred')], 500);
        }
    }
}
