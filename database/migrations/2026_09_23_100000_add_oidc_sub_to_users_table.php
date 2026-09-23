<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * oidc_sub speichert die eindeutige Benutzer-ID (Claim "sub")
     * des OIDC-Providers, mit der ein Konto verknüpft ist.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('oidc_sub')
                ->nullable()
                ->unique()
                ->after('last_login_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['oidc_sub']);
            $table->dropColumn('oidc_sub');
        });
    }
};
