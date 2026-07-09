<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccProduct extends Model
{
    protected $table = 'acc_products';

    protected $guarded = ['id'];

    protected $casts = [
        'sale_price' => 'decimal:2',
        'purchase_price' => 'decimal:2',
        'stock' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}
