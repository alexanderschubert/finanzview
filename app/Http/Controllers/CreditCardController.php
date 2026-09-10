<?php

namespace App\Http\Controllers;

use App\Models\CreditCard;
use App\Models\FinancialProvider;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CreditCardController extends Controller
{
    public function index(Request $request): View
    {
        $creditCards = $request->user()
            ->creditCards()
            ->with(['provider', 'account'])
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return view('credit-cards.index', [
            'creditCards' => $creditCards,
        ]);
    }

    public function create(Request $request): View
    {
        return view('credit-cards.create', [
            'providers' => $this->activeProviders(),
            'accounts' => $this->activeAccounts($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateData($request);

        $user = $request->user();

        $this->validateProvider($validated['provider_id'] ?? null);
        $this->validateAccount($user, $validated['account_id'] ?? null);

        $validated['user_id'] = $user->id;
        $validated['is_active'] = $request->boolean('is_active', true);

        $user->creditCards()->create($validated);

        return redirect()
            ->route('credit-cards.index')
            ->with('success', 'Kreditkarte wurde erfolgreich erstellt.');
    }

    public function show(Request $request, CreditCard $creditCard): View
    {
        $this->authorizeOwner($request, $creditCard);

        $creditCard->load([
            'provider',
            'account',
            'statements' => fn ($query) => $query
                ->orderByDesc('period_end')
                ->orderByDesc('due_date'),
        ]);

        return view('credit-cards.show', [
            'creditCard' => $creditCard,
        ]);
    }

    public function edit(Request $request, CreditCard $creditCard): View
    {
        $this->authorizeOwner($request, $creditCard);

        return view('credit-cards.edit', [
            'creditCard' => $creditCard,
            'providers' => $this->activeProviders(),
            'accounts' => $this->activeAccounts($request),
        ]);
    }

    public function update(
        Request $request,
        CreditCard $creditCard
    ): RedirectResponse {
        $this->authorizeOwner($request, $creditCard);

        $validated = $this->validateData($request);

        $this->validateProvider($validated['provider_id'] ?? null);
        $this->validateAccount(
            $request->user(),
            $validated['account_id'] ?? null
        );

        $validated['is_active'] = $request->boolean('is_active', false);

        $creditCard->update($validated);

        return redirect()
            ->route('credit-cards.show', $creditCard)
            ->with('success', 'Kreditkarte wurde erfolgreich aktualisiert.');
    }

    public function destroy(
        Request $request,
        CreditCard $creditCard
    ): RedirectResponse {
        $this->authorizeOwner($request, $creditCard);

        $creditCard->delete();

        return redirect()
            ->route('credit-cards.index')
            ->with('success', 'Kreditkarte wurde archiviert.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'issuer' => ['nullable', 'string', 'max:255'],
            'provider_id' => [
                'nullable',
                'integer',
                'exists:financial_providers,id',
            ],
            'account_id' => [
                'nullable',
                'integer',
                'exists:accounts,id',
            ],
            'last_four' => ['nullable', 'digits:4'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'current_balance' => ['required', 'numeric', 'min:0'],
            'billing_day' => ['nullable', 'integer', 'between:1,31'],
            'payment_due_day' => ['nullable', 'integer', 'between:1,31'],
            'color' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    private function activeProviders()
    {
        return FinancialProvider::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    private function activeAccounts(Request $request)
    {
        return $request->user()
            ->accounts()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    private function validateProvider(?int $providerId): void
    {
        if (
            $providerId !== null &&
            ! FinancialProvider::query()
                ->whereKey($providerId)
                ->where('is_active', true)
                ->exists()
        ) {
            abort(403);
        }
    }

    private function validateAccount(User $requestUser, ?int $accountId): void
    {
        if (
            $accountId !== null &&
            ! $requestUser->accounts()
                ->whereKey($accountId)
                ->exists()
        ) {
            abort(403);
        }
    }

    private function authorizeOwner(
        Request $request,
        CreditCard $creditCard
    ): void {
        abort_unless(
            $creditCard->user_id === $request->user()->id,
            403
        );
    }
}
