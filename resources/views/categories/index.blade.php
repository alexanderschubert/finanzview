@extends('layouts.app')

@section('title', 'Kategorien – FinanzView')
@section('eyebrow', 'Finanzverwaltung')
@section('page_title', 'Kategorien')

@php
    $groups = [
        'Ausgaben' => $categories->where('type', 'expense'),
        'Einnahmen' => $categories->where('type', 'income'),
        'Einnahmen und Ausgaben' => $categories->where('type', 'both'),
    ];
@endphp

@section('content')

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-page-header title="Kategorien" subtitle="Ordne deine Einnahmen und Ausgaben.">
        <a href="{{ route('categories.create') }}" class="fv-btn fv-btn-primary text-sm py-2.5">
            <x-icon name="plus" class="w-4 h-4" />
            Neue Kategorie
        </a>
    </x-page-header>

    <x-flash />

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
                    <span class="font-normal text-slate-400 dark:text-slate-500">· {{ $groupCategories->count() }}</span>
                </h3>

                <ul class="fv-card overflow-hidden divide-y divide-slate-100 dark:divide-white/5">
                    @foreach ($groupCategories as $category)
                        <li>
                            <a href="{{ route('categories.edit', $category) }}" class="flex items-center gap-3 px-4 py-3 hover:bg-slate-50 dark:hover:bg-white/5 transition {{ $category->is_active ? '' : 'opacity-60' }}">
                                <x-emoji-tile :emoji="$category->icon" fallback="tag" :color="$category->color" />

                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-slate-900 dark:text-white truncate">{{ $category->name }}</p>

                                    @if ($category->description || ! $category->is_active)
                                        <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                                            {{ $category->is_active ? $category->description : 'Deaktiviert' }}
                                        </p>
                                    @endif
                                </div>

                                <x-icon name="chevron-right" class="w-4 h-4 text-slate-300 dark:text-slate-600" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach

    @endif

</div>

@endsection
