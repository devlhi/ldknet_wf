<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccJournalLine extends Model
{
    protected $table = 'acc_journal_lines';

    protected $guarded = ['id'];

    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    public function journal(): BelongsTo
    {
        return $this->belongsTo(AccJournal::class, 'journal_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(AccAccount::class, 'account_id');
    }
}
