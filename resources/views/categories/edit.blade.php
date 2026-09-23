@extends('layouts.app')

@section('title', 'Kategorie bearbeiten – FinanzView')
@section('eyebrow', 'Kategorien')
@section('page_title', 'Kategorie bearbeiten')

@section('content')

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-page-header :title="$category->name" subtitle="Kategorie bearbeiten">
        <a href="{{ route('transactions.index', ['category_id' => $category->id]) }}" class="fv-btn fv-btn-secondary text-sm py-2.5">
            <x-icon name="arrows" class="w-4 h-4" />
            Buchungen
        </a>
    </x-page-header>

    @include('categories._form')

</div>

@endsection
