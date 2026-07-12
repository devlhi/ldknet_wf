<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('gangguan_reports')) {
            return;
        }

        Schema::table('gangguan_reports', function (Blueprint $table) {
            // ODP/area pelanggan (di-resolve dari orders saat capture) untuk deteksi
            // gangguan massal per titik ODP / area.
            if (! Schema::hasColumn('gangguan_reports', 'nama_odp')) {
                $table->string('nama_odp', 191)->nullable()->after('idpel');
            }
            if (! Schema::hasColumn('gangguan_reports', 'kode_area')) {
                $table->string('kode_area', 50)->nullable()->after('nama_odp');
            }
            // Waktu respons pertama (status keluar dari 'baru') untuk hitung SLA.
            if (! Schema::hasColumn('gangguan_reports', 'responded_at')) {
                $table->dateTime('responded_at')->nullable()->after('resolved_at');
            }
            // Penanda balasan otomatis sudah dikirim ke pelanggan.
            if (! Schema::hasColumn('gangguan_reports', 'auto_reply_sent')) {
                $table->boolean('auto_reply_sent')->default(false)->after('responded_at');
            }
        });

        // Index bantu untuk query gangguan massal per ODP (hindari error bila sudah ada).
        try {
            Schema::table('gangguan_reports', function (Blueprint $table) {
                $table->index('nama_odp');
            });
        } catch (Throwable $e) {
            // index sudah ada / DB tidak mendukung — abaikan.
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('gangguan_reports')) {
            return;
        }

        Schema::table('gangguan_reports', function (Blueprint $table) {
            try {
                $table->dropIndex(['nama_odp']);
            } catch (Throwable $e) {
            }
            foreach (['nama_odp', 'kode_area', 'responded_at', 'auto_reply_sent'] as $col) {
                if (Schema::hasColumn('gangguan_reports', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
