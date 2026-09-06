@extends('layouts.app')

@section('title', 'Dashboard-Einstellungen')

@section('content')
<div class="mx-auto max-w-5xl space-y-6">

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-3">
                <a
                    href="{{ route('dashboard') }}"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-xl
                           bg-white text-slate-600 shadow-sm ring-1 ring-slate-200
                           transition hover:bg-slate-50 hover:text-slate-900
                           dark:bg-slate-900 dark:text-slate-400 dark:ring-slate-800
                           dark:hover:bg-slate-800 dark:hover:text-white"
                    title="Zurück zum Dashboard"
                >
                    ←
                </a>

                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                        Dashboard
                    </h1>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Passe dein Dashboard an deine persönlichen Bedürfnisse an.
                    </p>
                </div>
            </div>
        </div>

        <a
            href="{{ route('dashboard') }}"
            class="inline-flex items-center justify-center rounded-xl
                   bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white
                   shadow-sm transition hover:bg-slate-800
                   dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100"
        >
            ← Zum Dashboard
        </a>
    </div>

    {{-- Success --}}
    @if(session('success'))
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3
                    text-sm text-emerald-800 dark:border-emerald-900/60
                    dark:bg-emerald-950/30 dark:text-emerald-300">
            <div class="flex items-center gap-2">
                <span class="text-lg">✓</span>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    {{-- Validation errors --}}
    @if($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3
                    text-sm text-red-800 dark:border-red-900/60
                    dark:bg-red-950/30 dark:text-red-300">
            <div class="font-semibold">Bitte überprüfe deine Eingaben.</div>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('settings.dashboard.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Display mode --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm
                        dark:border-slate-800 dark:bg-slate-900">

            <div class="border-b border-slate-200 px-5 py-5 dark:border-slate-800 sm:px-6">
                <div class="flex items-start gap-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl
                                bg-indigo-50 text-xl dark:bg-indigo-950/40">
                        🎨
                    </div>

                    <div>
                        <h2 class="font-semibold text-slate-900 dark:text-white">
                            Darstellungsmodus
                        </h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            Bestimme, wie kompakt die Inhalte auf deinem Dashboard dargestellt werden.
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 p-5 sm:grid-cols-2 sm:p-6">

                {{-- Standard --}}
                <label class="relative cursor-pointer">
                    <input
                        type="radio"
                        name="display_mode"
                        value="standard"
                        class="peer sr-only"
                        {{ old('display_mode', $settings->display_mode) === 'standard' ? 'checked' : '' }}
                    >

                    <div class="rounded-2xl border-2 border-slate-200 p-5 transition
                                peer-checked:border-indigo-500 peer-checked:bg-indigo-50/50
                                hover:border-slate-300
                                dark:border-slate-700 dark:bg-slate-950/30
                                dark:peer-checked:border-indigo-500 dark:peer-checked:bg-indigo-950/20
                                dark:hover:border-slate-600">

                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="text-2xl">▦</span>
                                <span class="font-semibold text-slate-900 dark:text-white">
                                    Standard
                                </span>
                            </div>

                            <span class="flex h-5 w-5 items-center justify-center rounded-full
                                         border border-slate-300
                                         peer-checked:bg-indigo-500 peer-checked:border-indigo-500
                                         dark:border-slate-600">
                                <span class="hidden text-xs text-white peer-checked:block">✓</span>
                            </span>
                        </div>

                        <p class="mt-3 text-sm leading-6 text-slate-500 dark:text-slate-400">
                            Größere Karten und mehr Informationen auf einmal.
                            Ideal für Desktop und große Bildschirme.
                        </p>
                    </div>
                </label>

                {{-- Compact --}}
                <label class="relative cursor-pointer">
                    <input
                        type="radio"
                        name="display_mode"
                        value="compact"
                        class="peer sr-only"
                        {{ old('display_mode', $settings->display_mode) === 'compact' ? 'checked' : '' }}
                    >

                    <div class="rounded-2xl border-2 border-slate-200 p-5 transition
                                peer-checked:border-indigo-500 peer-checked:bg-indigo-50/50
                                hover:border-slate-300
                                dark:border-slate-700 dark:bg-slate-950/30
                                dark:peer-checked:border-indigo-500 dark:peer-checked:bg-indigo-950/20
                                dark:hover:border-slate-600">

                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="text-2xl">▤</span>
                                <span class="font-semibold text-slate-900 dark:text-white">
                                    Kompakt
                                </span>
                            </div>

                            <span class="flex h-5 w-5 items-center justify-center rounded-full
                                         border border-slate-300
                                         dark:border-slate-600">
                                <span class="hidden text-xs text-white">✓</span>
                            </span>
                        </div>

                        <p class="mt-3 text-sm leading-6 text-slate-500 dark:text-slate-400">
                            Weniger Abstände und kleinere Karten.
                            So bekommst du mehr Informationen auf den Bildschirm.
                        </p>
                    </div>
                </label>

            </div>
        </section>

        {{-- Widgets --}}
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm
                        dark:border-slate-800 dark:bg-slate-900">

            <div class="border-b border-slate-200 px-5 py-5 dark:border-slate-800 sm:px-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                    <div class="flex items-start gap-4">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl
                                    bg-emerald-50 text-xl dark:bg-emerald-950/40">
                            🧩
                        </div>

                        <div>
                            <h2 class="font-semibold text-slate-900 dark:text-white">
                                Dashboard-Inhalte
                            </h2>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                Wähle aus, welche Bereiche auf deinem Dashboard angezeigt werden.
                            </p>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button
                            type="button"
                            id="select-all-widgets"
                            class="rounded-lg border border-slate-200 px-3 py-2 text-xs
                                   font-semibold text-slate-600 transition hover:bg-slate-50
                                   dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                        >
                            Alle auswählen
                        </button>

                        <button
                            type="button"
                            id="deselect-all-widgets"
                            class="rounded-lg border border-slate-200 px-3 py-2 text-xs
                                   font-semibold text-slate-600 transition hover:bg-slate-50
                                   dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                        >
                            Alle abwählen
                        </button>
                    </div>
                </div>
            </div>

                          <input
                  type="hidden"
                  name="widget_order"
                  id="dashboard-widget-order"
                  value="{{ implode(',', $settings->effectiveWidgetOrder()) }}"
              >

<div class="grid gap-3 p-5 sm:grid-cols-2 sm:p-6 lg:grid-cols-3" id="dashboard-widget-list">

                @foreach($widgets as $key => $widget)
                    @php
                        $enabled = old(
                            'widgets',
                            collect($selectedWidgets)
                                ->filter(fn ($enabled) => $enabled)
                                ->keys()
                                ->all()
                        );

                        $checked = in_array($key, $enabled, true);
                    @endphp

                    <label
                          class="dashboard-widget-item group relative cursor-pointer"
                          data-widget="{{ $key }}"
                          draggable="true"
                      >
                        <input
                            type="checkbox"
                            name="widgets[]"
                            value="{{ $key }}"
                            class="widget-checkbox peer sr-only"
                            {{ $checked ? 'checked' : '' }}
                        >

                        <div class="flex min-h-[104px] items-start gap-3 rounded-xl border-2
                                    border-slate-200 p-4 transition
                                    peer-checked:border-indigo-500
                                    peer-checked:bg-indigo-50/40
                                    hover:border-slate-300
                                    dark:border-slate-700 dark:bg-slate-950/30
                                    dark:peer-checked:border-indigo-500
                                    dark:peer-checked:bg-indigo-950/20
                                    dark:hover:border-slate-600">

                              {{-- Drag Handle --}}
                              <span
                                  class="dashboard-drag-handle flex h-10 w-7 shrink-0 cursor-grab
                                         items-center justify-center rounded-lg
                                         text-slate-400 transition
                                         hover:bg-slate-100 hover:text-slate-600
                                         active:cursor-grabbing
                                         dark:text-slate-500
                                         dark:hover:bg-slate-800 dark:hover:text-slate-300"
                                  title="Widget verschieben"
                                  aria-label="Widget verschieben"
                              >
                                  <svg
                                      viewBox="0 0 20 20"
                                      fill="currentColor"
                                      class="h-5 w-5"
                                      aria-hidden="true"
                                  >
                                      <path d="M6.25 2.75a1.25 1.25 0 1 1 0 2.5 1.25 1.25 0 0 1 0-2.5Zm0 6.25a1.25 1.25 0 1 1 0 2.5 1.25 1.25 0 0 1 0-2.5Zm0 6.25a1.25 1.25 0 1 1 0 2.5 1.25 1.25 0 0 1 0-2.5ZM13.75 2.75a1.25 1.25 0 1 1 0 2.5 1.25 1.25 0 0 1 0-2.5Zm0 6.25a1.25 1.25 0 1 1 0 2.5 1.25 1.25 0 0 1 0-2.5Zm0 6.25a1.25 1.25 0 1 1 0-2.5 1.25 1.25 0 1 1 0-2.5Z"/>
                                  </svg>
                              </span>

                            <div class="flex h-10 w-10 shrink-0 items-center justify-center
                                        rounded-lg bg-slate-100 text-lg
                                        dark:bg-slate-800">
                                {{ $widget['icon'] }}
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-semibold text-slate-900 dark:text-white">
                                        {{ $widget['label'] }}
                                    </span>

                                    <span class="widget-check hidden shrink-0 text-sm font-bold
                                                 text-indigo-600 dark:text-indigo-400">
                                        ✓
                                    </span>
                                </div>

                                <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">
                                    {{ $widget['description'] }}
                                </p>
                            </div>
                        </div>
                    </label>
                @endforeach

            </div>

            <div class="border-t border-slate-200 bg-slate-50 px-5 py-4
                        dark:border-slate-800 dark:bg-slate-950/40">
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    💡 Tipp: Deaktiviere Bereiche, die du nicht benötigst.
                    Das Dashboard wird dadurch übersichtlicher.
                </p>
            </div>
        </section>

        {{-- Save --}}
        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-end">

            <a
                href="{{ route('dashboard') }}"
                class="inline-flex items-center justify-center rounded-xl border
                       border-slate-200 px-5 py-3 text-sm font-semibold
                       text-slate-700 transition hover:bg-slate-50
                       dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
            >
                Abbrechen
            </a>

            <button
                type="submit"
                class="inline-flex items-center justify-center rounded-xl
                       bg-indigo-600 px-5 py-3 text-sm font-semibold text-white
                       shadow-sm transition hover:bg-indigo-700 focus:outline-none
                       focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2
                       dark:focus:ring-offset-slate-950"
            >
                Einstellungen speichern
            </button>

        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {

    /*
     * =====================================================
     * Dashboard Widget Drag & Drop
     * =====================================================
     */

    const widgetList = document.getElementById('dashboard-widget-list');
    const widgetOrder = document.getElementById('dashboard-widget-order');

    let draggedWidget = null;

    const widgetItems = () => Array.from(
        widgetList?.querySelectorAll('.dashboard-widget-item') ?? []
    );

    const updateWidgetOrder = () => {
        if (!widgetOrder) {
            return;
        }

        widgetOrder.value = widgetItems()
            .map((item) => item.dataset.widget)
            .filter(Boolean)
            .join(',');
    };

    if (widgetList) {

        widgetItems().forEach((item) => {

            item.addEventListener('dragstart', (event) => {

                draggedWidget = item;

                item.classList.add(
                    'opacity-50',
                    'ring-2',
                    'ring-indigo-500',
                    'dark:ring-indigo-400'
                );

                event.dataTransfer.effectAllowed = 'move';

                event.dataTransfer.setData(
                    'text/plain',
                    item.dataset.widget || ''
                );
            });

            item.addEventListener('dragend', () => {

                item.classList.remove(
                    'opacity-50',
                    'ring-2',
                    'ring-indigo-500',
                    'dark:ring-indigo-400'
                );

                draggedWidget = null;

                updateWidgetOrder();
            });

            item.addEventListener('dragover', (event) => {

                event.preventDefault();

                if (!draggedWidget || draggedWidget === item) {
                    return;
                }

                const rect = item.getBoundingClientRect();

                const before =
                    event.clientY < rect.top + rect.height / 2;

                if (before) {

                    item.parentNode.insertBefore(
                        draggedWidget,
                        item
                    );

                } else {

                    item.parentNode.insertBefore(
                        draggedWidget,
                        item.nextSibling
                    );
                }

                updateWidgetOrder();
            });
        });

        updateWidgetOrder();
    }

    /*
     * =====================================================
     * Bestehende Checkbox-Logik
     * =====================================================
     */

    const checkboxes = () => Array.from(
        document.querySelectorAll('.widget-checkbox')
    );

    const updateVisualState = () => {
        checkboxes().forEach((checkbox) => {
            const check = checkbox
                .closest('label')
                ?.querySelector('.widget-check');

            if (check) {
                check.classList.toggle('hidden', !checkbox.checked);
            }
        });
    };

    document
        .getElementById('select-all-widgets')
        ?.addEventListener('click', () => {
            checkboxes().forEach((checkbox) => {
                checkbox.checked = true;
            });

            updateVisualState();
        });

    document
        .getElementById('deselect-all-widgets')
        ?.addEventListener('click', () => {
            checkboxes().forEach((checkbox) => {
                checkbox.checked = false;
            });

            updateVisualState();
        });

    checkboxes().forEach((checkbox) => {
        checkbox.addEventListener('change', updateVisualState);
    });

    updateVisualState();
});
</script>
@endsection
