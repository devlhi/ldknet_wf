<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentGateway extends Model
{
    protected $table = 'payment_gateway';

    public $timestamps = false;

    protected $guarded = ['id'];
}
