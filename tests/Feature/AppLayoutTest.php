<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_layout_renders_navigation_and_marks_current_page(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $response = $this->actingAs($user)->get(route('transactions.index'));

        $response->assertOk();
        $response->assertSee('aria-label="Hauptnavigation"', false);
        $response->assertSee('href="' . route('transactions.index') . '"', false);
        $response->assertSee('aria-current="page"', false);
        $response->assertSee('aria-label="FinanzView"', false);
        $response->assertDontSee(route('admin.index'), false);
    }

    public function test_admin_sees_administration_link(): void
    {
        $admin = User::factory()->create(['is_active' => true]);
        $admin->forceFill(['is_admin' => true])->save();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('admin.index'), false);
    }
}
