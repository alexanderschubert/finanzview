@extends('layouts.app')

@section('title', 'Konto wählen – FinanzView')
@section('eyebrow', 'Bankverbindungen')
@section('page_title', 'Konto wählen')

@section('content')

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-back-link :href="route('bank-connections.index')" label="Bankverbindungen" />

    <x-page-header :title="$connection->name" subtitle="Schritt 3 von 3: Welches Bankkonto soll in welches FinanzView-Konto?" />

    <x-flash />

    <form method="POST" action="{{ route('bank-connections.accounts.store', $connection) }}" class="space-y-5">
        @csrf

        <fieldset class="fv-card divide-y divide-slate-100 dark:divide-white/5 overflow-hidden">
            <legend class="sr-only">Bankkonto</legend>
            @foreach ($bankAccounts as $bankAccount)
                <label class="flex items-center gap-3 px-5 py-3.5 cursor-pointer hover:bg-slate-50 dark:hover:bg-white/5">
                    <input type="radio" name="iban" value="{{ $bankAccount['iban'] }}" class="w-4 h-4 accent-emerald-600" @checked(old('iban', $loop->first ? $bankAccount['iban'] : null) === $bankAccount['iban']) required>
                    <span class="flex-1 font-mono text-sm text-slate-900 dark:text-white">{{ trim(chunk_split($bankAccount['iban'], 4, ' ')) }}</span>
                </label>
            @endforeach
        </fieldset>
        @error('iban') <p class="px-1 text-[13px] text-red-600 dark:text-red-400">{{ $message }}</p> @enderror

        <div class="fv-card p-5 sm:p-6">
            <x-field label="Importieren in FinanzView-Konto" for="account_id" error="account_id">
                <select id="account_id" name="account_id" class="fv-input" required>
                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}" @selected((string) old('account_id') === (string) $account->id || (! old('account_id') && $account->iban && str_replace(' ', '', $account->iban) === ($bankAccounts[0]['iban'] ?? null)))>{{ $account->name }}</option>
                    @endforeach
                </select>
            </x-field>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="fv-btn fv-btn-primary">Fertig</button>
        </div>
    </form>

</div>

@endsection
