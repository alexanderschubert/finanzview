@extends('layouts.guest')

@section('title', 'Neues Passwort')
@section('heading', 'Neues Passwort')
@section('intro', 'Wähle ein neues Passwort für dein Konto.')

@section('content')

    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <label for="email" class="fv-label">E-Mail-Adresse</label>
            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autocomplete="username"
                class="fv-input">
        </div>

        <div>
            <label for="password" class="fv-label">Neues Passwort</label>
            <x-password-input autocomplete="new-password" required autofocus placeholder="Mindestens 8 Zeichen" />
        </div>

        <div>
            <label for="password_confirmation" class="fv-label">Passwort wiederholen</label>
            <x-password-input id="password_confirmation" name="password_confirmation" autocomplete="new-password" required />
        </div>

        <button type="submit" class="fv-btn fv-btn-primary w-full mt-2">
            Passwort speichern
        </button>
    </form>

@endsection
