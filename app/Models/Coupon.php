<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $table = 'coupon';

    public $timestamps = false;

    protected $guarded = ['id'];
}
