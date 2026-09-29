<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mehrere Bankkonten pro Bankverbindung (z. B. Girokonto und
 * Sparbuch mit einem Online-Banking-Zugang) samt Kontostand.
 *
 * Die bisherigen Einzelkonto-Spalten in bank_connections werden
 * übernommen und bleiben danach ungenutzt stehen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_connection_accounts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('bank_connection_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('account_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('iban', 34);
            $table->string('bic', 11)->nullable();
            $table->string('account_number', 30)->nullable();
            $table->string('sub_account', 30)->nullable();

            // Kontostand laut Bank beim letzten Abruf.
            $table->decimal('bank_balance', 14, 2)->nullable();
            $table->date('balance_date')->nullable();

            // Neu angelegtes FinanzView-Konto: Startsaldo beim ersten Abruf übernehmen.
            $table->boolean('adopt_balance')->default(false);

            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_error')->nullable();

            $table->timestamps();

            $table->unique(['bank_connection_id', 'iban']);
        });

        foreach (DB::table('bank_connections')->whereNotNull('iban')->whereNotNull('account_id')->get() as $connection) {
            DB::table('bank_connection_accounts')->insert([
                'bank_connection_id' => $connection->id,
                'account_id' => $connection->account_id,
                'iban' => $connection->iban,
                'bic' => $connection->bic,
                'account_number' => $connection->bank_account_number,
                'sub_account' => $connection->bank_sub_account,
                'last_synced_at' => $connection->last_synced_at,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_connection_accounts');
    }
};
