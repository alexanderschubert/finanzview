@extends('layouts.app')

@section('title', 'Konto bearbeiten – FinanzView')
@section('eyebrow', 'Konten')
@section('page_title', 'Konto bearbeiten')

@section('content')

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-page-header :title="$account->name" subtitle="Konto bearbeiten">
        <a href="{{ route('transactions.index', ['account_id' => $account->id]) }}" class="fv-btn fv-btn-secondary text-sm py-2.5">
            <x-icon name="arrows" class="w-4 h-4" />
            Buchungen
        </a>
    </x-page-header>

    @include('accounts._form')

</div>

@endsection
