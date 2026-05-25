<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_marketplace_credential_id',
        'remote_id',
        'question_text',
        'answer_text',
        'status',
        'product_name',
        'question_date',
        'answered_date',
        'raw_data',
    ];

    protected function casts(): array
    {
        return [
            'question_date' => 'datetime',
            'answered_date' => 'datetime',
            'raw_data' => 'array',
        ];
    }

    public function credential(): BelongsTo
    {
        return $this->belongsTo(UserMarketplaceCredential::class, 'user_marketplace_credential_id');
    }

    /**
     * Map this question into the array shape expected by the questions views.
     *
     * @return array<string, mixed>
     */
    public function toViewArray(): array
    {
        $raw = $this->raw_data ?? [];

        return [
            'id' => $this->remote_id,
            'text' => $this->question_text,
            'status' => $this->status,
            'creationDate' => $this->question_date?->getTimestampMs() ?? ($raw['creationDate'] ?? now()->getTimestampMs()),
            'productName' => $this->product_name ?? ($raw['productName'] ?? null),
            'userName' => $raw['userName'] ?? null,
            'imageUrl' => $raw['imageUrl'] ?? null,
            'webUrl' => $raw['webUrl'] ?? null,
            'answer' => $this->answer_text
                ? ['text' => $this->answer_text, 'creationDate' => $this->answered_date?->getTimestampMs() ?? now()->getTimestampMs()]
                : ($raw['answer'] ?? null),
        ];
    }
}
