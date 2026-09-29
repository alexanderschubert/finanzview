<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_connections', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // FinanzView-Konto, in das importiert wird.
            $table->foreignId('account_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('name');
            $table->string('bank_code', 20);
            $table->string('url');

            // Anmeldename verschlüsselt. Die PIN wird nie gespeichert.
            $table->text('username');

            $table->unsignedInteger('tan_mode')->nullable();
            $table->string('tan_mode_name')->nullable();
            $table->string('tan_medium')->nullable();

            // Gewähltes Bankkonto
            $table->string('iban', 34)->nullable();
            $table->string('bic', 11)->nullable();
            $table->string('bank_account_number', 30)->nullable();
            $table->string('bank_sub_account', 30)->nullable();

            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_result')->nullable();

            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_connections');
    }
};
