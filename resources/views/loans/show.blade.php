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

    <x-back-link :href="route('loans.index')" label="Kredite" />

    <x-page-header :title="$loan->name" :subtitle="($loan->creditor_name ?: ($typeLabels[$loan->type] ?? 'Kredit')) . ($loan->is_active ? '' : ' · abgeschlossen')">
        <x-slot:leading>
            <x-financial-provider :provider="$loan->provider" :fallback-icon="$loan->creditor_icon ?: '🏦'" size="md" />
        </x-slot:leading>

        <a href="{{ route('loans.edit', $loan) }}" class="fv-btn fv-btn-secondary text-sm py-2.5">
            <x-icon name="pencil" class="w-4 h-4" />
            Bearbeiten
        </a>
    </x-page-header>

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
            <p class="fv-display text-[34px] leading-tight font-extrabold tabular-nums text-slate-900 dark:text-white">{{ $money($remaining) }}</p>
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


    {{-- KENNZAHLEN DES TILGUNGSPLANS --}}

    @if ($planTotals['count'] > 0)
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <x-stat label="Zinsen insgesamt" icon="percent" tone="negative">{{ $money($planTotals['interest']) }}</x-stat>
            <x-stat label="Alle Raten zusammen" icon="banknote">{{ $money($planTotals['total']) }}</x-stat>
            <x-stat label="Laufzeit" icon="calendar" :hint="$lastPlanned ? 'bis ' . $lastPlanned->due_date?->format('m/Y') : null">{{ $planTotals['count'] }} Monate</x-stat>
        </div>
    @endif


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
        @php
            $byYear = $regularPayments->groupBy(fn ($payment) => $payment->due_date?->format('Y') ?? '–');
            $currentYear = now()->format('Y');
            $openYear = $byYear->has($currentYear) ? $currentYear : $byYear->keys()->first();
            $maxYearTotal = max(1, (float) $byYear->map(fn ($year) => $year->sum('amount'))->max());
            $paidCount = $regularPayments->where('status', 'paid')->count();
            $nextPayment = $regularPayments->whereIn('status', ['planned', 'overdue'])->sortBy('due_date')->first();
        @endphp

        <details class="fv-card group overflow-hidden" open data-plan>

            <summary class="cursor-pointer list-none flex items-center gap-3 px-5 py-4">
                <span class="w-10 h-10 shrink-0 rounded-2xl bg-linear-to-br from-emerald-400 to-emerald-700 text-white flex items-center justify-center shadow-lg shadow-emerald-900/20">
                    <x-icon name="calendar" class="w-5 h-5" />
                </span>

                <span class="flex-1 min-w-0">
                    <span class="block font-semibold text-slate-900 dark:text-white">Tilgungsplan</span>
                    <span class="block text-[13px] text-slate-500 dark:text-slate-400 truncate">
                        {{ $planTotals['count'] }} Raten · {{ $paidCount }} bezahlt @if ($lastPlanned) · letzte Rate {{ $lastPlanned->due_date?->format('m/Y') }} @endif
                    </span>
                </span>

                <span class="hidden sm:flex items-center gap-3 text-xs text-slate-500 dark:text-slate-400" aria-hidden="true">
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500 dark:bg-emerald-400"></span>Tilgung</span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-amber-500 dark:bg-amber-400"></span>Zinsen</span>
                </span>

                <x-icon name="chevron-right" class="w-4 h-4 text-slate-400 transition group-open:rotate-90" />
            </summary>

            <div class="border-t border-slate-100 dark:border-white/5">

                {{-- WERKZEUGE --}}

                <div class="flex items-center justify-between gap-3 px-5 py-2.5 text-[13px]">
                    <div class="flex items-center gap-3">
                        <button type="button" class="fv-link" onclick="this.closest('[data-plan]').querySelectorAll('details[data-year]').forEach(d => d.open = true)">Alle Jahre aufklappen</button>
                        <button type="button" class="text-slate-500 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white" onclick="this.closest('[data-plan]').querySelectorAll('details[data-year]').forEach(d => d.open = false)">Alle zuklappen</button>
                    </div>

                    <a href="{{ route('loans.plan.export', $loan) }}" class="fv-link inline-flex items-center gap-1">
                        <x-icon name="download" class="w-3.5 h-3.5" />
                        CSV
                    </a>
                </div>


                {{-- JAHRE --}}

                @foreach ($byYear as $year => $yearPayments)
                    @php
                        $yearInterest = (float) $yearPayments->sum('interest_amount');
                        $yearPrincipal = (float) $yearPayments->sum('principal_amount');
                        $yearTotal = (float) $yearPayments->sum('amount');
                        $yearPaid = $yearPayments->where('status', 'paid')->count();
                        $barWidth = max(8, round($yearTotal / $maxYearTotal * 100));
                    @endphp

                    <details class="group/year border-t border-slate-100 dark:border-white/5" data-year @if ((string) $year === (string) $openYear) open @endif>

                        <summary class="cursor-pointer list-none px-5 py-3 hover:bg-slate-900/[0.03] dark:hover:bg-white/[0.04] transition">
                            <div class="flex items-center gap-3">
                                <x-icon name="chevron-right" class="w-4 h-4 shrink-0 text-slate-400 transition group-open/year:rotate-90" />

                                <span class="w-12 shrink-0 font-semibold tabular-nums text-slate-900 dark:text-white">{{ $year }}</span>

                                <span class="flex-1 min-w-0">
                                    <span class="block h-2.5 rounded-full bg-slate-900/5 dark:bg-white/10 overflow-hidden">
                                        <span class="flex h-full rounded-full overflow-hidden" style="width: {{ $barWidth }}%">
                                            <span class="h-full bg-emerald-500 dark:bg-emerald-400" style="width: {{ $yearTotal > 0 ? round($yearPrincipal / $yearTotal * 100, 1) : 0 }}%"></span>
                                            <span class="h-full bg-amber-500 dark:bg-amber-400 flex-1"></span>
                                        </span>
                                    </span>
                                </span>

                                <span class="shrink-0 text-right">
                                    <span class="block text-sm font-semibold tabular-nums text-slate-900 dark:text-white">{{ $money($yearTotal) }}</span>
                                    <span class="block text-[11px] text-slate-500 dark:text-slate-400 tabular-nums">
                                        {{ $yearPaid }}/{{ $yearPayments->count() }} bezahlt
                                    </span>
                                </span>
                            </div>

                            <p class="mt-1 pl-[4.75rem] text-[11px] text-slate-500 dark:text-slate-400 tabular-nums">
                                Tilgung {{ $money($yearPrincipal) }} · Zinsen {{ $money($yearInterest) }}
                            </p>
                        </summary>

                        <div class="overflow-x-auto">
                            <table class="w-full text-sm tabular-nums">
                                <thead class="text-[11px] uppercase tracking-wide text-slate-400 dark:text-slate-500">
                                    <tr class="text-left">
                                        <th class="pl-5 pr-2 py-1.5 font-medium">Nr.</th>
                                        <th class="px-2 py-1.5 font-medium">Fällig</th>
                                        <th class="px-2 py-1.5 font-medium text-right">Rate</th>
                                        <th class="px-2 py-1.5 font-medium text-right hidden sm:table-cell">Zinsen</th>
                                        <th class="px-2 py-1.5 font-medium text-right hidden sm:table-cell">Tilgung</th>
                                        <th class="px-2 py-1.5 font-medium text-right">Rest</th>
                                        <th class="pl-2 pr-5 py-1.5 font-medium text-right"><span class="sr-only">Status</span></th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-slate-100/80 dark:divide-white/5">
                                    @foreach ($yearPayments as $payment)
                                        @php
                                            $isNext = $nextPayment && $payment->id === $nextPayment->id;
                                            $done = in_array($payment->status, ['paid', 'cancelled'], true);
                                        @endphp

                                        <tr class="{{ $isNext ? 'bg-emerald-500/10' : '' }} {{ $done ? 'text-slate-400 dark:text-slate-500' : 'text-slate-700 dark:text-slate-200' }}">
                                            <td class="pl-5 pr-2 py-2 {{ $isNext ? 'border-l-2 border-emerald-500' : '' }}">{{ $payment->installment_number }}</td>
                                            <td class="px-2 py-2">
                                                {{ $payment->due_date?->format('d.m.Y') }}
                                                <span class="block sm:hidden text-[11px] text-slate-400 dark:text-slate-500">Z {{ number_format((float) $payment->interest_amount, 2, ',', '.') }} · T {{ number_format((float) $payment->principal_amount, 2, ',', '.') }}</span>
                                            </td>
                                            <td class="px-2 py-2 text-right font-medium">{{ number_format((float) $payment->amount, 2, ',', '.') }}</td>
                                            <td class="px-2 py-2 text-right hidden sm:table-cell text-amber-600 dark:text-amber-400/90">{{ number_format((float) $payment->interest_amount, 2, ',', '.') }}</td>
                                            <td class="px-2 py-2 text-right hidden sm:table-cell text-emerald-600 dark:text-emerald-400/90">{{ number_format((float) $payment->principal_amount, 2, ',', '.') }}</td>
                                            <td class="px-2 py-2 text-right">{{ number_format((float) $payment->remaining_amount, 2, ',', '.') }}</td>
                                            <td class="pl-2 pr-5 py-2 text-right whitespace-nowrap">
                                                @if ($payment->status === 'paid')
                                                    <x-icon name="check-circle" class="inline w-4 h-4 text-emerald-500" />
                                                    <span class="sr-only">Bezahlt</span>
                                                @elseif ($payment->status === 'overdue')
                                                    <span class="rounded-full px-2 py-0.5 text-[11px] font-medium bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300">Überfällig</span>
                                                @elseif ($payment->status === 'cancelled')
                                                    <span class="rounded-full px-2 py-0.5 text-[11px] font-medium bg-slate-100 text-slate-400 dark:bg-white/5 dark:text-slate-500">Entfällt</span>
                                                @elseif ($isNext)
                                                    <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold bg-emerald-500/15 text-emerald-700 dark:text-emerald-300">Nächste</span>
                                                @else
                                                    <span class="inline-block w-2 h-2 rounded-full bg-slate-300 dark:bg-slate-600" aria-hidden="true"></span>
                                                    <span class="sr-only">Geplant</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                    </details>
                @endforeach


                {{-- SUMME --}}

                <div class="border-t border-slate-200 dark:border-white/10 px-5 py-3 flex flex-wrap items-center justify-between gap-x-6 gap-y-1 text-sm font-semibold text-slate-900 dark:text-white tabular-nums">
                    <span>Summe aller Raten</span>
                    <span class="flex items-center gap-4">
                        <span class="text-amber-600 dark:text-amber-400">Zinsen {{ $money($planTotals['interest']) }}</span>
                        <span class="text-emerald-600 dark:text-emerald-400">Tilgung {{ $money($planTotals['principal']) }}</span>
                        <span>{{ $money($planTotals['total']) }}</span>
                    </span>
                </div>

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
        @if ($loan->effective_rate !== null)
            <div class="flex items-center justify-between gap-4 px-4 py-3">
                <dt class="text-slate-500 dark:text-slate-400">Effektiver Jahreszins laut Vertrag</dt>
                <dd class="font-medium text-slate-900 dark:text-white">{{ number_format((float) $loan->effective_rate, 2, ',', '.') }} %</dd>
            </div>
        @endif
        @if ($loan->financed_costs !== null)
            <div class="flex items-center justify-between gap-4 px-4 py-3">
                <dt class="text-slate-500 dark:text-slate-400">Mitfinanzierte Kosten</dt>
                <dd class="font-medium text-slate-900 dark:text-white">{{ $money($loan->financed_costs) }}</dd>
            </div>
        @endif
        @if ($loan->contract_total !== null)
            @php $difference = $planTotals['total'] - (float) $loan->contract_total; @endphp
            <div class="px-4 py-3">
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-slate-500 dark:text-slate-400">Gesamtbetrag laut Vertrag</dt>
                    <dd class="font-medium text-slate-900 dark:text-white">{{ $money($loan->contract_total) }}</dd>
                </div>
                @if ($planTotals['count'] > 0)
                    <p class="mt-1 text-xs {{ abs($difference) > 1 ? 'text-amber-600 dark:text-amber-400' : 'text-slate-500 dark:text-slate-400' }}">
                        @if (abs($difference) > 1)
                            Der berechnete Plan liegt bei {{ $money($planTotals['total']) }} ({{ $difference > 0 ? '+' : '−' }}{{ $money(abs($difference)) }}). Prüfe Zinssatz, Monatsrate und Anzahl der Raten.
                        @else
                            Passt zum berechneten Tilgungsplan.
                        @endif
                    </p>
                @endif
            </div>
        @endif
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
