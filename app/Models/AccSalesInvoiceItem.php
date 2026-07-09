<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccSalesInvoiceItem extends Model
{
    protected $table = 'acc_sales_invoice_items';

    protected $guarded = ['id'];

    protected $casts = [
        'qty' => 'decimal:2',
        'price' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(AccSalesInvoice::class, 'invoice_id');
    }
}
