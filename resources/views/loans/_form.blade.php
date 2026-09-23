{{--
    Gemeinsames Formular für "Neuer Kredit" und "Kredit bearbeiten".

    Erwartet: $loan (neu oder bestehend), $accounts, $providers
--}}

@php
    $isEdit = $loan->exists;

    $value = fn (string $field, $default = null) => old($field, $loan->{$field} ?? $default);

    $types = [
        'loan' => 'Ratenkredit',
        'installment' => 'Finanzierung',
        'paypal_installment' => 'PayPal Ratenzahlung',
        'other' => 'Sonstiges',
    ];

    $startDate = old('start_date', $loan->start_date?->format('Y-m-d') ?? now()->format('Y-m-d'));
    $endDate = old('end_date', $loan->end_date?->format('Y-m-d'));

    $color = $value('creditor_color');
    $hasColor = is_string($color) && preg_match('/^#[0-9a-fA-F]{6}$/', $color);
@endphp

<form
    id="loan-form"
    method="POST"
    action="{{ $isEdit ? route('loans.update', $loan) : route('loans.store') }}"
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


    {{-- KREDIT --}}

    <div class="fv-card p-5 sm:p-6 space-y-4">

        <div class="flex items-center gap-4">
            <span
                data-preview-tile
                class="w-14 h-14 rounded-2xl shrink-0 flex items-center justify-center text-2xl bg-slate-100 dark:bg-white/5"
                @if ($hasColor) style="background-color: {{ $color }}26" @endif
            >{{ $value('creditor_icon') ?: '🏦' }}</span>

            <div class="flex-1">
                <label for="name" class="sr-only">Name</label>
                <input id="name" name="name" type="text" required maxlength="255" autofocus
                    value="{{ $value('name') }}" placeholder="Name, z. B. Autokredit"
                    class="w-full bg-transparent border-0 p-0 text-xl font-semibold text-slate-900 dark:text-white placeholder:text-slate-300 dark:placeholder:text-slate-600"
                    style="background: transparent; box-shadow: none;">
                @error('name')
                    <p class="mt-1 text-[13px] text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <x-field label="Kreditgeber" for="creditor_name" error="creditor_name">
                <input id="creditor_name" name="creditor_name" type="text" maxlength="255"
                    value="{{ $value('creditor_name') }}" placeholder="z. B. Hausbank" class="fv-input">
            </x-field>

            <x-field label="Art" for="type" error="type">
                <select id="type" name="type" required class="fv-input">
                    @foreach ($types as $key => $label)
                        <option value="{{ $key }}" @selected($value('type', 'loan') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
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

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-field label="Kreditbetrag" for="principal_amount" error="principal_amount">
                <input id="principal_amount" name="principal_amount" type="number" step="0.01" min="0.01" inputmode="decimal" required
                    value="{{ $value('principal_amount') }}" placeholder="0,00" class="fv-input tabular-nums" data-calc>
            </x-field>

            <x-field label="Zinssatz (% p. a.)" for="interest_rate" error="interest_rate">
                <input id="interest_rate" name="interest_rate" type="number" step="0.001" min="0" inputmode="decimal"
                    value="{{ $value('interest_rate') }}" placeholder="0,00" class="fv-input tabular-nums" data-calc>
            </x-field>

            <x-field label="Monatsrate" for="installment_amount" error="installment_amount">
                <input id="installment_amount" name="installment_amount" type="number" step="0.01" min="0.01" inputmode="decimal" required
                    value="{{ $value('installment_amount') }}" placeholder="0,00" class="fv-input tabular-nums" data-calc>
            </x-field>
        </div>

        <div class="hidden" data-calc-box>
            <div class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 dark:bg-white/5 px-4 py-3 text-sm text-slate-600 dark:text-slate-300">
                <span data-calc-result aria-live="polite"></span>
                <button type="button" class="fv-link shrink-0 text-sm" data-calc-apply>Übernehmen</button>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <x-field label="Bereits getilgt" for="paid_amount" error="paid_amount" hint="Vor FinanzView gezahlt.">
                <input id="paid_amount" name="paid_amount" type="number" step="0.01" min="0" inputmode="decimal"
                    value="{{ $value('paid_amount', '0.00') }}" class="fv-input tabular-nums">
            </x-field>

            <x-field label="Bereits gezahlte Raten" for="paid_installments" error="paid_installments">
                <input id="paid_installments" name="paid_installments" type="number" step="1" min="0" inputmode="numeric"
                    value="{{ $value('paid_installments', 0) }}" class="fv-input tabular-nums">
            </x-field>
        </div>

    </div>


    {{-- LAUFZEIT --}}

    <div class="fv-card p-5 sm:p-6 space-y-4">

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-field label="Beginn" for="start_date" error="start_date">
                <input id="start_date" name="start_date" type="date" value="{{ $startDate }}" class="fv-input" data-calc>
            </x-field>

            <x-field label="Anzahl Raten" for="total_installments" error="total_installments" hint="Nötig für den Tilgungsplan.">
                <input id="total_installments" name="total_installments" type="number" step="1" min="1" inputmode="numeric"
                    value="{{ $value('total_installments') }}" placeholder="Optional" class="fv-input tabular-nums">
            </x-field>

            <x-field label="Ende" for="end_date" error="end_date">
                <input id="end_date" name="end_date" type="date" value="{{ $endDate }}" class="fv-input">
            </x-field>
        </div>

        <x-field label="Rate wird abgebucht von" for="account_id" error="account_id">
            <select id="account_id" name="account_id" class="fv-input">
                <option value="">Kein Konto</option>
                @foreach ($accounts as $account)
                    <option value="{{ $account->id }}" @selected((string) $value('account_id') === (string) $account->id)>
                        {{ $account->name }}
                    </option>
                @endforeach
            </select>
        </x-field>

    </div>


    {{-- DARSTELLUNG, NOTIZEN, STATUS --}}

    <div class="fv-card p-5 sm:p-6 space-y-4">

        <div class="grid grid-cols-[1fr_auto] gap-4 items-end">
            <x-field label="Symbol" for="creditor_icon" error="creditor_icon">
                <input id="creditor_icon" name="creditor_icon" type="text" maxlength="20"
                    value="{{ $value('creditor_icon') }}" placeholder="🏦" class="fv-input text-xl">
            </x-field>

            <x-field label="Farbe" for="creditor_color" error="creditor_color">
                <input id="creditor_color" type="color" value="{{ $hasColor ? $color : '#16a57a' }}"
                    class="h-[46px] w-14 cursor-pointer rounded-xl border border-slate-200 dark:border-slate-700 bg-transparent p-1" data-color-picker>
                <input type="hidden" name="creditor_color" value="{{ $hasColor ? $color : '' }}" data-color-value>
            </x-field>
        </div>

        <x-field label="Notizen" for="notes" error="notes">
            <textarea id="notes" name="notes" rows="3" placeholder="z. B. Vertragsnummer, Sondertilgungsrecht" class="fv-input">{{ $value('notes') }}</textarea>
        </x-field>

        <label class="flex items-center justify-between gap-4 cursor-pointer border-t border-slate-100 dark:border-white/5 pt-4">
            <span>
                <span class="block font-medium text-slate-900 dark:text-white">Kredit läuft noch</span>
                <span class="block text-[13px] text-slate-500 dark:text-slate-400">Abgeschlossene Kredite zählen nicht zu den monatlichen Raten</span>
            </span>
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" class="sr-only peer" @checked((bool) $value('is_active', true))>
            <span aria-hidden="true" class="relative inline-flex h-[31px] w-[51px] shrink-0 rounded-full bg-slate-200 dark:bg-slate-700 transition peer-checked:bg-emerald-500 peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-500 after:absolute after:top-[2px] after:left-[2px] after:h-[27px] after:w-[27px] after:rounded-full after:bg-white after:shadow after:transition peer-checked:after:translate-x-[20px]"></span>
        </label>

    </div>


    @if ($isEdit)
        <p class="px-1 text-[13px] text-slate-500 dark:text-slate-400">
            Änderst du Betrag, Zins, Rate, Anzahl der Raten oder Beginn, werden die offenen Raten neu berechnet. Bereits bezahlte Raten bleiben erhalten.
        </p>
    @endif


    {{-- AKTIONEN --}}

    <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-end gap-3">
        <a href="{{ $isEdit ? route('loans.show', $loan) : route('loans.index') }}" class="fv-btn fv-btn-secondary">Abbrechen</a>

        <button type="submit" class="fv-btn fv-btn-primary">
            {{ $isEdit ? 'Änderungen speichern' : 'Kredit anlegen' }}
        </button>
    </div>

</form>


<script>
    (function () {
        const form = document.getElementById('loan-form');
        const tile = form.querySelector('[data-preview-tile]');
        const iconInput = document.getElementById('creditor_icon');
        const colorPicker = form.querySelector('[data-color-picker]');
        const colorValue = form.querySelector('[data-color-value]');
        const box = form.querySelector('[data-calc-box]');
        const result = form.querySelector('[data-calc-result]');
        const applyButton = form.querySelector('[data-calc-apply]');
        const endDate = document.getElementById('end_date');
        let suggestion = null;

        const principal = document.getElementById('principal_amount');
        const rate = document.getElementById('interest_rate');
        const installment = document.getElementById('installment_amount');
        const start = document.getElementById('start_date');
        const totalInstallments = document.getElementById('total_installments');

        iconInput.addEventListener('input', () => tile.textContent = iconInput.value || '🏦');

        colorPicker.addEventListener('input', () => {
            colorValue.value = colorPicker.value;
            tile.style.backgroundColor = colorPicker.value + '26';
        });

        const money = value => value.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €';

        // Laufzeit nach der Annuitätenformel schätzen.
        function calculate() {
            const P = parseFloat(principal.value);
            const A = parseFloat(installment.value);
            const r = (parseFloat(rate.value) || 0) / 100 / 12;

            suggestion = null;
            applyButton.classList.add('hidden');

            if (!(P > 0) || !(A > 0)) {
                box.classList.add('hidden');
                return;
            }

            let months;

            if (r === 0) {
                months = Math.ceil(P / A);
            } else if (A <= P * r) {
                box.classList.remove('hidden');
                result.textContent = 'Die Rate deckt nicht einmal die Zinsen – der Kredit würde nie abbezahlt.';
                return;
            } else {
                months = Math.ceil(-Math.log(1 - (r * P) / A) / Math.log(1 + r));
            }

            const totalPaid = r === 0 ? P : months * A;
            let text = `≈ ${months} Raten`;

            let lastDate = null;

            if (start.value) {
                const end = new Date(start.value);
                end.setDate(1);
                end.setMonth(end.getMonth() + months - 1);
                lastDate = end;
                text += ` · letzte Rate ca. ${String(end.getMonth() + 1).padStart(2, '0')}/${end.getFullYear()}`;
            }

            if (r > 0) {
                text += ` · Zinsen gesamt ca. ${money(Math.max(0, totalPaid - P))}`;
            }

            result.textContent = text;
            box.classList.remove('hidden');

            totalInstallments.placeholder = `Vorschlag: ${months}`;

            suggestion = { months, lastDate };
            applyButton.classList.toggle('hidden', String(months) === totalInstallments.value);
        }

        // Vorschlag in "Anzahl Raten" und "Ende" übernehmen.
        applyButton.addEventListener('click', () => {
            if (!suggestion) return;

            totalInstallments.value = suggestion.months;

            if (suggestion.lastDate) {
                const end = new Date(suggestion.lastDate.getFullYear(), suggestion.lastDate.getMonth() + 1, 0);
                endDate.value = `${end.getFullYear()}-${String(end.getMonth() + 1).padStart(2, '0')}-${String(end.getDate()).padStart(2, '0')}`;
            }

            applyButton.classList.add('hidden');
        });

        totalInstallments.addEventListener('input', calculate);

        form.querySelectorAll('[data-calc]').forEach(input => input.addEventListener('input', calculate));
        calculate();
    })();
</script>
