{{--
    Gemeinsames Formular für wiederkehrende Buchungen (anlegen und bearbeiten).

    Erwartet: $recurringTransaction (neu oder bestehend), $accounts, $categories
--}}

@php
    $item = $recurringTransaction;
    $isEdit = $item->exists;

    $value = fn (string $field, $default = null) => old($field, $item->{$field} ?? $default);

    $selectedType = $value('type', 'expense');
    $nextDate = old('next_date', $item->next_date?->format('Y-m-d') ?? now()->format('Y-m-d'));
    $endDate = old('end_date', $item->end_date?->format('Y-m-d'));

    $frequencies = [
        'weekly' => 'Wöchentlich',
        'monthly' => 'Monatlich',
        'quarterly' => 'Quartal',
        'yearly' => 'Jährlich',
    ];
@endphp

<form
    id="recurring-form"
    method="POST"
    action="{{ $isEdit ? route('recurring-transactions.update', $item) : route('recurring-transactions.store') }}"
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

        <div class="grid grid-cols-2 gap-1 rounded-xl bg-slate-100 dark:bg-white/5 p-1" role="radiogroup" aria-label="Art">
            @foreach (['expense' => 'Ausgabe', 'income' => 'Einnahme'] as $type => $label)
                <label class="cursor-pointer rounded-lg py-2 text-center text-sm font-medium text-slate-600 dark:text-slate-300 transition has-[:checked]:bg-white has-[:checked]:text-slate-900 has-[:checked]:shadow-sm dark:has-[:checked]:bg-slate-700 dark:has-[:checked]:text-white has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-emerald-500">
                    <input type="radio" name="type" value="{{ $type }}" class="sr-only" @checked($selectedType === $type)>
                    {{ $label }}
                </label>
            @endforeach
        </div>

        <label for="amount" class="sr-only">Betrag</label>
        <div class="mt-6 flex items-baseline justify-center gap-2">
            <input
                id="amount" name="amount" type="number" step="0.01" min="0.01" inputmode="decimal" required
                placeholder="0,00" value="{{ $value('amount') }}"
                class="w-full max-w-[16rem] bg-transparent border-0 p-0 text-center text-5xl font-semibold tracking-tight tabular-nums text-slate-900 dark:text-white placeholder:text-slate-300 dark:placeholder:text-slate-600 [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                style="background: transparent; box-shadow: none;"
            >
            <span class="text-3xl font-semibold text-slate-400">€</span>
        </div>
        @error('amount')
            <p class="mt-2 text-center text-[13px] text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror

        <div class="mt-6 grid grid-cols-4 gap-1 rounded-xl bg-slate-100 dark:bg-white/5 p-1" role="radiogroup" aria-label="Intervall">
            @foreach ($frequencies as $key => $label)
                <label class="cursor-pointer rounded-lg py-2 text-center text-[13px] sm:text-sm font-medium text-slate-600 dark:text-slate-300 transition has-[:checked]:bg-white has-[:checked]:text-slate-900 has-[:checked]:shadow-sm dark:has-[:checked]:bg-slate-700 dark:has-[:checked]:text-white has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-emerald-500">
                    <input type="radio" name="frequency" value="{{ $key }}" class="sr-only" @checked($value('frequency', 'monthly') === $key)>
                    {{ $label }}
                </label>
            @endforeach
        </div>
        @error('frequency')
            <p class="mt-2 text-[13px] text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror

    </div>


    {{-- BEZEICHNUNG, KONTO, KATEGORIE --}}

    <div class="fv-card p-5 sm:p-6 space-y-4">

        <x-field label="Bezeichnung" for="description" error="description">
            <input id="description" name="description" type="text" required maxlength="255"
                value="{{ $value('description') }}" placeholder="z. B. Miete, Netflix, Gehalt" class="fv-input">
        </x-field>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <x-field label="Konto" for="account_id" error="account_id">
                <select id="account_id" name="account_id" required class="fv-input">
                    <option value="">Konto auswählen</option>
                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}" @selected((string) $value('account_id') === (string) $account->id)>
                            {{ $account->name }}{{ $account->is_active ? '' : ' (inaktiv)' }}
                        </option>
                    @endforeach
                </select>
            </x-field>

            <x-field label="Kategorie" for="category_id" error="category_id">
                <select id="category_id" name="category_id" class="fv-input">
                    <option value="">Keine Kategorie</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" data-type="{{ $category->type }}" @selected((string) $value('category_id') === (string) $category->id)>
                            {{ $category->icon }} {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </x-field>
        </div>

    </div>


    {{-- TERMINE --}}

    <div class="fv-card p-5 sm:p-6 space-y-4">

        <div class="grid grid-cols-2 gap-4">
            <x-field label="Nächste Ausführung" for="next_date" error="next_date">
                <input id="next_date" name="next_date" type="date" required value="{{ $nextDate }}" class="fv-input">
            </x-field>

            <x-field label="Endet am" for="end_date" error="end_date">
                <input id="end_date" name="end_date" type="date" value="{{ $endDate }}" class="fv-input">
            </x-field>
        </div>

        <p class="-mt-1 text-[13px] text-slate-500 dark:text-slate-400">
            FinanzView bucht automatisch am Fälligkeitstag. Ohne Enddatum läuft die Buchung unbegrenzt.
        </p>

        <label class="flex items-center justify-between gap-4 cursor-pointer border-t border-slate-100 dark:border-white/5 pt-4">
            <span>
                <span class="block font-medium text-slate-900 dark:text-white">Aktiv</span>
                <span class="block text-[13px] text-slate-500 dark:text-slate-400">Pausierte Einträge werden nicht gebucht</span>
            </span>
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" class="sr-only peer" @checked((bool) $value('is_active', true))>
            <span aria-hidden="true" class="relative inline-flex h-[31px] w-[51px] shrink-0 rounded-full bg-slate-200 dark:bg-slate-700 transition peer-checked:bg-emerald-500 peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-500 after:absolute after:top-[2px] after:left-[2px] after:h-[27px] after:w-[27px] after:rounded-full after:bg-white after:shadow after:transition peer-checked:after:translate-x-[20px]"></span>
        </label>

    </div>


    {{-- AKTIONEN --}}

    <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-3">
        <a href="{{ $isEdit ? route('recurring-transactions.show', $item) : route('recurring-transactions.index') }}" class="fv-btn fv-btn-secondary">Abbrechen</a>

        <button type="submit" class="fv-btn fv-btn-primary">
            {{ $isEdit ? 'Änderungen speichern' : 'Anlegen' }}
        </button>
    </div>

</form>


<script>
    // Nur passende Kategorien anbieten.
    (function () {
        const form = document.getElementById('recurring-form');
        const categorySelect = document.getElementById('category_id');
        const options = Array.from(categorySelect.options).filter(option => option.dataset.type);

        function update() {
            const type = form.querySelector('input[name="type"]:checked')?.value || 'expense';

            options.forEach(option => {
                const matches = option.dataset.type === type || option.dataset.type === 'both';
                option.hidden = !matches;
                option.disabled = !matches;
            });

            if (categorySelect.selectedOptions[0]?.disabled) {
                categorySelect.value = '';
            }
        }

        form.querySelectorAll('input[name="type"]').forEach(input => input.addEventListener('change', update));
        update();
    })();
</script>
