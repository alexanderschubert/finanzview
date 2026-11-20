{{-- Dialog „Neue Buchung“ (wird per JavaScript in den Dialog der Seite geladen). --}}

<form method="POST" action="{{ route('transactions.store') }}" data-quick-form class="space-y-4">
    @csrf

    <div class="flex items-center justify-between">
        <h2 class="text-lg font-bold tracking-tight text-slate-900 dark:text-white">Neue Buchung</h2>
        <button type="button" data-quick-close class="w-8 h-8 rounded-full flex items-center justify-center text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-900/5 dark:hover:bg-white/10 transition" aria-label="Schließen">
            <x-icon name="x" class="w-5 h-5" />
        </button>
    </div>

    {{-- ART --}}

    <div class="grid grid-cols-2 gap-1 rounded-xl bg-slate-900/5 dark:bg-white/5 p-1" role="group" aria-label="Art">
        <label class="cursor-pointer">
            <input type="radio" name="type" value="expense" checked class="sr-only peer" data-quick-type>
            <span class="block rounded-lg py-2 text-center text-sm font-semibold text-slate-500 dark:text-slate-400 peer-checked:bg-white dark:peer-checked:bg-white/15 peer-checked:text-red-600 dark:peer-checked:text-red-300 peer-checked:shadow-sm transition">Ausgabe</span>
        </label>
        <label class="cursor-pointer">
            <input type="radio" name="type" value="income" class="sr-only peer" data-quick-type>
            <span class="block rounded-lg py-2 text-center text-sm font-semibold text-slate-500 dark:text-slate-400 peer-checked:bg-white dark:peer-checked:bg-white/15 peer-checked:text-emerald-600 dark:peer-checked:text-emerald-300 peer-checked:shadow-sm transition">Einnahme</span>
        </label>
    </div>

    {{-- BETRAG --}}

    <div>
        <label for="quick-amount" class="sr-only">Betrag</label>
        <div class="flex items-baseline justify-center gap-2">
            <input id="quick-amount" name="amount" type="text" inputmode="decimal" autocomplete="off" required placeholder="0,00" data-quick-amount
                class="w-full bg-transparent text-center fv-display text-[44px] leading-tight font-extrabold tabular-nums text-slate-900 dark:text-white placeholder:text-slate-300 dark:placeholder:text-slate-600 focus:outline-none">
            <span class="text-2xl font-semibold text-slate-400">€</span>
        </div>
        <p class="hidden text-center text-sm text-red-600 dark:text-red-400" data-quick-error="amount"></p>
    </div>

    {{-- WAS --}}

    <div>
        <label for="quick-description" class="fv-label">Was war es?</label>
        <input id="quick-description" name="description" type="text" required maxlength="255" autocomplete="off" placeholder="z. B. REWE, Gehalt, Tanken" class="fv-input">
        <p class="hidden mt-1.5 text-[13px] text-red-600 dark:text-red-400" data-quick-error="description"></p>
    </div>

    <div class="grid grid-cols-2 gap-3">
        <div>
            <label for="quick-category" class="fv-label">Kategorie</label>
            <select id="quick-category" name="category_id" class="fv-input" data-quick-category>
                <option value="">Ohne Kategorie</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" data-type="{{ $category->type }}">{{ $category->icon }} {{ $category->display_name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="quick-date" class="fv-label">Datum</label>
            <input id="quick-date" name="transaction_date" type="date" required value="{{ now()->toDateString() }}" class="fv-input">
        </div>
    </div>

    @if ($accounts->count() > 1)
        <div>
            <label for="quick-account" class="fv-label">Konto</label>
            <select id="quick-account" name="account_id" class="fv-input">
                @foreach ($accounts as $account)
                    <option value="{{ $account->id }}" @selected($account->id === $defaultAccountId)>{{ $account->name }}</option>
                @endforeach
            </select>
        </div>
    @else
        <input type="hidden" name="account_id" value="{{ $defaultAccountId }}">
    @endif

    <p class="hidden text-sm text-red-600 dark:text-red-400" data-quick-error="general" role="alert"></p>

    <div class="flex items-center justify-between gap-3 pt-1">
        <a href="{{ route('transactions.create') }}" class="fv-link text-sm">Alle Felder</a>
        <button type="submit" class="fv-btn fv-btn-primary" data-quick-submit>Sichern</button>
    </div>
</form>
