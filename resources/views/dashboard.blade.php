<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Dashboard – Finanzblick</title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])
</head>

<body class="min-h-screen bg-slate-100">

    <div class="min-h-screen">

        <header class="bg-white border-b border-slate-200">

            <div class="max-w-7xl mx-auto px-6 py-5">

                <div class="flex items-center justify-between">

                    <div>
                        <h1 class="text-2xl font-semibold text-slate-900">
                            Finanzblick
                        </h1>

                        <p class="text-sm text-slate-500 mt-1">
                            Willkommen, {{ $user->name }}
                        </p>
                    </div>

                    <form
                        method="POST"
                        action="{{ route('logout') }}"
                    >
                        @csrf

                        <button
                            type="submit"
                            class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-medium text-white"
                        >
                            Abmelden
                        </button>
                    </form>

                </div>

            </div>

        </header>


        <main class="max-w-7xl mx-auto px-6 py-8">

            <!-- Gesamtvermögen -->

            <div class="bg-white rounded-3xl p-6 shadow-sm mb-6">

                <p class="text-sm text-slate-500">
                    Gesamtbestand
                </p>

                <p class="text-4xl font-semibold text-slate-900 mt-2">
                    {{ number_format($totalBalance, 2, ',', '.') }} €
                </p>

            </div>


            <!-- Konten -->

            <section class="mb-8">

                <div class="flex items-center justify-between mb-4">

                    <h2 class="text-xl font-semibold text-slate-900">
                        Deine Konten
                    </h2>

                </div>

                @if ($accounts->count())

                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">

                        @foreach ($accounts as $account)

                            <div class="bg-white rounded-2xl p-5 shadow-sm">

                                <div class="flex items-center justify-between">

                                    <div>

                                        <p class="font-medium text-slate-900">
                                            {{ $account->name }}
                                        </p>

                                        <p class="text-sm text-slate-500 mt-1">
                                            {{ $account->institution ?? 'Konto' }}
                                        </p>

                                    </div>

                                    <span class="text-2xl">
                                        {{ $account->icon ?? '🏦' }}
                                    </span>

                                </div>

                                <p class="text-2xl font-semibold mt-5">
                                    {{ number_format($account->opening_balance, 2, ',', '.') }} €
                                </p>

                            </div>

                        @endforeach

                    </div>

                @else

                    <div class="bg-white rounded-2xl p-8 text-center">

                        <div class="text-4xl mb-3">
                            🏦
                        </div>

                        <h3 class="font-semibold text-slate-900">
                            Noch keine Konten
                        </h3>

                        <p class="text-sm text-slate-500 mt-2">
                            Lege dein erstes Konto an, um mit Finanzblick zu starten.
                        </p>

                    </div>

                @endif

            </section>


            <!-- Letzte Buchungen -->

            <section>

                <h2 class="text-xl font-semibold text-slate-900 mb-4">
                    Letzte Buchungen
                </h2>

                <div class="bg-white rounded-2xl shadow-sm overflow-hidden">

                    @forelse ($recentTransactions as $transaction)

                        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">

                            <div>

                                <p class="font-medium text-slate-900">
                                    {{ $transaction->description }}
                                </p>

                                <p class="text-sm text-slate-500">
                                    {{ $transaction->transaction_date?->format('d.m.Y') }}

                                    @if ($transaction->category)
                                        · {{ $transaction->category->name }}
                                    @endif
                                </p>

                            </div>

                            <span class="font-semibold
                                {{ $transaction->type === 'income'
                                    ? 'text-emerald-600'
                                    : 'text-slate-900' }}
                            ">
                                {{ $transaction->type === 'income' ? '+' : '-' }}
                                {{ number_format(abs($transaction->amount), 2, ',', '.') }} €
                            </span>

                        </div>

                    @empty

                        <div class="p-8 text-center text-slate-500">
                            Noch keine Buchungen vorhanden.
                        </div>

                    @endforelse

                </div>

            </section>

        </main>

    </div>

</body>
</html>