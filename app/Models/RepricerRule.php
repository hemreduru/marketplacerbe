<?php

namespace App\Models;

use Database\Factories\RepricerRuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Kural tabanlı repricer kuralı (ML değil).
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $master_product_id
 * @property string $name
 * @property string $strategy
 * @property string|null $min_price
 * @property string|null $max_price
 * @property string|null $target_margin
 * @property string|null $undercut_amount
 * @property bool $is_active
 * @property Carbon|null $last_run_at
 */
class RepricerRule extends Model
{
    /** @use HasFactory<RepricerRuleFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'master_product_id',
        'name',
        'strategy',
        'min_price',
        'max_price',
        'target_margin',
        'undercut_amount',
        'is_active',
        'last_run_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'min_price' => 'decimal:4',
            'max_price' => 'decimal:4',
            'target_margin' => 'decimal:2',
            'undercut_amount' => 'decimal:4',
            'is_active' => 'boolean',
            'last_run_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<MasterProduct, $this>
     */
    public function masterProduct(): BelongsTo
    {
        return $this->belongsTo(MasterProduct::class);
    }
}
