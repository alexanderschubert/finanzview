@props([
    'icon' => null,
    'color' => null,
    'fallback' => '🏷️',
])

@php
    /*
     * Gemeinsame Auswahl für Symbol (Emoji) und Farbe – für Kategorien und
     * Plan-Gruppen. Felder: `icon` und `color`. Bei Änderungen sendet das
     * Element das Ereignis „symbolchange“ (detail: icon, color).
     */
    $sets = [
        'Haushalt' => ['🏠', '🛋️', '💡', '🔌', '🚿', '🧹', '🧺', '🔧', '🪴', '🛏️', '📶', '🔥', '💧', '🗑️', '🔑', '🏡'],
        'Essen' => ['🛒', '🍽️', '☕', '🍕', '🍔', '🥗', '🍺', '🍷', '🥐', '🍎', '🛍️', '🧁', '🍣', '🥩', '🥛', '🍫'],
        'Mobilität' => ['🚗', '⛽', '🚌', '🚆', '🚲', '✈️', '🅿️', '🛵', '🚕', '🛠️', '🧳', '⛴️', '🏍️', '🚦', '🔋', '🛞'],
        'Freizeit' => ['🎬', '🎮', '🎵', '📚', '⚽', '🏋️', '🎨', '🎟️', '🏖️', '🎲', '📷', '🎧', '🧘', '🏕️', '🎤', '🎭'],
        'Gesundheit' => ['💊', '🩺', '🦷', '👓', '❤️', '💆', '🧴', '🏥', '🩹', '🧬', '🏃', '🥦'],
        'Finanzen' => ['💰', '💶', '📈', '🏦', '💳', '🧾', '🛡️', '📊', '🪙', '💼', '🏷️', '↩️', '🧮', '📉', '🤝', '🏛️'],
        'Familie' => ['👶', '🐶', '🐱', '🎁', '🎓', '🧸', '👕', '👟', '💍', '🎂', '🎒', '🧒', '👨‍👩‍👧', '🌷'],
        'Sonstiges' => ['📦', '📱', '💻', '📞', '✉️', '🔔', '⭐', '🌍', '♻️', '🧰', '🖨️', '📌', '🎯', '🚀', '🛎️', '❓'],
    ];

    $palette = [
        '#16a57a', '#0e9f6e', '#0e7490', '#0284c7', '#1f5fa8', '#4f46e5', '#5b3fa8', '#a21caf',
        '#be185d', '#e11d48', '#b91c1c', '#c2410c', '#d97706', '#8a6516', '#65a30d', '#3f3f45',
    ];

    $hasColor = is_string($color) && preg_match('/^#[0-9a-fA-F]{6}$/', $color) && strtolower($color) !== '#f1f5f9';
@endphp

<div {{ $attributes->merge(['class' => 'space-y-5']) }} data-symbol-picker data-fallback="{{ $fallback }}" data-lucide-url="{{ asset('symbols/lucide.json') }}">

    <div class="flex items-center gap-4">
        <span
            data-sp-tile
            class="w-16 h-16 shrink-0 rounded-[20px] flex items-center justify-center text-3xl bg-slate-100 dark:bg-white/5"
            @if ($hasColor) style="background-color: {{ $color }}26; box-shadow: inset 0 0 0 1.5px {{ $color }}55" @endif
            aria-hidden="true"
        >@if (\App\Support\Symbols::svg($icon)){!! \App\Support\Symbols::svg($icon, 'w-8 h-8') !!}@elseif (\App\Support\Symbols::isSymbol($icon)){{ $fallback }}@else{{ filled($icon) ? $icon : $fallback }}@endif</span>

        <div class="min-w-0 flex-1">
            <label for="icon" class="fv-label">Symbol</label>
            <input id="icon" name="icon" type="text" maxlength="{{ \App\Support\Symbols::MAX_LENGTH }}" value="{{ $icon }}" placeholder="{{ $fallback }}" data-sp-icon class="fv-input text-xl" autocomplete="off">
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Wähle unten ein Emoji oder ein Symbol (Reiter „Symbole“ mit Suche) oder füge ein eigenes Emoji ein.</p>
        </div>
    </div>

    <div>
        <div class="flex flex-wrap gap-1.5" role="tablist" aria-label="Symbol-Themen">
            @foreach (array_keys($sets) as $i => $label)
                <button type="button" role="tab" data-sp-tab="{{ $i }}" aria-selected="{{ $i === 0 ? 'true' : 'false' }}"
                    class="rounded-full px-3 py-1 text-xs font-medium transition bg-slate-900/5 text-slate-600 hover:bg-slate-900/10 dark:bg-white/10 dark:text-slate-300 dark:hover:bg-white/15 aria-selected:bg-emerald-600 aria-selected:text-white dark:aria-selected:bg-emerald-500">
                    {{ $label }}
                </button>
            @endforeach
            <button type="button" role="tab" data-sp-tab="{{ count($sets) }}" data-sp-lucide-tab aria-selected="false"
                class="rounded-full px-3 py-1 text-xs font-medium transition bg-slate-900/5 text-slate-600 hover:bg-slate-900/10 dark:bg-white/10 dark:text-slate-300 dark:hover:bg-white/15 aria-selected:bg-emerald-600 aria-selected:text-white dark:aria-selected:bg-emerald-500">
                Symbole
            </button>
        </div>

        @foreach ($sets as $label => $emojis)
            <div data-sp-set="{{ $loop->index }}" @if (! $loop->first) hidden @endif class="mt-3 grid grid-cols-8 sm:grid-cols-12 gap-1" role="tabpanel" aria-label="{{ $label }}">
                @foreach ($emojis as $emoji)
                    <button type="button" data-sp-emoji="{{ $emoji }}"
                        class="aspect-square rounded-xl text-xl hover:bg-slate-900/5 dark:hover:bg-white/10 transition">{{ $emoji }}</button>
                @endforeach
            </div>
        @endforeach

        <div data-sp-set="{{ count($sets) }}" hidden class="mt-3" role="tabpanel" aria-label="Symbole">
            <input type="search" data-sp-search class="fv-input" placeholder="Symbol suchen, z. B. Auto, Haus, Strom, Urlaub" autocomplete="off">
            <p data-sp-status class="mt-2 text-xs text-slate-500 dark:text-slate-400">Wird geladen …</p>
            <div data-sp-grid class="mt-2 grid grid-cols-8 sm:grid-cols-12 gap-1 max-h-72 overflow-y-auto"></div>
            <button type="button" data-sp-more hidden class="mt-2 fv-link text-sm">Mehr anzeigen</button>
        </div>
    </div>

    <div>
        <span class="fv-label">Farbe</span>
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" data-sp-color=""
                class="w-8 h-8 rounded-full border-2 border-dashed border-slate-300 dark:border-slate-600 text-slate-400 flex items-center justify-center"
                aria-label="Keine Farbe" title="Keine Farbe">
                <x-icon name="x" class="w-4 h-4" />
            </button>

            @foreach ($palette as $swatch)
                <button type="button" data-sp-color="{{ $swatch }}"
                    class="w-8 h-8 rounded-full ring-offset-2 ring-offset-white dark:ring-offset-slate-900 transition {{ $hasColor && strtolower($color) === $swatch ? 'ring-2 ring-slate-900 dark:ring-white' : '' }}"
                    style="background-color: {{ $swatch }}" aria-label="Farbe {{ $swatch }}"></button>
            @endforeach

            <label class="relative w-8 h-8 rounded-full overflow-hidden cursor-pointer bg-[conic-gradient(red,yellow,lime,cyan,blue,magenta,red)]" title="Eigene Farbe">
                <input type="color" value="{{ $hasColor ? $color : '#16a57a' }}" class="absolute inset-0 opacity-0 cursor-pointer" data-sp-picker>
                <span class="sr-only">Eigene Farbe</span>
            </label>
        </div>
        <input type="hidden" name="color" value="{{ $hasColor ? $color : '' }}" data-sp-color-value>
    </div>
</div>

@once
    <script>
        (function () {
            document.querySelectorAll('[data-symbol-picker]').forEach(root => {
                const tile = root.querySelector('[data-sp-tile]');
                const iconInput = root.querySelector('[data-sp-icon]');
                const colorValue = root.querySelector('[data-sp-color-value]');
                const picker = root.querySelector('[data-sp-picker]');
                const swatches = root.querySelectorAll('[data-sp-color]');
                const fallback = root.dataset.fallback;
                const PREFIX = 'lucide:';
                let lucide = null;      // { name: { n: shapes, t: text } }
                let lucideLoading = null;
                let results = [];
                let shown = 0;
                const PAGE = 240;

                const svgFor = (name, cls) => {
                    const entry = lucide && lucide[name];
                    if (!entry) return '';
                    const inner = entry.n.map(([tag, attrs]) =>
                        '<' + tag + Object.entries(attrs).map(([k, v]) => ' ' + k + '="' + String(v).replace(/"/g, '&quot;') + '"').join('') + '/>').join('');
                    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="' + (cls || 'w-6 h-6') + '" aria-hidden="true">' + inner + '</svg>';
                };

                const loadLucide = () => {
                    if (lucide) return Promise.resolve(lucide);
                    lucideLoading ??= fetch(root.dataset.lucideUrl)
                        .then(r => r.json())
                        .then(data => { lucide = data.icons; return lucide; })
                        .catch(() => { lucideLoading = null; return null; });
                    return lucideLoading;
                };

                const norm = text => text.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/ß/g, 'ss');

                const paint = value => {
                    if (value && value.startsWith(PREFIX)) {
                        const svg = svgFor(value.slice(PREFIX.length), 'w-8 h-8');
                        if (svg) { tile.innerHTML = svg; return svg; }
                        tile.textContent = fallback;
                        return '';
                    }
                    tile.textContent = value || fallback;
                    return '';
                };

                let currentSvg = '';

                const emit = () => root.dispatchEvent(new CustomEvent('symbolchange', {
                    bubbles: true,
                    detail: { icon: iconInput.value, color: colorValue.value, svg: currentSvg },
                }));

                const setIcon = value => {
                    iconInput.value = value;
                    currentSvg = paint(value);
                    emit();
                };

                const setColor = hex => {
                    colorValue.value = hex;
                    tile.style.backgroundColor = hex ? hex + '26' : '';
                    tile.style.boxShadow = hex ? 'inset 0 0 0 1.5px ' + hex + '55' : '';

                    swatches.forEach(swatch => {
                        const active = swatch.dataset.spColor && swatch.dataset.spColor === hex;
                        swatch.classList.toggle('ring-2', !!active);
                        swatch.classList.toggle('ring-slate-900', !!active);
                        swatch.classList.toggle('dark:ring-white', !!active);
                    });
                    emit();
                };

                iconInput.addEventListener('input', () => {
                    const value = iconInput.value;
                    if (value.startsWith(PREFIX) && !lucide) {
                        loadLucide().then(() => { if (iconInput.value === value) setIcon(value); });
                    }
                    setIcon(value);
                });

                const grid = root.querySelector('[data-sp-grid]');
                const status = root.querySelector('[data-sp-status]');
                const more = root.querySelector('[data-sp-more]');
                const search = root.querySelector('[data-sp-search]');

                const renderPage = () => {
                    const slice = results.slice(shown, shown + PAGE);
                    grid.insertAdjacentHTML('beforeend', slice.map(name =>
                        '<button type="button" data-sp-lucide="' + name + '" title="' + name + '" class="aspect-square rounded-xl flex items-center justify-center text-slate-700 dark:text-slate-200 hover:bg-slate-900/5 dark:hover:bg-white/10 transition">' + svgFor(name, 'w-5 h-5') + '</button>').join(''));
                    shown += slice.length;
                    more.hidden = shown >= results.length;
                    status.textContent = results.length + (results.length === 1 ? ' Symbol' : ' Symbole') + (results.length > shown ? ', ' + shown + ' angezeigt' : '');
                };

                const runSearch = () => {
                    if (!lucide) return;
                    const terms = norm(search.value).split(/\s+/).filter(Boolean);
                    results = Object.keys(lucide).filter(name => {
                        const hay = norm(name.replace(/-/g, ' ') + ' ' + lucide[name].t);
                        return terms.every(term => hay.includes(term));
                    });
                    grid.innerHTML = '';
                    shown = 0;
                    renderPage();
                };

                grid.addEventListener('click', event => {
                    const button = event.target.closest('[data-sp-lucide]');
                    if (button) setIcon(PREFIX + button.dataset.spLucide);
                });
                more.addEventListener('click', renderPage);
                let timer;
                search.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(runSearch, 150); });

                const lucideTab = root.querySelector('[data-sp-lucide-tab]');
                lucideTab.addEventListener('click', () => loadLucide().then(data => {
                    if (!data) { status.textContent = 'Symbole konnten nicht geladen werden.'; return; }
                    if (!grid.children.length) runSearch();
                }));
                root.querySelectorAll('[data-sp-emoji]').forEach(b => b.addEventListener('click', () => setIcon(b.dataset.spEmoji)));
                swatches.forEach(s => s.addEventListener('click', () => setColor(s.dataset.spColor)));
                picker.addEventListener('input', () => setColor(picker.value));

                root.querySelectorAll('[data-sp-tab]').forEach(tab => tab.addEventListener('click', () => {
                    root.querySelectorAll('[data-sp-tab]').forEach(t => t.setAttribute('aria-selected', t === tab ? 'true' : 'false'));
                    root.querySelectorAll('[data-sp-set]').forEach(set => set.hidden = set.dataset.spSet !== tab.dataset.spTab);
                }));
            });
        })();
    </script>
@endonce
