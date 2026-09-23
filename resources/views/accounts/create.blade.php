@extends('layouts.app')

@section('title', 'Neues Konto – FinanzView')
@section('eyebrow', 'Konten')
@section('page_title', 'Neues Konto')

@section('content')

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-page-header title="Neues Konto" subtitle="Bankkonto, Bargeld, PayPal oder Depot hinzufügen." />

    @include('accounts._form', ['account' => new \App\Models\Account()])

</div>

@endsection
