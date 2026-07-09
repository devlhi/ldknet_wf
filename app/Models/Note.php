<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    protected $table = 'note';

    public $timestamps = false;

    protected $guarded = ['id'];
}
