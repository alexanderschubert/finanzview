<?php

namespace App\Http\Controllers;

use App\Models\DashboardSetting;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardSettingsController extends Controller
{
    /**
     * Dashboard-Einstellungen anzeigen.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();

        $settings = $user->dashboardSetting;

        if (!$settings) {
            $settings = new DashboardSetting([
                'widgets' => DashboardSetting::defaultWidgets(),
                'widget_order' => DashboardSetting::defaultWidgetOrder(),
                'display_mode' => 'standard',
            ]);
        }

        /*
         * Die Widgets werden entsprechend der gespeicherten Reihenfolge
         * an die View übergeben.
         */
        $availableWidgets = DashboardSetting::availableWidgets();
        $orderedWidgets = [];

        foreach ($settings->effectiveWidgetOrder() as $key) {
            if (isset($availableWidgets[$key])) {
                $orderedWidgets[$key] = $availableWidgets[$key];
            }
        }

        return view('settings.dashboard', [
            'settings' => $settings,
            'widgets' => $orderedWidgets,
            'selectedWidgets' => $settings->effectiveWidgets(),
        ]);
    }

    /**
     * Dashboard-Einstellungen speichern.
     */
    public function update(Request $request): RedirectResponse
    {
        $availableWidgets = array_keys(
            DashboardSetting::availableWidgets()
        );

        $validated = $request->validate([
            'display_mode' => [
                'required',
                'in:standard,compact',
            ],

            'widgets' => [
                'nullable',
                'array',
            ],

            'widgets.*' => [
                'string',
                'in:' . implode(',', $availableWidgets),
            ],

            /*
             * Die Reihenfolge wird als kommaseparierte Widget-Liste
             * vom Drag-&-Drop-Feld übertragen.
             */
            'widget_order' => [
                'required',
                'string',
                'max:2000',
            ],
        ]);

        $selectedWidgets = [];

        foreach ($availableWidgets as $widget) {
            $selectedWidgets[$widget] = in_array(
                $widget,
                $validated['widgets'] ?? [],
                true
            );
        }

        /*
         * Widget-Reihenfolge aus dem Formular lesen.
         */
        $requestedOrder = array_values(
            array_filter(
                array_map(
                    'trim',
                    explode(',', $validated['widget_order'])
                ),
                static fn (string $widget): bool => $widget !== ''
            )
        );

        /*
         * Doppelte Widgets verhindern.
         */
        if (
            count($requestedOrder) !==
            count(array_unique($requestedOrder))
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'widget_order' =>
                        'Die Widget-Reihenfolge enthält doppelte Einträge.',
                ]);
        }

        /*
         * Unbekannte Widgets verhindern.
         */
        $unknownWidgets = array_diff(
            $requestedOrder,
            $availableWidgets
        );

        if ($unknownWidgets !== []) {
            return back()
                ->withInput()
                ->withErrors([
                    'widget_order' =>
                        'Die Widget-Reihenfolge enthält ungültige Widgets.',
                ]);
        }

        /*
         * Falls zukünftig neue Widgets hinzukommen, werden diese
         * automatisch hinten an die gespeicherte Reihenfolge angehängt.
         */
        foreach ($availableWidgets as $widget) {
            if (!in_array($widget, $requestedOrder, true)) {
                $requestedOrder[] = $widget;
            }
        }

        $request->user()->dashboardSetting()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
            ],
            [
                'widgets' => $selectedWidgets,
                'widget_order' => $requestedOrder,
                'display_mode' => $validated['display_mode'],
            ]
        );

        return redirect()
            ->route('settings.dashboard')
            ->with(
                'success',
                'Deine Dashboard-Einstellungen wurden gespeichert.'
            );
    }
}
