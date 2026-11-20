// Befehlspalette: Strg/⌘+K oder „/“ öffnet Suche und Kurzbefehle.
// Zusätzlich: „G“ gefolgt von D, B, K, P, R, M, E, Z springt direkt zu einer Seite.

const dialog = document.getElementById('command-palette');

if (dialog) {
    const input = dialog.querySelector('[data-palette-input]');
    const list = dialog.querySelector('[data-palette-list]');
    const items = JSON.parse(dialog.dataset.items || '[]');
    const searchUrl = dialog.dataset.searchUrl;
    const transactionsUrl = dialog.dataset.transactionsUrl;

    let visible = [];
    let active = 0;
    let remote = [];
    let timer = null;

    const isMac = /mac|iphone|ipad/i.test(navigator.platform || navigator.userAgent);
    document.querySelectorAll('[data-palette-kbd]').forEach((kbd) => (kbd.textContent = isMac ? '⌘K' : 'Strg K'));

    const isTyping = (element) =>
        element && (['INPUT', 'TEXTAREA', 'SELECT'].includes(element.tagName) || element.isContentEditable);

    const matches = (item, words) => {
        const haystack = `${item.label} ${item.group} ${item.keywords}`.toLowerCase();
        return words.every((word) => haystack.includes(word));
    };

    const render = () => {
        const query = input.value.trim().toLowerCase();
        const words = query.split(/\s+/).filter(Boolean);

        visible = items.filter((item) => matches(item, words)).slice(0, 12);

        if (query.length >= 2) {
            visible = [
                { label: `Buchungen durchsuchen: „${input.value.trim()}“`, group: 'Suche', url: `${transactionsUrl}?search=${encodeURIComponent(input.value.trim())}`, keys: '' },
                ...remote,
                ...visible,
            ];
        }

        active = Math.min(active, Math.max(visible.length - 1, 0));
        list.textContent = '';

        if (!visible.length) {
            const empty = document.createElement('li');
            empty.className = 'px-3 py-6 text-center text-sm text-slate-500 dark:text-slate-400';
            empty.textContent = 'Nichts gefunden.';
            list.appendChild(empty);
            return;
        }

        let group = null;

        visible.forEach((item, index) => {
            if (item.group !== group) {
                group = item.group;
                const heading = document.createElement('li');
                heading.className = 'px-3 pt-2 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-500';
                heading.setAttribute('role', 'presentation');
                heading.textContent = group;
                list.appendChild(heading);
            }

            const row = document.createElement('li');
            row.setAttribute('role', 'option');
            row.setAttribute('aria-selected', index === active ? 'true' : 'false');
            row.className = 'flex items-center gap-3 rounded-xl px-3 py-2 text-sm cursor-pointer text-slate-700 dark:text-slate-200 aria-selected:bg-emerald-500/10 aria-selected:text-emerald-800 dark:aria-selected:text-emerald-200';

            const label = document.createElement('span');
            label.className = 'flex-1 truncate';
            label.textContent = item.label;
            row.appendChild(label);

            if (item.keys) {
                const keys = document.createElement('span');
                keys.className = 'flex gap-1';
                item.keys.split(' ').forEach((key) => {
                    const kbd = document.createElement('kbd');
                    kbd.className = 'rounded-md border border-slate-200 dark:border-white/10 px-1.5 text-[11px] font-medium text-slate-500 dark:text-slate-400';
                    kbd.textContent = key;
                    keys.appendChild(kbd);
                });
                row.appendChild(keys);
            }

            row.addEventListener('mousemove', () => {
                if (active !== index) {
                    active = index;
                    list.querySelectorAll('[role=option]').forEach((element, position) => element.setAttribute('aria-selected', position === index ? 'true' : 'false'));
                }
            });
            row.addEventListener('click', () => choose(item));
            list.appendChild(row);
        });

        list.querySelector('[aria-selected=true]')?.scrollIntoView({ block: 'nearest' });
    };

    const choose = (item) => {
        dialog.close();

        if (item.action === 'quick-add') {
            document.querySelector('[data-quick-add]')?.click();
            return;
        }

        window.location.href = item.url;
    };

    const fetchRemote = () => {
        const query = input.value.trim();

        if (query.length < 2) {
            remote = [];
            render();
            return;
        }

        fetch(`${searchUrl}?q=${encodeURIComponent(query)}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
            .then((response) => (response.ok ? response.json() : []))
            .then((results) => {
                if (input.value.trim() === query) {
                    remote = results;
                    render();
                }
            })
            .catch(() => {});
    };

    const open = () => {
        if (!dialog.open) {
            dialog.showModal();
        }

        input.value = '';
        remote = [];
        active = 0;
        render();
        input.focus();
    };

    input.addEventListener('input', () => {
        active = 0;
        render();
        clearTimeout(timer);
        timer = setTimeout(fetchRemote, 180);
    });

    input.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            active = (active + (event.key === 'ArrowDown' ? 1 : -1) + visible.length) % Math.max(visible.length, 1);
            render();
        } else if (event.key === 'Enter' && visible[active]) {
            event.preventDefault();
            choose(visible[active]);
        }
    });

    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dialog.close();
        }
    });

    document.querySelectorAll('[data-palette-open]').forEach((button) => button.addEventListener('click', open));

    // „G“ + Buchstabe: direkt zu einer Seite.
    const jumps = { d: 'Übersicht', b: 'Buchungen', z: 'Zu prüfen', k: 'Konten', p: 'Monatsplan', r: 'Kredite', m: 'Monatsbericht', e: 'Einstellungen' };
    let waitingForJump = false;
    let jumpTimer = null;

    document.addEventListener('keydown', (event) => {
        const modifier = event.metaKey || event.ctrlKey;

        if (modifier && event.key.toLowerCase() === 'k') {
            event.preventDefault();
            open();
            return;
        }

        if (modifier || event.altKey || isTyping(event.target) || document.querySelector('dialog[open]')) {
            return;
        }

        if (event.key === '/') {
            event.preventDefault();
            open();
            return;
        }

        if (waitingForJump) {
            waitingForJump = false;
            clearTimeout(jumpTimer);
            const target = items.find((item) => item.label === jumps[event.key.toLowerCase()] && item.group !== 'Aktionen');

            if (target) {
                event.preventDefault();
                window.location.href = target.url;
            }
            return;
        }

        if (event.key.toLowerCase() === 'g') {
            waitingForJump = true;
            jumpTimer = setTimeout(() => (waitingForJump = false), 1200);
        }
    });
}
