<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Odp;
use App\Models\Order;
use App\Models\Website;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

    public function odp()
    {
        $getallodp = Odp::all();

        // Inisialisasi array untuk menyimpan data ODP dengan jumlah port yang digunakan
        $odpWithDetails = [];

        foreach ($getallodp as $odp) {
            // Mengambil order berdasarkan nama ODP
            $orders = Order::where('nama_odp', $odp->nama)->get()->toArray();

            $totalport = $odp->port;

            // Membuat array dari 1 hingga jumlah total port
            $allPorts = range(1, $odp->port);
            $usedPorts = array_column($orders, 'port_odp'); // Mengumpulkan port yang digunakan dari data order

            $availablePorts = array_diff($allPorts, $usedPorts); // Menghitung port yang tersedia

            $odpWithDetails[] = [
                'nama' => $odp->nama,
                'total_port' => $totalport,
                'used_ports' => count($usedPorts),
                'available_ports' => $availablePorts,
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

    public function area()
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

    public function getCustomerMap()
    {
        return view('admin.coverage.customers', [
            'title' => 'Data Map Pelanggan',
            'customers' => Order::where('status', 'Active')->get(),
            'odps' => Odp::all(),
        ] + $this->websiteData());
    }

    public function rxpower()
    {
        return view('admin.coverage.rxpower', [
            'title' => 'Data Redaman Pelanggan',
            'customers' => Order::where('status', 'Active')->get(),
            'odps' => Odp::all(),
            'rxPowerData' => [],
        ] + $this->websiteData());
    }
}
