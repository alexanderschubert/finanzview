<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlanItem;
use App\Services\PlanService;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Monatsplan für die native App: lesen und Beträge ändern.
 */
class PlanningController extends Controller
{
    public function __construct(private PlanService $plans)
    {
    }

    public function plan(Request $request)
    {
        return response()->json($this->payload($request, $this->month($request)));
    }

    /**
     * Plan für einen Monat anlegen: leer oder aus dem vorherigen Monat übernommen
     * (wie in der Web-Oberfläche; ein vorhandener Plan bleibt unverändert).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'source' => ['nullable', 'in:previous,empty'],
        ], ['month.required' => 'Bitte einen Monat angeben.', 'month.regex' => 'Der Monat muss im Format JJJJ-MM angegeben werden.']);

        $user = $request->user();
        $month = Carbon::createFromFormat('!Y-m', $validated['month'])->startOfMonth();
        $created = false;

        if (! $this->plans->find($user, $month)) {
            $plan = ($validated['source'] ?? null) === 'previous' ? $this->plans->copyFromPrevious($user, $month) : null;
            $plan ??= $this->plans->createEmpty($user, $month);
            $created = true;
        }

        return response()->json($this->payload($request, $month) + ['created' => $created], $created ? 201 : 200);
    }

    private function payload(Request $request, Carbon $month): array
    {
        $user = $request->user();
        $plan = $this->plans->find($user, $month);

        if (! $plan) {
            return [
                'month' => $month->format('Y-m'),
                'exists' => false,
                'previous' => $this->plans->previous($user, $month)?->month->format('Y-m'),
                'totals' => null,
                'groups' => [],
            ];
        }

        $totals = $this->plans->totals($plan);

        return [
            'month' => $month->format('Y-m'),
            'exists' => true,
            'previous' => null,
            'totals' => [
                'income' => $totals['income'],
                'expense' => $totals['expense'],
                'balance' => $totals['balance'],
                'rates' => $totals['rates'],
                'loans' => $totals['loans'],
                'planned_ratio' => $totals['income'] > 0 ? round($totals['expense'] / $totals['income'], 2) : null,
            ],
            'groups' => $plan->groups->map(fn ($group) => [
                'id' => $group->id,
                'name' => $group->name,
                'kind' => $group->kind,
                'icon' => $group->icon,
                'color' => $group->color,
                'sum' => PlanService::groupSum($group, $month),
                'items' => $group->items->map(function (PlanItem $item) use ($month) {
                    $state = $item->stateIn($month);

                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'amount' => round((float) $item->amount, 2),
                        'interval' => $item->interval,
                        'interval_label' => $item->intervalLabel(),
                        'due_day' => $item->due_day,
                        'note' => $item->note,
                        'state' => $state,
                        'next_due' => in_array($state, ['off', 'upcoming'], true) ? $item->nextDueAfter($month)?->format('Y-m') : null,
                    ];
                })->values(),
            ])->values(),
        ];
    }

    public function updateItem(Request $request, int $id)
    {
        $item = PlanItem::with('group.planMonth')->findOrFail($id);

        // Fremde Pläne wie nicht vorhanden behandeln.
        abort_unless($item->group?->planMonth?->user_id === $request->user()->id, 404);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
        ], ['amount.required' => 'Bitte einen Betrag angeben.', 'amount.numeric' => 'Der Betrag muss eine Zahl sein.']);

        $item->update(['amount' => round((float) $validated['amount'], 2)]);

        return response()->json(['id' => $item->id, 'amount' => round((float) $item->amount, 2)]);
    }

    private function month(Request $request): Carbon
    {
        try {
            return Carbon::createFromFormat('!Y-m', (string) $request->input('month', now()->format('Y-m')))->startOfMonth();
        } catch (\Throwable) {
            return now()->startOfMonth();
        }
    }
}
