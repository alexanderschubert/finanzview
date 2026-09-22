@extends('layouts.guest')

@section('title', 'Passwort vergessen')
@section('heading', 'Passwort vergessen')
@section('intro', 'Gib deine E-Mail-Adresse ein. Wir senden dir einen Link zum Zurücksetzen.')

@section('content')
    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-slate-700 mb-2">E-Mail</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"
                class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:ring-2 focus:ring-slate-900">
        </div>

        <button type="submit" class="w-full rounded-xl bg-slate-950 py-3.5 font-medium text-white hover:bg-slate-800 transition">
            Link senden
        </button>
    </form>

    <div class="text-center mt-6">
        <a href="{{ route('login') }}" class="text-sm font-medium text-slate-950 hover:underline">Zurück zur Anmeldung</a>
    </div>
@endsection
