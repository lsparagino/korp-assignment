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
        Schema::table('wallets', function (Blueprint $table) {
            $table->decimal('locked_balance', 15, 2)->default(0.00)->after('address');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->string('status')->default('completed')->after('external');
            $table->string('currency')->default('USD')->after('status');
            $table->decimal('exchange_rate', 15, 6)->default(1.0)->after('currency');
            $table->foreignId('initiator_user_id')->nullable()->after('exchange_rate')
                ->constrained('users')->nullOnDelete();
            $table->foreignId('reviewer_user_id')->nullable()->after('initiator_user_id')
                ->constrained('users')->nullOnDelete();
            $table->string('reject_reason')->nullable()->after('reviewer_user_id');
            $table->string('external_address')->nullable()->after('reject_reason');
            $table->string('external_name')->nullable()->after('external_address');

            $table->index('status');
            $table->index('initiator_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['initiator_user_id']);
            $table->dropForeign(['reviewer_user_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['initiator_user_id']);
            $table->dropColumn([
                'status',
                'currency',
                'exchange_rate',
                'initiator_user_id',
                'reviewer_user_id',
                'reject_reason',
                'external_address',
                'external_name',
            ]);
        });

        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn('locked_balance');
        });
    }
};
