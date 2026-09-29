@extends('layouts.app')

@section('title', 'Tag bearbeiten – FinanzView')
@section('eyebrow', 'Tags')
@section('page_title', 'Tag bearbeiten')

@section('content')

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6">

    <x-back-link :href="route('tags.index')" label="Tags" />

    <x-page-header title="Tag bearbeiten" />

    <form method="POST" action="{{ route('tags.update', $tag) }}" class="fv-card p-5 sm:p-6 space-y-5">
        @csrf
        @method('PUT')

        <x-field label="Name" for="name" error="name" hint="Umbenennen gilt für alle Buchungen mit diesem Tag.">
            <input id="name" name="name" type="text" maxlength="{{ \App\Services\TagService::MAX_LENGTH }}" required value="{{ old('name', $tag->name) }}" class="fv-input">
        </x-field>

        <fieldset>
            <legend class="fv-label">Farbe</legend>
            <div class="flex flex-wrap gap-2">
                <label class="cursor-pointer" title="Automatisch">
                    <input type="radio" name="color" value="" class="sr-only peer" @checked(old('color', $tag->color) === null || old('color', $tag->color) === '')>
                    <span class="w-9 h-9 rounded-full flex items-center justify-center text-[11px] font-medium text-slate-500 bg-slate-100 dark:bg-white/10 ring-2 ring-transparent ring-offset-2 ring-offset-white dark:ring-offset-slate-900 peer-checked:ring-slate-400 peer-focus-visible:ring-emerald-500">Auto</span>
                </label>
                @foreach (\App\Models\Tag::COLORS as $hex => $label)
                    <label class="cursor-pointer" title="{{ $label }}">
                        <input type="radio" name="color" value="{{ $hex }}" class="sr-only peer" @checked(strtolower((string) old('color', $tag->color)) === $hex)>
                        <span class="block w-9 h-9 rounded-full ring-2 ring-transparent ring-offset-2 ring-offset-white dark:ring-offset-slate-900 peer-checked:ring-slate-400 peer-focus-visible:ring-emerald-500" style="background-color: {{ $hex }}"></span>
                        <span class="sr-only">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </fieldset>

        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
            <a href="{{ route('tags.index') }}" class="fv-btn fv-btn-secondary">Abbrechen</a>
            <button type="submit" class="fv-btn fv-btn-primary">Speichern</button>
        </div>
    </form>

    <form method="POST" action="{{ route('tags.destroy', $tag) }}" onsubmit="return confirm('Tag wirklich löschen? Die Buchungen bleiben erhalten.')" class="flex justify-center">
        @csrf
        @method('DELETE')
        <button type="submit" class="fv-btn text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10">
            <x-icon name="trash" class="w-4 h-4" />
            Tag löschen
        </button>
    </form>

</div>

@endsection
