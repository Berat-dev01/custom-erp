<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('erp_notification_preferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('notification_type');
            $table->boolean('in_app')->default(true);
            $table->boolean('email')->default(true);
            $table->timestamps();
            $table->unique(['user_id', 'notification_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('erp_notification_preferences');
    }
};
