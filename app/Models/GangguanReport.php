<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class GangguanReport extends Model
{
    protected $table = 'gangguan_reports';

    protected $guarded = ['id'];

    protected $casts = [
        'resolved_at' => 'datetime',
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
        'wifi' => ['wifi', 'wi-fi', 'ganti password', 'password wifi', 'sinyal lemah', 'jangkauan wifi', 'router panas'],
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

            self::create([
                'from_number' => $number,
                'from_name' => $fromName ?: ($order->nama ?? null),
                'idpel' => $order->idpel ?? null,
                'gateway' => $gateway,
                'kategori' => $kategori,
                'pesan' => mb_substr($text, 0, 1000),
                'status' => 'baru',
            ]);
        } catch (\Throwable $e) {
            Log::warning('Gagal menyerap laporan gangguan: '.$e->getMessage());
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
}
