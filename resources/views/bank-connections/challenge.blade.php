@extends('layouts.app')

@section('title', 'Freigabe – FinanzView')
@section('eyebrow', 'Bankverbindungen')
@section('page_title', 'Freigabe')

@section('content')

<div class="max-w-md mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-10 space-y-6">

    <div class="text-center space-y-3">
        <span class="mx-auto w-16 h-16 rounded-2xl bg-teal-600 text-white flex items-center justify-center">
            <x-icon name="{{ $decoupled ? 'shield' : 'key' }}" class="w-8 h-8" />
        </span>
        <h2 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
            {{ $decoupled ? 'In der App freigeben' : 'TAN eingeben' }}
        </h2>
        <p class="text-[15px] text-slate-500 dark:text-slate-400">
            {{ $connection->name }} · {{ $operation === 'accounts' ? 'Konten abfragen' : 'Umsätze abrufen' }}
        </p>
    </div>

    <x-flash />

    @if (session('status'))
        <p class="rounded-2xl bg-slate-100 dark:bg-white/5 p-4 text-sm text-slate-600 dark:text-slate-300 text-center" role="status">{{ session('status') }}</p>
    @endif

    <div class="fv-card p-5 sm:p-6 space-y-4">
        @if ($challenge)
            <p class="text-sm text-slate-700 dark:text-slate-200 whitespace-pre-line">{{ $challenge }}</p>
        @endif

        @if ($tanMedium)
            <p class="text-[13px] text-slate-500 dark:text-slate-400">Gerät: {{ $tanMedium }}</p>
        @endif

        <form method="POST" action="{{ route('bank-connections.confirm') }}" class="space-y-4" id="confirm-form" data-decoupled="{{ $decoupled ? '1' : '0' }}">
            @csrf

            @if ($decoupled)
                <p class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400" data-waiting>
                    <span class="w-2 h-2 rounded-full bg-teal-500 animate-pulse" aria-hidden="true"></span>
                    Warte auf Freigabe in der App …
                </p>
                <button type="submit" class="fv-btn fv-btn-primary w-full">Ich habe freigegeben</button>
            @else
                <x-field label="TAN" for="tan" error="tan">
                    <input id="tan" name="tan" type="text" inputmode="numeric" autocomplete="one-time-code" required maxlength="50" class="fv-input text-center text-lg tracking-[0.3em]" autofocus>
                </x-field>
                <button type="submit" class="fv-btn fv-btn-primary w-full">Bestätigen</button>
            @endif
        </form>
    </div>

    <form method="POST" action="{{ route('bank-connections.cancel') }}" class="text-center">
        @csrf
        @method('DELETE')
        <button type="submit" class="fv-link text-sm">Abbrechen</button>
    </form>

</div>

<script>
    // pushTAN: regelmäßig nachfragen, ob in der App freigegeben wurde.
    (function () {
        const form = document.getElementById('confirm-form');
        if (!form || form.dataset.decoupled !== '1') return;

        const token = form.querySelector('input[name="_token"]').value;
        let attempts = 0;
        let busy = false;

        const poll = async () => {
            if (busy || attempts++ > 60) return;
            busy = true;

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await response.json().catch(() => ({}));

                if (data.redirect) {
                    window.location.assign(data.redirect);
                    return;
                }
            } catch (e) {
                // Netzwerkfehler: beim nächsten Durchlauf erneut versuchen.
            } finally {
                busy = false;
            }

            setTimeout(poll, 5000);
        };

        setTimeout(poll, 5000);

        form.addEventListener('submit', () => {
            attempts = 999;
            form.querySelector('button[type="submit"]').disabled = true;
        });
    })();
</script>

@endsection
