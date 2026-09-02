<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApplicationSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminController extends Controller
{
    /**
     * Admin-Dashboard.
     */
    public function index(): View
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'inactive_users' => User::where('is_active', false)->count(),
            'admin_users' => User::where('is_admin', true)->count(),
        ];

        $users = User::query()
            ->latest()
            ->get();

        $registrationEnabled = ApplicationSetting::get(
            'registration_enabled',
            true
        );

        return view(
            'admin.index',
            compact('stats', 'users', 'registrationEnabled')
        );
    }

    /**
     * Registrierung global aktivieren/deaktivieren.
     */
    public function toggleRegistration(): RedirectResponse
    {
        $enabled = ApplicationSetting::get(
            'registration_enabled',
            true
        );

        ApplicationSetting::set(
            'registration_enabled',
            ! $enabled
        );

        return back()->with(
            'success',
            ! $enabled
                ? 'Die Registrierung wurde aktiviert.'
                : 'Die Registrierung wurde deaktiviert.'
        );
    }

    /**
     * Benutzer bearbeiten.
     */
    public function edit(User $user): View
    {
        return view('admin.users.edit', compact('user'));
    }

    /**
     * Benutzer aktualisieren.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $currentUser = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'is_admin' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $newIsAdmin = $request->boolean('is_admin');
        $newIsActive = $request->boolean('is_active');

        // Eigenen Account nicht über diese Seite deaktivieren.
        if ($user->id === $currentUser->id && ! $newIsActive) {
            return back()
                ->withInput()
                ->with('error', 'Du kannst deinen eigenen Account nicht deaktivieren.');
        }

        // Eigene Administratorrechte nicht über diese Seite entfernen.
        if ($user->id === $currentUser->id && ! $newIsAdmin) {
            return back()
                ->withInput()
                ->with('error', 'Du kannst deine eigenen Administratorrechte nicht entfernen.');
        }

        // Letzten Administrator nicht entfernen.
        if (
            $user->is_admin &&
            ! $newIsAdmin &&
            User::where('is_admin', true)
                ->whereKeyNot($user->id)
                ->count() === 0
        ) {
            return back()
                ->withInput()
                ->with('error', 'Der letzte Administrator kann nicht entfernt werden.');
        }

        // Letzten aktiven Administrator nicht deaktivieren.
        if (
            $user->is_admin &&
            $user->is_active &&
            $newIsAdmin &&
            ! $newIsActive &&
            User::where('is_admin', true)
                ->where('is_active', true)
                ->whereKeyNot($user->id)
                ->count() === 0
        ) {
            return back()
                ->withInput()
                ->with('error', 'Der letzte aktive Administrator kann nicht deaktiviert werden.');
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->is_admin = $newIsAdmin;
        $user->is_active = $newIsActive;

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()
            ->route('admin.index')
            ->with('success', 'Benutzer wurde erfolgreich aktualisiert.');
    }

    /**
     * Benutzer aktivieren/deaktivieren.
     */
    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        $currentUser = $request->user();

        // Eigener Account darf nicht deaktiviert werden.
        if ($user->id === $currentUser->id) {
            return back()->with('error', 'Du kannst deinen eigenen Account nicht deaktivieren.');
        }

        // Letzten Administrator nicht deaktivieren.
        if (
            $user->is_admin &&
            $user->is_active &&
            User::where('is_admin', true)
                ->where('is_active', true)
                ->count() <= 1
        ) {
            return back()->with('error', 'Der letzte aktive Administrator kann nicht deaktiviert werden.');
        }

        $user->update([
            'is_active' => ! $user->is_active,
        ]);

        return back()->with(
            'success',
            $user->is_active
                ? 'Benutzer wurde aktiviert.'
                : 'Benutzer wurde deaktiviert.'
        );
    }

    /**
     * Administratorrechte vergeben/entziehen.
     */
    public function toggleAdmin(Request $request, User $user): RedirectResponse
    {
        $currentUser = $request->user();

        // Eigene Adminrechte nicht über diese Aktion entfernen.
        if ($user->id === $currentUser->id) {
            return back()->with('error', 'Du kannst deine eigenen Administratorrechte hier nicht ändern.');
        }

        // Letzten Administrator nicht entfernen.
        if (
            $user->is_admin &&
            User::where('is_admin', true)->count() <= 1
        ) {
            return back()->with('error', 'Der letzte Administrator kann nicht entfernt werden.');
        }

        $user->update([
            'is_admin' => ! $user->is_admin,
        ]);

        return back()->with(
            'success',
            $user->is_admin
                ? 'Administratorrechte wurden vergeben.'
                : 'Administratorrechte wurden entfernt.'
        );
    }

    /**
     * Benutzer löschen.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $currentUser = $request->user();

        // Eigener Account darf nicht gelöscht werden.
        if ($user->id === $currentUser->id) {
            return back()->with('error', 'Du kannst deinen eigenen Account nicht löschen.');
        }

        // Letzten Administrator nicht löschen.
        if (
            $user->is_admin &&
            User::where('is_admin', true)->count() <= 1
        ) {
            return back()->with('error', 'Der letzte Administrator kann nicht gelöscht werden.');
        }

        DB::transaction(function () use ($user) {
            $user->accounts()->delete();
            $user->transactions()->delete();
            $user->categories()->delete();
            $user->budgets()->delete();
            $user->loans()->delete();
            $user->creditCards()->delete();
            $user->recurringTransactions()->delete();
            $user->setting()->delete();

            $user->delete();
        });

        return back()->with('success', 'Benutzer wurde gelöscht.');
    }
}
