@extends('layouts.app')

@section('title', 'Neue wiederkehrende Buchung – FinanzView')
@section('eyebrow', 'Wiederkehrend')
@section('page_title', 'Neu')

@section('content')

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-page-header title="Wiederkehrende Buchung" subtitle="Miete, Abos, Versicherungen oder Gehalt automatisch buchen." />

    @include('recurring_transactions._form', ['recurringTransaction' => new \App\Models\RecurringTransaction()])

</div>

@endsection
