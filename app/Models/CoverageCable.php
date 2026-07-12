<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoverageCable extends Model
{
    protected $table = 'coverage_cables';

    protected $guarded = ['id'];

    protected $casts = [
        'path' => 'array',
    ];
}
