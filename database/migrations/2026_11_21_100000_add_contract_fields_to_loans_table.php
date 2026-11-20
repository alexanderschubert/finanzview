<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            // Angaben aus dem Kreditvertrag, nur zur Kontrolle gegenüber dem berechneten Tilgungsplan.
            $table->decimal('effective_rate', 6, 3)->nullable()->after('interest_rate');
            $table->decimal('contract_total', 12, 2)->nullable()->after('installment_amount');
            $table->decimal('financed_costs', 12, 2)->nullable()->after('contract_total');
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn(['effective_rate', 'contract_total', 'financed_costs']);
        });
    }
};
