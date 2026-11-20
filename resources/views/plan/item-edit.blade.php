@extends('layouts.app')

@section('title', 'Posten bearbeiten – FinanzView')
@section('eyebrow', 'Monatsplan')
@section('page_title', 'Posten bearbeiten')

@section('content')

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-back-link :href="route('plan.index', ['month' => $monthKey]) . '#group-' . $group->id" label="Monatsplan" />

    <x-page-header title="Posten bearbeiten" :subtitle="$group->name" />

    <form method="POST" action="{{ route('plan.items.update', $item) }}" class="fv-card p-5 sm:p-6 space-y-5">
        @csrf
        @method('PUT')

        <x-field label="Beschreibung" for="name" error="name">
            <input id="name" name="name" type="text" maxlength="255" required value="{{ old('name', $item->name) }}" class="fv-input">
        </x-field>

        <x-field label="Betrag pro Monat" for="amount" error="amount">
            <input id="amount" name="amount" type="text" inputmode="decimal" required value="{{ old('amount', number_format((float) $item->amount, 2, ',', '.')) }}" class="fv-input tabular-nums">
        </x-field>

        <x-field label="Bemerkung" for="note" error="note" hint="Zum Beispiel „AMEX“ oder „Fällig ab 08.05.26“.">
            <input id="note" name="note" type="text" maxlength="255" value="{{ old('note', $item->note) }}" class="fv-input">
        </x-field>

        <x-field label="Wie oft fällt der Betrag an?" for="interval" error="interval" hint="Der Betrag gilt je Zahlung. Bei „Jährlich“ zählt er nur in dem Monat, in dem er fällig ist.">
            <select id="interval" name="interval" class="fv-input">
                @foreach ($intervals as $value => $meta)
                    <option value="{{ $value }}" @selected(old('interval', $item->interval) === $value)>{{ $meta['label'] }}</option>
                @endforeach
            </select>
        </x-field>

        <x-field label="Zahlungstag im Monat" for="due_day" error="due_day" hint="Optional. Damit erscheint der Posten im Kalender an diesem Tag. In kürzeren Monaten gilt der letzte Tag.">
            <input id="due_day" name="due_day" type="number" min="1" max="31" step="1" inputmode="numeric" value="{{ old('due_day', $item->due_day) }}" placeholder="z. B. 1" class="fv-input tabular-nums sm:max-w-[10rem]">
        </x-field>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
            <x-field label="Beginn / erste Zahlung" for="starts_on" error="starts_on" hint="Bei Krediten und Raten der Start. Bei jährlich, halbjährlich oder vierteljährlich legt der Monat fest, wann gezahlt wird.">
                <input id="starts_on" name="starts_on" type="date" value="{{ old('starts_on', $item->starts_on?->format('Y-m-d')) }}" class="fv-input">
            </x-field>

            <x-field label="Ende / letzte Rate" for="ends_on" error="ends_on" hint="Danach zählt der Posten nicht mehr. Leer lassen für unbefristet.">
                <input id="ends_on" name="ends_on" type="date" value="{{ old('ends_on', $item->ends_on?->format('Y-m-d')) }}" class="fv-input">
            </x-field>
        </div>

        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
            <a href="{{ route('plan.index', ['month' => $monthKey]) }}#group-{{ $group->id }}" class="fv-btn fv-btn-secondary">Abbrechen</a>
            <button type="submit" class="fv-btn fv-btn-primary">Speichern</button>
        </div>
    </form>

    <form method="POST" action="{{ route('plan.items.destroy', $item) }}" onsubmit="return confirm('Posten aus dem Plan entfernen?')" class="flex justify-center">
        @csrf
        @method('DELETE')
        <button type="submit" class="fv-btn text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10">
            <x-icon name="trash" class="w-4 h-4" />
            Posten entfernen
        </button>
    </form>

</div>

@endsection
