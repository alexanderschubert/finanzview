@extends('layouts.app')

@section('title', 'Kredit bearbeiten – FinanzView')
@section('eyebrow', 'Kredite')
@section('page_title', 'Kredit bearbeiten')

@section('content')

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-page-header :title="$loan->name" subtitle="Kredit bearbeiten" />

    @include('loans._form')

</div>

@endsection
