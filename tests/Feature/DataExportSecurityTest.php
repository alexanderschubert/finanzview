<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DataExportSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'is_active' => true,
        ], $attributes));
    }

    private function validBackup(): array
    {
        return [
            'metadata' => [
                'application' => 'FinanzView',
                'format' => 'finanzview-export',
                'version' => 1,
                'exported_at' => now()->toIso8601String(),
            ],

            'accounts' => [
                [
                    'id' => 100,
                    'name' => 'Testkonto',
                    'institution' => 'Testbank',
                    'provider_id' => null,
                    'type' => 'checking',
                    'currency' => 'EUR',
                    'opening_balance' => '1000.00',
                    'credit_limit' => null,
                    'iban' => 'DE00123456789012345678',
                    'account_number' => '123456',
                    'color' => null,
                    'icon' => null,
                    'notes' => null,
                    'include_in_total' => true,
                    'is_active' => true,
                ],
            ],

            'categories' => [
                [
                    'id' => 200,
                    'name' => 'Test Kategorie',
                    'type' => 'expense',
                    'icon' => null,
                    'description' => null,
                    'is_active' => true,
                ],
            ],

            'tags' => [
                [
                    'id' => 300,
                    'name' => 'Test Tag',
                    'color' => '#000000',
                ],
            ],

            'transactions' => [
                [
                    'id' => 400,
                    'account_id' => 100,
                    'category_id' => 200,
                    'type' => 'expense',
                    'amount' => '25.50',
                    'transaction_date' => '2026-09-01',
                    'description' => 'Test Transaktion',
                    'merchant' => 'Test Händler',
                    'reference' => 'TEST-001',
                    'notes' => 'Test',
                    'is_pending' => false,
                    'is_recurring' => false,
                    'recurring_transaction_id' => null,
                    'tags' => [
                        [
                            'id' => 300,
                            'name' => 'Test Tag',
                            'color' => '#000000',
                        ],
                    ],
                ],
            ],

            'budgets' => [
                [
                    'id' => 500,
                    'name' => 'Test Budget',
                    'amount' => '500.00',
                    'start_date' => '2026-09-01',
                    'end_date' => '2026-09-30',
                    'period' => 'monthly',
                    'is_active' => true,
                    'color' => null,
                    'icon' => null,
                    'categories' => [
                        [
                            'id' => 200,
                            'name' => 'Test Kategorie',
                        ],
                    ],
                ],
            ],

            'recurring_transactions' => [],

            'credit_cards' => [],

            'loans' => [],

            'dashboard_settings' => null,
        ];
    }

    private function storeBackup(array $payload): string
    {
        Storage::disk('local')->makeDirectory('imports');

        $token = (string) \Illuminate\Support\Str::uuid();

        Storage::disk('local')->put(
            'imports/' . $token . '.json',
            json_encode(
                $payload,
                JSON_THROW_ON_ERROR
            )
        );

        return $token;
    }

    public function test_valid_backup_can_be_previewed(): void
    {
        $user = $this->createUser();

        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent(
            'backup.json',
            json_encode($this->validBackup(), JSON_THROW_ON_ERROR)
        );

        $response = $this
            ->actingAs($user)
            ->post(
                route('settings.data-export.import'),
                ['backup' => $file]
            );

        $response->assertOk();

        $response->assertViewIs(
            'settings.data-export-import-preview'
        );

        $response->assertViewHas('counts');

        $this->assertNotNull(
            session('finanzview_import_token')
        );
    }

    public function test_invalid_json_is_rejected(): void
    {
        $user = $this->createUser();

        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent(
            'backup.json',
            '{invalid-json'
        );

        $response = $this
            ->actingAs($user)
            ->post(
                route('settings.data-export.import'),
                ['backup' => $file]
            );

        $response
            ->assertSessionHasErrors('backup');
    }

    public function test_wrong_backup_format_is_rejected(): void
    {
        $user = $this->createUser();

        $payload = $this->validBackup();
        $payload['metadata']['format'] = 'other-format';

        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent(
            'backup.json',
            json_encode($payload, JSON_THROW_ON_ERROR)
        );

        $response = $this
            ->actingAs($user)
            ->post(
                route('settings.data-export.import'),
                ['backup' => $file]
            );

        $response
            ->assertSessionHasErrors('backup');
    }

    public function test_wrong_backup_version_is_rejected(): void
    {
        $user = $this->createUser();

        $payload = $this->validBackup();
        $payload['metadata']['version'] = 99;

        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent(
            'backup.json',
            json_encode($payload, JSON_THROW_ON_ERROR)
        );

        $response = $this
            ->actingAs($user)
            ->post(
                route('settings.data-export.import'),
                ['backup' => $file]
            );

        $response
            ->assertSessionHasErrors('backup');
    }

    public function test_missing_backup_section_is_rejected(): void
    {
        $user = $this->createUser();

        $payload = $this->validBackup();
        unset($payload['transactions']);

        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent(
            'backup.json',
            json_encode($payload, JSON_THROW_ON_ERROR)
        );

        $response = $this
            ->actingAs($user)
            ->post(
                route('settings.data-export.import'),
                ['backup' => $file]
            );

        $response
            ->assertSessionHasErrors('backup');
    }

    public function test_restore_requires_confirmation(): void
    {
        $user = $this->createUser();

        $token = $this->storeBackup(
            $this->validBackup()
        );

        $this
            ->withSession([
                'finanzview_import_token' => $token,
            ])
            ->actingAs($user)
            ->post(
                route('settings.data-export.import.restore'),
                [
                    'token' => $token,
                ]
            )
            ->assertSessionHasErrors('confirm');

        $this->assertDatabaseCount('accounts', 0);
    }

    public function test_restore_rejects_wrong_token(): void
    {
        $user = $this->createUser();

        $realToken = $this->storeBackup(
            $this->validBackup()
        );

        $wrongToken = (string) \Illuminate\Support\Str::uuid();

        $response = $this
            ->withSession([
                'finanzview_import_token' => $realToken,
            ])
            ->actingAs($user)
            ->post(
                route('settings.data-export.import.restore'),
                [
                    'token' => $wrongToken,
                    'confirm' => '1',
                ]
            );

        $response->assertForbidden();

        $this->assertDatabaseCount('accounts', 0);
    }

    public function test_valid_backup_is_restored_for_current_user(): void
    {
        $user = $this->createUser();

        $token = $this->storeBackup(
            $this->validBackup()
        );

        $response = $this
            ->withSession([
                'finanzview_import_token' => $token,
            ])
            ->actingAs($user)
            ->post(
                route('settings.data-export.import.restore'),
                [
                    'token' => $token,
                    'confirm' => '1',
                ]
            );

        $response
            ->assertRedirect(
                route('settings.data-export')
            );

        $this->assertDatabaseHas('accounts', [
            'user_id' => $user->id,
            'name' => 'Testkonto',
        ]);

        $this->assertDatabaseHas('categories', [
            'user_id' => $user->id,
            'name' => 'Test Kategorie',
        ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'description' => 'Test Transaktion',
        ]);

        $this->assertDatabaseHas('budgets', [
            'user_id' => $user->id,
            'name' => 'Test Budget',
        ]);
    }

    public function test_restore_allows_loan_without_account(): void
    {
        $user = $this->createUser();

        $payload = $this->validBackup();

        $payload['loans'] = [
            [
                'id' => 600,
                'account_id' => null,
                'name' => 'Test Kredit ohne Konto',
                'creditor_name' => 'Test Kreditgeber',
                'provider_id' => null,
                'creditor_icon' => null,
                'creditor_color' => null,
                'principal_amount' => '10000.00',
                'paid_amount' => '1000.00',
                'interest_rate' => '4.500',
                'installment_amount' => '250.00',
                'total_installments' => 40,
                'paid_installments' => 4,
                'start_date' => '2026-01-01',
                'end_date' => '2029-04-01',
                'type' => 'loan',
                'is_active' => true,
                'notes' => 'Test',
                'payments' => [],
            ],
        ];

        $token = $this->storeBackup($payload);

        $response = $this
            ->withSession([
                'finanzview_import_token' => $token,
            ])
            ->actingAs($user)
            ->post(
                route('settings.data-export.import.restore'),
                [
                    'token' => $token,
                    'confirm' => '1',
                ]
            );

        $response
            ->assertRedirect(
                route('settings.data-export')
            );

        $this->assertDatabaseHas('loans', [
            'user_id' => $user->id,
            'name' => 'Test Kredit ohne Konto',
            'account_id' => null,
            'principal_amount' => '10000.00',
        ]);

        $this->assertSame(
            1,
            DB::table('loans')
                ->where('user_id', $user->id)
                ->count()
        );
    }

    public function test_restore_does_not_duplicate_existing_data(): void
    {
        $user = $this->createUser();

        $payload = $this->validBackup();

        $token = $this->storeBackup($payload);

        $this
            ->withSession([
                'finanzview_import_token' => $token,
            ])
            ->actingAs($user)
            ->post(
                route('settings.data-export.import.restore'),
                [
                    'token' => $token,
                    'confirm' => '1',
                ]
            )
            ->assertRedirect();

        $accountsAfterFirstRestore =
            DB::table('accounts')
                ->where('user_id', $user->id)
                ->count();

        $transactionsAfterFirstRestore =
            DB::table('transactions')
                ->where('user_id', $user->id)
                ->count();

        $token = $this->storeBackup($payload);

        $this
            ->withSession([
                'finanzview_import_token' => $token,
            ])
            ->actingAs($user)
            ->post(
                route('settings.data-export.import.restore'),
                [
                    'token' => $token,
                    'confirm' => '1',
                ]
            )
            ->assertRedirect();

        $this->assertSame(
            $accountsAfterFirstRestore,
            DB::table('accounts')
                ->where('user_id', $user->id)
                ->count()
        );

        $this->assertSame(
            $transactionsAfterFirstRestore,
            DB::table('transactions')
                ->where('user_id', $user->id)
                ->count()
        );
    }

    public function test_transaction_tags_are_restored(): void
    {
        $user = $this->createUser();

        $token = $this->storeBackup(
            $this->validBackup()
        );

        $this
            ->withSession([
                'finanzview_import_token' => $token,
            ])
            ->actingAs($user)
            ->post(
                route('settings.data-export.import.restore'),
                [
                    'token' => $token,
                    'confirm' => '1',
                ]
            )
            ->assertRedirect();

        $transactionId = DB::table('transactions')
            ->where('user_id', $user->id)
            ->value('id');

        $tagId = DB::table('tags')
            ->where('user_id', $user->id)
            ->value('id');

        $this->assertDatabaseHas('transaction_tag', [
            'transaction_id' => $transactionId,
            'tag_id' => $tagId,
        ]);
    }

    public function test_budget_categories_are_restored(): void
    {
        $user = $this->createUser();

        $token = $this->storeBackup(
            $this->validBackup()
        );

        $this
            ->withSession([
                'finanzview_import_token' => $token,
            ])
            ->actingAs($user)
            ->post(
                route('settings.data-export.import.restore'),
                [
                    'token' => $token,
                    'confirm' => '1',
                ]
            )
            ->assertRedirect();

        $budgetId = DB::table('budgets')
            ->where('user_id', $user->id)
            ->value('id');

        $categoryId = DB::table('categories')
            ->where('user_id', $user->id)
            ->value('id');

        $this->assertDatabaseHas('budget_category', [
            'budget_id' => $budgetId,
            'category_id' => $categoryId,
        ]);
    }

    public function test_restore_rejects_loan_referencing_unknown_account(): void
    {
        $user = $this->createUser();

        $payload = $this->validBackup();

        $payload['loans'] = [
            [
                'id' => 600,
                'account_id' => 999999,
                'name' => 'Test Kredit mit unbekanntem Konto',
                'creditor_name' => 'Test Kreditgeber',
                'provider_id' => null,
                'creditor_icon' => null,
                'creditor_color' => null,
                'principal_amount' => '10000.00',
                'paid_amount' => '1000.00',
                'interest_rate' => '4.500',
                'installment_amount' => '250.00',
                'total_installments' => 40,
                'paid_installments' => 4,
                'start_date' => '2026-01-01',
                'end_date' => '2029-04-01',
                'type' => 'loan',
                'is_active' => true,
                'notes' => null,
                'payments' => [],
            ],
        ];

        $token = $this->storeBackup($payload);

        $response = $this
            ->withSession([
                'finanzview_import_token' => $token,
            ])
            ->actingAs($user)
            ->post(
                route('settings.data-export.import.restore'),
                [
                    'token' => $token,
                    'confirm' => '1',
                ]
            );

        $response->assertSessionHasErrors('backup');

        /*
         * Der Restore muss wegen der ungültigen Account-ID
         * vollständig zurückgerollt werden.
         */
        $this->assertDatabaseCount('accounts', 0);
        $this->assertDatabaseCount('loans', 0);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_restore_rolls_back_completely_when_later_data_is_invalid(): void
    {
        $user = $this->createUser();

        $payload = $this->validBackup();

        /*
         * Das Konto wäre gültig und könnte angelegt werden.
         * Die Transaktion wird absichtlich ungültig gemacht.
         * Der gesamte Restore muss deshalb zurückgerollt werden.
         */
        $payload['transactions'][0]['account_id'] = 999999;

        $token = $this->storeBackup($payload);

        $response = $this
            ->withSession([
                'finanzview_import_token' => $token,
            ])
            ->actingAs($user)
            ->post(
                route('settings.data-export.import.restore'),
                [
                    'token' => $token,
                    'confirm' => '1',
                ]
            );

        $response->assertSessionHasErrors('backup');

        /*
         * Wichtig:
         * Das vorher gültige Konto darf nach dem Fehler NICHT
         * in der Datenbank verbleiben.
         */
        $this->assertDatabaseMissing('accounts', [
            'user_id' => $user->id,
            'name' => 'Testkonto',
        ]);

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_restore_rejects_transaction_referencing_unknown_account(): void
    {
        $user = $this->createUser();

        $payload = $this->validBackup();

        /*
         * Die Transaktion verweist auf ein Konto, das im Backup
         * nicht existiert. Der Restore muss vollständig abbrechen.
         */
        $payload['transactions'][0]['account_id'] = 999999;

        $token = $this->storeBackup($payload);

        $response = $this
            ->withSession([
                'finanzview_import_token' => $token,
            ])
            ->actingAs($user)
            ->post(
                route('settings.data-export.import.restore'),
                [
                    'token' => $token,
                    'confirm' => '1',
                ]
            );

        $response->assertSessionHasErrors('backup');

        $this->assertDatabaseMissing('accounts', [
            'user_id' => $user->id,
            'name' => 'Testkonto',
        ]);

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_backup_cannot_create_records_for_another_user(): void
    {
        $owner = $this->createUser();
        $otherUser = $this->createUser();

        $payload = $this->validBackup();

        /*
         * user_id wird absichtlich in das Backup eingeschleust.
         * Der Restore darf diesen Wert niemals übernehmen.
         */
        $payload['user_id'] = $owner->id;

        $token = $this->storeBackup($payload);

        $this
            ->withSession([
                'finanzview_import_token' => $token,
            ])
            ->actingAs($otherUser)
            ->post(
                route('settings.data-export.import.restore'),
                [
                    'token' => $token,
                    'confirm' => '1',
                ]
            )
            ->assertRedirect();

        $this->assertDatabaseHas('accounts', [
            'user_id' => $otherUser->id,
            'name' => 'Testkonto',
        ]);

        $this->assertDatabaseMissing('accounts', [
            'user_id' => $owner->id,
            'name' => 'Testkonto',
        ]);
    }
}
