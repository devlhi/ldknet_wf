<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccAccount extends Model
{
    protected $table = 'acc_accounts';

    protected $guarded = ['id'];

    protected $casts = [
        'is_cash' => 'boolean',
        'is_locked' => 'boolean',
        'is_active' => 'boolean',
        'opening_balance' => 'decimal:2',
    ];

    public const NORMAL_DEBIT = ['asset', 'expense'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(AccAccount::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(AccAccount::class, 'parent_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(AccJournalLine::class, 'account_id');
    }

    public function isDebitNormal(): bool
    {
        return in_array($this->type, self::NORMAL_DEBIT, true);
    }
}
