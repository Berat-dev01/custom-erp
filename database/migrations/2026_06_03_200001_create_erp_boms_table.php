<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_boms', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('erp_products');
            $table->string('version', 10)->default('1.0');
            $table->boolean('is_active')->default(true);
            $table->decimal('quantity', 10, 3)->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['product_id', 'version']);
        });

        Schema::create('erp_bom_components', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bom_id')->constrained('erp_boms')->cascadeOnDelete();
            $table->foreignId('component_id')->constrained('erp_products');
            $table->decimal('quantity', 10, 3);
            $table->string('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('erp_work_orders', function (Blueprint $table): void {
            $table->id();
            $table->string('wo_number')->unique();
            $table->foreignId('bom_id')->constrained('erp_boms');
            $table->foreignId('product_id')->constrained('erp_products');
            $table->foreignId('warehouse_id')->constrained('erp_warehouses');
            $table->decimal('planned_quantity', 10, 3);
            $table->decimal('produced_quantity', 10, 3)->default(0);
            $table->enum('status', ['draft', 'released', 'in_progress', 'completed', 'cancelled'])->default('draft');
            $table->date('planned_start');
            $table->date('planned_end');
            $table->date('actual_start')->nullable();
            $table->date('actual_end')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('erp_work_order_consumptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('work_order_id')->constrained('erp_work_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('erp_products');
            $table->decimal('planned_quantity', 10, 3);
            $table->decimal('actual_quantity', 10, 3)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_work_order_consumptions');
        Schema::dropIfExists('erp_work_orders');
        Schema::dropIfExists('erp_bom_components');
        Schema::dropIfExists('erp_boms');
    }
};
