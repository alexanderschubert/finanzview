{{--
    Gemeinsames Formular für "Neues Konto" und "Konto bearbeiten".

    Erwartet: $account (neu oder bestehend), $providers
--}}

@php
    $isEdit = $account->exists;

    $value = fn (string $field, $default = null) => old($field, $account->{$field} ?? $default);

    $typeLabels = [
        'checking' => 'Girokonto',
        'savings' => 'Sparkonto',
        'credit_card' => 'Kreditkarte',
        'paypal' => 'PayPal',
        'cash' => 'Bargeld',
        'investment' => 'Depot / Investment',
        'loan' => 'Kredit',
        'other' => 'Sonstiges',
    ];

    // Gleiche Farben wie in der Kontenübersicht.
    $typeColors = [
        'checking' => '#0b7155',
        'savings' => '#1f5fa8',
        'credit_card' => '#3f3f45',
        'paypal' => '#123a86',
        'cash' => '#8a6516',
        'investment' => '#5b3fa8',
        'loan' => '#9f2d2d',
        'other' => '#3f3f45',
    ];

    $type = $value('type', 'checking');
    $color = $value('color');
    $hasOwnColor = is_string($color) && preg_match('/^#[0-9a-fA-F]{6}$/', $color) && strtolower($color) !== '#f1f5f9';

    $currencies = ['EUR' => 'Euro (€)', 'USD' => 'US-Dollar ($)', 'CHF' => 'Schweizer Franken (CHF)', 'GBP' => 'Britisches Pfund (£)'];

    $previewAmount = $isEdit
        ? number_format($account->current_balance, 2, ',', '.') . ' €'
        : number_format((float) $value('opening_balance', 0), 2, ',', '.') . ' €';
@endphp

<form
    id="account-form"
    method="POST"
    action="{{ $isEdit ? route('accounts.update', $account) : route('accounts.store') }}"
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


    {{-- LIVE-VORSCHAU --}}

    <div class="mx-auto max-w-sm" data-account-preview data-type-colors='@json($typeColors)'>
        <x-wallet-card
            :color="$color"
            :fallback-color="$typeColors[$type] ?? '#3f3f45'"
            :title="$value('name') ?: 'Neues Konto'"
            :subtitle="$value('institution') ?: ($typeLabels[$type] ?? '')"
            :amount-label="$isEdit ? 'Aktueller Kontostand' : 'Startsaldo'"
            :amount="$previewAmount"
            :number="$value('iban') ? '•••• ' . substr(preg_replace('/\s+/', '', $value('iban')), -4) : ''"
        />
    </div>


    {{-- GRUNDDATEN --}}

    <div class="fv-card p-5 sm:p-6 space-y-4">

        <x-field label="Name" for="name" error="name">
            <input id="name" name="name" type="text" required maxlength="255" autofocus
                value="{{ $value('name') }}" placeholder="z. B. Girokonto" class="fv-input" data-preview-title>
        </x-field>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <x-field label="Kontotyp" for="type" error="type">
                <select id="type" name="type" required class="fv-input" data-preview-type>
                    @foreach ($typeLabels as $key => $label)
                        <option value="{{ $key }}" @selected($type === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </x-field>

            <x-field label="Bank oder Institut" for="institution" error="institution">
                <input id="institution" name="institution" type="text" maxlength="255"
                    value="{{ $value('institution') }}" placeholder="z. B. Sparkasse" class="fv-input" data-preview-subtitle>
            </x-field>
        </div>

        @if ($providers->isNotEmpty())
            <x-field label="Finanzanbieter" for="provider_id" error="provider_id" hint="Optional – zeigt Logo und Farbe des Anbieters.">
                <select id="provider_id" name="provider_id" class="fv-input">
                    <option value="">Kein Anbieter</option>
                    @foreach ($providers as $provider)
                        <option value="{{ $provider->id }}" @selected((string) $value('provider_id') === (string) $provider->id)>
                            {{ $provider->emoji }} {{ $provider->name }}
                        </option>
                    @endforeach
                </select>
            </x-field>
        @endif

    </div>


    {{-- BETRÄGE --}}

    <div class="fv-card p-5 sm:p-6 space-y-4">

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <x-field
                label="Startsaldo"
                for="opening_balance"
                error="opening_balance"
                :hint="$isEdit ? 'Kontostand vor der ersten Buchung in FinanzView.' : 'Aktueller Kontostand beim Anlegen.'"
            >
                <input id="opening_balance" name="opening_balance" type="number" step="0.01" inputmode="decimal" required
                    value="{{ $value('opening_balance', '0.00') }}" class="fv-input tabular-nums">
            </x-field>

            <x-field label="Währung" for="currency" error="currency">
                <select id="currency" name="currency" required class="fv-input">
                    @foreach ($currencies as $code => $label)
                        <option value="{{ $code }}" @selected($value('currency', 'EUR') === $code)>{{ $label }}</option>
                    @endforeach
                </select>
            </x-field>
        </div>

        <x-field label="Kreditrahmen / Dispo" for="credit_limit" error="credit_limit" hint="Optional.">
            <input id="credit_limit" name="credit_limit" type="number" step="0.01" min="0" inputmode="decimal"
                value="{{ $value('credit_limit') }}" class="fv-input tabular-nums">
        </x-field>

    </div>


    {{-- KONTODATEN --}}

    <div class="fv-card p-5 sm:p-6 space-y-4">

        <x-field label="IBAN" for="iban" error="iban">
            <input id="iban" name="iban" type="text" maxlength="255" autocomplete="off"
                value="{{ $value('iban') }}" placeholder="DE00 0000 0000 0000 0000 00" class="fv-input font-mono" data-preview-iban>
        </x-field>

        <x-field label="Kontonummer" for="account_number" error="account_number" hint="Nur nötig, wenn es keine IBAN gibt.">
            <input id="account_number" name="account_number" type="text" maxlength="255" autocomplete="off"
                value="{{ $value('account_number') }}" class="fv-input font-mono">
        </x-field>

    </div>


    {{-- DARSTELLUNG UND OPTIONEN --}}

    <div class="fv-card p-5 sm:p-6 space-y-5">

        <div class="grid grid-cols-[1fr_auto] gap-4 items-end">
            <x-field label="Symbol" for="icon" error="icon" hint="Ein Emoji, z. B. 🏦 💶 🐷">
                <input id="icon" name="icon" type="text" maxlength="20" value="{{ $value('icon') }}" placeholder="🏦" class="fv-input">
            </x-field>

            <div>
                <label for="color" class="fv-label">Kartenfarbe</label>
                <div class="flex items-center gap-2">
                    <input id="color" type="color" value="{{ $hasOwnColor ? $color : ($typeColors[$type] ?? '#3f3f45') }}"
                        class="h-[46px] w-14 cursor-pointer rounded-xl border border-slate-200 dark:border-slate-700 bg-transparent p-1" data-preview-color>
                    <input type="hidden" name="color" value="{{ $hasOwnColor ? $color : '' }}" data-color-value>
                    <button type="button" class="fv-link text-[13px] {{ $hasOwnColor ? '' : 'hidden' }}" data-color-reset>Automatisch</button>
                </div>
            </div>
        </div>

        <div class="divide-y divide-slate-100 dark:divide-white/5 -my-1">
            <label class="flex items-center justify-between gap-4 py-3 cursor-pointer">
                <span>
                    <span class="block font-medium text-slate-900 dark:text-white">Im Gesamtvermögen berücksichtigen</span>
                    <span class="block text-[13px] text-slate-500 dark:text-slate-400">Zählt auf dem Dashboard zum Vermögen</span>
                </span>
                <input type="hidden" name="include_in_total" value="0">
                <input type="checkbox" name="include_in_total" value="1" class="sr-only peer" @checked((bool) $value('include_in_total', true))>
                <span aria-hidden="true" class="relative inline-flex h-[31px] w-[51px] shrink-0 rounded-full bg-slate-200 dark:bg-slate-700 transition peer-checked:bg-emerald-500 peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-500 after:absolute after:top-[2px] after:left-[2px] after:h-[27px] after:w-[27px] after:rounded-full after:bg-white after:shadow after:transition peer-checked:after:translate-x-[20px]"></span>
            </label>

            @if ($isEdit)
                <label class="flex items-center justify-between gap-4 py-3 cursor-pointer">
                    <span>
                        <span class="block font-medium text-slate-900 dark:text-white">Konto aktiv</span>
                        <span class="block text-[13px] text-slate-500 dark:text-slate-400">Inaktive Konten erscheinen nicht mehr bei neuen Buchungen</span>
                    </span>
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" class="sr-only peer" @checked((bool) $value('is_active', true))>
                    <span aria-hidden="true" class="relative inline-flex h-[31px] w-[51px] shrink-0 rounded-full bg-slate-200 dark:bg-slate-700 transition peer-checked:bg-emerald-500 peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-500 after:absolute after:top-[2px] after:left-[2px] after:h-[27px] after:w-[27px] after:rounded-full after:bg-white after:shadow after:transition peer-checked:after:translate-x-[20px]"></span>
                </label>
            @endif
        </div>

        <x-field label="Notizen" for="notes" error="notes">
            <textarea id="notes" name="notes" rows="3" placeholder="Optional" class="fv-input">{{ $value('notes') }}</textarea>
        </x-field>

    </div>


    {{-- AKTIONEN --}}

    <div class="flex flex-col-reverse sm:flex-row sm:items-center gap-3">
        @if ($isEdit)
            <button type="submit" form="delete-account-form" class="fv-btn text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 sm:mr-auto">
                <x-icon name="trash" class="w-4 h-4" />
                Konto löschen
            </button>
        @else
            <span class="hidden sm:block sm:mr-auto"></span>
        @endif

        <a href="{{ route('accounts.index') }}" class="fv-btn fv-btn-secondary">Abbrechen</a>

        <button type="submit" class="fv-btn fv-btn-primary">
            {{ $isEdit ? 'Änderungen speichern' : 'Konto anlegen' }}
        </button>
    </div>

</form>

@if ($isEdit)
    <form
        id="delete-account-form"
        method="POST"
        action="{{ route('accounts.destroy', $account) }}"
        class="hidden"
        onsubmit="return confirm('Möchtest du das Konto „{{ addslashes($account->name) }}“ wirklich löschen?');"
    >
        @csrf
        @method('DELETE')
    </form>
@endif


<script>
    // Live-Vorschau der Wallet-Karte.
    (function () {
        const preview = document.querySelector('[data-account-preview]');
        const card = preview.firstElementChild;
        const typeColors = JSON.parse(preview.dataset.typeColors);
        const typeLabels = @json($typeLabels);

        const title = card.querySelector('p.font-semibold');
        const subtitle = title.nextElementSibling;
        const numberLine = card.querySelector('p.font-mono');

        const nameInput = document.querySelector('[data-preview-title]');
        const institutionInput = document.querySelector('[data-preview-subtitle]');
        const typeSelect = document.querySelector('[data-preview-type]');
        const ibanInput = document.querySelector('[data-preview-iban]');
        const colorInput = document.querySelector('[data-preview-color]');
        const colorValue = document.querySelector('[data-color-value]');
        const colorReset = document.querySelector('[data-color-reset]');

        function paint(hex) {
            const r = parseInt(hex.slice(1, 3), 16);
            const g = parseInt(hex.slice(3, 5), 16);
            const b = parseInt(hex.slice(5, 7), 16);
            const isLight = (0.2126 * r + 0.7152 * g + 0.0722 * b) / 255 > 0.62;

            card.style.background = `linear-gradient(135deg, ${hex}, color-mix(in srgb, ${hex} ${isLight ? '80%' : '55%'}, black))`;
            card.classList.toggle('text-white', !isLight);
            card.classList.toggle('text-slate-900', isLight);
        }

        function currentColor() {
            return colorValue.value || typeColors[typeSelect.value] || '#3f3f45';
        }

        nameInput.addEventListener('input', () => title.textContent = nameInput.value || 'Neues Konto');

        institutionInput.addEventListener('input', () => {
            if (subtitle) subtitle.textContent = institutionInput.value || typeLabels[typeSelect.value] || '';
        });

        typeSelect.addEventListener('change', () => {
            if (subtitle && !institutionInput.value) subtitle.textContent = typeLabels[typeSelect.value] || '';
            if (!colorValue.value) colorInput.value = typeColors[typeSelect.value] || '#3f3f45';
            paint(currentColor());
        });

        ibanInput.addEventListener('input', () => {
            const iban = ibanInput.value.replace(/\s+/g, '');
            if (numberLine) numberLine.textContent = iban ? '•••• ' + iban.slice(-4) : '';
        });

        colorInput.addEventListener('input', () => {
            colorValue.value = colorInput.value;
            colorReset.classList.remove('hidden');
            paint(colorInput.value);
        });

        colorReset.addEventListener('click', () => {
            colorValue.value = '';
            colorInput.value = typeColors[typeSelect.value] || '#3f3f45';
            colorReset.classList.add('hidden');
            paint(currentColor());
        });
    })();
</script>
