<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('currency', 3);
            $table->decimal('approval_threshold', 15, 2);
            $table->timestamps();

            $table->unique(['company_id', 'currency']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_settings');
    }
};
