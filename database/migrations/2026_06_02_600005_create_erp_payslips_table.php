<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_run_id')->constrained('erp_payroll_runs')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('erp_employees');
            $table->decimal('basic_salary', 12, 2);
            $table->decimal('gross_salary', 12, 2);
            $table->decimal('total_deductions', 12, 2);
            $table->decimal('net_salary', 12, 2);
            $table->json('breakdown')->nullable();
            $table->enum('status', ['draft', 'approved', 'paid'])->default('draft');
            $table->timestamps();
            $table->unique(['payroll_run_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_payslips');
    }
};
