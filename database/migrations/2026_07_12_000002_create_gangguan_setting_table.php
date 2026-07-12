<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('gangguan_setting')) {
            return;
        }

        Schema::create('gangguan_setting', function (Blueprint $table) {
            $table->id();
            // Balasan otomatis ke pelanggan saat laporan gangguan masuk.
            $table->boolean('auto_reply_enabled')->default(true);
            $table->text('auto_reply_text')->nullable();
            // SLA: batas jam sebuah laporan 'baru' dianggap terlambat ditangani.
            $table->unsignedSmallInteger('sla_response_hours')->default(3);
            // Deteksi gangguan massal: minimal laporan dari ODP sama dalam N jam.
            $table->unsignedSmallInteger('massal_threshold')->default(3);
            $table->unsignedSmallInteger('massal_window_hours')->default(6);
            // Teks broadcast pemberitahuan gangguan massal ke pelanggan ODP terdampak.
            $table->text('massal_broadcast_text')->nullable();
            $table->timestamps();
        });

        DB::table('gangguan_setting')->insert([
            'auto_reply_enabled' => true,
            'auto_reply_text' => "Halo{nama} 🙏\nLaporan kendala Anda ({kategori}) sudah kami terima dan akan segera ditindaklanjuti oleh tim teknis kami.\n\nMohon ditunggu ya, terima kasih atas laporannya. 🛠️",
            'sla_response_hours' => 3,
            'massal_threshold' => 3,
            'massal_window_hours' => 6,
            'massal_broadcast_text' => "Pemberitahuan 📢\nSaat ini sedang terjadi gangguan jaringan di area Anda ({odp}). Tim teknis kami sudah menangani perbaikan.\n\nMohon maaf atas ketidaknyamanannya, koneksi akan segera normal kembali. Terima kasih atas kesabarannya. 🙏",
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('gangguan_setting');
    }
};
