<?php

namespace App\Models;

use App\Support\OdpAssignment;
use App\Support\WhatsAppNotifier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class GangguanReport extends Model
{
    protected $table = 'gangguan_reports';

    protected $guarded = ['id'];

    protected $casts = [
        'resolved_at' => 'datetime',
        'responded_at' => 'datetime',
        'auto_reply_sent' => 'boolean',
    ];

    public const STATUSES = ['baru', 'diproses', 'selesai'];

    /**
     * Peta kata kunci -> kategori gangguan (urut dari paling spesifik).
     * Cocokkan substring pada teks yang sudah di-lowercase.
     */
    public const KATEGORI = [
        'internet_mati' => ['mati', 'putus', 'tidak konek', 'ga konek', 'gak konek', 'nggak konek', 'gak nyambung', 'tidak nyambung', 'no internet', 'offline', 'disconnect', 'los', 'lampu merah', 'redaman'],
        'internet_lambat' => ['lambat', 'lemot', 'lelet', 'lola', 'lag', 'ngelag', 'buffering', 'loading lama', 'lambat sekali', 'lambat banget', 'kecepatan turun', 'lambat bgt'],
        'tidak_bisa_akses' => ['tidak bisa buka', 'ga bisa buka', 'gak bisa buka', 'tidak bisa browsing', 'ga bisa browsing', 'tidak bisa youtube', 'tidak bisa game', 'error', 'dns', 'tidak bisa akses'],
        'wifi' => ['ganti password', 'password wifi', 'sinyal lemah', 'jangkauan wifi', 'router panas', 'wifi tidak bisa', 'tidak bisa wifi', 'wifi lemot', 'wifi lambat', 'wifi mati', 'wifi putus', 'wifi bermasalah', 'wifi rusak'],
        'pembayaran' => ['sudah bayar', 'sudah transfer', 'konfirmasi bayar', 'belum aktif padahal sudah bayar', 'kok belum aktif'],
    ];

    /** Kata kunci umum keluhan -> masuk kategori "lainnya" bila tak cocok kategori spesifik. */
    public const KELUHAN_UMUM = ['gangguan', 'komplain', 'keluhan', 'rusak', 'bermasalah', 'trouble', 'minta bantuan', 'tolong dicek', 'tolong cek', 'kenapa internet', 'kok internet', 'internet saya', 'wifi saya', 'gimana ini', 'perbaiki'];

    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    /**
     * Klasifikasi teks jadi kategori gangguan. Return null bila teks tidak
     * terindikasi laporan gangguan (mis. ucapan terima kasih / chat biasa).
     */
    public static function classify(string $text): ?string
    {
        $t = mb_strtolower(trim($text));
        if ($t === '') {
            return null;
        }

        // Cek jika teks tidak mengandung karakter huruf sama sekali (misal hanya emoji/symbol/gibberish)
        if (! preg_match('/\p{L}/u', $t)) {
            return null;
        }

        // Jangan respon/buat laporan jika pesan hanya berupa "wifi" atau "wi-fi" saja
        if ($t === 'wifi' || $t === 'wi-fi') {
            return null;
        }

        // Cocokkan dgn batas kata (bukan sekadar substring) supaya kata kunci
        // pendek tidak salah cocok: "lag" tidak cocok "lagi", "mati" tidak cocok
        // "matikan", dsb.
        $matches = fn (string $kw): bool => (bool) preg_match('/(?<![\p{L}])'.preg_quote($kw, '/').'(?![\p{L}])/u', $t);

        foreach (self::KATEGORI as $kategori => $keywords) {
            foreach ($keywords as $kw) {
                if ($matches($kw)) {
                    return $kategori;
                }
            }
        }

        foreach (self::KELUHAN_UMUM as $kw) {
            if ($matches($kw)) {
                return 'lainnya';
            }
        }

        return null;
    }

    /**
     * Serap pesan masuk jadi laporan gangguan bila terindikasi keluhan.
     * Aman dipanggil dari webhook: dibungkus try/catch agar TIDAK pernah
     * menggagalkan pemrosesan pesan. Dedup: lewati bila sudah ada laporan
     * terbuka (baru/diproses) dari nomor sama dalam 12 jam terakhir.
     */
    public static function capture(string $fromNumber, ?string $fromName, string $text, string $gateway): void
    {
        try {
            $kategori = self::classify($text);
            if ($kategori === null) {
                return;
            }

            $number = self::normalizeNumber($fromNumber);
            if ($number === '') {
                return;
            }

            $sudahAda = self::where('from_number', $number)
                ->whereIn('status', ['baru', 'diproses'])
                ->where('created_at', '>=', now()->subHours(12))
                ->exists();
            if ($sudahAda) {
                return;
            }

            // Cocokkan ke pelanggan (orders) via nomor (bandingkan bentuk 62 & 08).
            $local = '0'.substr($number, 2);
            $order = Order::where('nomor', $number)->orWhere('nomor', $local)->first();

            $report = self::create([
                'from_number' => $number,
                'from_name' => $fromName ?: ($order->nama ?? null),
                'idpel' => $order->idpel ?? null,
                'nama_odp' => $order->nama_odp ?? null,
                'kode_area' => $order->kode_area ?? null,
                'gateway' => $gateway,
                'kategori' => $kategori,
                'pesan' => mb_substr($text, 0, 1000),
                'status' => 'baru',
            ]);

            self::sendAutoReply($report);
        } catch (\Throwable $e) {
            Log::warning('Gagal menyerap laporan gangguan: '.$e->getMessage());
        }
    }

    /**
     * Balas otomatis ke pelanggan bahwa laporannya diterima. Dibungkus try/catch
     * sendiri: kegagalan kirim TIDAK boleh menggagalkan pencatatan laporan.
     * Dikirim via gateway aktif (Meta/lama) — pelanggan baru saja chat jadi masih
     * dalam window 24 jam Meta (pesan sesi bebas diperbolehkan).
     */
    protected static function sendAutoReply(self $report): void
    {
        try {
            $setting = GangguanSetting::current();
            if (! $setting->auto_reply_enabled) {
                return;
            }

            $template = trim((string) $setting->auto_reply_text);
            if ($template === '') {
                return;
            }

            $nama = trim((string) $report->from_name);
            $message = strtr($template, [
                // {nama} diawali spasi bila ada nama, kosong bila anonim (biar "Halo 🙏" tetap rapi).
                '{nama}' => $nama !== '' ? ' '.$nama : '',
                '{kategori}' => self::kategoriLabel($report->kategori),
            ]);

            WhatsAppNotifier::sendText($report->from_number, $message);

            $report->auto_reply_sent = true;
            $report->save();
        } catch (\Throwable $e) {
            Log::warning('Gagal kirim balasan otomatis laporan gangguan: '.$e->getMessage());
        }
    }

    /** Samakan format nomor: 08xxx -> 62xxx, buang non-digit & suffix WA. */
    public static function normalizeNumber(string $number): string
    {
        $number = preg_replace('/\D+/', '', $number);

        return str_starts_with($number, '0') ? '62'.substr($number, 1) : $number;
    }

    public static function kategoriLabel(string $kategori): string
    {
        return match ($kategori) {
            'internet_mati' => 'Internet Mati/Putus',
            'internet_lambat' => 'Internet Lambat',
            'tidak_bisa_akses' => 'Tidak Bisa Akses',
            'wifi' => 'WiFi/Perangkat',
            'pembayaran' => 'Terkait Pembayaran',
            default => 'Lainnya',
        };
    }

    /** Format durasi menit -> "2j 15m" / "45m" / "-" untuk tampilan SLA. */
    public static function humanDuration(?float $minutes): string
    {
        if ($minutes === null) {
            return '-';
        }
        $minutes = (int) round($minutes);
        if ($minutes < 60) {
            return $minutes.'m';
        }
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        return $m > 0 ? "{$h}j {$m}m" : "{$h}j";
    }

    /**
     * Deteksi kemungkinan gangguan massal: kelompokkan laporan terbuka
     * (baru/diproses) per ODP dalam rentang $windowHours jam terakhir; ODP dengan
     * jumlah laporan >= $threshold ditandai sebagai indikasi gangguan massal.
     * Return Collection stdClass: {nama_odp, total, latitude, longitude, pelanggan_aktif}.
     */
    public static function massalAlerts(int $threshold, int $windowHours): Collection
    {
        $threshold = max(2, $threshold);
        $windowHours = max(1, $windowHours);

        $odps = Odp::query()->get(['id', 'nama', 'latitude', 'longitude']);
        $odpByAssignment = OdpAssignment::uniqueByStoredName($odps);
        $reports = self::query()
            ->whereIn('status', ['baru', 'diproses'])
            ->whereNotNull('nama_odp')
            ->where('nama_odp', '!=', '')
            ->where('created_at', '>=', now()->subHours($windowHours))
            ->get(['nama_odp', 'created_at']);
        $reportsByOdpId = $reports
            ->groupBy(fn (self $report) => OdpAssignment::resolve($odps, $report->nama_odp)?->id)
            ->filter(fn (Collection $group, $odpId) => $odpId !== null && $odpId !== '' && $group->count() >= $threshold);

        if ($reportsByOdpId->isEmpty()) {
            return collect();
        }

        $customersByOdpId = Order::query()
            ->where('status', 'Active')
            ->whereNotNull('nama_odp')
            ->where('nama_odp', '!=', '')
            ->get(['nama_odp'])
            ->groupBy(fn (Order $order) => $odpByAssignment->get(OdpAssignment::key($order->nama_odp))?->id)
            ->map->count();

        return $reportsByOdpId->map(function (Collection $group, $odpId) use ($odps, $customersByOdpId) {
            $odp = $odps->firstWhere('id', (int) $odpId);

            return (object) [
                'odp_id' => $odp->id,
                'nama_odp' => $odp->nama,
                'total' => $group->count(),
                'last_at' => $group->max('created_at'),
                'latitude' => $odp->latitude,
                'longitude' => $odp->longitude,
                'pelanggan_aktif' => (int) ($customersByOdpId[$odp->id] ?? 0),
            ];
        })->sortByDesc('total')->values();
    }
}
