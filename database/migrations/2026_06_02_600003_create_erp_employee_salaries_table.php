<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_employee_salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('erp_employees')->cascadeOnDelete();
            $table->decimal('basic_salary', 12, 2);
            $table->string('currency', 3)->default('TRY');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_employee_salaries');
    }
};
