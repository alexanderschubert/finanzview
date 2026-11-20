<?php

namespace App\Http\Controllers;

use App\Models\PlanGroup;
use App\Models\PlanItem;
use App\Models\PlanMonth;
use App\Services\PlanService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Monatsplan: feste Einnahmen und Ausgaben pro Monat in Gruppen.
 * Der Plan bucht nichts und ändert keine Kontostände.
 */
class PlanController extends Controller
{
    public function __construct(
        private PlanService $plans
    ) {
    }

    public function index(Request $request)
    {
        $month = $this->month($request->query('month'));
        $user = Auth::user();

        $plan = $this->plans->find($user, $month);

        return view('plan.index', [
            'month' => $month,
            'monthKey' => $month->format('Y-m'),
            'plan' => $plan,
            'totals' => $plan ? $this->plans->totals($plan) : null,
            'previous' => $plan ? null : $this->plans->previous($user, $month),
            'kinds' => PlanGroup::KINDS,
        ]);
    }

    /**
     * Plan für einen Monat anlegen: aus dem letzten Monat oder leer.
     */
    public function store(Request $request)
    {
        $month = $this->month($request->input('month'));
        $user = Auth::user();

        $plan = $request->input('source') === 'previous'
            ? $this->plans->copyFromPrevious($user, $month)
            : null;

        $plan ??= $this->plans->createEmpty($user, $month);

        return redirect()->route('plan.index', ['month' => $month->format('Y-m')]);
    }

    /**
     * Kalender: Zahlungen des Monats aus dem Plan (mit Zahlungstag) und die Raten der Kredite.
     */
    public function calendar(Request $request)
    {
        $month = $this->month($request->query('month'));
        $user = Auth::user();

        $calendar = $this->plans->calendar($user, $month);

        return view('plan.calendar', [
            'month' => $month,
            'monthKey' => $month->format('Y-m'),
            'calendar' => $calendar,
        ]);
    }

    public function destroy(PlanMonth $planMonth)
    {
        $this->authorizeMonth($planMonth);

        $key = $planMonth->month->format('Y-m');
        $planMonth->delete();

        return redirect()
            ->route('plan.index', ['month' => $key])
            ->with('success', 'Der Plan für ' . Carbon::createFromFormat('!Y-m', $key)->translatedFormat('F Y') . ' wurde gelöscht. Buchungen sind davon nicht betroffen.');
    }


    /*
    |--------------------------------------------------------------------------
    | Gruppen
    |--------------------------------------------------------------------------
    */

    public function storeGroup(Request $request, PlanMonth $planMonth)
    {
        $this->authorizeMonth($planMonth);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'kind' => ['required', Rule::in(array_keys(PlanGroup::KINDS))],
        ], ['name.required' => 'Bitte einen Namen für die Gruppe eingeben.']);

        $group = $planMonth->groups()->create([
            'name' => trim($validated['name']),
            'kind' => $validated['kind'],
            'sort_order' => $this->plans->nextSortOrder($planMonth),
        ]);

        return $this->backTo($planMonth, $group);
    }

    public function editGroup(PlanGroup $planGroup)
    {
        $this->authorizeGroup($planGroup);

        return view('plan.group-edit', [
            'group' => $planGroup,
            'monthKey' => $planGroup->planMonth->month->format('Y-m'),
            'kinds' => PlanGroup::KINDS,
        ]);
    }

    public function updateGroup(Request $request, PlanGroup $planGroup)
    {
        $this->authorizeGroup($planGroup);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'kind' => ['required', Rule::in(array_keys(PlanGroup::KINDS))],
        ], ['name.required' => 'Bitte einen Namen eingeben.']);

        $planGroup->update([
            'name' => trim($validated['name']),
            'kind' => $validated['kind'],
        ]);

        return $this->backTo($planGroup->planMonth, $planGroup);
    }

    public function destroyGroup(PlanGroup $planGroup)
    {
        $this->authorizeGroup($planGroup);

        $month = $planGroup->planMonth;
        $planGroup->delete();

        return $this->backTo($month);
    }


    /*
    |--------------------------------------------------------------------------
    | Posten
    |--------------------------------------------------------------------------
    */

    public function storeItem(Request $request, PlanGroup $planGroup)
    {
        $this->authorizeGroup($planGroup);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'amount' => ['nullable', 'string', 'max:30'],
            'note' => ['nullable', 'string', 'max:255'],
            'interval' => ['nullable', Rule::in(array_keys(PlanItem::INTERVALS))],
            'due_day' => ['nullable', 'integer', 'between:1,31'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', ...($request->filled('starts_on') ? ['after_or_equal:starts_on'] : [])],
        ], $this->messages());

        $this->requireStart($validated);

        $planGroup->items()->create([
            'name' => trim($validated['name']),
            'amount' => $this->amount($validated['amount'] ?? null, allowEmpty: true),
            'note' => $this->text($validated['note'] ?? null),
            'interval' => $validated['interval'] ?? PlanItem::MONTHLY,
            'due_day' => $validated['due_day'] ?? null,
            'starts_on' => $validated['starts_on'] ?? null,
            'ends_on' => $validated['ends_on'] ?? null,
            'sort_order' => $this->plans->nextItemSortOrder($planGroup),
        ]);

        return $this->backTo($planGroup->planMonth, $planGroup);
    }

    public function editItem(PlanItem $planItem)
    {
        $this->authorizeItem($planItem);

        return view('plan.item-edit', [
            'item' => $planItem,
            'group' => $planItem->group,
            'intervals' => PlanItem::INTERVALS,
            'monthKey' => $planItem->group->planMonth->month->format('Y-m'),
        ]);
    }

    /**
     * Betrag direkt in der Liste ändern (nur „amount“) oder den
     * ganzen Posten auf der Bearbeiten-Seite.
     */
    public function updateItem(Request $request, PlanItem $planItem)
    {
        $this->authorizeItem($planItem);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'amount' => ['required', 'string', 'max:30'],
            'note' => ['nullable', 'string', 'max:255'],
            'interval' => ['nullable', Rule::in(array_keys(PlanItem::INTERVALS))],
            'due_day' => ['nullable', 'integer', 'between:1,31'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', ...($request->filled('starts_on') ? ['after_or_equal:starts_on'] : [])],
        ], $this->messages());

        $data = ['amount' => $this->amount($validated['amount'])];

        if ($request->has('name')) {
            $this->requireStart($validated);

            $data['name'] = trim($validated['name']);
            $data['note'] = $this->text($validated['note'] ?? null);
            $data['interval'] = $validated['interval'] ?? PlanItem::MONTHLY;
            $data['due_day'] = $validated['due_day'] ?? null;
            $data['starts_on'] = $validated['starts_on'] ?? null;
            $data['ends_on'] = $validated['ends_on'] ?? null;
        }

        $planItem->update($data);

        return $this->backTo($planItem->group->planMonth, $planItem->group);
    }

    public function destroyItem(PlanItem $planItem)
    {
        $this->authorizeItem($planItem);

        $group = $planItem->group;
        $planItem->delete();

        return $this->backTo($group->planMonth, $group);
    }


    /*
    |--------------------------------------------------------------------------
    | CSV-Export und -Import
    |--------------------------------------------------------------------------
    */

    /**
     * Plan eines Monats als CSV (Semikolon, UTF-8 mit BOM für Excel) –
     * im selben Aufbau wie der Import.
     */
    public function export(Request $request)
    {
        $month = $this->month($request->query('month'));
        $plan = $this->plans->find(Auth::user(), $month);

        if (! $plan) {
            return redirect()
                ->route('plan.index', ['month' => $month->format('Y-m')])
                ->with('error', 'Für diesen Monat gibt es noch keinen Plan.');
        }

        $rows = $this->plans->csvRows($plan);

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, PlanService::CSV_HEADER, ';', '"', '');

            foreach ($rows as $row) {
                fputcsv($handle, $row, ';', '"', '');
            }

            fclose($handle);
        }, 'finanzview-monatsplan-' . $month->format('Y-m') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function importForm(Request $request)
    {
        $month = $this->month($request->query('month'));

        return view('plan.import', ['monthKey' => $month->format('Y-m'), 'month' => $month]);
    }

    public function import(Request $request)
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:2048', 'extensions:csv,txt,tsv'],
            'month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
        ], [
            'file.required' => 'Bitte eine CSV-Datei auswählen.',
            'file.extensions' => 'Bitte eine CSV-Datei wählen.',
        ]);

        $month = $this->month($validated['month']);
        $user = Auth::user();

        $plan = $this->plans->find($user, $month) ?? $this->plans->createEmpty($user, $month);

        $stats = $this->plans->import($plan, (string) file_get_contents($request->file('file')->getRealPath()));

        $message = "{$stats['items']} Posten in {$stats['groups']} neuen Gruppen übernommen.";

        if ($stats['skipped'] > 0) {
            $message .= " {$stats['skipped']} Zeilen ohne Gruppe, Beschreibung oder Betrag wurden übersprungen.";
        }

        return redirect()
            ->route('plan.index', ['month' => $month->format('Y-m')])
            ->with('success', $message);
    }


    /*
    |--------------------------------------------------------------------------
    | Hilfen
    |--------------------------------------------------------------------------
    */

    private function month(?string $key): Carbon
    {
        if ($key !== null && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $key)) {
            return PlanService::monthStart($key);
        }

        return now()->startOfMonth();
    }

    private function amount(?string $value, bool $allowEmpty = false): float
    {
        if ($allowEmpty && trim((string) $value) === '') {
            return 0.0;
        }

        $amount = PlanService::parseAmount($value);

        if ($amount === null) {
            throw ValidationException::withMessages(['amount' => 'Bitte einen gültigen Betrag eingeben, z. B. 12,50.']);
        }

        return $amount;
    }

    /**
     * Bei Zahlungen, die nicht monatlich anfallen, muss der Beginn feststehen –
     * er legt fest, in welchen Monaten gezahlt wird.
     */
    private function requireStart(array $validated): void
    {
        $interval = $validated['interval'] ?? PlanItem::MONTHLY;

        if ($interval !== PlanItem::MONTHLY && empty($validated['starts_on'])) {
            throw ValidationException::withMessages(['starts_on' => 'Bitte angeben, wann die erste Zahlung fällig ist. Daraus ergibt sich, in welchen Monaten gezahlt wird.']);
        }
    }

    private function messages(): array
    {
        return [
            'name.required' => 'Bitte eine Beschreibung eingeben.',
            'ends_on.after_or_equal' => 'Das Ende muss nach dem Beginn liegen.',
        ];
    }

    private function text(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function backTo(PlanMonth $month, ?PlanGroup $group = null)
    {
        $url = route('plan.index', ['month' => $month->month->format('Y-m')]);

        return redirect($group && $group->exists ? $url . '#group-' . $group->id : $url);
    }

    private function authorizeMonth(PlanMonth $month): void
    {
        abort_unless($month->user_id === Auth::id(), 404);
    }

    private function authorizeGroup(PlanGroup $group): void
    {
        $this->authorizeMonth($group->planMonth);
    }

    private function authorizeItem(PlanItem $item): void
    {
        $this->authorizeGroup($item->group);
    }
}
