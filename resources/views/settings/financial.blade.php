@extends('layouts.app')

@section('title', 'Finanzen – FinanzView')
@section('eyebrow', 'Einstellungen')
@section('page_title', 'Finanzen')

@section('content')

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-back-link :href="route('settings.index')" label="Einstellungen" />

    <x-page-header title="Finanzen" subtitle="Voreinstellungen für neue Buchungen." />

    <x-flash />

    <form method="POST" action="{{ route('settings.financial.update') }}" class="space-y-5">
        @csrf
        @method('PUT')

        {{-- Werte, die (noch) nirgends verwendet werden, unverändert mitsenden --}}
        <input type="hidden" name="currency" value="{{ $setting->currency ?: 'EUR' }}">
        <input type="hidden" name="decimal_places" value="{{ $setting->decimal_places ?? 2 }}">
        <input type="hidden" name="date_format" value="{{ $setting->date_format ?: 'd.m.Y' }}">
        <input type="hidden" name="first_day_of_week" value="{{ $setting->first_day_of_week ?: 1 }}">

        <div class="fv-card p-5 sm:p-6 space-y-4">
            <x-field label="Standardkonto" for="default_account_id" error="default_account_id" hint="Wird bei „Neue Buchung“ vorausgewählt.">
                <select id="default_account_id" name="default_account_id" class="fv-input">
                    <option value="">Keins – jedes Mal auswählen</option>
                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}" @selected((string) old('default_account_id', $setting->default_account_id) === (string) $account->id)>
                            {{ $account->name }}{{ $account->is_active ? '' : ' (inaktiv)' }}
                        </option>
                    @endforeach
                </select>
            </x-field>

            <x-field label="Standardkategorie" for="default_category_id" error="default_category_id" hint="Wird bei neuen Ausgaben vorausgewählt, wenn sie passt.">
                <select id="default_category_id" name="default_category_id" class="fv-input">
                    <option value="">Keine</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) old('default_category_id', $setting->default_category_id) === (string) $category->id)>
                            {{ $category->icon }} {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </x-field>
        </div>

        <p class="px-1 text-[13px] text-slate-500 dark:text-slate-400">
            Beträge werden in der Währung des jeweiligen Kontos und im deutschen Format angezeigt.
        </p>

        <div class="flex justify-end">
            <button type="submit" class="fv-btn fv-btn-primary">Speichern</button>
        </div>
    </form>

</div>

@endsection
