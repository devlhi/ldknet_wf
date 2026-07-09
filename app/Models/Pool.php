<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pool extends Model
{
    protected $table = 'pool';

    public $timestamps = false;

    protected $guarded = ['id'];
}
