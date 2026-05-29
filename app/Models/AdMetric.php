<?php

namespace App\Models;

use Database\Factories\AdMetricFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Reklam kampanyasının günlük metrikleri.
 *
 * @property int $id
 * @property int $ad_campaign_id
 * @property Carbon $date
 * @property string $spend
 * @property string $attributed_revenue
 * @property int $impressions
 * @property int $clicks
 * @property int $orders
 */
class AdMetric extends Model
{
    /** @use HasFactory<AdMetricFactory> */
    use HasFactory;

    protected $fillable = [
        'ad_campaign_id',
        'date',
        'spend',
        'attributed_revenue',
        'impressions',
        'clicks',
        'orders',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'spend' => 'decimal:4',
            'attributed_revenue' => 'decimal:4',
            'impressions' => 'integer',
            'clicks' => 'integer',
            'orders' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<AdCampaign, $this>
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(AdCampaign::class, 'ad_campaign_id');
    }
}
