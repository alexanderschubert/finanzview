@extends('layouts.app')

@section('title', 'Neues Budget – FinanzView')
@section('eyebrow', 'Planung')
@section('page_title', 'Neues Budget')

@section('content')

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-back-link :href="route('budgets.index')" label="Budgets" />

    <x-page-header title="Neues Budget" subtitle="Lege fest, wie viel du für bestimmte Kategorien ausgeben möchtest." />

    @include('budgets._form', ['budget' => new \App\Models\Budget()])

</div>

@endsection
