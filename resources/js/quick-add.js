// Schnellerfassung: Dialog „Neue Buchung“ (Taste N oder Knopf „+ Buchung“).
// Ohne JavaScript führt der Knopf wie bisher zur Seite „Neue Buchung“.

const dialog = document.getElementById('quick-add');

function toast(message) {
    const element = document.createElement('div');
    element.textContent = message;
    element.setAttribute('role', 'status');
    element.className = 'fixed bottom-24 lg:bottom-8 left-1/2 -translate-x-1/2 z-[100] rounded-full bg-slate-900 text-white dark:bg-white dark:text-slate-900 px-4 py-2 text-sm font-medium shadow-xl';
    document.body.appendChild(element);
    setTimeout(() => element.remove(), 2200);
}

if (dialog) {
    const body = dialog.querySelector('[data-quick-body]');

    const isTyping = (element) =>
        element && (['INPUT', 'TEXTAREA', 'SELECT'].includes(element.tagName) || element.isContentEditable);

    const normalizeAmount = (value) => {
        let text = value.trim().replace(/[\s€]/g, '');

        if (text.includes(',')) {
            text = text.replace(/\./g, '').replace(',', '.');
        }

        return text;
    };

    const bind = () => {
        const form = body.querySelector('[data-quick-form]');

        if (!form) {
            return;
        }

        const category = form.querySelector('[data-quick-category]');
        const types = form.querySelectorAll('[data-quick-type]');

        // Nur Kategorien zeigen, die zur gewählten Art passen.
        const filterCategories = () => {
            const type = form.querySelector('[data-quick-type]:checked')?.value;

            category.querySelectorAll('option[data-type]').forEach((option) => {
                const fits = option.dataset.type === type || option.dataset.type === 'both';

                option.hidden = !fits;
                option.disabled = !fits;
            });

            if (category.selectedOptions[0]?.disabled) {
                category.value = '';
            }
        };

        types.forEach((radio) => radio.addEventListener('change', filterCategories));
        filterCategories();

        body.querySelectorAll('[data-quick-close]').forEach((button) =>
            button.addEventListener('click', () => dialog.close()));

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            form.querySelectorAll('[data-quick-error]').forEach((element) => {
                element.textContent = '';
                element.classList.add('hidden');
            });

            const data = new FormData(form);
            data.set('amount', normalizeAmount(String(data.get('amount') || '')));

            const submit = form.querySelector('[data-quick-submit]');
            submit.disabled = true;

            const show = (field, message) => {
                const element = form.querySelector(`[data-quick-error="${field}"]`) || form.querySelector('[data-quick-error="general"]');

                element.textContent = message;
                element.classList.remove('hidden');
            };

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: data,
                    credentials: 'same-origin',
                    redirect: 'manual',
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });

                // Erfolg: Laravel leitet auf die Buchungsliste weiter.
                if (response.type === 'opaqueredirect' || response.ok) {
                    dialog.close();
                    toast('Buchung gespeichert');
                    setTimeout(() => window.location.reload(), 500);

                    return;
                }

                if (response.status === 422) {
                    const payload = await response.json();

                    Object.entries(payload.errors || {}).forEach(([field, messages]) => show(field, messages[0]));
                } else {
                    show('general', 'Die Buchung konnte nicht gespeichert werden.');
                }
            } catch (error) {
                show('general', 'Keine Verbindung. Bitte später noch einmal versuchen.');
            } finally {
                submit.disabled = false;
            }
        });
    };

    const open = async () => {
        if (dialog.open || document.querySelector('dialog[open]')) {
            return;
        }

        body.innerHTML = '<p class="py-10 text-center text-sm text-slate-500">Lädt …</p>';
        dialog.showModal();

        try {
            const response = await fetch(dialog.dataset.quickUrl, {
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!response.ok) {
                throw new Error(String(response.status));
            }

            body.innerHTML = await response.text();
            bind();
            body.querySelector('[data-quick-amount]')?.focus();
        } catch (error) {
            dialog.close();
            window.location.href = dialog.dataset.fallbackUrl;
        }
    };

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('[data-quick-add]');

        if (!trigger || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey) {
            return;
        }

        event.preventDefault();
        open();
    });

    // Klick auf den abgedunkelten Hintergrund schließt den Dialog.
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) {
            dialog.close();
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'n' || event.metaKey || event.ctrlKey || event.altKey || isTyping(document.activeElement)) {
            return;
        }

        event.preventDefault();
        open();
    });
}
