@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
                Anbieter hinzufügen
            </h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Einen zentralen Finanzanbieter für Konten, Karten und Kredite anlegen.
            </p>
        </div>

        <a href="{{ route('admin.providers.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800">
            ← Zurück
        </a>
    </div>

    @if ($errors->any())
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 dark:border-red-900/50 dark:bg-red-950/30 p-4">
            <ul class="text-sm text-red-700 dark:text-red-300 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST"
          action="{{ route('admin.providers.store') }}"
          class="space-y-6">
        @csrf

        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 space-y-5">

            <div>
                <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Name
                </label>
                <input
                    id="name"
                    name="name"
                    type="text"
                    value="{{ old('name') }}"
                    required
                    autofocus
                    class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white px-4 py-2.5 focus:ring-2 focus:ring-violet-500 focus:border-violet-500"
                    placeholder="z. B. Sparkasse"
                >
            </div>

            <div>
                <label for="slug" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Slug
                </label>
                <input
                    id="slug"
                    name="slug"
                    type="text"
                    value="{{ old('slug') }}"
                    class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white px-4 py-2.5"
                    placeholder="sparkasse"
                >
                <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                    Kann leer bleiben und wird automatisch erzeugt.
                </p>
            </div>

            <div>
                <label for="type" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Typ
                </label>
                <select
                    id="type"
                    name="type"
                    class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white px-4 py-2.5"
                >
                    @php($selectedType = old('type', 'other'))

                    <option value="bank" @selected($selectedType === 'bank')>Bank</option>
                    <option value="payment" @selected($selectedType === 'payment')>Zahlungsanbieter</option>
                    <option value="card" @selected($selectedType === 'card')>Kartenanbieter</option>
                    <option value="lender" @selected($selectedType === 'lender')>Kreditgeber</option>
                    <option value="other" @selected($selectedType === 'other')>Sonstiger Anbieter</option>
                </select>
            </div>

            <div>
                <label for="logo" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Logo
                </label>

                <select
                    id="logo"
                    name="logo"
                    class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white px-4 py-2.5"
                >
                    <option value="">Kein Logo – Emoji/Fallback verwenden</option>

                    @foreach ($providerLogos as $filename => $logoPath)
                        <option value="{{ $logoPath }}" @selected(old('logo') === $logoPath)>
                            {{ pathinfo($filename, PATHINFO_FILENAME) }}
                        </option>
                    @endforeach
                </select>

                <div class="mt-3 flex flex-wrap gap-3">
                    @foreach ($providerLogos as $filename => $logoPath)
                        <button
                            type="button"
                            data-logo="{{ $logoPath }}"
                            class="provider-logo-option w-16 h-16 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-950 p-2 hover:border-violet-500 hover:ring-2 hover:ring-violet-500/20 transition"
                            title="{{ pathinfo($filename, PATHINFO_FILENAME) }}"
                        >
                            <img src="{{ asset($logoPath) }}"
                                 alt="{{ pathinfo($filename, PATHINFO_FILENAME) }}"
                                 class="w-full h-full object-contain">
                        </button>
                    @endforeach
                </div>
            </div>

            <div>
                <label for="emoji" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Emoji-Fallback
                </label>
                <input
                    id="emoji"
                    name="emoji"
                    type="text"
                    value="{{ old('emoji', '🏦') }}"
                    maxlength="20"
                    class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white px-4 py-2.5"
                    placeholder="🏦"
                >
            </div>

            <div>
                <label for="color" class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                    Fallback-Farbe
                </label>
                <input
                    id="color"
                    name="color"
                    type="text"
                    value="{{ old('color') }}"
                    maxlength="7"
                    pattern="^#[0-9A-Fa-f]{6}$"
                    class="w-full rounded-xl border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-950 text-slate-900 dark:text-white px-4 py-2.5"
                    placeholder="#7C3AED"
                >
                <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                    Optional, z. B. <code>#7C3AED</code>.
                </p>
            </div>

            <label class="flex items-center gap-3 cursor-pointer">
                <input
                    type="checkbox"
                    name="is_active"
                    value="1"
                    @checked(old('is_active', true))
                    class="rounded border-slate-300 dark:border-slate-700 text-violet-600 focus:ring-violet-500"
                >
                <span class="text-sm font-medium text-slate-700 dark:text-slate-300">
                    Anbieter aktiv
                </span>
            </label>

        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.providers.index') }}"
               class="px-5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800">
                Abbrechen
            </a>

            <button
                type="submit"
                class="px-5 py-2.5 rounded-xl bg-violet-600 text-white text-sm font-semibold hover:bg-violet-700 transition"
            >
                Anbieter speichern
            </button>
        </div>
    </form>
</div>

<script>
document.querySelectorAll('.provider-logo-option').forEach(button => {
    button.addEventListener('click', () => {
        const select = document.getElementById('logo');
        select.value = button.dataset.logo;
        select.dispatchEvent(new Event('change'));
    });
});
</script>
@endsection
