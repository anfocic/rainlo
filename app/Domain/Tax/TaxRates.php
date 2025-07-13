<?php

namespace App\Domain\Tax;

class TaxRates
{
    public const INCOME_TAX_RATES = [
        'standard_rate' => 0.20,
        'higher_rate' => 0.40,
    ];

    public const INCOME_TAX_BANDS = [
        'single' => 44000,
        'married_one_income' => 53000,
        'married_two_incomes_base' => 53000,
        'married_two_incomes_max_increase' => 35000,
        'single_parent' => 48000,
    ];

    public const USC_BANDS = [
        ['limit' => 12012, 'rate' => 0.005],
        ['limit' => 27382, 'rate' => 0.02],
        ['limit' => 70044, 'rate' => 0.03],
        ['limit' => null, 'rate' => 0.08],
    ];

    public const PRSI_RATE = 0.042;

    public const TAX_CREDITS = [
        'single_person' => 2000,
        'married_person' => 4000,
        'employee_paye' => 2000,
        'single_parent_child_carer' => 1900,
    ];

    public const YEAR = 2025;

    public static function asArray(): array
    {
        return [
            'income_tax' => [
                'rates' => self::INCOME_TAX_RATES,
                'bands' => self::INCOME_TAX_BANDS,
            ],
            'usc' => [
                'bands' => self::USC_BANDS,
            ],
            'prsi' => [
                'rate' => self::PRSI_RATE,
            ],
            'tax_credits' => self::TAX_CREDITS,
            'year' => self::YEAR,
        ];
    }
}
