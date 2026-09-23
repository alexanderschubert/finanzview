@extends('layouts.app')

@section('title', 'Neue Kategorie – FinanzView')
@section('eyebrow', 'Kategorien')
@section('page_title', 'Neue Kategorie')

@section('content')

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-page-header title="Neue Kategorie" subtitle="Ordne Buchungen zu, um zu sehen, wofür dein Geld ausgegeben wird." />

    @include('categories._form', ['category' => new \App\Models\Category()])

</div>

@endsection
