@extends('layouts.app')

@section('title', 'Budget bearbeiten – FinanzView')
@section('eyebrow', 'Budgets')
@section('page_title', 'Budget bearbeiten')

@section('content')

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-page-header :title="$budget->name" subtitle="Budget bearbeiten">
        <a href="{{ route('budgets.show', $budget) }}" class="fv-btn fv-btn-secondary text-sm py-2.5">Details</a>
    </x-page-header>

    @if ($budget->end_date && $budget->end_date->isPast() && $budget->period !== 'custom')
        <div class="flex gap-3 rounded-2xl bg-amber-50 dark:bg-amber-500/10 p-4 text-sm text-amber-800 dark:text-amber-300">
            <x-icon name="alert" class="w-5 h-5" />
            <p>Dieses Budget ist am {{ $budget->end_date->format('d.m.Y') }} abgelaufen. Leere das Feld „Bis“, damit es wieder jeden {{ $budget->period === 'yearly' ? 'Jahr' : 'Monat' }} gilt.</p>
        </div>
    @endif

    @include('budgets._form')

</div>

@endsection
