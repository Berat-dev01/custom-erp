<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_employee_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('erp_employees')->cascadeOnDelete();
            $table->string('name');
            $table->string('type'); // contract, id_copy, certificate, other
            $table->string('file_path');
            $table->date('expiry_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_employee_documents');
    }
};
