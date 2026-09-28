@extends('layouts.app')

@section('title', 'Regel bearbeiten – FinanzView')
@section('eyebrow', 'Kategorien')
@section('page_title', 'Regel bearbeiten')

@section('content')

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-back-link :href="route('category-rules.index')" label="Regeln" />

    <x-page-header title="Regel bearbeiten" />

    @include('category-rules._form')

    <form method="POST" action="{{ route('category-rules.destroy', $rule) }}" onsubmit="return confirm('Regel wirklich löschen?')" class="flex justify-center">
        @csrf
        @method('DELETE')
        <button type="submit" class="fv-btn text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10">
            <x-icon name="trash" class="w-4 h-4" />
            Regel löschen
        </button>
    </form>

</div>

@endsection
