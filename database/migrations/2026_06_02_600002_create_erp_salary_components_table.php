<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_salary_components', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['earning', 'deduction']);
            $table->enum('calculation', ['fixed', 'percentage']);
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('percentage', 5, 2)->default(0);
            $table->string('base')->nullable();
            $table->boolean('is_taxable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_salary_components');
    }
};
