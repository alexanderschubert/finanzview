{{--
    Gemeinsames Formular für "Neue Kategorie" und "Kategorie bearbeiten".

    Erwartet: $category (neu oder bestehend)
--}}

@php
    $isEdit = $category->exists;

    $value = fn (string $field, $default = null) => old($field, $category->{$field} ?? $default);

    $types = [
        'expense' => 'Ausgabe',
        'income' => 'Einnahme',
        'both' => 'Beides',
    ];

    $emojiSuggestions = ['🛒', '🏠', '🚗', '⛽', '🍽️', '☕', '🎬', '🎮', '🛍️', '👕', '💡', '📱', '🛡️', '❤️', '💊', '✈️', '🎁', '🐶', '👶', '📚', '💰', '💶', '📈', '↩️', '🏦', '📦'];

    $colorSuggestions = ['#16a57a', '#1f5fa8', '#5b3fa8', '#c2410c', '#b91c1c', '#be185d', '#0e7490', '#8a6516', '#3f3f45'];

    $color = $value('color');
    $hasColor = is_string($color) && preg_match('/^#[0-9a-fA-F]{6}$/', $color) && strtolower($color) !== '#f1f5f9';
@endphp

<form
    id="category-form"
    method="POST"
    action="{{ $isEdit ? route('categories.update', $category) : route('categories.store') }}"
    class="space-y-5"
>
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    @if ($errors->any())
        <div class="flex gap-3 rounded-2xl bg-red-50 dark:bg-red-500/10 p-4 text-sm text-red-700 dark:text-red-300" role="alert">
            <x-icon name="alert" class="w-5 h-5" />
            <p>Bitte prüfe die markierten Angaben.</p>
        </div>
    @endif


    {{-- VORSCHAU, NAME, ART --}}

    <div class="fv-card p-5 sm:p-6">

        <div class="flex flex-col items-center text-center">
            <span
                data-preview-tile
                class="w-20 h-20 rounded-[22px] flex items-center justify-center text-4xl bg-slate-100 dark:bg-white/5"
                @if ($hasColor) style="background-color: {{ $color }}26" @endif
            >{{ $value('icon') ?: '🏷️' }}</span>

            <p data-preview-name class="mt-3 text-lg font-semibold text-slate-900 dark:text-white">
                {{ $value('name') ?: 'Neue Kategorie' }}
            </p>
        </div>

        <div class="mt-6 space-y-4">
            <x-field label="Name" for="name" error="name">
                <input id="name" name="name" type="text" required maxlength="255" autofocus
                    value="{{ $value('name') }}" placeholder="z. B. Lebensmittel" class="fv-input">
            </x-field>

            <div>
                <span class="fv-label">Verwendet für</span>
                <div class="grid grid-cols-3 gap-1 rounded-xl bg-slate-100 dark:bg-white/5 p-1" role="radiogroup" aria-label="Verwendet für">
                    @foreach ($types as $key => $label)
                        <label class="cursor-pointer rounded-lg py-2 text-center text-sm font-medium text-slate-600 dark:text-slate-300 transition has-[:checked]:bg-white has-[:checked]:text-slate-900 has-[:checked]:shadow-sm dark:has-[:checked]:bg-slate-700 dark:has-[:checked]:text-white has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-emerald-500">
                            <input type="radio" name="type" value="{{ $key }}" class="sr-only" @checked($value('type', 'expense') === $key)>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                @error('type')
                    <p class="mt-1.5 text-[13px] text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

    </div>


    {{-- SYMBOL UND FARBE --}}

    <div class="fv-card p-5 sm:p-6 space-y-5">

        <x-field label="Symbol" for="icon" error="icon" hint="Tippe ein Emoji an oder gib ein eigenes ein.">
            <input id="icon" name="icon" type="text" maxlength="20" value="{{ $value('icon') }}" placeholder="🏷️" class="fv-input text-xl">
        </x-field>

        <div class="grid grid-cols-9 sm:grid-cols-13 gap-1.5 -mt-2" aria-label="Emoji-Vorschläge">
            @foreach ($emojiSuggestions as $emoji)
                <button type="button" data-emoji="{{ $emoji }}"
                    class="aspect-square rounded-lg text-xl hover:bg-slate-100 dark:hover:bg-white/10 transition">{{ $emoji }}</button>
            @endforeach
        </div>

        <div>
            <span class="fv-label">Farbe</span>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" data-color=""
                    class="w-8 h-8 rounded-full border-2 border-dashed border-slate-300 dark:border-slate-600 text-slate-400 flex items-center justify-center"
                    aria-label="Keine Farbe" title="Keine Farbe">
                    <x-icon name="x" class="w-4 h-4" />
                </button>

                @foreach ($colorSuggestions as $suggestion)
                    <button type="button" data-color="{{ $suggestion }}"
                        class="w-8 h-8 rounded-full ring-offset-2 ring-offset-white dark:ring-offset-slate-900 transition {{ $hasColor && strtolower($color) === $suggestion ? 'ring-2 ring-slate-900 dark:ring-white' : '' }}"
                        style="background-color: {{ $suggestion }}" aria-label="Farbe {{ $suggestion }}"></button>
                @endforeach

                <label class="relative w-8 h-8 rounded-full overflow-hidden cursor-pointer bg-[conic-gradient(red,yellow,lime,cyan,blue,magenta,red)]" title="Eigene Farbe">
                    <input type="color" value="{{ $hasColor ? $color : '#16a57a' }}" class="absolute inset-0 opacity-0 cursor-pointer" data-color-picker>
                    <span class="sr-only">Eigene Farbe</span>
                </label>
            </div>
            <input type="hidden" name="color" value="{{ $hasColor ? $color : '' }}" data-color-value>
        </div>

    </div>


    {{-- BESCHREIBUNG UND STATUS --}}

    <div class="fv-card p-5 sm:p-6 space-y-4">

        <x-field label="Beschreibung" for="description" error="description">
            <textarea id="description" name="description" rows="2" placeholder="Optional" class="fv-input">{{ $value('description') }}</textarea>
        </x-field>

        @if ($isEdit)
            <label class="flex items-center justify-between gap-4 cursor-pointer">
                <span>
                    <span class="block font-medium text-slate-900 dark:text-white">Kategorie aktiv</span>
                    <span class="block text-[13px] text-slate-500 dark:text-slate-400">Inaktive Kategorien stehen bei neuen Buchungen nicht zur Auswahl</span>
                </span>
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" class="sr-only peer" @checked((bool) $value('is_active', true))>
                <span aria-hidden="true" class="relative inline-flex h-[31px] w-[51px] shrink-0 rounded-full bg-slate-200 dark:bg-slate-700 transition peer-checked:bg-emerald-500 peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-500 after:absolute after:top-[2px] after:left-[2px] after:h-[27px] after:w-[27px] after:rounded-full after:bg-white after:shadow after:transition peer-checked:after:translate-x-[20px]"></span>
            </label>
        @endif

    </div>


    {{-- AKTIONEN --}}

    <div class="flex flex-col-reverse sm:flex-row sm:items-center gap-3">
        @if ($isEdit)
            <button type="submit" form="delete-category-form" class="fv-btn text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 sm:mr-auto">
                <x-icon name="trash" class="w-4 h-4" />
                Löschen
            </button>
        @else
            <span class="hidden sm:block sm:mr-auto"></span>
        @endif

        <a href="{{ route('categories.index') }}" class="fv-btn fv-btn-secondary">Abbrechen</a>

        <button type="submit" class="fv-btn fv-btn-primary">
            {{ $isEdit ? 'Änderungen speichern' : 'Kategorie anlegen' }}
        </button>
    </div>

</form>

@if ($isEdit)
    <form
        id="delete-category-form"
        method="POST"
        action="{{ route('categories.destroy', $category) }}"
        class="hidden"
        onsubmit="return confirm('Kategorie „{{ addslashes($category->name) }}“ löschen? Wenn es bereits Buchungen damit gibt, wird sie nur deaktiviert.');"
    >
        @csrf
        @method('DELETE')
    </form>
@endif


<script>
    // Live-Vorschau, Emoji- und Farbauswahl.
    (function () {
        const tile = document.querySelector('[data-preview-tile]');
        const name = document.querySelector('[data-preview-name]');
        const nameInput = document.getElementById('name');
        const iconInput = document.getElementById('icon');
        const colorValue = document.querySelector('[data-color-value]');
        const colorPicker = document.querySelector('[data-color-picker]');
        const swatches = document.querySelectorAll('[data-color]');

        nameInput.addEventListener('input', () => name.textContent = nameInput.value || 'Neue Kategorie');
        iconInput.addEventListener('input', () => tile.textContent = iconInput.value || '🏷️');

        document.querySelectorAll('[data-emoji]').forEach(button => {
            button.addEventListener('click', () => {
                iconInput.value = button.dataset.emoji;
                tile.textContent = button.dataset.emoji;
            });
        });

        function setColor(hex) {
            colorValue.value = hex;
            tile.style.backgroundColor = hex ? hex + '26' : '';

            swatches.forEach(swatch => {
                const active = swatch.dataset.color && swatch.dataset.color === hex;
                swatch.classList.toggle('ring-2', active);
                swatch.classList.toggle('ring-slate-900', active);
                swatch.classList.toggle('dark:ring-white', active);
            });
        }

        swatches.forEach(swatch => swatch.addEventListener('click', () => setColor(swatch.dataset.color)));
        colorPicker.addEventListener('input', () => setColor(colorPicker.value));
    })();
</script>
