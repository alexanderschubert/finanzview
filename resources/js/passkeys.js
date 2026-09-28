/*
 * Passkeys (WebAuthn) im Browser.
 *
 * Die Server-Seite liefert Laravel Fortify (laravel/passkeys). Hier
 * werden nur die Optionen abgeholt, der Browser-Dialog geöffnet und
 * das Ergebnis zurückgeschickt.
 *
 *   [data-passkey-login]     Anmelden (Login-Seite)
 *   [data-passkey-confirm]   Passwort per Passkey bestätigen
 *   [data-passkey-register]  Formular: neuen Passkey hinzufügen
 */

const supported = () =>
    window.isSecureContext && typeof window.PublicKeyCredential === 'function' && !!navigator.credentials;

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';


/* Base64URL <-> ArrayBuffer */

const toBuffer = (value) => {
    const base64 = value.replace(/-/g, '+').replace(/_/g, '/').padEnd(Math.ceil(value.length / 4) * 4, '=');
    return Uint8Array.from(atob(base64), (c) => c.charCodeAt(0)).buffer;
};

const toBase64Url = (buffer) =>
    btoa(String.fromCharCode(...new Uint8Array(buffer))).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');


const creationOptions = (options) => {
    if (typeof PublicKeyCredential.parseCreationOptionsFromJSON === 'function') {
        return PublicKeyCredential.parseCreationOptionsFromJSON(options);
    }

    return {
        ...options,
        challenge: toBuffer(options.challenge),
        user: { ...options.user, id: toBuffer(options.user.id) },
        excludeCredentials: (options.excludeCredentials ?? []).map((c) => ({ ...c, id: toBuffer(c.id) })),
    };
};

const requestOptions = (options) => {
    if (typeof PublicKeyCredential.parseRequestOptionsFromJSON === 'function') {
        return PublicKeyCredential.parseRequestOptionsFromJSON(options);
    }

    return {
        ...options,
        challenge: toBuffer(options.challenge),
        allowCredentials: (options.allowCredentials ?? []).map((c) => ({ ...c, id: toBuffer(c.id) })),
    };
};

const credentialJson = (credential) => {
    if (typeof credential.toJSON === 'function') {
        try {
            return credential.toJSON();
        } catch (e) {
            // ältere Browser: manuell umwandeln
        }
    }

    const response = credential.response;
    const json = {
        id: credential.id,
        rawId: toBase64Url(credential.rawId),
        type: credential.type,
        authenticatorAttachment: credential.authenticatorAttachment ?? undefined,
        clientExtensionResults: credential.getClientExtensionResults?.() ?? {},
        response: { clientDataJSON: toBase64Url(response.clientDataJSON) },
    };

    if (response.attestationObject) {
        json.response.attestationObject = toBase64Url(response.attestationObject);
        json.response.transports = response.getTransports?.() ?? [];
    } else {
        json.response.authenticatorData = toBase64Url(response.authenticatorData);
        json.response.signature = toBase64Url(response.signature);
        json.response.userHandle = response.userHandle ? toBase64Url(response.userHandle) : null;
    }

    return json;
};


/* Server */

class HttpError extends Error {
    constructor(status, message) {
        super(message);
        this.status = status;
    }
}

const request = async (url, method = 'GET', body = null) => {
    const response = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: body ? JSON.stringify(body) : null,
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        const message = data.errors ? Object.values(data.errors).flat()[0] : data.message;
        throw new HttpError(response.status, message || 'Unbekannter Fehler');
    }

    return data;
};

const friendlyError = (error) => {
    if (error?.name === 'NotAllowedError' || error?.name === 'AbortError') {
        return 'Vorgang abgebrochen oder Zeit abgelaufen.';
    }

    if (error?.name === 'InvalidStateError') {
        return 'Dieser Passkey ist bereits für dein Konto gespeichert.';
    }

    if (error?.status === 429) {
        return 'Zu viele Versuche. Bitte warte kurz.';
    }

    if (error?.status === 422) {
        return 'Der Passkey ist unbekannt oder ungültig. Melde dich mit Passwort an und richte ihn unter Einstellungen → Sicherheit neu ein.';
    }

    return 'Das hat nicht geklappt. Bitte erneut versuchen.';
};

const showError = (element, message) => {
    const target = element.querySelector('[data-passkey-error]') ?? document.querySelector('[data-passkey-error]');

    if (target) {
        target.textContent = message;
        target.hidden = !message;
    }
};


/* Anmelden bzw. bestätigen */

let conditionalAbort = null;

const verify = async (element, { conditional = false } = {}) => {
    const { optionsUrl, verifyUrl } = element.dataset;

    const { options } = await request(optionsUrl);

    conditionalAbort?.abort();
    conditionalAbort = conditional ? new AbortController() : null;

    const credential = await navigator.credentials.get({
        publicKey: requestOptions(options),
        ...(conditional ? { mediation: 'conditional', signal: conditionalAbort.signal } : {}),
    });

    const remember = document.querySelector('input[name="remember"]')?.checked ?? false;

    const result = await request(verifyUrl, 'POST', { credential: credentialJson(credential), remember });

    window.location.assign(result.redirect || '/');
};

const initVerify = (element) => {
    if (!supported()) {
        return;
    }

    element.hidden = false;

    element.querySelector('button')?.addEventListener('click', async (event) => {
        const button = event.currentTarget;
        button.disabled = true;
        showError(element, '');

        try {
            await verify(element);
        } catch (error) {
            showError(element, friendlyError(error));
            button.disabled = false;
        }
    });

    /*
     * Login-Seite: Passkeys zusätzlich im Autofill des E-Mail-Felds
     * anbieten (bedingte Mediation), falls der Browser das kann.
     */
    if (element.hasAttribute('data-passkey-autofill') && PublicKeyCredential.isConditionalMediationAvailable) {
        PublicKeyCredential.isConditionalMediationAvailable().then((available) => {
            if (available) {
                verify(element, { conditional: true }).catch(() => {});
            }
        });
    }
};


/* Neuen Passkey hinzufügen */

const guessDeviceName = () => {
    const ua = navigator.userAgent;

    if (/iPhone/.test(ua)) return 'iPhone';
    if (/iPad/.test(ua) || (/Macintosh/.test(ua) && navigator.maxTouchPoints > 1)) return 'iPad';
    if (/Macintosh/.test(ua)) return 'Mac';
    if (/Android/.test(ua)) return 'Android';
    if (/Windows/.test(ua)) return 'Windows';

    return 'Passkey';
};

const initRegister = (form) => {
    if (!supported()) {
        form.querySelector('[data-passkey-unsupported]')?.removeAttribute('hidden');
        form.querySelector('[data-passkey-fields]')?.setAttribute('hidden', '');
        return;
    }

    const nameInput = form.querySelector('input[name="name"]');

    if (nameInput && !nameInput.value) {
        nameInput.value = guessDeviceName();
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const button = form.querySelector('button[type="submit"]');
        button.disabled = true;
        showError(form, '');

        try {
            const { options } = await request(form.dataset.optionsUrl);

            const credential = await navigator.credentials.create({ publicKey: creationOptions(options) });

            await request(form.action, 'POST', {
                name: nameInput?.value || guessDeviceName(),
                credential: credentialJson(credential),
            });

            window.location.assign(form.dataset.successUrl);
        } catch (error) {
            // Passwort-Bestätigung abgelaufen: erst bestätigen lassen.
            if (error?.status === 423 && form.dataset.confirmUrl) {
                window.location.assign(form.dataset.confirmUrl);
                return;
            }

            showError(form, friendlyError(error));
            button.disabled = false;
        }
    });
};


document.querySelectorAll('[data-passkey-login], [data-passkey-confirm]').forEach(initVerify);
document.querySelectorAll('[data-passkey-register]').forEach(initRegister);
