<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CategoryRule;
use App\Models\Transaction;
use App\Services\CategoryRuleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CategoryRuleController extends Controller
{
    public function __construct(
        private CategoryRuleService $rules
    ) {
    }


    /**
     * Regeln anzeigen, neue Regel anlegen
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $rules = CategoryRule::query()
            ->where('user_id', $user->id)
            ->with('category')
            ->orderBy('pattern')
            ->get();

        $categories = $this->categories();

        $uncategorizedCount = Transaction::query()
            ->where('user_id', $user->id)
            ->whereNull('category_id')
            ->whereIn('type', ['income', 'expense'])
            ->count();

        // Vorbelegung, z. B. aus „Als Regel speichern“ bei einer Buchung.
        $prefill = new CategoryRule([
            'pattern' => mb_substr((string) $request->query('pattern'), 0, 100),
            'category_id' => $categories->contains('id', (int) $request->query('category_id'))
                ? (int) $request->query('category_id')
                : null,
            'match_field' => 'any',
            'is_active' => true,
        ]);

        return view('category-rules.index', compact('rules', 'categories', 'uncategorizedCount', 'prefill'));
    }


    public function store(Request $request)
    {
        $user = Auth::user();

        $rule = CategoryRule::create([
            ...$this->validated($request),
            'user_id' => $user->id,
        ]);

        $message = "Regel „{$rule->pattern}“ wurde angelegt.";

        if ($request->boolean('apply')) {
            $count = $this->rules->applyToUncategorized($user->id, $rule);
            $message .= ' ' . $this->appliedMessage($count);
        }

        return redirect()->route('category-rules.index')->with('success', $message);
    }


    public function edit(CategoryRule $categoryRule)
    {
        $this->authorizeRule($categoryRule);

        return view('category-rules.edit', [
            'rule' => $categoryRule,
            'categories' => $this->categories(),
        ]);
    }


    public function update(Request $request, CategoryRule $categoryRule)
    {
        $this->authorizeRule($categoryRule);

        $categoryRule->update($this->validated($request));

        $message = 'Regel wurde gespeichert.';

        if ($request->boolean('apply')) {
            $count = $this->rules->applyToUncategorized(Auth::id(), $categoryRule);
            $message .= ' ' . $this->appliedMessage($count);
        }

        return redirect()->route('category-rules.index')->with('success', $message);
    }


    public function destroy(CategoryRule $categoryRule)
    {
        $this->authorizeRule($categoryRule);

        $categoryRule->delete();

        return redirect()
            ->route('category-rules.index')
            ->with('success', 'Regel wurde gelöscht. Bereits zugeordnete Buchungen bleiben unverändert.');
    }


    /**
     * Alle Regeln auf Buchungen ohne Kategorie anwenden
     */
    public function apply()
    {
        $count = $this->rules->applyToUncategorized(Auth::id());

        return redirect()->route('category-rules.index')->with('success', $this->appliedMessage($count));
    }


    /*
    |--------------------------------------------------------------------------
    | Hilfsfunktionen
    |--------------------------------------------------------------------------
    */

    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'pattern' => ['required', 'string', 'min:2', 'max:100'],
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')
                    ->where('user_id', Auth::id())
                    ->where('is_active', true)
                    ->whereNull('deleted_at'),
            ],
            'match_field' => ['required', Rule::in(array_keys(CategoryRule::FIELDS))],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'pattern.required' => 'Bitte einen Suchbegriff eingeben.',
            'pattern.min' => 'Der Suchbegriff muss mindestens 2 Zeichen lang sein.',
            'category_id.required' => 'Bitte eine Kategorie auswählen.',
            'category_id.exists' => 'Die ausgewählte Kategorie ist ungültig.',
        ]);

        return [
            'pattern' => trim($validated['pattern']),
            'category_id' => (int) $validated['category_id'],
            'match_field' => $validated['match_field'],
            'is_active' => $request->boolean('is_active'),
        ];
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

    private function authorizeRule(CategoryRule $rule): void
    {
        abort_unless((int) $rule->user_id === (int) Auth::id(), 404);
    }

    private function appliedMessage(int $count): string
    {
        return match ($count) {
            0 => 'Keine Buchung ohne Kategorie passte.',
            1 => '1 Buchung wurde zugeordnet.',
            default => "{$count} Buchungen wurden zugeordnet.",
        };
    }
}
