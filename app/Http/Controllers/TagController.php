<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Services\TagService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Übersicht und Pflege der Tags. Neue Tags entstehen direkt
 * beim Erfassen einer Buchung.
 */
class TagController extends Controller
{
    public function index()
    {
        $tags = Tag::query()
            ->where('user_id', Auth::id())
            ->withCount('transactions')
            ->withSum(['transactions as expense_total' => fn ($q) => $q->where('type', 'expense')], 'amount')
            ->withSum(['transactions as income_total' => fn ($q) => $q->where('type', 'income')], 'amount')
            ->orderBy('name')
            ->get();

        return view('tags.index', compact('tags'));
    }

    public function edit(Tag $tag)
    {
        $this->authorizeTag($tag);

        return view('tags.edit', compact('tag'));
    }

    public function update(Request $request, Tag $tag)
    {
        $this->authorizeTag($tag);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:' . TagService::MAX_LENGTH],
            'color' => ['nullable', Rule::in(array_keys(Tag::COLORS))],
        ], [
            'name.required' => 'Bitte einen Namen eingeben.',
        ]);

        $name = Str::squish(ltrim(trim($validated['name']), '#'));

        $duplicate = Tag::query()
            ->where('user_id', Auth::id())
            ->whereKeyNot($tag->id)
            ->get(['name'])
            ->contains(fn (Tag $other) => mb_strtolower($other->name) === mb_strtolower($name));

        if ($name === '' || $duplicate) {
            throw ValidationException::withMessages([
                'name' => $name === '' ? 'Bitte einen Namen eingeben.' : 'Diesen Tag gibt es bereits.',
            ]);
        }

        $tag->update([
            'name' => $name,
            'color' => $validated['color'] ?? null,
        ]);

        return redirect()->route('tags.index')->with('success', "Tag „{$name}“ wurde gespeichert.");
    }

    public function destroy(Tag $tag)
    {
        $this->authorizeTag($tag);

        $tag->transactions()->detach();
        $tag->delete();

        return redirect()
            ->route('tags.index')
            ->with('success', 'Tag wurde gelöscht. Die Buchungen bleiben erhalten.');
    }

    private function authorizeTag(Tag $tag): void
    {
        abort_unless((int) $tag->user_id === (int) Auth::id(), 404);
    }
}
