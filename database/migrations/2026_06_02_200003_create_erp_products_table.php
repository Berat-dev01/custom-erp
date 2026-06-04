<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('name');
            $table->string('barcode')->nullable()->unique();
            $table->foreignId('category_id')->nullable()->constrained('erp_product_categories')->nullOnDelete();
            $table->foreignId('unit_id')->constrained('erp_units');
            $table->text('description')->nullable();
            $table->decimal('purchase_price', 12, 2)->default(0);
            $table->decimal('sale_price', 12, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(20.00);
            $table->enum('type', ['product', 'service', 'consumable'])->default('product');
            $table->boolean('track_stock')->default(true);
            $table->decimal('reorder_point', 10, 3)->default(0);
            $table->string('photo_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_products');
    }
};
