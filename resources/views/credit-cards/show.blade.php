@extends('layouts.app')

@section('title', $creditCard->name)

@section('content')
<div class="mx-auto max-w-6xl space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">
                Kreditkarten
            </p>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
                {{ $creditCard->name }}
            </h1>
        </div>

        <div class="flex gap-3">
            <a
                href="{{ route('credit-cards.index') }}"
                class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
            >
                Zur Übersicht
            </a>

            <a
                href="{{ route('credit-cards.edit', $creditCard) }}"
                class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700"
            >
                Bearbeiten
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    @php
        $limit = (float) $creditCard->credit_limit;
        $balance = (float) $creditCard->current_balance;
        $available = $limit > 0 ? max(0, $limit - $balance) : null;
        $utilization = $limit > 0 ? min(100, max(0, ($balance / $limit) * 100)) : null;
    @endphp

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="text-sm text-slate-500 dark:text-slate-400">Aktueller Saldo</p>
            <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">
                {{ number_format($balance, 2, ',', '.') }} €
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="text-sm text-slate-500 dark:text-slate-400">Kreditlimit</p>
            <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">
                @if ($limit > 0)
                    {{ number_format($limit, 2, ',', '.') }} €
                @else
                    Kein Limit
                @endif
            </p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="text-sm text-slate-500 dark:text-slate-400">Verfügbar</p>
            <p class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">
                @if ($available !== null)
                    {{ number_format($available, 2, ',', '.') }} €
                @else
                    –
                @endif
            </p>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 lg:col-span-2">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                        Karteninformationen
                    </h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Details zu deiner Kreditkarte.
                    </p>
                </div>

                <span class="rounded-full px-3 py-1 text-xs font-semibold
                    {{ $creditCard->is_active
                        ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300'
                        : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' }}">
                    {{ $creditCard->is_active ? 'Aktiv' : 'Inaktiv' }}
                </span>
            </div>

            <dl class="mt-6 grid gap-5 sm:grid-cols-2">

                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">
                        Herausgeber
                    </dt>
                    <dd class="mt-1 text-sm font-medium text-slate-900 dark:text-white">
                        {{ $creditCard->issuer ?: '–' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">
                        Letzte 4 Ziffern
                    </dt>
                    <dd class="mt-1 font-mono text-sm font-medium text-slate-900 dark:text-white">
                        {{ $creditCard->last_four ? '•••• '.$creditCard->last_four : '–' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">
                        Anbieter
                    </dt>
                    <dd class="mt-1 text-sm font-medium text-slate-900 dark:text-white">
                        {{ $creditCard->provider?->name ?: '–' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">
                        Verknüpftes Konto
                    </dt>
                    <dd class="mt-1 text-sm font-medium text-slate-900 dark:text-white">
                        {{ $creditCard->account?->name ?: '–' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">
                        Abrechnungstag
                    </dt>
                    <dd class="mt-1 text-sm font-medium text-slate-900 dark:text-white">
                        {{ $creditCard->billing_day ? 'Tag '.$creditCard->billing_day : '–' }}
                    </dd>
                </div>

                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">
                        Fälligkeit
                    </dt>
                    <dd class="mt-1 text-sm font-medium text-slate-900 dark:text-white">
                        {{ $creditCard->payment_due_day ? 'Tag '.$creditCard->payment_due_day : '–' }}
                    </dd>
                </div>

            </dl>

            @if ($utilization !== null)
                <div class="mt-8">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium text-slate-700 dark:text-slate-300">
                            Kreditkartenauslastung
                        </span>
                        <span class="font-semibold text-slate-900 dark:text-white">
                            {{ number_format($utilization, 1, ',', '.') }} %
                        </span>
                    </div>

                    <div class="mt-2 h-3 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                        <div
                            class="h-full rounded-full
                                {{ $utilization >= 80
                                    ? 'bg-red-500'
                                    : ($utilization >= 50 ? 'bg-amber-500' : 'bg-emerald-500') }}"
                            style="width: {{ $utilization }}%"
                        ></div>
                    </div>
                </div>
            @endif
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                Abrechnungen
            </h2>

            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Abrechnungen werden hier angezeigt, sobald sie vorhanden sind.
            </p>

            @if ($creditCard->statements->isEmpty())
                <div class="mt-8 rounded-xl border border-dashed border-slate-300 p-5 text-center dark:border-slate-700">
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        Noch keine Abrechnungen vorhanden.
                    </p>
                </div>
            @else
                <div class="mt-5 space-y-3">
                    @foreach ($creditCard->statements->take(5) as $statement)
                        <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-800">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900 dark:text-white">
                                        {{ number_format((float) $statement->amount, 2, ',', '.') }} €
                                    </p>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        {{ $statement->period_start?->format('d.m.Y') }}
                                        –
                                        {{ $statement->period_end?->format('d.m.Y') }}
                                    </p>
                                </div>

                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                    {{ ucfirst($statement->status) }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>

</div>
@endsection
