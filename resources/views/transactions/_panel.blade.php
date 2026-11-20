@php
    $isTransfer = $transaction->type === 'transfer';
    $isIncome = $transaction->type === 'income';
    $title = $transaction->payee?->name ?: ($transaction->merchant ?: $transaction->description);
@endphp

{{-- Detailspalte der Buchungsliste (wird per JavaScript geladen). --}}

<form method="POST" action="{{ route('transactions.quick-update', $transaction) }}" data-panel-form data-transaction="{{ $transaction->id }}" class="space-y-4">

    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="text-xs text-slate-500 dark:text-slate-400">{{ $transaction->transaction_date?->translatedFormat('j. F Y') }}</p>
            <h3 class="font-semibold text-slate-900 dark:text-white break-words">{{ $title }}</h3>
        </div>

        <a href="{{ route('transactions.edit', $transaction) }}" class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-slate-400 hover:text-slate-900 dark:hover:text-white hover:bg-slate-900/5 dark:hover:bg-white/10 transition" aria-label="Alle Felder bearbeiten">
            <x-icon name="pencil" class="w-4 h-4" />
        </a>
    </div>

    <p class="fv-display text-[32px] leading-tight font-extrabold tabular-nums {{ $isTransfer ? 'text-slate-900 dark:text-white' : ($isIncome ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-900 dark:text-white') }}">
        {{ $isTransfer ? '' : ($isIncome ? '+' : '−') }}{{ number_format((float) $transaction->amount, 2, ',', '.') }} €
    </p>

    <dl class="divide-y divide-slate-100 dark:divide-white/5 text-sm border-y border-slate-100 dark:border-white/5">
        <div class="flex justify-between gap-4 py-2.5">
            <dt class="text-slate-500 dark:text-slate-400">Konto</dt>
            <dd class="text-right font-medium text-slate-900 dark:text-white">
                {{ $transaction->account?->name ?? '–' }}@if ($isTransfer) → {{ $transaction->transferAccount?->name ?? '–' }}@endif
            </dd>
        </div>

        @if ($transaction->payee)
            <div class="flex justify-between gap-4 py-2.5">
                <dt class="text-slate-500 dark:text-slate-400">Empfänger</dt>
                <dd class="text-right font-medium text-slate-900 dark:text-white">{{ $transaction->payee->name }}</dd>
            </div>
        @endif

        @if ($transaction->description && $transaction->description !== $title)
            <div class="py-2.5">
                <dt class="text-slate-500 dark:text-slate-400">Buchungstext</dt>
                <dd class="mt-0.5 text-slate-900 dark:text-white break-words">{{ $transaction->description }}</dd>
            </div>
        @endif

        @if ($transaction->tags->isNotEmpty())
            <div class="flex flex-wrap items-center gap-1.5 py-2.5">
                @foreach ($transaction->tags as $tag)
                    <x-tag-chip :tag="$tag" />
                @endforeach
            </div>
        @endif
    </dl>

    @unless ($isTransfer)
        <div>
            <label for="panel-category" class="fv-label">Kategorie</label>
            <select id="panel-category" name="category_id" class="fv-input">
                <option value="">Ohne Kategorie</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected($transaction->category_id === $category->id)>{{ $category->icon }} {{ $category->display_name }}</option>
                @endforeach
            </select>
        </div>
    @endunless

    <div>
        <label for="panel-notes" class="fv-label">Notiz</label>
        <textarea id="panel-notes" name="notes" rows="3" maxlength="1000" placeholder="Notiz hinzufügen …" class="fv-input">{{ $transaction->notes }}</textarea>
    </div>

    <p class="hidden text-sm text-red-600 dark:text-red-400" data-panel-error role="alert"></p>

    <div class="flex items-center justify-between gap-3">
        <span class="text-[13px] text-emerald-600 dark:text-emerald-400 opacity-0 transition" data-panel-saved>Gespeichert</span>
        <button type="submit" class="fv-btn fv-btn-primary text-sm py-2">Speichern</button>
    </div>
</form>
