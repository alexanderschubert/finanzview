{{--
    Gemeinsames Formular für "Neue Kreditkarte" und "Kreditkarte bearbeiten".

    Erwartet: $creditCard (neu oder bestehend), $providers, $accounts
--}}

@php
    $isEdit = $creditCard->exists;

    $value = fn (string $field, $default = null) => old($field, $creditCard->{$field} ?? $default);

    $color = $value('color');
    $hasOwnColor = is_string($color) && preg_match('/^#[0-9a-fA-F]{6}$/', $color) && strtolower($color) !== '#334155';
    $defaultColor = '#3f3f45';
@endphp

<form
    id="credit-card-form"
    method="POST"
    action="{{ $isEdit ? route('credit-cards.update', $creditCard) : route('credit-cards.store') }}"
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

    <div class="mx-auto max-w-sm" data-card-preview>
        <x-wallet-card
            :color="$hasOwnColor ? $color : null"
            :fallback-color="$defaultColor"
            :title="$value('name') ?: 'Neue Kreditkarte'"
            :subtitle="$value('issuer') ?: 'Kreditkarte'"
            amount-label="Aktueller Saldo"
            :amount="number_format((float) $value('current_balance', 0), 2, ',', '.') . ' €'"
            :number="'•••• ' . ($value('last_four') ?: '····')"
        />
    </div>


    {{-- KARTE --}}

    <div class="fv-card p-5 sm:p-6 space-y-4">

        <x-field label="Name" for="name" error="name">
            <input id="name" name="name" type="text" required maxlength="255" autofocus
                value="{{ $value('name') }}" placeholder="z. B. Reisekarte" class="fv-input" data-preview-title>
        </x-field>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <x-field label="Herausgeber" for="issuer" error="issuer">
                <input id="issuer" name="issuer" type="text" maxlength="255"
                    value="{{ $value('issuer') }}" placeholder="z. B. Barclays" class="fv-input" data-preview-subtitle>
            </x-field>

            <x-field label="Letzte vier Ziffern" for="last_four" error="last_four">
                <input id="last_four" name="last_four" type="text" inputmode="numeric" pattern="[0-9]{4}" maxlength="4" autocomplete="off"
                    value="{{ $value('last_four') }}" placeholder="1234" class="fv-input font-mono tracking-widest" data-preview-number>
            </x-field>
        </div>

        @if ($providers->isNotEmpty())
            <x-field label="Finanzanbieter" for="provider_id" error="provider_id">
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
            <x-field label="Aktueller Saldo" for="current_balance" error="current_balance" hint="Offener Betrag auf der Karte.">
                <input id="current_balance" name="current_balance" type="number" step="0.01" min="0" inputmode="decimal" required
                    value="{{ $value('current_balance', '0.00') }}" class="fv-input tabular-nums" data-preview-amount>
            </x-field>

            <x-field label="Kreditlimit" for="credit_limit" error="credit_limit">
                <input id="credit_limit" name="credit_limit" type="number" step="0.01" min="0" inputmode="decimal"
                    value="{{ $value('credit_limit') }}" placeholder="Optional" class="fv-input tabular-nums">
            </x-field>
        </div>

    </div>


    {{-- ABRECHNUNG --}}

    <div class="fv-card p-5 sm:p-6 space-y-4">

        <x-field label="Abbuchung vom Konto" for="account_id" error="account_id" hint="Von diesem Konto wird die monatliche Abrechnung bezahlt.">
            <select id="account_id" name="account_id" class="fv-input">
                <option value="">Kein Konto</option>
                @foreach ($accounts as $account)
                    <option value="{{ $account->id }}" @selected((string) $value('account_id') === (string) $account->id)>
                        {{ $account->name }}
                    </option>
                @endforeach
            </select>
        </x-field>

        <div class="grid grid-cols-2 gap-4">
            <x-field label="Abrechnungstag" for="billing_day" error="billing_day" hint="Tag im Monat, 1–31">
                <input id="billing_day" name="billing_day" type="number" min="1" max="31" inputmode="numeric"
                    value="{{ $value('billing_day') }}" class="fv-input tabular-nums">
            </x-field>

            <x-field label="Fällig am" for="payment_due_day" error="payment_due_day" hint="Tag im Monat, 1–31">
                <input id="payment_due_day" name="payment_due_day" type="number" min="1" max="31" inputmode="numeric"
                    value="{{ $value('payment_due_day') }}" class="fv-input tabular-nums">
            </x-field>
        </div>

    </div>


    {{-- DARSTELLUNG UND STATUS --}}

    <div class="fv-card p-5 sm:p-6 space-y-4">

        <div>
            <label for="color" class="fv-label">Kartenfarbe</label>
            <div class="flex items-center gap-2">
                <input id="color" type="color" value="{{ $hasOwnColor ? $color : $defaultColor }}"
                    class="h-[46px] w-14 cursor-pointer rounded-xl border border-slate-200 dark:border-slate-700 bg-transparent p-1" data-preview-color>
                <input type="hidden" name="color" value="{{ $hasOwnColor ? $color : '' }}" data-color-value>
                <button type="button" class="fv-link text-[13px] {{ $hasOwnColor ? '' : 'hidden' }}" data-color-reset>Standard</button>
            </div>
        </div>

        <label class="flex items-center justify-between gap-4 cursor-pointer border-t border-slate-100 dark:border-white/5 pt-4">
            <span>
                <span class="block font-medium text-slate-900 dark:text-white">Karte aktiv</span>
                <span class="block text-[13px] text-slate-500 dark:text-slate-400">Inaktive Karten stehen bei neuen Buchungen nicht zur Auswahl</span>
            </span>
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" class="sr-only peer" @checked((bool) $value('is_active', true))>
            <span aria-hidden="true" class="relative inline-flex h-[31px] w-[51px] shrink-0 rounded-full bg-slate-200 dark:bg-slate-700 transition peer-checked:bg-emerald-500 peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-500 after:absolute after:top-[2px] after:left-[2px] after:h-[27px] after:w-[27px] after:rounded-full after:bg-white after:shadow after:transition peer-checked:after:translate-x-[20px]"></span>
        </label>

    </div>


    {{-- AKTIONEN --}}

    <div class="flex flex-col-reverse sm:flex-row sm:items-center gap-3">
        @if ($isEdit)
            <button type="submit" form="delete-credit-card-form" class="fv-btn text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 sm:mr-auto">
                <x-icon name="trash" class="w-4 h-4" />
                Archivieren
            </button>
        @else
            <span class="hidden sm:block sm:mr-auto"></span>
        @endif

        <a href="{{ $isEdit ? route('credit-cards.show', $creditCard) : route('credit-cards.index') }}" class="fv-btn fv-btn-secondary">Abbrechen</a>

        <button type="submit" class="fv-btn fv-btn-primary">
            {{ $isEdit ? 'Änderungen speichern' : 'Karte anlegen' }}
        </button>
    </div>

</form>

@if ($isEdit)
    <form
        id="delete-credit-card-form"
        method="POST"
        action="{{ route('credit-cards.destroy', $creditCard) }}"
        class="hidden"
        onsubmit="return confirm('Kreditkarte „{{ addslashes($creditCard->name) }}“ archivieren? Bestehende Buchungen und Abrechnungen bleiben erhalten.');"
    >
        @csrf
        @method('DELETE')
    </form>
@endif


<script>
    // Live-Vorschau der Karte.
    (function () {
        const card = document.querySelector('[data-card-preview]').firstElementChild;
        const title = card.querySelector('p.font-semibold');
        const subtitle = title.nextElementSibling;
        const amount = card.querySelector('p.text-2xl');
        const number = card.querySelector('p.font-mono');

        const colorInput = document.querySelector('[data-preview-color]');
        const colorValue = document.querySelector('[data-color-value]');
        const colorReset = document.querySelector('[data-color-reset]');
        const defaultColor = @json($defaultColor);

        const format = value => (Number(value) || 0).toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €';

        function paint(hex) {
            const r = parseInt(hex.slice(1, 3), 16);
            const g = parseInt(hex.slice(3, 5), 16);
            const b = parseInt(hex.slice(5, 7), 16);
            const isLight = (0.2126 * r + 0.7152 * g + 0.0722 * b) / 255 > 0.62;

            card.style.background = `linear-gradient(135deg, ${hex}, color-mix(in srgb, ${hex} ${isLight ? '80%' : '55%'}, black))`;
            card.classList.toggle('text-white', !isLight);
            card.classList.toggle('text-slate-900', isLight);
        }

        document.querySelector('[data-preview-title]').addEventListener('input', e => title.textContent = e.target.value || 'Neue Kreditkarte');
        document.querySelector('[data-preview-subtitle]').addEventListener('input', e => subtitle.textContent = e.target.value || 'Kreditkarte');
        document.querySelector('[data-preview-number]').addEventListener('input', e => number.textContent = '•••• ' + (e.target.value || '····'));
        document.querySelector('[data-preview-amount]').addEventListener('input', e => amount.textContent = format(e.target.value));

        colorInput.addEventListener('input', () => {
            colorValue.value = colorInput.value;
            colorReset.classList.remove('hidden');
            paint(colorInput.value);
        });

        colorReset.addEventListener('click', () => {
            colorValue.value = '';
            colorInput.value = defaultColor;
            colorReset.classList.add('hidden');
            paint(defaultColor);
        });
    })();
</script>
