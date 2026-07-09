<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Odp extends Model
{
    protected $table = 'odp';

    public $timestamps = false;

    protected $guarded = ['id'];
}
