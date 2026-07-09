<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Olt extends Model
{
    protected $table = 'olt';

    public $timestamps = false;

    protected $guarded = ['id'];
}
