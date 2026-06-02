<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_settings', function (Blueprint $table) {
            $table->id();
            $table->string('company_name')->default('');
            $table->string('company_email')->nullable();
            $table->string('company_phone')->nullable();
            $table->string('company_address')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('currency', 10)->default('TRY');
            $table->string('currency_symbol', 5)->default('₺');
            $table->decimal('default_tax_rate', 5, 2)->default(20.00);
            $table->string('invoice_prefix', 20)->default('INV');
            $table->unsignedInteger('invoice_next_number')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_settings');
    }
};
