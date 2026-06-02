<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_time_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('erp_projects')->cascadeOnDelete();
            $table->foreignId('task_id')->nullable()->constrained('erp_project_tasks')->nullOnDelete();
            $table->foreignId('employee_id')->constrained('erp_employees')->cascadeOnDelete();
            $table->date('date');
            $table->decimal('hours', 5, 2);
            $table->text('description')->nullable();
            $table->boolean('billable')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_time_entries');
    }
};
