<?php

namespace App\Http\Controllers\Server;

use App\Http\Controllers\Controller;
use App\Libraries\RouterosAPI;
use App\Models\Router;
use App\Models\Website;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RouterController extends Controller
{
    private RouterosAPI $ros;

    public function __construct()
    {
        $this->ros = new RouterosAPI;
    }

    private function websiteData(): array
    {
        $website = Website::first();

        return [
            'titletext' => $website->title ?? 'LandakNet',
            'logo' => $website->logo ?? '',
        ];
    }

    public function home()
    {
        return view('server.router.home', [
            'title' => 'Management Router',
            'getData' => Router::all(),
        ] + $this->websiteData());
    }

    public function add(Request $request)
    {
        $nama = $request->post('nama');
        $dns = $request->post('dns');
        $host = $request->post('host');
        $username = $request->post('username');
        $password = $request->post('password');
        $interface = $request->post('interface');

        if (empty($nama) || empty($dns) || empty($host) || empty($username) || empty($password) || empty($interface)) {
            return redirect(url('server/router'))->with('auth_errors', ['Harap mengisi semua input']);
        }

        Router::create([
            'nama' => $nama,
            'dns' => $dns,
            'ip' => $host,
            'username' => $username,
            'password' => legacy_encrypt($password),
            'interface' => $interface,
        ]);

        return redirect(url('server/router'))->with('success', ['Berhasil menambahkan data']);
    }

    public function deleteData($id)
    {
        Router::where('id', $id)->delete();

        return redirect(url('server/router'))->with('success', ['Berhasil menghapus data']);
    }

    public function update(Request $request, $id)
    {
        $cek = Router::find($id);

        if ($cek === null) {
            return redirect(url('server/router'))->with('auth_errors', ['Mikrotik tidak ditemukan !']);
        }

        $password = $request->post('password');

        $update = [
            'nama' => $request->post('nama'),
            'dns' => $request->post('dns'),
            'ip' => $request->post('host'),
            'username' => $request->post('username'),
            'interface' => $request->post('interface'),
        ];

        if ($password != null) {
            $update['password'] = legacy_encrypt($password);
        }

        Router::where('id', $id)->update($update);

        return redirect(url('server/router'))->with('success', ['Berhasil memperbaharui data']);
    }

    public function connect($id)
    {
        $cek = Router::find($id);

        if ($cek === null) {
            return redirect(url('server/router'))->with('auth_errors', ['Mikrotik tidak ditemukan !']);
        }

        session([
            'idrouter' => $id,
            'isLoggedIn' => 'connect',
        ]);

        return redirect(url('server/router/dashboard'));
    }

    public function disconnect()
    {
        session()->forget(['idrouter', 'isLoggedIn']);

        return redirect(url('server/router'));
    }

    /**
     * Ambil kredensial router yang sedang terpilih dari session.
     * Return null jika belum connect atau router tidak ditemukan.
     */
    private function currentRouter(): ?Router
    {
        $idrouter = session()->get('idrouter');

        if ($idrouter == null) {
            return null;
        }

        return Router::find($idrouter);
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

    private function rosFirstRow(mixed $response): array
    {
        return $this->rosRows($response)[0] ?? [];
    }

    private function trafficResponse(mixed $response)
    {
        $row = $this->rosFirstRow($response);

        if (empty($row)) {
            return response()->json([
                'message' => 'Data traffic interface tidak diterima dari Mikrotik.',
            ], 422);
        }

        return response()->json([
            ['name' => 'Tx', 'data' => [(int) ($row['rx-bits-per-second'] ?? 0)]],
            ['name' => 'Rx', 'data' => [(int) ($row['tx-bits-per-second'] ?? 0)]],
        ]);
    }

    public function dashboard()
    {
        $router = $this->currentRouter();

        if ($router === null) {
            return redirect(url('server/router'))->with('auth_errors', ['Anda belum klik connect']);
        }

        $nameserver = $router->nama;
        $dnsserver = $router->dns;
        $host = $router->ip;
        $username = $router->username;
        $password = legacy_decrypt($router->password);
        $interface = $router->interface;

        if ($this->ros->connect($host, $username, $password)) {

            // get hotspot info
            $hotspotuser = $this->rosRows($this->ros->comm('/ip/hotspot/user/print'));
            $hotspotactive = $this->rosRows($this->ros->comm('/ip/hotspot/active/print'));
            $hotspotprofile = $this->rosRows($this->ros->comm('/ip/hotspot/user/profile/print'));

            // get ppp info
            $pppsecret = $this->rosRows($this->ros->comm('/ppp/secret/print'));
            $pppactive = $this->rosRows($this->ros->comm('/ppp/active/print'));
            $pppprofile = $this->rosRows($this->ros->comm('/ppp/profile/print'));

            // get log hotspot
            $getlog = $this->rosRows($this->ros->comm('/log/print', [
                '?topics' => 'hotspot,info,debug',
            ]));
            $log = array_reverse($getlog);
            $TotalReg = count($getlog);

            // get mikrotik system clock
            $clock = $this->rosFirstRow($this->ros->comm('/system/clock/print'));
            $timezone = $clock['time-zone-name'] ?? '-';

            // get MikroTik system resource
            $resource = $this->rosFirstRow($this->ros->comm('/system/resource/print'));

            // get routeboard info
            $routerboard = $this->rosFirstRow($this->ros->comm('/system/routerboard/print'));

            $model = $routerboard['model'] ?? '';

            // get interface
            $getinterface = $this->rosRows($this->ros->comm('/interface/print'));

            // get interface db
            $monitor = $interface;

            return view('server.router.dashboard', [
                'title' => 'Dashboard Router',
                'nameserver' => $nameserver,
                'dnsserver' => $dnsserver,
                'hotspotuser' => count($hotspotuser),
                'hotspotactive' => count($hotspotactive),
                'hotspotprofile' => count($hotspotprofile),
                'hotspotlog' => $log,
                'totalhotspotlog' => $TotalReg,
                'pppuser' => count($pppsecret),
                'pppactive' => count($pppactive),
                'pppprofile' => count($pppprofile),
                'clock' => $clock['time'] ?? '-',
                'uptime' => $resource['uptime'] ?? '0s',
                'timezone' => $timezone,
                'model' => $model,
                'architecture' => $resource['architecture-name'] ?? '-',
                'version' => $resource['version'] ?? '-',
                'interface' => $getinterface,
                'traffics' => $monitor,
            ] + $this->websiteData());
        }

        return redirect(url('server/router'))->with('auth_errors', ['Mikrotik not connected !']);
    }

    public function users()
    {
        $router = $this->currentRouter();

        if ($router === null) {
            return redirect(url('server/router'))->with('auth_errors', ['Anda belum klik connect']);
        }

        if ($this->ros->connect($router->ip, $router->username, legacy_decrypt($router->password))) {

            // get hotspot info
            $hotspotuser = $this->ros->comm('/ip/hotspot/user/print', []);

            return view('server.router.hotspot.user.list', [
                'title' => 'Hotspot Users',
                'totalhotspotuser' => count($hotspotuser),
                'hotspotuser' => $hotspotuser,
            ] + $this->websiteData());
        }

        return redirect(url('server/router/setting'))->with('auth_errors', ['Mikrotik not connected !']);
    }

    public function deleteUser($id)
    {
        $router = $this->currentRouter();

        if ($router === null) {
            return redirect(url('server/router'))->with('auth_errors', ['Anda belum klik connect']);
        }

        if ($this->ros->connect($router->ip, $router->username, legacy_decrypt($router->password))) {

            $this->ros->comm('/ip/hotspot/user/remove', [
                '.id' => '*'.$id,
            ]);

            return redirect(url('server/router/hotspot/users'))->with('success', ['Berhasil menghapus user']);
        }

        return redirect(url('server/router/setting'))->with('auth_errors', ['Mikrotik not connected !']);
    }

    public function addUser()
    {
        $router = $this->currentRouter();

        if ($router === null) {
            return redirect(url('server/router'))->with('auth_errors', ['Anda belum klik connect']);
        }

        if ($this->ros->connect($router->ip, $router->username, legacy_decrypt($router->password))) {

            // get hotspot info
            $hotspotuser = $this->ros->comm('/ip/hotspot/user/print', []);

            $server = $this->ros->comm('/ip/hotspot/print');
            $profile = $this->ros->comm('/ip/hotspot/user/profile/print');

            return view('server.router.hotspot.user.add', [
                'title' => 'Hotspot Add User',
                'hotspotuser' => $hotspotuser,
                'server' => $server,
                'profile' => $profile,
            ] + $this->websiteData());
        }

        return redirect(url('server/router/setting'))->with('auth_errors', ['Mikrotik not connected !']);
    }

    public function prosesAddUser(Request $request)
    {
        $router = $this->currentRouter();

        if ($router === null) {
            return redirect(url('server/router'))->with('auth_errors', ['Anda belum klik connect']);
        }

        $profile = $request->post('profile');
        $code = $request->post('code');

        if (empty($code) || empty($profile)) {
            return redirect(url('server/router/hotspot/user/add'))->with('auth_errors', ['Gagal membuat user']);
        }

        if ($this->ros->connect($router->ip, $router->username, legacy_decrypt($router->password))) {
            $server = $request->post('server');
            $timelimit = $request->post('timelimit');

            $time = $timelimit == '' || $timelimit === null ? '0' : $timelimit;

            $this->ros->comm('/ip/hotspot/user/add', [
                'server' => $server,
                'name' => $code,
                'password' => $code,
                'profile' => $profile,
                'limit-uptime' => $time,
            ]);

            return redirect(url('server/router/hotspot/user/add'))->with('success', ['Berhasil membuat user baru']);
        }
    }

    public function profile()
    {
        $router = $this->currentRouter();

        if ($router === null) {
            return redirect(url('server/router'))->with('auth_errors', ['Anda belum klik connect']);
        }

        if ($this->ros->connect($router->ip, $router->username, legacy_decrypt($router->password))) {

            // get profile
            $getprofile = $this->ros->comm('/ip/hotspot/user/profile/print');

            return view('server.router.hotspot.profile.list', [
                'title' => 'Hotspot Profile',
                'totalprofile' => count($getprofile),
                'getprofile' => $getprofile,
            ] + $this->websiteData());
        }

        return redirect(url('server/router/setting'))->with('auth_errors', ['Mikrotik not connected !']);
    }

    public function prosesAddProfile(Request $request)
    {
        $router = $this->currentRouter();

        if ($router === null) {
            return redirect(url('server/router'))->with('auth_errors', ['Anda belum klik connect']);
        }

        $name = $request->post('name');
        $ratelimit = $request->post('ratelimit');
        $uptime = $request->post('uptime');
        $price = $request->post('price');
        $mac = $request->post('mac');
        $shared = $request->post('shared');

        if ($mac == 'Ya') {
            $lock = '; [:local mac $"mac-address"; /ip hotspot user set mac-address=$mac [find where name=$user]]';
        } else {
            $lock = '';
        }

        $scheduler = '{:local usernya $user;:if ([/system schedule find name=$usernya]="") do={/system schedule add name=$usernya interval='.$uptime.' on-event="/ip hotspot user remove [find name=$usernya]\r\n/ip hotspot active remove [find user=$usernya]\r\n/system schedule remove [find name=$usernya]"}}'.$lock;

        // Tabel services_voucher belum tentu ada di skema ini. Cek dulu supaya
        // profile MikroTik tidak terlanjur dibuat saat insert DB akan gagal
        // (mencegah state MikroTik & DB tidak sinkron).
        if (! Schema::hasTable('services_voucher')) {
            return redirect(url('server/router/hotspot/profile'))->with('auth_errors', ['Tabel services_voucher belum ada di database ini — profile tidak dibuat.']);
        }

        if ($this->ros->connect($router->ip, $router->username, legacy_decrypt($router->password))) {
            $this->ros->comm('/ip/hotspot/user/profile/add', [
                'name' => $name,
                'rate-limit' => $ratelimit,
                'shared-users' => $shared,
                'on-login' => $scheduler,
                'status-autorefresh' => '1m',
            ]);

            DB::table('services_voucher')->insert([
                'service' => $name,
                'shared' => $shared,
                'ratelimit' => $ratelimit,
                'uptime' => $uptime,
                'harga' => $price,
                'status' => 'Active',
            ]);

            return redirect(url('server/router/hotspot/profile'))->with('success', ['Berhasil membuat profile baru']);
        }
    }

    public function hotspotActive()
    {
        $router = $this->currentRouter();

        if ($router === null) {
            return redirect(url('server/router'))->with('auth_errors', ['Anda belum klik connect']);
        }

        if ($this->ros->connect($router->ip, $router->username, legacy_decrypt($router->password))) {

            // get hotspot info
            $hotspotactive = $this->ros->comm('/ip/hotspot/active/print');

            return view('server.router.hotspot.active', [
                'title' => 'Hotspot Active',
                'totalhotspotactive' => count($hotspotactive),
                'hotspotactive' => $hotspotactive,
            ] + $this->websiteData());
        }

        return redirect(url('server/router/setting'))->with('auth_errors', ['Mikrotik not connected !']);
    }

    public function hotspotLog()
    {
        $router = $this->currentRouter();

        if ($router === null) {
            return redirect(url('server/router'))->with('auth_errors', ['Anda belum klik connect']);
        }

        if ($this->ros->connect($router->ip, $router->username, legacy_decrypt($router->password))) {

            $getlog = $this->ros->comm('/log/print', [
                '?topics' => 'hotspot,info,debug',
            ]);
            $log = array_reverse($getlog);
            $TotalReg = count($getlog);

            return view('server.router.hotspot.log', [
                'title' => 'Hotspot Log',
                'log' => $log,
                'totalhotspotlog' => $TotalReg,
            ] + $this->websiteData());
        }

        return redirect(url('server/router/setting'))->with('auth_errors', ['Mikrotik not connected !']);
    }

    public function hotspotHost()
    {
        $router = $this->currentRouter();

        if ($router === null) {
            return redirect(url('server/router'))->with('auth_errors', ['Anda belum klik connect']);
        }

        if ($this->ros->connect($router->ip, $router->username, legacy_decrypt($router->password))) {

            $gethosts = $this->ros->comm('/ip/hotspot/host/print');

            $TotalReg = count($gethosts);

            return view('server.router.hotspot.host', [
                'title' => 'Hotspot Host',
                'hosts' => $gethosts,
                'totalhost' => $TotalReg,
            ] + $this->websiteData());
        }

        return redirect(url('server/router/setting'))->with('auth_errors', ['Mikrotik not connected !']);
    }

    public function pppProfile()
    {
        $router = $this->currentRouter();

        if ($router === null) {
            return redirect(url('server/router'))->with('auth_errors', ['Anda belum klik connect']);
        }

        if ($this->ros->connect($router->ip, $router->username, legacy_decrypt($router->password))) {

            // get ppp profile info
            $getprofile = $this->ros->comm('/ppp/profile/print');
            $countprofile = count($getprofile);

            $getpool = $this->ros->comm('/ip/pool/print');

            $getallqueue = $this->ros->comm('/queue/simple/print', [
                '?dynamic' => 'false',
            ]);

            return view('server.router.ppp.profile', [
                'title' => 'PPP Profile',
                'totalprofile' => $countprofile,
                'getprofile' => $getprofile,
                'pool' => $getpool,
                'queue' => $getallqueue,
            ] + $this->websiteData());
        }

        return redirect(url('server/router/setting'))->with('auth_errors', ['Mikrotik not connected !']);
    }

    public function pppSecret()
    {
        $router = $this->currentRouter();

        if ($router === null) {
            return redirect(url('server/router'))->with('auth_errors', ['Anda belum klik connect']);
        }

        if ($this->ros->connect($router->ip, $router->username, legacy_decrypt($router->password))) {

            // get ppp secret info
            $getsecret = $this->ros->comm('/ppp/secret/print');
            $countsecret = count($getsecret);
            $getprofile = $this->ros->comm('/ppp/profile/print');

            return view('server.router.ppp.secret', [
                'title' => 'PPP Secret',
                'totalsecret' => $countsecret,
                'getsecret' => $getsecret,
                'profile' => $getprofile,
            ] + $this->websiteData());
        }

        return redirect(url('server/router/setting'))->with('auth_errors', ['Mikrotik not connected !']);
    }

    public function pppActive()
    {
        $router = $this->currentRouter();

        if ($router === null) {
            return redirect(url('server/router'))->with('auth_errors', ['Anda belum klik connect']);
        }

        if ($this->ros->connect($router->ip, $router->username, legacy_decrypt($router->password))) {

            // get ppp active info
            $getsecret = $this->ros->comm('/ppp/active/print');
            $countsecret = count($getsecret);

            return view('server.router.ppp.active', [
                'title' => 'PPP Active',
                'totalsecret' => $countsecret,
                'getsecret' => $getsecret,
            ] + $this->websiteData());
        }

        return redirect(url('server/router/setting'))->with('auth_errors', ['Mikrotik not connected !']);
    }

    public function getTraffic()
    {
        $router = $this->currentRouter() ?? Router::first();

        if ($router && $this->ros->connect($router->ip, $router->username, legacy_decrypt($router->password))) {
            $getinterface = $this->ros->comm('/interface/monitor-traffic', [
                'interface' => $router->interface,
                'once' => '',
            ]);

            return $this->trafficResponse($getinterface);
        }

        return response()->json([
            'message' => 'Router belum terhubung atau tidak bisa diakses.',
        ], 503);
    }

    public function getTrafficPPPOE($pppoe)
    {
        $order = DB::table('orders')->where('pppoe_user', $pppoe)->first();
        $router = $order?->id_router ? Router::find($order->id_router) : $this->currentRouter();

        if ($router && $this->ros->connect($router->ip, $router->username, legacy_decrypt($router->password))) {
            $getinterface = $this->ros->comm('/interface/monitor-traffic', [
                'interface' => '<pppoe-'.$pppoe.'>',
                'once' => '',
            ]);

            return $this->trafficResponse($getinterface);
        }

        return response()->json([
            'message' => 'Router belum terhubung atau tidak bisa diakses.',
        ], 503);
    }

    public function getBandwithPPPOE($pppoe)
    {
        return $this->getTrafficPPPOE($pppoe);
    }

    public function getTrafficFixes(Request $request)
    {
        $interface = $request->post('interface');
        $router = $this->currentRouter() ?? Router::first();

        if ($router && $this->ros->connect($router->ip, $router->username, legacy_decrypt($router->password))) {
            $targetInterface = $interface ?: $router->interface;
            $getinterface = $this->ros->comm('/interface/monitor-traffic', [
                'interface' => $targetInterface,
                'once' => '',
            ]);

            return $this->trafficResponse($getinterface);
        }

        return response()->json([
            'message' => 'Router belum terhubung atau tidak bisa diakses.',
        ], 503);
    }
}
