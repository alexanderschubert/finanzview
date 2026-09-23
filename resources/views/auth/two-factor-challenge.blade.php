@extends('layouts.guest')

@section('title', 'Zwei-Faktor-Authentifizierung')
@section('heading', 'Bestätigungscode')
@section('intro', 'Gib den 6-stelligen Code aus deiner Authenticator-App ein.')

@section('content')

    <form method="POST" action="{{ url('/two-factor-challenge') }}" class="space-y-4">
        @csrf

        <div>
            <label for="code" class="fv-label">Code</label>
            <input id="code" type="text" name="code" inputmode="numeric" pattern="[0-9 ]*" autofocus autocomplete="one-time-code"
                placeholder="000000"
                class="fv-input text-center text-2xl font-semibold tracking-[0.5em] tabular-nums">
        </div>

        <details class="group rounded-xl bg-slate-50 dark:bg-white/5 px-4 py-3">
            <summary class="cursor-pointer list-none flex items-center justify-between text-sm font-medium text-slate-600 dark:text-slate-300">
                Kein Zugriff auf die App?
                <x-icon name="chevron-right" class="w-4 h-4 text-slate-400 transition group-open:rotate-90" />
            </summary>

            <div class="mt-3">
                <label for="recovery_code" class="fv-label">Wiederherstellungscode</label>
                <input id="recovery_code" type="text" name="recovery_code" autocomplete="off"
                    placeholder="abcdefghij-klmnopqrst" class="fv-input font-mono text-sm">
                <p class="mt-2 text-xs text-slate-500">Jeder Wiederherstellungscode funktioniert nur einmal.</p>
            </div>
        </details>

        <button type="submit" class="fv-btn fv-btn-primary w-full mt-2">
            Anmelden
        </button>
    </form>

@endsection

@section('footer')
    <a href="{{ route('login') }}" class="fv-link inline-flex items-center gap-1.5">
        <x-icon name="arrow-left" class="w-4 h-4" />
        Zurück zur Anmeldung
    </a>
@endsection
