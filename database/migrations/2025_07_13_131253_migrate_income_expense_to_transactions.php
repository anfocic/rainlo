<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrate Income records to transactions
        if (Schema::hasTable('incomes')) {
            DB::table('incomes')->orderBy('id')->chunk(100, function ($incomes) {
                foreach ($incomes as $income) {
                    DB::table('transactions')->insert([
                        'user_id' => $income->user_id,
                        'type' => 'income',
                        'amount' => $income->amount,
                        'description' => $income->description,
                        'category' => $income->category,
                        'date' => $income->date,
                        'is_business' => $income->is_business ?? false,
                        'recurring' => $income->recurring ?? false,
                        'source' => $income->source ?? null,
                        'tax_category' => $income->tax_category ?? null,
                        'notes' => $income->notes ?? null,
                        'receipt_url' => $income->receipt_url ?? null,
                        'created_at' => $income->created_at,
                        'updated_at' => $income->updated_at,
                    ]);
                }
            });
        }

        // Migrate Expense records to transactions
        if (Schema::hasTable('expenses')) {
            DB::table('expenses')->orderBy('id')->chunk(100, function ($expenses) {
                foreach ($expenses as $expense) {
                    DB::table('transactions')->insert([
                        'user_id' => $expense->user_id,
                        'type' => 'expense',
                        'amount' => $expense->amount,
                        'description' => $expense->description,
                        'category' => $expense->category,
                        'date' => $expense->date,
                        'is_business' => $expense->is_business ?? false,
                        'recurring' => $expense->recurring ?? false,
                        'vendor' => $expense->vendor ?? null,
                        'tax_category' => $expense->tax_category ?? null,
                        'notes' => $expense->notes ?? null,
                        'receipt_url' => $expense->receipt_url ?? null,
                        'created_at' => $expense->created_at,
                        'updated_at' => $expense->updated_at,
                    ]);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove all migrated data from transactions table
        DB::table('transactions')->truncate();
    }
};
