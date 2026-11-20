@extends('layouts.app')

@section('title', 'Kategorien – FinanzView')
@section('eyebrow', 'Einstellungen')
@section('page_title', 'Kategorien')

@php
    /*
     * Hauptkategorien nach Art gruppiert, Unterkategorien darunter.
     * Eine Unterkategorie, deren Hauptkategorie fehlt, gilt als Hauptkategorie.
     */
    $ids = $categories->pluck('id')->all();
    $roots = $categories->filter(fn ($c) => $c->parent_id === null || ! in_array($c->parent_id, $ids, true));
    $children = $categories->filter(fn ($c) => $c->parent_id !== null && in_array($c->parent_id, $ids, true))->groupBy('parent_id');

    $groups = [
        'Ausgaben' => $roots->where('type', 'expense'),
        'Einnahmen' => $roots->where('type', 'income'),
        'Einnahmen und Ausgaben' => $roots->where('type', 'both'),
    ];

    $fmt = fn ($value) => number_format((float) $value, 2, ',', '.') . ' €';

    // Summe einer Hauptkategorie inklusive ihrer Unterkategorien.
    $statsOf = function ($category) use ($stats, $children) {
        $ids = collect([$category->id])->merge($children->get($category->id, collect())->pluck('id'));

        return [
            'total' => (int) $ids->sum(fn ($id) => $stats[$id]->total ?? 0),
            'month' => (float) $ids->sum(fn ($id) => $stats[$id]->month_sum ?? 0),
        ];
    };

    $unused = $categories->filter(fn ($c) => ! isset($stats[$c->id]))->count();
@endphp

@section('content')

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-page-header title="Kategorien" subtitle="Ordne deine Einnahmen und Ausgaben.">
        <a href="{{ route('tags.index') }}" class="fv-btn fv-btn-secondary text-sm py-2.5">
            <span class="text-base leading-none" aria-hidden="true">#</span>
            Tags
        </a>
        <a href="{{ route('category-rules.index') }}" class="fv-btn fv-btn-secondary text-sm py-2.5">
            <x-icon name="repeat" class="w-4 h-4" />
            Regeln
        </a>
        <a href="{{ route('categories.create') }}" class="fv-btn fv-btn-primary text-sm py-2.5">
            <x-icon name="plus" class="w-4 h-4" />
            Neue Kategorie
        </a>
    </x-page-header>

    <x-flash />

    @if ($categories->isNotEmpty())
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="relative flex-1">
                <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                <input type="search" id="category-search" placeholder="Kategorie suchen …" aria-label="Kategorien durchsuchen" class="fv-input !pl-10" autocomplete="off">
            </div>

            <p class="text-xs text-slate-500 dark:text-slate-400">
                {{ $categories->count() }} Kategorien @if ($unused > 0) · {{ $unused }} ohne Buchungen @endif
            </p>
        </div>
    @endif

    @if ($categories->isEmpty())

        <div class="fv-card">
            <x-empty-state icon="tag" title="Noch keine Kategorien" :href="route('categories.create')" action="Kategorie erstellen">
                Mit Kategorien siehst du, wofür du dein Geld ausgibst.
            </x-empty-state>
        </div>

    @else

        @foreach ($groups as $group => $groupCategories)
            @continue($groupCategories->isEmpty())

            <section>
                <h3 class="px-1 pb-2 text-[13px] font-semibold text-slate-500 dark:text-slate-400">
                    {{ $group }}
                    <span class="font-normal text-slate-400 dark:text-slate-500">· {{ $groupCategories->count() + $groupCategories->sum(fn ($c) => $children->get($c->id, collect())->count()) }}</span>
                </h3>

                <ul class="fv-card overflow-hidden divide-y divide-slate-100 dark:divide-white/5">
                    @foreach ($groupCategories as $category)
                        <li data-category-item data-search="{{ mb_strtolower($category->name . ' ' . $children->get($category->id, collect())->pluck('name')->implode(' ')) }}">
                            <div class="flex items-center {{ $category->is_active ? '' : 'opacity-60' }}">
                                <a href="{{ route('categories.edit', $category) }}" class="flex-1 min-w-0 flex items-center gap-3 px-4 py-3 hover:bg-slate-50 dark:hover:bg-white/5 transition">
                                    <x-emoji-tile :emoji="$category->icon" fallback="tag" :color="$category->color" />

                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-slate-900 dark:text-white truncate">{{ $category->name }}</p>

                                        @if ($category->description || ! $category->is_active)
                                            <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                                                {{ $category->is_active ? $category->description : 'Deaktiviert' }}
                                            </p>
                                        @endif
                                    </div>

                                    @php($rootStats = $statsOf($category))
                                    <div class="hidden shrink-0 text-right sm:block">
                                        <p class="text-sm font-semibold tabular-nums {{ $rootStats['month'] == 0 ? 'text-slate-400 dark:text-slate-500' : 'text-slate-900 dark:text-white' }}">{{ $fmt($rootStats['month']) }}</p>
                                        <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ $rootStats['total'] }} {{ $rootStats['total'] === 1 ? 'Buchung' : 'Buchungen' }} · {{ $monthLabel }}</p>
                                    </div>

                                    <x-icon name="chevron-right" class="w-4 h-4 text-slate-300 dark:text-slate-600" />
                                </a>

                                @if ($category->is_active)
                                    <a href="{{ route('categories.create', ['parent' => $category->id]) }}"
                                        class="mr-2 w-9 h-9 shrink-0 rounded-lg flex items-center justify-center text-slate-400 hover:text-emerald-600 hover:bg-slate-100 dark:hover:bg-white/10 transition"
                                        title="Unterkategorie hinzufügen" aria-label="Unterkategorie zu {{ $category->name }} hinzufügen">
                                        <x-icon name="plus" class="w-4 h-4" />
                                    </a>
                                @endif
                            </div>

                            {{-- Unterkategorien --}}
                            @foreach ($children->get($category->id, collect()) as $child)
                                <a href="{{ route('categories.edit', $child) }}" class="flex items-center gap-3 pl-10 pr-4 py-2.5 border-t border-slate-100 dark:border-white/5 hover:bg-slate-50 dark:hover:bg-white/5 transition {{ $child->is_active ? '' : 'opacity-60' }}">
                                    <span class="w-4 text-slate-300 dark:text-slate-600 select-none" aria-hidden="true">↳</span>
                                    <x-emoji-tile :emoji="$child->icon" fallback="tag" :color="$child->color" size="sm" />

                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ $child->name }}</p>

                                        @if ($child->description || ! $child->is_active)
                                            <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                                                {{ $child->is_active ? $child->description : 'Deaktiviert' }}
                                            </p>
                                        @endif
                                    </div>

                                    <p class="hidden shrink-0 text-right text-xs tabular-nums text-slate-500 dark:text-slate-400 sm:block">
                                        {{ $fmt($stats[$child->id]->month_sum ?? 0) }} · {{ (int) ($stats[$child->id]->total ?? 0) }}
                                    </p>

                                    <x-icon name="chevron-right" class="w-4 h-4 text-slate-300 dark:text-slate-600" />
                                </a>
                            @endforeach
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach

    @endif

</div>

<script>
    // Filtert die Liste beim Tippen; Gruppen ohne Treffer werden ausgeblendet.
    (function () {
        const input = document.getElementById('category-search');
        if (!input) return;

        input.addEventListener('input', () => {
            const q = input.value.trim().toLowerCase();

            document.querySelectorAll('[data-category-item]').forEach(item => {
                item.hidden = q !== '' && !item.dataset.search.includes(q);
            });

            document.querySelectorAll('section').forEach(section => {
                const items = section.querySelectorAll('[data-category-item]');
                if (items.length) section.hidden = [...items].every(i => i.hidden);
            });
        });
    })();
</script>

@endsection
