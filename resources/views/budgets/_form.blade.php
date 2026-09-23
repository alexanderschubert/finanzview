{{--
    Gemeinsames Formular für "Neues Budget" und "Budget bearbeiten".

    Erwartet: $budget (neu oder bestehend), $categories
--}}

@php
    $isEdit = $budget->exists;

    $value = fn (string $field, $default = null) => old($field, $budget->{$field} ?? $default);

    $period = $value('period', 'monthly');

    $startDate = old('start_date', $budget->start_date?->format('Y-m-d') ?? now()->startOfMonth()->format('Y-m-d'));
    $endDate = old('end_date', $budget->end_date?->format('Y-m-d'));

    $selectedCategories = collect(old('category_ids', $isEdit ? $budget->categories->pluck('id')->all() : []))
        ->map(fn ($id) => (int) $id)
        ->all();

    $periods = [
        'monthly' => 'Monatlich',
        'yearly' => 'Jährlich',
        'custom' => 'Zeitraum',
    ];

    // Budgets begrenzen Ausgaben – Einnahmenkategorien ans Ende.
    $sortedCategories = $categories->sortBy(fn ($category) => [$category->type === 'income' ? 1 : 0, $category->name]);

    $emojiSuggestions = ['🎯', '🛒', '🍽️', '☕', '⛽', '🚗', '🏠', '💡', '🎬', '🎮', '🛍️', '👕', '✈️', '🎁', '🚬', '🐶', '💊', '📚'];
    $colorSuggestions = ['#16a57a', '#1f5fa8', '#5b3fa8', '#c2410c', '#b91c1c', '#be185d', '#0e7490', '#8a6516', '#3f3f45'];

    $color = $value('color');
    $hasColor = is_string($color) && preg_match('/^#[0-9a-fA-F]{6}$/', $color) && ! in_array(strtolower($color), ['#f1f5f9', '#ecfdf5'], true);
@endphp

<form
    id="budget-form"
    method="POST"
    action="{{ $isEdit ? route('budgets.update', $budget) : route('budgets.store') }}"
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


    {{-- NAME UND BETRAG --}}

    <div class="fv-card p-5 sm:p-6">

        <div class="flex items-center gap-4">
            <span
                data-preview-tile
                class="w-14 h-14 rounded-2xl shrink-0 flex items-center justify-center text-2xl bg-slate-100 dark:bg-white/5"
                @if ($hasColor) style="background-color: {{ $color }}26" @endif
            >{{ $value('icon') ?: '🎯' }}</span>

            <div class="flex-1">
                <label for="name" class="sr-only">Name</label>
                <input id="name" name="name" type="text" required maxlength="255" autofocus
                    value="{{ $value('name') }}" placeholder="Name, z. B. Lebensmittel"
                    class="w-full bg-transparent border-0 p-0 text-xl font-semibold text-slate-900 dark:text-white placeholder:text-slate-300 dark:placeholder:text-slate-600"
                    style="background: transparent; box-shadow: none;">
                @error('name')
                    <p class="mt-1 text-[13px] text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mt-6 grid grid-cols-3 gap-1 rounded-xl bg-slate-100 dark:bg-white/5 p-1" role="radiogroup" aria-label="Zeitraum">
            @foreach ($periods as $key => $label)
                <label class="cursor-pointer rounded-lg py-2 text-center text-sm font-medium text-slate-600 dark:text-slate-300 transition has-[:checked]:bg-white has-[:checked]:text-slate-900 has-[:checked]:shadow-sm dark:has-[:checked]:bg-slate-700 dark:has-[:checked]:text-white has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-emerald-500">
                    <input type="radio" name="period" value="{{ $key }}" class="sr-only" @checked($period === $key)>
                    {{ $label }}
                </label>
            @endforeach
        </div>

        <div class="mt-5">
            <label for="amount" class="fv-label" data-amount-label>
                {{ ['monthly' => 'Betrag pro Monat', 'yearly' => 'Betrag pro Jahr', 'custom' => 'Betrag für den Zeitraum'][$period] ?? 'Betrag' }}
            </label>
            <div class="relative">
                <input id="amount" name="amount" type="number" step="0.01" min="0" inputmode="decimal" required
                    value="{{ $value('amount') }}" placeholder="0,00" class="fv-input pr-10 text-lg font-semibold tabular-nums">
                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400">€</span>
            </div>
            @error('amount')
                <p class="mt-1.5 text-[13px] text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div class="mt-4 grid grid-cols-2 gap-4">
            <x-field label="Ab" for="start_date" error="start_date">
                <input id="start_date" name="start_date" type="date" required value="{{ $startDate }}" class="fv-input">
            </x-field>

            <x-field label="Bis" for="end_date" error="end_date">
                <input id="end_date" name="end_date" type="date" value="{{ $endDate }}" class="fv-input" @required($period === 'custom')>
            </x-field>
        </div>

        <p class="mt-2 text-[13px] text-slate-500 dark:text-slate-400" data-end-hint>
            @if ($period === 'custom')
                Für einen festen Zeitraum ist ein Enddatum nötig.
            @else
                Leer lassen, damit das Budget jeden {{ $period === 'yearly' ? 'Jahr' : 'Monat' }} weiterläuft.
            @endif
        </p>

    </div>


    {{-- KATEGORIEN --}}

    <div class="fv-card p-5 sm:p-6">

        <p class="fv-label">Kategorien</p>
        <p class="-mt-1 mb-3 text-[13px] text-slate-500 dark:text-slate-400">
            Buchungen dieser Kategorien zählen zum Budget.
        </p>

        @if ($categories->isEmpty())
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Noch keine Kategorien vorhanden.
                <a href="{{ route('categories.create') }}" class="fv-link">Kategorie anlegen</a>
            </p>
        @else
            <div class="flex flex-wrap gap-2">
                @foreach ($sortedCategories as $category)
                    <label class="cursor-pointer select-none rounded-full border border-slate-200 dark:border-white/10 px-3 py-1.5 text-sm text-slate-700 dark:text-slate-200 transition hover:bg-slate-50 dark:hover:bg-white/5 has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50 has-[:checked]:text-emerald-800 dark:has-[:checked]:bg-emerald-500/15 dark:has-[:checked]:text-emerald-200 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-emerald-500 {{ $category->is_active ? '' : 'opacity-60' }}">
                        <input type="checkbox" name="category_ids[]" value="{{ $category->id }}" class="sr-only" @checked(in_array($category->id, $selectedCategories, true))>
                        {{ $category->icon }} {{ $category->name }}
                    </label>
                @endforeach
            </div>
        @endif

        @error('category_ids')
            <p class="mt-2 text-[13px] text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror

    </div>


    {{-- DARSTELLUNG UND STATUS --}}

    <div class="fv-card p-5 sm:p-6 space-y-5">

        <x-field label="Symbol" for="icon" error="icon">
            <input id="icon" name="icon" type="text" maxlength="20" value="{{ $value('icon') }}" placeholder="🎯" class="fv-input text-xl">
        </x-field>

        <div class="grid grid-cols-9 gap-1.5 -mt-2" aria-label="Emoji-Vorschläge">
            @foreach ($emojiSuggestions as $emoji)
                <button type="button" data-emoji="{{ $emoji }}" class="aspect-square rounded-lg text-xl hover:bg-slate-100 dark:hover:bg-white/10 transition">{{ $emoji }}</button>
            @endforeach
        </div>

        <div>
            <span class="fv-label">Farbe</span>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" data-color="" class="w-8 h-8 rounded-full border-2 border-dashed border-slate-300 dark:border-slate-600 text-slate-400 flex items-center justify-center" aria-label="Keine Farbe" title="Keine Farbe">
                    <x-icon name="x" class="w-4 h-4" />
                </button>

                @foreach ($colorSuggestions as $suggestion)
                    <button type="button" data-color="{{ $suggestion }}"
                        class="w-8 h-8 rounded-full ring-offset-2 ring-offset-white dark:ring-offset-slate-900 transition {{ $hasColor && strtolower($color) === $suggestion ? 'ring-2 ring-slate-900 dark:ring-white' : '' }}"
                        style="background-color: {{ $suggestion }}" aria-label="Farbe {{ $suggestion }}"></button>
                @endforeach

                <label class="relative w-8 h-8 rounded-full overflow-hidden cursor-pointer bg-[conic-gradient(red,yellow,lime,cyan,blue,magenta,red)]" title="Eigene Farbe">
                    <input type="color" value="{{ $hasColor ? $color : '#16a57a' }}" class="absolute inset-0 opacity-0 cursor-pointer" data-color-picker>
                    <span class="sr-only">Eigene Farbe</span>
                </label>
            </div>
            <input type="hidden" name="color" value="{{ $hasColor ? $color : '' }}" data-color-value>
        </div>

        <label class="flex items-center justify-between gap-4 cursor-pointer border-t border-slate-100 dark:border-white/5 pt-4">
            <span>
                <span class="block font-medium text-slate-900 dark:text-white">Budget aktiv</span>
                <span class="block text-[13px] text-slate-500 dark:text-slate-400">Pausierte Budgets erscheinen nicht auf dem Dashboard</span>
            </span>
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" class="sr-only peer" @checked((bool) $value('is_active', true))>
            <span aria-hidden="true" class="relative inline-flex h-[31px] w-[51px] shrink-0 rounded-full bg-slate-200 dark:bg-slate-700 transition peer-checked:bg-emerald-500 peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-500 after:absolute after:top-[2px] after:left-[2px] after:h-[27px] after:w-[27px] after:rounded-full after:bg-white after:shadow after:transition peer-checked:after:translate-x-[20px]"></span>
        </label>

    </div>


    {{-- AKTIONEN --}}

    <div class="flex flex-col-reverse sm:flex-row sm:items-center gap-3">
        @if ($isEdit)
            <button type="submit" form="delete-budget-form" class="fv-btn text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 sm:mr-auto">
                <x-icon name="trash" class="w-4 h-4" />
                Löschen
            </button>
        @else
            <span class="hidden sm:block sm:mr-auto"></span>
        @endif

        <a href="{{ route('budgets.index') }}" class="fv-btn fv-btn-secondary">Abbrechen</a>

        <button type="submit" class="fv-btn fv-btn-primary">
            {{ $isEdit ? 'Änderungen speichern' : 'Budget anlegen' }}
        </button>
    </div>

</form>

@if ($isEdit)
    <form
        id="delete-budget-form"
        method="POST"
        action="{{ route('budgets.destroy', $budget) }}"
        class="hidden"
        onsubmit="return confirm('Möchtest du das Budget „{{ addslashes($budget->name) }}“ wirklich löschen?');"
    >
        @csrf
        @method('DELETE')
    </form>
@endif


<script>
    (function () {
        const form = document.getElementById('budget-form');
        const tile = form.querySelector('[data-preview-tile]');
        const iconInput = document.getElementById('icon');
        const endDate = document.getElementById('end_date');
        const endHint = form.querySelector('[data-end-hint]');
        const amountLabel = form.querySelector('[data-amount-label]');
        const colorValue = form.querySelector('[data-color-value]');
        const colorPicker = form.querySelector('[data-color-picker]');
        const swatches = form.querySelectorAll('[data-color]');

        const amountLabels = { monthly: 'Betrag pro Monat', yearly: 'Betrag pro Jahr', custom: 'Betrag für den Zeitraum' };

        // Zeitraum: Enddatum nur bei festem Zeitraum Pflicht.
        form.querySelectorAll('input[name="period"]').forEach(input => {
            input.addEventListener('change', () => {
                const period = input.value;
                endDate.required = period === 'custom';
                amountLabel.textContent = amountLabels[period];
                endHint.textContent = period === 'custom'
                    ? 'Für einen festen Zeitraum ist ein Enddatum nötig.'
                    : 'Leer lassen, damit das Budget jeden ' + (period === 'yearly' ? 'Jahr' : 'Monat') + ' weiterläuft.';
            });
        });

        iconInput.addEventListener('input', () => tile.textContent = iconInput.value || '🎯');

        form.querySelectorAll('[data-emoji]').forEach(button => {
            button.addEventListener('click', () => {
                iconInput.value = button.dataset.emoji;
                tile.textContent = button.dataset.emoji;
            });
        });

        function setColor(hex) {
            colorValue.value = hex;
            tile.style.backgroundColor = hex ? hex + '26' : '';

            swatches.forEach(swatch => {
                const active = swatch.dataset.color && swatch.dataset.color === hex;
                swatch.classList.toggle('ring-2', active);
                swatch.classList.toggle('ring-slate-900', active);
                swatch.classList.toggle('dark:ring-white', active);
            });
        }

        swatches.forEach(swatch => swatch.addEventListener('click', () => setColor(swatch.dataset.color)));
        colorPicker.addEventListener('input', () => setColor(colorPicker.value));
    })();
</script>
