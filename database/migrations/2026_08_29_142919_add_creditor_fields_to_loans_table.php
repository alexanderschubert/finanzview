<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->string('creditor_name')->nullable()->after('name');
            $table->string('creditor_icon', 20)->nullable()->after('creditor_name');
            $table->string('creditor_color', 20)->nullable()->after('creditor_icon');
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn([
                'creditor_name',
                'creditor_icon',
                'creditor_color',
            ]);
        });
    }
};
