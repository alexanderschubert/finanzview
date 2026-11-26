<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plan_months', function (Blueprint $table) {
            // Von Hand eingetragener Girokonto-Stand zum Monatsanfang; leer = automatisch.
            $table->decimal('start_balance', 14, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('plan_months', function (Blueprint $table) {
            $table->dropColumn('start_balance');
        });
    }
};
