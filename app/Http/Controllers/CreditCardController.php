<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\CreditCard;
use App\Models\FinancialProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CreditCardController extends Controller
{
    public function index(Request $request): View
    {
        $creditCards = $request->user()
            ->creditCards()
            ->with([
                'provider',
                'account',
            ])
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return view('credit-cards.index', [
            'creditCards' => $creditCards,
        ]);
    }

    public function create(Request $request): View
    {
        $providers = FinancialProvider::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $accounts = $request->user()
            ->accounts()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('credit-cards.create', [
            'providers' => $providers,
            'accounts' => $accounts,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'issuer' => ['nullable', 'string', 'max:255'],
            'provider_id' => ['nullable', 'integer', 'exists:financial_providers,id'],
            'account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'last_four' => ['nullable', 'digits:4'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'current_balance' => ['required', 'numeric', 'min:0'],
            'billing_day' => ['nullable', 'integer', 'between:1,31'],
            'payment_due_day' => ['nullable', 'integer', 'between:1,31'],
            'color' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();

        if (
            isset($validated['provider_id']) &&
            ! FinancialProvider::query()
                ->whereKey($validated['provider_id'])
                ->where('is_active', true)
                ->exists()
        ) {
            abort(403);
        }

        if (
            isset($validated['account_id']) &&
            ! $user->accounts()
                ->whereKey($validated['account_id'])
                ->exists()
        ) {
            abort(403);
        }

        $validated['user_id'] = $user->id;
        $validated['is_active'] = $request->boolean('is_active', true);

        $user->creditCards()->create($validated);

        return redirect()
            ->route('credit-cards.index')
            ->with('success', 'Kreditkarte wurde erfolgreich erstellt.');
    }
}
