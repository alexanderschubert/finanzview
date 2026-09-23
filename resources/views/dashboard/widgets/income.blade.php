@if($dashboardWidgets['income'])

    <x-stat label="Einnahmen" icon="trending-up" tone="positive" :hint="$currentMonth">
        {{ $monthlyIncome > 0 ? '+' : '' }}{{ number_format($monthlyIncome, 2, ',', '.') }} €
    </x-stat>

@endif
