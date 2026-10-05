@extends('layouts.app')

@section('title', 'Empfänger zusammenführen – FinanzView')
@section('eyebrow', 'Empfänger')
@section('page_title', 'Zusammenführen')

@php
    $total = $selected->sum(fn ($payee) => $stats[\App\Services\PayeeService::key($payee->name)]['count'] ?? 0);
@endphp

@section('content')

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-back-link :href="route('payees.index')" label="Empfänger" />

    <x-page-header title="Zusammenführen" :subtitle="$selected->count() . ' Empfänger mit zusammen ' . $total . ' Buchungen werden zu einem.'" />

    <x-flash />

    <form method="POST" action="{{ route('payees.merge.store') }}" class="space-y-5">
        @csrf
        @foreach ($selected as $payee) <input type="hidden" name="ids[]" value="{{ $payee->id }}"> @endforeach

        <ul class="fv-card divide-y divide-slate-100 dark:divide-white/5">
            @foreach ($selected as $payee)
                @php $count = $stats[\App\Services\PayeeService::key($payee->name)]['count'] ?? 0; @endphp
                <li class="flex items-center gap-3 px-5 py-3">
                    <p class="flex-1 min-w-0 text-sm font-medium text-slate-900 dark:text-white truncate">{{ $payee->name }}</p>
                    <button type="button" data-use-name="{{ $payee->name }}" class="fv-link text-xs shrink-0">Name übernehmen</button>
                    <p class="shrink-0 text-xs text-slate-500 dark:text-slate-400 tabular-nums">{{ $count === 1 ? '1 Buchung' : $count . ' Buchungen' }}</p>
                </li>
            @endforeach
        </ul>

        <div class="fv-card p-5 sm:p-6 space-y-4">
            <x-field label="Gemeinsamer Name" for="name" error="name" hint="Alle Buchungen bekommen diesen Namen. Die bisherigen Schreibweisen merkt sich FinanzView: Künftige Buchungen damit (Import, Bankabruf, Eingabe) werden automatisch zugeordnet.">
                <input id="name" name="name" type="text" required maxlength="255" value="{{ old('name', $suggestedName) }}" class="fv-input" autofocus>
            </x-field>

            <x-field label="Standardkategorie" for="default_category_id" error="default_category_id" hint="Optional – wird bei neuen Buchungen ohne eigene Kategorie gesetzt.">
                <select id="default_category_id" name="default_category_id" class="fv-input">
                    <option value="">Keine</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) old('default_category_id', $suggestedCategory) === (string) $category->id)>{{ $category->icon }} {{ $category->display_name }}</option>
                    @endforeach
                </select>
            </x-field>

            <label class="flex items-center justify-between gap-4 cursor-pointer">
                <span>
                    <span class="block text-sm font-medium text-slate-900 dark:text-white">Auf vorhandene Buchungen anwenden</span>
                    <span class="block text-[13px] text-slate-500 dark:text-slate-400">Nur Buchungen ohne Kategorie – vorhandene Zuordnungen bleiben.</span>
                </span>
                <input type="checkbox" name="apply" value="1" class="sr-only peer" @checked(old('apply', true))>
                <span aria-hidden="true" class="relative inline-flex h-[31px] w-[51px] shrink-0 rounded-full bg-slate-200 dark:bg-slate-700 transition peer-checked:bg-emerald-500 peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-500 after:absolute after:top-[2px] after:left-[2px] after:h-[27px] after:w-[27px] after:rounded-full after:bg-white after:shadow after:transition peer-checked:after:translate-x-[20px]"></span>
            </label>
        </div>

        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
            <a href="{{ route('payees.index') }}" class="fv-btn fv-btn-secondary">Abbrechen</a>
            <button type="submit" class="fv-btn fv-btn-primary">Zusammenführen</button>
        </div>
    </form>

</div>

<script>
    document.querySelectorAll('[data-use-name]').forEach(button => button.addEventListener('click', () => {
        const input = document.getElementById('name');
        input.value = button.dataset.useName;
        input.focus();
    }));
</script>

@endsection
