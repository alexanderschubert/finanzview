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
                'display_mode' => 'standard',
            ]);
        }

        return view('settings.dashboard', [
            'settings' => $settings,
            'widgets' => DashboardSetting::availableWidgets(),
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
        ]);

        $selectedWidgets = [];

        foreach ($availableWidgets as $widget) {
            $selectedWidgets[$widget] = in_array(
                $widget,
                $validated['widgets'] ?? [],
                true
            );
        }

        $request->user()->dashboardSetting()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
            ],
            [
                'widgets' => $selectedWidgets,
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
