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
            ->with(['provider', 'account', 'cardAccount'])
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();

        return view('credit-cards.index', [
            'creditCards' => $creditCards,
        ]);
    }

    public function create(Request $request): View
    {
        $accounts = $this->activeAccounts($request);

        return view('credit-cards.create', [
            'providers' => $this->activeProviders(),
            'accounts' => $accounts,
            'suggestedCardAccountId' => $accounts->firstWhere('type', 'credit_card')?->id,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateData($request);

        $user = $request->user();

        $this->validateProvider($validated['provider_id'] ?? null);
        $this->validateAccount($user, $validated['account_id'] ?? null);
        $this->validateAccount($user, $validated['card_account_id'] ?? null);

        // Mit Kartenkonto wird der Saldo berechnet, das Handfeld entfällt.
        if (! empty($validated['card_account_id'])) {
            $validated['current_balance'] = 0;
        }

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
            'cardAccount',
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

        $accounts = $this->activeAccounts($request);

        return view('credit-cards.edit', [
            'creditCard' => $creditCard,
            'providers' => $this->activeProviders(),
            'accounts' => $accounts,
            'suggestedCardAccountId' => $creditCard->card_account_id ?? $this->suggestCardAccount($creditCard, $accounts),
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
        $this->validateAccount($request->user(), $validated['card_account_id'] ?? null);

        // Mit Kartenkonto bleibt der zuletzt eingetragene Handwert unverändert.
        if (! empty($validated['card_account_id'])) {
            unset($validated['current_balance']);
        }

        $validated['is_active'] = $request->boolean('is_active', false);

        $creditCard->update($validated);

        // Offene Abrechnungen mit der neuen Zuordnung neu berechnen.
        $statements = app(\App\Services\CreditCardStatementService::class);

        $creditCard->statements()->where('status', 'open')->get()
            ->each(fn ($statement) => $statements->updateAmount($statement->setRelation('creditCard', $creditCard->fresh())));

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
            'card_account_id' => [
                'nullable',
                'integer',
                'exists:accounts,id',
                'different:account_id',
            ],
            'current_balance' => ['required_without:card_account_id', 'nullable', 'numeric', 'min:0'],
            'billing_day' => ['nullable', 'integer', 'between:1,31'],
            'payment_due_day' => ['nullable', 'integer', 'between:1,31'],
            'color' => ['nullable', 'string', 'max:50'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    /**
     * Kartenkonto für bestehende Karten vorschlagen: gleicher Name bzw.
     * Herausgeber (z. B. Karte „Amex Gold“ → Konto „AMEX“), sonst ein
     * Konto vom Typ Kreditkarte.
     */
    private function suggestCardAccount(CreditCard $creditCard, $accounts): ?int
    {
        $words = collect(preg_split('/[^\pL\pN]+/u', mb_strtolower($creditCard->name . ' ' . $creditCard->issuer)))
            ->filter(fn ($word) => mb_strlen($word) >= 3);

        $candidates = $accounts->reject(fn ($account) => $account->id === $creditCard->account_id);

        $byName = $candidates->first(function ($account) use ($words) {
            $name = mb_strtolower($account->name);

            return $words->contains(fn ($word) => str_contains($name, $word));
        });

        return ($byName ?? $candidates->firstWhere('type', 'credit_card'))?->id;
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
