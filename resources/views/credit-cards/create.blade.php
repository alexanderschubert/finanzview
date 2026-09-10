@extends('layouts.app')

@section('title', 'Kreditkarte hinzufügen')

@section('content')
<div class="mx-auto max-w-3xl space-y-6">

    {{-- Header --}}
    <div>
        <div class="flex items-center gap-3">
            <a
                href="{{ route('credit-cards.index') }}"
                class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-900 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-100"
                aria-label="Zurück"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M15 18l-6-6 6-6"/>
                </svg>
            </a>

            <div>
                <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">
                    Kreditkarte hinzufügen
                </h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Hinterlege die wichtigsten Daten deiner Kreditkarte.
                </p>
            </div>
        </div>
    </div>


    {{-- Validation errors --}}
    @if($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 p-4 dark:border-red-900/60 dark:bg-red-950/30">
            <div class="flex gap-3">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="9"/>
                    <path d="M12 8v4M12 16h.01"/>
                </svg>

                <div>
                    <p class="font-medium text-red-800 dark:text-red-300">
                        Bitte prüfe deine Eingaben.
                    </p>

                    <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-700 dark:text-red-400">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif


    <form
        method="POST"
        action="{{ route('credit-cards.store') }}"
        class="space-y-6"
    >
        @csrf

        {{-- Basic information --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                <h2 class="font-semibold text-slate-900 dark:text-slate-100">
                    Kartendaten
                </h2>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Identifikation der Kreditkarte.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-5 p-5 sm:grid-cols-2">

                <div class="sm:col-span-2">
                    <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                        Kartenname <span class="text-red-500">*</span>
                    </label>

                    <input
                        id="name"
                        name="name"
                        type="text"
                        required
                        maxlength="255"
                        value="{{ old('name') }}"
                        placeholder="z. B. PayPal Mastercard"
                        class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-600 dark:focus:border-slate-500 dark:focus:ring-slate-800"
                    >
                </div>


                <div>
                    <label for="issuer" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                        Herausgeber
                    </label>

                    <input
                        id="issuer"
                        name="issuer"
                        type="text"
                        maxlength="255"
                        value="{{ old('issuer') }}"
                        placeholder="z. B. Mastercard"
                        class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-600 dark:focus:border-slate-500 dark:focus:ring-slate-800"
                    >
                </div>


                <div>
                    <label for="last_four" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                        Letzte 4 Stellen
                    </label>

                    <input
                        id="last_four"
                        name="last_four"
                        type="text"
                        inputmode="numeric"
                        maxlength="4"
                        value="{{ old('last_four') }}"
                        placeholder="1234"
                        class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm tracking-widest text-slate-900 outline-none transition placeholder:tracking-normal placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-600 dark:focus:border-slate-500 dark:focus:ring-slate-800"
                    >
                </div>


                <div>
                    <label for="provider_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                        Anbieter
                    </label>

                    <select
                        id="provider_id"
                        name="provider_id"
                        class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:focus:border-slate-500 dark:focus:ring-slate-800"
                    >
                        <option value="">Kein Anbieter</option>

                        @foreach($providers as $provider)
                            <option
                                value="{{ $provider->id }}"
                                @selected((string) old('provider_id') === (string) $provider->id)
                            >
                                {{ $provider->name }}
                            </option>
                        @endforeach
                    </select>
                </div>


                <div>
                    <label for="account_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                        Verknüpftes Konto
                    </label>

                    <select
                        id="account_id"
                        name="account_id"
                        class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:focus:border-slate-500 dark:focus:ring-slate-800"
                    >
                        <option value="">Kein Konto</option>

                        @foreach($accounts as $account)
                            <option
                                value="{{ $account->id }}"
                                @selected((string) old('account_id') === (string) $account->id)
                            >
                                {{ $account->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

            </div>
        </div>


        {{-- Financial data --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                <h2 class="font-semibold text-slate-900 dark:text-slate-100">
                    Finanzielle Daten
                </h2>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Kreditlimit und aktueller Saldo.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-5 p-5 sm:grid-cols-2">

                <div>
                    <label for="credit_limit" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                        Kreditlimit
                    </label>

                    <div class="relative mt-2">
                        <input
                            id="credit_limit"
                            name="credit_limit"
                            type="number"
                            min="0"
                            step="0.01"
                            value="{{ old('credit_limit') }}"
                            placeholder="2000.00"
                            class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 pr-10 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-slate-500 focus:ring-2 focus:ring-slate-200 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-600 dark:focus:border-slate-500 dark:focus:ring-slate-800"
                        >
                        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-400">
                            €
                        </span>
                    </div>
                </div>


                <div>
                    <label for="current_balance" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                        Aktueller Saldo <span class="text-red-500">*</span>
                    </label>

                    <div class="relative mt-2">
                        <input
                            id="current_balance"
                            name="current_balance"
                            type="number"
                            min="0"
                            step="0.01"
                            required
                            value="{{ old('current_balance', '0.00') }}"
                            class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 pr-10 text-sm text-slate-900 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:focus:border-slate-500 dark:focus:ring-slate-800"
                        >
                        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-sm text-slate-400">
                            €
                        </span>
                    </div>
                </div>

            </div>
        </div>


        {{-- Billing --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                <h2 class="font-semibold text-slate-900 dark:text-slate-100">
                    Abrechnung
                </h2>
                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                    Tage für Abrechnung und Zahlungsziel.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-5 p-5 sm:grid-cols-2">

                <div>
                    <label for="billing_day" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                        Abrechnungstag
                    </label>

                    <select
                        id="billing_day"
                        name="billing_day"
                        class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                    >
                        <option value="">Nicht hinterlegt</option>

                        @for($day = 1; $day <= 31; $day++)
                            <option value="{{ $day }}" @selected((string) old('billing_day') === (string) $day)>
                                {{ $day }}. Tag
                            </option>
                        @endfor
                    </select>
                </div>


                <div>
                    <label for="payment_due_day" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                        Zahlungsziel
                    </label>

                    <select
                        id="payment_due_day"
                        name="payment_due_day"
                        class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-200 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                    >
                        <option value="">Nicht hinterlegt</option>

                        @for($day = 1; $day <= 31; $day++)
                            <option value="{{ $day }}" @selected((string) old('payment_due_day') === (string) $day)>
                                {{ $day }}. Tag
                            </option>
                        @endfor
                    </select>
                </div>

            </div>
        </div>


        {{-- Appearance --}}
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                <h2 class="font-semibold text-slate-900 dark:text-slate-100">
                    Darstellung
                </h2>
            </div>

            <div class="p-5">
                <label for="color" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                    Kartenfarbe
                </label>

                <div class="mt-2 flex items-center gap-3">
                    <input
                        id="color"
                        name="color"
                        type="color"
                        value="{{ old('color', '#334155') }}"
                        class="h-10 w-16 cursor-pointer rounded-lg border border-slate-300 bg-white p-1 dark:border-slate-700 dark:bg-slate-950"
                    >

                    <span class="text-xs text-slate-500 dark:text-slate-400">
                        Wird in der Kreditkartenübersicht angezeigt.
                    </span>
                </div>
            </div>
        </div>


        {{-- Actions --}}
        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <a
                href="{{ route('credit-cards.index') }}"
                class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800"
            >
                Abbrechen
            </a>

            <button
                type="submit"
                class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-slate-700 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white"
            >
                Kreditkarte speichern
            </button>
        </div>

    </form>

</div>
@endsection
