@extends('layouts.app')

@section('title', 'Kreditkarten')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">
                Kreditkarten
            </h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Übersicht deiner Kreditkarten und deren Auslastung.
            </p>
        </div>

        <a
            href="#"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-slate-700 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white"
        >
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 5v14M5 12h14"/>
            </svg>
            Kreditkarte hinzufügen
        </a>
    </div>


    {{-- Empty state --}}
    @if($creditCards->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center dark:border-slate-700 dark:bg-slate-900">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300">
                <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                    <rect x="3" y="5" width="18" height="14" rx="2"/>
                    <path d="M3 10h18"/>
                    <path d="M7 15h4"/>
                </svg>
            </div>

            <h2 class="mt-4 text-lg font-semibold text-slate-900 dark:text-slate-100">
                Noch keine Kreditkarten
            </h2>

            <p class="mx-auto mt-2 max-w-md text-sm text-slate-500 dark:text-slate-400">
                Lege deine erste Kreditkarte an, um Kreditlimit, Saldo,
                Auslastung und Abrechnungen in FinanzView zu verwalten.
            </p>
        </div>
    @else

        {{-- Summary --}}
        @php
            $totalLimit = $creditCards->sum(fn ($card) => (float) ($card->credit_limit ?? 0));
            $totalBalance = $creditCards->sum(fn ($card) => (float) ($card->current_balance ?? 0));
            $totalAvailable = max(0, $totalLimit - $totalBalance);
            $overallUtilization = $totalLimit > 0
                ? min(100, max(0, ($totalBalance / $totalLimit) * 100))
                : 0;
            $activeCards = $creditCards->where('is_active', true)->count();
        @endphp

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

            {{-- Cards --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <p class="text-sm text-slate-500 dark:text-slate-400">Kreditkarten</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-slate-100">
                    {{ $activeCards }}
                </p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    aktiv
                </p>
            </div>

            {{-- Limit --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <p class="text-sm text-slate-500 dark:text-slate-400">Kreditlimit</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-slate-100">
                    {{ number_format($totalLimit, 2, ',', '.') }} €
                </p>
            </div>

            {{-- Balance --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <p class="text-sm text-slate-500 dark:text-slate-400">Aktueller Saldo</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-slate-100">
                    {{ number_format($totalBalance, 2, ',', '.') }} €
                </p>
            </div>

            {{-- Available --}}
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <p class="text-sm text-slate-500 dark:text-slate-400">Verfügbar</p>
                <p class="mt-2 text-2xl font-semibold text-slate-900 dark:text-slate-100">
                    {{ number_format($totalAvailable, 2, ',', '.') }} €
                </p>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    {{ number_format($overallUtilization, 1, ',', '.') }} % Auslastung
                </p>
            </div>
        </div>


        {{-- Credit card grid --}}
        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">

            @foreach($creditCards as $creditCard)

                @php
                    $limit = (float) ($creditCard->credit_limit ?? 0);
                    $balance = (float) ($creditCard->current_balance ?? 0);
                    $available = max(0, $limit - $balance);
                    $utilization = $limit > 0
                        ? min(100, max(0, ($balance / $limit) * 100))
                        : 0;

                    $cardColor = $creditCard->color ?: '#334155';

                    $utilizationLabel =
                        $utilization >= 90 ? 'Sehr hoch' :
                        ($utilization >= 70 ? 'Hoch' :
                        ($utilization >= 40 ? 'Mittel' : 'Niedrig'));

                    $utilizationClass =
                        $utilization >= 90
                            ? 'text-red-600 dark:text-red-400'
                            : ($utilization >= 70
                                ? 'text-amber-600 dark:text-amber-400'
                                : 'text-emerald-600 dark:text-emerald-400');
                @endphp

                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">

                    {{-- Card header --}}
                    <div class="p-5">

                        <div class="flex items-start justify-between gap-4">

                            <div class="flex min-w-0 items-center gap-3">

                                <div
                                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-white shadow-sm"
                                    style="background-color: {{ $cardColor }}"
                                >
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                                        <rect x="3" y="5" width="18" height="14" rx="2"/>
                                        <path d="M3 10h18"/>
                                        <path d="M7 15h4"/>
                                    </svg>
                                </div>

                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <h2 class="truncate font-semibold text-slate-900 dark:text-slate-100">
                                            {{ $creditCard->name }}
                                        </h2>

                                        @if(!$creditCard->is_active)
                                            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                                                Inaktiv
                                            </span>
                                        @endif
                                    </div>

                                    <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
                                        @if($creditCard->issuer)
                                            {{ $creditCard->issuer }}
                                        @endif

                                        @if($creditCard->last_four)
                                            @if($creditCard->issuer) · @endif
                                            •••• {{ $creditCard->last_four }}
                                        @endif
                                    </p>
                                </div>
                            </div>

                            @if($creditCard->provider)
                                <span class="shrink-0 rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                    {{ $creditCard->provider->name }}
                                </span>
                            @endif

                        </div>


                        {{-- Main values --}}
                        <div class="mt-6 grid grid-cols-3 gap-3">

                            <div>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    Saldo
                                </p>
                                <p class="mt-1 font-semibold text-slate-900 dark:text-slate-100">
                                    {{ number_format($balance, 2, ',', '.') }} €
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    Limit
                                </p>
                                <p class="mt-1 font-semibold text-slate-900 dark:text-slate-100">
                                    {{ $limit > 0 ? number_format($limit, 2, ',', '.') . ' €' : '–' }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    Verfügbar
                                </p>
                                <p class="mt-1 font-semibold text-slate-900 dark:text-slate-100">
                                    {{ $limit > 0 ? number_format($available, 2, ',', '.') . ' €' : '–' }}
                                </p>
                            </div>

                        </div>


                        {{-- Utilization --}}
                        <div class="mt-5">

                            <div class="mb-2 flex items-center justify-between text-xs">
                                <span class="text-slate-500 dark:text-slate-400">
                                    Kredit-Auslastung
                                </span>

                                <span class="font-medium {{ $utilizationClass }}">
                                    {{ number_format($utilization, 1, ',', '.') }} %
                                    · {{ $utilizationLabel }}
                                </span>
                            </div>

                            <div class="h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                                <div
                                    class="h-full rounded-full transition-all
                                        {{ $utilization >= 90
                                            ? 'bg-red-500'
                                            : ($utilization >= 70
                                                ? 'bg-amber-500'
                                                : 'bg-emerald-500') }}"
                                    style="width: {{ $utilization }}%"
                                ></div>
                            </div>

                        </div>


                        {{-- Dates --}}
                        <div class="mt-5 grid grid-cols-2 gap-3 border-t border-slate-100 pt-4 dark:border-slate-800">

                            <div>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    Abrechnungstag
                                </p>
                                <p class="mt-1 text-sm font-medium text-slate-700 dark:text-slate-300">
                                    {{ $creditCard->billing_day ? 'Tag ' . $creditCard->billing_day : 'Nicht hinterlegt' }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-slate-500 dark:text-slate-400">
                                    Zahlungsziel
                                </p>
                                <p class="mt-1 text-sm font-medium text-slate-700 dark:text-slate-300">
                                    {{ $creditCard->payment_due_day ? 'Tag ' . $creditCard->payment_due_day : 'Nicht hinterlegt' }}
                                </p>
                            </div>

                        </div>

                    </div>


                    {{-- Footer --}}
                    <div class="flex items-center justify-between border-t border-slate-100 bg-slate-50 px-5 py-3 dark:border-slate-800 dark:bg-slate-950/50">

                        @if($creditCard->account)
                            <span class="truncate text-xs text-slate-500 dark:text-slate-400">
                                Konto: {{ $creditCard->account->name }}
                            </span>
                        @else
                            <span class="text-xs text-slate-400 dark:text-slate-500">
                                Kein Konto verknüpft
                            </span>
                        @endif

                        <a
                            href="#"
                            class="text-sm font-medium text-slate-700 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white"
                        >
                            Details
                            <span aria-hidden="true">→</span>
                        </a>

                    </div>

                </div>

            @endforeach

        </div>

    @endif

</div>
@endsection
