@extends('layouts.app')

@section('title', 'Empfänger – FinanzView')
@section('eyebrow', 'Kategorien')
@section('page_title', 'Empfänger')

@php
    $money = fn ($amount) => number_format((float) $amount, 2, ',', '.') . ' €';
    $sorts = ['count' => 'Häufigkeit', 'amount' => 'Betrag', 'recent' => 'Zuletzt', 'name' => 'Name'];
@endphp

@section('content')

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6 pb-32">

    <x-back-link :href="route('categories.index')" label="Kategorien" />

    <x-page-header title="Empfänger" subtitle="Einheitliche Namen für Händler und Auftraggeber – mit Standardkategorie, die bei neuen Buchungen automatisch greift. Der Buchungstext der Bank bleibt dabei unverändert." />

    <x-flash />

    {{-- ANSICHT --}}

    <div class="grid grid-cols-2 gap-1 rounded-xl bg-slate-100 dark:bg-white/5 p-1" role="tablist">
        <a href="{{ route('payees.index') }}" role="tab" aria-selected="{{ $showSuggestions ? 'false' : 'true' }}"
            class="rounded-lg py-2 text-center text-sm font-medium transition {{ $showSuggestions ? 'text-slate-600 dark:text-slate-300' : 'bg-white text-slate-900 shadow-sm dark:bg-slate-700 dark:text-white' }}">
            Alle <span class="text-slate-400 font-normal">· {{ $total }}</span>
        </a>
        <a href="{{ route('payees.index', ['view' => 'suggestions']) }}" role="tab" aria-selected="{{ $showSuggestions ? 'true' : 'false' }}"
            class="rounded-lg py-2 text-center text-sm font-medium transition {{ $showSuggestions ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-700 dark:text-white' : 'text-slate-600 dark:text-slate-300' }}">
            Ähnliche Namen
            @if ($suggestions->isNotEmpty())
                <span class="ml-1 rounded-full bg-emerald-600 text-white text-[11px] px-1.5 py-0.5">{{ $suggestions->count() }}</span>
            @endif
        </a>
    </div>


    @if ($showSuggestions)

        {{-- ========================================================= --}}
        {{-- ÄHNLICHE NAMEN --}}
        {{-- ========================================================= --}}

        @if ($suggestions->isEmpty())
            <div class="fv-card">
                <x-empty-state icon="check-circle" title="Keine ähnlichen Namen">
                    Alles aufgeräumt – es gibt keine Empfänger, die sich nur in Ziffern oder Schreibweise unterscheiden.
                </x-empty-state>
            </div>
        @else
            <div class="space-y-3">
                @foreach ($suggestions as $group)
                    @php $best = $group->first(); @endphp
                    <div class="fv-card p-4 sm:p-5 space-y-3">
                        <ul class="divide-y divide-slate-100 dark:divide-white/5">
                            @foreach ($group as $payee)
                                @php $stat = $stats[$payee->id] ?? ['count' => 0]; @endphp
                                <li class="flex items-center gap-3 py-2">
                                    <p class="flex-1 min-w-0 text-sm font-medium text-slate-900 dark:text-white truncate">{{ $payee->name }}</p>
                                    <p class="shrink-0 text-xs text-slate-500 dark:text-slate-400 tabular-nums">{{ $stat['count'] === 1 ? '1 Buchung' : $stat['count'] . ' Buchungen' }}</p>
                                </li>
                            @endforeach
                        </ul>

                        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                            <form method="POST" action="{{ route('payees.ignore') }}">
                                @csrf
                                @foreach ($group as $payee) <input type="hidden" name="ids[]" value="{{ $payee->id }}"> @endforeach
                                <button type="submit" class="fv-btn fv-btn-secondary text-sm py-2 w-full sm:w-auto">Nicht zusammenführen</button>
                            </form>

                            <form method="POST" action="{{ route('payees.merge.store') }}">
                                @csrf
                                @foreach ($group as $payee) <input type="hidden" name="ids[]" value="{{ $payee->id }}"> @endforeach
                                <input type="hidden" name="name" value="{{ $best->name }}">
                                <button type="submit" class="fv-btn fv-btn-primary text-sm py-2 w-full sm:w-auto">Zu „{{ \Illuminate\Support\Str::limit($best->name, 28) }}“ zusammenführen</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    @else

        {{-- ========================================================= --}}
        {{-- ALLE EMPFÄNGER --}}
        {{-- ========================================================= --}}

        <form method="GET" action="{{ route('payees.index') }}" class="flex gap-2">
            <div class="relative flex-1">
                <x-icon name="search" class="w-[18px] h-[18px] absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
                <input type="search" name="q" value="{{ $search }}" placeholder="Empfänger suchen" aria-label="Empfänger suchen" class="fv-input pl-10">
            </div>
            <select name="sort" aria-label="Sortierung" onchange="this.form.submit()" class="fv-input w-auto">
                @foreach ($sorts as $key => $label)
                    <option value="{{ $key }}" @selected($sort === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </form>

        @if ($rows->isEmpty())
            <div class="fv-card">
                <x-empty-state icon="user" :title="$search !== '' ? 'Kein Empfänger gefunden' : 'Noch keine Empfänger'">
                    {{ $search !== '' ? 'Versuche einen anderen Suchbegriff.' : 'Empfänger entstehen automatisch aus den Händlernamen deiner Buchungen.' }}
                </x-empty-state>
            </div>
        @else
            <form method="POST" action="{{ route('payees.merge') }}" id="merge-form">
                @csrf

                <ul class="fv-card overflow-hidden divide-y divide-slate-100 dark:divide-white/5">
                    @foreach ($rows as $row)
                        @php $payee = $row['payee']; @endphp
                        <li class="flex items-center">
                            <label class="pl-4 pr-1 py-3 cursor-pointer" title="Zum Zusammenführen auswählen">
                                <span class="sr-only">{{ $payee->name }} auswählen</span>
                                <input type="checkbox" name="ids[]" value="{{ $payee->id }}" class="w-5 h-5 rounded-md accent-emerald-600" data-select>
                            </label>

                            <a href="{{ route('payees.edit', $payee) }}" class="flex-1 min-w-0 flex items-center gap-3 pl-2 pr-4 py-3 hover:bg-slate-50 dark:hover:bg-white/5 transition">
                                <x-emoji-tile :emoji="$payee->defaultCategory?->icon" fallback="user" :color="$payee->defaultCategory?->color" size="sm" />

                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-slate-900 dark:text-white truncate">{{ $payee->name }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                                        {{ $row['count'] === 1 ? '1 Buchung' : $row['count'] . ' Buchungen' }}
                                        @if ($row['last']) · zuletzt {{ \Carbon\Carbon::parse($row['last'])->format('d.m.Y') }} @endif
                                        · {{ $payee->defaultCategory?->display_name ?? 'ohne Standardkategorie' }}
                                    </p>
                                </div>

                                <div class="text-right shrink-0">
                                    @if ($row['expense'] > 0)
                                        <p class="text-sm font-semibold tabular-nums text-slate-900 dark:text-white">−{{ $money($row['expense']) }}</p>
                                    @endif
                                    @if ($row['income'] > 0)
                                        <p class="text-xs tabular-nums text-emerald-600 dark:text-emerald-400">+{{ $money($row['income']) }}</p>
                                    @endif
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>

                {{-- Aktionsleiste --}}
                <div class="fixed inset-x-0 bottom-0 z-30 lg:pl-64 pb-[calc(env(safe-area-inset-bottom)+4.5rem)] lg:pb-4 pointer-events-none" data-merge-bar hidden>
                    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div class="fv-glass pointer-events-auto rounded-2xl shadow-lg ring-1 ring-black/5 dark:ring-white/10 p-3 flex items-center gap-3">
                            <p class="flex-1 text-sm text-slate-600 dark:text-slate-300"><span data-count>0</span> ausgewählt</p>
                            <button type="submit" class="fv-btn fv-btn-primary" data-merge-button disabled>Zusammenführen</button>
                        </div>
                    </div>
                </div>
            </form>

            <p class="px-1 text-[13px] text-slate-500 dark:text-slate-400">
                {{ $rows->count() === $total ? 'Alle ' . $total . ' Empfänger werden angezeigt.' : $rows->count() . ' von ' . $total . ' Empfängern (Suche).' }}
                Wähle mindestens zwei aus, um sie zusammenzuführen – z. B. „PayPal *Patreon“ und „PAYPAL *PATREONIREL“.
            </p>

        @endif

    @endif

</div>

<script>
    (function () {
        const bar = document.querySelector('[data-merge-bar]');
        if (!bar) return;

        const boxes = () => Array.from(document.querySelectorAll('[data-select]'));

        const update = () => {
            const count = boxes().filter(box => box.checked).length;
            bar.hidden = count === 0;
            bar.querySelector('[data-count]').textContent = count;
            bar.querySelector('[data-merge-button]').disabled = count < 2;
        };

        document.addEventListener('change', event => { if (event.target.matches('[data-select]')) update(); });
        window.addEventListener('pageshow', update);
        update();
    })();
</script>

@endsection
