// Buchungsliste: Detailspalte rechts (ab 1280 px Breite). Auf kleineren
// Bildschirmen führt ein Klick wie bisher zur Bearbeiten-Seite.

const panel = document.getElementById('tx-panel');

if (panel) {
    const wide = window.matchMedia('(min-width: 1280px)');
    const selected = ['bg-emerald-500/10', 'dark:bg-emerald-400/10'];
    const placeholder = panel.innerHTML;

    const mark = (link) => {
        document.querySelectorAll('[data-tx-panel][aria-current]').forEach((element) => {
            element.removeAttribute('aria-current');
            element.classList.remove(...selected);
        });

        link.setAttribute('aria-current', 'true');
        link.classList.add(...selected);
    };

    const bind = (link) => {
        const form = panel.querySelector('[data-panel-form]');

        if (!form) {
            return;
        }

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const error = form.querySelector('[data-panel-error]');
            const saved = form.querySelector('[data-panel-saved]');

            error.classList.add('hidden');

            const data = new FormData(form);
            data.append('_method', 'PATCH');

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: data,
                    credentials: 'same-origin',
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });

                if (response.status === 422) {
                    const payload = await response.json();

                    error.textContent = Object.values(payload.errors || {})[0]?.[0] || 'Das konnte nicht gespeichert werden.';
                    error.classList.remove('hidden');

                    return;
                }

                if (!response.ok) {
                    throw new Error(String(response.status));
                }

                const result = await response.json();

                // Kategorie in der Zeile aktualisieren (erster Teil der Zeile unter dem Titel).
                const details = link.querySelector('[data-tx-details]');
                const before = link.dataset.category || '';

                if (details) {
                    const parts = details.textContent.trim().split(' · ');
                    const next = result.category || 'Ohne Kategorie';

                    if (before && parts[0] === before) {
                        parts[0] = next;
                    } else if (parts[0] === 'Ohne Kategorie') {
                        parts[0] = next;
                    } else {
                        parts.unshift(next);
                    }

                    details.textContent = parts.join(' · ');
                }

                link.dataset.category = result.category || '';

                saved.classList.remove('opacity-0');
                setTimeout(() => saved.classList.add('opacity-0'), 1800);
            } catch (failure) {
                error.textContent = 'Keine Verbindung. Bitte später noch einmal versuchen.';
                error.classList.remove('hidden');
            }
        });
    };

    document.addEventListener('click', async (event) => {
        const link = event.target.closest('[data-tx-panel]');

        if (!link || !wide.matches || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey) {
            return;
        }

        event.preventDefault();
        mark(link);

        panel.innerHTML = '<p class="py-10 text-center text-sm text-slate-500">Lädt …</p>';

        try {
            const response = await fetch(link.dataset.txPanel, {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!response.ok) {
                throw new Error(String(response.status));
            }

            panel.innerHTML = await response.text();
            bind(link);
        } catch (failure) {
            window.location.href = link.href;
        }
    });

    // Beim Schmaler-Werden zurück auf den Ausgangszustand.
    wide.addEventListener('change', () => {
        if (!wide.matches) {
            panel.innerHTML = placeholder;
        }
    });
}
