<?php

namespace App\Http\Controllers\Server;

use App\Http\Controllers\Controller;
use App\Libraries\ACSRequest;
use App\Models\Website;
use App\Services\AcsDeviceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class AcsController extends Controller
{
    private function websiteData(): array
    {
        $website = Website::first();

        return [
            'titletext' => $website->title ?? 'LandakNet',
            'logo' => $website->logo ?? '',
        ];
    }

    public function home(Request $request)
    {
        return view('admin.acs.home', [
            'title' => 'Dashboard ACS',
            'getData' => DB::table('acs')->get(),
        ] + $this->websiteData());
    }

    public function add(Request $request)
    {
        DB::table('acs')->insert([
            'nama' => $request->post('nama'),
            'url' => $request->post('host'),
        ]);

        return redirect(url('server/acs'))->with('success', ['Berhasil menambahkan data']);
    }

    public function deleteData($id)
    {
        DB::table('acs')->where('id', $id)->delete();

        return redirect(url('server/acs'))->with('success', ['Berhasil menghapus data']);
    }

    public function update(Request $request, $id)
    {
        DB::table('acs')->where('id', $id)->update([
            'nama' => $request->post('nama'),
            'url' => $request->post('host'),
        ]);

        return redirect(url('server/acs'))->with('success', ['Berhasil memperbaharui data']);
    }

    public function connect($id = null)
    {
        $row = DB::table('acs')->where('id', $id)->first();

        if ($row === null) {
            return redirect(url('server/acs'))->with('auth_errors', ['Data tidak ditemukan !']);
        }

        Session::put([
            'idrouter' => $id,
            'isLoggedIn' => 'connect',
        ]);

        return redirect(url('server/acs/dashboard'));
    }

    public function dashboard(Request $request)
    {
        $idrouter = Session::get('idrouter');

        if ($idrouter == null) {
            return redirect(url('server/acs'))->with('auth_errors', ['Anda belum klik connect']);
        }

        $acs = DB::table('acs')->where('id', $idrouter)->first();
        $host = $acs->url ?? null;

        if (empty($host)) {
            return redirect(url('server/acs'))->with('auth_errors', ['URL tidak ditemukan']);
        }

        try {
            $deviceData = app(AcsDeviceService::class)->getDashboardDevices($host);
        } catch (\RuntimeException) {
            return redirect(url('server/acs'))->with('auth_errors', ['Gagal mengambil data dari API atau respons tidak valid']);
        }

        $onlineCount = count(array_filter($deviceData, fn (array $device): bool => $device['online']));
        $offlineCount = count($deviceData) - $onlineCount;
        $criticalRxPowerCount = count(array_filter(
            $deviceData,
            fn (array $device): bool => $device['rxPowerClass'] === 'rx-power-critical'
        ));

        return view('admin.acs.dashboard', [
            'title' => 'GenieACS',
            'devices' => $deviceData,
            'online' => $onlineCount,
            'offline' => $offlineCount,
            'criticalRxPowerCount' => $criticalRxPowerCount,
        ] + $this->websiteData());
    }

    public function edit(Request $request)
    {
        $idrouter = Session::get('idrouter');

        if ($idrouter == null) {
            return redirect(url('server/acs'))->with('auth_errors', ['Anda belum klik connect']);
        }

        $acs = DB::table('acs')->where('id', $idrouter)->first();
        $host = $acs->url ?? null;

        if (empty($host)) {
            return redirect(url('server/acs'))->with('auth_errors', ['URL tidak ditemukan']);
        }

        $acsRequest = new ACSRequest($host);
        $deviceId = $request->post('deviceId');
        $devices = $acsRequest->getDeviceById($deviceId);

        $ssid = '';
        $clientInfoList = [];

        if (! empty($devices) && isset($devices[0]['InternetGatewayDevice']['LANDevice']) && is_array($devices[0]['InternetGatewayDevice']['LANDevice'])) {
            foreach ($devices[0]['InternetGatewayDevice']['LANDevice'] as $lanDevice) {
                if (empty($ssid)) {
                    $ssid = $this->getSSID($lanDevice);
                }

                $deviceInfo = $this->getDeviceInfo($lanDevice);
                if (! empty($deviceInfo)) {
                    $clientInfoList[] = $this->getClientInfo($deviceInfo);
                }
            }

            $getdeviceuptime = $devices[0]['VirtualParameters']['getdeviceuptime']['_value'] ?? '';
            $formattedUptime = $getdeviceuptime !== '' ? $this->convertToFullWords($getdeviceuptime) : '';

            return view('admin.acs.modem', [
                'title' => 'Akses Kontrol Modem',
                'id' => $devices[0]['_id'],
                'idrouter' => $idrouter,
                'ssid' => $ssid,
                'deviceUptime' => $formattedUptime,
                'clientInfoList' => $clientInfoList,
            ] + $this->websiteData());
        }

        return redirect(url('server/acs'))->with('auth_errors', ['Data tidak ditemukan']);
    }

    public function changeSsid(Request $request)
    {
        $idrouter = Session::get('idrouter');

        if ($idrouter == null) {
            return redirect(url('server/acs'))->with('auth_errors', ['Anda belum klik connect']);
        }

        $acs = DB::table('acs')->where('id', $idrouter)->first();
        $host = $acs->url ?? null;

        if (empty($host)) {
            return redirect(url('server/acs'))->with('auth_errors', ['URL tidak ditemukan']);
        }

        $acsRequest = new ACSRequest($host);

        $newssid = $request->post('ssid');
        $deviceId = $request->post('deviceId');

        $devices = $acsRequest->getDeviceById($deviceId);

        if ($devices) {
            $id_device = $devices[0]['_id'];
            $param = [
                ['InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.SSID', $newssid],
            ];

            $acsRequest->setParameterValues($param, $id_device);

            return redirect(url('server/acs/dashboard'))->with('success', ['Berhasil melakukan pergantian nama wifi, ID : '.$id_device]);
        }

        return redirect(url('server/acs/dashboard'))->with('auth_errors', ['Data tidak ditemukan']);
    }

    public function changePassword(Request $request)
    {
        $idrouter = Session::get('idrouter');

        if ($idrouter == null) {
            return redirect(url('server/acs'))->with('auth_errors', ['Anda belum klik connect']);
        }

        $acs = DB::table('acs')->where('id', $idrouter)->first();
        $host = $acs->url ?? null;

        if (empty($host)) {
            return redirect(url('server/acs'))->with('auth_errors', ['URL tidak ditemukan']);
        }

        $acsRequest = new ACSRequest($host);

        $deviceId = $request->post('deviceId');
        $newpassword = $request->post('newpassword');

        $devices = $acsRequest->getDeviceById($deviceId);

        if (strlen($newpassword) < 8) {
            return redirect(url('server/acs/dashboard'))->with('auth_errors', ['Password baru minimal 8 karakter']);
        }

        if ($devices) {
            $id_device = $devices[0]['_id'];

            $params = [
                ['InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.PreSharedKey.1.KeyPassphrase', $newpassword],
                ['InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.KeyPassphrase', $newpassword],
                ['InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.PreSharedKey.1.PreSharedKey', $newpassword],
            ];

            $acsRequest->setParameterValues($params, $id_device);

            return redirect(url('server/acs/dashboard'))->with('success', ['Berhasil melakukan pergantian kata sandi wifi, ID : '.$id_device]);
        }

        return redirect(url('server/acs/dashboard'))->with('auth_errors', ['Data tidak ditemukan']);
    }

    public function refresh(Request $request)
    {
        $idrouter = Session::get('idrouter');

        if ($idrouter == null) {
            return redirect(url('server/acs'))->with('auth_errors', ['Anda belum klik connect']);
        }

        $acs = DB::table('acs')->where('id', $idrouter)->first();
        $host = $acs->url ?? null;

        if (empty($host)) {
            return redirect(url('server/acs'))->with('auth_errors', ['URL tidak ditemukan']);
        }

        $acsRequest = new ACSRequest($host);

        $deviceId = $request->post('deviceId');
        $devices = $acsRequest->getDeviceById($deviceId);

        if ($devices) {
            $id_device = $devices[0]['_id'];

            $acsRequest->refreshAllObjects($id_device);

            return redirect(url('server/acs/connect/'.$idrouter))->with('success', ['Berhasil melakukan refresh device, ID : '.$id_device]);
        }

        return redirect(url('server/acs/connect/'.$idrouter))->with('auth_errors', ['Data tidak ditemukan']);
    }

    private function getSSID($device)
    {
        $ssid = '';
        if (isset($device['WLANConfiguration']) && is_array($device['WLANConfiguration'])) {
            foreach ($device['WLANConfiguration'] as $config) {
                if (isset($config['SSID']['_value'])) {
                    $ssid = $config['SSID']['_value'];
                    break;
                }
            }
        }

        return $ssid;
    }

    private function getDeviceInfo($device)
    {
        $info = [];
        if (isset($device['Hosts']['Host']) && is_array($device['Hosts']['Host'])) {
            foreach ($device['Hosts']['Host'] as $host) {
                if (isset($host['MACAddress']['_value'], $host['HostName']['_value'], $host['IPAddress']['_value'])) {
                    $info[] = [
                        'MACAddress' => $host['MACAddress']['_value'],
                        'HostName' => $host['HostName']['_value'],
                        'IPAddress' => $host['IPAddress']['_value'],
                    ];
                }
            }
        }

        return $info;
    }

    private function getClientInfo($deviceInfo)
    {
        $clients = [];
        foreach ($deviceInfo as $index => $info) {
            // Escape tiap field: HostName perangkat dikendalikan pelanggan,
            // dirender via {!! !!} di view (cegah stored XSS di panel admin).
            $clients[] = '- Perangkat '.($index + 1).':<br/>'.
                'Hostname: '.e($info['HostName']).'<br/>'.
                'IP: '.e($info['IPAddress']).'<br/>'.
                'MAC: '.e($info['MACAddress']).'<br/>';
        }

        return implode('<br/>', $clients);
    }

    /**
     * Convert CI4 helper `convertToFullWords()` (uptime parsing) - best-effort port.
     */
    private function convertToFullWords($value)
    {
        if ($value === null || $value === '') {
            return '';
        }

        $value = (string) $value;
        $days = 0;
        $hours = 0;
        $minutes = 0;
        $seconds = 0;

        if (preg_match('/(\d+)w/', $value, $m)) {
            $days += (int) $m[1] * 7;
        }
        if (preg_match('/(\d+)d/', $value, $m)) {
            $days += (int) $m[1];
        }
        if (preg_match('/(\d+)h/', $value, $m)) {
            $hours = (int) $m[1];
        }
        if (preg_match('/(\d+)m/', $value, $m)) {
            $minutes = (int) $m[1];
        }
        if (preg_match('/(\d+)s/', $value, $m)) {
            $seconds = (int) $m[1];
        }

        $parts = [];
        if ($days > 0) {
            $parts[] = $days.' hari';
        }
        if ($hours > 0) {
            $parts[] = $hours.' jam';
        }
        if ($minutes > 0) {
            $parts[] = $minutes.' menit';
        }
        if ($seconds > 0 || empty($parts)) {
            $parts[] = $seconds.' detik';
        }

        return implode(' ', $parts);
    }
}
