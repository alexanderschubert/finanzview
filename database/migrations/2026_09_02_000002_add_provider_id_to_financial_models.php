<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->foreignId('provider_id')
                ->nullable()
                ->after('institution')
                ->constrained('financial_providers')
                ->nullOnDelete();
        });

        Schema::table('credit_cards', function (Blueprint $table) {
            $table->foreignId('provider_id')
                ->nullable()
                ->after('issuer')
                ->constrained('financial_providers')
                ->nullOnDelete();
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->foreignId('provider_id')
                ->nullable()
                ->after('creditor_name')
                ->constrained('financial_providers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropForeign(['provider_id']);
            $table->dropColumn('provider_id');
        });

        Schema::table('credit_cards', function (Blueprint $table) {
            $table->dropForeign(['provider_id']);
            $table->dropColumn('provider_id');
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->dropForeign(['provider_id']);
            $table->dropColumn('provider_id');
        });
    }
};
