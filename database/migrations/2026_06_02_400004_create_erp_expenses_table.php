<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_expenses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('category', ['office', 'travel', 'utilities', 'salary', 'rent', 'marketing', 'other']);
            $table->decimal('amount', 12, 2);
            $table->date('expense_date');
            $table->enum('payment_method', ['cash', 'bank_transfer', 'credit_card', 'other']);
            $table->string('receipt_path')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_expenses');
    }
};
