<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Monatsplan: ein Plan pro Monat. Er bucht nichts, er dient nur zum Planen.
        Schema::create('plan_months', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Immer der erste Tag des Monats.
            $table->date('month');

            $table->timestamps();

            $table->unique(['user_id', 'month']);
        });

        Schema::create('plan_groups', function (Blueprint $table) {
            $table->id();

            $table->foreignId('plan_month_id')->constrained()->cascadeOnDelete();

            $table->string('name');

            // expense, income, rate (Raten) oder loan (Kredite)
            $table->string('kind', 20)->default('expense');

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['plan_month_id', 'sort_order']);
        });

        Schema::create('plan_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('plan_group_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('note')->nullable();

            // Rhythmus: monthly, quarterly, semiannual oder yearly. Der Betrag gilt je Zahlung.
            $table->string('interval', 20)->default('monthly');

            // Beginn bzw. erste Fälligkeit. Bei Rhythmus > monatlich legt der Monat fest, wann gezahlt wird.
            $table->date('starts_on')->nullable();

            // Ende bzw. letzte Rate; danach zählt der Posten nicht mehr.
            $table->date('ends_on')->nullable();

            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['plan_group_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_items');
        Schema::dropIfExists('plan_groups');
        Schema::dropIfExists('plan_months');
    }
};
