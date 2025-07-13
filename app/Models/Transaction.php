<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

/**
 * @method static Builder forUser(int $userId)
 * @method static Builder dateRange(?string $from, ?string $to)
 * @method static Builder category(?string $category)
 * @method static Builder isBusiness(?bool $isBusiness)
 * @method static Builder recurring(?bool $recurring)
 * @method static Builder amountRange(?float $min, ?float $max)
 * @method static Builder vendor(?string $vendor)
 * @method static Builder source(?string $source)
 */
class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'description',
        'category',
        'date',
        'is_business',
        'recurring',
        'vendor',
        'source',
        'tax_category',
        'notes',
        'receipt_url',
    ];

    protected $casts = [
        'date' => 'date',
        'is_business' => 'boolean',
        'recurring' => 'boolean',
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeDateRange(Builder $query, ?string $from, ?string $to): Builder
    {
        if ($from) {
            $query->where('date', '>=', $from);
        }

        if ($to) {
            $query->where('date', '<=', $to);
        }

        return $query;
    }

    public function scopeCategory(Builder $query, ?string $category): Builder
    {
        if ($category) {
            $query->where('category', $category);
        }

        return $query;
    }

    public function scopeIsBusiness(Builder $query, ?bool $isBusiness): Builder
    {
        if ($isBusiness !== null) {
            $query->where('is_business', $isBusiness);
        }

        return $query;
    }

    public function scopeRecurring(Builder $query, ?bool $recurring): Builder
    {
        if ($recurring !== null) {
            $query->where('recurring', $recurring);
        }

        return $query;
    }

    public function scopeAmountRange(Builder $query, ?float $min, ?float $max): Builder
    {
        if ($min !== null) {
            $query->where('amount', '>=', $min);
        }

        if ($max !== null) {
            $query->where('amount', '<=', $max);
        }

        return $query;
    }

    public function scopeVendor(Builder $query, ?string $vendor): Builder
    {
        if ($vendor) {
            $query->where('vendor', 'like', "%{$vendor}%");
        }

        return $query;
    }

    public function scopeSource(Builder $query, ?string $source): Builder
    {
        if ($source) {
            $query->where('source', 'like', "%{$source}%");
        }

        return $query;
    }

    // Helper methods
    public function isIncome(): bool
    {
        return $this->type === 'income';
    }

    public function isExpense(): bool
    {
        return $this->type === 'expense';
    }
}
