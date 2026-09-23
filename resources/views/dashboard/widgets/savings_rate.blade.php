@if($dashboardWidgets['savings_rate'])

    <x-stat
        label="Sparquote"
        icon="percent"
        :tone="$savingsRate < 0 ? 'negative' : 'neutral'"
        hint="Anteil der Einnahmen, der übrig bleibt"
    >
        {{ number_format($savingsRate, 1, ',', '.') }} %
    </x-stat>

@endif
