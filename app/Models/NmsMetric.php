<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NmsMetric extends Model
{
    public $timestamps = false;

    protected $table = 'nms_metrics';

    protected $guarded = ['id'];

    public function device(): BelongsTo
    {
        return $this->belongsTo(NmsDevice::class, 'device_id');
    }
}
