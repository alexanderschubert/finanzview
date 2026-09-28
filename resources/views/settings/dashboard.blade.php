@extends('layouts.app')

@section('title', 'Dashboard anpassen – FinanzView')
@section('eyebrow', 'Einstellungen')
@section('page_title', 'Dashboard')

@php
    $icons = [
        'summary' => 'wallet',
        'income' => 'trending-up',
        'expenses' => 'trending-down',
        'savings_rate' => 'percent',
        'monthly_balance' => 'arrows',
        'yearly' => 'calendar',
        'income_expense_chart' => 'chart',
        'wealth_chart' => 'trending-up',
        'budgets' => 'target',
        'credit_cards' => 'card',
        'loans' => 'banknote',
        'accounts' => 'landmark',
        'categories' => 'tag',
        'recent_transactions' => 'arrows',
        'quick_actions' => 'plus',
    ];

    $enabled = old(
        'widgets',
        collect($selectedWidgets)->filter()->keys()->all()
    );

    $displayMode = old('display_mode', $settings->display_mode ?? 'standard');
@endphp

@section('content')

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-back-link :href="route('settings.index')" label="Einstellungen" />

    <x-page-header title="Dashboard" subtitle="Wähle aus, was auf deiner Übersicht erscheint, und in welcher Reihenfolge." />

    <x-flash />

    @if ($errors->any())
        <div class="flex gap-3 rounded-2xl bg-red-50 dark:bg-red-500/10 p-4 text-sm text-red-700 dark:text-red-300" role="alert">
            <x-icon name="alert" class="w-5 h-5" />
            <p>{{ $errors->first() }}</p>
        </div>
    @endif

    <form method="POST" action="{{ route('settings.dashboard.update') }}" class="space-y-6" id="dashboard-settings-form">
        @csrf
        @method('PUT')

        {{-- DARSTELLUNG --}}

        <section>
            <h3 class="px-1 pb-2 text-[13px] font-semibold text-slate-500 dark:text-slate-400">Darstellung</h3>

            <div class="fv-card p-4">
                <div class="grid grid-cols-2 gap-1 rounded-xl bg-slate-100 dark:bg-white/5 p-1" role="radiogroup" aria-label="Darstellung">
                    @foreach (['standard' => 'Standard', 'compact' => 'Kompakt'] as $value => $label)
                        <label class="cursor-pointer rounded-lg py-2 text-center text-sm font-medium text-slate-600 dark:text-slate-300 transition has-[:checked]:bg-white has-[:checked]:text-slate-900 has-[:checked]:shadow-sm dark:has-[:checked]:bg-slate-700 dark:has-[:checked]:text-white has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-emerald-500">
                            <input type="radio" name="display_mode" value="{{ $value }}" class="sr-only" @checked($displayMode === $value)>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                <p class="mt-2 px-1 text-[13px] text-slate-500 dark:text-slate-400">„Kompakt“ zeigt kleinere Abstände und Zahlen – praktisch auf kleinen Bildschirmen.</p>
            </div>
        </section>


        {{-- WIDGETS --}}

        <section>
            <h3 class="px-1 pb-2 text-[13px] font-semibold text-slate-500 dark:text-slate-400">Widgets</h3>

            <input type="hidden" name="widget_order" id="widget-order" value="{{ implode(',', array_keys($widgets)) }}">

            <ul id="widget-list" class="fv-card overflow-hidden divide-y divide-slate-100 dark:divide-white/5">
                @foreach ($widgets as $key => $widget)
                    <li class="flex items-center gap-3 px-3 py-2.5 bg-white dark:bg-slate-900" data-widget="{{ $key }}" draggable="true">
                        <span class="hidden sm:flex w-6 cursor-grab active:cursor-grabbing justify-center text-slate-300 dark:text-slate-600" aria-hidden="true" title="Ziehen zum Sortieren">
                            <svg viewBox="0 0 24 24" class="w-4 h-4" fill="currentColor"><circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/></svg>
                        </span>

                        <span class="w-8 h-8 rounded-lg bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-slate-300 flex items-center justify-center shrink-0">
                            <x-icon :name="$icons[$key] ?? 'layout'" class="w-[18px] h-[18px]" />
                        </span>

                        <span class="flex-1 min-w-0">
                            <span class="block font-medium text-slate-900 dark:text-white truncate">{{ $widget['label'] }}</span>
                            @if (! empty($widget['description']))
                                <span class="block text-[13px] text-slate-500 dark:text-slate-400 truncate">{{ $widget['description'] }}</span>
                            @endif
                        </span>

                        {{-- Sortieren per Knopf (funktioniert auch auf dem iPhone) --}}
                        <span class="flex flex-col">
                            <button type="button" data-move="up" class="w-7 h-6 rounded flex items-center justify-center text-slate-400 hover:text-slate-900 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-white/10" aria-label="{{ $widget['label'] }} nach oben">
                                <x-icon name="chevron-right" class="w-4 h-4 -rotate-90" />
                            </button>
                            <button type="button" data-move="down" class="w-7 h-6 rounded flex items-center justify-center text-slate-400 hover:text-slate-900 hover:bg-slate-100 dark:hover:text-white dark:hover:bg-white/10" aria-label="{{ $widget['label'] }} nach unten">
                                <x-icon name="chevron-right" class="w-4 h-4 rotate-90" />
                            </button>
                        </span>

                        <label class="cursor-pointer">
                            <span class="sr-only">{{ $widget['label'] }} anzeigen</span>
                            <input type="checkbox" name="widgets[]" value="{{ $key }}" class="sr-only peer" @checked(in_array($key, $enabled, true))>
                            <span aria-hidden="true" class="relative inline-flex h-[31px] w-[51px] shrink-0 rounded-full bg-slate-200 dark:bg-slate-700 transition peer-checked:bg-emerald-500 peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-500 after:absolute after:top-[2px] after:left-[2px] after:h-[27px] after:w-[27px] after:rounded-full after:bg-white after:shadow after:transition peer-checked:after:translate-x-[20px]"></span>
                        </label>
                    </li>
                @endforeach
            </ul>
        </section>

        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
            <a href="{{ route('dashboard') }}" class="fv-btn fv-btn-secondary">Zum Dashboard</a>
            <button type="submit" class="fv-btn fv-btn-primary">Speichern</button>
        </div>
    </form>

</div>

<script>
    (function () {
        const list = document.getElementById('widget-list');
        const order = document.getElementById('widget-order');
        let dragged = null;

        const items = () => Array.from(list.querySelectorAll('[data-widget]'));
        const sync = () => order.value = items().map(item => item.dataset.widget).join(',');

        // Hoch/Runter-Knöpfe
        list.addEventListener('click', event => {
            const button = event.target.closest('[data-move]');
            if (!button) return;

            const item = button.closest('[data-widget]');

            if (button.dataset.move === 'up' && item.previousElementSibling) {
                list.insertBefore(item, item.previousElementSibling);
            } else if (button.dataset.move === 'down' && item.nextElementSibling) {
                list.insertBefore(item.nextElementSibling, item);
            }

            button.focus();
            sync();
        });

        // Ziehen und Ablegen (Desktop)
        items().forEach(item => {
            item.addEventListener('dragstart', event => {
                dragged = item;
                item.classList.add('opacity-50');
                event.dataTransfer.effectAllowed = 'move';
            });

            item.addEventListener('dragend', () => {
                item.classList.remove('opacity-50');
                dragged = null;
                sync();
            });

            item.addEventListener('dragover', event => {
                event.preventDefault();
                if (!dragged || dragged === item) return;

                const rect = item.getBoundingClientRect();
                const before = event.clientY < rect.top + rect.height / 2;
                list.insertBefore(dragged, before ? item : item.nextSibling);
            });
        });

        sync();
    })();
</script>

@endsection
