<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccAsset extends Model
{
    protected $table = 'acc_assets';

    protected $guarded = ['id'];

    protected $casts = [
        'acquired_date' => 'date',
        'acquisition_cost' => 'decimal:2',
        'salvage_value' => 'decimal:2',
        'accumulated_depreciation' => 'decimal:2',
    ];

    public function depreciations(): HasMany
    {
        return $this->hasMany(AccAssetDepreciation::class, 'asset_id');
    }

    public function getBookValueAttribute(): float
    {
        return (float) $this->acquisition_cost - (float) $this->accumulated_depreciation;
    }

    public function getMonthlyDepreciationAttribute(): float
    {
        $months = max(1, (int) $this->useful_life_months);

        return ((float) $this->acquisition_cost - (float) $this->salvage_value) / $months;
    }
}
