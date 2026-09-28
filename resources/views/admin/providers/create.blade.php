@extends('layouts.app')

@section('title', 'Neuer Anbieter – FinanzView')
@section('eyebrow', 'Administration')
@section('page_title', 'Neuer Anbieter')

@section('content')

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-back-link :href="route('admin.providers.index')" label="Finanzanbieter" />

    <x-page-header title="Neuer Anbieter" />

    @include('admin.providers._form')

</div>

@endsection
