@extends('layouts.app')

@section('title', 'Konto wählen – FinanzView')
@section('eyebrow', 'Bankverbindungen')
@section('page_title', 'Konto wählen')

@section('content')

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-back-link :href="route('bank-connections.index')" label="Bankverbindungen" />

    <x-page-header :title="$connection->name" subtitle="Schritt 3 von 3: Welche Bankkonten sollen in welche FinanzView-Konten?" />

    <x-flash />

    <form method="POST" action="{{ route('bank-connections.accounts.store', $connection) }}" class="space-y-4">
        @csrf

        <ul class="fv-card divide-y divide-slate-100 dark:divide-white/5 overflow-hidden">
            @foreach ($bankAccounts as $bankAccount)
                @php
                    $iban = $bankAccount['iban'];
                    $selected = (string) old('link.' . $iban, $defaults[$iban] ?? '');
                @endphp
                <li class="px-5 py-4 space-y-2">
                    <label for="link_{{ $loop->index }}" class="block font-mono text-sm text-slate-900 dark:text-white">{{ trim(chunk_split($iban, 4, ' ')) }}</label>
                    <select id="link_{{ $loop->index }}" name="link[{{ $iban }}]" class="fv-input">
                        <option value="">Nicht abrufen</option>
                        <optgroup label="In vorhandenes Konto">
                            @foreach ($accounts as $account)
                                <option value="{{ $account->id }}" @selected($selected === (string) $account->id)>{{ $account->name }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="Neues Konto anlegen">
                            <option value="new:checking" @selected($selected === 'new:checking')>+ Neues Girokonto</option>
                            <option value="new:savings" @selected($selected === 'new:savings')>+ Neues Sparkonto (z. B. Sparbuch)</option>
                        </optgroup>
                    </select>
                </li>
            @endforeach
        </ul>

        @error('link') <p class="px-1 text-[13px] text-red-600 dark:text-red-400">{{ $message }}</p> @enderror

        <p class="px-1 text-[13px] text-slate-500 dark:text-slate-400">
            Alle gewählten Konten werden mit einem Abruf aktualisiert. Bei einem neuen Konto übernimmt FinanzView beim ersten Abruf den Kontostand der Bank.
        </p>

        <div class="flex justify-end">
            <button type="submit" class="fv-btn fv-btn-primary">Fertig</button>
        </div>
    </form>

</div>

@endsection
