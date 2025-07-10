<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add indexes for expenses table
        Schema::table('expenses', function (Blueprint $table) {
            $table->index(['user_id', 'date']); // Most common filter combination
            $table->index(['user_id', 'category']); // Category filtering
            $table->index(['user_id', 'is_business']); // Business/personal filtering
            $table->index(['user_id', 'tax_deductible']); // Tax deductible filtering
            $table->index(['user_id', 'recurring']); // Recurring filtering
            $table->index(['user_id', 'amount']); // Amount range filtering
            $table->index('vendor'); // Vendor search
            $table->index('tax_category'); // Tax category grouping
        });

        // Add indexes for incomes table
        Schema::table('incomes', function (Blueprint $table) {
            $table->index(['user_id', 'date']); // Most common filter combination
            $table->index(['user_id', 'category']); // Category filtering
            $table->index(['user_id', 'is_business']); // Business/personal filtering
            $table->index(['user_id', 'recurring']); // Recurring filtering
            $table->index(['user_id', 'amount']); // Amount range filtering
            $table->index('source'); // Source search
            $table->index('tax_category'); // Tax category grouping
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'date']);
            $table->dropIndex(['user_id', 'category']);
            $table->dropIndex(['user_id', 'is_business']);
            $table->dropIndex(['user_id', 'tax_deductible']);
            $table->dropIndex(['user_id', 'recurring']);
            $table->dropIndex(['user_id', 'amount']);
            $table->dropIndex(['vendor']);
            $table->dropIndex(['tax_category']);
        });

        Schema::table('incomes', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'date']);
            $table->dropIndex(['user_id', 'category']);
            $table->dropIndex(['user_id', 'is_business']);
            $table->dropIndex(['user_id', 'recurring']);
            $table->dropIndex(['user_id', 'amount']);
            $table->dropIndex(['source']);
            $table->dropIndex(['tax_category']);
        });
    }
};
