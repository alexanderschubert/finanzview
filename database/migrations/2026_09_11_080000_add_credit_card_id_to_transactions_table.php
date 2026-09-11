<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('credit_card_id')
                ->nullable()
                ->after('account_id')
                ->constrained('credit_cards')
                ->nullOnDelete();

            $table->index([
                'credit_card_id',
                'transaction_date',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['credit_card_id']);
            $table->dropIndex([
                'transactions_credit_card_id_transaction_date_index',
            ]);
            $table->dropColumn('credit_card_id');
        });
    }
};
