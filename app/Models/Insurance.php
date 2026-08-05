<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Insurance extends Model
{
    private const CODE_PREFIX = 'INS';

    protected $fillable = [
        'code',
        'name',
        'phone',
        'email',
        'address',
        'npwp',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Insurance $insurance): void {
            if (empty($insurance->code)) {
                $insurance->code = self::generateNextCode();
            }
        });
    }

    public static function generateNextCode(): string
    {
        $maxNumber = 0;

        self::query()
            ->where('code', 'like', self::CODE_PREFIX . '-%')
            ->pluck('code')
            ->each(function (string $code) use (&$maxNumber): void {
                if (preg_match('/^' . self::CODE_PREFIX . '-(\d+)$/', $code, $matches)) {
                    $currentNumber = (int) $matches[1];
                    if ($currentNumber > $maxNumber) {
                        $maxNumber = $currentNumber;
                    }
                }
            });

        return sprintf('%s-%05d', self::CODE_PREFIX, $maxNumber + 1);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }
}
