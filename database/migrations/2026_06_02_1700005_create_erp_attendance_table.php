<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_attendance', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('employee_id')->constrained('erp_employees');
            $table->date('date');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->decimal('work_hours', 4, 2)->nullable();
            $table->decimal('overtime_hours', 4, 2)->default(0);
            $table->enum('status', ['present', 'absent', 'on_leave', 'holiday', 'half_day'])->default('present');
            $table->timestamps();
            $table->unique(['employee_id', 'date']);
            $table->index(['employee_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_attendance');
    }
};
