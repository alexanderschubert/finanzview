<?php

namespace App\Services\Fints;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

/**
 * Laufender Bankvorgang eines Benutzers (Einrichtung oder Abruf)
 * zwischen mehreren Anfragen, z. B. während der pushTAN-Freigabe.
 *
 * Enthält die PIN für die Dauer des Vorgangs – deshalb verschlüsselt
 * im privaten Speicher, nach 15 Minuten ungültig und nach Abschluss
 * sofort gelöscht.
 */
class PendingFintsSession
{
    public const TTL_SECONDS = 900;

    public function put(int $userId, array $data): void
    {
        $data['expires_at'] = time() + self::TTL_SECONDS;

        Storage::disk('local')->put($this->path($userId), Crypt::encryptString(serialize($data)));
    }

    public function get(int $userId, ?int $connectionId = null): ?array
    {
        $disk = Storage::disk('local');

        if (! $disk->exists($this->path($userId))) {
            return null;
        }

        try {
            $data = unserialize(Crypt::decryptString($disk->get($this->path($userId))), ['allowed_classes' => false]);
        } catch (\Throwable) {
            $this->forget($userId);

            return null;
        }

        if (! is_array($data) || ($data['expires_at'] ?? 0) < time()) {
            $this->forget($userId);

            return null;
        }

        if ($connectionId !== null && (int) ($data['connection_id'] ?? 0) !== $connectionId) {
            return null;
        }

        return $data;
    }

    public function update(int $userId, array $changes): void
    {
        $data = $this->get($userId);

        if ($data !== null) {
            $this->put($userId, array_merge($data, $changes));
        }
    }

    public function forget(int $userId): void
    {
        Storage::disk('local')->delete($this->path($userId));
    }

    private function path(int $userId): string
    {
        return 'fints/' . $userId . '.pending';
    }
}
