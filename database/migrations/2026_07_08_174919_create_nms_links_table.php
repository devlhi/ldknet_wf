<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Guard: skip bila tabel sudah ada. Catatan: bila migrasi ini pernah
        // GAGAL di tengah (tabel terbentuk tanpa foreign key), hapus dulu
        // tabel kosongnya: DROP TABLE nms_links; lalu jalankan ulang migrate.
        if (Schema::hasTable('nms_links')) {
            return;
        }

        Schema::create('nms_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_a_id')->constrained('nms_devices')->cascadeOnDelete();
            $table->foreignId('device_b_id')->constrained('nms_devices')->cascadeOnDelete();
            $table->string('port_a', 50)->nullable();
            $table->string('port_b', 50)->nullable();
            $table->string('label', 100)->nullable();
            $table->enum('link_type', ['fiber', 'wireless', 'copper'])->default('fiber');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nms_links');
    }
};
