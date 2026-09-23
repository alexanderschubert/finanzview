@extends('layouts.guest')

@section('title', 'Zwei-Faktor-Authentifizierung')
@section('heading', 'Bestätigungscode')
@section('intro', 'Gib den 6-stelligen Code aus deiner Authenticator-App ein.')

@section('content')
    <form method="POST" action="{{ url('/two-factor-challenge') }}" class="space-y-5">
        @csrf

        <div>
            <label for="code" class="block text-sm font-medium text-slate-700 mb-2">Code</label>
            <input id="code" type="text" name="code" inputmode="numeric" pattern="[0-9 ]*" autofocus autocomplete="one-time-code"
                placeholder="123456"
                class="w-full rounded-xl border border-slate-200 px-4 py-3 text-center text-lg tracking-[0.4em] outline-none focus:ring-2 focus:ring-slate-900">
        </div>

        <details class="rounded-xl border border-slate-200 px-4 py-3">
            <summary class="cursor-pointer text-sm font-medium text-slate-700">
                Kein Zugriff auf die App? Wiederherstellungscode verwenden
            </summary>

            <div class="mt-4">
                <label for="recovery_code" class="block text-sm font-medium text-slate-700 mb-2">Wiederherstellungscode</label>
                <input id="recovery_code" type="text" name="recovery_code" autocomplete="off"
                    placeholder="abcdefghij-klmnopqrst"
                    class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:ring-2 focus:ring-slate-900">
                <p class="text-xs text-slate-500 mt-2">Jeder Wiederherstellungscode kann nur einmal verwendet werden.</p>
            </div>
        </details>

        <button type="submit" class="w-full rounded-xl bg-slate-950 py-3.5 font-medium text-white hover:bg-slate-800 transition">
            Anmelden
        </button>
    </form>

    <div class="text-center mt-6">
        <a href="{{ route('login') }}" class="text-sm font-medium text-slate-950 hover:underline">Zurück zur Anmeldung</a>
    </div>
@endsection
