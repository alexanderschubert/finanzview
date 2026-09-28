{{--
    Gemeinsames Formular für neue und bestehende Kategorie-Regeln.

    Erwartet: $rule (neu oder bestehend), $categories
--}}

@php
    $isEdit = $rule->exists;

    $value = fn (string $field, $default = null) => old($field, $rule->{$field} ?? $default);

    $groups = [
        'expense' => 'Ausgaben',
        'income' => 'Einnahmen',
        'both' => 'Einnahmen und Ausgaben',
    ];
@endphp

<form method="POST" action="{{ $isEdit ? route('category-rules.update', $rule) : route('category-rules.store') }}" class="fv-card p-5 sm:p-6 space-y-4">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <x-field label="Wenn der Text enthält" for="pattern" error="pattern" hint="z. B. „Netflix“, „REWE“ oder „Miete“">
            <input id="pattern" name="pattern" type="text" maxlength="100" required value="{{ $value('pattern') }}" class="fv-input" autocomplete="off" @unless ($isEdit) autofocus @endunless>
        </x-field>

        <x-field label="dann Kategorie" for="rule_category_id" error="category_id">
            <select id="rule_category_id" name="category_id" required class="fv-input">
                <option value="">Kategorie auswählen</option>
                @foreach ($groups as $type => $label)
                    @php $options = $categories->where('type', $type); @endphp
                    @continue($options->isEmpty())
                    <optgroup label="{{ $label }}">
                        @foreach ($options as $category)
                            <option value="{{ $category->id }}" @selected((string) $value('category_id') === (string) $category->id)>{{ $category->icon }} {{ $category->name }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </x-field>
    </div>

    <x-field label="Suchen in" for="match_field" error="match_field">
        <select id="match_field" name="match_field" class="fv-input">
            @foreach (\App\Models\CategoryRule::FIELDS as $field => $label)
                <option value="{{ $field }}" @selected($value('match_field', 'any') === $field)>{{ $label }}</option>
            @endforeach
        </select>
    </x-field>

    <div class="divide-y divide-slate-100 dark:divide-white/5 rounded-xl bg-slate-50 dark:bg-white/5">
        <label class="flex items-center justify-between gap-4 px-4 py-3 cursor-pointer">
            <span>
                <span class="block text-sm font-medium text-slate-900 dark:text-white">Aktiv</span>
                <span class="block text-[13px] text-slate-500 dark:text-slate-400">Bei neuen Buchungen und beim CSV-Import anwenden.</span>
            </span>
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" class="sr-only peer" @checked((bool) $value('is_active', true))>
            <span aria-hidden="true" class="relative inline-flex h-[31px] w-[51px] shrink-0 rounded-full bg-slate-200 dark:bg-slate-700 transition peer-checked:bg-emerald-500 peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-500 after:absolute after:top-[2px] after:left-[2px] after:h-[27px] after:w-[27px] after:rounded-full after:bg-white after:shadow after:transition peer-checked:after:translate-x-[20px]"></span>
        </label>

        <label class="flex items-center justify-between gap-4 px-4 py-3 cursor-pointer">
            <span>
                <span class="block text-sm font-medium text-slate-900 dark:text-white">Auf bestehende Buchungen anwenden</span>
                <span class="block text-[13px] text-slate-500 dark:text-slate-400">Nur Buchungen ohne Kategorie – vorhandene Zuordnungen bleiben.</span>
            </span>
            <input type="checkbox" name="apply" value="1" class="sr-only peer" @checked(old('apply', ! $isEdit))>
            <span aria-hidden="true" class="relative inline-flex h-[31px] w-[51px] shrink-0 rounded-full bg-slate-200 dark:bg-slate-700 transition peer-checked:bg-emerald-500 peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-500 after:absolute after:top-[2px] after:left-[2px] after:h-[27px] after:w-[27px] after:rounded-full after:bg-white after:shadow after:transition peer-checked:after:translate-x-[20px]"></span>
        </label>
    </div>

    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
        @if ($isEdit)
            <a href="{{ route('category-rules.index') }}" class="fv-btn fv-btn-secondary">Abbrechen</a>
        @endif
        <button type="submit" class="fv-btn fv-btn-primary">{{ $isEdit ? 'Speichern' : 'Regel anlegen' }}</button>
    </div>
</form>
