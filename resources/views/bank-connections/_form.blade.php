{{-- Erwartet: $connection --}}

@php
    $isEdit = $connection->exists;
    $value = fn (string $field) => old($field, $connection->{$field});
@endphp

@php $hasInstitutes = \App\Models\FintsInstitute::query()->exists(); @endphp

<form method="POST" action="{{ $isEdit ? route('bank-connections.update', $connection) : route('bank-connections.store') }}" class="space-y-5">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="fv-card p-5 sm:p-6 space-y-4">
        {{-- Bankensuche: füllt Bankleitzahl und FinTS-Adresse aus der importierten Liste. --}}
        <div data-institute-search data-url="{{ route('bank-connections.institutes.search') }}" @if (! $hasInstitutes) hidden @endif>
            <x-field label="Bank suchen" for="institute_q" hint="Name oder Bankleitzahl eingeben – Bankleitzahl und FinTS-Adresse werden automatisch ausgefüllt.">
                <div class="relative">
                    <x-icon name="search" class="w-[18px] h-[18px] absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
                    <input id="institute_q" type="search" autocomplete="off" placeholder="z. B. Berliner Sparkasse" class="fv-input pl-10" data-institute-input>
                    <ul hidden data-institute-results class="absolute z-20 mt-1 w-full max-h-72 overflow-auto rounded-2xl bg-white dark:bg-slate-900 shadow-lg ring-1 ring-black/5 dark:ring-white/10 divide-y divide-slate-100 dark:divide-white/5"></ul>
                </div>
            </x-field>
        </div>

        <x-field label="Bezeichnung" for="name" error="name">
            <input id="name" name="name" type="text" maxlength="100" required value="{{ $value('name') }}" class="fv-input" placeholder="z. B. Girokonto Sparkasse">
        </x-field>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-field label="Bankleitzahl" for="bank_code" error="bank_code">
                <input id="bank_code" name="bank_code" type="text" inputmode="numeric" maxlength="8" required value="{{ $value('bank_code') }}" class="fv-input tabular-nums" placeholder="10050000">
            </x-field>

            <x-field label="FinTS-Adresse (PIN/TAN)" for="url" error="url" class="sm:col-span-2">
                <input id="url" name="url" type="url" required value="{{ $value('url') }}" class="fv-input" placeholder="https://…/fints30">
            </x-field>
        </div>

        <p class="text-[13px] text-slate-500 dark:text-slate-400">
            @if ($hasInstitutes)
                Du kannst die Felder auch von Hand ändern.
            @else
                Die FinTS-Adresse deiner Bank steht in der Bankenliste der Deutschen Kreditwirtschaft. Importiere sie auf der Seite
                <a href="{{ route('bank-connections.index') }}" class="fv-link">Bankverbindungen</a> (Administrator), dann genügt künftig der Banknamen.
            @endif
        </p>

        <x-field label="Anmeldename (Legitimations-ID)" for="username" error="username" hint="Wie beim Online-Banking. Wird verschlüsselt gespeichert.">
            <input id="username" name="username" type="text" maxlength="100" required value="{{ $value('username') }}" class="fv-input" autocomplete="username">
        </x-field>

    </div>

    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
        <a href="{{ route('bank-connections.index') }}" class="fv-btn fv-btn-secondary">Abbrechen</a>
        <button type="submit" class="fv-btn fv-btn-primary">{{ $isEdit ? 'Speichern' : 'Weiter' }}</button>
    </div>
</form>

<script>
    // Bankensuche mit Vorschlägen (Name oder Bankleitzahl).
    (function () {
        const root = document.querySelector('[data-institute-search]');
        if (!root || root.hidden) return;

        const input = root.querySelector('[data-institute-input]');
        const list = root.querySelector('[data-institute-results]');
        const form = root.closest('form');
        let timer = null;
        let request = 0;

        const hide = () => { list.hidden = true; list.innerHTML = ''; };

        const choose = bank => {
            form.elements.bank_code.value = bank.bank_code;
            form.elements.url.value = bank.url;

            // Bezeichnung nur überschreiben, wenn noch nichts Eigenes drinsteht.
            const name = form.elements.name;
            if (!name.value || name.value === 'Girokonto') name.value = bank.name;

            input.value = bank.name + (bank.city ? ' (' + bank.city + ')' : '');
            hide();
            form.elements.username.focus();
        };

        const render = banks => {
            list.innerHTML = '';

            if (!banks.length) {
                const empty = document.createElement('li');
                empty.className = 'px-4 py-3 text-sm text-slate-500 dark:text-slate-400';
                empty.textContent = 'Keine Bank gefunden – Felder bitte von Hand ausfüllen.';
                list.append(empty);
            }

            banks.forEach(bank => {
                const item = document.createElement('li');
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'w-full text-left px-4 py-2.5 hover:bg-slate-50 dark:hover:bg-white/5';

                const title = document.createElement('span');
                title.className = 'block text-sm font-medium text-slate-900 dark:text-white';
                title.textContent = bank.name;

                const meta = document.createElement('span');
                meta.className = 'block text-xs text-slate-500 dark:text-slate-400';
                meta.textContent = 'BLZ ' + bank.bank_code + (bank.city ? ' · ' + bank.city : '');

                button.append(title, meta);
                button.addEventListener('click', () => choose(bank));
                item.append(button);
                list.append(item);
            });

            list.hidden = false;
        };

        input.addEventListener('input', () => {
            clearTimeout(timer);
            const term = input.value.trim();

            if (term.length < 2) return hide();

            timer = setTimeout(async () => {
                const current = ++request;

                try {
                    const response = await fetch(root.dataset.url + '?q=' + encodeURIComponent(term), {
                        headers: { Accept: 'application/json' }, credentials: 'same-origin',
                    });
                    if (current === request && response.ok) render(await response.json());
                } catch (e) {
                    hide();
                }
            }, 200);
        });

        document.addEventListener('click', event => { if (!root.contains(event.target)) hide(); });
        input.addEventListener('keydown', event => { if (event.key === 'Escape') hide(); });
    })();
</script>
