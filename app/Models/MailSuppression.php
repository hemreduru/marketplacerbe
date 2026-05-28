<?php

namespace App\Models;

use Database\Factories\MailSuppressionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $email
 * @property string $reason
 * @property array|null $raw
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class MailSuppression extends Model
{
    /** @use HasFactory<MailSuppressionFactory> */
    use HasFactory;

    protected $fillable = [
        'email',
        'reason',
        'raw',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'raw' => 'array',
        ];
    }
}
