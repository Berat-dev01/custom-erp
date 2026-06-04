<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_depreciation_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('erp_assets')->cascadeOnDelete();
            $table->integer('year');
            $table->integer('month');
            $table->decimal('amount', 12, 2);
            $table->decimal('book_value_after', 12, 2);
            $table->timestamps();
            $table->unique(['asset_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_depreciation_entries');
    }
};
