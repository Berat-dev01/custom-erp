<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense']);
            $table->enum('normal_balance', ['debit', 'credit']);
            $table->foreignId('parent_id')->nullable()->constrained('erp_accounts')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->boolean('allow_manual_entry')->default(true);
            $table->timestamps();
            $table->index(['code', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_accounts');
    }
};
