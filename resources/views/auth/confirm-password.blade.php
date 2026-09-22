@extends('layouts.guest')

@section('title', 'Passwort bestätigen')
@section('heading', 'Passwort bestätigen')
@section('intro', 'Bitte bestätige dein Passwort, bevor du fortfährst.')

@section('content')
    <form method="POST" action="{{ route('password.confirm.store') }}" class="space-y-5">
        @csrf

        <div>
            <label for="password" class="block text-sm font-medium text-slate-700 mb-2">Passwort</label>
            <input id="password" type="password" name="password" required autofocus autocomplete="current-password"
                class="w-full rounded-xl border border-slate-200 px-4 py-3 outline-none focus:ring-2 focus:ring-slate-900">
        </div>

        <button type="submit" class="w-full rounded-xl bg-slate-950 py-3.5 font-medium text-white hover:bg-slate-800 transition">
            Bestätigen
        </button>
    </form>
@endsection
