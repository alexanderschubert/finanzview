<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\TransferMatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Vorschläge: Buchungspaare als Umbuchung zusammenfassen.
 */
class TransferSuggestionController extends Controller
{
    public function __construct(
        private TransferMatcher $matcher
    ) {
    }

    public function index()
    {
        return view('transactions.transfers', [
            'pairs' => $this->matcher->suggestions(Auth::id()),
        ]);
    }

    public function merge(Request $request)
    {
        [$expense, $income] = $this->pair($request);

        if (! $this->matcher->merge($expense, $income)) {
            return back()->with('error', 'Diese Buchungen passen nicht (mehr) als Umbuchung zusammen.');
        }

        return back()->with('success', 'Als Umbuchung zusammengefasst.');
    }

    public function mergeAll()
    {
        $count = 0;

        foreach ($this->matcher->suggestions(Auth::id()) as $pair) {
            $count += $this->matcher->merge($pair['expense'], $pair['income']) ? 1 : 0;
        }

        return redirect()
            ->route('transactions.transfers')
            ->with('success', $count === 1 ? '1 Umbuchung zusammengefasst.' : "{$count} Umbuchungen zusammengefasst.");
    }

    public function dismiss(Request $request)
    {
        [$expense, $income] = $this->pair($request);

        $this->matcher->dismiss($expense, $income);

        return back()->with('success', 'Wird nicht mehr als Umbuchung vorgeschlagen.');
    }

    /**
     * @return array{0: Transaction, 1: Transaction}
     */
    private function pair(Request $request): array
    {
        $validated = $request->validate([
            'expense_id' => ['required', 'integer'],
            'income_id' => ['required', 'integer'],
        ]);

        $find = fn (int $id) => Transaction::query()
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        return [$find((int) $validated['expense_id']), $find((int) $validated['income_id'])];
    }
}
