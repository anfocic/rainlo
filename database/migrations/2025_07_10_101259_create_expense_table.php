<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->string('description')->nullable();
            $table->string('category')->nullable();
            $table->boolean('is_business')->default(false);
            $table->boolean('recurring')->default(false);
            $table->date('date');
            $table->string('vendor')->nullable();
            $table->string('receipt')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense');
    }
};
