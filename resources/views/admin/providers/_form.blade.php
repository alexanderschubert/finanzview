{{--
    Gemeinsames Formular für Finanzanbieter (anlegen und bearbeiten).

    Erwartet: $provider, $providerLogos (Dateiname => Pfad)
--}}

@php
    $isEdit = $provider->exists;

    $value = fn (string $field, $default = null) => old($field, $provider->{$field} ?? $default);

    $types = [
        'bank' => 'Bank',
        'payment' => 'Zahlungsdienst',
        'card' => 'Kartenanbieter',
        'lender' => 'Kreditgeber',
        'other' => 'Sonstiges',
    ];

    $color = $value('color');
    $hasColor = is_string($color) && preg_match('/^#[0-9a-fA-F]{6}$/', $color);
    $logo = $value('logo');
@endphp

<form method="POST" action="{{ $isEdit ? route('admin.providers.update', $provider) : route('admin.providers.store') }}" class="space-y-5">
    @csrf
    @if ($isEdit)
        @method('PATCH')
    @endif

    @if ($errors->any())
        <div class="flex gap-3 rounded-2xl bg-red-50 dark:bg-red-500/10 p-4 text-sm text-red-700 dark:text-red-300" role="alert">
            <x-icon name="alert" class="w-5 h-5" />
            <p>Bitte prüfe die markierten Angaben.</p>
        </div>
    @endif

    <div class="fv-card p-5 sm:p-6 space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <x-field label="Name" for="name" error="name">
                <input id="name" name="name" type="text" required maxlength="255" autofocus value="{{ $value('name') }}" placeholder="z. B. Sparkasse" class="fv-input">
            </x-field>

            <x-field label="Art" for="type" error="type">
                <select id="type" name="type" required class="fv-input">
                    @foreach ($types as $key => $label)
                        <option value="{{ $key }}" @selected($value('type', 'bank') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </x-field>
        </div>

        <x-field label="Kürzel (Slug)" for="slug" error="slug" :hint="$isEdit ? 'Eindeutiger Bezeichner, nur Kleinbuchstaben und Bindestriche.' : 'Leer lassen – wird aus dem Namen erzeugt.'">
            <input id="slug" name="slug" type="text" maxlength="255" value="{{ $value('slug') }}" placeholder="sparkasse" class="fv-input font-mono" @required($isEdit)>
        </x-field>
    </div>

    <div class="fv-card p-5 sm:p-6 space-y-4">
        <x-field label="Logo" for="logo" error="logo" hint="Dateien aus public/images/providers.">
            <select id="logo" name="logo" class="fv-input">
                <option value="">Kein Logo</option>
                @foreach ($providerLogos as $file => $path)
                    <option value="{{ $path }}" @selected($logo === $path)>{{ $file }}</option>
                @endforeach
            </select>
        </x-field>

        <div class="grid grid-cols-2 gap-4">
            <x-field label="Emoji (ohne Logo)" for="emoji" error="emoji">
                <input id="emoji" name="emoji" type="text" maxlength="20" value="{{ $value('emoji') }}" placeholder="🏦" class="fv-input text-xl">
            </x-field>

            <x-field label="Markenfarbe" for="color" error="color">
                <div class="flex items-center gap-2">
                    <input id="color" type="color" value="{{ $hasColor ? $color : '#3f3f45' }}"
                        oninput="this.nextElementSibling.value = this.value"
                        class="h-[46px] w-14 cursor-pointer rounded-xl border border-slate-200 dark:border-slate-700 bg-transparent p-1">
                    <input type="text" name="color" value="{{ $hasColor ? $color : '' }}" placeholder="#16a57a" maxlength="7" class="fv-input font-mono">
                </div>
            </x-field>
        </div>

        <label class="flex items-center justify-between gap-4 cursor-pointer border-t border-slate-100 dark:border-white/5 pt-4">
            <span>
                <span class="block font-medium text-slate-900 dark:text-white">Aktiv</span>
                <span class="block text-[13px] text-slate-500 dark:text-slate-400">Nur aktive Anbieter stehen in Formularen zur Auswahl</span>
            </span>
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" class="sr-only peer" @checked((bool) $value('is_active', true))>
            <span aria-hidden="true" class="relative inline-flex h-[31px] w-[51px] shrink-0 rounded-full bg-slate-200 dark:bg-slate-700 transition peer-checked:bg-emerald-500 peer-focus-visible:ring-2 peer-focus-visible:ring-emerald-500 after:absolute after:top-[2px] after:left-[2px] after:h-[27px] after:w-[27px] after:rounded-full after:bg-white after:shadow after:transition peer-checked:after:translate-x-[20px]"></span>
        </label>
    </div>

    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
        <a href="{{ route('admin.providers.index') }}" class="fv-btn fv-btn-secondary">Abbrechen</a>
        <button type="submit" class="fv-btn fv-btn-primary">{{ $isEdit ? 'Änderungen speichern' : 'Anbieter anlegen' }}</button>
    </div>
</form>
