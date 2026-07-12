<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoverageMapSetting extends Model
{
    protected $table = 'coverage_map_setting';

    protected $guarded = ['id'];

    protected $casts = [
        'hub_lat' => 'float',
        'hub_lng' => 'float',
        'center_lat' => 'float',
        'center_lng' => 'float',
        'zoom' => 'integer',
    ];

    public static function current(): self
    {
        try {
            return static::query()->first() ?? static::create(static::defaults());
        } catch (\Throwable $e) {
            return new static(static::defaults());
        }
    }

    public static function defaults(): array
    {
        return [
            'hub_label' => null,
            'hub_lat' => null,
            'hub_lng' => null,
            'center_lat' => 0.3,
            'center_lng' => 109.5,
            'zoom' => 11,
            'basemap' => 'streets',
        ];
    }
}
