<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NmsDevice extends Model
{
    protected $table = 'nms_devices';

    protected $guarded = ['id'];

    protected $hidden = ['password'];

    public function metrics(): HasMany
    {
        return $this->hasMany(NmsMetric::class, 'device_id');
    }

    public function linksAsA()
    {
        return $this->hasMany(NmsLink::class, 'device_a_id');
    }

    public function linksAsB()
    {
        return $this->hasMany(NmsLink::class, 'device_b_id');
    }
}
