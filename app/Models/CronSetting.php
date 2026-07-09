<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CronSetting extends Model
{
    protected $table = 'cron_setting';

    public $timestamps = false;

    protected $guarded = ['id'];
}
