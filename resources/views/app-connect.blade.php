@extends('layouts.guest')

@section('title', 'App verbinden')
@section('heading', 'App verbinden')
@section('intro', 'FinanzView für „' . $deviceName . '“ verbinden?')

@section('content')

    <p class="text-sm text-slate-600 dark:text-slate-300">
        Die App erhält Zugriff auf deine Konten und Buchungen. Du kannst die Verbindung jederzeit unter Einstellungen → Sicherheit trennen.
    </p>

    <form method="POST" action="{{ route('app.connect.approve') }}" class="mt-5 space-y-3">
        @csrf
        <input type="hidden" name="state" value="{{ $state }}">
        <input type="hidden" name="redirect_uri" value="{{ $redirectUri }}">
        <input type="hidden" name="device_name" value="{{ $deviceName }}">

        <button type="submit" class="fv-btn fv-btn-primary w-full">Verbinden</button>
        <a href="{{ route('dashboard') }}" class="fv-btn fv-btn-secondary w-full">Abbrechen</a>
    </form>

@endsection
