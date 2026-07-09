<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Member extends Model
{
    protected $table = 'member';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $casts = [
        'date' => 'date',
        'expdate' => 'date',
    ];

    public function router()
    {
        return $this->belongsTo(Router::class, 'id_router');
    }
}
