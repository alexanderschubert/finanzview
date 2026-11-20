@props([
    'providers',
    'selected' => null,
])

@php
    $typeLabels = ['bank' => 'Banken', 'payment' => 'Zahlungsdienste', 'card' => 'Kartenanbieter', 'lender' => 'Kreditgeber', 'other' => 'Sonstige'];
    $order = array_keys($typeLabels);

    $sorted = $providers->sortBy(fn ($p) => [array_search($p->type, $order) === false ? 99 : array_search($p->type, $order), mb_strtolower($p->name)])->values();
@endphp

{{-- Anbieter mit Logo wählen. Das <select> bleibt als Grundlage bestehen (auch ohne JavaScript). --}}
<div data-provider-select {{ $attributes }}>
    <select id="provider_id" name="provider_id" class="fv-input" data-ps-select>
        <option value="">Kein Anbieter</option>
        @foreach ($sorted as $provider)
            <option value="{{ $provider->id }}" @selected((string) $selected === (string) $provider->id)>
                {{ $provider->emoji }} {{ $provider->name }}
            </option>
        @endforeach
    </select>

    <div data-ps-ui hidden class="space-y-3">
        <div class="flex items-center gap-3 rounded-2xl bg-slate-900/[0.03] dark:bg-white/5 px-3 py-2.5" data-ps-current>
            <span class="w-10 h-10 shrink-0 rounded-xl bg-white/90 dark:bg-white/90 flex items-center justify-center overflow-hidden text-lg" data-ps-current-logo aria-hidden="true">🏦</span>
            <span class="min-w-0 flex-1 truncate font-medium text-slate-900 dark:text-white" data-ps-current-name>Kein Anbieter</span>
            <button type="button" data-ps-clear class="text-xs text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white" hidden>Entfernen</button>
        </div>

        <div class="relative">
            <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
            <input type="search" data-ps-search placeholder="Bank suchen, z. B. Sparkasse, ING, N26 …" aria-label="Anbieter suchen" class="fv-input !pl-10" autocomplete="off">
        </div>

        <div class="grid max-h-64 grid-cols-3 gap-2 overflow-y-auto pr-1 sm:grid-cols-4" role="listbox" aria-label="Finanzanbieter">
            @foreach ($sorted as $provider)
                <button type="button" role="option" data-ps-option="{{ $provider->id }}" data-ps-name="{{ $provider->name }}" data-ps-search-text="{{ mb_strtolower($provider->name) }}"
                    @if ($provider->logo) data-ps-logo="{{ asset($provider->logo) }}" @endif
                    @if ($provider->emoji) data-ps-emoji="{{ $provider->emoji }}" @endif
                    aria-selected="{{ (string) $selected === (string) $provider->id ? 'true' : 'false' }}"
                    class="flex flex-col items-center gap-1.5 rounded-2xl border border-transparent p-2.5 text-center transition hover:bg-slate-900/5 dark:hover:bg-white/10 aria-selected:border-emerald-500 aria-selected:bg-emerald-500/10">
                    <span class="w-11 h-11 rounded-xl bg-white shadow-sm ring-1 ring-black/5 flex items-center justify-center overflow-hidden text-xl">
                        @if ($provider->logo)
                            <img src="{{ asset($provider->logo) }}" alt="" loading="lazy" class="h-3/4 w-3/4 object-contain">
                        @else
                            {{ $provider->emoji ?: '🏦' }}
                        @endif
                    </span>
                    <span class="line-clamp-2 text-[11px] leading-tight text-slate-700 dark:text-slate-300">{{ $provider->name }}</span>
                </button>
            @endforeach
        </div>

        <p data-ps-empty hidden class="text-sm text-slate-500 dark:text-slate-400">Kein passender Anbieter – in der Administration kannst du weitere anlegen.</p>
    </div>
</div>

@once
    <script>
        document.querySelectorAll('[data-provider-select]').forEach(root => {
            const select = root.querySelector('[data-ps-select]');
            const ui = root.querySelector('[data-ps-ui]');
            const search = root.querySelector('[data-ps-search]');
            const options = [...root.querySelectorAll('[data-ps-option]')];
            const logo = root.querySelector('[data-ps-current-logo]');
            const name = root.querySelector('[data-ps-current-name]');
            const clear = root.querySelector('[data-ps-clear]');
            const empty = root.querySelector('[data-ps-empty]');

            select.hidden = true;
            ui.hidden = false;

            const render = () => {
                const chosen = options.find(o => o.dataset.psOption === select.value);

                options.forEach(o => o.setAttribute('aria-selected', o === chosen ? 'true' : 'false'));
                name.textContent = chosen ? chosen.dataset.psName : 'Kein Anbieter';
                clear.hidden = !chosen;

                logo.textContent = '';
                if (chosen?.dataset.psLogo) {
                    const img = document.createElement('img');
                    img.src = chosen.dataset.psLogo;
                    img.alt = '';
                    img.className = 'h-3/4 w-3/4 object-contain';
                    logo.appendChild(img);
                } else {
                    logo.textContent = chosen?.dataset.psEmoji || '🏦';
                }
            };

            options.forEach(o => o.addEventListener('click', () => {
                select.value = o.dataset.psOption;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                render();
            }));

            clear.addEventListener('click', () => {
                select.value = '';
                select.dispatchEvent(new Event('change', { bubbles: true }));
                render();
            });

            search.addEventListener('input', () => {
                const q = search.value.trim().toLowerCase();
                let visible = 0;

                options.forEach(o => {
                    const show = q === '' || o.dataset.psSearchText.includes(q);
                    o.hidden = !show;
                    if (show) visible++;
                });

                empty.hidden = visible > 0;
            });

            render();
        });
    </script>
@endonce
