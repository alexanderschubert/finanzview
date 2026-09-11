<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'DROP INDEX IF EXISTS transactions_recurring_transaction_date_unique_active'
        );

        DB::statement(
            'CREATE UNIQUE INDEX transactions_recurring_transaction_date_unique_active
             ON transactions (recurring_transaction_id, transaction_date)
             WHERE recurring_transaction_id IS NOT NULL
               AND deleted_at IS NULL'
        );
    }

    public function down(): void
    {
        DB::statement(
            'DROP INDEX IF EXISTS transactions_recurring_transaction_date_unique_active'
        );
    }
};
