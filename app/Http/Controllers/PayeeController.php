<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Payee;
use App\Models\PayeeAlias;
use App\Services\PayeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PayeeController extends Controller
{
    public function __construct(
        private PayeeService $payees
    ) {
    }


    public function index(Request $request)
    {
        $userId = Auth::id();

        // Ältere Buchungen: für jeden Händlernamen einen Empfänger anlegen und zuordnen.
        $this->payees->assignUnassigned($userId);

        $stats = $this->payees->stats($userId);
        $suggestions = $this->payees->suggestions($userId);

        $showSuggestions = $request->query('view') === 'suggestions';
        $search = trim((string) $request->query('q'));
        $sort = in_array($request->query('sort'), ['count', 'amount', 'name', 'recent'], true) ? $request->query('sort') : 'count';

        $rows = Payee::query()
            ->where('user_id', $userId)
            ->with('defaultCategory')
            ->get()
            ->map(function (Payee $payee) use ($stats) {
                $stat = $stats[$payee->id] ?? ['count' => 0, 'expense' => 0.0, 'income' => 0.0, 'last' => null];

                return ['payee' => $payee] + $stat;
            })
            ->when($search !== '', fn ($rows) => $rows->filter(fn ($row) => str_contains(mb_strtolower($row['payee']->name), mb_strtolower($search))))
            ->sortBy(match ($sort) {
                'name' => fn ($row) => mb_strtolower($row['payee']->name),
                'amount' => fn ($row) => -($row['expense'] + $row['income']),
                'recent' => fn ($row) => -strtotime((string) $row['last']),
                default => fn ($row) => [-$row['count'], mb_strtolower($row['payee']->name)],
            })
            ->values();

        return view('payees.index', [
            'rows' => $rows,
            'suggestions' => $suggestions,
            'stats' => $stats,
            'showSuggestions' => $showSuggestions,
            'search' => $search,
            'sort' => $sort,
            'total' => Payee::query()->where('user_id', $userId)->count(),
        ]);
    }

    public function edit(Payee $payee)
    {
        $this->authorizePayee($payee);

        $stat = $this->payees->stats(Auth::id())[$payee->id]
            ?? ['count' => 0, 'expense' => 0.0, 'income' => 0.0, 'last' => null];

        return view('payees.edit', [
            'payee' => $payee->load('aliases'),
            'stat' => $stat,
            'categories' => $this->categories(),
        ]);
    }

    public function update(Request $request, Payee $payee)
    {
        $this->authorizePayee($payee);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'default_category_id' => ['nullable', 'integer'],
        ], [
            'name.required' => 'Bitte einen Namen eingeben.',
        ]);

        $categoryId = $this->categoryId($validated['default_category_id'] ?? null);

        $merged = $this->payees->merge(Auth::id(), collect([$payee]), $validated['name'], $categoryId, false);

        // Ausdrücklich „keine Standardkategorie“.
        if ($categoryId === null && $merged->default_category_id !== null) {
            $merged->update(['default_category_id' => null]);
        }

        $applied = $request->boolean('apply') ? $this->payees->applyCategory(Auth::id(), $merged->fresh()) : null;

        return redirect()
            ->route('payees.index')
            ->with('success', "Empfänger „{$merged->name}“ wurde gespeichert."
                . ($applied === null ? '' : ($applied === 1 ? ' 1 Buchung bekam die Kategorie.' : " {$applied} Buchungen bekamen die Kategorie.")));
    }

    public function destroy(Payee $payee)
    {
        $this->authorizePayee($payee);

        $count = $this->payees->stats(Auth::id())[$payee->id]['count'] ?? 0;

        if ($count > 0) {
            return back()->with('error', 'Empfänger mit Buchungen können nicht gelöscht werden – führe sie stattdessen mit einem anderen zusammen.');
        }

        $payee->delete();

        return redirect()->route('payees.index')->with('success', 'Empfänger wurde gelöscht.');
    }

    public function destroyAlias(Payee $payee, PayeeAlias $alias)
    {
        $this->authorizePayee($payee);

        if (! $this->payees->removeAlias($payee, $alias)) {
            return back()->with('error', 'Dieser Name kann nicht entfernt werden.');
        }

        return back()->with('success', 'Schreibweise wurde entfernt. Künftige Buchungen damit werden wieder als eigener Empfänger angelegt.');
    }


    /*
    |--------------------------------------------------------------------------
    | Zusammenführen
    |--------------------------------------------------------------------------
    */

    /**
     * Bestätigungsseite: Zielname und Standardkategorie wählen.
     */
    public function mergeForm(Request $request)
    {
        $selected = $this->selected($request);

        if ($selected->count() < 2) {
            return redirect()->route('payees.index')->with('error', 'Bitte mindestens zwei Empfänger auswählen.');
        }

        $stats = $this->payees->stats(Auth::id());
        $count = fn (Payee $payee) => $stats[$payee->id]['count'] ?? 0;

        $selected = $selected->sortByDesc($count)->values();

        return view('payees.merge', [
            'selected' => $selected,
            'stats' => $stats,
            'suggestedName' => $selected->first()->name,
            'suggestedCategory' => $selected->first(fn (Payee $payee) => $payee->default_category_id !== null)?->default_category_id,
            'categories' => $this->categories(),
        ]);
    }

    public function mergeStore(Request $request)
    {
        $selected = $this->selected($request);

        if ($selected->count() < 2) {
            return redirect()->route('payees.index')->with('error', 'Bitte mindestens zwei Empfänger auswählen.');
        }

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'default_category_id' => ['nullable', 'integer'],
        ], [
            'name.required' => 'Bitte einen Namen eingeben.',
        ]);

        $merged = $this->payees->merge(
            Auth::id(),
            $selected,
            (string) $request->input('name'),
            $this->categoryId($request->input('default_category_id')),
            $request->boolean('apply')
        );

        return redirect()
            ->route('payees.index')
            ->with('success', "{$selected->count()} Empfänger wurden zu „{$merged->name}“ zusammengeführt.");
    }

    public function ignore(Request $request)
    {
        $selected = $this->selected($request);

        Payee::query()->whereIn('id', $selected->pluck('id'))->update(['ignore_suggestions' => true]);

        return back()->with('success', 'Wird nicht mehr als ähnlicher Name vorgeschlagen.');
    }


    /*
    |--------------------------------------------------------------------------
    | Hilfsfunktionen
    |--------------------------------------------------------------------------
    */

    /**
     * Ausgewählte Empfänger – nur eigene.
     */
    private function selected(Request $request)
    {
        $ids = collect((array) $request->input('ids'))
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->take(200);

        return Payee::query()
            ->where('user_id', Auth::id())
            ->whereIn('id', $ids)
            ->get();
    }

    private function categories()
    {
        return Category::query()
            ->where('user_id', Auth::id())
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->inTreeOrder();
    }

    private function categoryId(mixed $id): ?int
    {
        if (! is_numeric($id)) {
            return null;
        }

        return Category::query()->where('user_id', Auth::id())->where('is_active', true)->whereKey((int) $id)->value('id');
    }

    private function authorizePayee(Payee $payee): void
    {
        abort_unless((int) $payee->user_id === (int) Auth::id(), 404);
    }
}
