<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('transfer_account_id')
                ->nullable()
                ->after('account_id')
                ->constrained('accounts')
                ->nullOnDelete();

            $table->index([
                'transfer_account_id',
                'transaction_date',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['transfer_account_id']);
            $table->dropIndex([
                'transactions_transfer_account_id_transaction_date_index',
            ]);
            $table->dropColumn('transfer_account_id');
        });
    }
};
