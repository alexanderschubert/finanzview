<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BudgetService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CategoryHierarchyTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Account $giro;

    private Category $food;

    private Category $market;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['is_active' => true]);

        $this->giro = Account::create([
            'user_id' => $this->user->id, 'name' => 'Girokonto', 'type' => 'checking',
            'currency' => 'EUR', 'opening_balance' => 0, 'include_in_total' => true, 'is_active' => true,
        ]);

        $this->food = $this->category('Lebensmittel', 'expense');
        $this->market = $this->category('Supermarkt', 'expense', $this->food);
    }

    private function category(string $name, string $type, ?Category $parent = null, array $extra = []): Category
    {
        return Category::create([
            'user_id' => $this->user->id, 'parent_id' => $parent?->id, 'name' => $name, 'type' => $type,
            'icon' => '🛒', 'is_active' => true, ...$extra,
        ]);
    }

    private function spend(Category $category, float $amount, string $date = '2026-06-10'): Transaction
    {
        return Transaction::create([
            'user_id' => $this->user->id, 'account_id' => $this->giro->id, 'category_id' => $category->id,
            'type' => 'expense', 'amount' => $amount, 'transaction_date' => $date, 'description' => 'Einkauf',
        ]);
    }

    public function test_subcategory_can_be_created_and_takes_the_type_of_its_parent(): void
    {
        $this->actingAs($this->user)
            ->post(route('categories.store'), [
                'parent_id' => $this->food->id, 'name' => 'Bäckerei', 'type' => 'income', 'icon' => '🥐',
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Unterkategorie wurde erstellt.');

        $bakery = Category::where('name', 'Bäckerei')->firstOrFail();
        $this->assertSame($this->food->id, $bakery->parent_id);
        $this->assertSame('expense', $bakery->type);
        $this->assertSame('Lebensmittel › Bäckerei', $bakery->display_name);

        // Parent mit „Beides“ erlaubt jede Art.
        $other = $this->category('Sonstiges', 'both');

        $this->actingAs($this->user)
            ->post(route('categories.store'), ['parent_id' => $other->id, 'name' => 'Erstattung', 'type' => 'income']);

        $this->assertSame('income', Category::where('name', 'Erstattung')->value('type'));
    }

    public function test_tree_is_shown_in_the_category_list_with_add_link(): void
    {
        $this->actingAs($this->user)
            ->get(route('categories.index'))
            ->assertOk()
            ->assertSee('Supermarkt')
            ->assertSee('↳')
            ->assertSee(route('categories.create', ['parent' => $this->food->id]), false);

        $this->actingAs($this->user)
            ->get(route('categories.create', ['parent' => $this->food->id]))
            ->assertOk()
            ->assertSee('Neue Unterkategorie')
            ->assertSee('Unter „Lebensmittel“.', false);
    }

    public function test_parent_validation(): void
    {
        $other = User::factory()->create();
        $foreign = Category::create(['user_id' => $other->id, 'name' => 'Fremd', 'type' => 'expense', 'is_active' => true]);

        // Fremde Kategorie, Unterkategorie als Elternteil (zu tief), inaktive Kategorie.
        foreach ([$foreign->id, $this->market->id, 99999] as $invalidParent) {
            $this->actingAs($this->user)
                ->post(route('categories.store'), ['parent_id' => $invalidParent, 'name' => 'X', 'type' => 'expense'])
                ->assertSessionHasErrors('parent_id');
        }

        $inactive = $this->category('Alt', 'expense', null, ['is_active' => false]);
        $this->actingAs($this->user)
            ->post(route('categories.store'), ['parent_id' => $inactive->id, 'name' => 'X', 'type' => 'expense'])
            ->assertSessionHasErrors('parent_id');

        // Sich selbst oder eine Kategorie mit Unterkategorien unterordnen.
        $this->actingAs($this->user)
            ->put(route('categories.update', $this->food), ['parent_id' => $this->food->id, 'name' => 'Lebensmittel', 'type' => 'expense', 'is_active' => '1'])
            ->assertSessionHasErrors('parent_id');

        $drinks = $this->category('Getränke', 'expense');

        $this->actingAs($this->user)
            ->put(route('categories.update', $this->food), ['parent_id' => $drinks->id, 'name' => 'Lebensmittel', 'type' => 'expense', 'is_active' => '1'])
            ->assertSessionHasErrors('parent_id');

        $this->assertNull($this->food->fresh()->parent_id);
    }

    public function test_parent_type_cannot_change_if_children_would_conflict(): void
    {
        $this->actingAs($this->user)
            ->put(route('categories.update', $this->food), ['name' => 'Lebensmittel', 'type' => 'income', 'is_active' => '1'])
            ->assertSessionHasErrors('type');

        $this->actingAs($this->user)
            ->put(route('categories.update', $this->food), ['name' => 'Lebensmittel', 'type' => 'both', 'is_active' => '1'])
            ->assertSessionHasNoErrors();
    }

    public function test_selects_show_full_names_with_children_right_after_their_parent(): void
    {
        $this->category('Wohnen', 'expense');
        $this->category('Bäckerei', 'expense', $this->food);

        $content = $this->actingAs($this->user)->get(route('transactions.create'))->assertOk()->getContent();

        $positions = array_map(fn ($label) => strpos($content, $label), ['Lebensmittel</option>', 'Lebensmittel › Bäckerei', 'Lebensmittel › Supermarkt', 'Wohnen</option>']);

        $this->assertNotContains(false, $positions);
        $this->assertSame($positions, collect($positions)->sort()->values()->all());
    }

    public function test_transaction_filter_by_parent_includes_children(): void
    {
        $this->spend($this->food, 10)->update(['description' => 'Direkt in Hauptkategorie']);
        $this->spend($this->market, 20)->update(['description' => 'Im Supermarkt']);
        $this->spend($this->category('Wohnen', 'expense'), 30)->update(['description' => 'Miete']);

        $this->actingAs($this->user)
            ->get(route('transactions.index', ['category_id' => $this->food->id]))
            ->assertSee('Direkt in Hauptkategorie')
            ->assertSee('Im Supermarkt')
            ->assertDontSee('Miete');

        $this->actingAs($this->user)
            ->get(route('transactions.index', ['category_id' => $this->market->id]))
            ->assertSee('Im Supermarkt')
            ->assertDontSee('Direkt in Hauptkategorie');
    }

    public function test_budget_on_parent_counts_subcategory_spending(): void
    {
        $budget = Budget::create([
            'user_id' => $this->user->id, 'name' => 'Essen', 'amount' => 400,
            'period' => 'monthly', 'start_date' => '2026-01-01', 'is_active' => true,
        ]);
        $budget->categories()->attach($this->food->id);

        $this->spend($this->food, 50);
        $this->spend($this->market, 120);

        $result = app(BudgetService::class)->calculate($budget->load('categories'), $this->user, Carbon::create(2026, 6, 1));

        $this->assertEqualsWithDelta(170, $result['spent'], 0.001);

        $this->actingAs($this->user)
            ->get(route('budgets.show', ['budget' => $budget, 'month' => '2026-06']))
            ->assertOk()
            ->assertSee('Lebensmittel › Supermarkt');
    }

    public function test_deleting_a_parent_promotes_children_archiving_keeps_them(): void
    {
        $this->actingAs($this->user)->delete(route('categories.destroy', $this->food))->assertSessionHas('success', 'Kategorie wurde gelöscht.');

        $this->assertNull($this->market->fresh()->parent_id);
        $this->assertSame('Supermarkt', $this->market->fresh()->display_name);

        $housing = $this->category('Wohnen', 'expense');
        $rent = $this->category('Miete', 'expense', $housing);
        $this->spend($housing, 700);

        $this->actingAs($this->user)->delete(route('categories.destroy', $housing))
            ->assertSessionHas('success', 'Die Kategorie wurde archiviert, da bereits Buchungen vorhanden sind.');

        $this->assertFalse($housing->fresh()->is_active);
        $this->assertSame($housing->id, $rent->fresh()->parent_id);
    }

    public function test_backup_round_trip_keeps_hierarchy_even_if_child_comes_first(): void
    {
        $this->spend($this->market, 20);

        $payload = json_decode($this->actingAs($this->user)->post(route('settings.data-export.json'))->getContent(), true);

        $this->assertSame($this->food->id, collect($payload['categories'])->firstWhere('name', 'Supermarkt')['parent_id']);

        // Reihenfolge umdrehen: Unterkategorie vor Hauptkategorie.
        $payload['categories'] = array_reverse($payload['categories']);

        $target = User::factory()->create(['is_active' => true]);

        $this->actingAs($target)
            ->post(route('settings.data-export.import'), ['backup' => UploadedFile::fake()->createWithContent('backup.json', json_encode($payload))])
            ->assertOk();

        $this->withSession(['finanzview_import_token' => session('finanzview_import_token')])
            ->actingAs($target)
            ->post(route('settings.data-export.import.restore'), ['token' => session('finanzview_import_token'), 'confirm' => '1'])
            ->assertSessionHasNoErrors();

        $restored = Category::where('user_id', $target->id)->where('name', 'Supermarkt')->firstOrFail();

        $this->assertSame('Lebensmittel › Supermarkt', $restored->display_name);
    }

    public function test_monthly_report_uses_full_category_names(): void
    {
        $this->spend($this->market, 20);

        $this->actingAs($this->user)
            ->get(route('reports.month', ['month' => '2026-06']))
            ->assertOk()
            ->assertSee('Lebensmittel › Supermarkt');
    }
}
