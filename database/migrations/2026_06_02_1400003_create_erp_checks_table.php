<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_checks', function (Blueprint $table): void {
            $table->id();
            $table->enum('type', ['received', 'issued']);
            $table->string('check_number');
            $table->string('bank_name');
            $table->decimal('amount', 15, 2);
            $table->date('issue_date');
            $table->date('due_date');
            $table->enum('status', ['portfolio', 'sent_to_bank', 'cashed', 'bounced', 'cancelled'])->default('portfolio');
            $table->nullableMorphs('party');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_checks');
    }
};
