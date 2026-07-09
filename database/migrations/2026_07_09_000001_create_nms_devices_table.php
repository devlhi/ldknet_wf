<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nms_devices', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->enum('tipe', ['mikrotik', 'crs', 'olt', 'snmp']);
            $table->string('ip', 100);
            $table->integer('port')->default(8728);
            $table->string('username', 100)->nullable();
            $table->string('password', 255)->nullable();
            $table->string('community', 100)->nullable();
            $table->string('latitude', 50)->nullable();
            $table->string('longitude', 50)->nullable();
            $table->string('lokasi', 255)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nms_devices');
    }
};
