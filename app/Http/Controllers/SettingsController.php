<?php

namespace App\Http\Controllers;

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
    public function security(Request $request)
    {
        return view('settings.security');
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

        return redirect()
            ->route('settings.security')
            ->with(
                'success',
                'Dein Passwort wurde erfolgreich geändert.'
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
}