<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE nms_metrics MODIFY COLUMN metric_type ENUM(
            'sfp_rx_power',
            'sfp_tx_power',
            'link_status',
            'interface_rx_rate',
            'interface_tx_rate',
            'sfp_temperature',
            'sfp_voltage',
            'sfp_tx_bias',
            'onu_count'
        ) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE nms_metrics MODIFY COLUMN metric_type ENUM(
            'sfp_rx_power',
            'sfp_tx_power',
            'link_status',
            'interface_rx_rate',
            'interface_tx_rate'
        ) NOT NULL");
    }
};
