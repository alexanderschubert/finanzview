<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\CreditCard;
use App\Models\Loan;
use App\Models\Tag;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataExportController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return view('settings.data-export', [
            'accounts' => $user->accounts()
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function transactionsCsv(Request $request): StreamedResponse
    {
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'account_id' => ['nullable', 'integer'],
        ]);

        $user = $request->user();

        $accountId = $validated['account_id'] ?? null;

        if ($accountId !== null) {
            abort_unless(
                $user->accounts()->whereKey($accountId)->exists(),
                403
            );
        }

        $query = Transaction::query()
            ->where('user_id', $user->id)
            ->with([
                'account',
                'category',
                'recurringTransaction',
            ])
            ->orderBy('transaction_date')
            ->orderBy('id');

        if (!empty($validated['date_from'])) {
            $query->whereDate(
                'transaction_date',
                '>=',
                $validated['date_from']
            );
        }

        if (!empty($validated['date_to'])) {
            $query->whereDate(
                'transaction_date',
                '<=',
                $validated['date_to']
            );
        }

        if ($accountId !== null) {
            $query->where('account_id', $accountId);
        }

        $filename = 'finanzview-transaktionen-' .
            now()->format('Y-m-d_H-i-s') .
            '.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM für Excel
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'ID',
                'Datum',
                'Typ',
                'Betrag',
                'Konto',
                'Kategorie',
                'Händler',
                'Beschreibung',
                'Referenz',
                'Notizen',
                'Status',
                'Wiederkehrend',
                'Wiederkehrende Buchung',
            ], ';');

            $query->chunk(500, function ($transactions) use ($handle) {
                foreach ($transactions as $transaction) {
                    fputcsv($handle, [
                        $transaction->id,
                        $transaction->transaction_date instanceof \DateTimeInterface
                            ? $transaction->transaction_date->format('Y-m-d')
                            : (string) $transaction->transaction_date,
                        $transaction->type,
                        $transaction->amount,
                        $transaction->account?->name,
                        $transaction->category?->name,
                        $transaction->merchant,
                        $transaction->description,
                        $transaction->reference,
                        $transaction->notes,
                        $transaction->is_pending ? 'Ausstehend' : 'Gebucht',
                        $transaction->is_recurring ? 'Ja' : 'Nein',
                        $transaction->recurringTransaction?->description,
                    ], ';');
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function json(Request $request): Response
    {
        $user = $request->user();

        /*
         * Gelöschte (soft-deleted) Konten werden mit exportiert,
         * da ihre Transaktionen erhalten bleiben und sonst beim
         * Restore auf ein unbekanntes Konto verweisen würden.
         */
        $accounts = $user->accounts()
            ->withTrashed()
            ->get()
            ->map(fn ($item) => $item->only([
                'id',
                'name',
                'institution',
                'provider_id',
                'type',
                'currency',
                'opening_balance',
                'credit_limit',
                'iban',
                'account_number',
                'color',
                'icon',
                'notes',
                'include_in_total',
                'is_active',
                'deleted_at',
            ]));

        $categories = $user->categories()
            ->get()
            ->map(fn ($item) => $item->only([
                'id',
                'name',
                'type',
                'icon',
                'description',
                'is_active',
            ]));

        $tags = Tag::query()
            ->where('user_id', $user->id)
            ->get()
            ->map(fn ($item) => $item->only([
                'id',
                'name',
                'color',
            ]));

        $transactions = $user->transactions()
            ->with('tags')
            ->get()
            ->map(function ($item) {
                return array_merge(
                    $item->only([
                        'id',
                        'account_id',
                        'transfer_account_id',
                        'credit_card_id',
                        'category_id',
                        'type',
                        'amount',
                        'transaction_date',
                        'description',
                        'merchant',
                        'reference',
                        'notes',
                        'is_pending',
                        'is_recurring',
                        'recurring_transaction_id',
                    ]),
                    [
                        'tags' => $item->tags
                            ->map(fn ($tag) => $tag->only([
                                'id',
                                'name',
                                'color',
                            ]))
                            ->values()
                            ->all(),
                    ]
                );
            });

        $budgets = $user->budgets()
            ->with('categories')
            ->get()
            ->map(function ($item) {
                return array_merge(
                    $item->only([
                        'id',
                        'name',
                        'amount',
                        'start_date',
                        'end_date',
                        'period',
                        'is_active',
                        'color',
                        'icon',
                    ]),
                    [
                        'categories' => $item->categories
                            ->map(fn ($category) => $category->only([
                                'id',
                                'name',
                                'type',
                            ]))
                            ->values()
                            ->all(),
                    ]
                );
            });

        $recurringTransactions = $user->recurringTransactions()
            ->get()
            ->map(fn ($item) => $item->only([
                'id',
                'account_id',
                'category_id',
                'description',
                'amount',
                'type',
                'frequency',
                'next_date',
                'end_date',
                'is_active',
            ]));

        $creditCards = $user->creditCards()
            ->with('statements')
            ->get()
            ->map(function ($item) {
                return array_merge(
                    $item->only([
                        'id',
                        'account_id',
                        'name',
                        'issuer',
                        'provider_id',
                        'last_four',
                        'credit_limit',
                        'current_balance',
                        'billing_day',
                        'payment_due_day',
                        'color',
                        'is_active',
                    ]),
                    [
                        'statements' => $item->statements
                            ->map(fn ($statement) => $statement->only([
                                'id',
                                'period_start',
                                'period_end',
                                'due_date',
                                'amount',
                                'status',
                            ]))
                            ->values()
                            ->all(),
                    ]
                );
            });

        $loans = $user->loans()
            ->with('payments')
            ->get()
            ->map(function ($item) {
                return array_merge(
                    $item->only([
                        'id',
                        'account_id',
                        'name',
                        'creditor_name',
                        'provider_id',
                        'creditor_icon',
                        'creditor_color',
                        'principal_amount',
                        'paid_amount',
                        'interest_rate',
                        'installment_amount',
                        'total_installments',
                        'paid_installments',
                        'start_date',
                        'end_date',
                        'type',
                        'is_active',
                        'notes',
                    ]),
                    [
                        'payments' => $item->payments
                            ->map(fn ($payment) => $payment->only([
                                'id',
                                'transaction_id',
                                'installment_number',
                                'due_date',
                                'amount',
                                'payment_type',
                                'paid_date',
                                'status',
                            ]))
                            ->values()
                            ->all(),
                    ]
                );
            });

        $dashboardSetting = $user->dashboardSetting?->only([
            'id',
            'widgets',
            'widget_order',
            'display_mode',
        ]);

        $payload = [
            'metadata' => [
                'application' => 'FinanzView',
                'format' => 'finanzview-export',
                'version' => 1,
                'exported_at' => now()->toIso8601String(),
            ],

            'accounts' => $accounts->values()->all(),
            'categories' => $categories->values()->all(),
            'tags' => $tags->values()->all(),
            'transactions' => $transactions->values()->all(),
            'budgets' => $budgets->values()->all(),
            'recurring_transactions' => $recurringTransactions->values()->all(),
            'credit_cards' => $creditCards->values()->all(),
            'loans' => $loans->values()->all(),
            'dashboard_settings' => $dashboardSetting,
        ];

        $filename = 'finanzview-backup-' .
            now()->format('Y-m-d_H-i-s') .
            '.json';

        return response(
            json_encode(
                $payload,
                JSON_PRETTY_PRINT |
                JSON_UNESCAPED_UNICODE |
                JSON_UNESCAPED_SLASHES
            ),
            200,
            [
                'Content-Type' => 'application/json; charset=UTF-8',
                'Content-Disposition' =>
                    'attachment; filename="' . $filename . '"',
            ]
        );
    }


    /**
     * Upload eines JSON-Backups und Anzeige der Vorschau.
     */
    public function importPreview(Request $request)
    {
        $request->validate([
            'backup' => [
                'required',
                'file',
                'max:20480',
            ],
        ]);

        $file = $request->file('backup');

        try {
            $content = $file->get();

            if ($content === false || trim($content) === '') {
                throw new \RuntimeException('Die Backup-Datei ist leer.');
            }

            $payload = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'backup' => 'Die Datei enthält kein gültiges JSON-Backup.',
            ]);
        }

        $this->validateBackupPayload($payload);

        $token = (string) Str::uuid();

        Storage::disk('local')->put(
            'imports/' . $token . '.json',
            $content
        );

        $request->session()->put(
            'finanzview_import_token',
            $token
        );

        $counts = $this->backupCounts($payload);

        return view('settings.data-export-import-preview', [
            'metadata' => $payload['metadata'],
            'counts' => $counts,
            'token' => $token,
        ]);
    }


    /**
     * Führt den bestätigten Restore durch.
     */
    public function importRestore(Request $request)
    {
        $request->validate([
            'token' => [
                'required',
                'uuid',
            ],
            'confirm' => [
                'accepted',
            ],
        ]);

        $sessionToken = $request->session()->get('finanzview_import_token');

        abort_unless(
            $sessionToken &&
            hash_equals($sessionToken, $request->input('token')),
            403
        );

        $path = 'imports/' . $sessionToken . '.json';

        abort_unless(
            Storage::disk('local')->exists($path),
            404
        );

        try {
            $content = Storage::disk('local')->get($path);

            $payload = json_decode(
                $content,
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            $this->validateBackupPayload($payload);

            $result = DB::transaction(function () use ($payload, $request) {
                return $this->restoreBackup(
                    $payload,
                    $request->user()
                );
            }, 3);

        } catch (\Throwable $e) {
            report($e);

            throw ValidationException::withMessages([
                'backup' =>
                    'Die Wiederherstellung wurde vollständig abgebrochen. ' .
                    'Es wurden keine Änderungen übernommen.',
            ]);
        }

        Storage::disk('local')->delete($path);

        $request->session()->forget('finanzview_import_token');

        return redirect()
            ->route('settings.data-export')
            ->with(
                'success',
                sprintf(
                    'Backup erfolgreich wiederhergestellt: %d Konten, %d Transaktionen, %d Kategorien, %d Budgets, %d wiederkehrende Buchungen, %d Kreditkarten und %d Kredite.',
                    $result['accounts'],
                    $result['transactions'],
                    $result['categories'],
                    $result['budgets'],
                    $result['recurring_transactions'],
                    $result['credit_cards'],
                    $result['loans']
                )
            );
    }


    /**
     * Validiert die grundlegende Backup-Struktur.
     */
    private function validateBackupPayload(array $payload): void
    {
        if (!isset($payload['metadata']) || !is_array($payload['metadata'])) {
            throw ValidationException::withMessages([
                'backup' => 'Das Backup enthält keine gültigen Metadaten.',
            ]);
        }

        if (($payload['metadata']['format'] ?? null) !== 'finanzview-export') {
            throw ValidationException::withMessages([
                'backup' => 'Die Datei ist kein gültiges FinanzView-Backup.',
            ]);
        }

        if (($payload['metadata']['version'] ?? null) !== 1) {
            throw ValidationException::withMessages([
                'backup' =>
                    'Die Backup-Version wird von dieser FinanzView-Version nicht unterstützt.',
            ]);
        }

        $arrays = [
            'accounts',
            'categories',
            'tags',
            'transactions',
            'budgets',
            'recurring_transactions',
            'credit_cards',
            'loans',
        ];

        foreach ($arrays as $key) {
            if (!array_key_exists($key, $payload)) {
                throw ValidationException::withMessages([
                    'backup' => "Der Bereich '{$key}' fehlt im Backup.",
                ]);
            }

            if (!is_array($payload[$key])) {
                throw ValidationException::withMessages([
                    'backup' => "Der Bereich '{$key}' ist ungültig.",
                ]);
            }
        }

        if (
            array_key_exists('dashboard_settings', $payload) &&
            $payload['dashboard_settings'] !== null &&
            !is_array($payload['dashboard_settings'])
        ) {
            throw ValidationException::withMessages([
                'backup' => 'Die Dashboard-Einstellungen sind ungültig.',
            ]);
        }
    }


    /**
     * Liefert die Anzahl der Datensätze für die Vorschau.
     */
    private function backupCounts(array $payload): array
    {
        $countNested = function (string $key, string $nested): int {
            return collect($payload[$key] ?? [])
                ->sum(
                    fn ($item) =>
                        is_array($item)
                        ? count($item[$nested] ?? [])
                        : 0
                );
        };

        return [
            'accounts' => count($payload['accounts'] ?? []),
            'categories' => count($payload['categories'] ?? []),
            'tags' => count($payload['tags'] ?? []),
            'transactions' => count($payload['transactions'] ?? []),
            'budgets' => count($payload['budgets'] ?? []),
            'budget_categories' => $countNested('budgets', 'categories'),
            'recurring_transactions' => count(
                $payload['recurring_transactions'] ?? []
            ),
            'credit_cards' => count($payload['credit_cards'] ?? []),
            'credit_card_statements' => $countNested(
                'credit_cards',
                'statements'
            ),
            'loans' => count($payload['loans'] ?? []),
            'loan_payments' => $countNested('loans', 'payments'),
            'dashboard_settings' =>
                !empty($payload['dashboard_settings']) ? 1 : 0,
        ];
    }


    /**
     * Führt den eigentlichen Restore innerhalb einer DB-Transaktion aus.
     *
     * Wichtig:
     * - Es werden niemals user_id-Werte aus dem Backup übernommen.
     * - Primärschlüssel aus dem Backup werden niemals wiederverwendet.
     * - Alle Foreign Keys werden über Mapping-Tabellen auf lokale IDs umgeschrieben.
     */
    private function restoreBackup(array $payload, $user): array
    {
        $result = [
            'accounts' => 0,
            'categories' => 0,
            'tags' => 0,
            'transactions' => 0,
            'budgets' => 0,
            'recurring_transactions' => 0,
            'credit_cards' => 0,
            'credit_card_statements' => 0,
            'loans' => 0,
            'loan_payments' => 0,
        ];

        $accountMap = [];
        $categoryMap = [];
        $tagMap = [];
        $recurringMap = [];
        $transactionMap = [];
        $budgetMap = [];
        $creditCardMap = [];
        $loanMap = [];

        /*
         * Bereits in diesem Restore verwendete Transaktions-IDs
         * (als Schlüssel) sowie neu angelegte Transaktionen
         * (Backup-ID => neue ID).
         */
        $matchedTransactionIds = [];
        $newTransactionIds = [];

        /*
         * =========================================================
         * HELPERS
         * =========================================================
         */

        $providerExists = function ($providerId): ?int {
            if (!$providerId) {
                return null;
            }

            return DB::table('financial_providers')
                ->where('id', $providerId)
                ->exists()
                ? (int) $providerId
                : null;
        };

        $isFilled = function ($value): bool {
            return $value !== null && $value !== '';
        };

        /*
         * =========================================================
         * ACCOUNTS
         * =========================================================
         */

        foreach ($payload['accounts'] as $item) {
            if (!is_array($item) || empty($item['id'])) {
                continue;
            }

            $existing = null;

            /*
             * IBAN ist die zuverlässigste Zuordnung.
             * Nur suchen, wenn tatsächlich eine IBAN vorhanden ist.
             */
            if ($isFilled($item['iban'] ?? null)) {
                $existing = DB::table('accounts')
                    ->where('user_id', $user->id)
                    ->where('iban', $item['iban'])
                    ->first();
            }

            /*
             * Falls keine IBAN vorhanden ist, verwenden wir
             * Name + Institut + Kontonummer.
             */
            if (!$existing) {
                $query = DB::table('accounts')
                    ->where('user_id', $user->id)
                    ->where('name', $item['name'] ?? '');

                if ($isFilled($item['institution'] ?? null)) {
                    $query->where(
                        'institution',
                        $item['institution']
                    );
                }

                if ($isFilled($item['account_number'] ?? null)) {
                    $query->where(
                        'account_number',
                        $item['account_number']
                    );
                }

                $existing = $query->first();
            }

            if ($existing) {
                $accountMap[$item['id']] = $existing->id;
                continue;
            }

            $newId = DB::table('accounts')->insertGetId([
                'user_id' => $user->id,
                'name' => $item['name'] ?? 'Konto',
                'institution' => $item['institution'] ?? null,
                'provider_id' => $providerExists(
                    $item['provider_id'] ?? null
                ),
                'type' => $item['type'] ?? null,
                'currency' => $item['currency'] ?? 'EUR',
                'opening_balance' => $item['opening_balance'] ?? 0,
                'credit_limit' => $item['credit_limit'] ?? null,
                'iban' => $item['iban'] ?? null,
                'account_number' => $item['account_number'] ?? null,
                'color' => $item['color'] ?? null,
                'icon' => $item['icon'] ?? null,
                'notes' => $item['notes'] ?? null,
                'include_in_total' =>
                    $item['include_in_total'] ?? true,
                'is_active' => $item['is_active'] ?? true,
                /*
                 * Gelöschte Konten bleiben gelöscht.
                 * Ältere Backups enthalten kein deleted_at.
                 */
                'deleted_at' => $isFilled($item['deleted_at'] ?? null)
                    ? \Illuminate\Support\Carbon::parse($item['deleted_at'])
                    : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $accountMap[$item['id']] = $newId;
            $result['accounts']++;
        }

        /*
         * =========================================================
         * CATEGORIES
         * =========================================================
         */

        foreach ($payload['categories'] as $item) {
            if (!is_array($item) || empty($item['id'])) {
                continue;
            }

            $existing = DB::table('categories')
                ->where('user_id', $user->id)
                ->where('name', $item['name'] ?? '')
                ->where('type', $item['type'] ?? '')
                ->first();

            if ($existing) {
                $categoryMap[$item['id']] = $existing->id;
                continue;
            }

            $newId = DB::table('categories')->insertGetId([
                'user_id' => $user->id,
                'name' => $item['name'] ?? 'Kategorie',
                'type' => $item['type'] ?? null,
                'icon' => $item['icon'] ?? null,
                'description' => $item['description'] ?? null,
                'is_active' => $item['is_active'] ?? true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $categoryMap[$item['id']] = $newId;
            $result['categories']++;
        }

        /*
         * =========================================================
         * TAGS
         * =========================================================
         */

        foreach ($payload['tags'] as $item) {
            if (!is_array($item) || empty($item['id'])) {
                continue;
            }

            $existing = DB::table('tags')
                ->where('user_id', $user->id)
                ->where('name', $item['name'] ?? '')
                ->first();

            if ($existing) {
                $tagMap[$item['id']] = $existing->id;
                continue;
            }

            $newId = DB::table('tags')->insertGetId([
                'user_id' => $user->id,
                'name' => $item['name'] ?? 'Tag',
                'color' => $item['color'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $tagMap[$item['id']] = $newId;
            $result['tags']++;
        }

        /*
         * =========================================================
         * RECURRING TRANSACTIONS
         * =========================================================
         */

        foreach ($payload['recurring_transactions'] as $item) {
            if (!is_array($item) || empty($item['id'])) {
                continue;
            }

            $accountId =
                $accountMap[$item['account_id'] ?? 0] ?? null;

            $categoryId =
                $categoryMap[$item['category_id'] ?? 0] ?? null;

            if (!$accountId) {
                throw new \RuntimeException(
                    'Eine wiederkehrende Buchung verweist auf ein unbekanntes Konto.'
                );
            }

            $existing = DB::table('recurring_transactions')
                ->where('user_id', $user->id)
                ->where('account_id', $accountId)
                ->where('description', $item['description'] ?? '')
                ->where('amount', $item['amount'] ?? 0)
                ->where('frequency', $item['frequency'] ?? '')
                ->where('next_date', $item['next_date'] ?? null)
                ->first();

            if ($existing) {
                $recurringMap[$item['id']] = $existing->id;
                continue;
            }

            $newId = DB::table('recurring_transactions')->insertGetId([
                'user_id' => $user->id,
                'account_id' => $accountId,
                'category_id' => $categoryId,
                'description' => $item['description'] ?? '',
                'amount' => $item['amount'] ?? 0,
                'type' => $item['type'] ?? null,
                'frequency' => $item['frequency'] ?? null,
                'next_date' => $item['next_date'] ?? null,
                'end_date' => $item['end_date'] ?? null,
                'is_active' => $item['is_active'] ?? true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $recurringMap[$item['id']] = $newId;
            $result['recurring_transactions']++;
        }

        /*
         * =========================================================
         * TRANSACTIONS
         * =========================================================
         */

        foreach ($payload['transactions'] as $item) {
            if (!is_array($item) || empty($item['id'])) {
                continue;
            }

            $accountId =
                $accountMap[$item['account_id'] ?? 0] ?? null;

            $categoryId =
                $categoryMap[$item['category_id'] ?? 0] ?? null;

            $recurringId =
                $recurringMap[
                    $item['recurring_transaction_id'] ?? 0
                ] ?? null;

            if (!$accountId) {
                throw new \RuntimeException(
                    'Eine Transaktion verweist auf ein unbekanntes Konto.'
                );
            }

            /*
             * Zielkonto bei Transfers.
             * Ältere Backups enthalten transfer_account_id nicht.
             */
            $transferAccountId = null;

            if ($isFilled($item['transfer_account_id'] ?? null)) {
                $transferAccountId =
                    $accountMap[$item['transfer_account_id']] ?? null;

                if (!$transferAccountId) {
                    throw new \RuntimeException(
                        'Eine Transaktion verweist auf ein unbekanntes Zielkonto.'
                    );
                }
            }

            $query = DB::table('transactions')
                ->where('user_id', $user->id)
                ->where('account_id', $accountId)
                ->where(
                    'transaction_date',
                    $item['transaction_date'] ?? null
                )
                ->where('type', $item['type'] ?? '')
                ->where('amount', $item['amount'] ?? 0)
                ->where(
                    'description',
                    $item['description'] ?? ''
                );

            if ($isFilled($item['merchant'] ?? null)) {
                $query->where('merchant', $item['merchant']);
            } else {
                $query->whereNull('merchant');
            }

            if ($isFilled($item['reference'] ?? null)) {
                $query->where('reference', $item['reference']);
            } else {
                $query->whereNull('reference');
            }

            if ($transferAccountId) {
                $query->where(
                    'transfer_account_id',
                    $transferAccountId
                );
            } else {
                $query->whereNull('transfer_account_id');
            }

            /*
             * Bereits in diesem Restore zugeordnete Transaktionen
             * ausschließen, damit identische Buchungen (z. B. zwei
             * gleiche Kaffees am selben Tag) nicht zu einer
             * einzigen zusammenfallen.
             */
            if (!empty($matchedTransactionIds)) {
                $query->whereNotIn(
                    'id',
                    array_keys($matchedTransactionIds)
                );
            }

            $existing = $query->orderBy('id')->first();

            if ($existing) {
                $transactionMap[$item['id']] = $existing->id;
                $matchedTransactionIds[$existing->id] = true;
                continue;
            }

            $newId = DB::table('transactions')->insertGetId([
                'user_id' => $user->id,
                'account_id' => $accountId,
                'category_id' => $categoryId,
                'type' => $item['type'] ?? null,
                'amount' => $item['amount'] ?? 0,
                'transaction_date' =>
                    $item['transaction_date'] ?? null,
                'description' => $item['description'] ?? null,
                'merchant' => $item['merchant'] ?? null,
                'reference' => $item['reference'] ?? null,
                'notes' => $item['notes'] ?? null,
                'is_pending' => $item['is_pending'] ?? false,
                'is_recurring' => $item['is_recurring'] ?? false,
                'recurring_transaction_id' => $recurringId,
                'transfer_account_id' => $transferAccountId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $transactionMap[$item['id']] = $newId;
            $matchedTransactionIds[$newId] = true;
            $newTransactionIds[$item['id']] = $newId;
            $result['transactions']++;
        }

        /*
         * =========================================================
         * TRANSACTION TAGS
         * =========================================================
         */

        foreach ($payload['transactions'] as $item) {
            if (!is_array($item) || empty($item['id'])) {
                continue;
            }

            $transactionId =
                $transactionMap[$item['id']] ?? null;

            if (!$transactionId) {
                continue;
            }

            foreach (($item['tags'] ?? []) as $tag) {
                if (!is_array($tag)) {
                    continue;
                }

                $tagId =
                    $tagMap[$tag['id'] ?? 0] ?? null;

                if (!$tagId) {
                    continue;
                }

                $inserted = DB::table('transaction_tag')
                    ->insertOrIgnore([
                        'transaction_id' => $transactionId,
                        'tag_id' => $tagId,
                    ]);

                if ($inserted) {
                    /*
                     * Keine eigene result-Kategorie notwendig.
                     * Die Tags selbst werden bereits gezählt.
                     */
                }
            }
        }

        /*
         * =========================================================
         * BUDGETS
         * =========================================================
         */

        foreach ($payload['budgets'] as $item) {
            if (!is_array($item) || empty($item['id'])) {
                continue;
            }

            $existing = DB::table('budgets')
                ->where('user_id', $user->id)
                ->where('name', $item['name'] ?? '')
                ->where(
                    'start_date',
                    $item['start_date'] ?? null
                )
                ->where(
                    'end_date',
                    $item['end_date'] ?? null
                )
                ->where('period', $item['period'] ?? '')
                ->first();

            if ($existing) {
                $budgetId = $existing->id;
            } else {
                $budgetId = DB::table('budgets')->insertGetId([
                    'user_id' => $user->id,
                    'name' => $item['name'] ?? 'Budget',
                    'amount' => $item['amount'] ?? 0,
                    'start_date' =>
                        $item['start_date'] ?? null,
                    'end_date' =>
                        $item['end_date'] ?? null,
                    'period' => $item['period'] ?? null,
                    'is_active' =>
                        $item['is_active'] ?? true,
                    'color' => $item['color'] ?? null,
                    'icon' => $item['icon'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $result['budgets']++;
            }

            $budgetMap[$item['id']] = $budgetId;

            /*
             * Budget-Kategorien immer verknüpfen,
             * auch wenn das Budget bereits existiert.
             */
            foreach (($item['categories'] ?? []) as $category) {
                if (!is_array($category)) {
                    continue;
                }

                $categoryId =
                    $categoryMap[$category['id'] ?? 0] ?? null;

                if (!$categoryId) {
                    continue;
                }

                DB::table('budget_category')
                    ->insertOrIgnore([
                        'budget_id' => $budgetId,
                        'category_id' => $categoryId,
                    ]);
            }
        }

        /*
         * =========================================================
         * CREDIT CARDS
         * =========================================================
         */

        foreach ($payload['credit_cards'] as $item) {
            if (!is_array($item) || empty($item['id'])) {
                continue;
            }

            $accountId =
                $accountMap[$item['account_id'] ?? 0] ?? null;

            if (!$accountId) {
                throw new \RuntimeException(
                    'Eine Kreditkarte verweist auf ein unbekanntes Konto.'
                );
            }

            $query = DB::table('credit_cards')
                ->where('user_id', $user->id)
                ->where('account_id', $accountId)
                ->where('name', $item['name'] ?? '');

            if ($isFilled($item['last_four'] ?? null)) {
                $query->where('last_four', $item['last_four']);
            } else {
                $query->whereNull('last_four');
            }

            $existing = $query->first();

            if ($existing) {
                $creditCardId = $existing->id;
            } else {
                $creditCardId = DB::table('credit_cards')
                    ->insertGetId([
                        'user_id' => $user->id,
                        'account_id' => $accountId,
                        'name' =>
                            $item['name'] ?? 'Kreditkarte',
                        'issuer' => $item['issuer'] ?? null,
                        'provider_id' => $providerExists(
                            $item['provider_id'] ?? null
                        ),
                        'last_four' =>
                            $item['last_four'] ?? null,
                        'credit_limit' =>
                            $item['credit_limit'] ?? null,
                        'current_balance' =>
                            $item['current_balance'] ?? 0,
                        'billing_day' =>
                            $item['billing_day'] ?? null,
                        'payment_due_day' =>
                            $item['payment_due_day'] ?? null,
                        'color' => $item['color'] ?? null,
                        'is_active' =>
                            $item['is_active'] ?? true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                $result['credit_cards']++;
            }

            $creditCardMap[$item['id']] = $creditCardId;

            /*
             * Statements immer verarbeiten.
             */
            foreach (($item['statements'] ?? []) as $statement) {
                if (!is_array($statement)) {
                    continue;
                }

                if (
                    !isset($statement['period_start']) ||
                    !isset($statement['period_end'])
                ) {
                    throw new \RuntimeException(
                        'Ein Kreditkarten-Statement enthält keinen gültigen Zeitraum.'
                    );
                }

                $inserted = DB::table('credit_card_statements')
                    ->insertOrIgnore([
                        'credit_card_id' => $creditCardId,
                        'period_start' =>
                            $statement['period_start'],
                        'period_end' =>
                            $statement['period_end'],
                        'due_date' =>
                            $statement['due_date'] ?? null,
                        'amount' =>
                            $statement['amount'] ?? 0,
                        'status' =>
                            $statement['status'] ?? 'open',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                if ($inserted) {
                    $result['credit_card_statements']++;
                }
            }
        }

        /*
         * =========================================================
         * TRANSACTION CREDIT CARDS
         * =========================================================
         *
         * Kreditkarten werden erst nach den Transaktionen
         * wiederhergestellt. Daher wird credit_card_id für neu
         * angelegte Transaktionen hier nachgetragen.
         */

        foreach ($payload['transactions'] as $item) {
            if (!is_array($item) || empty($item['id'])) {
                continue;
            }

            $transactionId =
                $newTransactionIds[$item['id']] ?? null;

            if (!$transactionId) {
                continue;
            }

            if (!$isFilled($item['credit_card_id'] ?? null)) {
                continue;
            }

            $creditCardId =
                $creditCardMap[$item['credit_card_id']] ?? null;

            if (!$creditCardId) {
                continue;
            }

            DB::table('transactions')
                ->where('id', $transactionId)
                ->update([
                    'credit_card_id' => $creditCardId,
                ]);
        }

        /*
         * =========================================================
         * LOANS
         * =========================================================
         */

        foreach ($payload['loans'] as $item) {
            if (!is_array($item) || empty($item['id'])) {
                continue;
            }

            /*
             * account_id darf bei Krediten NULL sein.
             * Wenn eine konkrete Account-ID vorhanden ist,
             * muss sie aber zwingend im Backup vorhanden sein
             * und auf ein lokales Konto gemappt werden können.
             */
            $accountId = null;

            if ($isFilled($item['account_id'] ?? null)) {
                $backupAccountId = $item['account_id'];

                $accountId =
                    $accountMap[$backupAccountId] ?? null;

                if (!$accountId) {
                    throw new \RuntimeException(
                        'Ein Kredit verweist auf ein unbekanntes Konto.'
                    );
                }
            }

            $existing = DB::table('loans')
                ->where('user_id', $user->id)
                ->where('account_id', $accountId)
                ->where('name', $item['name'] ?? '')
                ->where(
                    'start_date',
                    $item['start_date'] ?? null
                )
                ->where(
                    'principal_amount',
                    $item['principal_amount'] ?? 0
                )
                ->first();

            if ($existing) {
                $loanId = $existing->id;
            } else {
                $loanId = DB::table('loans')->insertGetId([
                    'user_id' => $user->id,
                    'account_id' => $accountId,
                    'name' => $item['name'] ?? 'Kredit',
                    'creditor_name' =>
                        $item['creditor_name'] ?? null,
                    'provider_id' => $providerExists(
                        $item['provider_id'] ?? null
                    ),
                    'creditor_icon' =>
                        $item['creditor_icon'] ?? null,
                    'creditor_color' =>
                        $item['creditor_color'] ?? null,
                    'principal_amount' =>
                        $item['principal_amount'] ?? 0,
                    'paid_amount' =>
                        $item['paid_amount'] ?? 0,
                    'interest_rate' =>
                        $item['interest_rate'] ?? 0,
                    'installment_amount' =>
                        $item['installment_amount'] ?? 0,
                    'total_installments' =>
                        $item['total_installments'] ?? 0,
                    'paid_installments' =>
                        $item['paid_installments'] ?? 0,
                    'start_date' =>
                        $item['start_date'] ?? null,
                    'end_date' =>
                        $item['end_date'] ?? null,
                    'type' => $item['type'] ?? null,
                    'is_active' =>
                        $item['is_active'] ?? true,
                    'notes' =>
                        $item['notes'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $result['loans']++;
            }

            $loanMap[$item['id']] = $loanId;

            /*
             * Zahlungen immer verarbeiten,
             * auch bei bereits vorhandenem Kredit.
             */
            foreach (($item['payments'] ?? []) as $payment) {
                if (!is_array($payment)) {
                    continue;
                }

                if (
                    !isset($payment['installment_number']) ||
                    !isset($payment['due_date'])
                ) {
                    throw new \RuntimeException(
                        'Eine Kreditzahlung enthält keine gültige Rateninformation.'
                    );
                }

                $transactionId =
                    $transactionMap[
                        $payment['transaction_id'] ?? 0
                    ] ?? null;

                $inserted = DB::table('loan_payments')
                    ->insertOrIgnore([
                        'loan_id' => $loanId,
                        'transaction_id' => $transactionId,
                        'installment_number' =>
                            $payment['installment_number'],
                        'due_date' =>
                            $payment['due_date'],
                        'amount' =>
                            $payment['amount'] ?? 0,
                        'payment_type' =>
                            $payment['payment_type'] ?? 'regular',
                        'paid_date' =>
                            $payment['paid_date'] ?? null,
                        'status' =>
                            $payment['status'] ?? 'planned',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                if ($inserted) {
                    $result['loan_payments']++;
                }
            }
        }

        /*
         * =========================================================
         * DASHBOARD SETTINGS
         * =========================================================
         */

        if (
            !empty($payload['dashboard_settings']) &&
            is_array($payload['dashboard_settings'])
        ) {
            $settings = $payload['dashboard_settings'];

            $existing = DB::table('dashboard_settings')
                ->where('user_id', $user->id)
                ->exists();

            $values = [
                'widgets' => json_encode(
                    $settings['widgets'] ?? []
                ),
                'widget_order' => json_encode(
                    $settings['widget_order'] ?? []
                ),
                'display_mode' =>
                    $settings['display_mode'] ?? 'standard',
                'updated_at' => now(),
            ];

            if (!$existing) {
                $values['created_at'] = now();
            }

            DB::table('dashboard_settings')
                ->updateOrInsert(
                    ['user_id' => $user->id],
                    $values
                );
        }

        return $result;
    }

}
