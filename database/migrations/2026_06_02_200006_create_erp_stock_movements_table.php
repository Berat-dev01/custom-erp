<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('erp_products');
            $table->foreignId('warehouse_id')->constrained('erp_warehouses');
            $table->enum('type', ['in', 'out', 'transfer', 'adjustment']);
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->index(['product_id', 'warehouse_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_stock_movements');
    }
};
