<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_bank_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bank_account_id')->constrained('erp_bank_accounts')->cascadeOnDelete();
            $table->enum('type', ['deposit', 'withdrawal', 'transfer']);
            $table->decimal('amount', 15, 2);
            $table->date('transaction_date');
            $table->string('description')->nullable();
            $table->string('reference')->nullable();
            $table->boolean('is_reconciled')->default(false);
            $table->nullableMorphs('source');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->index(['bank_account_id', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_bank_transactions');
    }
};
