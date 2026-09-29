<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('credit_cards', function (Blueprint $table) {
            // Konto mit den Kartenumsätzen (z. B. „AMEX“). Saldo und
            // Abrechnungen werden daraus berechnet.
            $table->foreignId('card_account_id')
                ->nullable()
                ->after('account_id')
                ->constrained('accounts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('credit_cards', function (Blueprint $table) {
            $table->dropConstrainedForeignId('card_account_id');
        });
    }
};
