<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function create(): View
    {
        return view('categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:income,expense,both'],
            'icon' => ['nullable', 'string', 'max:20'],
            'color' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['is_active'] = true;

        $request->user()->categories()->create($validated);

        return redirect()
            ->route('categories.index')
            ->with('success', 'Kategorie wurde erstellt.');
    }

    public function edit(
        Request $request,
        Category $category
    ): View {
        abort_unless(
            $category->user_id === $request->user()->id,
            403
        );

        return view('categories.edit', [
            'category' => $category,
        ]);
    }

    public function update(
        Request $request,
        Category $category
    ): RedirectResponse {
        abort_unless(
            $category->user_id === $request->user()->id,
            403
        );

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:income,expense,both'],
            'icon' => ['nullable', 'string', 'max:20'],
            'color' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $category->update($validated);

        return redirect()
            ->route('categories.index')
            ->with('success', 'Kategorie wurde aktualisiert.');
    }

    public function destroy(
        Request $request,
        Category $category
    ): RedirectResponse {
        abort_unless(
            $category->user_id === $request->user()->id,
            403
        );

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

        $category->delete();

        return redirect()
            ->route('categories.index')
            ->with('success', 'Kategorie wurde gelöscht.');
    }
}