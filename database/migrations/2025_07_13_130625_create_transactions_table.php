<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['income', 'expense']);
            $table->decimal('amount', 10, 2);
            $table->string('description');
            $table->string('category')->nullable();
            $table->date('date');
            $table->boolean('is_business')->default(false);
            $table->boolean('recurring')->default(false);
            $table->string('vendor')->nullable(); // For expenses
            $table->string('source')->nullable(); // For income
            $table->string('tax_category')->nullable();
            $table->text('notes')->nullable();
            $table->string('receipt_url')->nullable();
            $table->timestamps();

            // Indexes for better performance
            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'date']);
            $table->index(['user_id', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
