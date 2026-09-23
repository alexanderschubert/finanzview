@extends('layouts.guest')

@section('title', 'Passwort bestätigen')
@section('heading', 'Passwort bestätigen')
@section('intro', 'Dies ist ein geschützter Bereich. Bitte bestätige zuerst dein Passwort.')

@section('content')

    <form method="POST" action="{{ route('password.confirm.store') }}" class="space-y-4">
        @csrf

        <div>
            <label for="password" class="fv-label">Passwort</label>
            <x-password-input required autofocus />
        </div>

        <button type="submit" class="fv-btn fv-btn-primary w-full mt-2">
            <x-icon name="lock" class="w-[18px] h-[18px]" />
            Bestätigen
        </button>
    </form>

@endsection

@section('footer')
    <a href="{{ url()->previous() }}" class="fv-link inline-flex items-center gap-1.5">
        <x-icon name="arrow-left" class="w-4 h-4" />
        Zurück
    </a>
@endsection
