<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ursprünglichen Ausführungstag (1-31) speichern, damit
     * monatliche/quartalsweise/jährliche Termine nach kurzen
     * Monaten wieder auf den Originaltag zurückspringen
     * (31.01. -> 28.02. -> 31.03.).
     */
    public function up(): void
    {
        Schema::table('recurring_transactions', function (Blueprint $table) {
            $table->unsignedTinyInteger('anchor_day')
                ->nullable()
                ->after('next_date');
        });

        /*
         * Bestehende Datensätze aus next_date befüllen.
         */
        DB::table('recurring_transactions')
            ->whereNotNull('next_date')
            ->orderBy('id')
            ->each(function ($row) {
                DB::table('recurring_transactions')
                    ->where('id', $row->id)
                    ->update([
                        'anchor_day' => (int) date('j', strtotime($row->next_date)),
                    ]);
            });
    }

    public function down(): void
    {
        Schema::table('recurring_transactions', function (Blueprint $table) {
            $table->dropColumn('anchor_day');
        });
    }
};
