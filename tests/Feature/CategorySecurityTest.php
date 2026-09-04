<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategorySecurityTest extends TestCase
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
            'icon' => null,
            'color' => null,
            'description' => null,
            'is_active' => true,
        ], $attributes));
    }

    private function createAccount(User $user, array $attributes = []): Account
    {
        return Account::create(array_merge([
            'user_id' => $user->id,
            'name' => 'Testkonto',
            'institution' => 'Test Bank',
            'type' => 'checking',
            'currency' => 'EUR',
            'opening_balance' => 1000,
            'credit_limit' => null,
            'iban' => null,
            'account_number' => null,
            'color' => null,
            'icon' => null,
            'notes' => null,
            'include_in_total' => true,
            'is_active' => true,
        ], $attributes));
    }

    private function createTransaction(
        User $user,
        Account $account,
        Category $category,
        array $attributes = []
    ): Transaction {
        return Transaction::create(array_merge([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => 25.00,
            'description' => 'Testbuchung',
            'transaction_date' => '2026-09-01',
        ], $attributes));
    }

    private function validCategoryData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Neue Kategorie',
            'type' => 'expense',
            'icon' => null,
            'color' => null,
            'description' => 'Testbeschreibung',
        ], $overrides);
    }

    public function test_user_can_create_own_category(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->post(
            route('categories.store'),
            $this->validCategoryData()
        );

        $response
            ->assertRedirect(route('categories.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('categories', [
            'user_id' => $user->id,
            'name' => 'Neue Kategorie',
            'type' => 'expense',
            'is_active' => true,
        ]);
    }

    public function test_user_cannot_create_category_with_invalid_type(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->post(
            route('categories.store'),
            $this->validCategoryData([
                'type' => 'invalid',
            ])
        );

        $response->assertSessionHasErrors('type');

        $this->assertDatabaseMissing('categories', [
            'user_id' => $user->id,
            'name' => 'Neue Kategorie',
        ]);
    }

    public function test_user_cannot_create_category_for_another_user(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->post(
            route('categories.store'),
            $this->validCategoryData([
                'user_id' => 999999,
            ])
        );

        $response
            ->assertRedirect(route('categories.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('categories', [
            'user_id' => $user->id,
            'name' => 'Neue Kategorie',
        ]);

        $this->assertDatabaseMissing('categories', [
            'user_id' => 999999,
            'name' => 'Neue Kategorie',
        ]);
    }

    public function test_user_can_view_own_category(): void
    {
        $user = $this->createUser();
        $category = $this->createCategory($user);

        $response = $this->actingAs($user)
            ->get(route('categories.edit', $category));

        $response->assertOk();
        $response->assertViewIs('categories.edit');
    }

    public function test_user_cannot_view_another_users_category(): void
    {
        $owner = $this->createUser();
        $attacker = $this->createUser();

        $category = $this->createCategory($owner);

        $response = $this->actingAs($attacker)
            ->get(route('categories.edit', $category));

        $response->assertForbidden();
    }

    public function test_user_can_edit_own_category(): void
    {
        $user = $this->createUser();
        $category = $this->createCategory($user);

        $response = $this->actingAs($user)->put(
            route('categories.update', $category),
            $this->validCategoryData([
                'name' => 'Geänderte Kategorie',
                'type' => 'income',
                'is_active' => true,
            ])
        );

        $response
            ->assertRedirect(route('categories.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'user_id' => $user->id,
            'name' => 'Geänderte Kategorie',
            'type' => 'income',
            'is_active' => true,
        ]);
    }

    public function test_user_cannot_edit_another_users_category(): void
    {
        $owner = $this->createUser();
        $attacker = $this->createUser();

        $category = $this->createCategory($owner);

        $response = $this->actingAs($attacker)->put(
            route('categories.update', $category),
            $this->validCategoryData([
                'name' => 'Unbefugte Änderung',
                'type' => 'income',
                'is_active' => false,
            ])
        );

        $response->assertForbidden();

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'user_id' => $owner->id,
            'name' => 'Testkategorie',
            'type' => 'expense',
            'is_active' => true,
        ]);
    }

    public function test_user_can_delete_own_category_without_transactions(): void
    {
        $user = $this->createUser();
        $category = $this->createCategory($user);

        $response = $this->actingAs($user)
            ->delete(route('categories.destroy', $category));

        $response
            ->assertRedirect(route('categories.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('categories', [
            'id' => $category->id,
        ]);
    }

    public function test_user_cannot_delete_another_users_category(): void
    {
        $owner = $this->createUser();
        $attacker = $this->createUser();

        $category = $this->createCategory($owner);

        $response = $this->actingAs($attacker)
            ->delete(route('categories.destroy', $category));

        $response->assertForbidden();

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'user_id' => $owner->id,
            'deleted_at' => null,
            'is_active' => true,
        ]);
    }

    public function test_category_with_transactions_is_archived_instead_of_deleted(): void
    {
        $user = $this->createUser();
        $account = $this->createAccount($user);
        $category = $this->createCategory($user);

        $this->createTransaction($user, $account, $category);

        $response = $this->actingAs($user)
            ->delete(route('categories.destroy', $category));

        $response
            ->assertRedirect(route('categories.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'user_id' => $user->id,
            'is_active' => false,
            'deleted_at' => null,
        ]);
    }

    public function test_user_can_deactivate_own_category(): void
    {
        $user = $this->createUser();
        $category = $this->createCategory($user);

        $response = $this->actingAs($user)->put(
            route('categories.update', $category),
            $this->validCategoryData([
                'name' => 'Inaktive Kategorie',
                'is_active' => false,
            ])
        );

        $response->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'user_id' => $user->id,
            'is_active' => false,
        ]);
    }

    public function test_user_can_use_both_category_type(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->post(
            route('categories.store'),
            $this->validCategoryData([
                'name' => 'Gemischte Kategorie',
                'type' => 'both',
            ])
        );

        $response->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', [
            'user_id' => $user->id,
            'name' => 'Gemischte Kategorie',
            'type' => 'both',
        ]);
    }
}
