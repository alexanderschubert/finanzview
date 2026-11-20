@extends('layouts.app')

@section('title', 'Gruppe bearbeiten – FinanzView')
@section('eyebrow', 'Planung')
@section('page_title', 'Gruppe bearbeiten')

@section('content')

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-back-link :href="route('plan.index', ['month' => $monthKey]) . '#group-' . $group->id" label="Monatsplan" />

    <x-page-header title="Gruppe bearbeiten" />

    <form method="POST" action="{{ route('plan.groups.update', $group) }}" class="fv-card p-5 sm:p-6 space-y-5">
        @csrf
        @method('PUT')

        <x-field label="Name" for="name" error="name">
            <input id="name" name="name" type="text" maxlength="255" required value="{{ old('name', $group->name) }}" class="fv-input">
        </x-field>

        <x-field label="Art" for="kind" error="kind" hint="Raten und Kredite zählen als Ausgaben und bekommen oben eine eigene Summe.">
            <select id="kind" name="kind" class="fv-input">
                @foreach ($kinds as $value => $label)
                    <option value="{{ $value }}" @selected(old('kind', $group->kind) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </x-field>

        <x-symbol-picker :icon="old('icon', $group->icon)" :color="old('color', $group->color)" fallback="📁" />

        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
            <a href="{{ route('plan.index', ['month' => $monthKey]) }}#group-{{ $group->id }}" class="fv-btn fv-btn-secondary">Abbrechen</a>
            <button type="submit" class="fv-btn fv-btn-primary">Speichern</button>
        </div>
    </form>

    <form method="POST" action="{{ route('plan.groups.destroy', $group) }}" onsubmit="return confirm('Gruppe mit allen Posten aus dem Plan entfernen?')" class="flex justify-center">
        @csrf
        @method('DELETE')
        <button type="submit" class="fv-btn text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10">
            <x-icon name="trash" class="w-4 h-4" />
            Gruppe entfernen
        </button>
    </form>

</div>

@endsection
