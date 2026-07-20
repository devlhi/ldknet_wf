<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CoverageCable;
use App\Models\CoverageMapSetting;
use App\Models\GangguanReport;
use App\Models\Odp;
use App\Models\Order;
use App\Models\Website;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CoverageController extends Controller
{
    private function websiteData(): array
    {
        $website = Website::first();

        return [
            'titletext' => $website->title ?? 'LandakNet',
            'logo' => $website->logo ?? '',
        ];
    }

    public function getCoordinat()
    {
        return view('admin.coverage.get', [
            'title' => 'Get Coordinat Location',
        ] + $this->websiteData());
    }

    public function odc()
    {
        return view('admin.coverage.odc', [
            'title' => 'Data ODC',
        ] + $this->websiteData());
    }

    public function odp(Request $request)
    {
        $getallodp = Odp::all();

        // Jumlah pelanggan & laporan gangguan terbuka per ODP dihitung sekali
        // (hindari query di dalam loop).
        $pelangganPerOdp = Order::selectRaw('nama_odp, COUNT(*) as jml')
            ->whereNotNull('nama_odp')->where('nama_odp', '!=', '')
            ->groupBy('nama_odp')->pluck('jml', 'nama_odp');

        $gangguanPerOdp = Schema::hasTable('gangguan_reports')
            ? GangguanReport::whereIn('status', ['baru', 'diproses'])
                ->whereNotNull('nama_odp')->where('nama_odp', '!=', '')
                ->selectRaw('nama_odp, COUNT(*) as jml')
                ->groupBy('nama_odp')->pluck('jml', 'nama_odp')
            : collect();

        // Data ODP lengkap untuk tabel + peta (satu sumber data).
        $odpWithDetails = [];

        $portsPerOdp = Order::whereNotNull('nama_odp')->get(['nama_odp', 'port_odp'])->groupBy('nama_odp');
        foreach ($getallodp as $odp) {
            $orders = $portsPerOdp->get($odp->nama, collect());

            $totalport = (int) $odp->port;
            $allPorts = $totalport > 0 ? range(1, $totalport) : [];
            $usedPorts = $orders->pluck('port_odp')->filter(fn ($p) => $p !== null && $p !== '')->all();
            $availablePorts = array_values(array_diff($allPorts, $usedPorts));

            $odpWithDetails[] = [
                'id' => $odp->id,
                'kode' => $odp->kode,
                'nama' => $odp->nama,
                'latitude' => $odp->latitude,
                'longitude' => $odp->longitude,
                'total_port' => $totalport,
                'used_ports' => count($usedPorts),
                'available_ports' => $availablePorts,
                'pelanggan' => (int) ($pelangganPerOdp[$odp->nama] ?? 0),
                'gangguan_open' => (int) ($gangguanPerOdp[$odp->nama] ?? 0),
            ];
        }

        return view('admin.coverage.odp', [
            'title' => 'Data ODP',
            'odp' => $getallodp,
            'data' => $odpWithDetails,
        ] + $this->websiteData());
    }

    public function addodp(Request $request)
    {
        Odp::create([
            'nama' => $request->post('nama'),
            'kode' => $request->post('kode'),
            'port' => $request->post('jumlah'),
            'latitude' => $request->post('latitude'),
            'longitude' => $request->post('longitude'),
        ]);

        return redirect(url('admin/coverage/odp'))->with('success', ['Berhasil menambahkan data ODP']);
    }

    public function updateODP(Request $request, $id)
    {
        $data = [
            'nama' => $request->post('nama'),
            'kode' => $request->post('kode'),
            'port' => $request->post('jumlah') ?? $request->post('port'),
            'latitude' => $request->post('latitude'),
            'longitude' => $request->post('longitude'),
        ];

        Odp::where('id', $id)->update(array_filter($data, fn ($value) => $value !== null));

        return redirect(url('admin/coverage/odp'))->with('success', ['Berhasil mengupdate data ODP']);
    }

    public function deleteODP($id)
    {
        Odp::where('id', $id)->delete();

        return redirect(url('admin/coverage/odp'))->with('success', ['Berhasil menghapus data ODP']);
    }

    /**
     * Peta jaringan: tampilkan titik pusat/OLT, semua ODP, dan jalur kabel fiber
     * hub->ODP (mengikuti jalan via OSRM, hasil di-cache). Basemap bisa dipilih.
     */
    public function peta(Request $request)
    {
        $setting = CoverageMapSetting::current();

        $odps = Odp::whereNotNull('latitude')
            ->where('latitude', '!=', '')
            ->whereNotNull('longitude')
            ->where('longitude', '!=', '')
            ->get(['id', 'nama', 'kode', 'port', 'latitude', 'longitude']);

        // Cache jalur kabel per ODP (path + src_hash) untuk dibandingkan di sisi klien.
        $cables = CoverageCable::whereIn('odp_id', $odps->pluck('id'))
            ->get(['odp_id', 'path', 'src_hash'])
            ->keyBy('odp_id')
            ->map(fn ($c) => [
                'path' => $c->path,
                'src_hash' => $c->src_hash,
            ]);

        return view('admin.coverage.peta', [
            'title' => 'Peta Jaringan',
            'setting' => $setting,
            'odps' => $odps,
            'cables' => $cables,
        ] + $this->websiteData());
    }

    public function petaSettings()
    {
        return view('admin.coverage.peta-settings', [
            'title' => 'Pengaturan Peta Jaringan',
            'setting' => CoverageMapSetting::current(),
        ] + $this->websiteData());
    }

    public function petaSettingsUpdate(Request $request)
    {
        try {
            $data = $request->validate([
                'hub_label' => 'nullable|string|max:100',
                'hub_lat' => 'nullable|numeric|between:-90,90',
                'hub_lng' => 'nullable|numeric|between:-180,180',
                'center_lat' => 'required|numeric|between:-90,90',
                'center_lng' => 'required|numeric|between:-180,180',
                'zoom' => 'required|integer|min:1|max:20',
                'basemap' => 'required|in:streets,satelit,topografi,gelap',
            ]);
        } catch (ValidationException $e) {
            return redirect('admin/coverage/peta/pengaturan')->with('auth_errors', array_merge(...array_values($e->errors())));
        }

        $setting = CoverageMapSetting::current();
        // Bandingkan numerik (bukan string) supaya perbedaan format angka
        // (mis. 0.68 vs 0.6800000) tidak dianggap "pindah" & menghapus cache sia-sia.
        $sama = fn ($a, $b) => round((float) $a, 7) === round((float) $b, 7);
        $pindahHub = ! $sama($setting->hub_lat, $data['hub_lat']) || ! $sama($setting->hub_lng, $data['hub_lng']);
        $setting->fill($data)->save();

        // Titik pusat berpindah -> jalur kabel lama tidak valid, hapus cache biar di-route ulang.
        if ($pindahHub) {
            CoverageCable::query()->delete();
        }

        return redirect('admin/coverage/peta/pengaturan')->with('success', ['Pengaturan peta jaringan disimpan']);
    }

    /**
     * Simpan (cache) jalur kabel hasil routing OSRM dari sisi klien.
     * Dipanggil AJAX saat peta menemukan ODP yang belum punya cache/valid.
     */
    public function storeCable(Request $request)
    {
        try {
            $data = $request->validate([
                'odp_id' => 'required|integer',
                'path' => 'required|array|min:2|max:6000',
                'src_hash' => 'required|string|max:160',
            ]);
        } catch (ValidationException $e) {
            return response()->json(['ok' => false, 'error' => 'validasi'], 422);
        }

        CoverageCable::updateOrCreate(
            ['odp_id' => $data['odp_id']],
            ['path' => $data['path'], 'src_hash' => $data['src_hash']]
        );

        return response()->json(['ok' => true]);
    }

    public function area(Request $request)
    {
        $areas = Schema::hasTable('area') ? DB::table('area')->get() : collect();
        $areaData = [];

        foreach ($areas as $area) {
            $kode = $area->kode ?? null;
            $areaData[] = [
                'id' => $area->id ?? null,
                'kode' => $kode,
                'nama' => $area->nama ?? '',
                'jumlah_odp' => $kode && Schema::hasColumn('odp', 'kode_area') ? Odp::where('kode_area', $kode)->count() : 0,
                'jumlah_pelanggan' => $kode && Schema::hasColumn('orders', 'kode_area') ? Order::where('kode_area', $kode)->count() : 0,
            ];
        }

        return view('admin.coverage.area', [
            'title' => 'Data Area',
            'area' => $areaData,
        ] + $this->websiteData());
    }

    public function areaAdd(Request $request)
    {
        if (! Schema::hasTable('area')) {
            return redirect(url('admin/coverage/area'))->with('auth_errors', ['Tabel area tidak ditemukan']);
        }

        DB::table('area')->insert([
            'nama' => $request->post('area') ?? $request->post('nama'),
            'kode' => $request->post('kode'),
        ]);

        return redirect(url('admin/coverage/area'))->with('success', ['Berhasil menambahkan area']);
    }

    public function updateArea(Request $request, $id)
    {
        if (! Schema::hasTable('area')) {
            return redirect(url('admin/coverage/area'))->with('auth_errors', ['Tabel area tidak ditemukan']);
        }

        DB::table('area')->where('id', $id)->update([
            'nama' => $request->post('area') ?? $request->post('nama'),
            'kode' => $request->post('kode'),
        ]);

        return redirect(url('admin/coverage/area'))->with('success', ['Berhasil mengupdate area']);
    }

    public function deleteArea($id)
    {
        if (Schema::hasTable('area')) {
            DB::table('area')->where('id', $id)->delete();
        }

        return redirect(url('admin/coverage/area'))->with('success', ['Berhasil menghapus area']);
    }

    public function getCustomerMap(Request $request)
    {

        return view('admin.coverage.customers', [
            'title' => 'Data Map Pelanggan',
            'customers' => Order::where('status', 'Active')->get(),
            'odps' => Odp::all(),
        ] + $this->websiteData());
    }

    public function rxpower(Request $request)
    {

        return view('admin.coverage.rxpower', [
            'title' => 'Data Redaman Pelanggan',
            'customers' => Order::where('status', 'Active')->get(),
            'odps' => Odp::all(),
            'rxPowerData' => [],
        ] + $this->websiteData());
    }
}
