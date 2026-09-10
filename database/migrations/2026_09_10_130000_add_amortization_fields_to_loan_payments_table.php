<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loan_payments', function (Blueprint $table) {
            $table->decimal('interest_amount', 14, 2)
                ->default(0)
                ->after('amount');

            $table->decimal('principal_amount', 14, 2)
                ->default(0)
                ->after('interest_amount');

            $table->decimal('remaining_amount', 14, 2)
                ->default(0)
                ->after('principal_amount');
        });
    }

    public function down(): void
    {
        Schema::table('loan_payments', function (Blueprint $table) {
            $table->dropColumn([
                'interest_amount',
                'principal_amount',
                'remaining_amount',
            ]);
        });
    }
};
