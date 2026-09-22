@extends('layouts.guest')

@section('title', 'Neues Passwort')
@section('heading', 'Neues Passwort festlegen')
@section('intro', 'Wähle ein neues Passwort für dein Konto.')

@section('content')
    <form method="POST" action="{{ route('password.update') }}" class="space-y-5">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <label for="email" class="block text-sm font-medium text-slate-700 mb-2">E-Mail</label>
            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autocomplete="email"
                class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:ring-2 focus:ring-slate-900">
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-slate-700 mb-2">Neues Passwort</label>
            <input id="password" type="password" name="password" required autocomplete="new-password"
                class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:ring-2 focus:ring-slate-900">
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-2">Passwort wiederholen</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:ring-2 focus:ring-slate-900">
        </div>

        <button type="submit" class="w-full rounded-xl bg-slate-950 py-3.5 font-medium text-white hover:bg-slate-800 transition">
            Passwort speichern
        </button>
    </form>
@endsection
