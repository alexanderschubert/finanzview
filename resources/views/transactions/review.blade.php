@extends('layouts.app')

@section('title', 'Zu prüfen – FinanzView')
@section('eyebrow', 'Buchungen')
@section('page_title', 'Zu prüfen')

@php
    $money = fn ($amount) => number_format((float) $amount, 2, ',', '.') . ' €';
@endphp

@section('content')

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-page-header title="Zu prüfen" subtitle="Buchungen ohne Kategorie mit einem Klick zuordnen.">
        @if ($total > 0)
            <span class="fv-btn fv-btn-secondary text-sm py-2 cursor-default">{{ $total }} offen</span>
        @endif
    </x-page-header>

    <x-flash />

    @if ($errors->any())
        <div class="flex gap-3 rounded-2xl bg-red-50 dark:bg-red-500/10 p-4 text-sm text-red-700 dark:text-red-300" role="alert">
            <x-icon name="alert" class="w-5 h-5" />
            <p>{{ $errors->first() }}</p>
        </div>
    @endif


    {{-- UMBUCHUNGEN --}}

    @if ($transferCount > 0)
        <a href="{{ route('transactions.transfers') }}" class="fv-card flex items-center gap-4 p-4 hover:bg-slate-900/[0.03] dark:hover:bg-white/[0.04] transition">
            <span class="w-10 h-10 rounded-2xl bg-orange-500/15 text-orange-600 dark:text-orange-400 flex items-center justify-center">
                <x-icon name="arrows" class="w-5 h-5" />
            </span>
            <span class="flex-1 min-w-0">
                <span class="block font-medium text-slate-900 dark:text-white">{{ $transferCount === 1 ? '1 mögliche Umbuchung' : $transferCount . ' mögliche Umbuchungen' }}</span>
                <span class="block text-[13px] text-slate-500 dark:text-slate-400">Abbuchung und Gutschrift zwischen deinen Konten zusammenfassen.</span>
            </span>
            <x-icon name="chevron-right" class="w-4 h-4 text-slate-400" />
        </a>
    @endif


    {{-- OHNE KATEGORIE --}}

    @if ($items->isEmpty())

        <div class="fv-card">
            <x-empty-state icon="check-circle" title="Alles erledigt">
                Jede Buchung hat eine Kategorie.
            </x-empty-state>
        </div>

    @else

        <ul class="space-y-3">
            @foreach ($items as $item)
                @php
                    $isIncome = $item->type === 'income';
                    $options = $isIncome ? $incomeCategories : $expenseCategories;
                    $quick = $options->take(6);
                @endphp

                <li class="fv-card p-4 sm:p-5">
                    <div class="flex items-start gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-slate-900 dark:text-white break-words">{{ $item->payee?->name ?: ($item->merchant ?: $item->description) }}</p>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400 break-words">
                                {{ $item->transaction_date?->format('d.m.Y') }} · {{ $item->account?->name }}
                                @if ($item->description && $item->description !== ($item->payee?->name ?: $item->merchant)) · {{ \Illuminate\Support\Str::limit($item->description, 80) }} @endif
                            </p>
                        </div>

                        <p class="shrink-0 fv-display text-lg font-extrabold tabular-nums {{ $isIncome ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-900 dark:text-white' }}">
                            {{ $isIncome ? '+' : '−' }}{{ $money($item->amount) }}
                        </p>
                    </div>

                    <form method="POST" action="{{ route('transactions.review.assign', $item) }}" class="mt-3 space-y-3">
                        @csrf

                        <div class="flex flex-wrap gap-2">
                            @foreach ($quick as $category)
                                <button type="submit" name="category_id" value="{{ $category->id }}" class="rounded-full border border-slate-900/10 dark:border-white/10 bg-white/60 dark:bg-white/5 px-3 py-1.5 text-[13px] text-slate-700 dark:text-slate-200 hover:border-emerald-500 hover:bg-emerald-500/10 transition">
                                    {{ $category->icon_text }} {{ $category->display_name }}
                                </button>
                            @endforeach
                        </div>

                        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                            <select name="category_id" aria-label="Andere Kategorie" class="fv-input !py-2 sm:max-w-xs" onchange="if (this.value) this.form.requestSubmit()">
                                <option value="">Andere Kategorie …</option>
                                @foreach ($options as $category)
                                    <option value="{{ $category->id }}">{{ $category->icon_text }} {{ $category->display_name }}</option>
                                @endforeach
                            </select>

                            @if ($item->payee)
                                <label class="flex items-center gap-2 text-[13px] text-slate-500 dark:text-slate-400 cursor-pointer">
                                    <input type="checkbox" name="remember" value="1" checked class="rounded">
                                    Für „{{ $item->payee->name }}“ merken
                                </label>
                            @endif
                        </div>
                    </form>
                </li>
            @endforeach
        </ul>

        @if ($total > $limit)
            <p class="text-center text-sm text-slate-500 dark:text-slate-400">Die ersten {{ $limit }} von {{ $total }} Buchungen. Nach dem Zuordnen erscheinen die nächsten.</p>
        @endif

    @endif

</div>

@endsection
