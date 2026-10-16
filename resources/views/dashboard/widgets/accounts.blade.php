@if($dashboardWidgets['accounts'] && $accounts->isNotEmpty())

    {{-- KONTEN als Wallet-Karten --}}

    @php
        $typeLabels = [
            'checking' => 'Girokonto', 'savings' => 'Sparkonto', 'credit_card' => 'Kreditkarte', 'paypal' => 'PayPal',
            'cash' => 'Bargeld', 'investment' => 'Investment', 'loan' => 'Kredit', 'other' => 'Sonstiges',
        ];

        $typeColors = [
            'checking' => '#0b7155', 'savings' => '#1f5fa8', 'credit_card' => '#3f3f45', 'paypal' => '#123a86',
            'cash' => '#8a6516', 'investment' => '#5b3fa8', 'loan' => '#9f2d2d', 'other' => '#3f3f45',
        ];
    @endphp

    <x-section title="Konten" subtitle="Aktuelle Kontostände" :href="route('accounts.index')">
        <div class="grid grid-cols-1 min-[480px]:grid-cols-2 gap-3">
            @foreach ($accounts->take(4) as $account)
                @php
                    $iban = preg_replace('/\s+/', '', (string) $account->iban);
                    $number = $iban !== '' ? '•••• ' . substr($iban, -4) : null;
                @endphp

                <a href="{{ route('accounts.edit', $account) }}" class="group block">
                    <x-wallet-card
                        :color="$account->color"
                        :fallback-color="$account->provider?->color ?: ($typeColors[$account->type] ?? '#3f3f45')"
                        :title="$account->name"
                        :subtitle="$account->institution ?: ($typeLabels[$account->type] ?? 'Sonstiges')"
                        amount-label="Kontostand"
                        :amount="number_format($account->calculated_balance, 2, ',', '.') . ' ' . ($account->currency === 'EUR' ? '€' : $account->currency)"
                        :number="$number"
                        :provider="$account->provider"
                        class="!p-4 transition group-hover:-translate-y-0.5 group-hover:shadow-xl"
                    />
                </a>
            @endforeach
        </div>

        @if ($accounts->count() > 4)
            <p class="mt-3 text-center text-xs text-slate-500 dark:text-slate-400">
                und {{ $accounts->count() - 4 }} weitere Konten
            </p>
        @endif
    </x-section>

@endif
