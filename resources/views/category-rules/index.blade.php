@extends('layouts.app')

@section('title', 'Kategorie-Regeln – FinanzView')
@section('eyebrow', 'Kategorien')
@section('page_title', 'Regeln')

@section('content')

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-back-link :href="route('categories.index')" label="Kategorien" />

    <x-page-header title="Regeln" subtitle="Buchungen automatisch einer Kategorie zuordnen – bei neuen Buchungen und beim CSV-Import." />

    <x-flash />


    {{-- NEUE REGEL --}}

    <section>
        <h3 class="px-1 pb-2 text-[13px] font-semibold text-slate-500 dark:text-slate-400">Neue Regel</h3>

        @if ($categories->isEmpty())
            <div class="fv-card">
                <x-empty-state icon="tag" title="Noch keine Kategorien" :href="route('categories.create')" action="Kategorie erstellen">
                    Lege zuerst Kategorien an, denen Regeln Buchungen zuordnen können.
                </x-empty-state>
            </div>
        @else
            @include('category-rules._form', ['rule' => $prefill])
        @endif
    </section>


    {{-- VORHANDENE REGELN --}}

    @if ($rules->isNotEmpty())
        <section>
            <h3 class="px-1 pb-2 text-[13px] font-semibold text-slate-500 dark:text-slate-400">
                Deine Regeln
                <span class="font-normal text-slate-400 dark:text-slate-500">· {{ $rules->count() }}</span>
            </h3>

            <ul class="fv-card overflow-hidden divide-y divide-slate-100 dark:divide-white/5">
                @foreach ($rules as $rule)
                    <li>
                        <a href="{{ route('category-rules.edit', $rule) }}" class="flex items-center gap-3 px-4 py-3 hover:bg-slate-50 dark:hover:bg-white/5 transition {{ $rule->is_active && $rule->category?->is_active ? '' : 'opacity-60' }}">
                            <x-emoji-tile :emoji="$rule->category?->icon" fallback="tag" :color="$rule->category?->color" size="sm" />

                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-slate-900 dark:text-white truncate">
                                    „{{ $rule->pattern }}“
                                    <span class="font-normal text-slate-400">→</span>
                                    {{ $rule->category?->display_name ?? 'Kategorie gelöscht' }}
                                </p>
                                <p class="text-xs text-slate-500 dark:text-slate-400 truncate">
                                    {{ \App\Models\CategoryRule::FIELDS[$rule->match_field] ?? '' }}
                                    @unless ($rule->is_active) · pausiert @endunless
                                    @if ($rule->category && ! $rule->category->is_active) · Kategorie deaktiviert @endif
                                </p>
                            </div>

                            <x-icon name="chevron-right" class="w-4 h-4 text-slate-300 dark:text-slate-600" />
                        </a>
                    </li>
                @endforeach
            </ul>

            <p class="mt-2 px-1 text-[13px] text-slate-500 dark:text-slate-400">
                Groß-/Kleinschreibung spielt keine Rolle. Passen mehrere Regeln, gewinnt der längere Suchbegriff – „Amazon Prime“ vor „Amazon“.
            </p>
        </section>


        {{-- AUF BESTEHENDE ANWENDEN --}}

        <section class="fv-card p-5 flex flex-col sm:flex-row sm:items-center gap-4">
            <div class="flex-1">
                <p class="font-medium text-slate-900 dark:text-white">Alle Regeln anwenden</p>
                <p class="text-[13px] text-slate-500 dark:text-slate-400">
                    {{ $uncategorizedCount === 1 ? '1 Buchung hat' : $uncategorizedCount . ' Buchungen haben' }} noch keine Kategorie.
                </p>
            </div>

            <form method="POST" action="{{ route('category-rules.apply') }}">
                @csrf
                <button type="submit" class="fv-btn fv-btn-secondary w-full sm:w-auto" @disabled($uncategorizedCount === 0)>Jetzt zuordnen</button>
            </form>
        </section>
    @endif

</div>

@endsection
