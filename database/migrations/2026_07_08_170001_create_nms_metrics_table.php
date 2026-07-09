<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Guard: skip bila tabel sudah ada (DB legacy / rename file migrasi).
        if (Schema::hasTable('nms_metrics')) {
            return;
        }

        Schema::create('nms_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('nms_devices')->cascadeOnDelete();
            $table->string('port_name', 100);
            $table->enum('metric_type', [
                'sfp_rx_power',
                'sfp_tx_power',
                'link_status',
                'interface_rx_rate',
                'interface_tx_rate',
                'sfp_temperature',
                'sfp_voltage',
                'sfp_tx_bias',
                'onu_count',
            ]);
            $table->string('value', 50);
            $table->string('unit', 20);
            $table->timestamp('recorded_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nms_metrics');
    }
};
