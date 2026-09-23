@extends('layouts.app')

@section('title', 'Neue Buchung – FinanzView')
@section('eyebrow', 'Buchungen')
@section('page_title', 'Neue Buchung')

@section('content')

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-page-header title="Neue Buchung" subtitle="Erfasse eine Ausgabe, Einnahme oder Umbuchung." />

    @include('transactions._form', ['transaction' => new \App\Models\Transaction()])

</div>

@endsection
