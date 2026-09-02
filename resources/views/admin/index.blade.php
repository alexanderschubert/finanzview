@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- Flash-Meldungen --}}
    @if (session('success'))
        <div class="mb-6 rounded-2xl border border-emerald-200 dark:border-emerald-900/50 bg-emerald-50 dark:bg-emerald-950/30 px-5 py-4 text-sm text-emerald-700 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-6 rounded-2xl border border-rose-200 dark:border-rose-900/50 bg-rose-50 dark:bg-rose-950/30 px-5 py-4 text-sm text-rose-700 dark:text-rose-300">
            {{ session('error') }}
        </div>
    @endif


    {{-- Header --}}
    <div class="mb-8">
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-violet-100 dark:bg-violet-950/40 flex items-center justify-center text-2xl">
                🛡️
            </div>

            <div>
                <h1 class="text-2xl font-bold text-slate-900 dark:text-white">
                    Administration
                </h1>

                <p class="text-sm text-slate-500 dark:text-slate-400">
                    Benutzer und Systemeinstellungen verwalten
                </p>
            </div>
        </div>
    </div>


    {{-- Statistiken --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">

        <div class="rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-5">
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Benutzer
            </p>

            <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-white">
                {{ $stats['total_users'] }}
            </p>
        </div>

        <div class="rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-5">
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Aktive Benutzer
            </p>

            <p class="mt-2 text-3xl font-bold text-emerald-600 dark:text-emerald-400">
                {{ $stats['active_users'] }}
            </p>
        </div>

        <div class="rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-5">
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Deaktiviert
            </p>

            <p class="mt-2 text-3xl font-bold text-rose-600 dark:text-rose-400">
                {{ $stats['inactive_users'] }}
            </p>
        </div>

        <div class="rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-5">
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Administratoren
            </p>

            <p class="mt-2 text-3xl font-bold text-violet-600 dark:text-violet-400">
                {{ $stats['admin_users'] }}
            </p>
        </div>

    </div>


    {{-- Benutzer --}}
    <div class="rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 overflow-hidden">

        <div class="px-6 py-5 border-b border-slate-200 dark:border-slate-700">
            <h2 class="text-lg font-semibold text-slate-900 dark:text-white">
                Benutzer
            </h2>

            <p class="text-sm text-slate-500 dark:text-slate-400 mt-1">
                Übersicht und Verwaltung aller registrierten Benutzer
            </p>
        </div>


        <div class="overflow-x-auto">
            <table class="w-full text-sm">

                <thead class="bg-slate-50 dark:bg-slate-900/50">
                    <tr>
                        <th class="text-left px-6 py-4 font-medium text-slate-500 dark:text-slate-400">
                            Benutzer
                        </th>

                        <th class="text-left px-6 py-4 font-medium text-slate-500 dark:text-slate-400">
                            Status
                        </th>

                        <th class="text-left px-6 py-4 font-medium text-slate-500 dark:text-slate-400">
                            Rolle
                        </th>

                        <th class="text-left px-6 py-4 font-medium text-slate-500 dark:text-slate-400">
                            Letzter Login
                        </th>

                        <th class="text-left px-6 py-4 font-medium text-slate-500 dark:text-slate-400">
                            Registriert
                        </th>

                        <th class="text-right px-6 py-4 font-medium text-slate-500 dark:text-slate-400">
                            Aktionen
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-200 dark:divide-slate-700">

                    @forelse ($users as $user)

                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30">

                            {{-- Benutzer --}}
                            <td class="px-6 py-4">
                                <div class="font-medium text-slate-900 dark:text-white">
                                    {{ $user->name }}

                                    @if ($user->id === auth()->id())
                                        <span class="ml-1 text-xs text-violet-600 dark:text-violet-400">
                                            (Du)
                                        </span>
                                    @endif
                                </div>

                                <div class="text-slate-500 dark:text-slate-400">
                                    {{ $user->email }}
                                </div>
                            </td>


                            {{-- Status --}}
                            <td class="px-6 py-4">

                                @if ($user->is_active)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Aktiv
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-rose-100 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                        Deaktiviert
                                    </span>
                                @endif

                            </td>


                            {{-- Rolle --}}
                            <td class="px-6 py-4">

                                @if ($user->is_admin)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-violet-100 dark:bg-violet-950/40 text-violet-700 dark:text-violet-300">
                                        🛡️ Administrator
                                    </span>
                                @else
                                    <span class="text-slate-500 dark:text-slate-400">
                                        Benutzer
                                    </span>
                                @endif

                            </td>


                            {{-- Letzter Login --}}
                            <td class="px-6 py-4 text-slate-600 dark:text-slate-300 whitespace-nowrap">
                                {{ $user->last_login_at?->format('d.m.Y H:i') ?? 'Noch kein Login' }}
                            </td>


                            {{-- Registrierung --}}
                            <td class="px-6 py-4 text-slate-600 dark:text-slate-300 whitespace-nowrap">
                                {{ $user->created_at?->format('d.m.Y') }}
                            </td>


                            {{-- Aktionen --}}
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-2">

                                    @if ($user->id !== auth()->id())

                                        {{-- Aktivieren / Deaktivieren --}}
                                        <form
                                            method="POST"
                                            action="{{ route('admin.users.toggle-active', $user) }}"
                                        >
                                            @csrf
                                            @method('PATCH')

                                            <button
                                                type="submit"
                                                class="inline-flex items-center justify-center w-9 h-9 rounded-xl border border-slate-200 dark:border-slate-600 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 transition"
                                                title="{{ $user->is_active ? 'Benutzer deaktivieren' : 'Benutzer aktivieren' }}"
                                            >
                                                {{ $user->is_active ? '⏸️' : '▶️' }}
                                            </button>
                                        </form>


                                        {{-- Admin-Rechte --}}
                                        <form
                                            method="POST"
                                            action="{{ route('admin.users.toggle-admin', $user) }}"
                                        >
                                            @csrf
                                            @method('PATCH')

                                            <button
                                                type="submit"
                                                class="inline-flex items-center justify-center w-9 h-9 rounded-xl border border-slate-200 dark:border-slate-600 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 transition"
                                                title="{{ $user->is_admin ? 'Administratorrechte entfernen' : 'Administratorrechte vergeben' }}"
                                            >
                                                🛡️
                                            </button>
                                        </form>


                                        {{-- Löschen --}}
                                        <form
                                            method="POST"
                                            action="{{ route('admin.users.destroy', $user) }}"
                                            onsubmit="return confirm('Möchtest du den Benutzer {{ addslashes($user->name) }} wirklich löschen? Alle zugehörigen Finanzdaten werden ebenfalls gelöscht.');"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="inline-flex items-center justify-center w-9 h-9 rounded-xl border border-rose-200 dark:border-rose-900/50 hover:bg-rose-50 dark:hover:bg-rose-950/30 text-rose-600 dark:text-rose-400 transition"
                                                title="Benutzer löschen"
                                            >
                                                🗑️
                                            </button>
                                        </form>

                                    @else

                                        <span class="text-xs text-slate-400 dark:text-slate-500">
                                            Eigener Account
                                        </span>

                                    @endif

                                </div>
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-500 dark:text-slate-400">
                                Keine Benutzer vorhanden.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>
        </div>

    </div>

</div>
@endsection
