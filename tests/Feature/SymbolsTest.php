<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Category;
use App\Models\PlanMonth;
use App\Models\User;
use App\Support\Symbols;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SymbolsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['is_active' => true]);
    }

    public function test_symbol_set_is_large_and_searchable_in_german(): void
    {
        $data = json_decode((string) file_get_contents(public_path('symbols/lucide.json')), true);

        $this->assertGreaterThan(1000, count($data['icons']));
        $this->assertStringContainsString('auto', $data['icons']['car']['t']);
        $this->assertStringContainsString('einkaufen', $data['icons']['shopping-cart']['t']);
        $this->assertFileExists(public_path('symbols/LICENSE-lucide.txt'));
    }

    public function test_helper_separates_emoji_and_lucide_symbols(): void
    {
        $this->assertStringStartsWith('<svg', Symbols::svg('lucide:house'));
        $this->assertNull(Symbols::svg('🛒'));
        $this->assertNull(Symbols::svg('lucide:gibt-es-nicht'));
        $this->assertSame('', Symbols::text('lucide:house'));
        $this->assertSame('🛒', Symbols::text('🛒'));
    }

    public function test_category_accepts_emoji_and_known_symbol_but_not_unknown_names(): void
    {
        $base = ['name' => 'Wohnen', 'type' => 'expense'];

        $this->actingAs($this->user)->post(route('categories.store'), $base + ['icon' => 'lucide:house'])->assertSessionDoesntHaveErrors();
        $this->assertSame('lucide:house', Category::where('name', 'Wohnen')->value('icon'));

        $this->actingAs($this->user)->post(route('categories.store'), ['name' => 'Essen'] + $base + ['icon' => '🛒'])->assertSessionDoesntHaveErrors();

        $this->actingAs($this->user)->post(route('categories.store'), ['name' => 'Falsch'] + $base + ['icon' => 'lucide:gibt-es-nicht'])
            ->assertSessionHasErrors('icon');
        $this->assertNull(Category::where('name', 'Falsch')->first());
    }

    public function test_category_list_draws_the_symbol_and_options_stay_free_of_its_name(): void
    {
        Category::create(['user_id' => $this->user->id, 'name' => 'Wohnen', 'type' => 'expense', 'icon' => 'lucide:house', 'is_active' => true]);

        $this->actingAs($this->user)->get(route('categories.index'))
            ->assertOk()
            ->assertSee('<svg', false)
            ->assertDontSee('lucide:house', false);

        $this->actingAs($this->user)->get(route('categories.create'))
            ->assertOk()
            ->assertSee('data-sp-lucide-tab', false);
    }

    public function test_plan_group_icon_column_holds_a_symbol_and_api_validates_it(): void
    {
        PlanMonth::create(['user_id' => $this->user->id, 'month' => '2026-11-01']);
        [, $token] = ApiToken::issue($this->user, 'iPad');
        $headers = ['Authorization' => 'Bearer ' . $token];

        $this->postJson('/api/v1/plan/groups', ['month' => '2026-11', 'name' => 'Termine', 'kind' => 'expense', 'icon' => 'lucide:calendar-clock'], $headers)
            ->assertSuccessful();
        $this->assertSame('lucide:calendar-clock', $this->user->planMonths()->first()->groups()->first()->icon ?? \App\Models\PlanGroup::first()->icon);

        $this->postJson('/api/v1/plan/groups', ['month' => '2026-11', 'name' => 'X', 'kind' => 'expense', 'icon' => 'lucide:nope'], $headers)
            ->assertStatus(422);
    }
}
