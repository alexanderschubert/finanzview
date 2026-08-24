@extends('layouts.app')

@section('title', $budget->name . ' – Finanzblick')

@section('eyebrow', 'Finanzen')

@section('page_title', 'Budget')

@section('content')

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <a
        href="{{ route('budgets.index') }}"
        class="inline-flex items-center text-sm text-slate-500 hover:text-slate-900"
    >
        ← Budgets
    </a>


    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm mt-5 overflow-hidden">

        {{-- HEADER --}}

        <div class="p-6 sm:p-8">

            <div class="flex items-start justify-between gap-5">

                <div class="flex items-center gap-4">

                    <div
                        class="w-14 h-14 rounded-2xl flex items-center justify-center text-2xl"
                        style="background-color: {{ $budget->color ?: '#f1f5f9' }}"
                    >
                        {{ $budget->icon ?: '🎯' }}
                    </div>

                    <div>

                        <h2 class="text-2xl font-semibold text-slate-900">
                            {{ $budget->name }}
                        </h2>

                        <p class="text-sm text-slate-500 mt-1">
                            {{ $budget->start_date->format('d.m.Y') }}
                            –
                            {{ $budget->end_date->format('d.m.Y') }}
                        </p>

                    </div>

                </div>


                @if ($budget->is_active)

                    <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700">
                        Aktiv
                    </span>

                @else

                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-500">
                        Inaktiv
                    </span>

                @endif

            </div>


            {{-- BETRAG --}}

            <div class="mt-8">

                <p class="text-sm text-slate-500">
                    Budget
                </p>

                <p class="text-4xl font-semibold text-slate-900 mt-1">

                    {{ number_format(
                        $budget->amount,
                        2,
                        ',',
                        '.'
                    ) }}

                    €

                </p>

            </div>


            {{-- DETAILS --}}

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-8">

                <div class="rounded-2xl bg-slate-50 p-4">

                    <p class="text-xs text-slate-400">
                        Zeitraum
                    </p>

                    <p class="font-medium text-slate-900 mt-1">

                        {{ $budget->start_date->format('d.m.Y') }}
                        –
                        {{ $budget->end_date->format('d.m.Y') }}

                    </p>

                </div>


                <div class="rounded-2xl bg-slate-50 p-4">

                    <p class="text-xs text-slate-400">
                        Wiederholung
                    </p>

                    <p class="font-medium text-slate-900 mt-1">

                        @switch($budget->period)

                            @case('monthly')
                                Monatlich
                                @break

                            @case('yearly')
                                Jährlich
                                @break

                            @default
                                Benutzerdefiniert

                        @endswitch

                    </p>

                </div>

            </div>


            {{-- KATEGORIEN --}}

            <div class="mt-8">

                <h3 class="font-semibold text-slate-900">
                    Zugeordnete Kategorien
                </h3>


                @if ($budget->categories->isEmpty())

                    <p class="text-sm text-slate-500 mt-3">
                        Diesem Budget wurden noch keine Kategorien zugeordnet.
                    </p>

                @else

                    <div class="flex flex-wrap gap-2 mt-4">

                        @foreach ($budget->categories as $category)

                            <span
                                class="
                                    inline-flex
                                    items-center
                                    gap-2
                                    rounded-full
                                    bg-slate-100
                                    px-3
                                    py-2
                                    text-sm
                                    text-slate-700
                                "
                            >

                                {{ $category->icon ?: '📁' }}

                                {{ $category->name }}

                            </span>

                        @endforeach

                    </div>

                @endif

            </div>

        </div>


        {{-- AKTIONEN --}}

        <div class="border-t border-slate-100 p-6 flex flex-col sm:flex-row gap-3">

            <a
                href="{{ route('budgets.index') }}"
                class="
                    flex-1
                    rounded-xl
                    border
                    border-slate-200
                    bg-white
                    px-5
                    py-3
                    text-center
                    text-sm
                    font-medium
                    text-slate-700
                    hover:bg-slate-50
                "
            >
                Zurück
            </a>


            <a
                href="{{ route('budgets.edit', $budget) }}"
                class="
                    flex-1
                    rounded-xl
                    bg-slate-950
                    px-5
                    py-3
                    text-center
                    text-sm
                    font-medium
                    text-white
                    hover:bg-slate-800
                "
            >
                Budget bearbeiten
            </a>

        </div>

    </div>

</div>

@endsection