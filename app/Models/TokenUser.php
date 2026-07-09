<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TokenUser extends Model
{
    protected $table = 'token_user';

    public $timestamps = false;

    protected $guarded = ['id'];
}
