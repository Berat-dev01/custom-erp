<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['erp_invoices', 'erp_purchase_orders', 'erp_sales_orders'] as $tableName) {
            if (Schema::hasTable($tableName) && ! Schema::hasColumn($tableName, 'exchange_rate')) {
                Schema::table($tableName, function (Blueprint $t): void {
                    $t->decimal('exchange_rate', 15, 6)->default(1.000000);
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['erp_invoices', 'erp_purchase_orders', 'erp_sales_orders'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'exchange_rate')) {
                Schema::table($tableName, fn (Blueprint $t) => $t->dropColumn('exchange_rate'));
            }
        }
    }
};
