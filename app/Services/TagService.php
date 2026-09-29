<?php

namespace App\Services;

use App\Models\Tag;
use App\Models\Transaction;
use Illuminate\Support\Str;

class TagService
{
    public const MAX_TAGS = 10;

    public const MAX_LENGTH = 40;

    /**
     * „#Urlaub 2026, geschäftlich, Urlaub 2026“ → ['Urlaub 2026', 'geschäftlich']
     *
     * @return list<string>
     */
    public function parse(?string $input): array
    {
        $names = [];

        foreach (explode(',', (string) $input) as $name) {
            $name = Str::limit(Str::squish(ltrim(trim($name), '#')), self::MAX_LENGTH, '');

            if ($name !== '' && ! array_key_exists(mb_strtolower($name), $names)) {
                $names[mb_strtolower($name)] = $name;
            }
        }

        return array_slice(array_values($names), 0, self::MAX_TAGS);
    }

    /**
     * Setzt die Tags einer Buchung. Unbekannte Tags werden angelegt,
     * vorhandene ohne Beachtung der Groß-/Kleinschreibung wiederverwendet.
     */
    public function sync(Transaction $transaction, int $userId, ?string $input): void
    {
        $existing = Tag::query()
            ->where('user_id', $userId)
            ->get()
            ->keyBy(fn (Tag $tag) => mb_strtolower($tag->name));

        $ids = [];

        foreach ($this->parse($input) as $name) {
            $tag = $existing[mb_strtolower($name)] ?? Tag::create([
                'user_id' => $userId,
                'name' => $name,
            ]);

            $ids[] = $tag->id;
        }

        $transaction->tags()->sync($ids);
    }
}
