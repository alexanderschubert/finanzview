@extends('layouts.app')

@section('title', 'Bank verbinden – FinanzView')
@section('eyebrow', 'Bankverbindungen')
@section('page_title', 'Bank verbinden')

@section('content')

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-back-link :href="route('bank-connections.index')" label="Bankverbindungen" />

    <x-page-header title="Bank verbinden" subtitle="Schritt 1 von 3: Zugangsdaten" />

    @include('bank-connections._form')

</div>

@endsection
