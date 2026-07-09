<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Psb extends Model
{
    protected $table = 'psb';

    public $timestamps = false;

    protected $guarded = ['id'];
}
