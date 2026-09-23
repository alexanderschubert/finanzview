@extends('layouts.app')

@section('title', 'Budgets – FinanzView')
@section('eyebrow', 'Finanzplanung')
@section('page_title', 'Budgets')

@php
    $applicable = $budgets->filter(fn ($budget) => $budget->is_active && $budget->calculated_applicable);

    $plannedTotal = $applicable->sum(fn ($budget) => (float) $budget->amount);
    $spentTotal = $applicable->sum(fn ($budget) => (float) $budget->calculated_spent);
    $remainingTotal = $plannedTotal - $spentTotal;
    $totalPercentage = $plannedTotal > 0 ? ($spentTotal / $plannedTotal) * 100 : 0;

    $periodLabels = [
        'monthly' => 'Monatlich',
        'yearly' => 'Jährlich',
    ];
@endphp

@section('content')

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-page-header title="Budgets" subtitle="Plane deine Ausgaben und behalte deine Ziele im Blick.">
        <x-month-switcher route="budgets.index" :month="$selectedMonth" />

        <a href="{{ route('budgets.create') }}" class="fv-btn fv-btn-primary text-sm py-2.5">
            <x-icon name="plus" class="w-4 h-4" />
            Neues Budget
        </a>
    </x-page-header>

    <x-flash />

    @if ($budgets->isEmpty())

        <div class="fv-card">
            <x-empty-state icon="target" title="Noch keine Budgets" :href="route('budgets.create')" action="Budget erstellen">
                Lege Budgets für Kategorien wie Lebensmittel oder Freizeit an und sieh, wie viel noch übrig ist.
            </x-empty-state>
        </div>

    @else

        {{-- ÜBERSICHT --}}

        @if ($applicable->isNotEmpty())
            <div class="fv-card p-5 sm:p-6">
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Geplant</p>
                        <p class="mt-1 text-lg sm:text-xl font-semibold tabular-nums text-slate-900 dark:text-white">
                            {{ number_format($plannedTotal, 2, ',', '.') }} €
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500 dark:text-slate-400">Ausgegeben</p>
                        <p class="mt-1 text-lg sm:text-xl font-semibold tabular-nums text-slate-900 dark:text-white">
                            {{ number_format($spentTotal, 2, ',', '.') }} €
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ $remainingTotal < 0 ? 'Überschritten' : 'Verfügbar' }}</p>
                        <p class="mt-1 text-lg sm:text-xl font-semibold tabular-nums {{ $remainingTotal < 0 ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                            {{ number_format(abs($remainingTotal), 2, ',', '.') }} €
                        </p>
                    </div>
                </div>

                <x-progress
                    :value="$totalPercentage"
                    :tone="$totalPercentage > 100 ? 'negative' : ($totalPercentage >= 80 ? 'warning' : 'positive')"
                    class="mt-4"
                />
            </div>
        @endif


        {{-- BUDGETS --}}

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach ($budgets as $budget)
                @php
                    $isApplicable = $budget->is_active && (bool) $budget->calculated_applicable;
                    $percentage = (float) ($budget->calculated_percentage ?? 0);
                    $remaining = (float) ($budget->calculated_remaining ?? 0);
                    $tone = $budget->calculated_exceeded ? 'negative' : ($percentage >= 80 ? 'warning' : 'positive');
                @endphp

                <article class="fv-card p-5 flex flex-col {{ $isApplicable ? '' : 'opacity-70' }}">

                    <a href="{{ route('budgets.show', ['budget' => $budget, 'month' => $selectedMonth]) }}" class="flex items-center gap-3">
                        <x-emoji-tile :emoji="$budget->icon" fallback="target" :color="$budget->color" size="lg" />

                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-slate-900 dark:text-white truncate">{{ $budget->name }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                                {{ $periodLabels[$budget->period] ?? 'Benutzerdefiniert' }}
                                @if ($budget->calculated_start_date && $budget->calculated_end_date)
                                    · {{ $budget->calculated_start_date->format('d.m.') }}–{{ $budget->calculated_end_date->format('d.m.Y') }}
                                @endif
                            </p>
                        </div>

                        @if (! $budget->is_active)
                            <span class="rounded-full bg-slate-100 dark:bg-white/10 px-2 py-0.5 text-[11px] font-medium text-slate-500 dark:text-slate-400">Pausiert</span>
                        @elseif (! $isApplicable)
                            <span class="rounded-full bg-slate-100 dark:bg-white/10 px-2 py-0.5 text-[11px] font-medium text-slate-500 dark:text-slate-400">Nicht aktiv</span>
                        @elseif ($budget->calculated_exceeded)
                            <span class="rounded-full bg-red-100 dark:bg-red-500/15 px-2 py-0.5 text-[11px] font-medium text-red-700 dark:text-red-300">Überschritten</span>
                        @elseif ($percentage >= 80)
                            <span class="rounded-full bg-amber-100 dark:bg-amber-500/15 px-2 py-0.5 text-[11px] font-medium text-amber-700 dark:text-amber-300">Fast erreicht</span>
                        @endif
                    </a>

                    @if ($isApplicable)
                        <div class="mt-5 flex items-baseline justify-between gap-2">
                            <p class="text-2xl font-semibold tabular-nums {{ $budget->calculated_exceeded ? 'text-red-600 dark:text-red-400' : 'text-slate-900 dark:text-white' }}">
                                {{ number_format((float) $budget->calculated_spent, 2, ',', '.') }} €
                            </p>
                            <p class="text-sm text-slate-500 dark:text-slate-400 tabular-nums">
                                von {{ number_format((float) $budget->amount, 2, ',', '.') }} €
                            </p>
                        </div>

                        <x-progress :value="$percentage" :tone="$tone" class="mt-2" />

                        <p class="mt-2 text-xs tabular-nums {{ $remaining < 0 ? 'font-medium text-red-600 dark:text-red-400' : 'text-slate-500 dark:text-slate-400' }}">
                            @if ($remaining >= 0)
                                Noch {{ number_format($remaining, 2, ',', '.') }} € verfügbar · {{ number_format($percentage, 0, ',', '.') }} %
                            @else
                                {{ number_format(abs($remaining), 2, ',', '.') }} € über Budget
                            @endif
                        </p>
                    @else
                        <p class="mt-5 text-sm text-slate-500 dark:text-slate-400">
                            {{ $budget->is_active ? 'Gilt nicht für ' . $referenceMonth->translatedFormat('F Y') . '.' : 'Dieses Budget ist pausiert.' }}
                            Geplant: {{ number_format((float) $budget->amount, 2, ',', '.') }} €
                        </p>
                    @endif

                    @if ($budget->categories->isNotEmpty())
                        <div class="mt-4 flex flex-wrap gap-1.5">
                            @foreach ($budget->categories->take(4) as $category)
                                <span class="rounded-full bg-slate-100 dark:bg-white/5 px-2.5 py-1 text-xs text-slate-600 dark:text-slate-300">
                                    {{ $category->icon }} {{ $category->name }}
                                </span>
                            @endforeach
                            @if ($budget->categories->count() > 4)
                                <span class="rounded-full bg-slate-100 dark:bg-white/5 px-2.5 py-1 text-xs text-slate-500">+{{ $budget->categories->count() - 4 }}</span>
                            @endif
                        </div>
                    @endif

                    <div class="mt-auto pt-3 flex items-center gap-1 border-t border-slate-100 dark:border-white/5">
                        <a href="{{ route('budgets.show', ['budget' => $budget, 'month' => $selectedMonth]) }}" class="fv-link text-sm px-2 py-1.5 -ml-2">Details</a>

                        <span class="flex-1"></span>

                        <a href="{{ route('budgets.edit', $budget) }}" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-white/10 transition" aria-label="Budget bearbeiten" title="Bearbeiten">
                            <x-icon name="pencil" class="w-4 h-4" />
                        </a>

                        <form method="POST" action="{{ route('budgets.destroy', $budget) }}" onsubmit="return confirm('Möchtest du das Budget „{{ addslashes($budget->name) }}“ wirklich löschen?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-500/10 transition" aria-label="Budget löschen" title="Löschen">
                                <x-icon name="trash" class="w-4 h-4" />
                            </button>
                        </form>
                    </div>

                </article>
            @endforeach
        </div>

    @endif

</div>

@endsection
