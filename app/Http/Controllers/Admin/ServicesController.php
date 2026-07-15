<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Libraries\RouterosAPI;
use App\Models\Order;
use App\Models\Router;
use App\Models\Service;
use App\Models\Website;
use Illuminate\Http\Request;

class ServicesController extends Controller
{
    private function websiteData(): array
    {
        $website = Website::first();

        return [
            'titletext' => $website->title ?? 'LandakNet',
            'logo' => $website->logo ?? '',
        ];
    }

    private function rosRows(mixed $response): array
    {
        if (! is_array($response)) {
            return [];
        }

        if (isset($response['!trap'])) {
            return [];
        }

        if (! array_is_list($response)) {
            return [$response];
        }

        return array_values(array_filter($response, 'is_array'));
    }

    public function services(Request $request)
    {
        $showData = $request->boolean('show_data');

        return view('admin.services.index', [
            'title' => 'Services',
            'getServices' => $showData ? Service::all() : collect(),
            'router' => Router::all(),
            'showData' => $showData,
        ] + $this->websiteData());
    }

    public function addService(Request $request)
    {
        $package = trim((string) $request->post('package'));
        $price = $request->post('price');
        $ppn = $request->post('ppn', 0);
        $mode = $request->post('mode');
        $profile = trim((string) $request->post('profile'));

        if ($package === '' || $price === null || $mode === null) {
            return redirect(url('admin/services'))->with('auth_errors', ['Paket, harga, dan mode wajib diisi']);
        }

        if (! is_numeric($price) || ! is_numeric($ppn)) {
            return redirect(url('admin/services'))->with('auth_errors', ['Harga dan PPN harus berupa angka']);
        }

        if (! in_array($mode, ['hotspot', 'pppoe'], true)) {
            return redirect(url('admin/services'))->with('auth_errors', ['Mode layanan tidak valid']);
        }

        if (Service::where('paket', $package)->exists()) {
            return redirect(url('admin/services'))->with('auth_errors', ['Nama paket sudah ada']);
        }

        Service::create([
            'paket' => $package,
            'ppp_profile' => $profile,
            'harga' => $price,
            'ppn' => $ppn,
            'status' => 'Tersedia',
            'mode' => $mode,
        ]);

        return redirect(url('admin/services'))->with('success', ['Berhasil menambahkan layanan']);
    }

    public function services_edit($id)
    {
        $service = Service::find($id);

        if ($service === null) {
            return redirect(url('admin/services'))->with('auth_errors', ['Data tidak ditemukan']);
        }

        $isPaketExist = Order::where('paket', $service->paket)->exists();

        return view('admin.services.edit', [
            'title' => 'Edit Services',
            'service' => $service,
            'isPaketExist' => $isPaketExist,
        ] + $this->websiteData());
    }

    public function service_update(Request $request)
    {
        $target = $request->post('target');
        $nama = trim((string) $request->post('nama'));
        $harga = $request->post('harga');
        $ppn = $request->post('ppn', 0);
        $status = $request->post('status');
        $mode = $request->post('mode');

        if ($target == null) {
            return redirect(url('admin/services'))->with('auth_errors', ['Access Denied']);
        }

        $existingService = Service::find($target);

        if (! $existingService) {
            return redirect(url('admin/services'))->with('auth_errors', ['Data tidak ditemukan']);
        }

        if ($nama === '' || $harga === null || $mode === null || $status === null) {
            return redirect(url('admin/services'))->with('auth_errors', ['Nama, harga, mode, dan status wajib diisi']);
        }

        if (! is_numeric($harga) || ! is_numeric($ppn)) {
            return redirect(url('admin/services'))->with('auth_errors', ['Harga dan PPN harus berupa angka']);
        }

        if (! in_array($mode, ['hotspot', 'pppoe'], true)) {
            return redirect(url('admin/services'))->with('auth_errors', ['Mode layanan tidak valid']);
        }

        if (! in_array($status, ['Tersedia', 'Tidak Tersedia'], true)) {
            return redirect(url('admin/services'))->with('auth_errors', ['Status layanan tidak valid']);
        }

        if (Service::where('paket', $nama)->where('id', '!=', $target)->exists()) {
            return redirect(url('admin/services'))->with('auth_errors', ['Nama paket sudah ada']);
        }

        $isPaketUsed = Order::where('paket', $existingService->paket)->exists();

        if ($isPaketUsed) {
            // Jika paket sudah dipakai customer, hanya harga dan ppn yang boleh berubah.
            if ($existingService->paket !== $nama) {
                return redirect(url('admin/services'))->with('auth_errors', ['Nama tidak bisa diubah karena paket sudah ada pada customer']);
            }

            if ($existingService->mode !== $mode) {
                return redirect(url('admin/services'))->with('auth_errors', ['Mode tidak bisa diubah karena paket sudah ada pada customer']);
            }

            if ($existingService->status !== $status) {
                return redirect(url('admin/services'))->with('auth_errors', ['Status tidak bisa diubah karena paket sudah ada pada customer']);
            }
        }

        Service::where('id', $target)->update([
            'paket' => $nama,
            'harga' => $harga,
            'ppn' => $ppn,
            'status' => $status,
            'mode' => $mode,
        ]);

        return redirect(url('admin/services'))->with('success', ['Berhasil mengubah layanan']);
    }

    public function serviceSync($id)
    {
        $service = Service::find($id);

        if ($service === null) {
            return redirect(url('admin/services'))->with('auth_errors', ['Data tidak ditemukan']);
        }

        $getRouter = Router::all();

        if ($getRouter->isEmpty()) {
            return redirect(url('server/router'))->with('auth_errors', ['Tidak ada server pada database, silahkan tambah server terlebih dahulu']);
        }

        return view('admin.services.sync', [
            'title' => 'Sinkronisasi Services',
            'content' => [$service],
            'router' => $getRouter,
            'mode' => $service->mode,
            'profile' => [],
        ] + $this->websiteData());
    }

    public function serviceSyncUpdate(Request $request)
    {
        $target = $request->post('target');
        $profile = trim((string) $request->post('profile'));

        if ($target == null) {
            return redirect(url('admin/services'))->with('auth_errors', ['Access Denied']);
        }

        if ($profile === '') {
            return redirect(url('admin/services/sync/'.$target))->with('auth_errors', ['Pilih profile terlebih dahulu']);
        }

        if (! Service::where('id', $target)->exists()) {
            return redirect(url('admin/services'))->with('auth_errors', ['Data tidak ditemukan']);
        }

        Service::where('id', $target)->update([
            'ppp_profile' => $profile,
        ]);

        return redirect(url('admin/services'))->with('success', ['Berhasil sinkronisasi paket']);
    }

    public function servicesDelete($id = null)
    {
        $service = Service::find($id);

        if ($service === null) {
            return redirect(url('admin/services'))->with('auth_errors', ['Layanan tidak ditemukan']);
        }

        if (Order::where('paket', $service->paket)->exists()) {
            return redirect(url('admin/services'))->with('auth_errors', ['Layanan tidak bisa dihapus karena masih dipakai customer']);
        }

        $service->delete();

        return redirect(url('admin/services'))->with('success', ['Berhasil menghapus layanan']);
    }

    public function getProfileRouter(Request $request)
    {
        $idrouter = $request->post('routerId');
        $mode = $request->post('mode');

        if (! $idrouter || ! $mode) {
            return response()->json([
                'status' => 'error',
                'message' => 'Router ID dan mode harus diisi.',
            ]);
        }

        $router = Router::find($idrouter);

        if (! $router) {
            return response()->json([
                'status' => 'error',
                'message' => 'Router tidak ditemukan.',
            ]);
        }

        $ros = new RouterosAPI;
        if (! $ros->connect($router->ip, $router->username, legacy_decrypt($router->password))) {
            return response()->json([
                'status' => 'error',
                'message' => 'Router tidak terhubung.',
            ]);
        }

        $profiles = $mode == 'hotspot'
            ? $this->rosRows($ros->comm('/ip/hotspot/user/profile/print'))
            : $this->rosRows($ros->comm('/ppp/profile/print'));

        return response()->json([
            'status' => 'success',
            'data' => $profiles,
        ]);
    }
}
