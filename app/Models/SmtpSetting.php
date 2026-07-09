<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmtpSetting extends Model
{
    protected $table = 'smtp_setting';

    public $timestamps = false;

    protected $guarded = ['id'];
}
