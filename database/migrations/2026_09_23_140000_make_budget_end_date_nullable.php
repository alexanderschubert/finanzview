<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Monatliche und jährliche Budgets laufen unbegrenzt weiter.
     * Der BudgetService unterstützt ein leeres Enddatum bereits,
     * die Spalte war aber als Pflichtfeld angelegt. Dadurch wurde
     * im Formular immer das Monatsende vorbelegt und monatliche
     * Budgets liefen nach dem ersten Monat ab.
     */
    public function up(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->date('end_date')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('budgets', function (Blueprint $table) {
            $table->date('end_date')->nullable(false)->change();
        });
    }
};
