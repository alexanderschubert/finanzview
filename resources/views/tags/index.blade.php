@extends('layouts.app')

@section('title', 'Tags – FinanzView')
@section('eyebrow', 'Kategorien')
@section('page_title', 'Tags')

@php
    $money = fn ($amount) => number_format((float) $amount, 2, ',', '.') . ' €';
@endphp

@section('content')

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-back-link :href="route('categories.index')" label="Kategorien" />

    <x-page-header title="Tags" subtitle="Schlagwörter quer zu Kategorien – z. B. für einen Urlaub, ein Projekt oder Geschäftliches." />

    <x-flash />

    @if ($tags->isEmpty())
        <div class="fv-card">
            <x-empty-state icon="tag" title="Noch keine Tags" :href="route('transactions.create')" action="Buchung erfassen">
                Gib bei einer Buchung im Feld „Tags“ z. B. „Urlaub 2026“ ein – der Tag erscheint dann hier mit allen Summen.
            </x-empty-state>
        </div>
    @else
        <ul class="fv-card overflow-hidden divide-y divide-slate-100 dark:divide-white/5">
            @foreach ($tags as $tag)
                <li class="flex items-center gap-2 pr-2">
                    <a href="{{ route('transactions.index', ['tag' => $tag->id]) }}" class="flex-1 min-w-0 flex items-center gap-3 px-4 py-3 hover:bg-slate-50 dark:hover:bg-white/5 transition">
                        <span class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 text-base font-semibold" style="background-color: {{ $tag->displayColor() }}26; color: {{ $tag->displayColor() }}" aria-hidden="true">#</span>

                        <div class="flex-1 min-w-0">
                            <p class="font-medium text-slate-900 dark:text-white truncate">{{ $tag->name }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
                                {{ $tag->transactions_count === 1 ? '1 Buchung' : $tag->transactions_count . ' Buchungen' }}
                                @if ((float) $tag->income_total > 0)
                                    · <span class="text-emerald-600 dark:text-emerald-400">+{{ $money($tag->income_total) }}</span>
                                @endif
                            </p>
                        </div>

                        <p class="shrink-0 font-semibold tabular-nums text-slate-900 dark:text-white">
                            {{ (float) $tag->expense_total > 0 ? '−' . $money($tag->expense_total) : '' }}
                        </p>
                    </a>

                    <a href="{{ route('tags.edit', $tag) }}" class="w-9 h-9 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-white/10 transition"
                        title="Bearbeiten" aria-label="{{ $tag->name }} bearbeiten">
                        <x-icon name="pencil" class="w-4 h-4" />
                    </a>
                </li>
            @endforeach
        </ul>

        <p class="px-1 text-[13px] text-slate-500 dark:text-slate-400">
            Tippe auf einen Tag, um alle zugehörigen Buchungen zu sehen. Beträge rechts sind die Ausgaben insgesamt.
        </p>
    @endif

</div>

@endsection
