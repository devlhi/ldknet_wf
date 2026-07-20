<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GangguanReport;
use App\Models\GangguanSetting;
use App\Models\Odp;
use App\Models\Order;
use App\Models\Website;
use App\Support\OdpAssignment;
use App\Support\WhatsAppNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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

    /**
     * Tentukan periode laporan (harian/mingguan/bulanan/tahunan) + rentang tanggalnya
     * dari input request. Dipakai bersama oleh halaman daftar & cetak PDF.
     */
    private function resolvePeriode(Request $request): array
    {
        $periode = (string) $request->input('periode', 'bulanan');
        if (! in_array($periode, ['harian', 'mingguan', 'bulanan', 'tahunan'], true)) {
            $periode = 'bulanan';
        }

        // Tanggal acuan diterima sebagai Y-m-d (bisa juga Y-m atau Y — dinormalkan).
        $raw = (string) $request->input('tanggal', '');
        $anchor = Carbon::now();
        if ($raw !== '') {
            try {
                if (preg_match('/^\d{4}$/', $raw)) {
                    $raw .= '-01-01';
                } elseif (preg_match('/^\d{4}-\d{2}$/', $raw)) {
                    $raw .= '-01';
                }
                $anchor = Carbon::parse($raw);
            } catch (\Throwable $e) {
                $anchor = Carbon::now();
            }
        }

        [$start, $end, $label] = match ($periode) {
            'harian' => [
                $anchor->copy()->startOfDay(),
                $anchor->copy()->endOfDay(),
                $anchor->translatedFormat('l, d F Y'),
            ],
            'mingguan' => [
                $anchor->copy()->startOfWeek(Carbon::MONDAY),
                $anchor->copy()->endOfWeek(Carbon::SUNDAY),
                'Minggu '.$anchor->copy()->startOfWeek(Carbon::MONDAY)->translatedFormat('d M').' – '.$anchor->copy()->endOfWeek(Carbon::SUNDAY)->translatedFormat('d M Y'),
            ],
            'tahunan' => [
                $anchor->copy()->startOfYear(),
                $anchor->copy()->endOfYear(),
                'Tahun '.$anchor->format('Y'),
            ],
            default => [
                $anchor->copy()->startOfMonth(),
                $anchor->copy()->endOfMonth(),
                $anchor->translatedFormat('F Y'),
            ],
        };

        return [
            'periode' => $periode,
            'anchor' => $anchor,
            'tanggal' => $anchor->format('Y-m-d'),
            'start' => $start,
            'end' => $end,
            'label' => $label,
        ];
    }

    /**
     * Rekap SLA + jumlah laporan untuk satu rentang tanggal.
     */
    private function rekapFor(Carbon $start, Carbon $end): array
    {
        $base = fn () => GangguanReport::query()->whereBetween('created_at', [$start, $end]);

        $totalPeriode = $base()->count();
        $rekapStatus = $base()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $rekapKategori = $base()->selectRaw('kategori, COUNT(*) as total')->groupBy('kategori')->orderByDesc('total')->get();

        $sla = $base()
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, responded_at)) as avg_respon')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as avg_selesai')
            ->first();

        return [
            'totalPeriode' => $totalPeriode,
            'rekapStatus' => $rekapStatus,
            'rekapKategori' => $rekapKategori,
            'avgRespon' => $sla->avg_respon !== null ? (float) $sla->avg_respon : null,
            'avgSelesai' => $sla->avg_selesai !== null ? (float) $sla->avg_selesai : null,
        ];
    }

    /**
     * Riwayat SLA per sub-periode (per jam / hari / bulan) dalam rentang, untuk
     * melihat tren gangguan & kecepatan penanganan dari waktu ke waktu.
     */
    private function slaBreakdown(Carbon $start, Carbon $end, string $periode): array
    {
        $fmt = match ($periode) {
            'harian' => '%H:00',
            'tahunan' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        $rows = GangguanReport::whereBetween('created_at', [$start, $end])
            ->selectRaw("DATE_FORMAT(created_at, '{$fmt}') as bucket")
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(status = 'selesai') as selesai")
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, responded_at)) as avg_respon')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as avg_selesai')
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get();

        return $rows->map(function ($r) use ($periode) {
            $label = match ($periode) {
                'harian' => $r->bucket,
                'tahunan' => Carbon::createFromFormat('Y-m', $r->bucket)->translatedFormat('F Y'),
                default => Carbon::createFromFormat('Y-m-d', $r->bucket)->translatedFormat('D, d M Y'),
            };

            return [
                'label' => $label,
                'total' => (int) $r->total,
                'selesai' => (int) $r->selesai,
                'avg_respon' => $r->avg_respon !== null ? (float) $r->avg_respon : null,
                'avg_selesai' => $r->avg_selesai !== null ? (float) $r->avg_selesai : null,
            ];
        })->all();
    }

    public function index(Request $request)
    {
        $p = $this->resolvePeriode($request);
        $setting = GangguanSetting::current();

        $status = $request->input('status');
        $kategori = $request->input('kategori');

        $rekap = $this->rekapFor($p['start'], $p['end']);
        $breakdown = $this->slaBreakdown($p['start'], $p['end'], $p['periode']);

        // Laporan terlambat & gangguan massal = kondisi "saat ini" (tidak terikat periode).
        $batasSla = now()->subHours(max(1, (int) $setting->sla_response_hours));
        $overdue = GangguanReport::where('status', 'baru')->whereNull('responded_at')->where('created_at', '<', $batasSla)->count();
        $massal = GangguanReport::massalAlerts((int) $setting->massal_threshold, (int) $setting->massal_window_hours);

        $listQuery = GangguanReport::query()
            ->whereBetween('created_at', [$p['start'], $p['end']])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($kategori, fn ($q) => $q->where('kategori', $kategori));

        $openFilteredCount = (clone $listQuery)
            ->whereIn('status', ['baru', 'diproses'])
            ->count();

        $list = $listQuery->with('handler')->orderByDesc('created_at')->paginate(20)->withQueryString();

        return view('admin.gangguan.index', [
            'title' => 'Laporan Gangguan',
            'periode' => $p['periode'],
            'tanggal' => $p['tanggal'],
            'periodeLabel' => $p['label'],
            'statusFilter' => $status,
            'kategoriFilter' => $kategori,
            'rekapKategori' => $rekap['rekapKategori'],
            'rekapStatus' => $rekap['rekapStatus'],
            'totalPeriode' => $rekap['totalPeriode'],
            'avgRespon' => $rekap['avgRespon'],
            'avgSelesai' => $rekap['avgSelesai'],
            'breakdown' => $breakdown,
            'overdue' => $overdue,
            'slaHours' => (int) $setting->sla_response_hours,
            'massal' => $massal,
            'list' => $list,
            'openFilteredCount' => $openFilteredCount,
        ] + $this->websiteData());
    }

    /**
     * Halaman cetak / export PDF (via print-to-PDF browser). Standalone, tanpa
     * layout admin, sudah dioptimalkan untuk kertas A4.
     */
    public function cetak(Request $request)
    {
        $p = $this->resolvePeriode($request);
        $rekap = $this->rekapFor($p['start'], $p['end']);
        $breakdown = $this->slaBreakdown($p['start'], $p['end'], $p['periode']);

        $status = $request->input('status');
        $kategori = $request->input('kategori');

        // Batasi baris riwayat pada PDF agar tidak membengkak (mis. periode tahunan).
        $maxRows = 1000;
        $listQuery = GangguanReport::query()
            ->whereBetween('created_at', [$p['start'], $p['end']])
            ->with('handler')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($kategori, fn ($q) => $q->where('kategori', $kategori))
            ->orderByDesc('created_at');
        $totalList = $listQuery->count();
        $list = $listQuery->limit($maxRows)->get();

        $website = Website::first();

        return view('admin.gangguan.cetak', [
            'periode' => $p['periode'],
            'periodeLabel' => $p['label'],
            'start' => $p['start'],
            'end' => $p['end'],
            'statusFilter' => $status,
            'kategoriFilter' => $kategori,
            'rekapKategori' => $rekap['rekapKategori'],
            'rekapStatus' => $rekap['rekapStatus'],
            'totalPeriode' => $rekap['totalPeriode'],
            'avgRespon' => $rekap['avgRespon'],
            'avgSelesai' => $rekap['avgSelesai'],
            'breakdown' => $breakdown,
            'list' => $list,
            'totalList' => $totalList,
            'maxRows' => $maxRows,
            'namaPerusahaan' => $website->title ?? 'LandakNet',
            'logo' => $website->logo ?? '',
        ]);
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

        // Catat waktu respons pertama saat laporan keluar dari status 'baru'.
        if ($data['status'] !== 'baru' && $report->responded_at === null) {
            $report->responded_at = now();
        }

        $report->status = $data['status'];
        $report->catatan = $data['catatan'] ?? $report->catatan;
        $report->handled_by = auth()->id();
        $report->resolved_at = $data['status'] === 'selesai' ? ($report->resolved_at ?? now()) : null;
        $report->save();

        // Pertahankan konteks periode + filter daftar setelah update (pakai field khusus).
        $filter = array_filter([
            'periode' => $request->input('periode'),
            'tanggal' => $request->input('tanggal'),
            'status' => $request->input('f_status'),
            'kategori' => $request->input('f_kategori'),
        ], fn ($v) => $v !== null && $v !== '');

        return redirect('admin/gangguan?'.http_build_query($filter))
            ->with('success', ['Status laporan gangguan diperbarui']);
    }

    public function bulkClose(Request $request)
    {
        try {
            $data = $request->validate([
                'close_all' => 'nullable|boolean',
                'ids' => 'required_unless:close_all,1|array|min:1',
                'ids.*' => 'integer|distinct',
                'catatan' => 'nullable|string|max:500',
                'periode' => 'nullable|in:harian,mingguan,bulanan,tahunan',
                'tanggal' => 'nullable|date_format:Y-m-d',
                'f_status' => 'nullable|in:baru,diproses,selesai',
                'f_kategori' => 'nullable|in:internet_mati,internet_lambat,tidak_bisa_akses,wifi,pembayaran,lainnya',
            ]);
        } catch (ValidationException $e) {
            return redirect('admin/gangguan')->with('auth_errors', array_merge(...array_values($e->errors())));
        }

        $closeAll = $request->boolean('close_all');
        $ids = array_values(array_unique(array_map('intval', $data['ids'] ?? [])));
        $catatan = trim((string) ($data['catatan'] ?? ''));
        $handledAt = now();
        $periode = $this->resolvePeriode($request);

        $closed = DB::transaction(function () use ($closeAll, $ids, $catatan, $handledAt, $periode, $data) {
            $reports = GangguanReport::query()
                ->whereIn('status', ['baru', 'diproses'])
                ->when(
                    $closeAll,
                    fn ($query) => $query
                        ->whereBetween('created_at', [$periode['start'], $periode['end']])
                        ->when($data['f_status'] ?? null, fn ($filtered) => $filtered->where('status', $data['f_status']))
                        ->when($data['f_kategori'] ?? null, fn ($filtered) => $filtered->where('kategori', $data['f_kategori'])),
                    fn ($query) => $query->whereIn('id', $ids)
                )
                ->lockForUpdate()
                ->get();

            foreach ($reports as $report) {
                if ($report->responded_at === null) {
                    $report->responded_at = $handledAt;
                }
                if ($report->resolved_at === null) {
                    $report->resolved_at = $handledAt;
                }

                $report->status = 'selesai';
                $report->handled_by = auth()->id();
                if ($catatan !== '') {
                    $report->catatan = $catatan;
                }
                $report->save();
            }

            return $reports->count();
        });

        $filter = array_filter([
            'periode' => $request->input('periode'),
            'tanggal' => $request->input('tanggal'),
            'status' => $request->input('f_status'),
            'kategori' => $request->input('f_kategori'),
        ], fn ($v) => $v !== null && $v !== '');

        return redirect('admin/gangguan?'.http_build_query($filter))
            ->with('success', ["{$closed} laporan gangguan ditutup"]);
    }

    public function settings()
    {
        return view('admin.gangguan.settings', [
            'title' => 'Pengaturan Laporan Gangguan',
            'setting' => GangguanSetting::current(),
        ] + $this->websiteData());
    }

    public function updateSettings(Request $request)
    {
        try {
            $data = $request->validate([
                'auto_reply_enabled' => 'nullable|boolean',
                'auto_reply_text' => 'nullable|string|max:1000',
                'sla_response_hours' => 'required|integer|min:1|max:168',
                'massal_threshold' => 'required|integer|min:2|max:50',
                'massal_window_hours' => 'required|integer|min:1|max:72',
                'massal_broadcast_text' => 'nullable|string|max:1000',
            ]);
        } catch (ValidationException $e) {
            return redirect('admin/gangguan/pengaturan')->with('auth_errors', array_merge(...array_values($e->errors())));
        }

        $setting = GangguanSetting::current();
        $setting->auto_reply_enabled = (bool) $request->boolean('auto_reply_enabled');
        $setting->auto_reply_text = $data['auto_reply_text'] ?? '';
        $setting->sla_response_hours = $data['sla_response_hours'];
        $setting->massal_threshold = $data['massal_threshold'];
        $setting->massal_window_hours = $data['massal_window_hours'];
        $setting->massal_broadcast_text = $data['massal_broadcast_text'] ?? '';
        $setting->save();

        return redirect('admin/gangguan/pengaturan')->with('success', ['Pengaturan laporan gangguan disimpan']);
    }

    /**
     * Broadcast pemberitahuan gangguan massal ke seluruh pelanggan aktif pada
     * satu ODP terdampak. Dipicu manual oleh admin dari banner gangguan massal.
     */
    public function broadcastOdp(Request $request)
    {
        $odps = Odp::query()->get();
        $odp = $request->filled('odp_id')
            ? $odps->firstWhere('id', (int) $request->input('odp_id'))
            : OdpAssignment::resolve($odps, $request->input('nama_odp'));
        if (! $odp || ! OdpAssignment::isStoredNameUnique($odps, $odp)) {
            return redirect('admin/gangguan')->with('auth_errors', ['ODP tidak valid atau relasi ODP ambigu']);
        }
        $namaOdp = $odp->nama;

        $setting = GangguanSetting::current();
        $template = trim((string) $setting->massal_broadcast_text);
        if ($template === '') {
            return redirect('admin/gangguan')->with('auth_errors', ['Teks broadcast gangguan massal belum diatur. Isi di menu Pengaturan.']);
        }

        $pelanggan = Order::query()
            ->whereRaw('LOWER(TRIM(nama_odp)) = ?', [OdpAssignment::key($odp->nama)])
            ->where('status', 'Active')
            ->whereNotNull('nomor')
            ->where('nomor', '!=', '')
            ->get(['nama', 'nomor']);

        $terkirim = 0;
        $gagal = 0;
        foreach ($pelanggan as $pel) {
            $message = strtr($template, [
                '{odp}' => $namaOdp,
                '{nama}' => trim((string) $pel->nama) !== '' ? ' '.$pel->nama : '',
            ]);
            try {
                WhatsAppNotifier::sendText($pel->nomor, $message);
                $terkirim++;
            } catch (\Throwable $e) {
                $gagal++;
            }
        }

        if ($terkirim === 0 && $gagal === 0) {
            return redirect('admin/gangguan')->with('auth_errors', ['Tidak ada pelanggan aktif dengan nomor pada ODP '.$namaOdp]);
        }

        return redirect('admin/gangguan')->with('success', [
            "Broadcast gangguan massal ODP {$namaOdp} terkirim ke {$terkirim} pelanggan".($gagal > 0 ? " ({$gagal} gagal)" : '').'.',
        ]);
    }
}
