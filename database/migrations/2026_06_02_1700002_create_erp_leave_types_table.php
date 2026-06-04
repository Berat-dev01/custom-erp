<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_leave_types', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->integer('days_per_year')->default(0);
            $table->boolean('requires_approval')->default(true);
            $table->boolean('is_paid')->default(true);
            $table->boolean('carry_over')->default(false);
            $table->integer('max_carry_over_days')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_leave_types');
    }
};
