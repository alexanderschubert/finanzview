<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Vorhandene E-Mail-Adressen klein schreiben – außer es gäbe dadurch
 * eine Dublette (dann bleibt die Adresse unverändert).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('users')->get(['id', 'email']) as $user) {
            $lower = mb_strtolower(trim($user->email));

            if ($lower === $user->email) {
                continue;
            }

            $taken = DB::table('users')
                ->where('id', '!=', $user->id)
                ->whereRaw('LOWER(email) = ?', [$lower])
                ->exists();

            if (! $taken) {
                DB::table('users')->where('id', $user->id)->update(['email' => $lower]);
            }
        }
    }

    public function down(): void
    {
        //
    }
};
