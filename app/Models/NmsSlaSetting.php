<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NmsSlaSetting extends Model
{
    protected $table = 'nms_sla_settings';

    protected $fillable = [
        'device_id',
        'check_type',
        'interface_name',
        'target_sla',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'target_sla' => 'decimal:2',
        'interface_name' => 'array',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(NmsDevice::class, 'device_id');
    }
}
