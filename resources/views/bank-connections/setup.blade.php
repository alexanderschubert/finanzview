@extends('layouts.app')

@section('title', 'Bank einrichten – FinanzView')
@section('eyebrow', 'Bankverbindungen')
@section('page_title', 'Einrichten')

@section('content')

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-back-link :href="route('bank-connections.index')" label="Bankverbindungen" />

    <x-page-header :title="$connection->name" subtitle="Schritt 2 von 3: TAN-Verfahren" />

    <x-flash />

    @if ($stage === 'pin')

        <form method="POST" action="{{ route('bank-connections.tan-modes', $connection) }}" class="fv-card p-5 sm:p-6 space-y-4" data-busy-form>
            @csrf
            <p class="text-sm text-slate-600 dark:text-slate-300">
                FinanzView fragt bei deiner Bank nach, welche TAN-Verfahren möglich sind (BLZ {{ $connection->bank_code }}).
            </p>

            @include('bank-connections._pin')

            <div class="flex justify-end">
                <button type="submit" class="fv-btn fv-btn-primary">Mit Bank verbinden</button>
            </div>
        </form>

    @elseif ($stage === 'tan_modes')

        <form method="POST" action="{{ route('bank-connections.tan-mode', $connection) }}" class="space-y-4" data-busy-form>
            @csrf

            <fieldset class="fv-card divide-y divide-slate-100 dark:divide-white/5 overflow-hidden">
                <legend class="sr-only">TAN-Verfahren</legend>
                @foreach (collect($modes)->sortByDesc('decoupled') as $index => $mode)
                    <label class="flex items-center gap-3 px-5 py-3.5 cursor-pointer hover:bg-slate-50 dark:hover:bg-white/5">
                        <input type="radio" name="tan_mode" value="{{ $mode['id'] }}" class="w-4 h-4 accent-emerald-600" @checked($loop->first) required>
                        <span class="flex-1">
                            <span class="block font-medium text-slate-900 dark:text-white">{{ $mode['name'] }}</span>
                            <span class="block text-[13px] text-slate-500 dark:text-slate-400">
                                {{ $mode['decoupled'] ? 'Freigabe in der App (empfohlen)' : 'TAN eingeben' }}
                            </span>
                        </span>
                    </label>
                @endforeach
            </fieldset>

            <p class="px-1 text-[13px] text-slate-500 dark:text-slate-400">Bei der Sparkasse ist das meist „pushTAN“ (Freigabe in der S-pushTAN-App).</p>

            <div class="flex justify-end">
                <button type="submit" class="fv-btn fv-btn-primary">Weiter</button>
            </div>
        </form>

    @elseif ($stage === 'tan_media')

        <form method="POST" action="{{ route('bank-connections.tan-mode', $connection) }}" class="fv-card p-5 sm:p-6 space-y-4" data-busy-form>
            @csrf
            <input type="hidden" name="tan_mode" value="{{ $selectedMode }}">

            <x-field label="Gerät für die Freigabe" for="tan_medium">
                <select id="tan_medium" name="tan_medium" class="fv-input" required>
                    @foreach ($media as $medium)
                        <option value="{{ $medium['name'] }}">{{ $medium['name'] }}@if ($medium['phone']) ({{ $medium['phone'] }})@endif</option>
                    @endforeach
                </select>
            </x-field>

            <div class="flex justify-end">
                <button type="submit" class="fv-btn fv-btn-primary">Weiter</button>
            </div>
        </form>

    @endif

</div>

@include('bank-connections._busy')

@endsection
