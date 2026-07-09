<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Guard: skip bila enum sudah memuat 'ping_status' — berarti migrasi
        // 2026_07_08_181113 (enum final) sudah jalan lebih dulu di DB ini.
        // Tanpa guard, ALTER ini akan MENGHAPUS ping_status dan gagal
        // ("Data truncated") bila sudah ada baris ping_status.
        $column = DB::selectOne("SHOW COLUMNS FROM nms_metrics LIKE 'metric_type'");
        if ($column && str_contains($column->Type ?? '', 'ping_status')) {
            return;
        }

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
