<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_number')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('national_id', 11)->nullable();
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->foreignId('department_id')->nullable()->constrained('erp_departments')->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained('erp_positions')->nullOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('erp_employees')->nullOnDelete();
            $table->date('hire_date');
            $table->date('termination_date')->nullable();
            $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'intern']);
            $table->enum('status', ['active', 'on_leave', 'terminated'])->default('active');
            $table->string('photo_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // departments.manager_id FK'sini şimdi ekleyebiliriz
        Schema::table('erp_departments', function (Blueprint $table) {
            $table->foreign('manager_id')->references('id')->on('erp_employees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('erp_departments', function (Blueprint $table) {
            $table->dropForeign(['manager_id']);
        });

        Schema::dropIfExists('erp_employees');
    }
};
