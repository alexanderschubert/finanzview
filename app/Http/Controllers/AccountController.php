<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\FinancialProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(Request $request): View
    {
        $accounts = $request->user()
            ->accounts()
            ->with('provider')
            ->orderBy('is_active', 'desc')
            ->orderBy('name')
            ->get();

        return view('accounts.index', [
            'accounts' => $accounts,
        ]);
    }

    public function create(): View
    {
        $providers = FinancialProvider::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('accounts.create', [
            'providers' => $providers,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'institution' => ['nullable', 'string', 'max:255'],
            'provider_id' => ['nullable', 'integer', 'exists:financial_providers,id'],
            'type' => ['required', 'in:checking,savings,credit_card,paypal,cash,investment,loan,other'],
            'currency' => ['required', 'string', 'size:3', 'in:EUR,USD,CHF,GBP'],
            'opening_balance' => ['required', 'numeric'],
            'credit_limit' => ['nullable', 'numeric'],
            'iban' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:50'],
            'icon' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string'],
            'include_in_total' => ['nullable', 'boolean'],
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['include_in_total'] = $request->boolean('include_in_total');
        $validated['is_active'] = true;

        $request->user()->accounts()->create($validated);

        return redirect()
            ->route('accounts.index')
            ->with('success', 'Konto wurde erfolgreich erstellt.');
    }

    public function edit(Request $request, Account $account): View
    {
        abort_unless(
            $account->user_id === $request->user()->id,
            403
        );

        $providers = FinancialProvider::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('accounts.edit', [
            'account' => $account,
            'providers' => $providers,
        ]);
    }

    public function update(
        Request $request,
        Account $account
    ): RedirectResponse {
        abort_unless(
            $account->user_id === $request->user()->id,
            403
        );

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'institution' => ['nullable', 'string', 'max:255'],
            'provider_id' => ['nullable', 'integer', 'exists:financial_providers,id'],
            'type' => ['required', 'in:checking,savings,credit_card,paypal,cash,investment,loan,other'],
            'currency' => ['required', 'string', 'size:3'],
            'opening_balance' => ['required', 'numeric'],
            'credit_limit' => ['nullable', 'numeric'],
            'iban' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:50'],
            'icon' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string'],
            'include_in_total' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['include_in_total'] = $request->boolean('include_in_total');
        $validated['is_active'] = $request->boolean('is_active');

        $account->update($validated);

        return redirect()
            ->route('accounts.index')
            ->with('success', 'Konto wurde aktualisiert.');
    }

    public function destroy(
        Request $request,
        Account $account
    ): RedirectResponse {
        abort_unless(
            $account->user_id === $request->user()->id,
            403
        );

        $account->delete();

        return redirect()
            ->route('accounts.index')
            ->with('success', 'Konto wurde gelöscht.');
    }
}