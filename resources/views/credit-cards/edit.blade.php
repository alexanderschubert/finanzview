@extends('layouts.app')

@section('title', 'Kreditkarte bearbeiten')

@section('content')
<div class="mx-auto max-w-4xl space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm font-medium text-slate-500 dark:text-slate-400">
                Kreditkarten
            </p>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
                Kreditkarte bearbeiten
            </h1>
        </div>

        <a
            href="{{ route('credit-cards.show', $creditCard) }}"
            class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
        >
            Zurück zur Karte
        </a>
    </div>

    @if ($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900/60 dark:bg-red-950/30 dark:text-red-300">
            <p class="font-semibold">Bitte prüfe deine Eingaben.</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('credit-cards.update', $creditCard) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-slate-200 px-6 py-5 dark:border-slate-800">
                <h2 class="font-semibold text-slate-900 dark:text-white">Kartendaten</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    Allgemeine Informationen zur Kreditkarte.
                </p>
            </div>

            <div class="grid gap-5 p-6 md:grid-cols-2">
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                        Kartenname *
                    </label>
                    <input
                        id="name"
                        name="name"
                        type="text"
                        required
                        value="{{ old('name', $creditCard->name) }}"
                        class="mt-2 block w-full rounded-xl border-slate-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
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
                        value="{{ old('issuer', $creditCard->issuer) }}"
                        class="mt-2 block w-full rounded-xl border-slate-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                    >
                </div>

                <div>
                    <label for="last_four" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                        Letzte 4 Ziffern
                    </label>
                    <input
                        id="last_four"
                        name="last_four"
                        type="text"
                        inputmode="numeric"
                        maxlength="4"
                        value="{{ old('last_four', $creditCard->last_four) }}"
                        class="mt-2 block w-full rounded-xl border-slate-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                    >
                </div>

                <div>
                    <label for="provider_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                        Anbieter
                    </label>
                    <select
                        id="provider_id"
                        name="provider_id"
                        class="mt-2 block w-full rounded-xl border-slate-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                    >
                        <option value="">Kein Anbieter</option>
                        @foreach ($providers as $provider)
                            <option
                                value="{{ $provider->id }}"
                                @selected(old('provider_id', $creditCard->provider_id) == $provider->id)
                            >
                                {{ $provider->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label for="account_id" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                        Verknüpftes Konto
                    </label>
                    <select
                        id="account_id"
                        name="account_id"
                        class="mt-2 block w-full rounded-xl border-slate-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                    >
                        <option value="">Kein Konto</option>
                        @foreach ($accounts as $account)
                            <option
                                value="{{ $account->id }}"
                                @selected(old('account_id', $creditCard->account_id) == $account->id)
                            >
                                {{ $account->name }}{{ $account->institution ? ' – '.$account->institution : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-slate-200 px-6 py-5 dark:border-slate-800">
                <h2 class="font-semibold text-slate-900 dark:text-white">Finanzielle Daten</h2>
            </div>

            <div class="grid gap-5 p-6 md:grid-cols-2">
                <div>
                    <label for="credit_limit" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                        Kreditlimit
                    </label>
                    <input
                        id="credit_limit"
                        name="credit_limit"
                        type="number"
                        step="0.01"
                        min="0"
                        value="{{ old('credit_limit', $creditCard->credit_limit) }}"
                        class="mt-2 block w-full rounded-xl border-slate-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                    >
                </div>

                <div>
                    <label for="current_balance" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                        Aktueller Saldo *
                    </label>
                    <input
                        id="current_balance"
                        name="current_balance"
                        type="number"
                        step="0.01"
                        min="0"
                        required
                        value="{{ old('current_balance', $creditCard->current_balance) }}"
                        class="mt-2 block w-full rounded-xl border-slate-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                    >
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-slate-200 px-6 py-5 dark:border-slate-800">
                <h2 class="font-semibold text-slate-900 dark:text-white">Abrechnung</h2>
            </div>

            <div class="grid gap-5 p-6 md:grid-cols-2">
                <div>
                    <label for="billing_day" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                        Abrechnungstag
                    </label>
                    <input
                        id="billing_day"
                        name="billing_day"
                        type="number"
                        min="1"
                        max="31"
                        value="{{ old('billing_day', $creditCard->billing_day) }}"
                        class="mt-2 block w-full rounded-xl border-slate-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                    >
                </div>

                <div>
                    <label for="payment_due_day" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                        Fälligkeitstag
                    </label>
                    <input
                        id="payment_due_day"
                        name="payment_due_day"
                        type="number"
                        min="1"
                        max="31"
                        value="{{ old('payment_due_day', $creditCard->payment_due_day) }}"
                        class="mt-2 block w-full rounded-xl border-slate-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                    >
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="border-b border-slate-200 px-6 py-5 dark:border-slate-800">
                <h2 class="font-semibold text-slate-900 dark:text-white">Darstellung</h2>
            </div>

            <div class="space-y-5 p-6">
                <div>
                    <label for="color" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
                        Kartenfarbe
                    </label>
                    <input
                        id="color"
                        name="color"
                        type="text"
                        value="{{ old('color', $creditCard->color) }}"
                        placeholder="#334155"
                        class="mt-2 block w-full rounded-xl border-slate-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                    >
                </div>

                <label class="flex items-center gap-3">
                    <input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        @checked(old('is_active', $creditCard->is_active))
                        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-700"
                    >
                    <span>
                        <span class="block text-sm font-medium text-slate-800 dark:text-slate-200">
                            Kreditkarte aktiv
                        </span>
                        <span class="block text-xs text-slate-500 dark:text-slate-400">
                            Inaktive Karten bleiben gespeichert, werden aber entsprechend ausgeblendet.
                        </span>
                    </span>
                </label>
            </div>
        </div>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-between">
            <button
                type="submit"
                form="delete-credit-card"
                onclick="return confirm('Kreditkarte wirklich archivieren?')"
                class="inline-flex items-center justify-center rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-semibold text-red-700 transition hover:bg-red-100 dark:border-red-900/60 dark:bg-red-950/30 dark:text-red-300 dark:hover:bg-red-950/50"
            >
                Kreditkarte archivieren
            </button>

            <div class="flex gap-3">
                <a
                    href="{{ route('credit-cards.show', $creditCard) }}"
                    class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                >
                    Abbrechen
                </a>

                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700"
                >
                    Änderungen speichern
                </button>
            </div>
        </div>
    </form>

    <form
        id="delete-credit-card"
        method="POST"
        action="{{ route('credit-cards.destroy', $creditCard) }}"
        class="hidden"
    >
        @csrf
        @method('DELETE')
    </form>

</div>
@endsection
