<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // „Keine Umbuchung“: nicht mehr als mögliches Umbuchungspaar vorschlagen.
            $table->boolean('transfer_dismissed')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('transfer_dismissed');
        });
    }
};
