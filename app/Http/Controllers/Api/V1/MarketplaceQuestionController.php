<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Marketplace;
use App\Models\MarketplaceProduct;
use App\Models\MarketplaceQuestion;
use App\Models\Product;
use App\Models\UserMarketplaceCredential;
use App\Services\MarketplaceServiceFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MarketplaceQuestionController extends Controller
{
    /**
     * Display a listing of questions.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = MarketplaceQuestion::with(['marketplace', 'product', 'marketplaceProduct']);

            // Filter by marketplace
            if ($request->filled('marketplace_id')) {
                $query->where('marketplace_id', $request->marketplace_id);
            }

            // Filter by question status
            if ($request->filled('question_status')) {
                $query->where('question_status', $request->question_status);
            }

            // Filter by date range
            if ($request->filled('start_date')) {
                $query->where('question_date', '>=', $request->start_date);
            }

            if ($request->filled('end_date')) {
                $query->where('question_date', '<=', $request->end_date);
            }

            // Search by question text or customer name
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('question_text', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('product_name', 'like', "%{$search}%");
                });
            }

            // Order by question date
            $query->orderBy('question_date', 'desc');

            // Pagination
            $perPage = $request->get('per_page', 20);
            $questions = $query->paginate($perPage);

            Log::info("Soru listesi başarıyla getirildi", ['count' => $questions->total()]);

            return response()->json([
                'success' => true,
                'message' => __('api.question.list_success'),
                'data' => $questions,
            ]);
        } catch (\Exception $e) {
            Log::error("Soru listesi getirme hatası: {$e->getMessage()}");

            return response()->json([
                'success' => false,
                'message' => __('api.question.fetch_failed'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified question.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $question = MarketplaceQuestion::with(['marketplace', 'product', 'marketplaceProduct'])
                ->findOrFail($id);

            Log::info("Soru detayı getirildi", ['question_id' => $id]);

            return response()->json([
                'success' => true,
                'message' => __('api.question.show_success'),
                'data' => $question,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning("Soru bulunamadı", ['question_id' => $id]);

            return response()->json([
                'success' => false,
                'message' => __('api.question.not_found'),
            ], 404);
        } catch (\Exception $e) {
            Log::error("Soru detayı getirme hatası: {$e->getMessage()}", ['question_id' => $id]);

            return response()->json([
                'success' => false,
                'message' => __('api.question.fetch_failed'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Fetch questions from marketplace and store them.
     */
    public function fetch(Request $request): JsonResponse
    {
        $request->validate([
            'marketplace_id' => 'required|exists:marketplaces,id',
            'page' => 'nullable|integer|min:0',
            'size' => 'nullable|integer|min:1|max:200',
            'status' => 'nullable|string',
        ]);

        try {
            $credential = UserMarketplaceCredential::findOrFail($request->marketplace_id);
            $service = MarketplaceServiceFactory::make($credential);

            $filters = [
                'page' => $request->get('page', 0),
                'size' => $request->get('size', 50),
            ];

            if ($request->filled('status')) {
                $filters['status'] = $request->status;
            }

            Log::info("Soru verisi çekiliyor", ['marketplace' => $credential->marketplace->name, 'filters' => $filters]);

            $response = $service->getQuestions($filters);

            if (!isset($response['content']) || !is_array($response['content'])) {
                Log::error("API'den geçersiz yanıt alındı", ['response' => $response]);
                throw new \Exception('Invalid API response format');
            }

            $questions = $response['content'];
            $storedCount = 0;
            $updatedCount = 0;
            $errors = [];

            DB::beginTransaction();

            try {
                foreach ($questions as $questionData) {
                    try {
                        $result = $this->storeQuestion($marketplace, $questionData);
                        if ($result['action'] === 'created') {
                            $storedCount++;
                        } else {
                            $updatedCount++;
                        }
                    } catch (\Exception $e) {
                        $errors[] = [
                            'question_id' => $questionData['id'] ?? 'unknown',
                            'error' => $e->getMessage(),
                        ];
                        Log::error("Soru kaydedilirken hata", ['question_id' => $questionData['id'] ?? 'unknown', 'error' => $e->getMessage()]);
                    }
                }

                DB::commit();

                Log::info("Soru senkronizasyonu tamamlandı", ['stored' => $storedCount, 'updated' => $updatedCount, 'errors' => count($errors)]);

                return response()->json([
                    'success' => true,
                    'message' => __('api.question.fetch_success'),
                    'data' => [
                        'total' => count($questions),
                        'stored' => $storedCount,
                        'updated' => $updatedCount,
                        'errors' => $errors,
                        'pagination' => [
                            'page' => $response['page'] ?? 0,
                            'size' => $response['size'] ?? 50,
                            'totalPages' => $response['totalPages'] ?? 1,
                            'totalElements' => $response['totalElements'] ?? count($questions),
                        ],
                    ],
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error("Soru çekme hatası: {$e->getMessage()}");

            return response()->json([
                'success' => false,
                'message' => __('api.question.fetch_failed'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Answer a question.
     */
    public function answer(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'answer' => 'required|string|min:10',
        ]);

        try {
            $question = MarketplaceQuestion::findOrFail($id);
            $marketplace = $question->marketplace;
            $credential = $marketplace->credential;
            $service = MarketplaceServiceFactory::make($credential);

            Log::info("Soru cevapla��ıyor", ['question_id' => $id, 'marketplace_question_id' => $question->marketplace_question_id]);

            $response = $service->answerQuestion($question->marketplace_question_id, $request->answer);

            // Update question with answer
            $question->update([
                'answer_text' => $request->answer,
                'question_status' => 'Answered',
                'answered_at' => now(),
            ]);

            Log::info("Soru cevaplandı", ['question_id' => $id]);

            return response()->json([
                'success' => true,
                'message' => __('api.question.answer_success'),
                'data' => $question->fresh(),
            ]);
        } catch (\Exception $e) {
            Log::error("Soru cevaplama hatası: {$e->getMessage()}", ['question_id' => $id]);

            return response()->json([
                'success' => false,
                'message' => __('api.question.answer_failed'),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store question in database.
     *
     * @param Marketplace $marketplace
     * @param array $questionData
     * @return array
     */
    private function storeQuestion(Marketplace $marketplace, array $questionData): array
    {
        // Find or create question
        $question = MarketplaceQuestion::updateOrCreate(
            [
                'marketplace_id' => $marketplace->id,
                'marketplace_question_id' => $questionData['id'],
            ],
            [
                'user_id' => $marketplace->user_id,
                'marketplace_product_id_value' => $questionData['productId'] ?? null,
                'question_text' => $questionData['text'] ?? '',
                'answer_text' => $questionData['answer']['text'] ?? null,
                'question_status' => $questionData['status'] ?? 'Pending',
                'customer_name' => $questionData['customerName'] ?? null,
                'show_customer_name' => $questionData['showCustomerName'] ?? true,
                'product_name' => $questionData['productName'] ?? null,
                'question_date' => isset($questionData['creationDate']) ? date('Y-m-d H:i:s', $questionData['creationDate'] / 1000) : now(),
                'answered_at' => isset($questionData['answer']['creationDate']) ? date('Y-m-d H:i:s', $questionData['answer']['creationDate'] / 1000) : null,
                'marketplace_raw_data' => $questionData,
            ]
        );

        $action = $question->wasRecentlyCreated ? 'created' : 'updated';

        // Try to link with existing product
        if ($question->marketplace_product_id_value && !$question->marketplace_product_id) {
            $marketplaceProduct = MarketplaceProduct::where('marketplace_id', $marketplace->id)
                ->where('marketplace_product_id', $question->marketplace_product_id_value)
                ->first();

            if ($marketplaceProduct) {
                $question->update([
                    'marketplace_product_id' => $marketplaceProduct->id,
                    'product_id' => $marketplaceProduct->product_id,
                    'product_sku' => $marketplaceProduct->sku,
                ]);
            }
        }

        return ['question' => $question, 'action' => $action];
    }
}
