<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount',
        'description',
        'category',
        'is_business',
        'recurring',
        'date',
        'vendor',
        'receipt_url',
        'tax_deductible',
        'tax_category',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'is_business' => 'boolean',
        'recurring' => 'boolean',
        'tax_deductible' => 'boolean',
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser($query, $user_id)
    {
        return $query->where('user_id', $user_id);
    }

    public function scopeDateRange($query, $from, $to)
    {
        if ($from) {
            $query->where('date', '>=', $from);
        }

        if ($to) {
            $query->where('date', '<=', $to);
        }

        return $query;
    }

    public function scopeCategory($query, $category)
    {
        if ($category) {
            return $query->where('category', $category);
        }
        return $query;
    }

    public function scopeIsBusiness($query, $is_business)
    {
        if ($is_business !== null) {
            return $query->where('is_business', $is_business);
        }
        return $query;
    }

    public function scopeRecurring($query, $recurring)
    {
        if ($recurring !== null) {
            return $query->where('recurring', $recurring);
        }
        return $query;
    }

    public function scopeVendor($query, $vendor)
    {
        if ($vendor) {
            return $query->where('vendor', 'like', '%' . $vendor . '%');
        }
        return $query;
    }

    public function scopeTaxDeductible($query, $tax_deductible = null)
    {
        if ($tax_deductible !== null) {
            return $query->where('tax_deductible', $tax_deductible);
        }
        return $query;
    }

    public function scopeTaxCategory($query, $tax_category)
    {
        if ($tax_category) {
            return $query->where('tax_category', $tax_category);
        }
        return $query;
    }

    public function scopeWithReceipt($query)
    {
        return $query->whereNotNull('receipt_url');
    }

    public function scopeWithoutReceipt($query)
    {
        return $query->whereNull('receipt_url');
    }

    public function scopeAmountRange($query, $min, $max)
    {
        if ($min !== null) {
            $query->where('amount', '>=', $min);
        }

        if ($max !== null) {
            $query->where('amount', '<=', $max);
        }

        return $query;
    }
}
