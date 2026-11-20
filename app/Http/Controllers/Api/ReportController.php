<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MonthlyReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Monatsbericht für die native App (nur lesen).
 */
class ReportController extends Controller
{
    public function month(Request $request, MonthlyReportService $monthly)
    {
        try {
            $month = Carbon::createFromFormat('!Y-m', (string) $request->input('month', now()->format('Y-m')))->startOfMonth();
        } catch (\Throwable) {
            $month = now()->startOfMonth();
        }

        $report = $monthly->build($request->user(), $month);

        return response()->json([
            'month' => $month->format('Y-m'),
            'has_data' => $report['has_data'],
            'transaction_count' => $report['transaction_count'],
            'average_months' => $report['average_months'],
            'totals' => $report['totals'],
            'previous_totals' => $report['previous_totals'],
            'average_totals' => $report['average_totals'],
            'categories' => collect($report['categories'])->take(15)->map(fn ($c) => [
                'id' => $c['id'],
                'name' => $c['name'],
                'icon' => $c['icon'],
                'color' => $c['color'],
                'amount' => $c['amount'],
                'previous' => $c['previous'],
                'average' => $c['average'],
                'share' => $c['share'],
                'difference' => $c['difference'],
                'count' => $c['count'],
            ])->values(),
            'insights' => collect($report['insights'])->map(fn ($i) => ['tone' => $i['tone'] ?? 'neutral', 'text' => $i['text'] ?? ''])->values(),
        ]);
    }
}
