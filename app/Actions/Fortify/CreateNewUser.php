<?php

namespace App\Actions\Fortify;

use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                'unique:users',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
            ],
        ])->validate();

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
        ]);

        $this->createDefaultCategories($user);

        return $user;
    }

    /**
     * Legt die Standardkategorien für einen neuen Benutzer an.
     * Wird auch bei der Registrierung über OIDC verwendet.
     */
    public function createDefaultCategories(User $user): void
    {
        $categories = [
            ['name' => 'Wohnen', 'type' => 'expense', 'icon' => '🏠'],
            ['name' => 'Lebensmittel', 'type' => 'expense', 'icon' => '🛒'],
            ['name' => 'Mobilität', 'type' => 'expense', 'icon' => '🚗'],
            ['name' => 'Energie', 'type' => 'expense', 'icon' => '💡'],
            ['name' => 'Telekommunikation', 'type' => 'expense', 'icon' => '📱'],
            ['name' => 'Versicherungen', 'type' => 'expense', 'icon' => '🛡️'],
            ['name' => 'Freizeit', 'type' => 'expense', 'icon' => '🎬'],
            ['name' => 'Shopping', 'type' => 'expense', 'icon' => '🛍️'],
            ['name' => 'Gesundheit', 'type' => 'expense', 'icon' => '❤️'],
            ['name' => 'Restaurants', 'type' => 'expense', 'icon' => '🍽️'],
            ['name' => 'Gehalt', 'type' => 'income', 'icon' => '💰'],
            ['name' => 'Nebenverdienst', 'type' => 'income', 'icon' => '💶'],
            ['name' => 'Erstattung', 'type' => 'income', 'icon' => '↩️'],
            ['name' => 'Sonstige Einnahmen', 'type' => 'income', 'icon' => '📈'],
            ['name' => 'Sonstiges', 'type' => 'both', 'icon' => '📦'],
        ];

        foreach ($categories as $category) {
            $user->categories()->create([
                ...$category,
                'is_active' => true,
            ]);
        }
    }
}