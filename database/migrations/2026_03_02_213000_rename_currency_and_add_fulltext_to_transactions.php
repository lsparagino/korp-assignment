  <?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->renameColumn('currency', 'source_currency');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->string('destination_currency')->after('source_currency')->default('USD');
        });

        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('transactions', function (Blueprint $table) {
                $table->fullText('reference');
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropFullText(['reference']);
            });
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('destination_currency');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->renameColumn('source_currency', 'currency');
        });
    }
};
