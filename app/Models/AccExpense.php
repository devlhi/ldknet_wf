<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccExpense extends Model
{
    protected $table = 'acc_expenses';

    protected $guarded = ['id'];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function contact(): BelongsTo
    {
        return $this->belongsTo(AccContact::class, 'contact_id');
    }

    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(AccAccount::class, 'expense_account_id');
    }

    public function paymentAccount(): BelongsTo
    {
        return $this->belongsTo(AccAccount::class, 'payment_account_id');
    }
}
