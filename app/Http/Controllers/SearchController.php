<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Schnellsuche für die Befehlspalette: Konten, Kategorien, Empfänger, Kredite.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $term = mb_strtolower(trim((string) $request->query('q')));

        if (mb_strlen($term) < 2) {
            return response()->json([]);
        }

        $like = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $term) . '%';
        $user = $request->user();
        $results = [];

        $user->accounts()->whereRaw('LOWER(name) LIKE ?', [$like])->orderBy('name')->limit(5)->get()
            ->each(function ($account) use (&$results) {
                $results[] = ['label' => $account->name, 'group' => 'Konten', 'url' => route('accounts.edit', $account)];
            });

        $user->categories()->whereRaw('LOWER(name) LIKE ?', [$like])->orderBy('name')->limit(5)->get()
            ->each(function ($category) use (&$results) {
                $results[] = ['label' => trim(($category->icon ?: '') . ' ' . $category->name), 'group' => 'Kategorien', 'url' => route('categories.edit', $category)];
            });

        $user->loans()->whereRaw('LOWER(name) LIKE ?', [$like])->orderBy('name')->limit(5)->get()
            ->each(function ($loan) use (&$results) {
                $results[] = ['label' => $loan->name, 'group' => 'Kredite', 'url' => route('loans.show', $loan)];
            });

        \App\Models\Payee::where('user_id', $user->id)->whereRaw('LOWER(name) LIKE ?', [$like])->orderBy('name')->limit(5)->get()
            ->each(function ($payee) use (&$results) {
                $results[] = ['label' => $payee->name, 'group' => 'Empfänger', 'url' => route('transactions.index', ['search' => $payee->name])];
            });

        return response()->json($results);
    }
}
