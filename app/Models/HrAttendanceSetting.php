<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HrAttendanceSetting extends Model
{
    protected $table = 'hr_attendance_setting';

    protected $guarded = ['id'];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'radius_meter' => 'integer',
        'enforce' => 'boolean',
    ];
}
