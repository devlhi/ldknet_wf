<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccPurchaseBill extends Model
{
    protected $table = 'acc_purchase_bills';

    protected $guarded = ['id'];

    protected $casts = [
        'date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'paid' => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(AccPurchaseBillItem::class, 'bill_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(AccContact::class, 'contact_id');
    }

    public function getOutstandingAttribute(): float
    {
        return (float) $this->total - (float) $this->paid;
    }
}
