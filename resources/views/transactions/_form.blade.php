{{--
    Gemeinsames Formular für "Neue Buchung" und "Buchung bearbeiten".

    Erwartet: $transaction (neu oder bestehend), $accounts, $categories, $creditCards
--}}

@php
    $isEdit = $transaction->exists;

    $value = fn (string $field, $default = null) => old($field, $transaction->{$field} ?? $default);

    $selectedType = $value('type', 'expense');

    $date = old('transaction_date', $transaction->transaction_date?->format('Y-m-d') ?? now()->format('Y-m-d'));

    $types = [
        'expense' => 'Ausgabe',
        'income' => 'Einnahme',
        'transfer' => 'Umbuchung',
    ];
@endphp

<form
    id="transaction-form"
    method="POST"
    action="{{ $isEdit ? route('transactions.update', $transaction) : route('transactions.store') }}"
    class="space-y-5"
>
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    @if ($errors->any())
        <div class="flex gap-3 rounded-2xl bg-red-50 dark:bg-red-500/10 p-4 text-sm text-red-700 dark:text-red-300" role="alert">
            <x-icon name="alert" class="w-5 h-5" />
            <p>Bitte prüfe die markierten Angaben.</p>
        </div>
    @endif


    {{-- ART UND BETRAG --}}

    <div class="fv-card p-5 sm:p-6">

        <div class="grid grid-cols-3 gap-1 rounded-xl bg-slate-100 dark:bg-white/5 p-1" role="radiogroup" aria-label="Art der Buchung">
            @foreach ($types as $type => $label)
                <label class="cursor-pointer rounded-lg py-2 text-center text-sm font-medium text-slate-600 dark:text-slate-300 transition has-[:checked]:bg-white has-[:checked]:text-slate-900 has-[:checked]:shadow-sm dark:has-[:checked]:bg-slate-700 dark:has-[:checked]:text-white has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-emerald-500">
                    <input type="radio" name="type" value="{{ $type }}" class="sr-only" @checked($selectedType === $type)>
                    {{ $label }}
                </label>
            @endforeach
        </div>

        <label for="amount" class="sr-only">Betrag</label>
        <div class="mt-6 flex items-baseline justify-center gap-2">
            <input
                id="amount"
                name="amount"
                type="number"
                step="0.01"
                min="0.01"
                inputmode="decimal"
                required
                autofocus
                placeholder="0,00"
                value="{{ $value('amount') }}"
                class="w-full max-w-[16rem] bg-transparent border-0 p-0 text-center text-5xl font-semibold tracking-tight tabular-nums text-slate-900 dark:text-white placeholder:text-slate-300 dark:placeholder:text-slate-600 focus:ring-0 focus:shadow-none [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                style="background: transparent; box-shadow: none;"
            >
            <span class="text-3xl font-semibold text-slate-400">€</span>
        </div>
        @error('amount')
            <p class="mt-2 text-center text-[13px] text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
        @error('type')
            <p class="mt-2 text-center text-[13px] text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror

    </div>


    {{-- BESCHREIBUNG --}}

    <div class="fv-card p-5 sm:p-6 space-y-4">

        <x-field label="Beschreibung" for="description" error="description">
            <input id="description" name="description" type="text" required maxlength="255"
                value="{{ $value('description') }}" placeholder="z. B. Wocheneinkauf" class="fv-input">
        </x-field>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <x-field label="Händler" for="merchant" error="merchant">
                <input id="merchant" name="merchant" type="text" maxlength="255"
                    value="{{ $value('merchant') }}" placeholder="z. B. REWE" class="fv-input">
            </x-field>

            <x-field label="Datum" for="transaction_date" error="transaction_date">
                <input id="transaction_date" name="transaction_date" type="date" required value="{{ $date }}" class="fv-input">
            </x-field>
        </div>

    </div>


    {{-- KONTEN UND KATEGORIE --}}

    <div class="fv-card p-5 sm:p-6 space-y-4">

        <x-field label="Konto" for="account_id" error="account_id" id="account-field">
            <select id="account_id" name="account_id" required class="fv-input">
                <option value="">Konto auswählen</option>
                @foreach ($accounts as $account)
                    <option value="{{ $account->id }}" @selected((string) $value('account_id') === (string) $account->id)>
                        {{ $account->name }}
                    </option>
                @endforeach
            </select>
        </x-field>

        <x-field label="Auf Konto" for="transfer_account_id" error="transfer_account_id" id="transfer-account-card" class="{{ $selectedType === 'transfer' ? '' : 'hidden' }}">
            <select id="transfer_account_id" name="transfer_account_id" class="fv-input">
                <option value="">Zielkonto auswählen</option>
                @foreach ($accounts as $account)
                    <option value="{{ $account->id }}" @selected((string) $value('transfer_account_id') === (string) $account->id)>
                        {{ $account->name }}
                    </option>
                @endforeach
            </select>
        </x-field>

        <x-field label="Kategorie" for="category_id" error="category_id" id="category-card" class="{{ $selectedType === 'transfer' ? 'hidden' : '' }}">
            <select id="category_id" name="category_id" class="fv-input">
                <option value="">Keine Kategorie</option>
                @foreach ($categories as $category)
                    <option
                        value="{{ $category->id }}"
                        data-type="{{ $category->type }}"
                        @selected((string) $value('category_id') === (string) $category->id)
                    >
                        {{ $category->icon }} {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </x-field>

        @if ($creditCards->isNotEmpty())
            <x-field
                label="Kreditkarte"
                for="credit_card_id"
                error="credit_card_id"
                hint="Optional – ordnet die Buchung einer Kreditkartenabrechnung zu."
                id="credit-card-card"
                class="{{ $selectedType === 'transfer' ? 'hidden' : '' }}"
            >
                <select id="credit_card_id" name="credit_card_id" class="fv-input">
                    <option value="">Keine Kreditkarte</option>
                    @foreach ($creditCards as $creditCard)
                        <option value="{{ $creditCard->id }}" @selected((string) $value('credit_card_id') === (string) $creditCard->id)>
                            {{ $creditCard->name }}@if ($creditCard->last_four) (•••• {{ $creditCard->last_four }})@endif
                            @unless ($creditCard->is_active) (inaktiv) @endunless
                        </option>
                    @endforeach
                </select>
            </x-field>
        @endif

    </div>


    {{-- WEITERES --}}

    <div class="fv-card p-5 sm:p-6 space-y-4">

        <label class="flex items-center justify-between gap-4 cursor-pointer">
            <span>
                <span class="block font-medium text-slate-900 dark:text-white">Ausstehend</span>
                <span class="block text-[13px] text-slate-500 dark:text-slate-400">Noch nicht vom Konto abgebucht</span>
            </span>

            <input type="hidden" name="is_pending" value="0">
            <input type="checkbox" name="is_pending" value="1" class="sr-only peer" @checked((bool) $value('is_pending'))>
            <span aria-hidden="true" class="relative inline-flex h-[31px] w-[51px] shrink-0 rounded-full bg-slate-200 dark:bg-slate-700 transition peer-checked:bg-emerald-500 peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-500 after:absolute after:top-[2px] after:left-[2px] after:h-[27px] after:w-[27px] after:rounded-full after:bg-white after:shadow after:transition peer-checked:after:translate-x-[20px]"></span>
        </label>

        <x-field label="Notizen" for="notes" error="notes">
            <textarea id="notes" name="notes" rows="3" placeholder="Optional" class="fv-input">{{ $value('notes') }}</textarea>
        </x-field>

    </div>


    {{-- AKTIONEN --}}

    <div class="flex flex-col-reverse sm:flex-row sm:items-center gap-3">
        @if ($isEdit)
            <button type="submit" form="delete-transaction-form" class="fv-btn text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 sm:mr-auto">
                <x-icon name="trash" class="w-4 h-4" />
                Löschen
            </button>
        @else
            <span class="hidden sm:block sm:mr-auto"></span>
        @endif

        <a href="{{ route('transactions.index') }}" class="fv-btn fv-btn-secondary">Abbrechen</a>

        <button type="submit" class="fv-btn fv-btn-primary">
            {{ $isEdit ? 'Änderungen speichern' : 'Buchung speichern' }}
        </button>
    </div>

</form>

@if ($isEdit)
    <form
        id="delete-transaction-form"
        method="POST"
        action="{{ route('transactions.destroy', $transaction) }}"
        class="hidden"
        onsubmit="return confirm('Möchtest du diese Buchung wirklich löschen?');"
    >
        @csrf
        @method('DELETE')
    </form>
@endif


<script>
    // Felder je nach Art der Buchung ein-/ausblenden und nur passende
    // Kategorien anbieten (Ausgabe/Einnahme/beides).
    (function () {
        const form = document.getElementById('transaction-form');
        const categorySelect = document.getElementById('category_id');
        const transferCard = document.getElementById('transfer-account-card');
        const transferSelect = document.getElementById('transfer_account_id');
        const categoryCard = document.getElementById('category-card');
        const creditCardCard = document.getElementById('credit-card-card');
        const creditCardSelect = document.getElementById('credit_card_id');

        const categoryOptions = Array.from(categorySelect.options).filter(option => option.dataset.type);

        function selectedType() {
            const checked = form.querySelector('input[name="type"]:checked');
            return checked ? checked.value : 'expense';
        }

        function update() {
            const type = selectedType();
            const isTransfer = type === 'transfer';

            transferCard.classList.toggle('hidden', !isTransfer);
            transferSelect.required = isTransfer;
            if (!isTransfer) transferSelect.value = '';

            categoryCard.classList.toggle('hidden', isTransfer);

            if (creditCardCard) {
                creditCardCard.classList.toggle('hidden', isTransfer);
                if (isTransfer) creditCardSelect.value = '';
            }

            categoryOptions.forEach(option => {
                const matches = option.dataset.type === type || option.dataset.type === 'both';
                option.hidden = !matches;
                option.disabled = !matches;
            });

            const selected = categorySelect.selectedOptions[0];
            if (isTransfer || (selected && selected.disabled)) {
                categorySelect.value = '';
            }
        }

        form.querySelectorAll('input[name="type"]').forEach(input => input.addEventListener('change', update));
        update();
    })();
</script>
