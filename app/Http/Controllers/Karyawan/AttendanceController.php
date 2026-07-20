<?php

namespace App\Http\Controllers\Karyawan;

use App\Http\Controllers\Controller;
use App\Models\HrAttendance;
use App\Models\HrAttendanceSetting;
use App\Models\Website;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AttendanceController extends Controller
{
    private function websiteData(): array
    {
        $website = Website::first();

        return [
            'titletext' => $website->title ?? 'LandakNet',
            'logo' => $website->logo ?? '',
        ];
    }

    /**
     * Waktu WIB (Asia/Jakarta). App timezone default UTC, jadi absensi
     * pakai zona lokal supaya tanggal & jam sesuai kondisi nyata.
     */
    private function nowWib(): Carbon
    {
        return now()->setTimezone('Asia/Jakarta');
    }

    public function index()
    {
        $today = $this->nowWib()->format('Y-m-d');
        $attendance = HrAttendance::where('user_id', auth()->id())
            ->whereDate('tanggal', $today)
            ->first();
        $setting = HrAttendanceSetting::first();

        return view('karyawan.absensi', [
            'title' => 'Absensi',
            'attendance' => $attendance,
            'today' => $today,
            'attendanceSetting' => [
                'enforce' => (bool) ($setting->enforce ?? false),
                'latitude' => $setting->latitude ?? null,
                'longitude' => $setting->longitude ?? null,
                'radius_meter' => $setting->radius_meter ?? null,
                'label' => $setting->label ?? null,
            ],
        ] + $this->websiteData());
    }

    /**
     * Jarak antar 2 titik koordinat dalam meter (formula Haversine).
     */
    private function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Validasi lokasi absen terhadap radius yang dikonfigurasi admin.
     * Return null bila boleh lanjut (radius OK, atau enforcement nonaktif/belum
     * dikonfigurasi -> fail-open supaya instalasi lama tidak mendadak terkunci),
     * atau pesan error bila ditolak.
     */
    private function checkRadius(?string $lat, ?string $lng): ?string
    {
        $setting = HrAttendanceSetting::first();

        if (! $setting || ! $setting->enforce || $setting->latitude === null || $setting->longitude === null) {
            return null;
        }

        if ($lat === null || $lat === '' || $lng === null || $lng === '') {
            return 'Lokasi GPS tidak terdeteksi. Aktifkan izin lokasi di HP/browser Anda untuk bisa absen.';
        }

        $distance = $this->distanceMeters((float) $lat, (float) $lng, (float) $setting->latitude, (float) $setting->longitude);

        if ($distance > $setting->radius_meter) {
            $lokasi = $setting->label ? " ({$setting->label})" : '';

            return sprintf(
                'Anda berada %s meter dari lokasi absen%s (radius maksimal %s meter). Absen ditolak.',
                number_format($distance, 0, ',', '.'),
                $lokasi,
                number_format((float) $setting->radius_meter, 0, ',', '.')
            );
        }

        return null;
    }

    public function checkIn(Request $request)
    {
        $today = $this->nowWib()->format('Y-m-d');

        $existing = HrAttendance::where('user_id', auth()->id())
            ->whereDate('tanggal', $today)
            ->first();

        if ($existing) {
            return redirect('karyawan/absensi')->with('auth_errors', ['Anda sudah absen hari ini']);
        }

        try {
            $data = $request->validate([
                'status' => 'required|in:hadir,izin,sakit,cuti',
                'lat' => 'nullable|string|max:30',
                'lng' => 'nullable|string|max:30',
                'keterangan' => 'nullable|string|max:255',
                'foto' => 'nullable|image|max:2048',
            ]);
        } catch (ValidationException $e) {
            return redirect('karyawan/absensi')
                ->with('auth_errors', array_merge(...array_values($e->errors())));
        }

        // Hanya status "hadir" yang mencatat jam check-in, GPS, dan foto.
        // Izin/sakit/cuti cukup mencatat status tanpa check-in (tidak perlu lokasi).
        $isPresent = $data['status'] === 'hadir';

        if ($isPresent && ($err = $this->checkRadius($data['lat'] ?? null, $data['lng'] ?? null))) {
            return redirect('karyawan/absensi')->with('auth_errors', [$err]);
        }

        $foto = $isPresent ? $this->storePhoto($request, 'in') : null;

        HrAttendance::create([
            'user_id' => auth()->id(),
            'tanggal' => $today,
            'check_in' => $isPresent ? $this->nowWib() : null,
            'status' => $data['status'],
            'check_in_lat' => $isPresent ? ($data['lat'] ?? null) : null,
            'check_in_lng' => $isPresent ? ($data['lng'] ?? null) : null,
            'keterangan' => $data['keterangan'] ?? null,
            'foto_in' => $foto,
        ]);

        return redirect('karyawan/absensi')->with('success', ['Berhasil menyimpan absensi']);
    }

    public function checkOut(Request $request)
    {
        $today = $this->nowWib()->format('Y-m-d');

        $attendance = HrAttendance::where('user_id', auth()->id())
            ->whereDate('tanggal', $today)
            ->first();

        if (! $attendance || ! $attendance->check_in || $attendance->status !== 'hadir') {
            return redirect('karyawan/absensi')->with('auth_errors', ['Anda belum check-in hadir hari ini']);
        }

        if ($attendance->check_out) {
            return redirect('karyawan/absensi')->with('auth_errors', ['Anda sudah check-out hari ini']);
        }

        try {
            $data = $request->validate([
                'lat' => 'nullable|string|max:30',
                'lng' => 'nullable|string|max:30',
                'foto' => 'nullable|image|max:2048',
            ]);
        } catch (ValidationException $e) {
            return redirect('karyawan/absensi')
                ->with('auth_errors', array_merge(...array_values($e->errors())));
        }

        if ($err = $this->checkRadius($data['lat'] ?? null, $data['lng'] ?? null)) {
            return redirect('karyawan/absensi')->with('auth_errors', [$err]);
        }

        $attendance->update([
            'check_out' => $this->nowWib(),
            'check_out_lat' => $data['lat'] ?? null,
            'check_out_lng' => $data['lng'] ?? null,
            'foto_out' => $this->storePhoto($request, 'out') ?? $attendance->foto_out,
        ]);

        return redirect('karyawan/absensi')->with('success', ['Berhasil check-out']);
    }

    public function history(Request $request)
    {
        $rows = HrAttendance::where('user_id', auth()->id())
            ->orderByDesc('tanggal')
            ->limit(60)
            ->get();

        return view('karyawan.history', [
            'title' => 'Riwayat Absensi',
            'rows' => $rows,
        ] + $this->websiteData());
    }

    private function storePhoto(Request $request, string $suffix): ?string
    {
        if (! $request->hasFile('foto')) {
            return null;
        }

        $file = $request->file('foto');

        // Ekstensi diturunkan dari file yang tervalidasi (bukan nama asli klien).
        $ext = $file->extension() ?: 'jpg';
        $filename = 'absen-'.auth()->id().'-'.$suffix.'-'.$this->nowWib()->format('ymdHis').'-'.Str::random(8).'.'.$ext;

        try {
            $file->move(public_path('data/absensi'), $filename);
        } catch (\Throwable $e) {
            Log::warning('Gagal simpan foto absensi: '.$e->getMessage());

            return null;
        }

        return $filename;
    }
}
