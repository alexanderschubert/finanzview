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

    @if (auth()->user()?->hasPasskeysEnabled())
        <div
            data-passkey-confirm
            data-options-url="{{ route('passkey.confirm-options') }}"
            data-verify-url="{{ route('passkey.confirm') }}"
            hidden
            class="mt-3"
        >
            <button type="button" class="fv-btn fv-btn-secondary w-full">
                <x-icon name="passkey" class="w-[18px] h-[18px]" />
                Mit Passkey bestätigen
            </button>
            <p data-passkey-error hidden class="mt-2 text-[13px] text-red-600 dark:text-red-400" role="alert"></p>
        </div>
    @endif

@endsection

@section('footer')
    <a href="{{ url()->previous() }}" class="fv-link inline-flex items-center gap-1.5">
        <x-icon name="arrow-left" class="w-4 h-4" />
        Zurück
    </a>
@endsection
