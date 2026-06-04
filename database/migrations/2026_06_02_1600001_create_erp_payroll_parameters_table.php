<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_payroll_parameters', function (Blueprint $table): void {
            $table->id();
            $table->integer('year')->unique();
            $table->decimal('minimum_wage', 10, 2);
            $table->decimal('sgk_worker_rate', 5, 4)->default(0.1400);
            $table->decimal('sgk_employer_rate', 5, 4)->default(0.1550);
            $table->decimal('unemployment_worker_rate', 5, 4)->default(0.0100);
            $table->decimal('unemployment_employer_rate', 5, 4)->default(0.0200);
            $table->decimal('stamp_tax_rate', 6, 5)->default(0.00759);
            $table->json('income_tax_brackets');
            $table->decimal('agi_single', 10, 2)->default(0);
            $table->decimal('agi_married_spouse_not_working', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_payroll_parameters');
    }
};
