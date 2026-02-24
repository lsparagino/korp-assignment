<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('transactions');

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('group_id')->index();
            $table->foreignId('wallet_id')->constrained('wallets')->cascadeOnDelete();
            $table->foreignId('counterpart_wallet_id')->nullable()->constrained('wallets')->nullOnDelete();
            $table->string('type'); // credit | debit
            $table->decimal('amount', 15, 2);
            $table->boolean('external')->default(false);
            $table->string('reference')->nullable();
            $table->timestamps();
            $table->index('type');
            $table->index('created_at');
            $table->index('amount');
        });

        Schema::table('wallets', function (Blueprint $table) {
            $table->index('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
