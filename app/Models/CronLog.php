<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class CronLog extends Model
{
    protected $table = 'cron_log';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    /**
     * Jalankan task cron sambil mencatat mulai/selesai/error ke tabel log.
     * Output echo dari task legacy (mis. cetakinv) ditangkap via output buffer.
     */
    public static function run(string $task, callable $fn): void
    {
        $log = self::create([
            'task' => $task,
            'status' => 'running',
            'started_at' => now(),
        ]);

        $obLevel = ob_get_level();

        try {
            ob_start();
            $fn();
            $output = trim(strip_tags(str_replace('<br/>', "\n", (string) ob_get_clean())));

            $log->update([
                'status' => 'success',
                'finished_at' => now(),
                'message' => $output !== '' ? Str::limit($output, 2000) : null,
            ]);
        } catch (\Throwable $e) {
            // Tutup hanya buffer yang dibuka di dalam run() — jangan sentuh buffer framework.
            while (ob_get_level() > $obLevel) {
                ob_end_clean();
            }

            $log->update([
                'status' => 'failed',
                'finished_at' => now(),
                'message' => Str::limit($e->getMessage(), 2000),
            ]);
        }

        // Bersihkan log lama agar tabel tidak membengkak (retensi 30 hari).
        self::where('started_at', '<', now()->subDays(30))->delete();
    }

    /**
     * Heartbeat scheduler — disentuh tiap schedule:run agar UI bisa menampilkan
     * apakah cron server benar-benar jalan. Pakai file (bukan cache/DB) supaya
     * tidak tergantung driver cache dan tidak menambah row tiap menit.
     */
    public static function touchHeartbeat(): void
    {
        @file_put_contents(storage_path('app/cron_heartbeat'), now()->toDateTimeString());
    }

    public static function lastHeartbeat(): ?Carbon
    {
        $path = storage_path('app/cron_heartbeat');

        if (! is_file($path)) {
            return null;
        }

        return Carbon::createFromTimestamp(filemtime($path))->setTimezone(config('app.timezone'));
    }
}
