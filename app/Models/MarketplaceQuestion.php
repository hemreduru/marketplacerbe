<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceQuestion extends Model
{
    /** @use HasFactory<\Database\Factories\MarketplaceQuestionFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'marketplace_id',
        'product_id',
        'marketplace_product_id',
        'marketplace_question_id',
        'marketplace_product_id_value',
        'question_text',
        'answer_text',
        'question_status',
        'customer_name',
        'show_customer_name',
        'product_name',
        'product_sku',
        'question_date',
        'answered_at',
        'marketplace_raw_data',
        'internal_notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'show_customer_name' => 'boolean',
            'question_date' => 'datetime',
            'answered_at' => 'datetime',
            'marketplace_raw_data' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the question.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the marketplace for this question.
     */
    public function marketplace(): BelongsTo
    {
        return $this->belongsTo(Marketplace::class);
    }

    /**
     * Get the product.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the marketplace product.
     */
    public function marketplaceProduct(): BelongsTo
    {
        return $this->belongsTo(MarketplaceProduct::class);
    }
}
