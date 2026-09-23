@extends('layouts.guest')

@section('title', 'Passwort vergessen')
@section('heading', 'Passwort vergessen?')
@section('intro', 'Gib deine E-Mail-Adresse ein. Wir senden dir einen Link zum Zurücksetzen.')

@section('content')

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <div>
            <label for="email" class="fv-label">E-Mail-Adresse</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                placeholder="name@beispiel.de" class="fv-input">
        </div>

        <button type="submit" class="fv-btn fv-btn-primary w-full mt-2">
            <x-icon name="mail" class="w-[18px] h-[18px]" />
            Link senden
        </button>
    </form>

@endsection

@section('footer')
    <a href="{{ route('login') }}" class="fv-link inline-flex items-center gap-1.5">
        <x-icon name="arrow-left" class="w-4 h-4" />
        Zurück zur Anmeldung
    </a>
@endsection
