<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // FinTS-Bankenliste der Deutschen Kreditwirtschaft (vom Administrator
        // importiert, für alle Benutzer gleich).
        Schema::create('fints_institutes', function (Blueprint $table) {
            $table->id();
            $table->string('bank_code', 8)->index();
            $table->string('bic', 11)->nullable();
            $table->string('name');
            $table->string('city')->nullable();
            $table->string('url');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fints_institutes');
    }
};
