<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CoverageCable;
use App\Models\CoverageMapSetting;
use App\Models\GangguanReport;
use App\Models\Odp;
use App\Models\Order;
use App\Models\Website;
use App\Services\AcsDeviceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
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
        $setting = CoverageMapSetting::current();

        return view('admin.coverage.get', [
            'title' => 'Get Coordinat Location',
            'mapCenter' => [
                'latitude' => (float) $setting->center_lat,
                'longitude' => (float) $setting->center_lng,
                'zoom' => (int) $setting->zoom,
            ],
        ] + $this->websiteData());
    }

    public function odc()
    {
        $odcs = Schema::hasTable('odc')
            ? DB::table('odc')->orderBy('name')->get(['id', 'name', 'latitude', 'longitude', 'olt_id', 'description'])
            : collect();

        return view('admin.coverage.odc', [
            'title' => 'Data ODC',
            'odcs' => $odcs,
        ] + $this->websiteData());
    }

    public function odp(Request $request)
    {
        $getallodp = Odp::query()->orderBy('nama')->get(['id', 'kode', 'nama', 'port', 'latitude', 'longitude']);
        $orders = Order::query()
            ->whereNotNull('nama_odp')
            ->where('nama_odp', '!=', '')
            ->get(['nama_odp', 'port_odp'])
            ->groupBy('nama_odp');
        $gangguanPerOdp = Schema::hasTable('gangguan_reports') && Schema::hasColumn('gangguan_reports', 'nama_odp')
            ? GangguanReport::query()
                ->whereIn('status', ['baru', 'diproses'])
                ->whereNotNull('nama_odp')
                ->where('nama_odp', '!=', '')
                ->selectRaw('nama_odp, COUNT(*) as jml')
                ->groupBy('nama_odp')
                ->pluck('jml', 'nama_odp')
            : collect();

        $odpWithDetails = $getallodp->map(function (Odp $odp) use ($orders, $gangguanPerOdp) {
            $assignedOrders = $orders->get($odp->nama, collect());
            $totalPorts = max(0, (int) $odp->port);
            $usedPorts = $assignedOrders->pluck('port_odp')
                ->map(function ($port) {
                    $port = trim((string) $port);

                    return preg_match('/^0*([1-9][0-9]*)$/', $port, $matches) ? (int) $matches[1] : null;
                })
                ->filter(fn ($port) => $port !== null && $port <= $totalPorts)
                ->unique()
                ->sort()
                ->values();
            $availablePorts = $totalPorts > 0
                ? collect(range(1, $totalPorts))->diff($usedPorts)->values()->all()
                : [];

            return [
                'id' => $odp->id,
                'kode' => $odp->kode,
                'nama' => $odp->nama,
                'latitude' => $odp->latitude,
                'longitude' => $odp->longitude,
                'total_port' => $totalPorts,
                'used_ports' => $usedPorts->count(),
                'available_ports' => $availablePorts,
                'pelanggan' => $assignedOrders->count(),
                'gangguan_open' => (int) ($gangguanPerOdp[$odp->nama] ?? 0),
            ];
        })->all();

        return view('admin.coverage.odp', [
            'title' => 'Data ODP',
            'odp' => $getallodp,
            'data' => $odpWithDetails,
        ] + $this->websiteData());
    }

    public function addodp(Request $request)
    {
        try {
            $data = $this->validateOdp($request);
        } catch (ValidationException $e) {
            return $this->coverageValidationRedirect('admin/coverage/odp', $e);
        }

        Odp::create($data);

        return redirect(url('admin/coverage/odp'))->with('success', ['Berhasil menambahkan data ODP']);
    }

    public function updateODP(Request $request, $id)
    {
        $odp = Odp::query()->findOrFail($id);

        try {
            $data = $this->validateOdp($request, $odp->id);
        } catch (ValidationException $e) {
            return $this->coverageValidationRedirect('admin/coverage/odp', $e);
        }

        DB::transaction(function () use ($odp, $data) {
            $oldName = $odp->nama;
            $odp->update($data);

            if ($oldName !== $data['nama']) {
                Order::query()->where('nama_odp', $oldName)->update(['nama_odp' => $data['nama']]);

                if (Schema::hasTable('gangguan_reports') && Schema::hasColumn('gangguan_reports', 'nama_odp')) {
                    DB::table('gangguan_reports')->where('nama_odp', $oldName)->update(['nama_odp' => $data['nama']]);
                }
            }
        });

        return redirect(url('admin/coverage/odp'))->with('success', ['Berhasil mengupdate data ODP']);
    }

    public function deleteODP($id)
    {
        $odp = Odp::query()->findOrFail($id);
        $assignedCustomers = Order::query()->where('nama_odp', $odp->nama)->count();

        if ($assignedCustomers > 0) {
            return redirect(url('admin/coverage/odp'))->with('auth_errors', [
                "ODP {$odp->nama} tidak dapat dihapus karena masih digunakan oleh {$assignedCustomers} pelanggan.",
            ]);
        }

        DB::transaction(function () use ($odp) {
            if (Schema::hasTable('coverage_cables') && Schema::hasColumn('coverage_cables', 'odp_id')) {
                DB::table('coverage_cables')->where('odp_id', $odp->id)->delete();
            }

            $odp->delete();
        });

        return redirect(url('admin/coverage/odp'))->with('success', ['Berhasil menghapus data ODP']);
    }

    private function validateOdp(Request $request, ?int $ignoreId = null): array
    {
        $request->merge([
            'nama' => trim((string) $request->input('nama')),
            'kode' => $request->filled('kode') ? trim((string) $request->input('kode')) : null,
            'port' => $request->input('jumlah', $request->input('port')),
            'latitude' => $request->filled('latitude') ? trim((string) $request->input('latitude')) : null,
            'longitude' => $request->filled('longitude') ? trim((string) $request->input('longitude')) : null,
        ]);

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:191', Rule::unique('odp', 'nama')->ignore($ignoreId)],
            'kode' => ['nullable', 'string', 'max:100', Rule::unique('odp', 'kode')->ignore($ignoreId)],
            'port' => ['required', 'integer', 'min:1', 'max:1024'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
        ]);

        return [
            'nama' => $validated['nama'],
            'kode' => $validated['kode'] ?? null,
            'port' => $validated['port'],
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
        ];
    }

    private function coverageValidationRedirect(string $path, ValidationException $exception)
    {
        return redirect($path)
            ->withInput()
            ->with('auth_errors', collect($exception->errors())->flatten()->all());
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
        $cables = Schema::hasTable('coverage_cables')
            ? CoverageCable::whereIn('odp_id', $odps->pluck('id'))
                ->get(['odp_id', 'path', 'src_hash'])
                ->keyBy('odp_id')
                ->map(fn ($c) => [
                    'path' => $c->path,
                    'src_hash' => $c->src_hash,
                ])
            : collect();

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

        if (($data['hub_lat'] === null) !== ($data['hub_lng'] === null)) {
            return redirect('admin/coverage/peta/pengaturan')->with('auth_errors', ['Latitude dan longitude titik pusat harus diisi berpasangan.']);
        }

        $setting = CoverageMapSetting::current();
        // Bandingkan numerik (bukan string) supaya perbedaan format angka
        // (mis. 0.68 vs 0.6800000) tidak dianggap "pindah" & menghapus cache sia-sia.
        $sama = fn ($a, $b) => round((float) $a, 7) === round((float) $b, 7);
        $pindahHub = ! $sama($setting->hub_lat, $data['hub_lat']) || ! $sama($setting->hub_lng, $data['hub_lng']);
        $setting->fill($data)->save();

        // Titik pusat berpindah -> jalur kabel lama tidak valid, hapus cache biar di-route ulang.
        if ($pindahHub && Schema::hasTable('coverage_cables')) {
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
        if (! Schema::hasTable('coverage_cables') || ! Schema::hasTable('coverage_map_setting')) {
            return response()->json(['ok' => false, 'error' => 'cache tidak tersedia'], 503);
        }

        try {
            $data = $request->validate([
                'odp_id' => ['required', 'integer', Rule::exists('odp', 'id')],
                'path' => ['required', 'array', 'min:2', 'max:6000'],
                'path.*' => ['required', 'array', 'size:2'],
                'path.*.0' => ['required', 'numeric', 'between:-90,90'],
                'path.*.1' => ['required', 'numeric', 'between:-180,180'],
                'src_hash' => ['required', 'string', 'max:160'],
            ]);
        } catch (ValidationException $e) {
            return response()->json(['ok' => false, 'error' => 'validasi'], 422);
        }

        $odp = Odp::query()->find($data['odp_id']);
        $setting = CoverageMapSetting::current();
        $expectedHash = collect([$setting->hub_lat, $setting->hub_lng, $odp->latitude, $odp->longitude])
            ->map(fn ($coordinate) => number_format((float) $coordinate, 6, '.', ''))
            ->implode('|');

        if ($data['src_hash'] !== $expectedHash) {
            return response()->json(['ok' => false, 'error' => 'koordinat berubah'], 409);
        }

        CoverageCable::updateOrCreate(
            ['odp_id' => $data['odp_id']],
            ['path' => $data['path'], 'src_hash' => $expectedHash]
        );

        return response()->json(['ok' => true]);
    }

    public function area(Request $request)
    {
        $areas = Schema::hasTable('area') ? DB::table('area')->orderBy('nama')->get(['id', 'nama', 'kode']) : collect();
        $ordersHaveArea = Schema::hasTable('orders') && Schema::hasColumn('orders', 'kode_area');
        $odpHaveArea = Schema::hasTable('odp') && Schema::hasColumn('odp', 'kode_area');
        $customerCounts = $ordersHaveArea
            ? DB::table('orders')->whereNotNull('kode_area')->selectRaw('kode_area, COUNT(*) as total')->groupBy('kode_area')->pluck('total', 'kode_area')
            : collect();
        $odpCounts = $odpHaveArea
            ? DB::table('odp')->whereNotNull('kode_area')->selectRaw('kode_area, COUNT(*) as total')->groupBy('kode_area')->pluck('total', 'kode_area')
            : collect();

        $areaData = $areas->map(fn ($area) => [
            'id' => $area->id,
            'kode' => $area->kode,
            'nama' => $area->nama,
            'jumlah_odp' => $odpHaveArea ? (int) ($odpCounts[$area->kode] ?? 0) : null,
            'jumlah_pelanggan' => $ordersHaveArea ? (int) ($customerCounts[$area->kode] ?? 0) : null,
        ])->all();

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

        try {
            $data = $this->validateArea($request);
        } catch (ValidationException $e) {
            return $this->coverageValidationRedirect('admin/coverage/area', $e);
        }

        DB::table('area')->insert($data);

        return redirect(url('admin/coverage/area'))->with('success', ['Berhasil menambahkan area']);
    }

    public function updateArea(Request $request, $id)
    {
        if (! Schema::hasTable('area')) {
            return redirect(url('admin/coverage/area'))->with('auth_errors', ['Tabel area tidak ditemukan']);
        }

        $area = DB::table('area')->where('id', $id)->first();
        abort_if(! $area, 404);

        try {
            $data = $this->validateArea($request, (int) $id);
        } catch (ValidationException $e) {
            return $this->coverageValidationRedirect('admin/coverage/area', $e);
        }

        DB::transaction(function () use ($area, $data, $id) {
            DB::table('area')->where('id', $id)->update($data);

            if ($area->kode !== $data['kode']) {
                foreach (['orders', 'odp'] as $table) {
                    if (Schema::hasTable($table) && Schema::hasColumn($table, 'kode_area')) {
                        DB::table($table)->where('kode_area', $area->kode)->update(['kode_area' => $data['kode']]);
                    }
                }
            }
        });

        return redirect(url('admin/coverage/area'))->with('success', ['Berhasil mengupdate area']);
    }

    public function deleteArea($id)
    {
        if (! Schema::hasTable('area')) {
            return redirect(url('admin/coverage/area'))->with('auth_errors', ['Tabel area tidak ditemukan']);
        }

        $area = DB::table('area')->where('id', $id)->first();
        abort_if(! $area, 404);

        $references = collect(['orders', 'odp'])->sum(function ($table) use ($area) {
            return Schema::hasTable($table) && Schema::hasColumn($table, 'kode_area')
                ? DB::table($table)->where('kode_area', $area->kode)->count()
                : 0;
        });

        if ($references > 0) {
            return redirect(url('admin/coverage/area'))->with('auth_errors', [
                "Area {$area->nama} tidak dapat dihapus karena masih digunakan oleh {$references} data.",
            ]);
        }

        DB::table('area')->where('id', $id)->delete();

        return redirect(url('admin/coverage/area'))->with('success', ['Berhasil menghapus area']);
    }

    private function validateArea(Request $request, ?int $ignoreId = null): array
    {
        $request->merge([
            'nama' => trim((string) $request->input('area', $request->input('nama'))),
            'kode' => strtoupper(trim((string) $request->input('kode'))),
        ]);

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:191'],
            'kode' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('area', 'kode')->ignore($ignoreId)],
        ]);

        return ['nama' => $validated['nama'], 'kode' => $validated['kode']];
    }

    public function getCustomerMap(Request $request)
    {
        $customerColumns = ['idpel', 'nama', 'status', 'nama_odp', 'port_odp', 'latitude', 'longitude'];
        $odpColumns = ['id', 'nama', 'kode', 'latitude', 'longitude'];
        $ordersReady = Schema::hasTable('orders') && collect($customerColumns)->every(fn ($column) => Schema::hasColumn('orders', $column));
        $odpReady = Schema::hasTable('odp') && collect($odpColumns)->every(fn ($column) => Schema::hasColumn('odp', $column));
        $customers = $ordersReady ? Order::query()->where('status', 'Active')->get($customerColumns) : collect();
        $odps = $odpReady ? Odp::query()->get($odpColumns) : collect();
        $mappedCount = $customers->filter(fn ($customer) => $this->hasValidCoordinates($customer->latitude ?? null, $customer->longitude ?? null))->count();

        return view('admin.coverage.customers', [
            'title' => 'Data Map Pelanggan',
            'customers' => $customers,
            'odps' => $odps,
            'mappedCount' => $mappedCount,
            'unmappedCount' => $customers->count() - $mappedCount,
        ] + $this->websiteData());
    }

    private function hasValidCoordinates($latitude, $longitude): bool
    {
        return is_numeric($latitude) && is_numeric($longitude)
            && (float) $latitude >= -90 && (float) $latitude <= 90
            && (float) $longitude >= -180 && (float) $longitude <= 180;
    }

    public function rxpower(Request $request, AcsDeviceService $acsDeviceService)
    {
        $customers = Order::query()
            ->where('status', 'Active')
            ->get(['idpel', 'nama', 'pppoe_user', 'nama_odp', 'port_odp']);
        $acs = Schema::hasTable('acs') ? DB::table('acs')->orderBy('id')->first() : null;
        $rxPowerData = [];
        $acsError = null;

        if (! $acs || empty($acs->url)) {
            $acsError = 'Server ACS belum dikonfigurasi.';
        } else {
            try {
                $rxPowerData = $acsDeviceService->getRxPowerByPppoeUsername($acs->url);
            } catch (\RuntimeException $exception) {
                report($exception);
                $acsError = 'Data RX Power tidak dapat diambil dari server ACS.';
            }
        }

        return view('admin.coverage.rxpower', [
            'title' => 'Data Redaman Pelanggan',
            'customers' => $customers,
            'rxPowerData' => $rxPowerData,
            'acsError' => $acsError,
        ] + $this->websiteData());
    }
}
