@extends('layouts.guest')

@section('title', 'Registrieren')
@section('heading', 'Konto erstellen')
@section('intro', 'Deine Finanzdaten bleiben privat und nur deinem Konto zugeordnet.')

@section('content')

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div>
            <label for="name" class="fv-label">Name</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name"
                placeholder="Max Mustermann" class="fv-input">
        </div>

        <div>
            <label for="email" class="fv-label">E-Mail-Adresse</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username"
                placeholder="name@beispiel.de" class="fv-input">
        </div>

        <div>
            <label for="password" class="fv-label">Passwort</label>
            <x-password-input autocomplete="new-password" required placeholder="Mindestens 8 Zeichen" />
        </div>

        <div>
            <label for="password_confirmation" class="fv-label">Passwort wiederholen</label>
            <x-password-input id="password_confirmation" name="password_confirmation" autocomplete="new-password" required />
        </div>

        <button type="submit" class="fv-btn fv-btn-primary w-full mt-2">
            Konto erstellen
        </button>
    </form>

@endsection

@section('footer')
    Bereits registriert?
    <a href="{{ route('login') }}" class="fv-link ml-1">Anmelden</a>
@endsection
