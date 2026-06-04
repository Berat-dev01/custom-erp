<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_exchange_rates', function (Blueprint $table): void {
            $table->id();
            $table->string('from_currency', 3);
            $table->string('to_currency', 3);
            $table->decimal('rate', 15, 6);
            $table->date('rate_date');
            $table->enum('source', ['manual', 'tcmb', 'api'])->default('manual');
            $table->timestamps();
            $table->unique(['from_currency', 'to_currency', 'rate_date']);
            $table->index('rate_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_exchange_rates');
    }
};
