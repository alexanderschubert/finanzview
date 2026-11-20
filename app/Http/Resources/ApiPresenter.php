<?php

namespace App\Http\Resources;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;

/**
 * JSON-Formen der API (siehe native-api-contract.md).
 */
class ApiPresenter
{
    public static function user(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_admin' => $user->isAdmin(),
            'avatar_url' => $user->profile_photo_path ? url('/api/v1/me/avatar') . '?v=' . $user->updated_at?->timestamp : null,
        ];
    }

    public static function account(Account $account): array
    {
        $provider = $account->provider;

        return [
            'id' => $account->id,
            'name' => $account->name,
            'type' => $account->type,
            'currency' => $account->currency,
            'balance' => $account->current_balance,
            'include_in_total' => (bool) $account->include_in_total,
            'is_active' => (bool) $account->is_active,
            'iban_last4' => $account->iban ? substr(preg_replace('/\s+/', '', $account->iban), -4) : null,
            'color' => $account->color,
            'provider' => $provider ? [
                'id' => $provider->id,
                'name' => $provider->name,
                'logo_url' => $provider->logo ? asset($provider->logo) : null,
                'color' => $provider->color,
            ] : null,
        ];
    }

    public static function category(Category $category): array
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'icon' => $category->icon,
            'color' => $category->color,
            'type' => $category->type,
            'parent_id' => $category->parent_id,
        ];
    }

    /** Relationen account, category, payee und tags sollten geladen sein. */
    public static function transaction(Transaction $transaction): array
    {
        $amount = round((float) $transaction->amount, 2);

        return [
            'id' => $transaction->id,
            'date' => $transaction->transaction_date->toDateString(),
            'description' => $transaction->description,
            'merchant' => $transaction->merchant,
            'amount' => $transaction->type === 'income' ? $amount : -$amount,
            'type' => $transaction->type,
            'account' => $transaction->account ? ['id' => $transaction->account->id, 'name' => $transaction->account->name] : null,
            'transfer_account' => $transaction->transferAccount
                ? ['id' => $transaction->transferAccount->id, 'name' => $transaction->transferAccount->name]
                : null,
            'category' => $transaction->category ? [
                'id' => $transaction->category->id,
                'name' => $transaction->category->name,
                'icon' => $transaction->category->icon,
                'color' => $transaction->category->color,
            ] : null,
            'payee' => $transaction->payee ? ['id' => $transaction->payee->id, 'name' => $transaction->payee->name] : null,
            'tags' => $transaction->tags->map(fn ($tag) => ['id' => $tag->id, 'name' => $tag->name])->values()->all(),
            'notes' => $transaction->notes,
        ];
    }

    public static function loadTransaction(Transaction $transaction): Transaction
    {
        return $transaction->load(['account:id,name', 'transferAccount:id,name', 'category', 'payee:id,name', 'tags:id,name']);
    }
}
