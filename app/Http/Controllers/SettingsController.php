<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\SessionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class SettingsController extends Controller
{
    /**
     * Einstellungen – Übersicht
     */
    public function index(Request $request)
    {
        return view('settings.index', [
            'user' => $request->user(),
        ]);
    }


    /**
     * Profil anzeigen
     */
    public function profile(Request $request)
    {
        return view('settings.profile', [
            'user' => $request->user(),
        ]);
    }


    /**
     * Profilbild ausliefern (nur das eigene).
     */
    public function profilePhoto(Request $request)
    {
        $path = $request->user()->profile_photo_path;

        abort_unless($path && \Illuminate\Support\Facades\Storage::disk('local')->exists($path), 404);

        return \Illuminate\Support\Facades\Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'private, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * Profilbild hochladen.
     */
    public function updateProfilePhoto(Request $request)
    {
        $validated = $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ], [
            'photo.required' => 'Bitte ein Bild auswählen.',
            'photo.image' => 'Die Datei muss ein Bild sein.',
            'photo.mimes' => 'Erlaubt sind JPG, PNG und WebP.',
            'photo.max' => 'Das Bild darf höchstens 3 MB groß sein.',
        ]);

        $user = $request->user();
        $this->deleteProfilePhotoFile($user);

        $path = $validated['photo']->store('profile-photos', 'local');
        $user->forceFill(['profile_photo_path' => $path])->save();

        return redirect()->route('settings.profile')->with('success', 'Profilbild wurde gespeichert.');
    }

    /**
     * Profilbild entfernen.
     */
    public function destroyProfilePhoto(Request $request)
    {
        $user = $request->user();
        $this->deleteProfilePhotoFile($user);
        $user->forceFill(['profile_photo_path' => null])->save();

        return redirect()->route('settings.profile')->with('success', 'Profilbild wurde entfernt.');
    }

    private function deleteProfilePhotoFile($user): void
    {
        if ($user->profile_photo_path) {
            \Illuminate\Support\Facades\Storage::disk('local')->delete($user->profile_photo_path);
        }
    }

    /**
     * Profil speichern
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                'unique:users,email,' . $user->id,
            ],
        ], [
            'name.required' => 'Bitte gib deinen Namen ein.',
            'name.max' => 'Der Name darf maximal 255 Zeichen lang sein.',

            'email.required' => 'Bitte gib eine E-Mail-Adresse ein.',
            'email.email' => 'Bitte gib eine gültige E-Mail-Adresse ein.',
            'email.unique' => 'Diese E-Mail-Adresse wird bereits verwendet.',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        $user->save();

        return redirect()
            ->route('settings.profile')
            ->with('success', 'Dein Profil wurde erfolgreich gespeichert.');
    }


    /**
     * Sicherheit anzeigen
     */
    public function security(Request $request, SessionService $sessions)
    {
        return view('settings.security', [
            'sessions' => $sessions->forUser($request),
            'sessionsAvailable' => $sessions->available(),
        ]);
    }

    /**
     * Auf allen anderen Geräten abmelden
     */
    public function logoutOtherDevices(Request $request, SessionService $sessions)
    {
        $count = $sessions->logoutOtherDevices($request);

        return redirect()
            ->to(route('settings.security') . '#devices')
            ->with('success', match (true) {
                ! $sessions->available() => 'Andere Geräte werden bei ihrer nächsten Anfrage abgemeldet.',
                $count === 0 => 'Es war kein anderes Gerät angemeldet.',
                $count === 1 => '1 anderes Gerät wurde abgemeldet.',
                default => "{$count} andere Geräte wurden abgemeldet.",
            });
    }


    /**
     * Passwort ändern
     */
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => [
                'required',
                'current_password',
            ],

            'password' => [
                'required',
                'confirmed',
                Password::min(8),
            ],
        ], [
            'current_password.required' =>
                'Bitte gib dein aktuelles Passwort ein.',

            'current_password.current_password' =>
                'Das aktuelle Passwort ist nicht korrekt.',

            'password.required' =>
                'Bitte gib ein neues Passwort ein.',

            'password.confirmed' =>
                'Die Passwortbestätigung stimmt nicht überein.',

            'password.min' =>
                'Das neue Passwort muss mindestens 8 Zeichen lang sein.',
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        // Wer das alte Passwort kannte, soll nicht angemeldet bleiben.
        $count = app(SessionService::class)->logoutOtherDevices($request);

        return redirect()
            ->route('settings.security')
            ->with(
                'success',
                'Dein Passwort wurde erfolgreich geändert.'
                    . ($count > 0 ? ' ' . ($count === 1 ? '1 anderes Gerät wurde' : "{$count} andere Geräte wurden") . ' abgemeldet.' : '')
            );
    }


    /**
     * Darstellung anzeigen
     */
    public function appearance(Request $request)
    {
        return view('settings.appearance', [
            'user' => $request->user(),
        ]);
    }


    /**
     * Darstellung speichern
     */
    public function updateAppearance(Request $request)
    {
        $validated = $request->validate([
            'theme' => [
                'required',
                'in:light,dark,system',
            ],
        ]);

        $request->user()->update([
            'theme' => $validated['theme'],
        ]);

        return redirect()
            ->route('settings.appearance')
            ->with('success', 'Deine Darstellung wurde gespeichert.');
    }


    /**
     * Finanz-Einstellungen anzeigen
     */
    public function financial(Request $request)
    {
        $user = $request->user();

        $setting = Setting::firstOrCreate(
            [
                'user_id' => $user->id,
            ],
            [
                'currency' => 'EUR',
                'decimal_places' => 2,
                'date_format' => 'd.m.Y',
                'first_day_of_week' => 1,
            ]
        );

        $accounts = $user->accounts()
            ->orderBy('name')
            ->get();

        $categories = $user->categories()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()->inTreeOrder();

        return view('settings.financial', [
            'user' => $user,
            'setting' => $setting,
            'accounts' => $accounts,
            'categories' => $categories,
        ]);
    }


    /**
     * Finanz-Einstellungen speichern
     */
    public function updateFinancial(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'currency' => [
                'required',
                'string',
                'size:3',
            ],

            'decimal_places' => [
                'required',
                'integer',
                'in:0,2',
            ],

            'date_format' => [
                'required',
                'string',
                'in:d.m.Y,Y-m-d,d/m/Y,m/d/Y',
            ],

            'first_day_of_week' => [
                'required',
                'integer',
                'in:1,6,7',
            ],

            'default_account_id' => [
                'nullable',
                'integer',
                'exists:accounts,id',
            ],

            'default_category_id' => [
                'nullable',
                'integer',
                'exists:categories,id',
            ],
        ], [
            'currency.required' =>
                'Bitte wähle eine Währung aus.',

            'currency.size' =>
                'Die Währung muss aus genau 3 Zeichen bestehen.',

            'decimal_places.required' =>
                'Bitte wähle die Anzahl der Nachkommastellen.',

            'decimal_places.in' =>
                'Die Anzahl der Nachkommastellen ist ungültig.',

            'date_format.required' =>
                'Bitte wähle ein Datumsformat.',

            'date_format.in' =>
                'Das Datumsformat ist ungültig.',

            'first_day_of_week.required' =>
                'Bitte wähle den ersten Wochentag.',

            'first_day_of_week.in' =>
                'Der erste Wochentag ist ungültig.',

            'default_account_id.exists' =>
                'Das ausgewählte Konto existiert nicht.',

            'default_category_id.exists' =>
                'Die ausgewählte Kategorie existiert nicht.',
        ]);

        /*
         * Sicherstellen, dass Konto und Kategorie
         * tatsächlich dem angemeldeten Benutzer gehören.
         */
        if (!empty($validated['default_account_id'])) {

            $accountExists = $user->accounts()
                ->whereKey($validated['default_account_id'])
                ->exists();

            if (!$accountExists) {
                return back()
                    ->withErrors([
                        'default_account_id' =>
                            'Das ausgewählte Konto gehört nicht zu deinem Benutzerkonto.',
                    ])
                    ->withInput();
            }
        }

        if (!empty($validated['default_category_id'])) {

            $categoryExists = $user->categories()
                ->whereKey($validated['default_category_id'])
                ->exists();

            if (!$categoryExists) {
                return back()
                    ->withErrors([
                        'default_category_id' =>
                            'Die ausgewählte Kategorie gehört nicht zu deinem Benutzerkonto.',
                    ])
                    ->withInput();
            }
        }

        $setting = Setting::firstOrCreate([
            'user_id' => $user->id,
        ]);

        $setting->update($validated);

        return redirect()
            ->route('settings.financial')
            ->with(
                'success',
                'Deine Finanz-Einstellungen wurden gespeichert.'
            );
    }
}