<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiPresenter;
use App\Services\PlanService;
use App\Services\TransferMatcher;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Lesende Endpunkte: Benutzer, Cockpit, Konten, Kategorien.
 */
class ReadController extends Controller
{
    public function me(Request $request)
    {
        return response()->json(ApiPresenter::user($request->user()));
    }

    public function avatar(Request $request)
    {
        $path = $request->user()->profile_photo_path;
        $disk = Storage::disk('local');

        abort_unless($path && $disk->exists($path), 404);

        return $disk->response($path, null, [
            'Cache-Control' => 'private, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function dashboard(Request $request, PlanService $plans, TransferMatcher $transfers)
    {
        $user = $request->user();

        try {
            $month = Carbon::createFromFormat('!Y-m', (string) $request->input('month', now()->format('Y-m')))->startOfMonth();
        } catch (\Throwable) {
            $month = now()->startOfMonth();
        }

        $sum = fn (string $type) => (float) $user->transactions()
            ->where('type', $type)
            ->whereBetween('transaction_date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->sum('amount');

        $income = $sum('income');
        $spent = $sum('expense');

        // Wie im Web-Cockpit: mit Plan gegen die geplanten Ausgaben, sonst gegen die Einnahmen.
        $plan = $plans->find($user, $month);
        $totals = $plan ? $plans->totals($plan) : null;

        $remaining = $totals ? round($totals['expense'] - $spent, 2) : round($income - $spent, 2);
        $base = $totals ? $totals['expense'] : $income;
        $today = now();
        $daysLeft = $month->isSameMonth($today) ? $month->daysInMonth - $today->day : 0;

        $groups = $plan ? $plan->groups->keyBy('name') : collect();

        $upcoming = $plans->upcoming($user, $month)->map(function (array $entry) use ($groups) {
            $group = $groups->get($entry['group']);

            return [
                'name' => $entry['title'],
                'date' => $entry['date']->toDateString(),
                'amount' => $entry['amount'],
                'icon' => $group?->icon,
                'color' => $group?->color,
            ];
        })->values();

        $uncategorized = $user->transactions()
            ->whereNull('category_id')
            ->whereIn('type', ['income', 'expense']);

        $reviewItems = (clone $uncategorized)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return response()->json([
            'month' => $month->format('Y-m'),
            'greeting_name' => explode(' ', trim($user->name))[0],
            'cockpit' => [
                'has_plan' => $totals !== null,
                'remaining' => $remaining,
                'per_day' => $daysLeft > 0 && $remaining > 0 ? round($remaining / $daysLeft, 2) : null,
                'days_left' => $daysLeft,
                'planned_expense' => $totals['expense'] ?? null,
                'planned_income' => $totals['income'] ?? null,
                'spent' => round($spent, 2),
                'ratio' => $base > 0 ? round($spent / $base, 2) : 0,
            ],
            'upcoming' => $upcoming,
            'review' => [
                'count' => (clone $uncategorized)->count(),
                'transfer_suggestions' => $transfers->count($user->id),
                'items' => $reviewItems->map(fn ($t) => [
                    'id' => $t->id,
                    'description' => $t->description,
                    'amount' => $t->type === 'income' ? round((float) $t->amount, 2) : -round((float) $t->amount, 2),
                    'date' => $t->transaction_date->toDateString(),
                ])->values(),
            ],
        ]);
    }

    public function accounts(Request $request)
    {
        $accounts = $request->user()->accounts()->with('provider')->orderBy('name')->get();
        $items = $accounts->map(fn ($account) => ApiPresenter::account($account))->values();

        return response()->json([
            'data' => $items,
            'total_balance' => round($items->where('is_active', true)->where('include_in_total', true)->sum('balance'), 2),
        ]);
    }

    public function categories(Request $request)
    {
        $categories = $request->user()->categories()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json($categories->map(fn ($c) => ApiPresenter::category($c))->values());
    }
}
