<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\FinancialProvider;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsPagesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['is_active' => true]);
    }

    public function test_all_settings_pages_render_with_back_link(): void
    {
        foreach (['settings.profile', 'settings.security', 'settings.appearance', 'settings.financial', 'settings.dashboard', 'settings.data-export'] as $route) {
            $this->actingAs($this->user)
                ->get(route($route))
                ->assertOk()
                ->assertSee('href="' . route('settings.index') . '"', false);
        }
    }

    public function test_default_account_and_category_are_preselected_for_new_transactions(): void
    {
        $giro = Account::create([
            'user_id' => $this->user->id, 'name' => 'Girokonto', 'type' => 'checking',
            'currency' => 'EUR', 'opening_balance' => 0, 'include_in_total' => true, 'is_active' => true,
        ]);

        $food = Category::create([
            'user_id' => $this->user->id, 'name' => 'Lebensmittel', 'type' => 'expense',
            'icon' => '🛒', 'is_active' => true,
        ]);

        $this->actingAs($this->user)
            ->put(route('settings.financial.update'), [
                'currency' => 'EUR',
                'decimal_places' => 2,
                'date_format' => 'd.m.Y',
                'first_day_of_week' => 1,
                'default_account_id' => $giro->id,
                'default_category_id' => $food->id,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame($giro->id, Setting::where('user_id', $this->user->id)->value('default_account_id'));

        $response = $this->actingAs($this->user)
            ->get(route('transactions.create'))
            ->assertOk()
            ->assertSee('value="' . $giro->id . '" selected', false);

        $this->assertMatchesRegularExpression(
            '/value="' . $food->id . '"\s+data-type="expense"\s+selected/',
            $response->getContent()
        );
    }

    public function test_inactive_default_account_is_ignored(): void
    {
        $old = Account::create([
            'user_id' => $this->user->id, 'name' => 'Altkonto', 'type' => 'checking',
            'currency' => 'EUR', 'opening_balance' => 0, 'include_in_total' => true, 'is_active' => false,
        ]);

        Setting::create([
            'user_id' => $this->user->id, 'currency' => 'EUR', 'decimal_places' => 2,
            'date_format' => 'd.m.Y', 'first_day_of_week' => 1, 'default_account_id' => $old->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('transactions.create'))
            ->assertOk()
            ->assertViewHas('defaultAccountId', null);
    }

    public function test_appearance_is_saved(): void
    {
        $this->actingAs($this->user)
            ->put(route('settings.appearance.update'), ['theme' => 'dark'])
            ->assertSessionHasNoErrors();

        $this->assertSame('dark', $this->user->fresh()->theme);

        $this->actingAs($this->user)
            ->get(route('settings.appearance'))
            ->assertSee('value="dark" class="sr-only peer" checked', false);
    }

    public function test_dashboard_settings_save_order_and_widgets(): void
    {
        $this->actingAs($this->user)
            ->get(route('settings.dashboard'))
            ->assertOk()
            ->assertSee('data-move="up"', false);

        $this->actingAs($this->user)
            ->put(route('settings.dashboard.update'), [
                'display_mode' => 'compact',
                'widgets' => ['summary', 'budgets'],
                'widget_order' => 'budgets,summary',
            ])
            ->assertSessionHasNoErrors();

        $setting = $this->user->fresh()->dashboardSetting;

        $this->assertSame('compact', $setting->display_mode);
        $this->assertSame(['budgets', 'summary'], array_slice($setting->effectiveWidgetOrder(), 0, 2));
        $this->assertTrue($setting->isWidgetEnabled('budgets'));
        $this->assertFalse($setting->isWidgetEnabled('income'));
    }

    public function test_admin_cannot_toggle_own_switches_but_can_edit_others(): void
    {
        $this->user->forceFill(['is_admin' => true])->save();
        $other = User::factory()->create(['is_active' => true, 'name' => 'Erika']);

        $this->actingAs($this->user)
            ->get(route('admin.users.edit', $this->user))
            ->assertOk()
            ->assertSee('Du kannst dich nicht selbst deaktivieren.');

        // Eigene Seite: deaktivierte Schalter senden die versteckten Werte "1".
        $this->actingAs($this->user)
            ->patch(route('admin.users.update', $this->user), [
                'name' => $this->user->name,
                'email' => $this->user->email,
                'is_active' => '1',
                'is_admin' => '1',
            ])
            ->assertSessionHasNoErrors();

        $this->assertTrue($this->user->fresh()->is_admin);

        $this->actingAs($this->user)
            ->patch(route('admin.users.update', $other), [
                'name' => 'Erika Muster',
                'email' => $other->email,
                'is_active' => '0',
                'is_admin' => '0',
            ])
            ->assertSessionHasNoErrors();

        $this->assertFalse($other->fresh()->is_active);
        $this->assertSame('Erika Muster', $other->fresh()->name);
    }

    public function test_provider_pages_render_and_store(): void
    {
        $this->user->forceFill(['is_admin' => true])->save();

        $this->actingAs($this->user)->get(route('admin.providers.create'))->assertOk()->assertSee('Anbieter anlegen');

        $this->actingAs($this->user)
            ->post(route('admin.providers.store'), [
                'name' => 'Testbank',
                'type' => 'bank',
                'emoji' => '🏦',
                'color' => '#1f5fa8',
                'is_active' => '1',
            ])
            ->assertSessionHasNoErrors();

        $provider = FinancialProvider::where('name', 'Testbank')->firstOrFail();

        $this->actingAs($this->user)
            ->get(route('admin.providers.index'))
            ->assertOk()
            ->assertSee('Testbank')
            ->assertSee('nicht verwendet');

        $this->actingAs($this->user)
            ->get(route('admin.providers.edit', $provider))
            ->assertOk()
            ->assertSee('value="testbank"', false);
    }
}
