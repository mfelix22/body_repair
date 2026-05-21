<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use Auditable;

    private const CODE_PREFIX = 'CUST';

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
        static::creating(function (Customer $customer): void {
            if (empty($customer->code)) {
                $customer->code = self::generateNextCode();
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

    public function vehicles(): HasMany
    {
        return $this->hasMany(\App\Models\Vehicle::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
