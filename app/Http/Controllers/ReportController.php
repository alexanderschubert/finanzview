<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reports
    ) {
    }

    /**
     * Auswertungen anzeigen.
     */
    public function index(Request $request)
    {
        [$filters, $report] = $this->resolve($request);

        $accounts = $request->user()
            ->accounts()
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('reports.index', [
            'report' => $report,
            'filters' => $filters,
            'accounts' => $accounts,
            'periods' => ReportService::PERIODS,
        ]);
    }

    /**
     * Auswertung als CSV herunterladen (Semikolon, UTF-8 mit
     * BOM, deutsches Zahlenformat – direkt in Excel lesbar).
     */
    public function export(Request $request): StreamedResponse
    {
        [, $report] = $this->resolve($request);

        $filename = sprintf(
            'finanzview-auswertung-%s-bis-%s.csv',
            $report['start']->format('Y-m-d'),
            $report['end']->format('Y-m-d')
        );

        return response()->streamDownload(function () use ($report) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");

            $money = fn (float $value) => number_format($value, 2, ',', '');
            $percent = fn (?float $value) => $value === null
                ? ''
                : number_format($value, 1, ',', '');

            fputcsv($handle, [
                'Auswertung',
                $report['start']->format('d.m.Y') . ' – ' . $report['end']->format('d.m.Y'),
            ], ';');

            fputcsv($handle, [], ';');
            fputcsv($handle, ['Übersicht', 'Betrag (EUR)', 'Vorperiode (EUR)', 'Veränderung (%)'], ';');

            foreach ([
                'income' => 'Einnahmen',
                'expense' => 'Ausgaben',
                'balance' => 'Saldo',
            ] as $key => $label) {
                fputcsv($handle, [
                    $label,
                    $money($report['totals'][$key]),
                    $money($report['previous_totals'][$key]),
                    $percent($report['changes'][$key]),
                ], ';');
            }

            fputcsv($handle, [
                'Sparquote (%)',
                $percent($report['totals']['savings_rate']),
                $percent($report['previous_totals']['savings_rate']),
                '',
            ], ';');

            fputcsv($handle, [], ';');
            fputcsv($handle, ['Monat', 'Einnahmen (EUR)', 'Ausgaben (EUR)', 'Saldo (EUR)'], ';');

            foreach ($report['monthly'] as $month) {
                fputcsv($handle, [
                    $month['label'],
                    $money($month['income']),
                    $money($month['expense']),
                    $money($month['balance']),
                ], ';');
            }

            foreach ([
                'expense_categories' => 'Ausgaben nach Kategorie',
                'income_categories' => 'Einnahmen nach Kategorie',
            ] as $key => $title) {
                fputcsv($handle, [], ';');
                fputcsv($handle, [$title, 'Betrag (EUR)', 'Anteil (%)', 'Buchungen', 'Vorperiode (EUR)', 'Veränderung (%)'], ';');

                foreach ($report[$key] as $category) {
                    fputcsv($handle, [
                        $category['name'],
                        $money($category['amount']),
                        $percent($category['share']),
                        $category['count'],
                        $money($category['previous_amount']),
                        $percent($category['change']),
                    ], ';');
                }
            }

            fputcsv($handle, [], ';');
            fputcsv($handle, ['Top-Händler', 'Betrag (EUR)', 'Buchungen', 'Durchschnitt (EUR)'], ';');

            foreach ($report['top_merchants'] as $merchant) {
                fputcsv($handle, [
                    $merchant['name'],
                    $money($merchant['amount']),
                    $merchant['count'],
                    $money($merchant['average']),
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Filter validieren und Bericht berechnen.
     */
    private function resolve(Request $request): array
    {
        $user = $request->user();

        $validated = $request->validate([
            'period' => ['nullable', Rule::in(array_keys(ReportService::PERIODS))],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'account_id' => [
                'nullable',
                'integer',
                Rule::exists('accounts', 'id')->where('user_id', $user->id),
            ],
        ]);

        $period = $validated['period'] ?? 'last_6_months';

        [$start, $end] = $this->reports->resolvePeriod(
            $period,
            $validated['from'] ?? null,
            $validated['to'] ?? null
        );

        // Sehr lange Zeiträume begrenzen (max. 5 Jahre).
        if ($start->lessThan($end->subYears(5))) {
            $start = $end->subYears(5)->addDay()->startOfDay();
        }

        $accountId = isset($validated['account_id'])
            ? (int) $validated['account_id']
            : null;

        $filters = [
            'period' => $period,
            'from' => $start->format('Y-m-d'),
            'to' => $end->format('Y-m-d'),
            'account_id' => $accountId,
        ];

        return [
            $filters,
            $this->reports->build($user, $start, $end, $accountId),
        ];
    }
}
