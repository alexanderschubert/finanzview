<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

class DefaultCategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Wohnen',
                'type' => 'expense',
                'icon' => '🏠',
            ],
            [
                'name' => 'Lebensmittel',
                'type' => 'expense',
                'icon' => '🛒',
            ],
            [
                'name' => 'Mobilität',
                'type' => 'expense',
                'icon' => '🚗',
            ],
            [
                'name' => 'Energie',
                'type' => 'expense',
                'icon' => '💡',
            ],
            [
                'name' => 'Telekommunikation',
                'type' => 'expense',
                'icon' => '📱',
            ],
            [
                'name' => 'Versicherungen',
                'type' => 'expense',
                'icon' => '🛡️',
            ],
            [
                'name' => 'Freizeit',
                'type' => 'expense',
                'icon' => '🎬',
            ],
            [
                'name' => 'Shopping',
                'type' => 'expense',
                'icon' => '🛍️',
            ],
            [
                'name' => 'Gesundheit',
                'type' => 'expense',
                'icon' => '❤️',
            ],
            [
                'name' => 'Restaurants',
                'type' => 'expense',
                'icon' => '🍽️',
            ],
            [
                'name' => 'Gehalt',
                'type' => 'income',
                'icon' => '💰',
            ],
            [
                'name' => 'Nebenverdienst',
                'type' => 'income',
                'icon' => '💶',
            ],
            [
                'name' => 'Erstattung',
                'type' => 'income',
                'icon' => '↩️',
            ],
            [
                'name' => 'Sonstige Einnahmen',
                'type' => 'income',
                'icon' => '📈',
            ],
            [
                'name' => 'Sonstiges',
                'type' => 'both',
                'icon' => '📦',
            ],
        ];

        User::query()
            ->each(function (User $user) use ($categories) {

                foreach ($categories as $category) {

                    Category::firstOrCreate(
                        [
                            'user_id' => $user->id,
                            'name' => $category['name'],
                        ],
                        [
                            'type' => $category['type'],
                            'icon' => $category['icon'],
                            'is_active' => true,
                        ]
                    );

                }

            });
    }
}