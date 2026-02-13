<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->string('address')->nullable()->unique()->after('status');
        });

        // Backfill existing wallets with generated addresses.
        DB::table('wallets')->whereNull('address')->eachById(function ($wallet) {
            DB::table('wallets')
                ->where('id', $wallet->id)
                ->update(['address' => 'bc1q'.Str::lower(Str::random(36))]);
        });

        Schema::table('wallets', function (Blueprint $table) {
            $table->string('address')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn('address');
        });
    }
};
