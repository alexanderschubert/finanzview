<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * Notfall-Befehl: Benutzer zum (aktiven) Administrator machen,
 * z. B. wenn man sich aus der Administration ausgesperrt hat.
 *
 *   docker exec -it finanzview php artisan finanzview:admin max@example.com
 */
class MakeAdmin extends Command
{
    protected $signature = 'finanzview:admin {email : E-Mail-Adresse des Benutzers}';

    protected $description = 'Macht einen Benutzer zum Administrator und aktiviert ihn';

    public function handle(): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $this->error("Kein Benutzer mit der E-Mail-Adresse {$email} gefunden.");

            return self::FAILURE;
        }

        $user->forceFill(['is_admin' => true, 'is_active' => true])->save();

        $this->info("{$user->name} ({$user->email}) ist jetzt Administrator.");

        return self::SUCCESS;
    }
}
