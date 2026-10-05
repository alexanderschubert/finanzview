@extends('layouts.app')

@section('title', 'Import prüfen – FinanzView')
@section('eyebrow', 'Buchungen')
@section('page_title', 'Import')

@php
    $valid = $items->whereNull('error');
    $newCount = $valid->whereNull('duplicate')->where('pending', false)->count();
    $importedCount = $valid->where('duplicate', 'imported')->count();
    $possibleCount = $valid->where('duplicate', 'possible')->count();
    $errorCount = $items->whereNotNull('error')->count();

    $categoriesByType = [
        'expense' => $categories->whereIn('type', ['expense', 'both']),
        'income' => $categories->whereIn('type', ['income', 'both']),
    ];

    $money = fn ($amount) => number_format((float) $amount, 2, ',', '.') . ' €';
@endphp

@section('content')

<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6 pb-32">

    <x-back-link :href="route('transactions.import.create')" label="Andere Datei" />

    <x-page-header title="Import prüfen" subtitle="Wähle aus, welche Buchungen übernommen werden sollen." />

    <x-flash />


    {{-- ========================================================= --}}
    {{-- ÜBERSICHT --}}
    {{-- ========================================================= --}}

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <x-stat label="Neu" tone="positive">{{ $newCount }}</x-stat>
        <x-stat label="Schon importiert">{{ $importedCount }}</x-stat>
        <x-stat label="Evtl. doppelt">{{ $possibleCount }}</x-stat>
        <x-stat label="Nicht lesbar" :tone="$errorCount > 0 ? 'negative' : 'neutral'">{{ $errorCount }}</x-stat>
    </div>

    @if ($truncated)
        <div class="flex gap-3 rounded-2xl bg-amber-50 dark:bg-amber-500/10 p-4 text-sm text-amber-800 dark:text-amber-300" role="status">
            <x-icon name="alert" class="w-5 h-5" />
            <p>Die Datei enthält mehr als {{ \App\Services\CsvImportService::MAX_ROWS }} Zeilen. Es werden nur die ersten {{ \App\Services\CsvImportService::MAX_ROWS }} angezeigt – importiere den Rest danach mit einer zweiten Datei bzw. einem kürzeren Zeitraum.</p>
        </div>
    @endif


    {{-- ========================================================= --}}
    {{-- KONTO UND SPALTEN --}}
    {{-- ========================================================= --}}

    <details class="fv-card group" @if (! $mappingComplete || $errorCount > 0 && $errorCount === $items->count()) open @endif>
        <summary class="flex items-center gap-3 px-5 py-4 cursor-pointer list-none [&::-webkit-details-marker]:hidden">
            <span class="w-9 h-9 rounded-xl bg-slate-100 dark:bg-white/5 text-slate-500 dark:text-slate-400 flex items-center justify-center">
                <x-icon name="settings" class="w-[18px] h-[18px]" />
            </span>
            <span class="flex-1 min-w-0">
                <span class="block font-medium text-slate-900 dark:text-white">Konto und Spalten</span>
                <span class="block text-[13px] text-slate-500 dark:text-slate-400 truncate">
                    {{ $account->name }}
                    · {{ $mappingComplete ? 'Spalten automatisch erkannt' : 'Bitte Spalten zuordnen' }}
                    @if ($invert) · Vorzeichen umgekehrt @endif
                </span>
            </span>
            <x-icon name="chevron-right" class="w-4 h-4 text-slate-400 transition group-open:rotate-90" />
        </summary>

        <form method="GET" action="{{ route('transactions.import.preview', $token) }}" class="border-t border-slate-100 dark:border-white/5 p-5 space-y-4">
            <x-field label="Konto" for="preview_account">
                <select id="preview_account" name="account_id" class="fv-input">
                    @foreach ($accounts as $option)
                        <option value="{{ $option->id }}" @selected($option->id === $account->id)>{{ $option->name }}</option>
                    @endforeach
                </select>
            </x-field>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach ($fieldLabels as $field => $label)
                    <x-field :label="$label" :for="'map_' . $field">
                        <select id="map_{{ $field }}" name="map[{{ $field }}]" class="fv-input">
                            <option value="">– nicht verwenden –</option>
                            @foreach ($headers as $index => $header)
                                <option value="{{ $index }}" @selected($mapping[$field] === $index)>{{ $header !== '' ? $header : 'Spalte ' . ($index + 1) }}</option>
                            @endforeach
                        </select>
                    </x-field>
                @endforeach
            </div>

            <p class="text-[13px] text-slate-500 dark:text-slate-400">
                Entweder eine Spalte „Betrag“ (negativ = Ausgabe) oder getrennte Spalten für Soll und Haben.
            </p>

            <label class="flex items-center justify-between gap-4 rounded-xl bg-slate-50 dark:bg-white/5 px-4 py-3 cursor-pointer">
                <span>
                    <span class="block text-sm font-medium text-slate-900 dark:text-white">Vorzeichen umkehren</span>
                    <span class="block text-[13px] text-slate-500 dark:text-slate-400">
                        Für Kreditkarten-Exporte, in denen Ausgaben positiv stehen (z. B. American Express).
                        @if ($invertSuggested) Automatisch erkannt. @endif
                    </span>
                </span>
                <input type="hidden" name="invert" value="0">
                <input type="checkbox" name="invert" value="1" class="sr-only peer" @checked($invert) onchange="this.form.requestSubmit()">
                <span aria-hidden="true" class="relative inline-flex h-[31px] w-[51px] shrink-0 rounded-full bg-slate-200 dark:bg-slate-700 transition peer-checked:bg-emerald-500 peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-500 after:absolute after:top-[2px] after:left-[2px] after:h-[27px] after:w-[27px] after:rounded-full after:bg-white after:shadow after:transition peer-checked:after:translate-x-[20px]"></span>
            </label>

            <div class="flex justify-end">
                <button type="submit" class="fv-btn fv-btn-secondary">Vorschau aktualisieren</button>
            </div>
        </form>
    </details>


    {{-- ========================================================= --}}
    {{-- BUCHUNGEN --}}
    {{-- ========================================================= --}}

    @if (! $mappingComplete)
        <div class="fv-card">
            <x-empty-state icon="alert" title="Spalten nicht erkannt">
                Ordne oben mindestens Datum und Betrag zu.
            </x-empty-state>
        </div>
    @else
        <form method="POST" action="{{ route('transactions.import.store', $token) }}" id="import-form" class="space-y-3">
            @csrf
            <input type="hidden" name="account_id" value="{{ $account->id }}">
            <input type="hidden" name="invert" value="{{ $invert ? '1' : '0' }}">
            @foreach ($mapping as $field => $column)
                <input type="hidden" name="map[{{ $field }}]" value="{{ $column }}">
            @endforeach
            <input type="hidden" name="selected" value="">
            <input type="hidden" name="categories" value="">

            <div class="flex items-center justify-between px-1">
                <h3 class="text-[13px] font-semibold text-slate-500 dark:text-slate-400">{{ $items->count() }} Zeilen</h3>
                <div class="flex gap-3 text-sm">
                    <button type="button" class="fv-link" data-select="new">Nur neue</button>
                    <button type="button" class="fv-link" data-select="all">Alle</button>
                    <button type="button" class="fv-link" data-select="none">Keine</button>
                </div>
            </div>

            <ul class="fv-card overflow-hidden divide-y divide-slate-100 dark:divide-white/5">
                @foreach ($items as $item)
                    @php
                        $disabled = $item['error'] !== null || $item['duplicate'] === 'imported';
                        $checked = ! $disabled && $item['duplicate'] === null && ! $item['pending'];
                    @endphp

                    <li class="flex gap-3 px-4 py-3 {{ $disabled ? 'opacity-50' : '' }}" data-row data-duplicate="{{ $item['duplicate'] ?? ($item['error'] ? 'error' : ($item['pending'] ? 'pending' : 'new')) }}">
                        <label class="pt-0.5 cursor-pointer">
                            <span class="sr-only">Zeile {{ $item['index'] + 1 }} importieren</span>
                            <input type="checkbox" name="import[]" value="{{ $item['index'] }}" class="w-5 h-5 rounded-md accent-emerald-600" @checked($checked) @disabled($disabled)>
                        </label>

                        <div class="flex-1 min-w-0 space-y-2">
                            <div class="flex items-start gap-3">
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-slate-900 dark:text-white truncate">
                                        {{ $item['merchant'] !== '' ? $item['merchant'] : $item['description'] }}
                                    </p>
                                    <p class="text-[13px] text-slate-500 dark:text-slate-400 truncate">
                                        {{ $item['date']?->format('d.m.Y') ?? '–' }}
                                        @if ($item['merchant'] !== '' && $item['description'] !== $item['merchant'])
                                            · {{ $item['description'] }}
                                        @endif
                                    </p>
                                </div>

                                @if ($item['amount'] !== null)
                                    <p class="shrink-0 font-semibold tabular-nums {{ $item['type'] === 'income' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-900 dark:text-white' }}">
                                        {{ $item['type'] === 'income' ? '+' : '−' }}{{ $money($item['amount']) }}
                                    </p>
                                @endif
                            </div>

                            @if ($item['error'])
                                <p class="text-[13px] text-red-600 dark:text-red-400">Zeile {{ $item['index'] + 1 }}: {{ $item['error'] }}</p>
                            @elseif ($item['duplicate'] === 'imported')
                                <p class="text-[13px] text-slate-500 dark:text-slate-400">Bereits importiert</p>
                            @else
                                <div class="flex flex-wrap items-center gap-2">
                                    <select name="category[{{ $item['index'] }}]" class="fv-input py-1.5 text-sm w-auto max-w-full" aria-label="Kategorie">
                                        <option value="0">Keine Kategorie</option>
                                        @foreach ($categoriesByType[$item['type']] ?? [] as $category)
                                            <option value="{{ $category->id }}" @selected($item['category_id'] === $category->id)>{{ $category->icon }} {{ $category->display_name }}</option>
                                        @endforeach
                                    </select>

                                    @if ($item['pending'])
                                        <span class="inline-flex items-center gap-1 rounded-full bg-sky-100 dark:bg-sky-500/15 px-2 py-0.5 text-xs font-medium text-sky-800 dark:text-sky-300">
                                            Vorgemerkt – noch nicht gebucht
                                        </span>
                                    @endif

                                    @if ($item['duplicate'] === 'possible')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 dark:bg-amber-500/15 px-2 py-0.5 text-xs font-medium text-amber-800 dark:text-amber-300">
                                            Gleiche Buchung vorhanden
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>

            {{-- Aktionsleiste --}}
            <div class="fixed inset-x-0 bottom-0 z-30 lg:pl-64 pb-[calc(env(safe-area-inset-bottom)+4.5rem)] lg:pb-4 pointer-events-none">
                <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="fv-glass pointer-events-auto rounded-2xl shadow-lg ring-1 ring-black/5 dark:ring-white/10 p-3 flex items-center gap-3">
                        <button type="submit" form="cancel-form" class="fv-btn fv-btn-secondary">Abbrechen</button>
                        <button type="submit" class="fv-btn fv-btn-primary flex-1" data-submit>
                            <span data-count>{{ $newCount }}</span>&nbsp;<span data-count-label>{{ $newCount === 1 ? 'Buchung' : 'Buchungen' }}</span>&nbsp;importieren
                        </button>
                    </div>
                </div>
            </div>
        </form>
    @endif

    <form method="POST" action="{{ route('transactions.import.destroy', $token) }}" id="cancel-form" class="hidden">
        @csrf
        @method('DELETE')
    </form>

</div>

<script>
    (function () {
        const form = document.getElementById('import-form');
        if (!form) return;

        const boxes = () => Array.from(form.querySelectorAll('input[name="import[]"]:not(:disabled)'));

        const update = () => {
            const count = boxes().filter(box => box.checked).length;
            form.querySelector('[data-count]').textContent = count;
            form.querySelector('[data-count-label]').textContent = count === 1 ? 'Buchung' : 'Buchungen';
            form.querySelector('[data-submit]').disabled = count === 0;
        };

        form.addEventListener('change', event => {
            if (event.target.name === 'import[]') update();
        });

        form.querySelectorAll('[data-select]').forEach(button => button.addEventListener('click', () => {
            boxes().forEach(box => {
                const kind = box.closest('[data-row]').dataset.duplicate;
                box.checked = button.dataset.select === 'all' || (button.dataset.select === 'new' && kind === 'new');
            });
            update();
        }));

        // Auswahl und Kategorien gebündelt senden (große Dateien, max_input_vars).
        form.addEventListener('submit', () => {
            const selected = boxes().filter(box => box.checked);

            form.elements.selected.value = selected.map(box => box.value).join(',');
            form.elements.categories.value = selected.map(box => {
                const select = form.querySelector(`select[name="category[${box.value}]"]`);
                return select ? `${box.value}:${select.value}` : null;
            }).filter(Boolean).join(';');

            form.querySelectorAll('input[name="import[]"], select[name^="category["]').forEach(input => input.disabled = true);
            form.querySelector('[data-submit]').disabled = true;
        });

        // Zurück-Navigation (Seiten-Cache): Felder wieder freigeben.
        window.addEventListener('pageshow', () => {
            form.querySelectorAll('input[name="import[]"], select[name^="category["]').forEach(input => {
                input.disabled = input.closest('[data-row]').dataset.duplicate === 'imported'
                    || input.closest('[data-row]').dataset.duplicate === 'error';
            });
            update();
        });

        update();
    })();
</script>

@endsection
