<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_bank_accounts', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('bank_name');
            $table->string('iban', 26)->nullable();
            $table->string('account_number')->nullable();
            $table->string('branch')->nullable();
            $table->string('currency', 3)->default('TRY');
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('account_id')->nullable()->constrained('erp_accounts')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_bank_accounts');
    }
};
