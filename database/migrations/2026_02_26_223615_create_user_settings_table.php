<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('notify_money_received')->default(true);
            $table->boolean('notify_money_sent')->default(true);
            $table->boolean('notify_transaction_approved')->default(true);
            $table->boolean('notify_transaction_rejected')->default(true);
            $table->boolean('notify_approval_needed')->default(true);
            $table->string('date_format', 10)->default('en-GB');
            $table->string('number_format', 10)->default('en-GB');
            $table->decimal('daily_transaction_limit', 15, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_settings');
    }
};
