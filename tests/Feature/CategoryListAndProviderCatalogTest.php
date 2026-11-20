<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\FinancialProvider;
use App\Models\Transaction;
use App\Models\User;
use App\Support\ProviderCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryListAndProviderCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_list_shows_counts_month_sum_and_search(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $account = Account::create([
            'user_id' => $user->id, 'name' => 'Giro', 'type' => 'checking', 'currency' => 'EUR',
            'opening_balance' => 0, 'include_in_total' => true, 'is_active' => true,
        ]);
        $food = Category::create(['user_id' => $user->id, 'name' => 'Lebensmittel', 'type' => 'expense', 'icon' => '🛒', 'is_active' => true]);
        $market = Category::create(['user_id' => $user->id, 'parent_id' => $food->id, 'name' => 'Supermarkt', 'type' => 'expense', 'is_active' => true]);
        Category::create(['user_id' => $user->id, 'name' => 'Ungenutzt', 'type' => 'expense', 'is_active' => true]);

        foreach ([now()->toDateString(), now()->subMonths(3)->toDateString()] as $date) {
            Transaction::create([
                'user_id' => $user->id, 'account_id' => $account->id, 'category_id' => $market->id,
                'type' => 'expense', 'amount' => 25, 'transaction_date' => $date, 'description' => 'Einkauf',
            ]);
        }

        $this->actingAs($user)->get(route('categories.index'))
            ->assertOk()
            ->assertSee('category-search', false)
            ->assertSee('2 Buchungen', false)
            ->assertSee('25,00 €')
            ->assertSee('ohne Buchungen');
    }

    public function test_catalog_creates_known_banks_once_and_keeps_existing_values(): void
    {
        // Die Migration hat den Katalog schon geladen – für den Test leeren.
        FinancialProvider::query()->delete();

        FinancialProvider::create(['name' => 'ING', 'slug' => 'meine-ing', 'type' => 'bank', 'color' => '#123456', 'is_active' => true]);

        $first = ProviderCatalog::sync();
        $second = ProviderCatalog::sync();

        $this->assertGreaterThan(30, $first['created']);
        $this->assertSame(['created' => 0, 'updated' => 0], $second);
        $this->assertSame(1, FinancialProvider::whereRaw('lower(name) = ?', ['ing'])->count());

        $ing = FinancialProvider::where('slug', 'meine-ing')->first();
        $this->assertSame('#123456', $ing->color);
        $this->assertSame('images/providers/ing.svg', $ing->logo);
    }

    public function test_every_catalog_logo_file_exists(): void
    {
        foreach (ProviderCatalog::all() as $entry) {
            $this->assertFileExists(public_path('images/providers/' . $entry['slug'] . '.svg'), $entry['slug']);
        }
    }

    public function test_admin_can_load_catalog_and_form_shows_provider_picker(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);

        $this->actingAs($admin)->post(route('admin.providers.sync-catalog'))->assertRedirect();
        $this->assertTrue(FinancialProvider::where('slug', 'dkb')->exists());

        $this->actingAs($admin)->get(route('accounts.create'))
            ->assertOk()
            ->assertSee('data-provider-select', false)
            ->assertSee('Postbank');
    }

    public function test_providers_tab_in_settings_is_only_visible_for_admins(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'is_active' => true]);
        $user = User::factory()->create(['is_admin' => false, 'is_active' => true]);

        $this->actingAs($admin)->get(route('admin.providers.index'))
            ->assertOk()
            ->assertSee(route('admin.providers.index'), false);

        $this->actingAs($admin)->get(route('categories.index'))->assertOk()->assertSee('>Anbieter<', false);
        $this->actingAs($user)->get(route('categories.index'))->assertOk()->assertDontSee('>Anbieter<', false);
    }
}
