<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\PlanGroup;
use App\Models\PlanItem;
use App\Models\PlanMonth;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Umzug in eine neue Instanz: Backup herunterladen und bei einem anderen
 * Benutzer wieder einspielen (Monatsplan, Einstellungen, Umbuchungs-Merker).
 */
class BackupMigrationTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create(['is_active' => true]);
    }

    private function export(User $user): string
    {
        $response = $this->actingAs($user)->post(route('settings.data-export.json'));
        $response->assertOk();

        return $response->getContent();
    }

    private function restore(User $user, string $content)
    {
        $this->actingAs($user)
            ->post(route('settings.data-export.import'), ['backup' => UploadedFile::fake()->createWithContent('backup.json', $content)])
            ->assertOk();

        $token = session('finanzview_import_token');

        return $this->withSession(['finanzview_import_token' => $token])
            ->actingAs($user)
            ->post(route('settings.data-export.import.restore'), ['token' => $token, 'confirm' => '1']);
    }

    /**
     * Quelle: Konto, Buchung mit verworfenem Umbuchungsvorschlag, Einstellungen und Plan.
     */
    private function seedSource(User $user): Account
    {
        $account = Account::create([
            'user_id' => $user->id, 'name' => 'Girokonto', 'type' => 'checking', 'currency' => 'EUR',
            'opening_balance' => 100, 'include_in_total' => true, 'is_active' => true,
        ]);

        $transaction = Transaction::create([
            'user_id' => $user->id, 'account_id' => $account->id, 'type' => 'expense',
            'amount' => 12.50, 'transaction_date' => '2026-11-03', 'description' => 'Kaffee',
        ]);
        DB::table('transactions')->where('id', $transaction->id)->update(['transfer_dismissed' => true]);

        Setting::create([
            'user_id' => $user->id, 'currency' => 'EUR', 'decimal_places' => 0,
            'date_format' => 'Y-m-d', 'first_day_of_week' => 7, 'default_account_id' => $account->id,
        ]);

        $plan = PlanMonth::create(['user_id' => $user->id, 'month' => '2026-11-01']);
        $housing = $plan->groups()->create(['name' => 'Wohnung', 'kind' => 'expense', 'sort_order' => 1]);
        $housing->items()->create(['name' => 'Miete', 'amount' => 664.45, 'sort_order' => 1]);
        $car = $plan->groups()->create(['name' => 'Auto', 'kind' => 'expense', 'sort_order' => 2]);
        $car->items()->create([
            'name' => 'KFZ-Steuer', 'amount' => 98, 'interval' => 'yearly', 'starts_on' => '2026-05-08',
            'ends_on' => '2030-05-31', 'note' => 'jährlich', 'sort_order' => 1,
        ]);
        $plan->groups()->create(['name' => 'Einnahmen', 'kind' => 'income', 'sort_order' => 3])
            ->items()->create(['name' => 'Gehalt', 'amount' => 2400, 'sort_order' => 1]);

        return $account;
    }

    public function test_export_contains_plan_and_settings(): void
    {
        $user = $this->user();
        $this->seedSource($user);

        $payload = json_decode($this->export($user), true);

        $this->assertSame('2026-11-01', $payload['plan_months'][0]['month']);
        $this->assertSame(['Wohnung', 'Auto', 'Einnahmen'], array_column($payload['plan_months'][0]['groups'], 'name'));
        $this->assertSame('Y-m-d', $payload['settings']['date_format']);
    }

    public function test_plan_settings_and_flags_move_to_another_user(): void
    {
        $source = $this->user();
        $this->seedSource($source);
        $content = $this->export($source);

        $target = $this->user();
        $this->restore($target, $content)->assertRedirect(route('settings.data-export'));

        $plan = PlanMonth::where('user_id', $target->id)->firstOrFail()->load('groups.items');

        $this->assertSame('2026-11', $plan->month->format('Y-m'));
        $this->assertSame(['Wohnung', 'Auto', 'Einnahmen'], $plan->groups->pluck('name')->all());
        $this->assertSame('income', $plan->groups->last()->kind);

        $tax = PlanItem::where('name', 'KFZ-Steuer')->whereHas('group.planMonth', fn ($q) => $q->where('user_id', $target->id))->firstOrFail();
        $this->assertSame('yearly', $tax->interval);
        $this->assertSame('2026-05-08', $tax->starts_on->toDateString());
        $this->assertSame('2030-05-31', $tax->ends_on->toDateString());
        $this->assertSame('98.00', $tax->amount);
        $this->assertSame('jährlich', $tax->note);

        // Einstellungen: Standardkonto zeigt auf das neue Konto des Zielbenutzers, nicht auf das der Quelle.
        $setting = Setting::where('user_id', $target->id)->firstOrFail();
        $targetAccount = Account::where('user_id', $target->id)->firstOrFail();
        $this->assertSame('Y-m-d', $setting->date_format);
        $this->assertSame(0, $setting->decimal_places);
        $this->assertSame(7, $setting->first_day_of_week);
        $this->assertSame($targetAccount->id, $setting->default_account_id);

        // Verworfene Umbuchungsvorschläge bleiben verworfen.
        $this->assertTrue((bool) Transaction::where('user_id', $target->id)->firstOrFail()->transfer_dismissed);

        // Die Quelle bleibt unverändert.
        $this->assertSame(3, PlanGroup::whereHas('planMonth', fn ($q) => $q->where('user_id', $source->id))->count());
    }

    public function test_importing_twice_does_not_duplicate_plan_items(): void
    {
        $source = $this->user();
        $this->seedSource($source);
        $content = $this->export($source);

        $target = $this->user();
        $this->restore($target, $content);
        $this->restore($target, $content);

        $this->assertSame(1, PlanMonth::where('user_id', $target->id)->count());
        $this->assertSame(3, PlanGroup::whereHas('planMonth', fn ($q) => $q->where('user_id', $target->id))->count());
        $this->assertSame(3, PlanItem::whereHas('group.planMonth', fn ($q) => $q->where('user_id', $target->id))->count());
    }

    public function test_existing_plan_month_is_extended_not_replaced(): void
    {
        $source = $this->user();
        $this->seedSource($source);
        $content = $this->export($source);

        $target = $this->user();
        $plan = PlanMonth::create(['user_id' => $target->id, 'month' => '2026-11-01']);
        $plan->groups()->create(['name' => 'wohnung', 'kind' => 'expense', 'sort_order' => 1])
            ->items()->create(['name' => 'Eigener Posten', 'amount' => 5]);

        $this->restore($target, $content);

        $this->assertSame(1, PlanMonth::where('user_id', $target->id)->count());
        $housing = PlanGroup::where('plan_month_id', $plan->id)->whereRaw('lower(name) = ?', ['wohnung'])->get();
        $this->assertCount(1, $housing);
        $this->assertEqualsCanonicalizing(['Eigener Posten', 'Miete'], $housing->first()->items->pluck('name')->all());
    }

    public function test_old_backup_without_new_sections_still_restores(): void
    {
        $source = $this->user();
        $this->seedSource($source);

        $payload = json_decode($this->export($source), true);
        unset($payload['plan_months'], $payload['settings']);

        $target = $this->user();
        $this->restore($target, json_encode($payload))->assertRedirect(route('settings.data-export'));

        $this->assertSame(0, PlanMonth::where('user_id', $target->id)->count());
        $this->assertSame(1, Transaction::where('user_id', $target->id)->count());
    }

    public function test_preview_shows_plan_counts(): void
    {
        $source = $this->user();
        $this->seedSource($source);

        $this->actingAs($source)
            ->post(route('settings.data-export.import'), ['backup' => UploadedFile::fake()->createWithContent('backup.json', $this->export($source))])
            ->assertOk()
            ->assertSee('Plan-Posten')
            ->assertSee('Monatspläne');
    }

    public function test_page_explains_the_move(): void
    {
        $this->actingAs($this->user())
            ->get(route('settings.data-export'))
            ->assertOk()
            ->assertSee('Umzug in eine neue FinanzView-Instanz');
    }
}
