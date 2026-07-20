<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Libraries\RouterosAPI;
use App\Models\Invoice;
use App\Models\Odp;
use App\Models\Order;
use App\Models\Pool;
use App\Models\Router;
use App\Models\Service;
use App\Models\SmtpSetting;
use App\Models\TemplateMessage;
use App\Models\User;
use App\Models\Website;
use App\Support\OdpAssignment;
use App\Support\WhatsAppNotifier;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Configuration;
use Brevo\Client\Model\SendSmtpEmail;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CustomerController extends Controller
{
    private function websiteData(): array
    {
        $website = Website::first();

        return [
            'titletext' => $website->title ?? 'LandakNet',
            'logo' => $website->logo ?? '',
        ];
    }

    // GET admin/customers (CI4: AdminController::customer)
    public function customer()
    {
        // Data tabel di-load lazy via AJAX server-side (lihat customerData()).
        // Di sini cukup kirim ringkasan + daftar router untuk dropdown filter.
        $routers = Router::pluck('nama', 'id')->all();

        return view('admin.customer.index', [
            'title' => 'Customers',
            'customerActive' => Order::where('status', 'Active')->count(),
            'customerIsolir' => Order::where('status', 'Isolir')->count(),
            'customerEnd' => Order::where('status', 'Berhenti')->count(),
            'routers' => $routers,
        ] + $this->websiteData());
    }

    // GET admin/customer/data — sumber data DataTables server-side (lazy load)
    public function customerData(Request $request)
    {
        if (! $request->boolean('show_data')) {
            return response()->json([
                'draw' => (int) $request->input('draw', 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }

        $isDeveloper = auth()->user()->level === 'developer';
        $routers = Router::pluck('nama', 'id')->all();

        // Kolom yang bisa di-order, dipetakan dari index kolom DataTables ke kolom DB.
        // Urutan harus sama persis dengan header/columns di view.
        $orderable = [
            'orders.id',        // 0: No (pakai id sebagai urutan stabil)
            'orders.idpel',     // 1: ID Pelanggan
            'orders.nama',      // 2: Nama Pelanggan
            'orders.paket',     // 3: Layanan
            'orders.id_router', // 4: Server
            'orders.mode',      // 5: Mode
        ];
        $orderable[] = 'orders.pppoe_user'; // 6: User PPPOE
        $orderable[] = 'orders.expdate'; // Masa Aktif
        $orderable[] = 'orders.status';  // Status
        $orderable[] = 'orders.alamat';  // Alamat
        $orderable[] = 'orders.nama_odp'; // Nama ODP
        $orderable[] = 'orders.port_odp'; // Port ODP

        $base = fn () => Order::select('orders.*', 'users.id as customer_id')
            ->leftJoin('users', function ($join) {
                $join->on('orders.email', '=', 'users.email')
                    ->on('orders.nomor', '=', 'users.nomor')
                    ->where('users.level', 'user');
            });

        $recordsTotal = Order::count();

        $query = $base();

        // Filter toolbar: Server & Status
        $routerId = $request->get('router');
        if ($routerId && $routerId !== 'all') {
            $query->where('orders.id_router', $routerId);
        }
        $status = $request->get('status');
        if ($status && $status !== 'all') {
            $query->where('orders.status', $status);
        }

        // Global search DataTables
        $search = $request->input('search.value');
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                foreach (['idpel', 'nama', 'paket', 'mode', 'alamat', 'pppoe_user', 'nama_odp'] as $col) {
                    $q->orWhere('orders.'.$col, 'like', '%'.$search.'%');
                }
            });
        }

        $recordsFiltered = $query->clone()->count();

        // Order
        $orderColIndex = (int) $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'asc') === 'desc' ? 'desc' : 'asc';
        $orderColumn = $orderable[$orderColIndex] ?? 'orders.id';
        $query->orderBy($orderColumn, $orderDir);

        // Pagination
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);
        if ($length > 0) {
            $query->skip($start)->take($length);
        }

        $rows = $query->get();
        $odpByAssignment = OdpAssignment::uniqueByStoredName(Odp::query()->get(['id', 'nama']));

        $out = [];
        foreach ($rows as $i => $row) {
            $namarouter = $routers[$row->id_router] ?? 'Unknown';

            if ($row->status == 'Active') {
                $label = 'success';
            } elseif ($row->status == 'Isolir') {
                $label = 'warning';
            } else {
                $label = 'danger';
            }

            $masaAktif = tanggal(date('Y-m-d', strtotime($row->expdate)));
            // Tandai merah bila masa aktif sudah lewat (abaikan tanggal legacy 0000-00-00).
            $expTs = strtotime($row->expdate);
            $isExpired = $expTs !== false && $expTs > 0 && $expTs < strtotime(date('Y-m-d'));
            $masaAktifCell = $isExpired
                ? '<span class="text-danger fw-semibold">'.$masaAktif.' <i class="uil uil-exclamation-triangle"></i></span>'
                : $masaAktif;

            $resolvedOdp = $odpByAssignment->get(OdpAssignment::key($row->nama_odp));
            $namaOdp = trim((string) $row->nama_odp) !== ''
                ? '<span class="badge bg-info">'.e($resolvedOdp?->nama ?? $row->nama_odp).'</span>'
                : '<span class="badge bg-secondary">Belum diatur</span>';
            $portOdp = $row->port_odp != null
                ? '<span class="badge bg-info">Port '.e($row->port_odp).'</span>'
                : '<span class="badge bg-secondary">Belum diatur</span>';

            $namaEsc = e($row->nama);
            $namaAttr = htmlspecialchars($row->nama, ENT_QUOTES);
            $connectionUrl = url('admin/customer/connection/'.$row->id);
            $pppoeCell = trim((string) $row->pppoe_user) !== ''
                ? e($row->pppoe_user).' <button type="button" class="btn btn-xs btn-outline-info ms-1 btn-check-connection" data-url="'.$connectionUrl.'"><i class="mdi mdi-lan-connect"></i> Cek Koneksi</button>'
                : '<span class="text-muted">Belum sinkron</span> <button type="button" class="btn btn-xs btn-outline-secondary ms-1" disabled>Cek Koneksi</button>';

            $gantipassUrl = $row->customer_id ? url('admin/customer/gantipass/'.$row->customer_id) : null;
            $detailUrl = url('admin/customer/detail/'.$row->idpel);

            $editUrl = url('admin/customer/edit/'.$row->idpel);

            // Tombol WA: buka chat langsung ke nomor pelanggan (nomor dinormalisasi ke 62...).
            $waNumber = preg_replace('/\D+/', '', (string) $row->nomor);
            $waNumber = str_starts_with($waNumber, '0') ? '62'.substr($waNumber, 1) : $waNumber;
            $waBtn = $waNumber !== ''
                ? '<a href="https://wa.me/'.$waNumber.'" target="_blank" class="btn btn-sm btn-success" title="Chat WhatsApp"><i class="uil uil-whatsapp"></i></a> '
                : '';

            $passwordBtn = $gantipassUrl
                ? '<button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalGantiPass" data-action="'.$gantipassUrl.'" data-nama="'.$namaAttr.'"><i class="uil uil-lock"></i> Ganti Password</button> '
                : '<button type="button" class="btn btn-sm btn-secondary" disabled title="Akun login pelanggan belum cocok"><i class="uil uil-lock"></i> Ganti Password</button> ';

            $aksi = $waBtn
                .$passwordBtn
                .'<a href="'.$detailUrl.'" class="btn btn-sm btn-success"><i class="uil-check-circle"></i> Cek Disini</a> '
                .'<a href="'.$editUrl.'" class="btn btn-sm btn-primary"><i class="uil-edit"></i> Edit Data</a>';

            $editAksi = '';
            if ($isDeveloper) {
                $deleteUrl = url('admin/customer/delete/'.$row->id);
                $editAksi = '<button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#modalHapus" data-action="'.$deleteUrl.'" data-nama="'.$namaAttr.'"><i class="uil uil-trash"></i> Hapus Data</button>';
            }

            $rowData = [
                $start + $i + 1,
                e($row->idpel),
                $namaEsc,
                e($row->paket),
                e($namarouter),
                e($row->mode),
            ];
            $rowData[] = $pppoeCell;
            $rowData[] = $masaAktifCell;
            $rowData[] = '<span class="badge bg-'.$label.'">'.e($row->status).'</span>';
            $rowData[] = e($row->alamat);
            $rowData[] = trim($namaOdp);
            $rowData[] = trim($portOdp);
            $rowData[] = $aksi;
            if ($isDeveloper) {
                $rowData[] = $editAksi;
            }

            $out[] = $rowData;
        }

        return response()->json([
            'draw' => (int) $request->input('draw', 1),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $out,
        ]);
    }

    // GET admin/customer/add (CI4: AdminController::customerService)
    public function customerService()
    {
        $service = Service::all();

        if ($service->isEmpty()) {
            return redirect('admin/services');
        }

        return view('admin.customer.service', [
            'title' => 'Paket Layanan',
            'package' => $service,
        ] + $this->websiteData());
    }

    // GET admin/customer/add/{id} (CI4: AdminController::customerAdd)
    public function customerAdd($id)
    {
        $getRouter = Router::all();

        if ($getRouter->isEmpty()) {
            return redirect('admin/customers')->with('auth_errors', ['Tidak ada server pada database, silahkan tambah server terlebih dahulu']);
        }

        return view('admin.customer.add', [
            'title' => 'Input Pelanggan Baru',
            'password' => random_number(8),
            'getservice' => Service::where('id', $id)->get(),
            'getrouter' => $getRouter,
            'idpel' => Order::selectRaw('MAX(idpel) as idpel')->get(),
        ] + $this->websiteData());
    }

    // POST admin/customer/add/proses (CI4: AdminController::customerProccessAdd)
    public function customerProccessAdd(Request $request)
    {
        $website = Website::first();
        $logo = $website->logo ?? '';
        $titletext = $website->title ?? '';

        $package = $request->input('package');
        $name = $request->input('name');
        $nomor = $request->input('nohp');
        $email = $request->input('email');
        $alamat = $request->input('alamat');
        $idpel = $request->input('idpel');
        $password = (string) $request->input('password');
        $router = $request->input('router');
        $uname_pppoe = $request->input('uname_pppoe');
        $pass_pppoe = $request->input('pass_pppoe');
        $hariini = date('Y-m-d');
        $masa = date('Y-m-d', strtotime('+1 month', strtotime($hariini)));

        if (empty($name) || empty($nomor) || empty($email) || empty($alamat)) {
            return redirect('admin/customer/add')->with('auth_errors', ['Mohon mengisi semua input ']);
        }

        if (User::where('nama', $name)->exists()) {
            return redirect('admin/customer/add')->with('auth_errors', ['Nama Pelanggan sudah ada ']);
        }

        if (User::where('email', $email)->exists()) {
            return redirect('admin/customer/add')->with('auth_errors', ['Email sudah ada ']);
        }

        if (User::where('nomor', $nomor)->exists()) {
            return redirect('admin/customer/add')->with('auth_errors', ['Nomor sudah ada ']);
        }

        $service = Service::where('paket', $package)->get()->last();
        $mode = $service->mode ?? null;
        $ppprofile = $service->ppp_profile ?? null;

        $template = TemplateMessage::all()->last();
        $message = $template->notif_pelanggan_baru ?? '';
        $pesanemail = $template->notif_pelanggan_baru_email ?? '';

        $smtp = SmtpSetting::all()->last();

        $routerData = Router::find($router);

        if (! $routerData) {
            return redirect('admin/customers')->with('auth_errors', ['Router tidak ditemukan']);
        }

        $hostserver = $routerData->ip;
        $usernameserver = $routerData->username;
        $passwordserver = legacy_decrypt($routerData->password);

        if ($ppprofile == null) {
            return redirect('admin/services')->with('auth_errors', ['Harap sinkronkan paket terlebih dahulu']);
        }

        $expired = $masa;

        if ($mode !== 'hotspot' && $mode !== 'pppoe') {
            return redirect('admin/customer/add')->with('auth_errors', ['Mode paket yang dipilih salah']);
        }

        $ros = new RouterosAPI;

        if (! $ros->connect($hostserver, $usernameserver, $passwordserver)) {
            return redirect('admin/customers')->with('auth_errors', ['Router Not Connected ']);
        }

        if ($mode === 'hotspot') {
            $getprofile = $ros->comm('/ip/hotspot/user/profile/print');
        } else {
            $getprofile = $ros->comm('/ppp/profile/print');
        }

        $profileFound = false;

        if (is_array($getprofile)) {
            foreach ($getprofile as $dataprofile) {
                if (isset($dataprofile['name']) && $dataprofile['name'] === $ppprofile) {
                    $profileFound = true;
                    break;
                }
            }
        }

        if (! $profileFound) {
            return redirect('admin/services')->with('auth_errors', ['profile tersebut tidak ada di router itu']);
        }

        if ($mode === 'hotspot') {
            $ros->comm('/ip/hotspot/user/add', [
                'server' => 'all',
                'name' => $uname_pppoe,
                'password' => $pass_pppoe,
                'profile' => $ppprofile,
            ]);
        } else {
            $ros->comm('/ppp/secret/add', [
                'name' => $uname_pppoe,
                'password' => $pass_pppoe,
                'service' => 'pppoe',
                'profile' => $ppprofile,
            ]);
        }

        // Order + User ditulis atomik: bila salah satu gagal, keduanya di-rollback
        // supaya tidak ada data orphan (Order tanpa akun User, atau sebaliknya).
        DB::transaction(function () use ($idpel, $email, $name, $package, $alamat, $nomor, $hariini, $expired, $uname_pppoe, $router, $mode, $password) {
            Order::create([
                'idpel' => $idpel,
                'email' => $email,
                'nama' => $name,
                'paket' => $package,
                'alamat' => $alamat,
                'nomor' => $nomor,
                'status' => 'Active',
                'date' => $hariini,
                'expdate' => $expired,
                'pppoe_user' => $uname_pppoe,
                'id_router' => $router,
                'mode' => $mode,
            ]);

            User::create([
                'email' => $email,
                'nama' => $name,
                'nomor' => $nomor,
                'password' => Hash::make($password),
                'level' => 'user',
                'verify_account' => '1',
                'status_account' => 'Active',
            ]);
        });

        $indo = date('Y-m-d', strtotime($expired));
        $tglindo = tanggal_indo($indo);

        $message = str_replace('{nama_customer}', $name, $message);
        $message = str_replace('{id_pelanggan}', $idpel, $message);
        $message = str_replace('{paket}', $package, $message);
        $message = str_replace('{expdate}', $tglindo, $message);

        $pesanemail = str_replace('{namatitle}', $titletext, $pesanemail);
        $pesanemail = str_replace('{id_pelanggan}', $idpel, $pesanemail);
        $pesanemail = str_replace('{email}', $email, $pesanemail);
        $pesanemail = str_replace('{password}', $password, $pesanemail);
        $pesanemail = str_replace('{nohp}', $nomor, $pesanemail);

        // Password sengaja tidak dikirim ke template Meta (ditolak Meta di kategori UTILITY); password tetap dikirim via email.
        WhatsAppNotifier::sendNotification(WhatsAppNotifier::EVENT_PELANGGAN_BARU, $nomor, $message, [$name, $email, $idpel, $tglindo, $package, url('/')]);

        try {
            $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $smtp->key ?? '');
            $apiInstance = new TransactionalEmailsApi(new Client, $config);

            $sendSmtpEmail = new SendSmtpEmail;
            $sendSmtpEmail['params'] = ['subject' => 'Akun Client Area '.$titletext];
            $sendSmtpEmail['subject'] = '{{params.subject}}';
            $sendSmtpEmail['htmlContent'] = view('emails.new-customer', [
                'logo' => $logo,
                'titletext' => $titletext,
                'pesanemail' => $pesanemail,
            ])->render();
            $sendSmtpEmail['sender'] = ['name' => $smtp->nama ?? '', 'email' => $smtp->email ?? ''];
            $sendSmtpEmail['to'] = [['email' => $email]];

            $apiInstance->sendTransacEmail($sendSmtpEmail);

            return redirect('admin/customers')->with('success', ['Berhasil menambahkan data pelanggan baru ']);
        } catch (\Throwable $e) {
            return redirect('admin/manage/user')->with('auth_errors', ['Gagal menambahkan data akun baru ']);
        }
    }

    // GET admin/customer/detail/{id} (CI4: AdminController::customer_detail)
    public function customer_detail(Request $request, $id = null)
    {
        if ($id == null) {
            return redirect('admin/customers');
        }

        $getCustomer = Order::where('idpel', $id)->get();

        if ($getCustomer->isEmpty()) {
            return redirect('admin/customers');
        }

        $dataCustomers = $getCustomer->last();
        $mode = $dataCustomers->mode;
        $id_router = $dataCustomers->id_router;

        $customer = Order::where('idpel', $id)->first();

        $invoice = Invoice::select('invoice.*', 'orders.idpel')
            ->join('orders', 'invoice.idpel', '=', 'orders.idpel')
            ->where('orders.idpel', $id)
            ->orderByDesc('invoice.date')
            ->get();

        $datacustomer = Order::where('idpel', $id)->get();
        $pppoe = optional($datacustomer->last())->pppoe_user;

        if (Router::count() === 0) {
            return redirect('admin/customers')->with('auth_errors', ['Tidak ada server pada database, silahkan tambah server terlebih dahulu']);
        }

        $routerData = Router::find($id_router);

        if (! $routerData) {
            return redirect('admin/customers');
        }

        $ros = new RouterosAPI;

        if (! $ros->connect($routerData->ip, $routerData->username, legacy_decrypt($routerData->password))) {
            return redirect('admin/customers')->with('auth_errors', ['Server tidak merespon']);
        }

        $getinterface = $ros->comm('/interface/print');

        if ($mode == 'pppoe') {
            $statusppp = $ros->comm('/ppp/active/getall', [
                '.proplist' => '.id',
                '?name' => $pppoe,
            ]);
        } elseif ($mode == 'hotspot') {
            $statusppp = $ros->comm('/ip/hotspot/active/print', [
                '?user' => $pppoe,
            ]);
        } else {
            return redirect('admin/customers');
        }

        return view('admin.customer.detail', [
            'title' => 'Detail Pelanggan',
            'customer' => $customer,
            'invoice' => $invoice,
            'datacustomer' => $datacustomer,
            'statusppp' => $statusppp,
            'interface' => $getinterface,
            'traffics' => $pppoe,
            'mode' => $mode,
        ] + $this->websiteData());
    }

    public function connectionStatus($id)
    {
        $order = Order::query()->find($id);
        if (! $order) {
            return response()->json(['online' => false, 'message' => 'Pelanggan tidak ditemukan'], 404);
        }
        if (empty($order->id_router) || empty($order->pppoe_user)) {
            return response()->json(['online' => false, 'message' => 'Pelanggan offline']);
        }

        $router = Router::query()->find($order->id_router);
        if (! $router) {
            return response()->json(['online' => false, 'message' => 'Pelanggan offline']);
        }

        $ros = new RouterosAPI;
        $ros->timeout = 3;
        $ros->attempts = 1;

        try {
            if (! $ros->connect($router->ip, $router->username, legacy_decrypt($router->password))) {
                return response()->json(['online' => false, 'message' => 'Router tidak merespons']);
            }

            $active = match ($order->mode) {
                'pppoe' => $ros->comm('/ppp/active/getall', ['.proplist' => '.id', '?name' => $order->pppoe_user]),
                'hotspot' => $ros->comm('/ip/hotspot/active/print', ['?user' => $order->pppoe_user]),
                default => [],
            };
            $ros->disconnect();
            $online = ! empty($active[0]['.id']) || ! empty($active[0]);

            return response()->json([
                'online' => $online,
                'message' => $online ? 'Pelanggan online' : 'Pelanggan offline',
            ]);
        } catch (\Throwable $e) {
            try {
                $ros->disconnect();
            } catch (\Throwable) {
            }

            return response()->json(['online' => false, 'message' => 'Gagal menghubungi router']);
        }
    }

    // GET admin/customer/edit/{id} (CI4: AdminController::customer_edit)
    public function customer_edit($id = null)
    {
        if ($id == null) {
            return redirect('admin/customers');
        }

        $getCustomer = Order::where('idpel', $id)->get();

        if ($getCustomer->isEmpty()) {
            return redirect('admin/customers');
        }

        $dataCustomers = $getCustomer->last();
        $mode = $dataCustomers->mode;
        $id_router = $dataCustomers->id_router;

        if (Router::count() === 0) {
            return redirect('admin/customers')->with('auth_errors', ['Tidak ada server pada database, silahkan tambah server terlebih dahulu']);
        }

        $routerData = Router::find($id_router);

        if (! $routerData) {
            return redirect('admin/customers');
        }

        $ros = new RouterosAPI;

        if (! $ros->connect($routerData->ip, $routerData->username, legacy_decrypt($routerData->password))) {
            return redirect('admin/customers')->with('auth_errors', ['Server tidak merespon']);
        }

        if ($mode == 'pppoe') {
            $pppsecret = $ros->comm('/ppp/secret/print');
        } elseif ($mode == 'hotspot') {
            $pppsecret = $ros->comm('/ip/hotspot/user/print');
        } else {
            return redirect('admin/customers');
        }

        $getodp = Odp::query()->orderBy('nama')->get();
        $selectedOdp = OdpAssignment::resolve($getodp, $dataCustomers->nama_odp);

        return view('admin.customer.edit', [
            'title' => 'Detail Pelanggan',
            'customer' => Order::where('idpel', $id)->first(),
            'datacustomer' => Order::where('idpel', $id)->get(),
            'getservice' => Service::where('status', 'Tersedia')->orderBy('id', 'ASC')->get(),
            'pppoeuser' => $pppsecret,
            'getpool' => Pool::all(),
            'getodp' => $getodp,
            'selectedOdpId' => $selectedOdp?->id,
        ] + $this->websiteData());
    }

    // POST admin/customer/update (CI4: AdminController::customerUpdateData)
    public function customerUpdateData(Request $request)
    {
        $name = $request->input('name');
        $idpel = $request->input('idpel');
        $status = $request->input('status');
        $paket = $request->input('paket');

        $dataCustomer = Order::where('idpel', $idpel)->first();

        if (! $dataCustomer) {
            return redirect('admin/customers');
        }

        $pppoe = $dataCustomer->pppoe_user;
        $dataRouter = Router::find($dataCustomer->id_router);

        if (! $dataRouter) {
            return redirect('admin/customer/edit/'.$idpel)->with('auth_errors', ['Router pelanggan tidak ditemukan']);
        }

        if ($paket == null) {
            return redirect('admin/customer/edit/'.$idpel)->with('auth_errors', ['Pilih paket untuk mengubah']);
        }

        $ros = new RouterosAPI;

        if (! $ros->connect($dataRouter->ip, $dataRouter->username, legacy_decrypt($dataRouter->password))) {
            return redirect('admin/customers')->with('auth_errors', ['Server tidak merespon ']);
        }

        if ($status == 'Berhenti') {
            $ros->comm('/ppp/secret/disable', [
                'numbers' => $pppoe,
            ]);

            Order::where('idpel', $idpel)->update(['status' => 'Berhenti']);
            User::where('nama', $name)->update(['status_account' => 'Berhenti']);

            return redirect('admin/customer/edit/'.$idpel)->with('success', ['Berhasil mengupdate data']);
        }

        if ($pppoe == null) {
            return redirect('admin/customer/edit/'.$idpel)->with('auth_errors', ['Harap sinkronkan pppoe user terlebih dahulu']);
        }

        $service = Service::where('paket', $paket)->get()->last();
        $ppprofile = $service->ppp_profile ?? null;

        if ($ppprofile == null) {
            return redirect('admin/services')->with('auth_errors', ['Harap sinkronkan paket terlebih dahulu']);
        }

        $getsecretid = $ros->comm('/ppp/secret/getall', [
            '.proplist' => '.id',
            '?name' => $pppoe,
        ]);

        $updatesecret = $ros->comm('/ppp/secret/set', [
            '.id' => $getsecretid[0]['.id'] ?? '',
            'profile' => $ppprofile,
        ]);

        if (is_array($updatesecret) && isset($updatesecret['!trap'][0]['message'])) {
            return redirect('admin/customer/edit/'.$idpel)->with('auth_errors', [$updatesecret['!trap'][0]['message']]);
        }

        $active = $ros->comm('/ppp/active/getall', [
            '.proplist' => '.id',
            '?name' => $pppoe,
        ]);

        if (! empty($active) && isset($active[0]) && isset($active[0]['.id'])) {
            $ros->comm('/ppp/active/remove', [
                '.id' => $active[0]['.id'],
            ]);
        }

        Order::where('idpel', $idpel)->update(['paket' => $paket]);

        return redirect('admin/customer/edit/'.$idpel)->with('success', ['Berhasil mengupdate data']);
    }

    // POST admin/customer/update/bio — update biodata pelanggan (nama, email, nomor, alamat).
    // Tidak menyentuh Mikrotik. Nama & email disinkronkan ke tabel users agar relasi
    // (join daftar pelanggan & Ganti Password) tetap konsisten.
    public function customerUpdateBio(Request $request)
    {
        $idpel = $request->input('idpel');

        $order = Order::where('idpel', $idpel)->first();
        if (! $order) {
            return redirect('admin/customers')->with('auth_errors', ['Data pelanggan tidak ditemukan']);
        }

        $validated = $request->validate([
            'nama' => 'required|string|max:150',
            'email' => 'nullable|email|max:150',
            'nomor' => 'nullable|string|max:30',
            'alamat' => 'nullable|string|max:255',
        ], [
            'nama.required' => 'Nama pelanggan wajib diisi',
            'email.email' => 'Format email tidak valid',
        ]);

        // Simpan identitas lama untuk mencari baris users yang benar sebelum diubah.
        $oldNama = $order->nama;
        $oldEmail = $order->email;

        $order->update([
            'nama' => $validated['nama'],
            'email' => $validated['email'] ?? $order->email,
            'nomor' => $validated['nomor'] ?? $order->nomor,
            'alamat' => $validated['alamat'] ?? $order->alamat,
        ]);

        // Sinkronkan ke akun users (cari via email lama dulu, fallback nama lama).
        $userQuery = User::query();
        if (! empty($oldEmail)) {
            $userQuery->where('email', $oldEmail);
        } else {
            $userQuery->where('nama', $oldNama);
        }

        $userQuery->update([
            'nama' => $validated['nama'],
            'email' => $validated['email'] ?? $oldEmail,
            'nomor' => $validated['nomor'] ?? null,
        ]);

        return redirect('admin/customer/edit/'.$idpel)->with('success', ['Berhasil mengupdate biodata pelanggan']);
    }

    // POST admin/customer/gantipass/{id} (CI4: AdminController::customerUpdatePassword)
    public function customerUpdatePassword(Request $request, $id)
    {
        $account = User::where('id', $id)->where('level', 'user')->first();

        if (! $account) {
            return redirect('admin/customers')->with('auth_errors', ['Akun pelanggan tidak ditemukan']);
        }

        $account->update([
            'password' => Hash::make((string) $request->input('password')),
        ]);

        return redirect('admin/customers')->with('success', ['Berhasil mengupdate password tersebut']);
    }

    // GET admin/customer/delete/{id} (CI4: AdminController::customer_delete)
    public function customer_delete($id = null)
    {
        if ($id == null) {
            return redirect('admin/customers');
        }

        $datacustomer = Order::where('id', $id)->first();

        if (! $datacustomer) {
            return redirect('admin/customers');
        }

        $email = $datacustomer->email;
        $idrouter = $datacustomer->id_router;
        $pppoeuser = $datacustomer->pppoe_user;
        $mode = $datacustomer->mode;

        $datausers = User::where('email', $email)->first();

        if (! $datausers) {
            return redirect('admin/customers')->with('auth_errors', ['Pengguna tidak ditemukan']);
        }

        $idusers = $datausers->id;

        $routerData = Router::find($idrouter);

        if (! $routerData) {
            return redirect('admin/customers')->with('auth_errors', ['Router tidak ditemukan']);
        }

        $hostserver = $routerData->ip;
        $usernameserver = $routerData->username;
        $passwordserver = legacy_decrypt($routerData->password);

        $ros = new RouterosAPI;

        if ($mode == 'pppoe') {
            if (! $ros->connect($hostserver, $usernameserver, $passwordserver)) {
                return redirect('admin/customers')->with('auth_errors', ['Gagal menghapus data, router tidak terkoneksi']);
            }

            $ros->comm('/ppp/secret/remove', [
                'numbers' => $pppoeuser,
            ]);

            $active = $ros->comm('/ppp/active/getall', [
                '.proplist' => '.id',
                '?name' => $pppoeuser,
            ]);

            if (! empty($active)) {
                foreach ($active as $act) {
                    if (isset($act['.id'])) {
                        $ros->comm('/ppp/active/remove', [
                            '.id' => $act['.id'],
                        ]);
                    }
                }
            }
        } elseif ($mode == 'hotspot') {
            if (! $ros->connect($hostserver, $usernameserver, $passwordserver)) {
                return redirect('admin/customers')->with('auth_errors', ['Gagal menghapus data, router tidak terkoneksi']);
            }

            $ros->comm('/ip/hotspot/user/remove', [
                'numbers' => $pppoeuser,
            ]);

            $active = $ros->comm('/ip/hotspot/active/print', [
                '?user' => $pppoeuser,
            ]);

            if (! empty($active)) {
                foreach ($active as $act) {
                    if (isset($act['.id'])) {
                        $ros->comm('/ip/hotspot/active/remove', [
                            '.id' => $act['.id'],
                        ]);
                    }
                }
            }
        } else {
            return redirect('admin/customers')->with('auth_errors', ['Mode tidak dikenal']);
        }

        Order::where('id', $id)->delete();
        User::where('id', $idusers)->delete();

        return redirect('admin/customers')->with('success', ['Berhasil menghapus data']);
    }

    // POST admin/customer/update/pppoe (CI4: AdminController::customerUpdatePPPOE)
    public function customerUpdatePPPOE(Request $request)
    {
        $idpel = trim((string) $request->input('idpel'));
        $uname = $request->input('user_pppoe');

        Order::where('idpel', $idpel)->update([
            'pppoe_user' => $uname,
        ]);

        return redirect('admin/customer/edit/'.$idpel)->with('success_pppoe', ['Berhasil mengupdate data pppoe']);
    }

    // POST admin/customer/update/odp (CI4: AdminController::customerupdateODP)
    public function customerUpdateODP(Request $request)
    {
        $idpel = trim((string) $request->input('idpel'));
        $portOdp = (int) $request->input('port_odp');

        $customer = Order::where('idpel', $idpel)->first();
        if (! $customer) {
            return redirect('admin/customers')->with('auth_errors', ['Pelanggan tidak ditemukan']);
        }

        $odps = Odp::query()->get();
        $odp = $request->filled('odp_id')
            ? $odps->firstWhere('id', (int) $request->input('odp_id'))
            : OdpAssignment::resolve($odps, $request->input('nama_odp'));
        if (! $odp) {
            return redirect('admin/customer/edit/'.$idpel)->with('errors_odp', ['Data ODP tidak ditemukan atau nama ODP ambigu']);
        }
        if (! OdpAssignment::isStoredNameUnique($odps, $odp)) {
            return redirect('admin/customer/edit/'.$idpel)->with('errors_odp', ['Nama ODP memiliki 15 karakter awal yang sama dengan ODP lain. Ubah nama ODP agar alokasi pelanggan tidak ambigu.']);
        }

        $namaOdp = OdpAssignment::storedName($odp->nama);
        if ($portOdp < 1 || $portOdp > (int) $odp->port) {
            return redirect('admin/customer/edit/'.$idpel)->with('errors_odp', ['Port ODP di luar kapasitas']);
        }

        $updated = DB::transaction(function () use ($idpel, $namaOdp, $portOdp) {
            $customer = Order::where('idpel', $idpel)->lockForUpdate()->first();
            if (! $customer) {
                return false;
            }

            $portDipakai = Order::query()
                ->whereRaw('LOWER(TRIM(nama_odp)) = ?', [OdpAssignment::key($namaOdp)])
                ->where('idpel', '!=', $idpel)
                ->lockForUpdate()
                ->get(['port_odp'])
                ->contains(fn ($order) => ctype_digit(trim((string) $order->port_odp)) && (int) $order->port_odp === $portOdp);

            if ($portDipakai) {
                return false;
            }

            $customer->update([
                'nama_odp' => $namaOdp,
                'port_odp' => $portOdp,
            ]);

            return true;
        });

        if (! $updated) {
            return redirect('admin/customer/edit/'.$idpel)->with('errors_odp', ['Port ODP sudah digunakan pelanggan lain']);
        }

        return redirect('admin/customer/edit/'.$idpel)->with('success_odp', ['Berhasil mengupdate data odp']);
    }

    // POST /get-ports (CI4: AdminController::getPorts)
    public function getPorts(Request $request)
    {
        $odps = Odp::query()->get();
        $odp = $request->filled('odp_id')
            ? $odps->firstWhere('id', (int) $request->input('odp_id'))
            : OdpAssignment::resolve($odps, $request->input('namaODP'));

        if ($odp && OdpAssignment::isStoredNameUnique($odps, $odp)) {
            return response()->json([
                'status' => 'success',
                'jumlah_port' => $odp->port,
            ]);
        }

        return response()->json([
            'status' => 'error',
            'jumlah_port' => 0,
        ]);
    }

    // GET /get-used-ports (CI4: AdminController::getUsedPorts)
    public function getUsedPorts()
    {
        $usedPorts = [];
        $odps = Odp::query()->get(['id', 'nama']);

        $exactMap = [];
        foreach ($odps as $odp) {
            $exactMap[OdpAssignment::normalizedName($odp->nama)] = $odp;
        }

        $prefixMap = OdpAssignment::uniqueByStoredName($odps);

        foreach (Order::whereNotNull('nama_odp')
            ->where('nama_odp', '!=', '')
            ->whereNotNull('port_odp')
            ->where('port_odp', '!=', '')
            ->get(['nama_odp', 'port_odp', 'idpel']) as $order) {
            $normalized = OdpAssignment::normalizedName($order->nama_odp);
            $odp = $exactMap[$normalized] ?? $prefixMap->get(OdpAssignment::key($order->nama_odp));
            $portODP = (string) $order->port_odp;

            if (! $odp || ! ctype_digit(trim($portODP)) || (int) $portODP < 1) {
                continue;
            }

            $normalizedPort = (string) (int) $portODP;

            if (! isset($usedPorts[$odp->id])) {
                $usedPorts[$odp->id] = [];
            }

            $usedPorts[$odp->id][$normalizedPort] = [
                'idpel' => $order->idpel,
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => $usedPorts,
        ]);
    }

    // GET admin/export — export data pelanggan ke Excel.
    // Catatan: di CI4 route ini menunjuk AdminController::export yang tidak ada (dead route),
    // di sini diimplementasikan sebagai export daftar customer memakai PhpSpreadsheet.
    public function export()
    {
        $customers = Order::all();
        $odpByAssignment = OdpAssignment::uniqueByStoredName(Odp::query()->get(['id', 'nama']));
        $customers->each(function (Order $customer) use ($odpByAssignment) {
            $customer->nama_odp = $odpByAssignment->get(OdpAssignment::key($customer->nama_odp))?->nama ?? $customer->nama_odp;
        });

        $spreadsheet = new Spreadsheet;
        $worksheet = $spreadsheet->getActiveSheet();

        $header = ['No', 'ID Pelanggan', 'Nama Pelanggan', 'Layanan', 'Mode', 'Nomor', 'Email', 'Alamat', 'Status', 'Tanggal Daftar', 'Masa Aktif', 'Nama ODP', 'Port ODP'];

        $colIndex = 'A';
        foreach ($header as $cellData) {
            $worksheet->setCellValue($colIndex.'1', $cellData);
            $colIndex++;
        }

        $rowIndex = 2;
        $no = 1;

        foreach ($customers as $row) {
            $rowData = [
                $no++,
                $row->idpel,
                $row->nama,
                $row->paket,
                $row->mode,
                $row->nomor,
                $row->email,
                $row->alamat,
                $row->status,
                $row->date,
                $row->expdate,
                $row->nama_odp,
                $row->port_odp,
            ];

            $colIndex = 'A';
            foreach ($rowData as $cellData) {
                $worksheet->setCellValue($colIndex.$rowIndex, $cellData);
                $colIndex++;
            }
            $rowIndex++;
        }

        foreach (range('A', $worksheet->getHighestDataColumn()) as $col) {
            $worksheet->getColumnDimension($col)->setAutoSize(true);
        }

        $highestRow = $worksheet->getHighestRow();
        $highestColumn = $worksheet->getHighestColumn();
        $cellRange = 'A1:'.$highestColumn.$highestRow;
        $worksheet->getStyle($cellRange)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'data_pelanggan.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    // GET admin/customer/filter — filter pelanggan berdasarkan router (CI4: ambilDataFilterCustomer)
    public function ambilDataFilterCustomer(Request $request)
    {
        if (! $request->boolean('show_data')) {
            return response()->json(['status' => 'success', 'data' => []]);
        }

        $routerId = $request->get('router');
        $query = Order::select('orders.*', 'router.nama as router_nama')
            ->leftJoin('router', 'orders.id_router', '=', 'router.id');

        if ($routerId && $routerId !== 'all') {
            $query->where('orders.id_router', $routerId);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->get(),
        ]);
    }

    // GET admin/customer/exportexcel — export data pelanggan ke Excel (CI4: exportExcel)
    public function exportExcel(Request $request)
    {
        $routerId = $request->get('router');
        $query = Order::all();

        if ($routerId && $routerId !== 'all') {
            $query = Order::where('id_router', $routerId)->get();
        }

        $spreadsheet = new Spreadsheet;
        $worksheet = $spreadsheet->getActiveSheet();

        $header = ['No', 'ID Pelanggan', 'Nama', 'Layanan', 'Mode', 'Nomor', 'Email', 'Alamat', 'Status', 'Tanggal Daftar', 'Masa Aktif'];
        $colIndex = 'A';
        foreach ($header as $cellData) {
            $worksheet->setCellValue($colIndex.'1', $cellData);
            $colIndex++;
        }

        $rowIndex = 2;
        $no = 1;
        foreach ($query as $row) {
            $rowData = [
                $no++,
                $row->idpel,
                $row->nama,
                $row->paket,
                $row->mode,
                $row->nomor,
                $row->email,
                $row->alamat,
                $row->status,
                $row->date,
                $row->expdate,
            ];
            $colIndex = 'A';
            foreach ($rowData as $cellData) {
                $worksheet->setCellValue($colIndex.$rowIndex, $cellData);
                $colIndex++;
            }
            $rowIndex++;
        }

        foreach (range('A', $worksheet->getHighestDataColumn()) as $col) {
            $worksheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'data_pelanggan.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    // GET admin/customer/export — export data pelanggan ke CSV (CI4: exportCSV)
    public function exportCSV(Request $request)
    {
        $routerId = $request->get('router');
        $query = Order::all();

        if ($routerId && $routerId !== 'all') {
            $query = Order::where('id_router', $routerId)->get();
        }

        $filename = 'data_pelanggan.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['No', 'ID Pelanggan', 'Nama', 'Layanan', 'Mode', 'Nomor', 'Email', 'Alamat', 'Status', 'Tanggal Daftar', 'Masa Aktif']);
            $no = 1;
            foreach ($query as $row) {
                fputcsv($handle, [
                    $no++,
                    $row->idpel,
                    $row->nama,
                    $row->paket,
                    $row->mode,
                    $row->nomor,
                    $row->email,
                    $row->alamat,
                    $row->status,
                    $row->date,
                    $row->expdate,
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    // POST admin/customer/update/tikor — update titik koordinat (CI4: customerUpdateTikor)
    public function customerUpdateTikor(Request $request)
    {
        $idpel = $request->post('idpel');
        $latitude = $request->post('latitude');
        $longitude = $request->post('longitude');

        Order::where('idpel', $idpel)->update([
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);

        return redirect('admin/customer/edit/'.$idpel)->with('success_tikor', ['Berhasil mengupdate data titik koordinat']);
    }

    // POST admin/getPPPOEUsers — ambil daftar PPPoE user dari router (CI4: getPPPOEUsers)
    public function getPPPOEUsers(Request $request)
    {
        $idrouter = $request->post('routerId');
        $router = Router::find($idrouter);

        if (! $router) {
            return response()->json(['status' => 'error', 'message' => 'Router tidak ditemukan.']);
        }

        $ros = new RouterosAPI;
        $password = legacy_decrypt($router->password);

        if (! $ros->connect($router->ip, $router->username, $password)) {
            return response()->json(['status' => 'error', 'message' => 'Router not connected.']);
        }

        $secrets = $ros->comm('/ppp/secret/print');

        return response()->json([
            'status' => 'success',
            'data' => $secrets,
        ]);
    }

    // POST /get-odp-by-area — ambil daftar ODP berdasarkan kode area (CI4: getODPByArea)
    public function getODPByArea(Request $request)
    {
        $kodeArea = $request->post('kode_area') ?? $request->post('area');

        $columns = Schema::getColumnListing('odp');
        $areaColumn = in_array('kode_area', $columns) ? 'kode_area'
            : (in_array('area', $columns) ? 'area'
            : null);

        $query = Odp::query();
        if ($areaColumn && $kodeArea) {
            $query->where($areaColumn, $kodeArea);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->get(),
        ]);
    }
}
