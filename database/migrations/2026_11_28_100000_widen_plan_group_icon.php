<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Platz für „lucide:<name>“ (Kategorien haben schon 255 Zeichen).
        Schema::table('plan_groups', function (Blueprint $table) {
            $table->string('icon', 60)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('plan_groups', function (Blueprint $table) {
            $table->string('icon', 20)->nullable()->change();
        });
    }
};
