<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NmsLink extends Model
{
    protected $table = 'nms_links';

    protected $fillable = [
        'device_a_id',
        'device_b_id',
        'port_a',
        'port_b',
        'label',
        'link_type',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
        'link_type' => 'string',
    ];

    public function deviceA()
    {
        return $this->belongsTo(NmsDevice::class, 'device_a_id');
    }

    public function deviceB()
    {
        return $this->belongsTo(NmsDevice::class, 'device_b_id');
    }
}
