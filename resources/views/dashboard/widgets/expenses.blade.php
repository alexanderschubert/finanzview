@if($dashboardWidgets['expenses'])

    <x-stat label="Ausgaben" icon="trending-down" tone="negative" :hint="$currentMonth">
        {{ $monthlyExpense > 0 ? '−' : '' }}{{ number_format($monthlyExpense, 2, ',', '.') }} €
    </x-stat>

@endif
