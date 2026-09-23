{{-- Erfolgs- und Fehlermeldungen aus der Session. --}}

@if (session('success'))
    <div {{ $attributes->merge(['class' => 'flex gap-3 rounded-2xl bg-emerald-50 dark:bg-emerald-500/10 p-4 text-sm text-emerald-800 dark:text-emerald-300']) }} role="status">
        <x-icon name="check-circle" class="w-5 h-5" />
        <p>{{ session('success') }}</p>
    </div>
@endif

@if (session('error'))
    <div {{ $attributes->merge(['class' => 'flex gap-3 rounded-2xl bg-red-50 dark:bg-red-500/10 p-4 text-sm text-red-700 dark:text-red-300']) }} role="alert">
        <x-icon name="alert" class="w-5 h-5" />
        <p>{{ session('error') }}</p>
    </div>
@endif
