<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiPresenter;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Services\CategoryRuleService;
use App\Services\PayeeService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'account_id' => ['nullable', 'integer'],
            'category_id' => ['nullable', 'integer'],
            'uncategorized' => ['nullable', 'boolean'],
            'type' => ['nullable', 'in:income,expense,transfer'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $user = $request->user();

        $query = $user->transactions()
            ->with(['account:id,name', 'transferAccount:id,name', 'category', 'payee:id,name', 'tags:id,name']);

        if (filled($validated['search'] ?? null)) {
            $search = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], mb_strtolower($validated['search'])) . '%';

            $query->where(function ($q) use ($search) {
                $q->whereRaw("LOWER(description) LIKE ? ESCAPE '\\'", [$search])
                    ->orWhereRaw("LOWER(merchant) LIKE ? ESCAPE '\\'", [$search])
                    ->orWhereRaw("LOWER(notes) LIKE ? ESCAPE '\\'", [$search])
                    ->orWhereHas('payee', fn ($payee) => $payee->whereRaw("LOWER(name) LIKE ? ESCAPE '\\'", [$search]));
            });
        }

        if (! empty($validated['account_id'])) {
            $query->where(fn ($q) => $q->where('account_id', $validated['account_id'])->orWhere('transfer_account_id', $validated['account_id']));
        }

        if ($request->boolean('uncategorized')) {
            $query->whereNull('category_id')->whereIn('type', ['income', 'expense']);
        } elseif (! empty($validated['category_id'])) {
            $own = $user->categories()->whereKey($validated['category_id'])->exists();
            $query->whereIn('category_id', $own ? Category::withDescendantIds([$validated['category_id']]) : [0]);
        }

        if (! empty($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        if (! empty($validated['from'])) {
            $query->whereDate('transaction_date', '>=', $validated['from']);
        }

        if (! empty($validated['to'])) {
            $query->whereDate('transaction_date', '<=', $validated['to']);
        }

        $page = $query
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate($validated['per_page'] ?? 50);

        return response()->json([
            'data' => $page->getCollection()->map(fn ($t) => ApiPresenter::transaction($t))->values(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    public function show(Request $request, int $id)
    {
        return response()->json(ApiPresenter::transaction($this->own($request, $id)));
    }

    public function store(Request $request, PayeeService $payees, CategoryRuleService $rules)
    {
        $user = $request->user();

        $validated = $request->validate([
            'account_id' => ['required', 'integer', Rule::exists('accounts', 'id')->where('user_id', $user->id)->where('is_active', true)->whereNull('deleted_at')],
            'type' => ['required', 'in:income,expense'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer'],
            'payee_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $category = $this->category($user->id, $validated['category_id'] ?? null, $validated['type']);

        $transaction = new Transaction();
        $transaction->user_id = $user->id;
        $transaction->account_id = $validated['account_id'];
        $transaction->type = $validated['type'];
        $transaction->amount = $validated['amount'];
        $transaction->transaction_date = $validated['date'];
        $transaction->description = $validated['description'];
        $transaction->notes = $this->note($validated['notes'] ?? null);
        $transaction->category_id = $category?->id;

        // Wie in der Web-Oberfläche: ohne Kategorie zuerst Regeln, dann Standard des Empfängers.
        if ($transaction->category_id === null) {
            $transaction->category_id = $rules->match(
                $rules->rulesFor($user->id),
                $transaction->type,
                null,
                $transaction->description
            );
        }

        $payee = $payees->forTransaction($user->id, null, $validated['payee_name'] ?? null);

        if ($payee) {
            $transaction->payee_id = $payee->id;
            $default = $transaction->category_id === null ? $payee->defaultCategory : null;

            if ($default && $default->is_active && in_array($default->type, ['both', $transaction->type], true)) {
                $transaction->category_id = $default->id;
            }
        }

        $transaction->save();

        return response()->json(ApiPresenter::transaction(ApiPresenter::loadTransaction($transaction)), 201);
    }

    public function update(Request $request, int $id, PayeeService $payees)
    {
        $user = $request->user();
        $transaction = $this->own($request, $id);

        $validated = $request->validate([
            'category_id' => ['nullable', 'integer'],
            'payee_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'tag_ids' => ['nullable', 'array', 'max:10'],
            'tag_ids.*' => ['integer', Rule::exists('tags', 'id')->where('user_id', $user->id)],
            'remember' => ['nullable', 'boolean'],
        ]);

        $changes = [];

        if ($request->exists('category_id')) {
            if ($transaction->type === 'transfer' && ! empty($validated['category_id'])) {
                throw ValidationException::withMessages(['category_id' => 'Umbuchungen haben keine Kategorie.']);
            }

            $changes['category_id'] = $this->category($user->id, $validated['category_id'] ?? null, $transaction->type)?->id;
        }

        if ($request->exists('notes')) {
            $changes['notes'] = $this->note($validated['notes'] ?? null);
        }

        if ($request->exists('payee_name') && $transaction->type !== 'transfer') {
            $changes['payee_id'] = $payees->forTransaction($user->id, $transaction->merchant, $validated['payee_name'] ?? null)?->id;
        }

        if ($changes !== []) {
            $transaction->update($changes);
        }

        if ($request->exists('tag_ids')) {
            $transaction->tags()->sync($validated['tag_ids'] ?? []);
        }

        // Für den Empfänger merken (wie bei „Zu prüfen“ im Web).
        if ($request->boolean('remember') && ! empty($changes['category_id'])) {
            $payee = $transaction->fresh()->payee;

            if ($payee) {
                if ($payee->default_category_id === null) {
                    $payee->update(['default_category_id' => $changes['category_id']]);
                }

                $payees->applyCategory($user->id, $payee->fresh());
            }
        }

        return response()->json(ApiPresenter::transaction(ApiPresenter::loadTransaction($transaction->fresh())));
    }

    private function own(Request $request, int $id): Transaction
    {
        return ApiPresenter::loadTransaction($request->user()->transactions()->findOrFail($id));
    }

    private function note(?string $notes): ?string
    {
        return $notes !== null && trim($notes) !== '' ? trim($notes) : null;
    }

    private function category(int $userId, ?int $id, string $type): ?Category
    {
        if (empty($id)) {
            return null;
        }

        $category = Category::query()->where('user_id', $userId)->where('is_active', true)->find($id);

        if (! $category || ! in_array($category->type, [$type, 'both'], true)) {
            throw ValidationException::withMessages(['category_id' => 'Diese Kategorie passt nicht zur Buchung.']);
        }

        return $category;
    }
}
