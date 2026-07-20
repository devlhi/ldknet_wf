<?php

namespace App\Http\Controllers\Server;

use App\Http\Controllers\Controller;
use App\Libraries\HsgqAPI;
use App\Models\Olt;
use App\Models\Website;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OltController extends Controller
{
    private HsgqAPI $hsgqAPI;

    public function __construct()
    {
        $this->hsgqAPI = new HsgqAPI;
    }

    private function websiteData(): array
    {
        $website = Website::first();

        return [
            'titletext' => $website->title ?? 'LandakNet',
            'logo' => $website->logo ?? '',
        ];
    }

    private function extractXToken($responseHeader)
    {
        $headers = explode("\r\n", $responseHeader);
        foreach ($headers as $header) {
            if (strpos($header, 'X-Token:') !== false) {
                return trim(str_replace('X-Token:', '', $header));
            }
        }

        return null; // Jika tidak ada X-Token ditemukan
    }

    private function login($url, $username, $password)
    {
        $key = md5($username.':'.$password);
        $passwordValue = base64_encode($password);

        $data = [
            'method' => 'set',
            'param' => [
                'name' => $username,
                'key' => $key,
                'value' => $passwordValue,
                'captcha_v' => '',
                'captcha_f' => '',
            ],
        ];

        $jsonData = json_encode($data);

        $curl = curl_init();

        $url = rtrim($url).'/'.ltrim('userlogin?form=login');

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, true); // Menyertakan header dalam respons

        $result = curl_exec($curl);
        $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE); // Mendapatkan ukuran header
        $responseHeader = substr($result, 0, $headerSize); // Mengambil bagian header dari respons
        $responseBody = substr($result, $headerSize); // Mengambil bagian body dari respons

        $xToken = $this->extractXToken($responseHeader); // Memanggil fungsi untuk mengekstrak X-Token

        curl_close($curl);

        // Memastikan respons body adalah JSON yang valid
        $decodedResponseBody = json_decode($responseBody, true);
        if ($decodedResponseBody === null && json_last_error() !== JSON_ERROR_NONE) {
            $decodedResponseBody = $responseBody;
        }

        return [
            'response' => $decodedResponseBody,
            'xToken' => $xToken,
        ];
    }

    public function settings() {}

    public function add(Request $request)
    {
        Olt::create([
            'nama' => $request->post('nama'),
            'ip' => $request->post('host'),
            'username' => $request->post('username'),
            'password' => legacy_encrypt($request->post('password')),
        ]);

        return redirect(url('server/olt'))->with('success', ['Berhasil menambahkan data']);
    }

    public function home(Request $request)
    {
        return view('server.olt.home', [
            'title' => 'Managemen OLT',
            'getData' => Olt::all(),
        ] + $this->websiteData());
    }

    public function editData($id)
    {
        return view('server.olt.edit', [
            'title' => 'Edit Data OLT',
            'getData' => Olt::where('id', $id)->get(),
        ] + $this->websiteData());
    }

    public function updateData(Request $request)
    {
        $target = $request->post('target');

        Olt::where('id', $target)->update([
            'nama' => $request->post('nama'),
            'ip' => $request->post('host'),
            'username' => $request->post('username'),
            'password' => legacy_encrypt($request->post('password')),
        ]);

        return redirect(url('server/olt/edit/'.$target))->with('success', ['Berhasil mengupdate data']);
    }

    public function deleteData($id)
    {
        Olt::where('id', $id)->delete();

        return redirect(url('server/olt'))->with('success', ['Berhasil menghapus data']);
    }

    public function connect($id)
    {
        $data = Olt::find($id);

        if ($data === null) {
            return redirect(url('server/olt'))->with('auth_errors', ['OLT tidak ditemukan !']);
        }

        $nama = $data->nama;
        $host = $data->ip;
        $username = $data->username;
        $password = legacy_decrypt($data->password);
        $cookies = $data->cookies;

        if ($cookies == null) {
            $connect = $this->login($host, $username, $password);

            $responseCode = $connect['response']['code'] ?? null;

            if ($responseCode == '4028') {
                return redirect(url('server/olt'))->with('auth_errors', ['Password OLT salah']);
            } elseif ($responseCode == '4031') {
                return redirect(url('server/olt'))->with('auth_errors', ['Username tidak ditemukan pada sistem OLT']);
            } elseif ($responseCode == '1') {
                $xtoken = $connect['xToken'];

                Olt::where('id', $id)->update([
                    'cookies' => $xtoken,
                ]);

                session([
                    'x-token' => $xtoken,
                    'namaolt' => $nama,
                    'host' => $host,
                ]);

                return redirect(url('server/olt/dashboard'));
            }
        } else {
            session([
                'x-token' => $cookies,
                'namaolt' => $nama,
                'host' => $host,
            ]);

            return redirect(url('server/olt/dashboard'));
        }
    }

    public function dashbard(Request $request)
    {
        $xtoken = session()->get('x-token');
        $host = session()->get('host');
        $namaolt = session()->get('namaolt');

        if ($xtoken == null) {
            return redirect(url('server/olt'))->with('auth_errors', ['Silahkan klik connect terlebih dahulu']);
        }

        $dataolt = Olt::where('nama', $namaolt)->first();

        $idnya = $dataolt->id ?? null;
        $username = $dataolt->username ?? null;
        $password = $dataolt !== null ? legacy_decrypt($dataolt->password) : null;

        $result = $this->hsgqAPI->getBoardInfo($host, $xtoken);
        $response = json_decode($result, true);

        if (($response['code'] ?? '0') == '0') {
            $connect = $this->login($host, $username, $password);

            $responseCode = $connect['response']['code'] ?? null;

            if ($responseCode == '4028') {
                return redirect(url('server/olt'))->with('auth_errors', ['Password OLT salah']);
            } elseif ($responseCode == '4031') {
                return redirect(url('server/olt'))->with('auth_errors', ['Username tidak ditemukan pada sistem OLT']);
            } elseif ($responseCode == '1') {
                $xtoken = $connect['xToken'];

                Olt::where('id', $idnya)->update([
                    'cookies' => $xtoken,
                ]);

                session([
                    'x-token' => $xtoken,
                ]);

                return redirect(url('server/olt/dashboard'));
            }
        } else {
            return view('server.olt.dashboard', [
                'title' => 'Dashboard OLT',
                'onu' => $response['data'],
            ] + $this->websiteData());
        }
    }

    public function pon(Request $request, $id)
    {
        $xtoken = session()->get('x-token');
        $host = session()->get('host');

        if ($xtoken == null) {
            return redirect(url('server/olt'))->with('auth_errors', ['Silahkan klik connect terlebih dahulu']);
        }

        $result = $this->hsgqAPI->getOnuAllowList($host, $id, $xtoken);
        $response = json_decode($result, true);

        return view('server.olt.pon', [
            'title' => 'OLT PON '.$id,
            'id' => $id,
            'onu' => $response['data'],
        ] + $this->websiteData());
    }

    public function detail($portnya = null, $onunya = null)
    {
        $xtoken = session()->get('x-token');
        $host = session()->get('host');

        if ($xtoken == null) {
            return redirect(url('server/olt'))->with('auth_errors', ['Silahkan klik connect terlebih dahulu']);
        }

        $result = $this->hsgqAPI->getOnuData($host, $portnya, $onunya, $xtoken);

        return view('server.olt.detail', [
            'title' => 'OLT PON '.$portnya,
            'id' => $portnya,
            'dataoptic' => $result['optical'],
            'base' => $result['base'],
        ] + $this->websiteData());
    }

    public function deleteOnu($portnya = null, $onunya = null, $macnya = null)
    {
        $xtoken = session()->get('x-token');
        $host = session()->get('host');

        if ($xtoken == null) {
            return redirect(url('server/olt'))->with('auth_errors', ['Silahkan klik connect terlebih dahulu']);
        }

        $this->hsgqAPI->deleteOnuList($host, $portnya, $onunya, $macnya, $xtoken);

        return redirect(url('server/olt/pon/'.$portnya));
    }

    public function changename(Request $request, $portnya = null, $onunya = null)
    {
        $xtoken = session()->get('x-token');
        $host = session()->get('host');
        $name = $request->post('nama');

        if ($xtoken == null) {
            return redirect(url('server/olt'))->with('auth_errors', ['Silahkan klik connect terlebih dahulu']);
        }

        $this->hsgqAPI->changeName($host, $portnya, $onunya, $name, $xtoken);

        return redirect(url('server/olt/detail/port/'.$portnya.'/onu/'.$onunya))->with('success', ['Nama berhasil diganti']);
    }

    public function rebootOnu($portnya = null, $onunya = null)
    {
        $xtoken = session()->get('x-token');
        $host = session()->get('host');

        if ($xtoken == null) {
            return redirect(url('server/olt'))->with('auth_errors', ['Silahkan klik connect terlebih dahulu']);
        }

        $this->hsgqAPI->rebootOnu($host, $portnya, $onunya, $xtoken);

        return redirect(url('server/olt/detail/port/'.$portnya.'/onu/'.$onunya))->with('success', ['ONT berhasil direboot']);
    }

    public function logout()
    {
        session()->forget(['x-token', 'host', 'namaolt']);

        return redirect(url('server/olt'));
    }

    public function botWhatsapp(Request $request)
    {
        // Tabel bot_olt tidak ada di semua instalasi DB legacy; skema shared
        // tidak boleh diubah dari Laravel, tampilkan halaman kosong + warning.
        $hasTable = Schema::hasTable('bot_olt');
        if (! $hasTable) {
            // now() (bukan flash), pesan hanya untuk request ini, tidak bocor ke halaman berikutnya.
            session()->now('auth_errors', ['Tabel bot_olt belum ada di database ini, impor dari instalasi lama untuk memakai fitur bot.']);
        }

        return view('server.olt.bot', [
            'title' => 'Bot Whatsapp OLT',
            'getData' => $hasTable ? DB::table('bot_olt')->orderByDesc('command')->get() : collect(),
        ] + $this->websiteData());
    }

    public function botWhatsappAdd(Request $request)
    {
        if (! Schema::hasTable('bot_olt')) {
            return redirect(url('server/olt/bot/whatsapp'))->with('auth_errors', ['Tabel bot_olt belum ada di database ini.']);
        }

        DB::table('bot_olt')->insert([
            'command' => $request->post('command'),
            'keterangan' => $request->post('keterangan'),
        ]);

        return redirect(url('server/olt/bot/whatsapp'))->with('success', ['Berhasil menambahkan data']);
    }

    public function botWhatsappDelete($id)
    {
        if (! Schema::hasTable('bot_olt')) {
            return redirect(url('server/olt/bot/whatsapp'))->with('auth_errors', ['Tabel bot_olt belum ada di database ini.']);
        }

        DB::table('bot_olt')->where('id', $id)->delete();

        return redirect(url('server/olt/bot/whatsapp'))->with('success', ['Berhasil menghapus data']);
    }
}
