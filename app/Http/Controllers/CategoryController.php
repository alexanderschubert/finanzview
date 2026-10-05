<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $categories = $request->user()
            ->categories()
            ->orderBy('is_active', 'desc')
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return view('categories.index', [
            'categories' => $categories,
        ]);
    }

    public function create(Request $request): View
    {
        // Vorbelegung über „Unterkategorie hinzufügen“.
        $parent = $this->parentOptions($request)->firstWhere('id', (int) $request->query('parent'));

        return view('categories.create', [
            'parentOptions' => $this->parentOptions($request),
            'preselectedParent' => $parent,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        $validated['user_id'] = $request->user()->id;
        $validated['is_active'] = true;

        $request->user()->categories()->create($validated);

        return redirect()
            ->route('categories.index')
            ->with('success', empty($validated['parent_id']) ? 'Kategorie wurde erstellt.' : 'Unterkategorie wurde erstellt.');
    }

    public function edit(
        Request $request,
        Category $category
    ): View {
        $this->authorizeCategory($request, $category);

        return view('categories.edit', [
            'category' => $category,
            'parentOptions' => $this->parentOptions($request, $category),
            'childCount' => $category->children()->count(),
        ]);
    }

    public function update(
        Request $request,
        Category $category
    ): RedirectResponse {
        $this->authorizeCategory($request, $category);

        $validated = $this->validated($request, $category);
        $validated['is_active'] = $request->boolean('is_active');

        // Ändert sich die Art einer Hauptkategorie, müssen die Unterkategorien dazu passen.
        if ($validated['type'] !== 'both') {
            $conflict = $category->children()->where('type', '!=', $validated['type'])->exists();

            if ($conflict) {
                throw ValidationException::withMessages([
                    'type' => 'Unterkategorien dieser Kategorie haben eine andere Art. Ändere zuerst deren Art oder wähle „Beides“.',
                ]);
            }
        }

        $category->update($validated);

        return redirect()
            ->route('categories.index')
            ->with('success', 'Kategorie wurde aktualisiert.');
    }

    public function destroy(
        Request $request,
        Category $category
    ): RedirectResponse {
        $this->authorizeCategory($request, $category);

        /*
         * Kategorien mit Buchungen werden archiviert,
         * damit die Finanzhistorie erhalten bleibt.
         */
        if ($category->transactions()->exists()) {
            $category->update([
                'is_active' => false,
            ]);

            return redirect()
                ->route('categories.index')
                ->with(
                    'success',
                    'Die Kategorie wurde archiviert, da bereits Buchungen vorhanden sind.'
                );
        }

        // Unterkategorien werden zu eigenständigen Kategorien.
        $category->children()->update(['parent_id' => null]);

        $category->delete();

        return redirect()
            ->route('categories.index')
            ->with('success', 'Kategorie wurde gelöscht.');
    }


    /*
    |--------------------------------------------------------------------------
    | Hilfsfunktionen
    |--------------------------------------------------------------------------
    */

    /**
     * Mögliche übergeordnete Kategorien: aktive Hauptkategorien des
     * Benutzers (ohne die Kategorie selbst).
     */
    private function parentOptions(Request $request, ?Category $except = null)
    {
        return $request->user()
            ->categories()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->when($except, fn ($query) => $query->whereKeyNot($except->id))
            ->orderBy('type')
            ->orderBy('name')
            ->get();
    }

    private function validated(Request $request, ?Category $category = null): array
    {
        $validated = $request->validate([
            'parent_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:income,expense,both'],
            'icon' => ['nullable', 'string', 'max:20'],
            'color' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
        ] + ($category ? ['is_active' => ['nullable', 'boolean']] : []));

        $parentId = $validated['parent_id'] ?? null;

        if ($parentId === null) {
            $validated['parent_id'] = null;

            return $validated;
        }

        $parent = $this->parentOptions($request, $category)->firstWhere('id', (int) $parentId);

        // Nur eigene, aktive Hauptkategorien – nicht die Kategorie selbst.
        if (! $parent) {
            throw ValidationException::withMessages([
                'parent_id' => 'Die übergeordnete Kategorie ist ungültig.',
            ]);
        }

        // Nur eine Ebene: Eine Kategorie mit Unterkategorien kann keine Unterkategorie werden.
        if ($category && $category->children()->exists()) {
            throw ValidationException::withMessages([
                'parent_id' => 'Diese Kategorie hat selbst Unterkategorien und kann deshalb keiner anderen untergeordnet werden.',
            ]);
        }

        // Die Art folgt der übergeordneten Kategorie („Beides“ erlaubt jede Art).
        if ($parent->type !== 'both') {
            $validated['type'] = $parent->type;
        }

        $validated['parent_id'] = $parent->id;

        return $validated;
    }

    private function authorizeCategory(Request $request, Category $category): void
    {
        abort_unless(
            $category->user_id === $request->user()->id,
            403
        );
    }
}
