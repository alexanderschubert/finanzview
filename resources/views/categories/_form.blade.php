{{--
    Gemeinsames Formular für "Neue Kategorie" und "Kategorie bearbeiten".

    Erwartet: $category (neu oder bestehend), $parentOptions, optional $childCount
--}}

@php
    $isEdit = $category->exists;

    $value = fn (string $field, $default = null) => old($field, $category->{$field} ?? $default);

    $types = [
        'expense' => 'Ausgabe',
        'income' => 'Einnahme',
        'both' => 'Beides',
    ];

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
            >@if (\App\Support\Symbols::svg($value('icon'))){!! \App\Support\Symbols::svg($value('icon'), 'w-10 h-10') !!}@elseif (\App\Support\Symbols::isSymbol($value('icon')))🏷️@else{{ $value('icon') ?: '🏷️' }}@endif</span>

            <p data-preview-name class="mt-3 text-lg font-semibold text-slate-900 dark:text-white">
                {{ $value('name') ?: 'Neue Kategorie' }}
            </p>
        </div>

        <div class="mt-6 space-y-4">
            {{-- Unterkategorie: Auswahl der übergeordneten Kategorie (eine Ebene). --}}
            @php $hasChildren = ($childCount ?? 0) > 0; @endphp

            <x-field label="Übergeordnete Kategorie" for="parent_id" error="parent_id"
                :hint="$hasChildren ? 'Diese Kategorie hat ' . $childCount . ' Unterkategorie' . ($childCount === 1 ? '' : 'n') . ' und kann deshalb keiner anderen untergeordnet werden.' : 'Optional – z. B. „Supermarkt“ unter „Lebensmittel“. Budgets und Filter der Hauptkategorie schließen Unterkategorien ein.'">
                <select id="parent_id" name="parent_id" class="fv-input" data-parent-select @disabled($hasChildren)>
                    <option value="" data-type="">Keine – Hauptkategorie</option>
                    @foreach ($parentOptions as $option)
                        <option value="{{ $option->id }}" data-type="{{ $option->type }}" @selected((string) $value('parent_id') === (string) $option->id)>
                            {{ $option->icon_text }} {{ $option->name }}
                        </option>
                    @endforeach
                </select>
            </x-field>

            <x-field label="Name" for="name" error="name">
                <input id="name" name="name" type="text" required maxlength="255" autofocus
                    value="{{ $value('name') }}" placeholder="z. B. Lebensmittel" class="fv-input">
            </x-field>

            <div data-type-group>
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

    <div class="fv-card p-5 sm:p-6">
        <x-symbol-picker :icon="$value('icon')" :color="$hasColor ? $color : null" />
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
    // Live-Vorschau von Name, Symbol und Farbe.
    (function () {
        const tile = document.querySelector('[data-preview-tile]');
        const name = document.querySelector('[data-preview-name]');
        const nameInput = document.getElementById('name');

        nameInput.addEventListener('input', () => name.textContent = nameInput.value || 'Neue Kategorie');

        // Unterkategorie: Die Art folgt der übergeordneten Kategorie („Beides“ erlaubt jede Art).
        const parentSelect = document.querySelector('[data-parent-select]');
        const typeGroup = document.querySelector('[data-type-group]');

        const syncParent = () => {
            const type = parentSelect.selectedOptions[0]?.dataset.type || '';
            const fixed = type !== '' && type !== 'both';

            typeGroup.hidden = fixed;

            if (fixed) {
                document.querySelector(`input[name="type"][value="${type}"]`).checked = true;
            }
        };

        parentSelect.addEventListener('change', syncParent);
        syncParent();

        // Vorschau oben folgt dem Symbol- und Farbwähler.
        document.querySelector('[data-symbol-picker]').addEventListener('symbolchange', event => {
            if (event.detail.svg) { tile.innerHTML = event.detail.svg; } else { tile.textContent = event.detail.icon || '🏷️'; }
            tile.style.backgroundColor = event.detail.color ? event.detail.color + '26' : '';
        });
    })();
</script>
