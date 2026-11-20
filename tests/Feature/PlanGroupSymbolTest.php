<?php

namespace Tests\Feature;

use App\Models\PlanMonth;
use App\Models\User;
use App\Services\PlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanGroupSymbolTest extends TestCase
{
    use RefreshDatabase;

    public function test_group_icon_and_color_can_be_saved_and_are_shown(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $plan = PlanMonth::create(['user_id' => $user->id, 'month' => '2026-05-01']);
        $group = $plan->groups()->create(['name' => 'Versicherung', 'kind' => 'expense', 'sort_order' => 1]);

        $this->actingAs($user)
            ->put(route('plan.groups.update', $group), ['name' => 'Versicherung', 'kind' => 'expense', 'icon' => '🛡️', 'color' => '#4f46e5'])
            ->assertRedirect();

        $this->assertSame('🛡️', $group->fresh()->icon);
        $this->assertSame('#4f46e5', $group->fresh()->color);

        $this->actingAs($user)->get(route('plan.index', ['month' => '2026-05']))
            ->assertOk()
            ->assertSee('🛡️')
            ->assertSee('Bleibt im Monat übrig', false);

        $this->actingAs($user)->get(route('plan.groups.edit', $group))->assertOk()->assertSee('data-symbol-picker', false);
    }

    public function test_invalid_group_color_is_rejected(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $plan = PlanMonth::create(['user_id' => $user->id, 'month' => '2026-05-01']);
        $group = $plan->groups()->create(['name' => 'Auto', 'kind' => 'expense', 'sort_order' => 1]);

        $this->actingAs($user)
            ->put(route('plan.groups.update', $group), ['name' => 'Auto', 'kind' => 'expense', 'color' => 'rot'])
            ->assertSessionHasErrors('color');
    }

    public function test_symbol_is_kept_when_copying_the_plan(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $plan = PlanMonth::create(['user_id' => $user->id, 'month' => '2026-05-01']);
        $plan->groups()->create(['name' => 'Wohnen', 'kind' => 'expense', 'icon' => '🏠', 'color' => '#0e7490', 'sort_order' => 1]);

        $copy = app(PlanService::class)->copyFromPrevious($user, \Carbon\Carbon::parse('2026-06-01'));

        $this->assertSame('🏠', $copy->groups()->first()->icon);
        $this->assertSame('#0e7490', $copy->groups()->first()->color);
    }
}
