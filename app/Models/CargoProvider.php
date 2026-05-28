<?php

namespace App\Models;

use App\Support\Enums\CargoLabelFormat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CargoProvider extends Model
{
    use HasFactory;

    protected $table = 'cargo_providers';

    protected $fillable = [
        'code',
        'name',
        'protocol',
        'has_webhook',
        'label_formats',
        'is_active',
        'config',
    ];

    protected function casts(): array
    {
        return [
            'has_webhook' => 'boolean',
            'is_active' => 'boolean',
            'label_formats' => 'array',
            'config' => 'array',
        ];
    }

    public function credentials(): HasMany
    {
        return $this->hasMany(CargoCredential::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCode($query, string $code)
    {
        return $query->where('code', $code);
    }

    public function supportsLabelFormat(CargoLabelFormat $format): bool
    {
        $formats = $this->label_formats ?? [];

        return in_array($format->value, $formats, true);
    }
}
