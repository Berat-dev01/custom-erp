<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_journal_entries', function (Blueprint $table): void {
            $table->id();
            $table->string('entry_number')->unique();
            $table->date('entry_date');
            $table->enum('type', ['manual', 'invoice', 'payment', 'payroll', 'depreciation', 'adjustment']);
            $table->string('description');
            $table->string('reference')->nullable();
            $table->nullableMorphs('source');
            $table->enum('status', ['draft', 'posted'])->default('draft');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->index(['entry_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_journal_entries');
    }
};
