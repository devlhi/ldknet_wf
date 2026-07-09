<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccContact extends Model
{
    protected $table = 'acc_contacts';

    protected $guarded = ['id'];

    protected $casts = [
        'is_active' => 'boolean',
        'opening_balance' => 'decimal:2',
    ];
}
