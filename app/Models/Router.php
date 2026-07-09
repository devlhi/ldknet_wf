<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Router extends Model
{
    protected $table = 'router';

    public $timestamps = false;

    protected $guarded = ['id'];

    protected $hidden = [
        'password',
    ];
}
