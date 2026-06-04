<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('erp_invoices')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('erp_products')->nullOnDelete();
            $table->string('description');
            $table->decimal('quantity', 12, 3)->default(1);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('tax_rate', 5, 2)->default(20);
            $table->decimal('discount_rate', 5, 2)->default(0);
            $table->decimal('line_total', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_invoice_items');
    }
};
