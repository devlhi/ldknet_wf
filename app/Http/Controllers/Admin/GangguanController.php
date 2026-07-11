<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GangguanReport;
use App\Models\Website;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class GangguanController extends Controller
{
    private function websiteData(): array
    {
        $website = Website::first();

        return [
            'titletext' => $website->title ?? 'LandakNet',
            'logo' => $website->logo ?? '',
        ];
    }

    public function index(Request $request)
    {
        $bulan = (string) $request->input('bulan', now()->format('Y-m'));
        if (! preg_match('/^\d{4}-\d{2}$/', $bulan)) {
            $bulan = now()->format('Y-m');
        }
        [$th, $bl] = array_map('intval', explode('-', $bulan));

        $status = $request->input('status');
        $kategori = $request->input('kategori');

        $base = fn () => GangguanReport::query()
            ->whereYear('created_at', $th)
            ->whereMonth('created_at', $bl);

        // Rekap seluruh bulan (independen dari filter status/kategori di daftar).
        $rekapKategori = $base()
            ->selectRaw('kategori, COUNT(*) as total')
            ->groupBy('kategori')
            ->orderByDesc('total')
            ->get();
        $totalBulan = $base()->count();
        $rekapStatus = $base()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $list = $base()
            ->with('handler')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($kategori, fn ($q) => $q->where('kategori', $kategori))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.gangguan.index', [
            'title' => 'Laporan Gangguan',
            'bulan' => $bulan,
            'statusFilter' => $status,
            'kategoriFilter' => $kategori,
            'rekapKategori' => $rekapKategori,
            'rekapStatus' => $rekapStatus,
            'totalBulan' => $totalBulan,
            'list' => $list,
        ] + $this->websiteData());
    }

    public function updateStatus(Request $request, $id)
    {
        $report = GangguanReport::find($id);
        if (! $report) {
            return redirect('admin/gangguan')->with('auth_errors', ['Laporan tidak ditemukan']);
        }

        try {
            $data = $request->validate([
                'status' => 'required|in:baru,diproses,selesai',
                'catatan' => 'nullable|string|max:500',
            ]);
        } catch (ValidationException $e) {
            return redirect('admin/gangguan')->with('auth_errors', array_merge(...array_values($e->errors())));
        }

        $report->status = $data['status'];
        $report->catatan = $data['catatan'] ?? $report->catatan;
        $report->handled_by = auth()->id();
        $report->resolved_at = $data['status'] === 'selesai' ? now() : null;
        $report->save();

        // Pakai field filter khusus (f_status/f_kategori) — JANGAN 'status' dari form
        // karena itu status baru laporan, bukan filter daftar.
        $filter = array_filter([
            'bulan' => $request->input('bulan'),
            'status' => $request->input('f_status'),
            'kategori' => $request->input('f_kategori'),
        ], fn ($v) => $v !== null && $v !== '');

        return redirect('admin/gangguan?'.http_build_query($filter))
            ->with('success', ['Status laporan gangguan diperbarui']);
    }
}
