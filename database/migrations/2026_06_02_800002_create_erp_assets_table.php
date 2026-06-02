<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_assets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('asset_code')->unique();
            $table->string('serial_number')->nullable();
            $table->foreignId('category_id')->constrained('erp_asset_categories');
            $table->foreignId('assigned_to')->nullable()->constrained('erp_employees')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained('erp_warehouses')->nullOnDelete();
            $table->date('purchase_date');
            $table->decimal('purchase_price', 12, 2);
            $table->decimal('current_value', 12, 2);
            $table->date('disposal_date')->nullable();
            $table->enum('status', ['active', 'in_repair', 'disposed'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_assets');
    }
};
