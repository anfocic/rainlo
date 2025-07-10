<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add missing fields to expenses table
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('receipt_url')->nullable()->after('vendor');
            $table->boolean('tax_deductible')->default(false)->after('receipt_url');
            $table->string('tax_category')->nullable()->after('tax_deductible');
            $table->text('notes')->nullable()->after('tax_category');
        });

        // Add missing fields to incomes table
        Schema::table('incomes', function (Blueprint $table) {
            $table->string('source')->nullable()->after('date');
            $table->string('tax_category')->nullable()->after('source');
            $table->text('notes')->nullable()->after('tax_category');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn(['receipt_url', 'tax_deductible', 'tax_category', 'notes']);
        });

        Schema::table('incomes', function (Blueprint $table) {
            $table->dropColumn(['source', 'tax_category', 'notes']);
        });
    }
};
