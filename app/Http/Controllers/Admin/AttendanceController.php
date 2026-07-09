<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HrAttendance;
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

    public function employees()
    {
        $employees = User::where('level', 'technician')->orderBy('nama')->get();

        return view('admin.absensi.karyawan', [
            'title' => 'Data Karyawan',
            'employees' => $employees,
            'password' => random(5),
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

        $rows = $this->reportQuery($start, $end, $userId, $status)->get();

        $summary = [
            'hadir' => $rows->where('status', 'hadir')->count(),
            'izin' => $rows->where('status', 'izin')->count(),
            'sakit' => $rows->where('status', 'sakit')->count(),
            'alpha' => $rows->where('status', 'alpha')->count(),
            'cuti' => $rows->where('status', 'cuti')->count(),
        ];

        return view('admin.absensi.rekap', [
            'title' => 'Rekap Absensi',
            'rows' => $rows,
            'summary' => $summary,
            'employees' => User::where('level', 'technician')->orderBy('nama')->get(),
            'filters' => compact('start', 'end', 'userId', 'status'),
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
