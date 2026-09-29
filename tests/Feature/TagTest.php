<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Tag;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TagService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Account $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['is_active' => true]);

        $this->account = Account::create([
            'user_id' => $this->user->id, 'name' => 'Girokonto', 'type' => 'checking',
            'currency' => 'EUR', 'opening_balance' => 0, 'include_in_total' => true, 'is_active' => true,
        ]);
    }

    private function store(array $data = [])
    {
        return $this->actingAs($this->user)->post(route('transactions.store'), [
            'account_id' => $this->account->id,
            'type' => 'expense',
            'amount' => 25,
            'transaction_date' => '2026-09-10',
            'description' => 'Hotel',
            ...$data,
        ]);
    }

    public function test_parse_trims_hashes_duplicates_and_limits(): void
    {
        $service = new TagService();

        $this->assertSame(['Urlaub 2026', 'geschäftlich'], $service->parse(' #Urlaub   2026, geschäftlich,urlaub 2026 , ,'));
        $this->assertCount(TagService::MAX_TAGS, $service->parse(implode(',', range(1, 15))));
        $this->assertSame([], $service->parse(null));
    }

    public function test_tags_are_created_and_reused_case_insensitively(): void
    {
        $this->store(['tags' => 'Urlaub 2026, Geschäftlich'])->assertSessionHasNoErrors();
        $this->store(['description' => 'Essen', 'tags' => '#urlaub 2026'])->assertSessionHasNoErrors();

        $this->assertSame(2, Tag::where('user_id', $this->user->id)->count());

        $urlaub = Tag::where('name', 'Urlaub 2026')->firstOrFail();
        $this->assertSame(2, $urlaub->transactions()->count());
    }

    public function test_update_replaces_tags_and_form_shows_them(): void
    {
        $this->store(['tags' => 'Urlaub 2026, Geschäftlich']);
        $transaction = Transaction::firstOrFail();

        $this->actingAs($this->user)
            ->get(route('transactions.edit', $transaction))
            ->assertOk()
            ->assertSee('value="Urlaub 2026, Geschäftlich"', false)
            ->assertSee('data-tag-suggestion="Geschäftlich"', false);

        $this->actingAs($this->user)
            ->put(route('transactions.update', $transaction), [
                'account_id' => $this->account->id,
                'type' => 'expense',
                'amount' => 25,
                'transaction_date' => '2026-09-10',
                'description' => 'Hotel',
                'tags' => 'Geschäftlich',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(['Geschäftlich'], $transaction->fresh()->tags->pluck('name')->all());
    }

    public function test_list_shows_chips_and_filters_by_tag(): void
    {
        $this->store(['description' => 'Hotel Rom', 'tags' => 'Urlaub 2026']);
        $this->store(['description' => 'Wocheneinkauf']);

        $tag = Tag::firstOrFail();

        $this->actingAs($this->user)
            ->get(route('transactions.index'))
            ->assertOk()
            ->assertSee('Urlaub 2026')
            ->assertSee('Wocheneinkauf');

        $this->actingAs($this->user)
            ->get(route('transactions.index', ['tag' => $tag->id]))
            ->assertOk()
            ->assertSee('Hotel Rom')
            ->assertDontSee('Wocheneinkauf')
            ->assertSee('1 Buchung gefunden');
    }

    public function test_tags_page_shows_totals_and_supports_rename_color_delete(): void
    {
        $this->store(['amount' => 100, 'tags' => 'Urlaub']);
        $this->store(['amount' => 50.5, 'description' => 'Restaurant', 'tags' => 'Urlaub']);
        $this->store(['type' => 'income', 'amount' => 20, 'description' => 'Erstattung', 'tags' => 'Urlaub']);
        $this->store(['description' => 'Anderes', 'tags' => 'Projekt']);

        $tag = Tag::where('name', 'Urlaub')->firstOrFail();

        $this->actingAs($this->user)->get(route('categories.index'))->assertSee(route('tags.index'), false);

        $this->actingAs($this->user)
            ->get(route('tags.index'))
            ->assertOk()
            ->assertSee('3 Buchungen')
            ->assertSee('−150,50 €')
            ->assertSee('+20,00 €');

        // Umbenennen auf einen vorhandenen Namen ist nicht erlaubt.
        $this->actingAs($this->user)
            ->put(route('tags.update', $tag), ['name' => 'projekt'])
            ->assertSessionHasErrors('name');

        $this->actingAs($this->user)
            ->put(route('tags.update', $tag), ['name' => '#Urlaub Italien', 'color' => '#34c759'])
            ->assertSessionHasNoErrors();

        $tag->refresh();
        $this->assertSame('Urlaub Italien', $tag->name);
        $this->assertSame('#34c759', $tag->displayColor());

        $this->actingAs($this->user)->delete(route('tags.destroy', $tag))->assertRedirect(route('tags.index'));

        $this->assertNull(Tag::find($tag->id));
        $this->assertSame(4, Transaction::count());
    }

    public function test_tags_of_other_users_are_protected(): void
    {
        $this->store(['tags' => 'Privat']);
        $tag = Tag::firstOrFail();

        $other = User::factory()->create(['is_active' => true]);

        $this->actingAs($other)->get(route('tags.edit', $tag))->assertNotFound();
        $this->actingAs($other)->delete(route('tags.destroy', $tag))->assertNotFound();
        $this->actingAs($other)->get(route('tags.index'))->assertOk()->assertDontSee('Privat');

        // Gleicher Name bei anderem Benutzer = eigener Tag.
        $otherAccount = Account::create([
            'user_id' => $other->id, 'name' => 'Konto', 'type' => 'checking',
            'currency' => 'EUR', 'opening_balance' => 0, 'include_in_total' => true, 'is_active' => true,
        ]);

        $this->actingAs($other)->post(route('transactions.store'), [
            'account_id' => $otherAccount->id, 'type' => 'expense', 'amount' => 5,
            'transaction_date' => '2026-09-10', 'description' => 'Test', 'tags' => 'Privat',
        ])->assertSessionHasNoErrors();

        $this->assertSame(2, Tag::where('name', 'Privat')->count());
        $this->assertSame(1, $tag->transactions()->count());
    }
}
