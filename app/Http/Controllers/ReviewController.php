<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Transaction;
use App\Services\PayeeService;
use App\Services\TransferMatcher;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * „Zu prüfen“: Buchungen ohne Kategorie mit einem Klick zuordnen
 * (und auf Wunsch für den Empfänger merken).
 */
class ReviewController extends Controller
{
    private const LIMIT = 50;

    public function index(Request $request, TransferMatcher $transfers)
    {
        $user = $request->user();

        $query = $user->transactions()
            ->whereNull('category_id')
            ->whereIn('type', ['income', 'expense']);

        $total = (clone $query)->count();

        $items = $query
            ->with(['account:id,name', 'payee:id,name'])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(self::LIMIT)
            ->get();

        $categories = $user->categories()
            ->where('is_active', true)
            ->with('parent')
            ->withCount('transactions')
            ->orderByDesc('transactions_count')
            ->get();

        $forType = fn (string $type) => $categories
            ->filter(fn (Category $category) => in_array($category->type, [$type, 'both'], true))
            ->values();

        return view('transactions.review', [
            'items' => $items,
            'total' => $total,
            'limit' => self::LIMIT,
            'transferCount' => $transfers->count($user->id),
            'expenseCategories' => $forType('expense'),
            'incomeCategories' => $forType('income'),
        ]);
    }

    public function assign(Request $request, Transaction $transaction, PayeeService $payees)
    {
        abort_unless($transaction->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'category_id' => ['required', 'integer'],
            'remember' => ['nullable', 'boolean'],
            'back' => ['nullable', 'in:dashboard,review'],
        ], ['category_id.required' => 'Bitte eine Kategorie wählen.']);

        $category = Category::query()
            ->where('user_id', $request->user()->id)
            ->where('is_active', true)
            ->find($validated['category_id']);

        if (! $category || ! in_array($category->type, [$transaction->type, 'both'], true)) {
            throw ValidationException::withMessages(['category_id' => 'Diese Kategorie passt nicht zur Buchung.']);
        }

        $transaction->update(['category_id' => $category->id]);

        $message = 'Zugeordnet: ' . $category->display_name . '.';

        if ($request->boolean('remember') && $transaction->payee) {
            $payee = $transaction->payee;

            if ($payee->default_category_id === null) {
                $payee->update(['default_category_id' => $category->id]);
            }

            $others = $payees->applyCategory($request->user()->id, $payee->fresh());

            $message .= $others > 0
                ? " Für „{$payee->name}“ gemerkt, {$others} weitere Buchungen zugeordnet."
                : " Für „{$payee->name}“ gemerkt.";
        }

        $target = $request->input('back') === 'dashboard' ? 'dashboard' : 'transactions.review';

        return redirect()->route($target)->with('success', $message);
    }
}
