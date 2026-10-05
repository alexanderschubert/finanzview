<?php

use App\Models\User;
use App\Services\PayeeService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Empfänger als eigene Zuordnung – der Buchungstext der Bank bleibt unverändert.
            $table->foreignId('payee_id')
                ->nullable()
                ->after('merchant')
                ->constrained('payees')
                ->nullOnDelete();

            $table->index(['user_id', 'payee_id']);
        });

        // Vorhandene Buchungen den Empfängern zuordnen (nach Schreibweise der Händlernamen).
        $service = app(PayeeService::class);

        foreach (User::query()->pluck('id') as $userId) {
            $service->assignUnassigned((int) $userId);
        }
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'payee_id']);
            $table->dropConstrainedForeignId('payee_id');
        });
    }
};
