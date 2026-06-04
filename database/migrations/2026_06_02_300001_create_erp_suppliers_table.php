<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 20)->nullable()->unique();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('address')->nullable();
            $table->string('contact_person')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->decimal('credit_limit', 12, 2)->default(0);
            $table->integer('payment_terms_days')->default(30);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_suppliers');
    }
};
