@extends('layouts.app')

@section('title', $payee->name . ' – Empfänger – FinanzView')
@section('eyebrow', 'Empfänger')
@section('page_title', 'Empfänger')

@php
    $money = fn ($amount) => number_format((float) $amount, 2, ',', '.') . ' €';
    $ownKey = \App\Services\PayeeService::key($payee->name);
    $otherAliases = $payee->aliases->reject(fn ($alias) => $alias->alias_key === $ownKey);
@endphp

@section('content')

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-back-link :href="route('payees.index')" label="Empfänger" />

    <x-page-header :title="$payee->name" :subtitle="($stat['count'] === 1 ? '1 Buchung' : $stat['count'] . ' Buchungen') . ($stat['last'] ? ' · zuletzt ' . \Carbon\Carbon::parse($stat['last'])->format('d.m.Y') : '')">
        @if ($stat['count'] > 0)
            <a href="{{ route('transactions.index', ['payee' => $payee->id]) }}" class="fv-btn fv-btn-secondary text-sm py-2.5">
                <x-icon name="arrows" class="w-4 h-4" />
                Buchungen
            </a>
        @endif
    </x-page-header>

    <x-flash />

    @if ($stat['count'] > 0)
        <div class="grid grid-cols-2 gap-3">
            <x-stat label="Ausgaben" icon="trending-down" tone="negative">{{ $money($stat['expense']) }}</x-stat>
            <x-stat label="Einnahmen" icon="trending-up" tone="positive">{{ $money($stat['income']) }}</x-stat>
        </div>
    @endif

    <form method="POST" action="{{ route('payees.update', $payee) }}" class="fv-card p-5 sm:p-6 space-y-4">
        @csrf
        @method('PUT')

        <x-field label="Name" for="name" error="name" hint="Der Buchungstext der Bank bleibt unverändert – der Empfänger wird nur zusätzlich angezeigt.">
            <input id="name" name="name" type="text" required maxlength="255" value="{{ old('name', $payee->name) }}" class="fv-input">
        </x-field>

        <x-field label="Standardkategorie" for="default_category_id" error="default_category_id" hint="Wird bei neuen Buchungen (Import, Bankabruf, Eingabe) gesetzt, wenn keine Kategorie-Regel greift und keine gewählt ist.">
            <select id="default_category_id" name="default_category_id" class="fv-input">
                <option value="">Keine</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) old('default_category_id', $payee->default_category_id) === (string) $category->id)>{{ $category->icon }} {{ $category->display_name }}</option>
                @endforeach
            </select>
        </x-field>

        <label class="flex items-center justify-between gap-4 cursor-pointer">
            <span>
                <span class="block text-sm font-medium text-slate-900 dark:text-white">Auf vorhandene Buchungen anwenden</span>
                <span class="block text-[13px] text-slate-500 dark:text-slate-400">Nur Buchungen ohne Kategorie – vorhandene Zuordnungen bleiben.</span>
            </span>
            <input type="checkbox" name="apply" value="1" class="sr-only peer">
            <span aria-hidden="true" class="relative inline-flex h-[31px] w-[51px] shrink-0 rounded-full bg-slate-200 dark:bg-slate-700 transition peer-checked:bg-emerald-500 peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-500 after:absolute after:top-[2px] after:left-[2px] after:h-[27px] after:w-[27px] after:rounded-full after:bg-white after:shadow after:transition peer-checked:after:translate-x-[20px]"></span>
        </label>

        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
            <a href="{{ route('payees.index') }}" class="fv-btn fv-btn-secondary">Abbrechen</a>
            <button type="submit" class="fv-btn fv-btn-primary">Speichern</button>
        </div>
    </form>

    {{-- SCHREIBWEISEN --}}

    @if ($otherAliases->isNotEmpty())
        <section>
            <h3 class="px-1 pb-2 text-[13px] font-semibold text-slate-500 dark:text-slate-400">Weitere Schreibweisen</h3>

            <ul class="fv-card divide-y divide-slate-100 dark:divide-white/5">
                @foreach ($otherAliases as $alias)
                    <li class="flex items-center gap-3 px-5 py-2.5">
                        <p class="flex-1 min-w-0 text-sm text-slate-700 dark:text-slate-200 truncate">{{ $alias->alias }}</p>
                        <form method="POST" action="{{ route('payees.aliases.destroy', [$payee, $alias]) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-500/10 transition" aria-label="{{ $alias->alias }} entfernen" title="Entfernen">
                                <x-icon name="x" class="w-4 h-4" />
                            </button>
                        </form>
                    </li>
                @endforeach
            </ul>

            <p class="mt-2 px-1 text-[13px] text-slate-500 dark:text-slate-400">
                Buchungen mit diesen Schreibweisen werden automatisch diesem Empfänger zugeordnet. Entfernst du eine, entsteht beim nächsten Mal wieder ein eigener Empfänger.
            </p>
        </section>
    @endif

    @if ($stat['count'] === 0)
        <form method="POST" action="{{ route('payees.destroy', $payee) }}" onsubmit="return confirm('Empfänger löschen?')" class="flex justify-center">
            @csrf
            @method('DELETE')
            <button type="submit" class="fv-btn text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10">
                <x-icon name="trash" class="w-4 h-4" />
                Empfänger löschen
            </button>
        </form>
    @endif

</div>

@endsection
