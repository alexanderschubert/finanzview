@extends('layouts.app')

@section('title', 'Kreditkarte bearbeiten – FinanzView')
@section('eyebrow', 'Kreditkarten')
@section('page_title', 'Kreditkarte bearbeiten')

@section('content')

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-page-header :title="$creditCard->name" subtitle="Kreditkarte bearbeiten" />

    @include('credit-cards._form')

</div>

@endsection
