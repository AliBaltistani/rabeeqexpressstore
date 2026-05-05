<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use App\Models\Model;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'symbol',
        'exchange_rate',
        'is_default',
        'is_active',
        'decimal_places',
    ];

    protected $casts = [
        'exchange_rate' => 'decimal:6',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public static function getDefault(): ?self
    {
        return self::where('is_default', true)
            ->where('is_active', true)
            ->first();
    }

    public static function getActive(): Builder
    {
        return self::where('is_active', true);
    }

    public static function convert(float $amount, string $from, string $to): float
    {
        if ($from === $to) {
            return $amount;
        }

        $fromCurrency = self::where('code', $from)->first();
        $toCurrency = self::where('code', $to)->first();

        if (!$fromCurrency || !$toCurrency) {
            throw new \Exception("Currency not found: {$from} or {$to}");
        }

        // Convert to base currency (exchange_rate is relative to base)
        $baseAmount = $amount / $fromCurrency->exchange_rate;

        // Convert to target currency
        return $baseAmount * $toCurrency->exchange_rate;
    }
}
