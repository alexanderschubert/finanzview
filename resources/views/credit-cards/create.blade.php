@extends('layouts.app')

@section('title', 'Neue Kreditkarte – FinanzView')
@section('eyebrow', 'Konten')
@section('page_title', 'Neue Kreditkarte')

@section('content')

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-back-link :href="route('credit-cards.index')" label="Kreditkarten" />

    <x-page-header title="Neue Kreditkarte" subtitle="Saldo, Limit und Abrechnungstag hinterlegen." />

    @include('credit-cards._form', ['creditCard' => new \App\Models\CreditCard()])

</div>

@endsection
