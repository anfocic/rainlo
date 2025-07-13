<?php

namespace App\Domain\Transaction;

enum TransactionType: string
{
    case INCOME = 'income';
    case EXPENSE = 'expense';

    public function getLabel(): string
    {
        return match($this) {
            self::INCOME => 'Income',
            self::EXPENSE => 'Expense',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::INCOME => 'green',
            self::EXPENSE => 'red',
        };
    }

    public function getIcon(): string
    {
        return match($this) {
            self::INCOME => 'arrow-up',
            self::EXPENSE => 'arrow-down',
        };
    }

    public static function fromString(string $type): self
    {
        return match(strtolower($type)) {
            'income' => self::INCOME,
            'expense' => self::EXPENSE,
            default => throw new \InvalidArgumentException("Invalid transaction type: {$type}"),
        };
    }
}
