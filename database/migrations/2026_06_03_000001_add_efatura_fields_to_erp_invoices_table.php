<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erp_invoices', function (Blueprint $table): void {
            if (! Schema::hasColumn('erp_invoices', 'efatura_uuid')) {
                $table->string('efatura_uuid')->nullable()->unique()->after('reference');
            }
            if (! Schema::hasColumn('erp_invoices', 'efatura_ettn')) {
                $table->string('efatura_ettn')->nullable()->after('efatura_uuid');
            }
            if (! Schema::hasColumn('erp_invoices', 'efatura_status')) {
                $table->enum('efatura_status', ['none', 'pending', 'sent', 'accepted', 'rejected', 'cancelled'])->default('none')->after('efatura_ettn');
            }
            if (! Schema::hasColumn('erp_invoices', 'efatura_sent_at')) {
                $table->timestamp('efatura_sent_at')->nullable()->after('efatura_status');
            }
            if (! Schema::hasColumn('erp_invoices', 'efatura_type')) {
                $table->enum('efatura_type', ['efatura', 'earshiv'])->nullable()->after('efatura_sent_at');
            }
            if (! Schema::hasColumn('erp_invoices', 'efatura_pdf_path')) {
                $table->string('efatura_pdf_path')->nullable()->after('efatura_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('erp_invoices', function (Blueprint $table): void {
            $columns = array_filter(
                ['efatura_uuid', 'efatura_ettn', 'efatura_status', 'efatura_sent_at', 'efatura_type', 'efatura_pdf_path'],
                fn ($col) => Schema::hasColumn('erp_invoices', $col)
            );

            if ($columns) {
                $table->dropColumn(array_values($columns));
            }
        });
    }
};
