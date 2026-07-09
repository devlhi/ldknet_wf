<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nms_sla_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('nms_devices')->cascadeOnDelete();
            $table->enum('check_type', ['ping', 'interface'])->default('ping');
            $table->string('interface_name', 100)->nullable();
            $table->decimal('target_sla', 5, 2)->default(99.50);
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique('device_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nms_sla_settings');
    }
};
