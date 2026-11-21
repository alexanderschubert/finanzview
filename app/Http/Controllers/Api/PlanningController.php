<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlanGroup;
use App\Models\PlanItem;
use App\Models\PlanMonth;
use App\Services\PlanService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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

    public function storeGroup(Request $request)
    {
        $validated = $request->validate([
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'name' => ['required', 'string', 'max:255'],
            'kind' => ['required', Rule::in(array_keys(PlanGroup::KINDS))],
            'icon' => ['nullable', 'string', 'max:20'],
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ], ['name.required' => 'Bitte einen Namen für die Gruppe eingeben.']);

        $month = Carbon::createFromFormat('!Y-m', $validated['month'])->startOfMonth();
        $plan = $this->plans->find($request->user(), $month);

        if (! $plan) {
            throw ValidationException::withMessages(['month' => 'Für diesen Monat gibt es noch keinen Plan.']);
        }

        $plan->groups()->create([
            'name' => trim($validated['name']),
            'kind' => $validated['kind'],
            'icon' => filled($validated['icon'] ?? null) ? trim($validated['icon']) : null,
            'color' => $validated['color'] ?? null,
            'sort_order' => $this->plans->nextSortOrder($plan),
        ]);

        return response()->json($this->payload($request, $month), 201);
    }

    public function updateGroup(Request $request, int $id)
    {
        $group = $this->ownGroup($request, $id);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'kind' => ['sometimes', 'required', Rule::in(array_keys(PlanGroup::KINDS))],
            'icon' => ['sometimes', 'nullable', 'string', 'max:20'],
            'color' => ['sometimes', 'nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ], ['name.required' => 'Bitte einen Namen eingeben.']);

        if (isset($validated['name'])) {
            $validated['name'] = trim($validated['name']);
        }

        $group->update($validated);

        return response()->json($this->payload($request, $group->planMonth->month));
    }

    public function destroyGroup(Request $request, int $id)
    {
        $group = $this->ownGroup($request, $id);
        $month = $group->planMonth->month;
        $group->delete();

        return response()->json($this->payload($request, $month));
    }

    public function storeItem(Request $request, int $id)
    {
        $group = $this->ownGroup($request, $id);
        $validated = $this->validateItem($request, requireAmount: false);

        $group->items()->create([
            'name' => trim($validated['name']),
            'amount' => round((float) ($validated['amount'] ?? 0), 2),
            'note' => filled($validated['note'] ?? null) ? trim($validated['note']) : null,
            'interval' => $validated['interval'] ?? PlanItem::MONTHLY,
            'due_day' => $validated['due_day'] ?? null,
            'starts_on' => $validated['starts_on'] ?? null,
            'ends_on' => $validated['ends_on'] ?? null,
            'sort_order' => $this->plans->nextItemSortOrder($group),
        ]);

        return response()->json($this->payload($request, $group->planMonth->month), 201);
    }

    public function updateItem(Request $request, int $id)
    {
        $item = PlanItem::with('group.planMonth')->findOrFail($id);

        // Fremde Pläne wie nicht vorhanden behandeln.
        abort_unless($item->group?->planMonth?->user_id === $request->user()->id, 404);

        // Nur „amount“: schnelle Änderung des Betrags. Mit „name“: der ganze Posten.
        $full = $request->has('name');
        $validated = $this->validateItem($request, requireAmount: true, full: $full);

        $data = ['amount' => round((float) $validated['amount'], 2)];

        if ($full) {
            $data += [
                'name' => trim($validated['name']),
                'note' => filled($validated['note'] ?? null) ? trim($validated['note']) : null,
                'interval' => $validated['interval'] ?? PlanItem::MONTHLY,
                'due_day' => $validated['due_day'] ?? null,
                'starts_on' => $validated['starts_on'] ?? null,
                'ends_on' => $validated['ends_on'] ?? null,
            ];
        }

        $item->update($data);

        return response()->json($this->payload($request, $item->group->planMonth->month) + ['id' => $item->id, 'amount' => round((float) $item->amount, 2)]);
    }

    public function destroyItem(Request $request, int $id)
    {
        $item = PlanItem::with('group.planMonth')->findOrFail($id);
        abort_unless($item->group?->planMonth?->user_id === $request->user()->id, 404);

        $month = $item->group->planMonth->month;
        $item->delete();

        return response()->json($this->payload($request, $month));
    }

    private function ownGroup(Request $request, int $id): PlanGroup
    {
        $group = PlanGroup::with('planMonth')->findOrFail($id);
        abort_unless($group->planMonth?->user_id === $request->user()->id, 404);

        return $group;
    }

    private function validateItem(Request $request, bool $requireAmount, bool $full = true): array
    {
        $validated = $request->validate([
            'name' => $full ? ['required', 'string', 'max:255'] : ['sometimes', 'required', 'string', 'max:255'],
            'amount' => [$requireAmount ? 'required' : 'nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'note' => ['nullable', 'string', 'max:255'],
            'interval' => ['nullable', Rule::in(array_keys(PlanItem::INTERVALS))],
            'due_day' => ['nullable', 'integer', 'between:1,31'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', ...($request->filled('starts_on') ? ['after_or_equal:starts_on'] : [])],
        ], [
            'name.required' => 'Bitte eine Beschreibung eingeben.',
            'amount.required' => 'Bitte einen Betrag angeben.',
            'amount.numeric' => 'Der Betrag muss eine Zahl sein.',
            'ends_on.after_or_equal' => 'Das Ende muss nach dem Beginn liegen.',
        ]);

        if ($full && ($validated['interval'] ?? PlanItem::MONTHLY) !== PlanItem::MONTHLY && empty($validated['starts_on'])) {
            throw ValidationException::withMessages(['starts_on' => 'Bitte angeben, wann die erste Zahlung fällig ist. Daraus ergibt sich, in welchen Monaten gezahlt wird.']);
        }

        return $validated;
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
