@extends('layouts.app')

@section('title', 'Kalender – FinanzView')
@section('eyebrow', 'Planung')
@section('page_title', 'Kalender')

@php
    $money = fn ($value) => number_format((float) $value, 2, ',', '.') . ' €';
    $moneyShort = fn ($value) => number_format((float) $value, 0, ',', '.') . ' €';

    $palette = ['#38bdf8', '#fb923c', '#a78bfa', '#34d399', '#fbbf24', '#f472b6', '#2dd4bf', '#f87171'];

    // Farbe je Eintrag: Einnahmen grün, Kredite violett, sonst nach Gruppe.
    $color = fn (array $entry) => match (true) {
        $entry['kind'] === 'income' => '#34d399',
        $entry['kind'] === 'loan' => '#a78bfa',
        default => $palette[crc32($entry['group']) % count($palette)],
    };

    $today = now();
    $isThisMonth = $today->isSameMonth($month);
    $lead = $month->copy()->startOfMonth()->dayOfWeekIso - 1;
    $count = $month->daysInMonth;
    $days = $calendar['days'];
    $hasAnything = ! empty($days) || ! empty($calendar['unscheduled']);
@endphp

@section('content')

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-page-header title="Kalender" subtitle="Fällige Zahlungen aus dem Monatsplan und den Krediten.">
        <x-month-switcher route="plan.calendar" :month="$monthKey" />
    </x-page-header>

    <div class="grid grid-cols-2 gap-3">
        <x-stat label="Einnahmen" icon="trending-up" tone="positive">{{ $money($calendar['totals']['income']) }}</x-stat>
        <x-stat label="Zahlungen" icon="trending-down" tone="negative">{{ $money($calendar['totals']['expense']) }}</x-stat>
    </div>

    @unless ($hasAnything)

        <div class="fv-card">
            <x-empty-state icon="calendar" title="Keine Zahlungen in diesem Monat" :href="route('plan.index', ['month' => $monthKey])" action="Monatsplan öffnen">
                Trage beim Posten im Monatsplan einen Zahlungstag ein, dann erscheint er hier.
            </x-empty-state>
        </div>

    @else

        {{-- MONATSANSICHT (ab Tablet) --}}

        <div class="hidden md:block fv-card p-3">
            <div class="grid grid-cols-7 gap-1.5 text-center text-[11px] font-semibold uppercase tracking-wide text-slate-400 dark:text-slate-500 pb-1.5">
                @foreach (['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'] as $name)
                    <div>{{ $name }}</div>
                @endforeach
            </div>

            <div class="grid grid-cols-7 gap-1.5">
                @for ($i = 0; $i < $lead; $i++)
                    <div class="min-h-[5.5rem]"></div>
                @endfor

                @for ($day = 1; $day <= $count; $day++)
                    @php
                        $entries = $days[$day] ?? [];
                        $isToday = $isThisMonth && $today->day === $day;
                    @endphp

                    <div class="min-h-[5.5rem] rounded-xl border p-1.5 {{ $isToday ? 'border-emerald-500 bg-emerald-500/10' : 'border-slate-900/5 dark:border-white/10 bg-slate-900/[0.02] dark:bg-white/[0.03]' }}">
                        <p class="text-xs tabular-nums {{ $isToday ? 'font-bold text-emerald-700 dark:text-emerald-300' : 'text-slate-500 dark:text-slate-400' }}">{{ $day }}</p>

                        @foreach ($entries as $entry)
                            <p class="mt-1 truncate rounded-md px-1.5 py-0.5 text-[11px] leading-tight text-slate-900 dark:text-white" style="background-color: {{ $color($entry) }}2e; border-left: 3px solid {{ $color($entry) }}" title="{{ $entry['title'] }} · {{ $money($entry['amount']) }}">
                                {{ $entry['title'] }} <span class="opacity-70 tabular-nums">{{ $moneyShort($entry['amount']) }}</span>
                            </p>
                        @endforeach
                    </div>
                @endfor
            </div>
        </div>


        {{-- LISTE (Handy) --}}

        <ul class="md:hidden fv-card divide-y divide-slate-100 dark:divide-white/5">
            @forelse ($days as $day => $entries)
                @php $date = $month->copy()->day($day); @endphp

                <li class="px-4 py-3">
                    <p class="text-xs font-semibold {{ $isThisMonth && $today->day === $day ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400' }}">
                        {{ $date->translatedFormat('D, j. F') }}
                    </p>

                    @foreach ($entries as $entry)
                        <div class="mt-1.5 flex items-center gap-2.5">
                            <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $color($entry) }}" aria-hidden="true"></span>
                            <span class="flex-1 min-w-0 truncate text-slate-900 dark:text-white">{{ $entry['title'] }}</span>
                            <span class="tabular-nums font-medium {{ $entry['kind'] === 'income' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-900 dark:text-white' }}">{{ $entry['kind'] === 'income' ? '+' : '−' }}{{ $money($entry['amount']) }}</span>
                        </div>
                    @endforeach
                </li>
            @empty
                <li class="px-4 py-3 text-sm text-slate-500 dark:text-slate-400">Keine Zahlungen mit festem Tag.</li>
            @endforelse
        </ul>


        {{-- OHNE FESTEN TAG --}}

        @if ($calendar['unscheduled'])
            <section>
                <h3 class="px-1 pb-2 text-[13px] font-semibold text-slate-500 dark:text-slate-400">Ohne festen Tag</h3>

                <ul class="fv-card divide-y divide-slate-100 dark:divide-white/5">
                    @foreach ($calendar['unscheduled'] as $entry)
                        <li class="flex items-center gap-2.5 px-4 py-3">
                            <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $color($entry) }}" aria-hidden="true"></span>
                            <span class="flex-1 min-w-0 truncate text-slate-900 dark:text-white">{{ $entry['title'] }} <span class="text-xs text-slate-400">· {{ $entry['group'] }}</span></span>
                            <span class="tabular-nums font-medium text-slate-900 dark:text-white">{{ $money($entry['amount']) }}</span>
                        </li>
                    @endforeach
                </ul>

                <p class="mt-2 px-1 text-xs text-slate-500 dark:text-slate-400">Beim Posten im Monatsplan lässt sich ein Zahlungstag eintragen.</p>
            </section>
        @endif

    @endunless

</div>

@endsection
