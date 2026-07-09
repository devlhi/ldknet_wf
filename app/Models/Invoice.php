<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $table = 'invoice';

    public $timestamps = false;

    protected $guarded = ['id'];

    public function order()
    {
        return $this->belongsTo(Order::class, 'idpel', 'idpel');
    }
}
