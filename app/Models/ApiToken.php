<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Zugriffstoken für die native App. Gespeichert wird nur der SHA-256-Hash.
 */
class ApiToken extends Model
{
    protected $fillable = ['user_id', 'name', 'token_hash', 'last_used_at'];

    protected $hidden = ['token_hash'];

    protected function casts(): array
    {
        return ['last_used_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function hash(string $plain): string
    {
        return hash('sha256', $plain);
    }

    /**
     * Legt ein Token an und liefert es einmalig im Klartext.
     *
     * @return array{0: ApiToken, 1: string}
     */
    public static function issue(User $user, string $name): array
    {
        $plain = Str::random(48);

        $token = static::create([
            'user_id' => $user->id,
            'name' => Str::limit(trim($name) !== '' ? trim($name) : 'App', 100, ''),
            'token_hash' => self::hash($plain),
        ]);

        return [$token, $plain];
    }
}
