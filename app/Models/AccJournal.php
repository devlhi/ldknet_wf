<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccJournal extends Model
{
    protected $table = 'acc_journals';

    protected $guarded = ['id'];

    protected $casts = [
        'date' => 'date',
        'total' => 'decimal:2',
        'is_posted' => 'boolean',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(AccJournalLine::class, 'journal_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(AccContact::class, 'contact_id');
    }
}
