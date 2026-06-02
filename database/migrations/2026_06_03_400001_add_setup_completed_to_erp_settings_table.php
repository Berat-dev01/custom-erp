<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('erp_settings', function (Blueprint $table) {
            $table->boolean('setup_completed')->default(false)->after('invoice_next_number');
        });
    }

    public function down(): void
    {
        Schema::table('erp_settings', function (Blueprint $table) {
            $table->dropColumn('setup_completed');
        });
    }
};
