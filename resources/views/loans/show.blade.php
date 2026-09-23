@extends('layouts.app')

@section('title', $loan->name . ' – Kredit – FinanzView')
@section('eyebrow', 'Kredite')
@section('page_title', $loan->name)

@php
    $principal = (float) $loan->principal_amount;
    $remaining = max(0, (float) $loan->remaining_amount);
    $progress = (float) ($loan->progress ?? 0);

    $typeLabels = [
        'loan' => 'Ratenkredit',
        'installment' => 'Finanzierung',
        'paypal_installment' => 'PayPal Ratenzahlung',
        'other' => 'Kredit',
    ];

    $statusLabels = [
        'planned' => ['Geplant', 'bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-slate-300'],
        'paid' => ['Bezahlt', 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300'],
        'overdue' => ['Überfällig', 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300'],
        'cancelled' => ['Entfällt', 'bg-slate-100 text-slate-400 dark:bg-white/5 dark:text-slate-500'],
    ];

    $upcoming = $regularPayments
        ->whereIn('status', ['planned', 'overdue'])
        ->sortBy('due_date')
        ->take(6);

    $plannedCount = $regularPayments->whereIn('status', ['planned', 'overdue'])->count();
    $lastPlanned = $regularPayments->whereIn('status', ['planned', 'overdue'])->sortBy('due_date')->last();

    $money = fn ($value) => number_format((float) $value, 2, ',', '.') . ' €';
@endphp

@section('content')

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <div class="flex items-center gap-4">
        <x-financial-provider :provider="$loan->provider" :fallback-icon="$loan->creditor_icon ?: '🏦'" size="md" />

        <div class="flex-1 min-w-0">
            <h2 class="text-[28px] sm:text-[34px] leading-tight font-bold tracking-tight text-slate-900 dark:text-white truncate">{{ $loan->name }}</h2>
            <p class="text-[15px] text-slate-500 dark:text-slate-400 truncate">
                {{ $loan->creditor_name ?: ($typeLabels[$loan->type] ?? 'Kredit') }}
                @unless ($loan->is_active) · abgeschlossen @endunless
            </p>
        </div>

        <a href="{{ route('loans.edit', $loan) }}" class="fv-btn fv-btn-secondary text-sm py-2.5">
            <x-icon name="pencil" class="w-4 h-4" />
            <span class="hidden sm:inline">Bearbeiten</span>
        </a>
    </div>

    <x-flash />

    @if ($errors->any())
        <div class="flex gap-3 rounded-2xl bg-red-50 dark:bg-red-500/10 p-4 text-sm text-red-700 dark:text-red-300" role="alert">
            <x-icon name="alert" class="w-5 h-5" />
            <div>
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        </div>
    @endif


    {{-- RESTSCHULD --}}

    <div class="fv-card p-6 sm:p-8 flex flex-col sm:flex-row items-center gap-6 sm:gap-10">

        <x-progress-ring :value="$progress" :size="170" :stroke="15">
            <p class="text-xs text-slate-500 dark:text-slate-400">getilgt</p>
            <p class="text-3xl font-semibold tabular-nums text-slate-900 dark:text-white">{{ number_format($progress, 0, ',', '.') }} %</p>
        </x-progress-ring>

        <div class="w-full flex-1">
            <p class="text-sm text-slate-500 dark:text-slate-400">Restschuld</p>
            <p class="text-[34px] leading-tight font-semibold tracking-tight tabular-nums text-slate-900 dark:text-white">{{ $money($remaining) }}</p>
            <p class="text-sm text-slate-500 dark:text-slate-400 tabular-nums">von {{ $money($principal) }}</p>

            <dl class="mt-5 grid grid-cols-2 gap-x-4 gap-y-3 text-sm">
                <div>
                    <dt class="text-xs text-slate-500 dark:text-slate-400">Monatsrate</dt>
                    <dd class="font-semibold tabular-nums text-slate-900 dark:text-white">{{ $money($loan->installment_amount) }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500 dark:text-slate-400">Zinssatz</dt>
                    <dd class="font-semibold tabular-nums text-slate-900 dark:text-white">{{ number_format((float) $loan->interest_rate, 2, ',', '.') }} %</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500 dark:text-slate-400">Offene Raten</dt>
                    <dd class="font-semibold tabular-nums text-slate-900 dark:text-white">{{ $plannedCount }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500 dark:text-slate-400">Letzte Rate</dt>
                    <dd class="font-semibold tabular-nums text-slate-900 dark:text-white">{{ $lastPlanned?->due_date?->format('m/Y') ?? ($loan->end_date?->format('m/Y') ?? '–') }}</dd>
                </div>
            </dl>
        </div>

    </div>


    {{-- NÄCHSTE RATEN --}}

    <section>
        <h3 class="px-1 pb-2 text-[13px] font-semibold text-slate-500 dark:text-slate-400">Nächste Raten</h3>

        @if ($upcoming->isEmpty())
            <div class="fv-card px-4 py-4 text-sm text-slate-500 dark:text-slate-400">
                Keine offenen Raten – dieser Kredit ist vollständig verplant oder abbezahlt.
            </div>
        @else
            <ul class="fv-card overflow-hidden divide-y divide-slate-100 dark:divide-white/5">
                @foreach ($upcoming as $payment)
                    @php [$statusLabel, $statusClass] = $statusLabels[$payment->status] ?? $statusLabels['planned']; @endphp

                    <li class="flex items-center gap-3 px-4 py-3">
                        <div class="w-11 shrink-0 text-center">
                            <p class="text-[11px] font-semibold uppercase text-red-500">{{ $payment->due_date?->translatedFormat('M') }}</p>
                            <p class="text-lg font-semibold leading-tight tabular-nums text-slate-900 dark:text-white">{{ $payment->due_date?->format('j') }}</p>
                        </div>

                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-slate-900 dark:text-white">Rate {{ $payment->installment_number }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 tabular-nums truncate">
                                Tilgung {{ $money($payment->principal_amount) }} · Zinsen {{ $money($payment->interest_amount) }}
                            </p>
                        </div>

                        <div class="text-right shrink-0">
                            <p class="font-semibold tabular-nums text-slate-900 dark:text-white">{{ $money($payment->amount) }}</p>
                            @if ($payment->status !== 'planned')
                                <span class="inline-block mt-0.5 rounded-full px-2 py-0.5 text-[11px] font-medium {{ $statusClass }}">{{ $statusLabel }}</span>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>


    {{-- SONDERTILGUNG --}}

    <details class="fv-card group overflow-hidden" @if ($errors->hasAny(['amount', 'paid_date', 'notes'])) open @endif>
        <summary class="cursor-pointer list-none flex items-center gap-3 px-4 py-4">
            <span class="w-9 h-9 rounded-full bg-emerald-600 text-white flex items-center justify-center">
                <x-icon name="plus" class="w-5 h-5" />
            </span>
            <span class="flex-1">
                <span class="block font-medium text-slate-900 dark:text-white">Sondertilgung erfassen</span>
                <span class="block text-[13px] text-slate-500 dark:text-slate-400">
                    @if ($extraPayments->isNotEmpty())
                        Bisher {{ $money($extraPayments->where('status', 'paid')->sum('amount')) }} extra getilgt
                    @else
                        Zusätzliche Zahlung verkürzt die Laufzeit
                    @endif
                </span>
            </span>
            <x-icon name="chevron-right" class="w-4 h-4 text-slate-400 transition group-open:rotate-90" />
        </summary>

        <form method="POST" action="{{ route('loans.extra-payment', $loan) }}" class="border-t border-slate-100 dark:border-white/5 p-4 space-y-4">
            @csrf

            <div class="grid grid-cols-2 gap-4">
                <x-field label="Betrag" for="extra_amount" error="amount">
                    <input id="extra_amount" name="amount" type="number" step="0.01" min="0.01" inputmode="decimal" required
                        value="{{ old('amount') }}" placeholder="0,00" class="fv-input tabular-nums">
                </x-field>

                <x-field label="Gezahlt am" for="paid_date" error="paid_date">
                    <input id="paid_date" name="paid_date" type="date" required value="{{ old('paid_date', now()->format('Y-m-d')) }}" class="fv-input">
                </x-field>
            </div>

            <x-field label="Notiz" for="extra_notes" error="notes">
                <input id="extra_notes" name="notes" type="text" maxlength="1000" value="{{ old('notes') }}" placeholder="Optional" class="fv-input">
            </x-field>

            <div class="flex justify-end">
                <button type="submit" class="fv-btn fv-btn-primary text-sm">Sondertilgung speichern</button>
            </div>
        </form>

        @if ($extraPayments->isNotEmpty())
            <ul class="border-t border-slate-100 dark:border-white/5 divide-y divide-slate-100 dark:divide-white/5">
                @foreach ($extraPayments->sortByDesc('paid_date') as $payment)
                    <li class="flex items-center justify-between gap-3 px-4 py-3 text-sm">
                        <span class="text-slate-600 dark:text-slate-300">
                            {{ $payment->paid_date?->format('d.m.Y') ?? $payment->due_date?->format('d.m.Y') }}
                            @if ($payment->notes)
                                · {{ $payment->notes }}
                            @endif
                        </span>
                        <span class="font-semibold tabular-nums text-emerald-600 dark:text-emerald-400">{{ $money($payment->amount) }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </details>


    {{-- TILGUNGSPLAN --}}

    @if ($regularPayments->isNotEmpty())
        <details class="fv-card group overflow-hidden">
            <summary class="cursor-pointer list-none flex items-center gap-3 px-4 py-4">
                <x-emoji-tile fallback="calendar" size="sm" />
                <span class="flex-1 font-medium text-slate-900 dark:text-white">Kompletter Tilgungsplan</span>
                <span class="text-sm text-slate-500 dark:text-slate-400">{{ $regularPayments->count() }} Raten</span>
                <x-icon name="chevron-right" class="w-4 h-4 text-slate-400 transition group-open:rotate-90" />
            </summary>

            <div class="border-t border-slate-100 dark:border-white/5 overflow-x-auto">
                <table class="w-full text-sm tabular-nums">
                    <thead class="text-xs text-slate-500 dark:text-slate-400">
                        <tr class="text-left">
                            <th class="px-4 py-2 font-medium">Nr.</th>
                            <th class="px-2 py-2 font-medium">Fällig</th>
                            <th class="px-2 py-2 font-medium text-right">Rate</th>
                            <th class="px-2 py-2 font-medium text-right hidden sm:table-cell">Zinsen</th>
                            <th class="px-2 py-2 font-medium text-right hidden sm:table-cell">Tilgung</th>
                            <th class="px-2 py-2 font-medium text-right">Rest</th>
                            <th class="px-4 py-2 font-medium text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-white/5">
                        @foreach ($regularPayments as $payment)
                            @php [$statusLabel, $statusClass] = $statusLabels[$payment->status] ?? $statusLabels['planned']; @endphp
                            <tr class="{{ in_array($payment->status, ['paid', 'cancelled'], true) ? 'text-slate-400 dark:text-slate-500' : 'text-slate-700 dark:text-slate-200' }}">
                                <td class="px-4 py-2">{{ $payment->installment_number }}</td>
                                <td class="px-2 py-2">{{ $payment->due_date?->format('d.m.Y') }}</td>
                                <td class="px-2 py-2 text-right">{{ number_format((float) $payment->amount, 2, ',', '.') }}</td>
                                <td class="px-2 py-2 text-right hidden sm:table-cell">{{ number_format((float) $payment->interest_amount, 2, ',', '.') }}</td>
                                <td class="px-2 py-2 text-right hidden sm:table-cell">{{ number_format((float) $payment->principal_amount, 2, ',', '.') }}</td>
                                <td class="px-2 py-2 text-right">{{ number_format((float) $payment->remaining_amount, 2, ',', '.') }}</td>
                                <td class="px-4 py-2 text-right">
                                    <span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $statusClass }}">{{ $statusLabel }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    @endif


    {{-- DETAILS --}}

    <dl class="fv-card overflow-hidden divide-y divide-slate-100 dark:divide-white/5 text-sm">
        <div class="flex items-center justify-between gap-4 px-4 py-3">
            <dt class="text-slate-500 dark:text-slate-400">Art</dt>
            <dd class="font-medium text-slate-900 dark:text-white">{{ $typeLabels[$loan->type] ?? 'Kredit' }}</dd>
        </div>
        <div class="flex items-center justify-between gap-4 px-4 py-3">
            <dt class="text-slate-500 dark:text-slate-400">Beginn</dt>
            <dd class="font-medium text-slate-900 dark:text-white">{{ $loan->start_date?->format('d.m.Y') ?? '–' }}</dd>
        </div>
        <div class="flex items-center justify-between gap-4 px-4 py-3">
            <dt class="text-slate-500 dark:text-slate-400">Abbuchung von</dt>
            <dd class="font-medium text-slate-900 dark:text-white">{{ $loan->account?->name ?? '–' }}</dd>
        </div>
        @if ($loan->notes)
            <div class="px-4 py-3">
                <dt class="text-slate-500 dark:text-slate-400">Notizen</dt>
                <dd class="mt-1 whitespace-pre-line text-slate-900 dark:text-white">{{ $loan->notes }}</dd>
            </div>
        @endif
    </dl>

    <form method="POST" action="{{ route('loans.destroy', $loan) }}"
        onsubmit="return confirm('Kredit „{{ addslashes($loan->name) }}“ mit Tilgungsplan wirklich löschen?');">
        @csrf
        @method('DELETE')
        <button type="submit" class="fv-card w-full py-3.5 text-center font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 transition">
            Kredit löschen
        </button>
    </form>

</div>

@endsection
