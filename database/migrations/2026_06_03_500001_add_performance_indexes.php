<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // erp_journal_lines: en sık sorgu account_id + entry_date üzerinden
        Schema::table('erp_journal_lines', function (Blueprint $table) {
            $table->index(['account_id', 'journal_entry_id'], 'jl_account_entry_idx');
        });

        // erp_journal_entries: tarih + durum filtresi sık kullanılır
        Schema::table('erp_journal_entries', function (Blueprint $table) {
            $table->index(['entry_date', 'status'], 'je_date_status_idx');
        });

        // erp_stock_movements: ürün/depo/tarih üçlüsü
        Schema::table('erp_stock_movements', function (Blueprint $table) {
            $table->index(['product_id', 'warehouse_id', 'created_at'], 'sm_product_warehouse_date_idx');
        });

        // erp_payslips: run + çalışan çifti
        Schema::table('erp_payslips', function (Blueprint $table) {
            $table->index(['payroll_run_id', 'employee_id'], 'ps_run_employee_idx');
        });

        // erp_invoices: durum + vade tarihi (overdue sorguları)
        Schema::table('erp_invoices', function (Blueprint $table) {
            $table->index(['status', 'due_date'], 'inv_status_due_idx');
        });
    }

    public function down(): void
    {
        Schema::table('erp_journal_lines', fn ($t) => $t->dropIndex('jl_account_entry_idx'));
        Schema::table('erp_journal_entries', fn ($t) => $t->dropIndex('je_date_status_idx'));
        Schema::table('erp_stock_movements', fn ($t) => $t->dropIndex('sm_product_warehouse_date_idx'));
        Schema::table('erp_payslips', fn ($t) => $t->dropIndex('ps_run_employee_idx'));
        Schema::table('erp_invoices', fn ($t) => $t->dropIndex('inv_status_due_idx'));
    }
};
