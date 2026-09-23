@extends('layouts.app')

@section('title', 'Neuer Kredit – FinanzView')
@section('eyebrow', 'Kredite')
@section('page_title', 'Neuer Kredit')

@section('content')

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-page-header title="Neuer Kredit" subtitle="Kredit, Finanzierung oder Ratenzahlung erfassen." />

    @include('loans._form', ['loan' => new \App\Models\Loan()])

</div>

@endsection
