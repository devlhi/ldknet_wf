<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccPurchaseBillItem extends Model
{
    protected $table = 'acc_purchase_bill_items';

    protected $guarded = ['id'];

    protected $casts = [
        'qty' => 'decimal:2',
        'price' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(AccPurchaseBill::class, 'bill_id');
    }
}
