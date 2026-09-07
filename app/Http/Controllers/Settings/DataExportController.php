<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\CreditCard;
use App\Models\Loan;
use App\Models\Tag;
use App\Models\Transaction;
use Illuminate\Http\Request;
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

        $accounts = $user->accounts()
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
            ->with('recurringTransaction')
            ->get()
            ->map(function ($item) {
                return array_merge(
                    $item->only([
                        'id',
                        'account_id',
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

}
