<?php

namespace App\Models;

use Database\Factories\AdCampaignFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Pazaryeri reklam kampanyası (Trendyol Ads / HB Sponsor).
 *
 * @property int $id
 * @property int $user_marketplace_credential_id
 * @property string $marketplace_code
 * @property string $remote_campaign_id
 * @property string $name
 * @property string $status
 */
class AdCampaign extends Model
{
    /** @use HasFactory<AdCampaignFactory> */
    use HasFactory;

    protected $fillable = [
        'user_marketplace_credential_id',
        'marketplace_code',
        'remote_campaign_id',
        'name',
        'status',
        'period_start',
        'period_end',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
        ];
    }

    /**
     * @return BelongsTo<UserMarketplaceCredential, $this>
     */
    public function credential(): BelongsTo
    {
        return $this->belongsTo(UserMarketplaceCredential::class, 'user_marketplace_credential_id');
    }

    /**
     * @return HasMany<AdMetric, $this>
     */
    public function metrics(): HasMany
    {
        return $this->hasMany(AdMetric::class);
    }
}
