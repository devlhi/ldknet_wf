<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';

    public $timestamps = false;

    protected $guarded = ['id'];

    public function router()
    {
        return $this->belongsTo(Router::class, 'id_router');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'idpel', 'idpel');
    }
}
