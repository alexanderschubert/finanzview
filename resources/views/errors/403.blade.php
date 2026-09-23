@extends('layouts.guest')

@section('title', 'Zugriff verweigert')
@section('heading', 'Kein Zugriff')
@section('intro', 'Du hast keine Berechtigung, diese Seite aufzurufen.')

@section('content')

    <div class="flex flex-col items-center text-center">
        <div class="w-14 h-14 rounded-2xl bg-slate-100 dark:bg-white/5 flex items-center justify-center text-slate-500 dark:text-slate-400">
            <x-icon name="shield" class="w-7 h-7" />
        </div>

        <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">
            Dieser Bereich ist nur für Administratoren verfügbar.
            Wende dich an die Person, die FinanzView betreibt, wenn du Zugriff brauchst.
        </p>

        <a href="{{ url('/') }}" class="fv-btn fv-btn-primary w-full mt-6">
            Zur Übersicht
        </a>
    </div>

@endsection
