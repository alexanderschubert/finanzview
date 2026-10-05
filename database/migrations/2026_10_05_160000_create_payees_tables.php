<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Empfänger: einheitlicher Name für Händler/Auftraggeber samt Standardkategorie.
        Schema::create('payees', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('name');

            $table->foreignId('default_category_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete();

            // „Nicht zusammenführen“: nicht mehr als ähnlicher Name vorschlagen.
            $table->boolean('ignore_suggestions')->default(false);

            $table->timestamps();

            $table->index('user_id');
        });

        // Schreibweisen, die zu einem Empfänger gehören (inkl. seines eigenen Namens).
        Schema::create('payee_aliases', function (Blueprint $table) {
            $table->id();

            $table->foreignId('payee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('alias');
            // Kleingeschrieben, Leerzeichen vereinheitlicht – dient dem Abgleich.
            $table->string('alias_key');

            $table->timestamps();

            $table->unique(['user_id', 'alias_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payee_aliases');
        Schema::dropIfExists('payees');
    }
};
