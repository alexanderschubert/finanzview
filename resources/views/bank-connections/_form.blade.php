{{-- Erwartet: $connection, optional $accounts (beim Bearbeiten) --}}

@php
    $isEdit = $connection->exists;
    $value = fn (string $field) => old($field, $connection->{$field});
@endphp

<form method="POST" action="{{ $isEdit ? route('bank-connections.update', $connection) : route('bank-connections.store') }}" class="space-y-5">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="fv-card p-5 sm:p-6 space-y-4">
        <x-field label="Bezeichnung" for="name" error="name">
            <input id="name" name="name" type="text" maxlength="100" required value="{{ $value('name') }}" class="fv-input" placeholder="z. B. Girokonto Sparkasse">
        </x-field>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-field label="Bankleitzahl" for="bank_code" error="bank_code">
                <input id="bank_code" name="bank_code" type="text" inputmode="numeric" maxlength="8" required value="{{ $value('bank_code') }}" class="fv-input tabular-nums" placeholder="10050000">
            </x-field>

            <x-field label="FinTS-Adresse (PIN/TAN)" for="url" error="url" class="sm:col-span-2">
                <input id="url" name="url" type="url" required value="{{ $value('url') }}" class="fv-input" placeholder="https://…/fints30">
            </x-field>
        </div>

        <p class="text-[13px] text-slate-500 dark:text-slate-400">
            Die FinTS-Adresse deiner Bank steht in der Bankenliste der Deutschen Kreditwirtschaft (kommt mit der Produktregistrierung)
            oder auf der Website deiner Bank unter „FinTS“ bzw. „HBCI PIN/TAN“.
        </p>

        <x-field label="Anmeldename (Legitimations-ID)" for="username" error="username" hint="Wie beim Online-Banking. Wird verschlüsselt gespeichert.">
            <input id="username" name="username" type="text" maxlength="100" required value="{{ $value('username') }}" class="fv-input" autocomplete="username">
        </x-field>

        @if ($isEdit)
            <x-field label="Importieren in" for="account_id" error="account_id">
                <select id="account_id" name="account_id" class="fv-input">
                    <option value="">Kein Konto</option>
                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}" @selected((string) $value('account_id') === (string) $account->id)>{{ $account->name }}</option>
                    @endforeach
                </select>
            </x-field>
        @endif
    </div>

    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
        <a href="{{ route('bank-connections.index') }}" class="fv-btn fv-btn-secondary">Abbrechen</a>
        <button type="submit" class="fv-btn fv-btn-primary">{{ $isEdit ? 'Speichern' : 'Weiter' }}</button>
    </div>
</form>
