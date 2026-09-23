@extends('layouts.app')

@section('title', 'Wiederkehrende Buchung bearbeiten – FinanzView')
@section('eyebrow', 'Wiederkehrend')
@section('page_title', 'Bearbeiten')

@section('content')

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-page-header :title="$recurringTransaction->description" subtitle="Wiederkehrende Buchung bearbeiten" />

    @include('recurring_transactions._form')

</div>

@endsection
