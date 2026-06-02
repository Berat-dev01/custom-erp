<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->integer('month');
            $table->enum('status', ['draft', 'processed', 'approved', 'paid'])->default('draft');
            $table->date('pay_date')->nullable();
            $table->decimal('total_gross', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);
            $table->decimal('total_net', 12, 2)->default(0);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->unique(['year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_payroll_runs');
    }
};
