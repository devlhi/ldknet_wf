<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HrAttendance;
use App\Models\HrAttendanceSetting;
use App\Models\User;
use App\Models\Website;
use App\Services\NewAccountMailer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    public function employees(Request $request)
    {
        $showData = $request->boolean('show_data');
        $employees = $showData ? User::where('level', 'technician')->orderBy('nama')->get() : collect();

        return view('admin.absensi.karyawan', [
            'title' => 'Data Karyawan',
            'employees' => $employees,
            'password' => random(5),
            'showData' => $showData,
        ] + $this->websiteData());
    }

    public function storeEmployee(Request $request, NewAccountMailer $mailer)
    {
        try {
            $data = $request->validate([
                'nama' => 'required|string|max:50',
                'email' => 'required|email|max:40|unique:users,email',
                'nomor' => 'required|string|max:15',
                'password' => 'required|string|min:4',
            ], [
                'email.unique' => 'Email sudah terdaftar',
            ]);
        } catch (ValidationException $e) {
            return redirect('admin/karyawan')
                ->with('auth_errors', array_merge(...array_values($e->errors())));
        }

        User::create([
            'email' => $data['email'],
            'nama' => $data['nama'],
            'nomor' => $data['nomor'],
            'password' => Hash::make($data['password']),
            'level' => 'technician',
            'verify_account' => '1',
            'status_account' => 'Active',
        ]);

        try {
            $mailer->sendAccountInfo($data['nama'], $data['email'], $data['password']);
        } catch (\Throwable $e) {
            Log::warning('Gagal kirim email akun karyawan: '.$e->getMessage());
        }

        return redirect('admin/karyawan')->with('success', ['Berhasil menambahkan karyawan baru']);
    }

    public function resetPassword(Request $request, $id)
    {
        try {
            $request->validate(['password' => 'required|string|min:4']);
        } catch (ValidationException $e) {
            return redirect('admin/karyawan')
                ->with('auth_errors', array_merge(...array_values($e->errors())));
        }

        $employee = User::where('id', $id)->where('level', 'technician')->first();

        if (! $employee) {
            return redirect('admin/karyawan')->with('auth_errors', ['Karyawan tidak ditemukan']);
        }

        $employee->update(['password' => Hash::make((string) $request->input('password'))]);

        return redirect('admin/karyawan')->with('success', ['Password karyawan berhasil diperbarui']);
    }

    public function toggleEmployee($id)
    {
        $employee = User::where('id', $id)->where('level', 'technician')->first();

        if (! $employee) {
            return redirect('admin/karyawan')->with('auth_errors', ['Karyawan tidak ditemukan']);
        }

        $employee->update([
            'status_account' => $employee->status_account === 'Active' ? 'Non Active' : 'Active',
        ]);

        return redirect('admin/karyawan')->with('success', ['Status karyawan berhasil diperbarui']);
    }

    public function settings()
    {
        return view('admin.absensi.settings', [
            'title' => 'Pengaturan Radius Absensi',
            'setting' => HrAttendanceSetting::first(),
        ] + $this->websiteData());
    }

    public function updateSettings(Request $request)
    {
        try {
            $data = $request->validate([
                'label' => 'nullable|string|max:100',
                'latitude' => 'required|numeric|between:-90,90',
                'longitude' => 'required|numeric|between:-180,180',
                'radius_meter' => 'required|integer|min:10|max:5000',
            ], [
                'latitude.required' => 'Klik titik lokasi di peta terlebih dahulu',
                'longitude.required' => 'Klik titik lokasi di peta terlebih dahulu',
            ]);
        } catch (ValidationException $e) {
            return redirect('admin/absensi/pengaturan')
                ->with('auth_errors', array_merge(...array_values($e->errors())));
        }

        $setting = HrAttendanceSetting::first() ?? new HrAttendanceSetting;
        $setting->fill([
            'label' => $data['label'] ?? null,
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'radius_meter' => $data['radius_meter'],
            'enforce' => $request->boolean('enforce'),
        ]);
        $setting->save();

        return redirect('admin/absensi/pengaturan')->with('success', ['Pengaturan radius absensi berhasil disimpan']);
    }

    private function reportFilters(Request $request): array
    {
        $wibNow = now()->setTimezone('Asia/Jakarta');
        $start = $request->input('start', $wibNow->copy()->startOfMonth()->format('Y-m-d'));
        $end = $request->input('end', $wibNow->format('Y-m-d'));
        $userId = $request->input('user_id');
        $status = $request->input('status');

        // Normalisasi tanggal agar aman dipakai di nama file & query.
        $start = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $start) ? $start : $wibNow->copy()->startOfMonth()->format('Y-m-d');
        $end = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $end) ? $end : $wibNow->format('Y-m-d');

        return [$start, $end, $userId, $status];
    }

    private function reportQuery(string $start, string $end, ?string $userId, ?string $status)
    {
        return HrAttendance::with('user')
            ->whereBetween('tanggal', [$start, $end])
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByDesc('tanggal')
            ->orderBy('user_id');
    }

    public function report(Request $request)
    {
        [$start, $end, $userId, $status] = $this->reportFilters($request);

        $showData = $request->boolean('show_data');
        $rows = $showData ? $this->reportQuery($start, $end, $userId, $status)->get() : collect();

        $summary = [
            'hadir' => $rows->where('status', 'hadir')->count(),
            'izin' => $rows->where('status', 'izin')->count(),
            'sakit' => $rows->where('status', 'sakit')->count(),
            'alpha' => $rows->where('status', 'alpha')->count(),
            'cuti' => $rows->where('status', 'cuti')->count(),
        ];

        // Ringkasan per karyawan pada rentang terpilih: jumlah tiap status +
        // persentase kehadiran, diurut dari yang paling banyak entri.
        $perEmployee = $rows->groupBy('user_id')->map(function ($items) {
            $total = $items->count();
            $hadir = $items->where('status', 'hadir')->count();

            return [
                'nama' => optional($items->first()->user)->nama ?? '-',
                'hadir' => $hadir,
                'izin' => $items->where('status', 'izin')->count(),
                'sakit' => $items->where('status', 'sakit')->count(),
                'alpha' => $items->where('status', 'alpha')->count(),
                'cuti' => $items->where('status', 'cuti')->count(),
                'total' => $total,
                'persen' => $total > 0 ? (int) round($hadir / $total * 100) : 0,
            ];
        })->sortByDesc('total')->values();

        return view('admin.absensi.rekap', [
            'title' => 'Rekap Absensi',
            'rows' => $rows,
            'summary' => $summary,
            'perEmployee' => $perEmployee,
            'employees' => User::where('level', 'technician')->orderBy('nama')->get(),
            'filters' => compact('start', 'end', 'userId', 'status'),
            'showData' => $showData,
        ] + $this->websiteData());
    }

    public function updateEntry(Request $request, $id)
    {
        $entry = HrAttendance::find($id);

        if (! $entry) {
            return redirect('admin/absensi/rekap')->with('auth_errors', ['Data absensi tidak ditemukan']);
        }

        try {
            $data = $request->validate([
                'status' => 'required|in:hadir,izin,sakit,alpha,cuti',
                'check_in' => 'nullable|date',
                'check_out' => 'nullable|date|after_or_equal:check_in',
                'keterangan' => 'nullable|string|max:255',
            ]);
        } catch (ValidationException $e) {
            return redirect('admin/absensi/rekap')
                ->with('auth_errors', array_merge(...array_values($e->errors())));
        }

        $entry->update([
            'status' => $data['status'],
            'check_in' => $data['check_in'] ?: null,
            'check_out' => $data['check_out'] ?: null,
            'keterangan' => $data['keterangan'] ?? null,
        ]);

        return redirect('admin/absensi/rekap')->with('success', ['Data absensi berhasil diperbarui']);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        [$start, $end, $userId, $status] = $this->reportFilters($request);

        $rows = $this->reportQuery($start, $end, $userId, $status)->get();

        $filename = 'rekap-absensi-'.$start.'-sd-'.$end.'.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Tanggal', 'Nama', 'Email', 'Status', 'Check In', 'Check Out', 'Keterangan']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    optional($row->tanggal)->format('Y-m-d'),
                    $this->csvSafe($row->user->nama ?? '-'),
                    $this->csvSafe($row->user->email ?? '-'),
                    $this->csvSafe($row->status),
                    optional($row->check_in)->format('Y-m-d H:i:s'),
                    optional($row->check_out)->format('Y-m-d H:i:s'),
                    $this->csvSafe($row->keterangan),
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * Cegah CSV/formula injection: nilai yang diawali = + - @ (atau tab/CR)
     * diberi tanda kutip di depan agar tidak dieksekusi Excel.
     */
    private function csvSafe(?string $value): string
    {
        $value = (string) $value;

        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'".$value;
        }

        return $value;
    }
}
