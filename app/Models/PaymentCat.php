<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentCat extends Model
{
    protected $table = 'payment_cat';

    public $timestamps = false;

    protected $guarded = ['id'];
}
