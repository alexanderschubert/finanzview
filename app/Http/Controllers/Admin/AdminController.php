<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApplicationSetting;
use App\Models\User;
use App\Models\FinancialProvider;
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

        $user->forceFill([
            'is_active' => ! $user->is_active,
        ])->save();

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

        $user->forceFill([
            'is_admin' => ! $user->is_admin,
        ])->save();

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

        // Eigenen Account niemals über die Benutzerverwaltung löschen.
        if ($user->id === $currentUser->id) {
            return back()->with('error', 'Du kannst deinen eigenen Account nicht löschen.');
        }

        // Der letzte Administrator darf nicht gelöscht werden.
        if ($user->is_admin && User::where('is_admin', true)->count() <= 1) {
            return back()->with('error', 'Der letzte Administrator kann nicht gelöscht werden.');
        }

        DB::transaction(function () use ($user) {
            // Buchungen verwenden SoftDeletes und müssen deshalb
            // über Eloquent gelöscht werden.
            $user->transactions()->get()->each->delete();

            // Abhängige Datensätze werden vor dem Benutzer gelöscht.
            $user->accounts()->delete();
            $user->categories()->delete();
            $user->budgets()->delete();
            $user->loans()->delete();
            $user->creditCards()->delete();
            $user->recurringTransactions()->delete();
            $user->setting()->delete();

            // Der Benutzer selbst wird anschließend gelöscht.
            // Die PostgreSQL-FKs übernehmen ggf. verbleibende
            // abhängige Datensätze per ON DELETE CASCADE.
            $user->delete();
        });

        return back()->with('success', 'Benutzer wurde gelöscht.');
    }

    /*
     * =========================================================
     * ANBIETER
     * =========================================================
     */

    public function providers(): View
    {
        $providers = FinancialProvider::query()
            ->withCount([
                'accounts',
                'creditCards',
                'loans',
            ])
            ->orderBy('name')
            ->get();

        return view('admin.providers.index', compact('providers'));
    }

    public function toggleProviderActive(
        FinancialProvider $provider
    ): RedirectResponse {
        $provider->update([
            'is_active' => ! $provider->is_active,
        ]);

        return back()->with(
            'success',
            $provider->is_active
                ? 'Anbieter wurde aktiviert.'
                : 'Anbieter wurde deaktiviert.'
        );
    }

    public function destroyProvider(
        FinancialProvider $provider
    ): RedirectResponse {
        $usageCount =
            $provider->accounts()->count()
            + $provider->creditCards()->count()
            + $provider->loans()->count();

        if ($usageCount > 0) {
            return back()->with(
                'error',
                'Der Anbieter kann nicht gelöscht werden, da er noch verwendet wird.'
            );
        }

        $provider->delete();

        return back()->with(
            'success',
            'Anbieter wurde gelöscht.'
        );
    }


    /*
     * =========================================================
     * ANBIETER ERSTELLEN
     * =========================================================
     */

    private function providerLogos(): array
    {
        $path = public_path('images/providers');

        if (! is_dir($path)) {
            return [];
        }

        return collect(scandir($path))
            ->filter(function ($file) use ($path) {
                return is_file($path . DIRECTORY_SEPARATOR . $file)
                    && in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['svg', 'png', 'jpg', 'jpeg', 'webp'], true);
            })
            ->mapWithKeys(function ($file) {
                return [$file => 'images/providers/' . $file];
            })
            ->sortKeys()
            ->all();
    }

    public function createProvider(): View
    {
        return view('admin.providers.create', [
            'provider' => new FinancialProvider(),
            'providerLogos' => $this->providerLogos(),
        ]);
    }


    /*
     * =========================================================
     * ANBIETER SPEICHERN
     * =========================================================
     */

    public function storeProvider(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'unique:financial_providers,slug',
            ],
            'type' => [
                'required',
                'in:bank,payment,card,lender,other',
            ],
            'logo' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^images\/providers\/[A-Za-z0-9._-]+\.(svg|png|jpe?g|webp)$/i',
            ],
            'emoji' => [
                'nullable',
                'string',
                'max:20',
            ],
            'color' => [
                'nullable',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
        ]);

        $slug = $validated['slug'] ?? null;

        if (! $slug) {
            $slug = \Illuminate\Support\Str::slug($validated['name']);
        }

        $baseSlug = $slug;
        $counter = 2;

        while (FinancialProvider::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        FinancialProvider::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'type' => $validated['type'],
            'logo' => $validated['logo'] ?? null,
            'emoji' => $validated['emoji'] ?? null,
            'color' => $validated['color'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('admin.providers.index')
            ->with('success', 'Anbieter wurde erstellt.');
    }


    /*
     * =========================================================
     * ANBIETER BEARBEITEN
     * =========================================================
     */

    public function editProvider(
        FinancialProvider $provider
    ): View {
        return view('admin.providers.edit', [
            'provider' => $provider,
            'providerLogos' => $this->providerLogos(),
        ]);
    }


    /*
     * =========================================================
     * ANBIETER AKTUALISIEREN
     * =========================================================
     */

    public function updateProvider(
        Request $request,
        FinancialProvider $provider
    ): RedirectResponse {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'slug' => [
                'required',
                'string',
                'max:255',
                'unique:financial_providers,slug,' . $provider->id,
            ],
            'type' => [
                'required',
                'in:bank,payment,card,lender,other',
            ],
            'logo' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^images\/providers\/[A-Za-z0-9._-]+\.(svg|png|jpe?g|webp)$/i',
            ],
            'emoji' => [
                'nullable',
                'string',
                'max:20',
            ],
            'color' => [
                'nullable',
                'regex:/^#[0-9A-Fa-f]{6}$/',
            ],
            'is_active' => [
                'nullable',
                'boolean',
            ],
        ]);

        $provider->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'type' => $validated['type'],
            'logo' => $validated['logo'] ?? null,
            'emoji' => $validated['emoji'] ?? null,
            'color' => $validated['color'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()
            ->route('admin.providers.index')
            ->with('success', 'Anbieter wurde aktualisiert.');
    }

}
