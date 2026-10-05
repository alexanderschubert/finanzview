<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayeeAlias extends Model
{
    protected $fillable = [
        'payee_id',
        'user_id',
        'alias',
        'alias_key',
    ];

    public function payee(): BelongsTo
    {
        return $this->belongsTo(Payee::class);
    }
}
