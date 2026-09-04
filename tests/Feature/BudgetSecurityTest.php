<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'is_active' => true,
        ], $attributes));
    }

    private function createCategory(User $user, array $attributes = []): Category
    {
        return Category::create(array_merge([
            'user_id' => $user->id,
            'name' => 'Testkategorie',
            'type' => 'expense',
            'is_active' => true,
        ], $attributes));
    }

    private function createBudget(User $user, array $attributes = []): Budget
    {
        return Budget::create(array_merge([
            'user_id' => $user->id,
            'name' => 'Testbudget',
            'amount' => 500.00,
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'period' => 'monthly',
            'is_active' => true,
        ], $attributes));
    }

    private function validBudgetData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Neues Budget',
            'amount' => '750.00',
            'period' => 'monthly',
            'start_date' => '2026-09-01',
            'end_date' => '2026-09-30',
            'is_active' => '1',
        ], $overrides);
    }

    public function test_user_can_view_own_budget(): void
    {
        $user = $this->createUser();
        $budget = $this->createBudget($user);

        $response = $this
            ->actingAs($user)
            ->get(route('budgets.show', $budget));

        $response->assertOk();
        $response->assertViewIs('budgets.show');
        $response->assertViewHas('budget', fn ($viewBudget) => $viewBudget->id === $budget->id);
    }

    public function test_user_cannot_view_another_users_budget(): void
    {
        $user = $this->createUser();
        $otherUser = $this->createUser();
        $budget = $this->createBudget($otherUser);

        $response = $this
            ->actingAs($user)
            ->get(route('budgets.show', $budget));

        $response->assertForbidden();
    }

    public function test_user_can_create_own_budget_with_own_category(): void
    {
        $user = $this->createUser();
        $category = $this->createCategory($user);

        $response = $this
            ->actingAs($user)
            ->post(route('budgets.store'), $this->validBudgetData([
                'category_ids' => [$category->id],
            ]));

        $response->assertRedirect(route('budgets.index'));
        $response->assertSessionHas('success');

        $budget = Budget::where('user_id', $user->id)
            ->where('name', 'Neues Budget')
            ->first();

        $this->assertNotNull($budget);

        $this->assertDatabaseHas('budget_category', [
            'budget_id' => $budget->id,
            'category_id' => $category->id,
        ]);
    }

    public function test_user_cannot_assign_another_users_category_when_creating_budget(): void
    {
        $user = $this->createUser();
        $otherUser = $this->createUser();

        $foreignCategory = $this->createCategory($otherUser, [
            'name' => 'Fremde Kategorie',
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('budgets.store'), $this->validBudgetData([
                'category_ids' => [$foreignCategory->id],
            ]));

        $response->assertRedirect(route('budgets.index'));
        $response->assertSessionHas('success');

        $budget = Budget::where('user_id', $user->id)
            ->where('name', 'Neues Budget')
            ->first();

        $this->assertNotNull($budget);

        $this->assertDatabaseMissing('budget_category', [
            'budget_id' => $budget->id,
            'category_id' => $foreignCategory->id,
        ]);
    }

    public function test_user_can_edit_own_budget(): void
    {
        $user = $this->createUser();
        $budget = $this->createBudget($user);

        $response = $this
            ->actingAs($user)
            ->put(route('budgets.update', $budget), $this->validBudgetData([
                'name' => 'Geändertes Budget',
                'amount' => '900.00',
            ]));

        $response->assertRedirect(route('budgets.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('budgets', [
            'id' => $budget->id,
            'user_id' => $user->id,
            'name' => 'Geändertes Budget',
            'amount' => 900.00,
        ]);
    }

    public function test_user_cannot_edit_another_users_budget(): void
    {
        $user = $this->createUser();
        $otherUser = $this->createUser();

        $budget = $this->createBudget($otherUser);

        $response = $this
            ->actingAs($user)
            ->put(route('budgets.update', $budget), $this->validBudgetData([
                'name' => 'Manipuliertes Budget',
                'amount' => '9999.00',
            ]));

        $response->assertForbidden();

        $this->assertDatabaseHas('budgets', [
            'id' => $budget->id,
            'user_id' => $otherUser->id,
            'name' => 'Testbudget',
            'amount' => 500.00,
        ]);
    }

    public function test_user_cannot_assign_another_users_category_when_updating_budget(): void
    {
        $user = $this->createUser();
        $otherUser = $this->createUser();

        $ownCategory = $this->createCategory($user, [
            'name' => 'Eigene Kategorie',
        ]);

        $foreignCategory = $this->createCategory($otherUser, [
            'name' => 'Fremde Kategorie',
        ]);

        $budget = $this->createBudget($user);

        $response = $this
            ->actingAs($user)
            ->put(route('budgets.update', $budget), $this->validBudgetData([
                'category_ids' => [
                    $ownCategory->id,
                    $foreignCategory->id,
                ],
            ]));

        $response->assertRedirect(route('budgets.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('budget_category', [
            'budget_id' => $budget->id,
            'category_id' => $ownCategory->id,
        ]);

        $this->assertDatabaseMissing('budget_category', [
            'budget_id' => $budget->id,
            'category_id' => $foreignCategory->id,
        ]);
    }

    public function test_user_can_delete_own_budget(): void
    {
        $user = $this->createUser();
        $category = $this->createCategory($user);
        $budget = $this->createBudget($user);

        $budget->categories()->attach($category->id);

        $response = $this
            ->actingAs($user)
            ->delete(route('budgets.destroy', $budget));

        $response->assertRedirect(route('budgets.index'));
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('budgets', [
            'id' => $budget->id,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseMissing('budget_category', [
            'budget_id' => $budget->id,
            'category_id' => $category->id,
        ]);
    }

    public function test_user_cannot_delete_another_users_budget(): void
    {
        $user = $this->createUser();
        $otherUser = $this->createUser();

        $budget = $this->createBudget($otherUser);

        $response = $this
            ->actingAs($user)
            ->delete(route('budgets.destroy', $budget));

        $response->assertForbidden();

        $this->assertDatabaseHas('budgets', [
            'id' => $budget->id,
            'user_id' => $otherUser->id,
            'name' => 'Testbudget',
        ]);
    }

    public function test_custom_budget_requires_end_date(): void
    {
        $user = $this->createUser();

        $response = $this
            ->actingAs($user)
            ->post(route('budgets.store'), $this->validBudgetData([
                'period' => 'custom',
                'end_date' => null,
            ]));

        $response->assertSessionHasErrors('end_date');

        $this->assertDatabaseMissing('budgets', [
            'user_id' => $user->id,
            'name' => 'Neues Budget',
        ]);
    }
}
