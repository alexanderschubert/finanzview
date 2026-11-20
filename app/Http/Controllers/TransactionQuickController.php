<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Bausteine für die Buchungsliste: Schnellerfassung (Dialog) sowie
 * Detailspalte mit schneller Änderung von Kategorie und Notiz.
 * Liefert HTML-Ausschnitte, die per JavaScript eingebettet werden.
 */
class TransactionQuickController extends Controller
{
    /**
     * Dialog „Neue Buchung“.
     */
    public function form(Request $request)
    {
        $user = $request->user();

        $accounts = Account::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $categories = Category::query()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->inTreeOrder();

        $setting = $user->setting;

        $defaultAccountId = $accounts->contains('id', $setting?->default_account_id)
            ? $setting->default_account_id
            : $accounts->first()?->id;

        return view('transactions._quick', compact('accounts', 'categories', 'defaultAccountId'));
    }

    /**
     * Detailspalte einer Buchung.
     */
    public function panel(Request $request, Transaction $transaction)
    {
        abort_unless($transaction->user_id === $request->user()->id, 403);

        $transaction->load(['account', 'transferAccount', 'category', 'payee:id,name', 'tags']);

        $categories = Category::query()
            ->where('user_id', $request->user()->id)
            ->where('is_active', true)
            ->whereIn('type', [$transaction->type, 'both'])
            ->orderBy('name')
            ->get()
            ->inTreeOrder();

        return view('transactions._panel', compact('transaction', 'categories'));
    }

    /**
     * Kategorie und Notiz direkt aus der Detailspalte ändern.
     */
    public function update(Request $request, Transaction $transaction): JsonResponse
    {
        abort_unless($transaction->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'category_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($transaction->type === 'transfer' && ! empty($validated['category_id'])) {
            throw ValidationException::withMessages(['category_id' => 'Umbuchungen haben keine Kategorie.']);
        }

        $category = null;

        if (! empty($validated['category_id'])) {
            $category = Category::query()
                ->where('user_id', $request->user()->id)
                ->where('is_active', true)
                ->find($validated['category_id']);

            if (! $category || ! in_array($category->type, [$transaction->type, 'both'], true)) {
                throw ValidationException::withMessages(['category_id' => 'Diese Kategorie passt nicht zur Buchung.']);
            }
        }

        $transaction->update([
            'category_id' => $category?->id,
            'notes' => isset($validated['notes']) && trim($validated['notes']) !== '' ? trim($validated['notes']) : null,
        ]);

        return response()->json([
            'category' => $category?->display_name,
            'icon' => $category?->icon,
        ]);
    }
}
