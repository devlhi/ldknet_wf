<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Katkas extends Model
{
    protected $table = 'katkas';

    public $timestamps = false;

    protected $guarded = ['id'];
}
