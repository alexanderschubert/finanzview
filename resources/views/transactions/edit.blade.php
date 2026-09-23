@extends('layouts.app')

@section('title', 'Buchung bearbeiten – FinanzView')
@section('eyebrow', 'Buchungen')
@section('page_title', 'Buchung bearbeiten')

@section('content')

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-page-header title="Buchung bearbeiten" :subtitle="'Erfasst am ' . $transaction->created_at?->format('d.m.Y')" />

    @if ($transaction->recurring_transaction_id)
        <div class="flex gap-3 rounded-2xl bg-slate-100 dark:bg-white/5 p-4 text-sm text-slate-600 dark:text-slate-300">
            <x-icon name="repeat" class="w-5 h-5" />
            <p>
                Diese Buchung wurde automatisch aus einer
                <a href="{{ route('recurring-transactions.show', $transaction->recurring_transaction_id) }}" class="fv-link">wiederkehrenden Buchung</a>
                erzeugt. Änderungen gelten nur für diese eine Buchung.
            </p>
        </div>
    @endif

    @include('transactions._form')

</div>

@endsection
