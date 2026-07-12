<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GangguanSetting extends Model
{
    protected $table = 'gangguan_setting';

    protected $guarded = ['id'];

    protected $casts = [
        'auto_reply_enabled' => 'boolean',
        'sla_response_hours' => 'integer',
        'massal_threshold' => 'integer',
        'massal_window_hours' => 'integer',
    ];

    /**
     * Ambil baris setting tunggal (buat default bila belum ada / tabel baru dibuat).
     * Aman dipanggil dari webhook: gagal (tabel belum migrate) -> instance default in-memory.
     */
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
            'auto_reply_enabled' => true,
            'auto_reply_text' => "Halo{nama} 🙏\nLaporan kendala Anda ({kategori}) sudah kami terima dan akan segera ditindaklanjuti oleh tim teknis kami.\n\nMohon ditunggu ya, terima kasih atas laporannya. 🛠️",
            'sla_response_hours' => 3,
            'massal_threshold' => 3,
            'massal_window_hours' => 6,
            'massal_broadcast_text' => "Pemberitahuan 📢\nSaat ini sedang terjadi gangguan jaringan di area Anda ({odp}). Tim teknis kami sudah menangani perbaikan.\n\nMohon maaf atas ketidaknyamanannya, koneksi akan segera normal kembali. Terima kasih atas kesabarannya. 🙏",
        ];
    }
}
