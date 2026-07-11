<?php

namespace App\Http\Controllers;

use App\Libraries\ACSRequest;
use App\Libraries\RouterosAPI;
use App\Models\GangguanReport;
use App\Models\Member;
use App\Models\Order;
use App\Models\Router;
use App\Models\Service;
use App\Models\TemplateMessage;
use App\Models\User;
use App\Models\WaInboxMessage;
use App\Models\WhatsappSetting;
use App\Support\WhatsAppGatewayResolver;
use App\Support\WhatsAppNotifier;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WebhookCIResult
{
    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function getResult()
    {
        return $this->data instanceof Collection ? $this->data->all() : (array) $this->data;
    }

    public function getResultArray()
    {
        return array_map(function ($x) {
            return (array) $x;
        }, $this->getResult());
    }

    public function getNumRows()
    {
        return count($this->getResult());
    }
}
class WebhookDbCompat
{
    public function table($table)
    {
        return new WebhookBuilderCompat(DB::table($table));
    }
}
class WebhookBuilderCompat
{
    public $builder;

    public function __construct($builder)
    {
        $this->builder = $builder;
    }

    public function where($a, $b = null, $c = null)
    {
        $this->builder->where($a, $b, $c);

        return $this;
    }

    public function get()
    {
        return new WebhookCIResult($this->builder->get());
    }
}
class WebhookAdminCompat
{
    public function getNumber($number)
    {
        return User::where('nomor', $number)->get()->all();
    }

    public function checkamauser($nama)
    {
        return new WebhookCIResult(User::where('nama', $nama)->get());
    }

    public function checkomoruser($nomor)
    {
        return new WebhookCIResult(User::where('nomor', $nomor)->get());
    }

    public function getServices()
    {
        return Service::all()->all();
    }

    public function getServicesByID($id)
    {
        return new WebhookCIResult(Service::where('id', $id)->get());
    }

    public function getIDPel()
    {
        return [['idpel' => Order::max('idpel')]];
    }

    public function insertOrders($data)
    {
        return Order::insert($data);
    }

    public function insertUser($data)
    {
        return User::insert($data);
    }

    public function getTemplateMessage()
    {
        return new WebhookCIResult(TemplateMessage::all());
    }

    public function getNumberOrders($number)
    {
        return Order::where('nomor', $number)->get()->all();
    }

    public function updateOrdersByNomor($nomor, $data)
    {
        return Order::where('nomor', $nomor)->update($data);
    }

    public function insertMember($data)
    {
        return Member::insert($data);
    }

    public function getNumbersMembers($number)
    {
        return Member::where('nomor', $number)->get()->all();
    }

    public function UpdateMembersByNomor($nomor, $data)
    {
        return Member::where('nomor', $nomor)->update($data);
    }

    public function getOrdersByPPPOE($username)
    {
        return Order::where('pppoe_user', $username)->get()->all();
    }

    public function UpdateOrdersByPPPOE($username, $data)
    {
        return Order::where('pppoe_user', $username)->update($data);
    }

    public function getUsernameByNumbers($username, $numbers)
    {
        return Member::where('username', $username)->where('nomor', $numbers)->get()->all();
    }
}
class WebhookRosCompat
{
    public function getData()
    {
        return Router::all()->all();
    }

    public function getDatabyID($id)
    {
        return new WebhookCIResult(Router::where('id', $id)->get());
    }
}
class WebhookWhatsappCompat
{
    public function getWebhook()
    {
        return new WebhookCIResult(DB::table('webhook')->get());
    }

    public function getWhatsappTypeBlast()
    {
        return new WebhookCIResult(WhatsappSetting::where('type', 'blast')->get());
    }
}

class WebhookController extends Controller
{
    private $adminModel;

    private $ros;

    private $rosModel;

    private $whatsappModel;

    public function __construct()
    {
        $this->adminModel = new WebhookAdminCompat;
        $this->ros = new RouterosAPI;
        $this->rosModel = new WebhookRosCompat;
        $this->whatsappModel = new WebhookWhatsappCompat;
    }

    public function whatsapp()
    {
        header('content-type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        file_put_contents(storage_path('logs/whatsapp.txt'), '['.date('Y-m-d H:i:s')."]\n".json_encode($data, JSON_PRETTY_PRINT)."\n\n", FILE_APPEND);

        $result = false;

        // ===== DEBUG MODE - Edit true/false =====
        $debugMode = true;  // true = ON, false = OFF
        // ========================================

        // ===== WHITELIST ID GRUP - Edit manual di sini =====
        // Daftar ID grup WhatsApp yang diizinkan untuk akses bot
        $allowedGroups = [
            //  '120363399630489636',              // KARYAWAN PT.LANDAK ANNORTY NET (ID Lama)
            '120363405313250906',              // KARYAWAN PT.LANDAK ANNORTY NET (ID Baru)
            // Tambah grup lain di bawah ini:
            // '120363123456789012',
            // '628152895266-1730875000@g.us',
        ];
        // ====================================================

        if (isset($data['message'])) {
            $pesan = $data['message'];
            $from = strtolower($data['from']);

            // Serap otomatis jadi laporan gangguan — hanya chat pribadi pelanggan
            // (bukan grup, bukan pesan keluar dari kita). Additif & tidak mengubah
            // alur bot; capture() dibungkus try/catch + dedup sendiri.
            $isGroupChat = str_contains($from, '@g.us') || (strlen($from) > 13 && substr($from, 0, 5) === '12036');
            $isOutgoing = ! empty($data['fromMe']) || ! empty($data['from_me']);
            if (! $isGroupChat && ! $isOutgoing) {
                GangguanReport::capture($from, $data['pushName'] ?? ($data['notifyName'] ?? null), (string) $pesan, 'wablas');
            }

            // Deteksi apakah dari grup
            // Format 1: dari grup dengan @g.us
            // Format 2: dari grup dengan ID panjang (tanpa @g.us) seperti 120363399630489636
            // Format 3: jika ada participant dan from berbeda (grup)
            $isFromGroup = false;

            if (strpos($from, '@g.us') !== false) {
                // Format standar grup
                $isFromGroup = true;
            } elseif (strlen($from) > 13 && substr($from, 0, 5) === '12036') {
                // ID grup format: 120363xxxxx (tanpa @g.us)
                $isFromGroup = true;
            } elseif (isset($data['participant']) && $data['participant'] !== $from.'@c.us') {
                // Ada participant yang berbeda dari from
                $isFromGroup = true;
            }

            // Log untuk debugging
            if ($debugMode) {
                file_put_contents(storage_path('logs/whatsapp.txt'), '[DEBUG] From: '.$from."\n", FILE_APPEND);
                file_put_contents(storage_path('logs/whatsapp.txt'), '[DEBUG] Is Group: '.($isFromGroup ? 'YES' : 'NO')."\n", FILE_APPEND);
            }

            // Validasi: Jika dari grup, cek apakah grup di-whitelist
            if ($isFromGroup) {
                $isGroupAllowed = in_array($from, $allowedGroups);
                if ($debugMode) {
                    file_put_contents(storage_path('logs/whatsapp.txt'), '[DEBUG] Group Whitelisted: '.($isGroupAllowed ? 'YES' : 'NO')."\n", FILE_APPEND);
                }

                if (! $isGroupAllowed) {
                    if ($debugMode) {
                        file_put_contents(storage_path('logs/whatsapp.txt'), '[DEBUG] ACCESS DENIED - Group not in whitelist\n\n', FILE_APPEND);
                    }

                    // Tidak respon di grup yang tidak di-whitelist
                    return response()->noContent();
                }
            }

            // Variabel untuk nomor pengirim
            $senderNumber = '';
            $level = '';
            $result = '';

            if ($isFromGroup) {
                // Pesan dari grup - SEMUA GRUP DIIZINKAN
                // Ambil nomor pengirim sebenarnya dari berbagai kemungkinan field
                // Prioritas: participant > sender > author
                if (isset($data['participant'])) {
                    $senderNumber = $data['participant'];
                } elseif (isset($data['sender'])) {
                    $senderNumber = $data['sender'];
                } elseif (isset($data['author'])) {
                    $senderNumber = $data['author'];
                } elseif (isset($data['from_me']) && ! $data['from_me'] && isset($data['chatId'])) {
                    $senderNumber = $data['chatId'];
                }

                // Jika sender kosong, coba ambil dari key object
                if (empty($senderNumber) && isset($data['key'])) {
                    if (isset($data['key']['participant'])) {
                        $senderNumber = $data['key']['participant'];
                    } elseif (isset($data['key']['remoteJid'])) {
                        $senderNumber = $data['key']['remoteJid'];
                    }
                }

                // Log sender
                if ($debugMode) {
                    file_put_contents(storage_path('logs/whatsapp.txt'), '[DEBUG] Sender RAW: '.$senderNumber."\n", FILE_APPEND);

                    // Jika masih kosong, log semua keys untuk debug
                    if (empty($senderNumber)) {
                        file_put_contents(storage_path('logs/whatsapp.txt'), '[DEBUG] Available keys: '.implode(', ', array_keys($data))."\n", FILE_APPEND);
                    }
                }

                // Bersihkan nomor dari format WhatsApp (@c.us atau @s.whatsapp.net)
                if (strpos($senderNumber, '@') !== false) {
                    $senderNumber = explode('@', $senderNumber)[0];
                }

                if ($debugMode) {
                    file_put_contents(storage_path('logs/whatsapp.txt'), '[DEBUG] Cleaned sender: '.$senderNumber."\n", FILE_APPEND);
                }

                // Format nomor untuk cek database
                if (substr($senderNumber, 0, 2) == '62') {
                    $from_number = '0'.substr($senderNumber, 2);
                    $result = preg_replace('/[^0-9]/', '', $from_number);
                } elseif (! empty($senderNumber)) {
                    $result = preg_replace('/[^0-9]/', '', $senderNumber);
                } else {
                    // Jika sender tetap kosong, log dan exit
                    if ($debugMode) {
                        file_put_contents(storage_path('logs/whatsapp.txt'), '[ERROR] Cannot identify sender from group message\n\n', FILE_APPEND);
                    }
                    $response = ['text' => 'Error: Tidak dapat mengidentifikasi pengirim.'];

                    return response()->json($response);
                }

            } else {
                // Pesan dari personal/individual chat
                if (substr($from, 0, 2) == '62') {
                    $from_number = '0'.substr($from, 2);
                    $result = preg_replace('/[^0-9]/', '', $from_number);
                } else {
                    $from_number = $from;
                    $result = $from_number;
                }
            }

            // Cek nomor di database
            $search = $this->adminModel->getNumber($result);

            // Tentukan target balasan: jika dari grup yang diizinkan, balas ke grup. Jika personal, balas ke nomor
            $nomorbysend = $isFromGroup ? $from : $result;

            if ($debugMode) {
                file_put_contents(storage_path('logs/whatsapp.txt'), '[DEBUG] Reply to: '.$nomorbysend."\n", FILE_APPEND);
                file_put_contents(storage_path('logs/whatsapp.txt'), '[DEBUG] User number: '.$result."\n", FILE_APPEND);
            }

            $level = '';

            // Cek apakah user ada di database
            if ($search) {
                foreach ($search as $datauser) {
                    $level = $datauser->level;
                }
                if ($debugMode) {
                    file_put_contents(storage_path('logs/whatsapp.txt'), '[DEBUG] User found in DB: YES, Level: '.$level."\n\n", FILE_APPEND);
                }
            } else {
                if ($debugMode) {
                    file_put_contents(storage_path('logs/whatsapp.txt'), '[DEBUG] User found in DB: NO\n', FILE_APPEND);
                }

                // Jika dari grup yang di-whitelist, berikan akses dengan level default
                if ($isFromGroup) {
                    $level = 'admin'; // Level default untuk user di grup yang di-whitelist
                    if ($debugMode) {
                        file_put_contents(storage_path('logs/whatsapp.txt'), '[DEBUG] Group member auto-allowed with level: '.$level."\n\n", FILE_APPEND);
                    }
                    $search = true; // Set true agar bisa lanjut
                } else {
                    // Jika dari personal chat, harus terdaftar di database
                    if ($debugMode) {
                        file_put_contents(storage_path('logs/whatsapp.txt'), '[DEBUG] ACCESS DENIED - User not registered\n\n', FILE_APPEND);
                    }
                }
            }

            if ($search == true) {
                $pesan = str_replace('-', ' ', $pesan);

                // Pisahkan pesan berdasarkan spasi
                $params = explode(' ', $pesan);

                // Command pertama adalah $params[0]
                $command = strtolower($params[0]);

                // Initialize response variable (berbeda dari $result yang untuk nomor)
                $response = null;

                $webhook = $this->whatsappModel->getWebhook();
                $statuswebhook = null; // Inisialisasi status webhook
                $datawebhook = $webhook->getResult();

                if (! empty($datawebhook)) {
                    $statuswebhook = $datawebhook[0]->status; // Ambil status dari hasil query
                }

                if ($statuswebhook === 'on') {
                    switch ($command) {
                        case '/help':
                            $text = " ✅Berikut perintah yang tersedia ✅\n\n";
                            if ($level == 'admin' || $level == 'developer') {
                                // Daftar perintah yang tersedia
                                $text .= "=== ❇️ Fitur perintah admin ❇️ ===\n";
                                $text .= "Command : /regisuser\nInformasi : _Mendaftarkan data customer_\n\n";
                                $text .= "Command : /regisppp\nInformasi : _Mendaftarkan PPPOE berdasarkan Nomor Handphone dan memilih server router_\n\n";
                                $text .= "Command : /regishs\nInformasi : _Mendaftarkan Hotspot berdasarkan Nomor Handphone dan memilih server router_\n\n";
                                $text .= "Command : /addhs\nInformasi : _Menambahkan data member hotspot kedalam server web dan juga server router_\n\n";
                                $text .= "Command : /gantipass\nInformasi : _Mengganti password member hotspot berdasarkan nomor handphone_\n\n";
                                $text .= "Command : /openhs\nInformasi : _Mengaktifkan Member Hotspot berdasarkan nomor handphone yang sudah terdapat di server web_\n\n";
                                $text .= "Command : /disabledhs\nInformasi : _Menonaktifkan Member Hotspot berdasarkan nomor handphone yang sudah terdapat di server web_\n\n";
                                $text .= "Command : /openppp\nInformasi : _Mengaktifkan PPP berdasarkan username yang sudah terdapat di server web_\n\n";
                                $text .= "Command : /cekpaket\nInformasi : _Melihat semua paket atau filter berdasarkan router_\nFormat : /cekpaket atau /cekpaket router [ID]\n\n";
                                $text .= "Command : /cekrouter\nInformasi : _Melihat daftar ID dan nama router/server_\n\n";
                                $text .= "Command : /cekhs\nInformasi : _Cek status Hotspot user aktif (traffic & uptime)_\nFormat : /cekhs username [user]\n\n";
                                $text .= "Command : /cekppp\nInformasi : _Cek status PPPoE user aktif (traffic & uptime)_\nFormat : /cekppp username [user]\n\n";
                                $text .= "Command : /gpass ppp\nInformasi : _Ganti password PPPoE user (cek DB & MikroTik)_\nFormat : /gpass ppp username [user] password [pass]\n\n";
                                $text .= "Command : /gpass hs\nInformasi : _Ganti password Hotspot user (cek DB & MikroTik)_\nFormat : /gpass hs username [user] password [pass]\n\n";
                                $text .= "Command : /cekredaman\nInformasi : _Cek redaman ONT (RX Power) berdasarkan tag_\nFormat : /cekredaman tag [nama_tag]\n\n";
                                $text .= "Command : /cekdata ppp\nInformasi : _Cek detail PPPoE dari MikroTik langsung_\nFormat : /cekdata ppp username [user] router [ID]\n\n";
                                $text .= "Command : /cekdata hs\nInformasi : _Cek detail Hotspot dari MikroTik langsung_\nFormat : /cekdata hs username [user] router [ID]\n\n";
                                $text .= "============================\n\n";
                            } elseif ($level == 'member') {
                                $text .= "=== ❇️ Fitur perintah  ❇️ ===\n";
                                $text .= "Command : /gpass\nInformasi : _Mengganit password member berdasarkan username_\n\n";
                                $text .= "Command : /delhost\nInformasi : _Menghapus hotspot active anda berdasarkan username_\n\n";
                            }
                            $text .= "=== ✳️ Perintah tambahan ✳️ ===\n";
                            $text .= "Command : /mybot\nInformasi : _Untuk melihat tentang bot ini_\n";
                            $text .= "============================\n\n";
                            $text .= 'Version Bot : v1.0 🔰';
                            $text .= "\n";
                            $text .= 'Developed By Djunardi Ali';

                            $response = [
                                'text' => $text,
                            ];
                            break;

                        case '/regisuser':
                            // Cari indeks dari setiap parameter
                            $nama = '';
                            $email = '';
                            $alamat = '';
                            $nomor = '';
                            $paket = '';
                            $router = '';

                            for ($i = 1; $i < count($params); $i++) {
                                if ($params[$i] === 'nama') {
                                    $j = $i + 1;
                                    while ($j < count($params) && $params[$j] !== 'email' && $params[$j] !== 'alamat' && $params[$j] !== 'nomor' && $params[$j] !== 'paket' && $params[$j] !== 'router') {
                                        $nama .= $params[$j].' ';
                                        $j++;
                                    }
                                    $nama = trim($nama);
                                } elseif ($params[$i] === 'email') {
                                    $j = $i + 1;
                                    while ($j < count($params) && $params[$j] !== 'alamat' && $params[$j] !== 'nomor' && $params[$j] !== 'paket' && $params[$j] !== 'router') {
                                        $email .= $params[$j].' ';
                                        $j++;
                                    }
                                    $email = trim($email);
                                } elseif ($params[$i] === 'alamat') {
                                    $j = $i + 1;
                                    while ($j < count($params) && $params[$j] !== 'nomor' && $params[$j] !== 'paket' && $params[$j] !== 'router') {
                                        $alamat .= $params[$j].' ';
                                        $j++;
                                    }
                                    $alamat = trim($alamat);
                                } elseif ($params[$i] === 'nomor') {
                                    $nomor = isset($params[$i + 1]) ? $params[$i + 1] : '';
                                } elseif ($params[$i] === 'paket') {
                                    $paket = isset($params[$i + 1]) ? $params[$i + 1] : '';
                                } elseif ($params[$i] === 'router') {
                                    $router = isset($params[$i + 1]) ? $params[$i + 1] : '';
                                }
                            }

                            // Validasi apakah semua parameter telah diisi
                            if ($nama !== '' && $email !== '' && $alamat !== '' && $nomor !== '' && $paket !== '' && $router !== '') {

                                $checknamauser = $this->adminModel->checkamauser($nama);
                                $checknomoruser = $this->adminModel->checkomoruser($nomor);

                                if ($checknamauser->getNumRows() > 0) {
                                    $response = [
                                        'text' => 'Nama user sudah terdaftar!',
                                    ];
                                } elseif ($checknomoruser->getNumRows() > 0) {
                                    $response = [
                                        'text' => 'Nomor handphone sudah terdaftar!',
                                    ];
                                } else {
                                    $getRouter = $this->rosModel->getData();
                                    $getServices = $this->adminModel->getServices();

                                    $foundservice = false;

                                    foreach ($getServices as $rowservice) {
                                        $idservice = $rowservice->id;
                                        if ($paket === $idservice) {
                                            $foundservice = true;
                                            break;
                                        }
                                    }

                                    $found = false;

                                    foreach ($getRouter as $row) {
                                        $idnya = $row->id;
                                        if ($router === $idnya) {
                                            $found = true;
                                            break;
                                        }
                                    }

                                    if ($foundservice && $found) {

                                        $paketlayanan = $this->adminModel->getServicesByID($paket);
                                        foreach ($paketlayanan->getResult() as $datalayanan) {
                                            $namapaket = $datalayanan->paket;
                                        }

                                        $idpel = $this->adminModel->getIDPel();

                                        foreach ($idpel as $data) {
                                            $a = $data['idpel'];
                                            $char = 'P-';
                                            $urutan = (int) substr($a, 2, 4);
                                            $urutan++;
                                            $kode = $char.sprintf('%04s', $urutan);
                                        }

                                        $hariini = date('Y-m-d');
                                        $masa = date('Y-m-d', strtotime('+1 month', strtotime($hariini)));
                                        $password = random(7);

                                        $insertOrders = [
                                            'idpel' => $kode,
                                            'email' => $email.'@gmail.com',
                                            'nama' => $nama,
                                            'paket' => $namapaket,
                                            'alamat' => $alamat,
                                            'nomor' => $nomor,
                                            'status' => 'Active',
                                            'date' => $hariini,
                                            'expdate' => $masa,
                                            'id_router' => $router,
                                            'mode' => 'pppoe',
                                        ];

                                        $insertUsers = [
                                            'email' => $email.'@gmail.com',
                                            'nama' => $nama,
                                            'nomor' => $nomor,
                                            'password' => password_hash($password, PASSWORD_DEFAULT),
                                            'level' => 'user',
                                            'verify_account' => '1',
                                            'status_account' => 'Active',
                                        ];

                                        $tabelorders = $this->adminModel->insertOrders($insertOrders);

                                        if ($tabelorders == true) {
                                            $tabeluser = $this->adminModel->insertUser($insertUsers);
                                            if ($tabeluser) {
                                                $templateMessage = $this->adminModel->getTemplateMessage();

                                                foreach ($templateMessage->getResult() as $dataTemplateMessage) {
                                                    $message = $dataTemplateMessage->notif_pelanggan_baru;
                                                }

                                                $indo = date('Y-m-d', strtotime($masa));
                                                $tglindo = tanggal_indo($indo);
                                                $linkweb = url('/');
                                                $tambahan = '@gmail.com';
                                                $tambahannya = $email.$tambahan;
                                                $message = str_replace('{nama_customer}', $nama, $message);
                                                $message = str_replace('{email}', $tambahannya, $message);

                                                $message = str_replace('{id_pelanggan}', $kode, $message);
                                                $message = str_replace('{expdate}', $tglindo, $message);
                                                $message = str_replace('{paket}', $namapaket, $message);
                                                $message = str_replace('{link_web}', $linkweb, $message);
                                                $message = str_replace('{password}', $password, $message);

                                                // Password sengaja tidak dikirim ke template Meta (ditolak Meta di kategori UTILITY); password tetap dikirim via email.
                                                WhatsAppNotifier::sendNotification(WhatsAppNotifier::EVENT_PELANGGAN_BARU, $nomor, $message, [$nama, $tambahannya, $kode, $tglindo, $namapaket, $linkweb]);

                                                $text = 'Berhasil melakukan penambahan customer';
                                                $response = [
                                                    'text' => $text,
                                                ];
                                            } else {
                                                $text = 'Ada kesalahan sistem pada penginputan tabel orders';
                                                $response = [
                                                    'text' => $text,
                                                ];
                                            }
                                        } else {
                                            $text = 'Ada kesalahan sistem pada penginputan tabel orders';
                                            $response = [
                                                'text' => $text,
                                            ];
                                        }
                                    } else {
                                        if (! $foundservice) {
                                            $text = '*ERROR:* _ID_ Layanan tidak ditemukan !';
                                            $text .= "\n\n";
                                            $text .= 'List Layanan yang tersedia : ';
                                            $text .= "\n\n";
                                            foreach ($getServices as $row) {
                                                $text .= 'ID : '.$row->id."\n".'Nama Layanan  : '.$row->paket;
                                                $text .= "\n\n";
                                            }
                                            $response = [
                                                'text' => $text,
                                            ];
                                        } elseif (! $found) {
                                            $text = '*ERROR:* _ID_ Router tidak ditemukan !';
                                            $text .= "\n\n";
                                            $text .= 'List Device yang tersedia : ';
                                            $text .= "\n\n";
                                            foreach ($getRouter as $row) {
                                                $text .= 'ID : '.$row->id."\n".'Nama Server  : '.$row->nama;
                                                $text .= "\n\n";
                                            }
                                            $response = [
                                                'text' => $text,
                                            ];
                                        }
                                    }
                                }
                            } else {
                                $text = '*ERROR:* Format tidak sesuai !';
                                $text .= "\n\n";
                                $text .= 'Format : /regisuser nama waldi-landak email waldi@gmail.com alamat Dusun-mempawah nomor 082159512358 paket 2 router 1';
                                $response = ['text' => $text];
                            }
                            break;

                        case '/regisppp':
                            // Cari indeks dari setiap parameter
                            $nomor = '';
                            $username = '';
                            $password = '';
                            $paket = '';
                            $router = '';

                            for ($i = 1; $i < count($params); $i++) {
                                if ($params[$i] === 'nomor') {
                                    $nomor = isset($params[$i + 1]) ? $params[$i + 1] : '';
                                } elseif ($params[$i] === 'username') {
                                    $username = isset($params[$i + 1]) ? $params[$i + 1] : '';
                                } elseif ($params[$i] === 'password') {
                                    $password = isset($params[$i + 1]) ? $params[$i + 1] : '';
                                } elseif ($params[$i] === 'paket') {
                                    $paket = isset($params[$i + 1]) ? $params[$i + 1] : '';
                                } elseif ($params[$i] === 'router') {
                                    $router = isset($params[$i + 1]) ? $params[$i + 1] : '';
                                }
                            }

                            // Validasi apakah semua parameter telah diisi
                            if ($nomor !== '' && $username !== '' && $password !== '' && $paket !== '' && $router !== '') {
                                $ceknomor = $this->adminModel->getNumberOrders($nomor);
                                if ($ceknomor) {
                                    $getRouter = $this->rosModel->getData();
                                    $getServices = $this->adminModel->getServices();

                                    $foundservice = false;

                                    foreach ($getServices as $rowservice) {
                                        $idservice = $rowservice->id;
                                        if ($paket === $idservice) {
                                            $foundservice = true;
                                            break;
                                        }
                                    }
                                    $paketlayanan = $this->adminModel->getServicesByID($paket);
                                    foreach ($paketlayanan->getResult() as $datalayanan) {
                                        $pppprofile = $datalayanan->ppp_profile;
                                    }

                                    $found = false;
                                    foreach ($getRouter as $row) {
                                        $idnya = $row->id;
                                        if ($router === $idnya) {
                                            $found = true;
                                            break;
                                        }
                                    }

                                    if ($pppprofile == null) {
                                        $text = '*ERROR:* Lakukan sinkronisasi PPP terlebih dahulu pada server website';
                                        $response = [
                                            'text' => $text,
                                        ];
                                    } else {
                                        if ($foundservice && $found) {
                                            $cekrouter = $this->rosModel->getDatabyID($router);

                                            foreach ($cekrouter->getResultArray() as $data) {
                                                $hostserver = $data['ip'];
                                                $usernameserver = $data['username'];
                                                $passwordserver = legacy_decrypt($data['password']);
                                            }

                                            if ($this->ros->connect($hostserver, $usernameserver, $passwordserver)) {
                                                $this->ros->comm('/ppp/secret/add', [
                                                    'name' => $username,
                                                    'password' => $password,
                                                    'service' => 'pppoe',
                                                    'profile' => $pppprofile,
                                                ]);

                                                $updateOrders = [
                                                    'pppoe_user' => $username,
                                                ];

                                                $this->adminModel->updateOrdersByNomor($nomor, $updateOrders);
                                                $text = '*Berhasil:* Akun Berhasil dibuat, berikut akun anda :';
                                                $text .= "\n\n";
                                                $text .= "Username : $username\n";
                                                $text .= "Password : $password\n\n";
                                                $text .= 'Proses Selesai.';
                                                $response = ['text' => $text];
                                            } else {
                                                $text = '*ERROR:* Router Not Connected';
                                                $response = ['text' => $text];
                                            }
                                        } else {
                                            if (! $foundservice) {
                                                $text = '*ERROR:* _ID_ Layanan tidak ditemukan !';
                                                $text .= "\n\n";
                                                $text .= 'List Layanan yang tersedia : ';
                                                $text .= "\n\n";
                                                foreach ($getServices as $row) {
                                                    $text .= 'ID : '.$row->id."\n".'Nama Layanan  : '.$row->paket;
                                                    $text .= "\n\n";
                                                }
                                                $response = [
                                                    'text' => $text,
                                                ];
                                            } elseif (! $found) {
                                                $text = '*ERROR:* _ID_ Router tidak ditemukan !';
                                                $text .= "\n\n";
                                                $text .= 'List Device yang tersedia : ';
                                                $text .= "\n\n";
                                                foreach ($getRouter as $row) {
                                                    $text .= 'ID : '.$row->id."\n".'Nama Server  : '.$row->nama;
                                                    $text .= "\n\n";
                                                }
                                                $response = [
                                                    'text' => $text,
                                                ];
                                            }
                                        }
                                    }
                                } else {
                                    $text = '*ERROR:* Nomor tersebut belum menjadi customer, silahkan ketikan register di command /regisuser';
                                    $response = ['text' => $text];
                                }
                            } else {
                                $text = '*ERROR:* Format tidak sesuai !';
                                $text .= "\n\n";
                                $text .= 'Format : /regisppp nomor [nomor_pelanggan] username [username_ppp] password [password_ppp] paket [id_paket] router [router_id]';
                                $text .= "\n";
                                $text .= 'Contoh : /regisppp nomor 082159512358 username adinda password 1234 paket 1 router 1';
                                $response = ['text' => $text];
                            }
                            break;

                        case '/regishs':
                            // Registrasi Hotspot User - mirip /regisppp tapi untuk hotspot
                            $nomor = '';
                            $username = '';
                            $password = '';
                            $paket = '';
                            $router = '';

                            for ($i = 1; $i < count($params); $i++) {
                                if ($params[$i] === 'nomor') {
                                    $nomor = isset($params[$i + 1]) ? $params[$i + 1] : '';
                                } elseif ($params[$i] === 'username') {
                                    $username = isset($params[$i + 1]) ? $params[$i + 1] : '';
                                } elseif ($params[$i] === 'password') {
                                    $password = isset($params[$i + 1]) ? $params[$i + 1] : '';
                                } elseif ($params[$i] === 'paket') {
                                    $paket = isset($params[$i + 1]) ? $params[$i + 1] : '';
                                } elseif ($params[$i] === 'router') {
                                    $router = isset($params[$i + 1]) ? $params[$i + 1] : '';
                                }
                            }

                            // Validasi apakah semua parameter telah diisi
                            if ($nomor !== '' && $username !== '' && $password !== '' && $paket !== '' && $router !== '') {
                                $ceknomor = $this->adminModel->getNumberOrders($nomor);
                                if ($ceknomor) {
                                    $getRouter = $this->rosModel->getData();
                                    $getServices = $this->adminModel->getServices();

                                    $foundservice = false;

                                    foreach ($getServices as $rowservice) {
                                        $idservice = $rowservice->id;
                                        if ($paket === $idservice) {
                                            $foundservice = true;
                                            break;
                                        }
                                    }

                                    $paketlayanan = $this->adminModel->getServicesByID($paket);
                                    $modenya = '';
                                    foreach ($paketlayanan->getResult() as $datalayanan) {
                                        $modenya = $datalayanan->mode;
                                    }

                                    $found = false;
                                    foreach ($getRouter as $row) {
                                        $idnya = $row->id;
                                        if ($router === $idnya) {
                                            $found = true;
                                            break;
                                        }
                                    }

                                    // Validasi mode harus hotspot
                                    if ($modenya !== 'hotspot') {
                                        $text = "❌ *ERROR: Paket bukan untuk Hotspot!*\n\n";
                                        $text .= "Paket yang dipilih mode: *$modenya*\n";
                                        $text .= "Gunakan paket dengan mode: *hotspot*\n\n";
                                        $text .= 'Gunakan /cekpaket untuk melihat daftar paket.';
                                        $response = ['text' => $text];
                                    } else {
                                        if ($foundservice && $found) {
                                            $cekrouter = $this->rosModel->getDatabyID($router);

                                            foreach ($cekrouter->getResultArray() as $data) {
                                                $hostserver = $data['ip'];
                                                $usernameserver = $data['username'];
                                                $passwordserver = legacy_decrypt($data['password']);
                                            }

                                            if ($this->ros->connect($hostserver, $usernameserver, $passwordserver)) {
                                                // Tambah hotspot user ke MikroTik
                                                $this->ros->comm('/ip/hotspot/user/add', [
                                                    'server' => 'all',
                                                    'name' => $username,
                                                    'password' => $password,
                                                    'profile' => 'default',
                                                ]);

                                                // Update orders table dengan username hotspot
                                                $updateOrders = [
                                                    'pppoe_user' => $username,
                                                    'mode' => 'hotspot',
                                                ];

                                                $this->adminModel->updateOrdersByNomor($nomor, $updateOrders);

                                                $text = "✅ *Akun Hotspot Berhasil Dibuat*\n\n";
                                                $text .= "📱 Nomor: $nomor\n";
                                                $text .= "👤 Username: $username\n";
                                                $text .= "🔐 Password: $password\n";
                                                $text .= "📦 Paket ID: $paket\n";
                                                $text .= "🖥️ Router ID: $router\n\n";
                                                $text .= "✅ Status: User hotspot berhasil ditambahkan ke MikroTik dan database.\n\n";
                                                $text .= '📌 Proses Selesai.';
                                                $response = ['text' => $text];
                                            } else {
                                                $text = "❌ *ERROR: Router Not Connected*\n\n";
                                                $text .= "Tidak dapat terhubung ke router.\n";
                                                $text .= 'Hubungi Administrator.';
                                                $response = ['text' => $text];
                                            }
                                        } else {
                                            if (! $foundservice) {
                                                $text = "❌ *ERROR: ID Layanan tidak ditemukan!*\n\n";
                                                $text .= "📋 *List Layanan yang tersedia:*\n\n";
                                                foreach ($getServices as $row) {
                                                    $text .= '🔹 ID: '.$row->id."\n";
                                                    $text .= '   Nama: '.$row->paket."\n";
                                                    $text .= '   Mode: '.($row->mode ?? 'N/A')."\n\n";
                                                }
                                                $text .= '💡 Gunakan /cekpaket untuk info lengkap.';
                                                $response = ['text' => $text];
                                            } elseif (! $found) {
                                                $text = "❌ *ERROR: ID Router tidak ditemukan!*\n\n";
                                                $text .= "🖥️ *List Router yang tersedia:*\n\n";
                                                foreach ($getRouter as $row) {
                                                    $text .= '🔸 ID: '.$row->id."\n";
                                                    $text .= '   Nama: '.$row->nama."\n\n";
                                                }
                                                $text .= '💡 Gunakan /cekrouter untuk info lengkap.';
                                                $response = ['text' => $text];
                                            }
                                        }
                                    }
                                } else {
                                    $text = "❌ *ERROR: Nomor tidak terdaftar!*\n\n";
                                    $text .= "Nomor *$nomor* belum menjadi customer.\n\n";
                                    $text .= "📝 Silakan registrasi terlebih dahulu dengan command:\n";
                                    $text .= '/regisuser';
                                    $response = ['text' => $text];
                                }
                            } else {
                                $text = "❌ *ERROR: Format tidak sesuai!*\n\n";
                                $text .= "📝 *Format:*\n";
                                $text .= "/regishs nomor [nomor] username [user] password [pass] paket [id] router [id]\n\n";
                                $text .= "💡 *Contoh:*\n";
                                $text .= '/regishs nomor 082159512358 username user01 password Pass123 paket 2 router 1';
                                $response = ['text' => $text];
                            }
                            break;

                        case '/addhs':
                            // Cari indeks dari setiap parameter
                            $username = '';
                            $jumlah = '';
                            $nomor = '';
                            $paket = '';
                            $router = '';

                            for ($i = 1; $i < count($params); $i++) {
                                if ($params[$i] === 'username') {
                                    $j = $i + 1;
                                    while ($j < count($params) && $params[$j] !== 'jumlah' && $params[$j] !== 'nomor' && $params[$j] !== 'paket' && $params[$j] !== 'router') {
                                        $username .= $params[$j].' ';
                                        $j++;
                                    }
                                    $username = trim($username);
                                } elseif ($params[$i] === 'jumlah') {
                                    $jumlah = isset($params[$i + 1]) ? $params[$i + 1] : '';
                                } elseif ($params[$i] === 'nomor') {
                                    $nomor = isset($params[$i + 1]) ? $params[$i + 1] : '';
                                } elseif ($params[$i] === 'paket') {
                                    $paket = isset($params[$i + 1]) ? $params[$i + 1] : '';
                                } elseif ($params[$i] === 'router') {
                                    $router = isset($params[$i + 1]) ? $params[$i + 1] : '';
                                }
                            }

                            // Validasi apakah semua parameter telah diisi
                            if ($username !== '' && $jumlah !== '' && $nomor !== '' && $paket !== '' && $router !== '') {

                                $checknamauser = $this->adminModel->checkamauser($username);
                                $checknomoruser = $this->adminModel->checkomoruser($nomor);

                                if ($checknamauser->getNumRows() > 0) {
                                    $response = [
                                        'text' => 'Nama user sudah terdaftar!',
                                    ];
                                } elseif ($checknomoruser->getNumRows() > 0) {
                                    $response = [
                                        'text' => 'Nomor handphone sudah terdaftar!',
                                    ];
                                } else {
                                    $getRouter = $this->rosModel->getData();
                                    $getServices = $this->adminModel->getServices();

                                    $foundservice = false;

                                    foreach ($getServices as $rowservice) {
                                        $idservice = $rowservice->id;
                                        if ($paket === $idservice) {
                                            $foundservice = true;
                                            break;
                                        }
                                    }

                                    $found = false;

                                    foreach ($getRouter as $row) {
                                        $idnya = $row->id;
                                        if ($router === $idnya) {
                                            $found = true;
                                            break;
                                        }
                                    }

                                    if ($foundservice && $found) {

                                        $paketlayanan = $this->adminModel->getServicesByID($paket);
                                        foreach ($paketlayanan->getResult() as $datalayanan) {
                                            $modenya = $datalayanan->mode;
                                            $namapaket = $datalayanan->paket;
                                            $pppprofile = $datalayanan->ppp_profile;
                                        }

                                        if ($pppprofile == null) {
                                            $text = '*ERROR:* Lakukan sinkronisasi PPP terlebih dahulu pada server website';
                                            $response = [
                                                'text' => $text,
                                            ];
                                        } else {
                                            $cekrouter = $this->rosModel->getDatabyID($router);
                                            foreach ($cekrouter->getResultArray() as $data) {
                                                $hostserver = $data['ip'];
                                                $usernameserver = $data['username'];
                                                $passwordserver = legacy_decrypt($data['password']);
                                            }
                                            $hariini = date('Y-m-d');
                                            $masa = date('Y-m-d', strtotime('+1 month', strtotime($hariini)));

                                            if ($modenya == 'hotspot') {
                                                if ($this->ros->connect($hostserver, $usernameserver, $passwordserver)) {

                                                    $usernames = [];
                                                    for ($n = 1; $n <= $jumlah; $n++) {
                                                        $usernames[] = randULC(2);
                                                    }

                                                    $pass = random_number(5);

                                                    $text = "Created By System ✅\n";
                                                    $text .= "Kamu Berhasil Membuat Member\n";
                                                    $text .= "Berikut User Dan Pass Member\n";
                                                    $text .= "==============================\n";

                                                    for ($n = 1; $n <= $jumlah; $n++) {
                                                        $this->ros->comm('/ip/hotspot/user/add', [
                                                            'server' => 'all',
                                                            'name' => $username.$usernames[$n - 1],
                                                            'password' => $pass,
                                                            'profile' => 'default',
                                                        ]);

                                                        $insertMember = [
                                                            'username' => $username.$usernames[$n - 1],
                                                            'nomor' => $nomor,
                                                            'status' => 'Active',
                                                            'date' => $hariini,
                                                            'expdate' => $masa,
                                                            'id_router' => $router,
                                                        ];

                                                        $tabeluser = $this->adminModel->insertMember($insertMember);

                                                        $text .= "Member $n dari $jumlah Member.\n";
                                                        $text .= 'Username : '.$username.$usernames[$n - 1]."\n";
                                                        $text .= "Password : $pass\n";
                                                        $text .= "==============================\n";
                                                    }

                                                    $text .= "Best Regards\n";
                                                    $text .= 'Myserv Bot Djunardi Ali';

                                                    $insertUsers = [
                                                        'email' => $username.'@gmail.com',
                                                        'nama' => $username,
                                                        'nomor' => $nomor,
                                                        'password' => password_hash($pass, PASSWORD_DEFAULT),
                                                        'level' => 'member',
                                                        'verify_account' => '1',
                                                        'status_account' => 'Active',
                                                    ];

                                                    $tabeluser = $this->adminModel->insertUser($insertUsers);

                                                    $response = ['text' => $text];
                                                } else {
                                                    $text = '*ERROR:* Router Not Connected';
                                                    $response = ['text' => $text];
                                                }
                                            } else {
                                                $text = '*ERROR:* Paket tersebut bukan untuk hotspot !';
                                                $response = ['text' => $text];
                                            }
                                        }
                                    } else {
                                        if (! $foundservice) {
                                            $text = '*ERROR:* _ID_ Layanan tidak ditemukan !';
                                            $text .= "\n\n";
                                            $text .= 'List Layanan yang tersedia : ';
                                            $text .= "\n\n";
                                            foreach ($getServices as $row) {
                                                $text .= 'ID : '.$row->id."\n".'Nama Layanan  : '.$row->paket;
                                                $text .= "\n\n";
                                            }
                                            $response = [
                                                'text' => $text,
                                            ];
                                        } elseif (! $found) {
                                            $text = '*ERROR:* _ID_ Router tidak ditemukan !';
                                            $text .= "\n\n";
                                            $text .= 'List Device yang tersedia : ';
                                            $text .= "\n\n";
                                            foreach ($getRouter as $row) {
                                                $text .= 'ID : '.$row->id."\n".'Nama Server  : '.$row->nama;
                                                $text .= "\n\n";
                                            }
                                            $response = [
                                                'text' => $text,
                                            ];
                                        }
                                    }
                                }
                            } else {
                                $text = '*ERROR:* Format tidak sesuai !';
                                $text .= "\n\n";
                                $text .= 'Format : /addhs username [username] jumlah [jumlah_akun_yang_ingin_dibuat] nomor [nomor_pelanggan] paket [id_paket] router [router_id]';
                                $text .= "\n";
                                $text .= 'Contoh : /addhs username adinda jumlah 2 nomor 082159512358 paket 1 router 1';
                                $response = ['text' => $text];
                            }
                            break;

                        case '/disabledhs':
                            $nomor = '';

                            for ($i = 1; $i < count($params); $i++) {
                                if ($params[$i] === 'nomor') {
                                    $nomor = isset($params[$i + 1]) ? $params[$i + 1] : '';
                                }
                            }

                            // Validasi apakah semua parameter telah diisi
                            if ($nomor !== '') {
                                $ceknomor = $this->adminModel->getNumbersMembers($nomor);
                                if ($ceknomor) {
                                    $usernames = [];
                                    $idrouter = '';

                                    foreach ($ceknomor as $datamember) {
                                        $usernames[] = $datamember->username;
                                        $idrouter = $datamember->id_router;
                                    }

                                    $cekrouter = $this->rosModel->getDatabyID($idrouter);
                                    foreach ($cekrouter->getResultArray() as $data) {
                                        $hostserver = $data['ip'];
                                        $usernameserver = $data['username'];
                                        $passwordserver = legacy_decrypt($data['password']);
                                    }

                                    if ($this->ros->connect($hostserver, $usernameserver, $passwordserver)) {
                                        foreach ($usernames as $userhs) {
                                            $this->ros->comm('/ip/hotspot/user/disable', [
                                                'numbers' => $userhs,
                                            ]);

                                            // Find and remove active PPPoE sessions by username
                                            $active = $this->ros->comm('/ip/hotspot/active/print', [
                                                '?user' => $userhs,
                                            ]);

                                            if (! empty($active)) {
                                                foreach ($active as $act) {
                                                    $this->ros->comm(
                                                        '/ip/hotspot/active/remove',
                                                        [
                                                            '.id' => $act['.id'],
                                                        ]
                                                    );
                                                }
                                            }
                                        }

                                        $updateMembers = [
                                            'status' => 'Isolir',
                                        ];
                                        $this->adminModel->UpdateMembersByNomor($nomor, $updateMembers);

                                        $text = "Disabled By System ✅\n";
                                        $text .= "Kamu Berhasil disabled member hotspot\n";
                                        $text .= "Best Regards\n";
                                        $text .= 'Myserv Bot';
                                        $response = ['text' => $text];
                                    } else {
                                        $text = '*ERROR:* Router Not Connected';
                                        $response = ['text' => $text];
                                    }
                                } else {
                                    $text = '*ERROR:* Nomor tersebut belum menjadi member, silahkan ketikan register di command /addmember';
                                    $response = ['text' => $text];
                                }
                            } else {
                                $text = '*ERROR:* Format tidak sesuai !';
                                $text .= "\n\n";
                                $text .= 'Format : /disabledhs nomor [nomor_pelanggan] ';
                                $text .= "\n";
                                $text .= 'Contoh : /disabledhs nomor 082159512358';
                                $response = ['text' => $text];
                            }
                            break;

                        case '/openhs':

                            $nomor = '';

                            for ($i = 1; $i < count($params); $i++) {
                                if ($params[$i] === 'nomor') {
                                    $nomor = isset($params[$i + 1]) ? $params[$i + 1] : '';
                                }
                            }

                            // Validasi apakah semua parameter telah diisi
                            if ($nomor !== '') {
                                $ceknomor = $this->adminModel->getNumbersMembers($nomor);
                                if ($ceknomor) {
                                    $usernames = [];
                                    $idrouter = '';

                                    foreach ($ceknomor as $datamember) {
                                        $usernames[] = $datamember->username;
                                        $idrouter = $datamember->id_router;
                                    }

                                    $cekrouter = $this->rosModel->getDatabyID($idrouter);
                                    foreach ($cekrouter->getResultArray() as $data) {
                                        $hostserver = $data['ip'];
                                        $usernameserver = $data['username'];
                                        $passwordserver = legacy_decrypt($data['password']);
                                    }
                                    if ($this->ros->connect($hostserver, $usernameserver, $passwordserver)) {
                                        foreach ($usernames as $userhs) {
                                            $this->ros->comm('/ip/hotspot/user/enable', [
                                                'numbers' => $userhs,
                                            ]);
                                        }

                                        $updateMembers = [
                                            'status' => 'Active',
                                        ];
                                        $this->adminModel->UpdateMembersByNomor($nomor, $updateMembers);

                                        $text = "Enabled By System ✅\n";
                                        $text .= "Kamu Berhasil enabled member hotspot\n";
                                        $text .= "Best Regards\n";
                                        $text .= 'Myserv Bot';
                                        $response = ['text' => $text];
                                    } else {
                                        $text = '*ERROR:* Router Not Connected';
                                        $response = ['text' => $text];
                                    }
                                } else {
                                    $text = '*ERROR:* Nomor tersebut belum menjadi member, silahkan ketikan register di command /addmember';
                                    $response = ['text' => $text];
                                }
                            } else {
                                $text = '*ERROR:* Format tidak sesuai !';
                                $text .= "\n\n";
                                $text .= 'Format : /openhs nomor [nomor_pelanggan] ';
                                $text .= "\n";
                                $text .= 'Contoh : /openhs nomor 082159512358';
                                $response = ['text' => $text];
                            }

                            break;

                        case '/openppp':

                            $username = '';

                            for ($i = 1; $i < count($params); $i++) {
                                if ($params[$i] === 'username') {
                                    $username = isset($params[$i + 1]) ? $params[$i + 1] : '';
                                }
                            }

                            // Validasi apakah semua parameter telah diisi
                            if ($username !== '') {
                                $cekusername = $this->adminModel->getOrdersByPPPOE($username);
                                if ($cekusername) {
                                    $usernames = '';
                                    $idrouter = '';

                                    foreach ($cekusername as $datamember) {
                                        $idrouter = $datamember->id_router;
                                    }

                                    $cekrouter = $this->rosModel->getDatabyID($idrouter);
                                    foreach ($cekrouter->getResultArray() as $data) {
                                        $hostserver = $data['ip'];
                                        $usernameserver = $data['username'];
                                        $passwordserver = legacy_decrypt($data['password']);
                                    }
                                    if ($this->ros->connect($hostserver, $usernameserver, $passwordserver)) {
                                        $this->ros->comm('/ppp/secret/enable', [
                                            'numbers' => $username,
                                        ]);

                                        $updateOrders = [
                                            'status' => 'Active',
                                        ];
                                        $this->adminModel->UpdateOrdersByPPPOE($username, $updateOrders);

                                        $text = "Enabled By System ✅\n";
                                        $text .= "Kamu Berhasil mengaktifkan ppp secret\n";
                                        $text .= "Best Regards\n";
                                        $text .= 'Myserv Bot';
                                        $response = ['text' => $text];
                                    } else {
                                        $text = '*ERROR:* Router Not Connected';
                                        $response = ['text' => $text];
                                    }
                                } else {
                                    $text = '*ERROR:* PPPOE User tersebut tidak ada atau anda belum mensinkronkan data pppoe ke dalam server web';
                                    $response = ['text' => $text];
                                }
                            } else {
                                $text = '*ERROR:* Format tidak sesuai !';
                                $text .= "\n\n";
                                $text .= 'Format : /openppp username [pppoe_user] ';
                                $text .= "\n";
                                $text .= 'Contoh : /openppp username normanto';
                                $response = ['text' => $text];
                            }
                            break;

                        case '/gantipass':
                            $nomor = '';

                            for ($i = 1; $i < count($params); $i++) {
                                if ($params[$i] === 'nomor') {
                                    $nomor = isset($params[$i + 1]) ? $params[$i + 1] : '';
                                }
                            }

                            // Validasi apakah semua parameter telah diisi
                            if ($nomor !== '') {
                                $ceknomor = $this->adminModel->getNumbersMembers($nomor);
                                if ($ceknomor) {
                                    $usernames = [];
                                    $idrouter = '';

                                    foreach ($ceknomor as $datamember) {
                                        $usernames[] = $datamember->username;
                                        $idrouter = $datamember->id_router;
                                    }

                                    $pass = random_number(5);

                                    $cekrouter = $this->rosModel->getDatabyID($idrouter);
                                    foreach ($cekrouter->getResultArray() as $data) {
                                        $hostserver = $data['ip'];
                                        $usernameserver = $data['username'];
                                        $passwordserver = legacy_decrypt($data['password']);
                                    }

                                    if ($this->ros->connect($hostserver, $usernameserver, $passwordserver)) {
                                        $text = "Generate New Password By System ✅\n";
                                        $text .= "Kamu Berhasil melakukan perubahan password member hotspot\n";

                                        foreach ($usernames as $index => $userhs) {
                                            $this->ros->comm('/ip/hotspot/user/set', [
                                                'numbers' => $userhs,
                                                'password' => $pass,
                                            ]);

                                            // Find and remove active PPPoE sessions by username
                                            $active = $this->ros->comm('/ip/hotspot/active/print', [
                                                '?user' => $userhs,
                                            ]);

                                            if (! empty($active)) {
                                                foreach ($active as $act) {
                                                    $this->ros->comm(
                                                        '/ip/hotspot/active/remove',
                                                        [
                                                            '.id' => $act['.id'],
                                                        ]
                                                    );
                                                }
                                            }

                                            $text .= "==============================\n";
                                            $text .= 'Member '.($index + 1).' dari '.count($usernames)." Member.\n";
                                            $text .= 'Username: '.$userhs."\n";
                                            $text .= 'Password: '.$pass."\n";
                                        }

                                        $text .= "==============================\n";
                                        $text .= "Best Regards\n";
                                        $text .= 'Myserv Bot';
                                        $response = ['text' => $text];
                                    } else {
                                        $text = '*ERROR:* Router Not Connected';
                                        $response = ['text' => $text];
                                    }
                                } else {
                                    $text = '*ERROR:* Nomor tersebut belum menjadi member, silahkan ketikan register di command /addmember';
                                    $response = ['text' => $text];
                                }
                            } else {
                                $text = '*ERROR:* Format tidak sesuai !';
                                $text .= "\n\n";
                                $text .= 'Format : /gantipass nomor [nomor_pelanggan] ';
                                $text .= "\n";
                                $text .= 'Contoh : /gantipass nomor 082159512358';
                                $response = ['text' => $text];
                            }
                            break;

                        case '/gpass':
                            // Cek mode: ppp, hs, atau default (untuk member)
                            $mode = isset($params[1]) ? strtolower(trim($params[1])) : '';

                            // Debug log
                            if ($debugMode) {
                                file_put_contents(storage_path('logs/whatsapp.txt'), '[DEBUG /gpass] Raw params: '.json_encode($params)."\n", FILE_APPEND);
                                file_put_contents(storage_path('logs/whatsapp.txt'), '[DEBUG /gpass] Mode detected: '.$mode."\n", FILE_APPEND);
                                file_put_contents(storage_path('logs/whatsapp.txt'), '[DEBUG /gpass] Level: '.$level."\n", FILE_APPEND);
                            }

                            // Mode untuk Admin/Developer: /gpass ppp atau /gpass hs
                            if (($mode === 'ppp' || $mode === 'hs') && ($level == 'admin' || $level == 'developer')) {
                                $username = '';
                                $password = '';

                                // Filter empty strings dari params
                                $params = array_values(array_filter($params, function ($val) {
                                    return trim($val) !== '';
                                }));

                                // Debug after filter
                                if ($debugMode) {
                                    file_put_contents(storage_path('logs/whatsapp.txt'), '[DEBUG /gpass] Filtered params: '.json_encode($params)."\n", FILE_APPEND);
                                }

                                for ($i = 0; $i < count($params); $i++) {
                                    if (trim($params[$i]) === 'username' && isset($params[$i + 1])) {
                                        $username = trim($params[$i + 1]);
                                    } elseif (trim($params[$i]) === 'password' && isset($params[$i + 1])) {
                                        $password = trim($params[$i + 1]);
                                    }
                                }

                                // Debug parsed values
                                if ($debugMode) {
                                    file_put_contents(storage_path('logs/whatsapp.txt'), '[DEBUG /gpass] Username: '.$username."\n", FILE_APPEND);
                                    file_put_contents(storage_path('logs/whatsapp.txt'), '[DEBUG /gpass] Password: '.$password."\n\n", FILE_APPEND);
                                }

                                if ($username !== '' && $password !== '') {
                                    if ($debugMode) {
                                        file_put_contents(storage_path('logs/whatsapp.txt'), '[DEBUG /gpass] Entering main process...\n', FILE_APPEND);
                                    }

                                    if ($mode === 'ppp') {
                                        // Ganti password PPPoE - cek by mode 'pppoe'
                                        $db = new WebhookDbCompat;
                                        $builder = $db->table('orders');
                                        $builder->where('pppoe_user', $username);
                                        $builder->where('mode', 'pppoe');
                                        $query = $builder->get();
                                        $cekdata = $query->getResult();

                                        if ($debugMode) {
                                            file_put_contents(storage_path('logs/whatsapp.txt'), '[DEBUG /gpass ppp] Query result count: '.count($cekdata)."\n", FILE_APPEND);
                                        }

                                        if (! empty($cekdata)) {
                                            $idrouter = '';
                                            foreach ($cekdata as $dataorder) {
                                                $idrouter = $dataorder->id_router;
                                            }

                                            $cekrouter = $this->rosModel->getDatabyID($idrouter);
                                            foreach ($cekrouter->getResultArray() as $data) {
                                                $hostserver = $data['ip'];
                                                $usernameserver = $data['username'];
                                                $passwordserver = legacy_decrypt($data['password']);
                                            }

                                            if ($this->ros->connect($hostserver, $usernameserver, $passwordserver)) {
                                                // Cek apakah user ada di MikroTik
                                                $pppUser = $this->ros->comm('/ppp/secret/print', [
                                                    '?name' => $username,
                                                ]);

                                                if (! empty($pppUser)) {
                                                    // User ada di MikroTik, update password
                                                    $this->ros->comm('/ppp/secret/set', [
                                                        'numbers' => $username,
                                                        'password' => $password,
                                                    ]);

                                                    // Kick active session
                                                    $activeSession = $this->ros->comm('/ppp/active/print', [
                                                        '?name' => $username,
                                                    ]);

                                                    if (! empty($activeSession)) {
                                                        foreach ($activeSession as $session) {
                                                            $this->ros->comm('/ppp/active/remove', [
                                                                '.id' => $session['.id'],
                                                            ]);
                                                        }
                                                    }

                                                    $text = "✅ *Password PPPoE Berhasil Diubah*\n\n";
                                                    $text .= "👤 Username: $username\n";
                                                    $text .= "🔐 Password Baru: $password\n\n";
                                                    $text .= "📊 Status: User ditemukan di Database dan MikroTik\n";
                                                    $text .= '🔌 Session aktif telah di-disconnect.';
                                                } else {
                                                    $text = "❌ *ERROR: User tidak ditemukan di MikroTik!*\n\n";
                                                    $text .= "User ada di database tapi tidak ada di router.\n";
                                                    $text .= 'Hubungi Admin untuk sinkronisasi data.';
                                                }

                                                $response = ['text' => $text];
                                            } else {
                                                $text = "❌ *ERROR: Router Not Connected*\n\n";
                                                $text .= "Tidak dapat terhubung ke router.\n";
                                                $text .= 'Hubungi Administrator.';
                                                $response = ['text' => $text];
                                            }
                                        } else {
                                            $text = "❌ *ERROR: Username PPPoE tidak ditemukan!*\n\n";
                                            $text .= "*Pastikan:*\n";
                                            $text .= "1️⃣ Username ada di tabel orders\n";
                                            $text .= '2️⃣ Mode = pppoe';
                                            $response = ['text' => $text];
                                        }

                                    } elseif ($mode === 'hs') {
                                        if ($debugMode) {
                                            file_put_contents(storage_path('logs/whatsapp.txt'), '[DEBUG /gpass hs] Entering HS mode...\n', FILE_APPEND);
                                        }

                                        // Ganti password Hotspot - cek by mode 'hotspot'
                                        $db = new WebhookDbCompat;
                                        $builder = $db->table('orders');
                                        $builder->where('pppoe_user', $username);
                                        $builder->where('mode', 'hotspot');
                                        $query = $builder->get();
                                        $cekdata = $query->getResult();

                                        if ($debugMode) {
                                            file_put_contents(storage_path('logs/whatsapp.txt'), '[DEBUG /gpass hs] Query result count: '.count($cekdata)."\n", FILE_APPEND);
                                        }

                                        if (! empty($cekdata)) {
                                            $idrouter = '';
                                            foreach ($cekdata as $dataorder) {
                                                $idrouter = $dataorder->id_router;
                                            }

                                            $cekrouter = $this->rosModel->getDatabyID($idrouter);
                                            foreach ($cekrouter->getResultArray() as $data) {
                                                $hostserver = $data['ip'];
                                                $usernameserver = $data['username'];
                                                $passwordserver = legacy_decrypt($data['password']);
                                            }

                                            if ($this->ros->connect($hostserver, $usernameserver, $passwordserver)) {
                                                // Cek apakah user ada di MikroTik
                                                $hsUser = $this->ros->comm('/ip/hotspot/user/print', [
                                                    '?name' => $username,
                                                ]);

                                                if (! empty($hsUser)) {
                                                    // User ada di MikroTik, update password
                                                    $this->ros->comm('/ip/hotspot/user/set', [
                                                        'numbers' => $username,
                                                        'password' => $password,
                                                    ]);

                                                    // Kick active session
                                                    $activeSession = $this->ros->comm('/ip/hotspot/active/print', [
                                                        '?user' => $username,
                                                    ]);

                                                    if (! empty($activeSession)) {
                                                        foreach ($activeSession as $session) {
                                                            $this->ros->comm('/ip/hotspot/active/remove', [
                                                                '.id' => $session['.id'],
                                                            ]);
                                                        }
                                                    }

                                                    $text = "✅ *Password Hotspot Berhasil Diubah*\n\n";
                                                    $text .= "👤 Username: $username\n";
                                                    $text .= "🔐 Password Baru: $password\n\n";
                                                    $text .= "📊 Status: User ditemukan di Database dan MikroTik\n";
                                                    $text .= '🔌 Session aktif telah di-disconnect.';
                                                } else {
                                                    $text = "❌ *ERROR: User tidak ditemukan di MikroTik!*\n\n";
                                                    $text .= "User ada di database tapi tidak ada di router.\n";
                                                    $text .= 'Hubungi Admin untuk sinkronisasi data.';
                                                }

                                                $response = ['text' => $text];
                                            } else {
                                                $text = "❌ *ERROR: Router Not Connected*\n\n";
                                                $text .= "Tidak dapat terhubung ke router.\n";
                                                $text .= 'Hubungi Administrator.';
                                                $response = ['text' => $text];
                                            }
                                        } else {
                                            $text = "❌ *ERROR: Username Hotspot tidak ditemukan!*\n\n";
                                            $text .= "*Pastikan:*\n";
                                            $text .= "1️⃣ Username ada di buat\n";
                                            $text .= '2️⃣ Mode = hotspot';
                                            $response = ['text' => $text];
                                        }
                                    }
                                } else {
                                    if ($debugMode) {
                                        file_put_contents(storage_path('logs/whatsapp.txt'), '[DEBUG /gpass] ERROR: Username or Password empty!\n', FILE_APPEND);
                                        file_put_contents(storage_path('logs/whatsapp.txt'), '[DEBUG /gpass] Username check: "'.$username.'" (empty: '.($username === '' ? 'YES' : 'NO').")\n", FILE_APPEND);
                                        file_put_contents(storage_path('logs/whatsapp.txt'), '[DEBUG /gpass] Password check: "'.$password.'" (empty: '.($password === '' ? 'YES' : 'NO').")\n\n", FILE_APPEND);
                                    }

                                    $text = "❌ *ERROR: Format tidak sesuai!*\n\n";
                                    if ($mode === 'ppp') {
                                        $text .= "📝 Format:\n";
                                        $text .= "/gpass ppp username [username] password [password]\n\n";
                                        $text .= "💡 Contoh:\n";
                                        $text .= '/gpass ppp username johndoe password Pass123';
                                    } else {
                                        $text .= "📝 Format:\n";
                                        $text .= "/gpass hs username [username] password [password]\n\n";
                                        $text .= "💡 Contoh:\n";
                                        $text .= '/gpass hs username johndoe password Pass123';
                                    }
                                    $response = ['text' => $text];
                                }

                            } else {
                                // Mode untuk Member: /gpass username (auto generate password)
                                $username = '';

                                for ($i = 1; $i < count($params); $i++) {
                                    if ($params[$i] === 'username') {
                                        $username = isset($params[$i + 1]) ? $params[$i + 1] : '';
                                    }
                                }

                                if ($username !== '') {
                                    $cekdata = $this->adminModel->getUsernameByNumbers($username, $nomorbysend);
                                    if ($cekdata) {
                                        $idrouter = '';
                                        $usernames = [];

                                        foreach ($cekdata as $datamember) {
                                            $usernames[] = $datamember->username;
                                            $idrouter = $datamember->id_router;
                                        }
                                        $pass = random_number(5);

                                        $cekrouter = $this->rosModel->getDatabyID($idrouter);
                                        foreach ($cekrouter->getResultArray() as $data) {
                                            $hostserver = $data['ip'];
                                            $usernameserver = $data['username'];
                                            $passwordserver = legacy_decrypt($data['password']);
                                        }

                                        if ($this->ros->connect($hostserver, $usernameserver, $passwordserver)) {
                                            $text = "Generate New Password By System ✅\n";
                                            $text .= "Kamu Berhasil melakukan perubahan password member hotspot\n";

                                            foreach ($usernames as $index => $userhs) {
                                                $this->ros->comm('/ip/hotspot/user/set', [
                                                    'numbers' => $userhs,
                                                    'password' => $pass,
                                                ]);

                                                $active = $this->ros->comm('/ip/hotspot/active/print', [
                                                    '?user' => $userhs,
                                                ]);

                                                if (! empty($active)) {
                                                    foreach ($active as $act) {
                                                        $this->ros->comm(
                                                            '/ip/hotspot/active/remove',
                                                            [
                                                                '.id' => $act['.id'],
                                                            ]
                                                        );
                                                    }
                                                }

                                                $text .= "==============================\n";
                                                $text .= 'Username: '.$userhs."\n";
                                                $text .= 'Password: '.$pass."\n";
                                            }

                                            $text .= "==============================\n";
                                            $text .= "Best Regards\n";
                                            $text .= 'Myserv Bot';
                                            $response = ['text' => $text];
                                        } else {
                                            $text = '*ERROR:* Router Not Connected';
                                            $response = ['text' => $text];
                                        }
                                    } else {
                                        $text = '*ERROR:* Data tidak sesuai, nomor anda tidak sesuai dengan yang ada di database';
                                        $response = ['text' => $text];
                                    }
                                } else {
                                    $text = '*ERROR:* Format tidak sesuai !';
                                    $text .= "\n\n";
                                    $text .= 'Format : /gpass username [username mu] ';
                                    $text .= "\n";
                                    $text .= 'Contoh : /gpass username normanto';
                                    $response = ['text' => $text];
                                }
                            }
                            break;

                        case '/delhost':
                            $username = '';

                            for ($i = 1; $i < count($params); $i++) {
                                if ($params[$i] === 'username') {
                                    $username = isset($params[$i + 1]) ? $params[$i + 1] : '';
                                }
                            }

                            // Validasi apakah semua parameter telah diisi
                            if ($username !== '') {
                                $cekdata = $this->adminModel->getUsernameByNumbers($username, $nomorbysend);
                                if ($cekdata) {
                                    $idrouter = '';
                                    $usernames = [];

                                    foreach ($cekdata as $datamember) {
                                        $usernames[] = $datamember->username;
                                        $idrouter = $datamember->id_router;
                                    }
                                    $cekrouter = $this->rosModel->getDatabyID($idrouter);
                                    foreach ($cekrouter->getResultArray() as $data) {
                                        $hostserver = $data['ip'];
                                        $usernameserver = $data['username'];
                                        $passwordserver = legacy_decrypt($data['password']);
                                    }

                                    if ($this->ros->connect($hostserver, $usernameserver, $passwordserver)) {

                                        foreach ($usernames as $index => $userhs) {
                                            $active = $this->ros->comm('/ip/hotspot/active/print', [
                                                '?user' => $userhs,
                                            ]);

                                            if (! empty($active)) {
                                                foreach ($active as $act) {
                                                    $this->ros->comm(
                                                        '/ip/hotspot/active/remove',
                                                        [
                                                            '.id' => $act['.id'],
                                                        ]
                                                    );
                                                }
                                            }

                                            $text = "Berhasil melakukan Delete Hotspot Active ✅\n";
                                        }
                                    } else {
                                        $text = '*ERROR:* Router Not Connected';
                                        $response = ['text' => $text];
                                    }
                                } else {
                                    $text = '*ERROR:* Data tidak sesuai, nomor anda tidak sesuai dengan yang ada di database';
                                    $response = ['text' => $text];
                                }
                            } else {
                                $text = '*ERROR:* Format tidak sesuai !';
                                $text .= "\n\n";
                                $text .= 'Format : /delhost username [username mu] ';
                                $text .= "\n";
                                $text .= 'Contoh : /delhost username normanto';
                                $response = ['text' => $text];
                            }

                            break;

                        case '/cekpaket':
                            // Command untuk cek daftar ID paket/layanan (hanya untuk admin/developer)
                            if ($level == 'admin' || $level == 'developer') {
                                // Parse parameter router (opsional)
                                $routerId = '';
                                for ($i = 1; $i < count($params); $i++) {
                                    if ($params[$i] === 'router') {
                                        $routerId = isset($params[$i + 1]) ? $params[$i + 1] : '';
                                        break;
                                    }
                                }

                                $getServices = $this->adminModel->getServices();

                                if ($getServices) {
                                    // Filter paket jika ada router ID
                                    $filteredServices = [];
                                    if ($routerId !== '') {
                                        foreach ($getServices as $row) {
                                            // Asumsi: tabel services punya field id_router atau router_id
                                            // Sesuaikan dengan struktur database Anda
                                            if (isset($row->id_router) && $row->id_router == $routerId) {
                                                $filteredServices[] = $row;
                                            }
                                        }

                                        if (empty($filteredServices)) {
                                            $text = "❌ Tidak ada paket untuk Router ID: $routerId\n\n";
                                            $text .= 'Gunakan /cekrouter untuk melihat daftar router.';
                                            $response = ['text' => $text];
                                            break;
                                        }

                                        $displayServices = $filteredServices;
                                        $text = "📦 *PAKET LAYANAN - Router ID: $routerId* 📦\n";
                                    } else {
                                        $displayServices = $getServices;
                                        $text = "📦 *DAFTAR SEMUA PAKET LAYANAN* 📦\n";
                                    }

                                    $text .= "============================\n\n";

                                    foreach ($displayServices as $row) {
                                        $text .= '🔹 ID Paket : '.$row->id."\n";
                                        $text .= '   Nama Paket : '.$row->paket."\n";
                                        $text .= '   Mode : '.($row->mode ?? 'N/A')."\n";
                                        $text .= '   PPP Profile : '.($row->ppp_profile ?? 'N/A')."\n";
                                        if (isset($row->id_router)) {
                                            $text .= '   ID Router : '.$row->id_router."\n";
                                        }
                                        $text .= "----------------------------\n";
                                    }

                                    if ($routerId === '') {
                                        $text .= "\n💡 Filter by Router: /cekpaket router [ID]";
                                    }
                                } else {
                                    $text = '❌ Belum ada paket yang terdaftar di database.';
                                }
                            } else {
                                $text = '⛔ Command ini hanya untuk Admin/Developer.';
                            }

                            $response = ['text' => $text];
                            break;

                        case '/cekrouter':
                            // Command untuk cek daftar ID router/server (hanya untuk admin/developer)
                            if ($level == 'admin' || $level == 'developer') {
                                $getRouter = $this->rosModel->getData();

                                if ($getRouter) {
                                    $text = "🖥️ *DAFTAR ROUTER/SERVER* 🖥️\n";
                                    $text .= "============================\n\n";

                                    foreach ($getRouter as $row) {
                                        $text .= '🔸 ID Router : '.$row->id."\n";
                                        $text .= '   Nama Server : '.$row->nama."\n";
                                        $text .= '   IP Address : '.$row->ip."\n";
                                        $text .= '   Username : '.$row->username."\n";
                                        $text .= "----------------------------\n";
                                    }
                                } else {
                                    $text = '❌ Belum ada router yang terdaftar di database.';
                                }
                            } else {
                                $text = '⛔ Command ini hanya untuk Admin/Developer.';
                            }

                            $response = ['text' => $text];
                            break;

                        case '/cekhs':
                            // Cek Hotspot Active - traffic & uptime
                            if ($level == 'admin' || $level == 'developer') {
                                $username = '';

                                for ($i = 1; $i < count($params); $i++) {
                                    if ($params[$i] === 'username') {
                                        $username = isset($params[$i + 1]) ? $params[$i + 1] : '';
                                        break;
                                    }
                                }

                                if ($username !== '') {
                                    // Cek username di database
                                    $db = new WebhookDbCompat;
                                    $builder = $db->table('orders');
                                    $builder->where('pppoe_user', $username);
                                    $builder->where('mode', 'hotspot');
                                    $query = $builder->get();
                                    $cekdata = $query->getResult();

                                    if (! empty($cekdata)) {
                                        $idrouter = '';
                                        foreach ($cekdata as $dataorder) {
                                            $idrouter = $dataorder->id_router;
                                        }

                                        $cekrouter = $this->rosModel->getDatabyID($idrouter);
                                        foreach ($cekrouter->getResultArray() as $data) {
                                            $hostserver = $data['ip'];
                                            $usernameserver = $data['username'];
                                            $passwordserver = legacy_decrypt($data['password']);
                                        }

                                        if ($this->ros->connect($hostserver, $usernameserver, $passwordserver)) {
                                            // Query hotspot active
                                            $activeSession = $this->ros->comm('/ip/hotspot/active/print', [
                                                '?user' => $username,
                                            ]);

                                            if (! empty($activeSession)) {
                                                $text = "🌐 *HOTSPOT USER ACTIVE*\n";
                                                $text .= "============================\n\n";

                                                foreach ($activeSession as $session) {
                                                    $user = $session['user'] ?? 'N/A';
                                                    $address = $session['address'] ?? 'N/A';
                                                    $uptime = $session['uptime'] ?? '0s';
                                                    $bytesIn = isset($session['bytes-in']) ? $session['bytes-in'] : 0;
                                                    $bytesOut = isset($session['bytes-out']) ? $session['bytes-out'] : 0;
                                                    $server = $session['server'] ?? 'N/A';

                                                    // Convert bytes to readable format
                                                    $downloadMB = round($bytesIn / 1048576, 2); // Bytes to MB
                                                    $uploadMB = round($bytesOut / 1048576, 2);
                                                    $totalMB = round(($bytesIn + $bytesOut) / 1048576, 2);

                                                    $text .= "👤 Username: *$user*\n";
                                                    $text .= "📡 IP Address: $address\n";
                                                    $text .= "🖥️ Server: $server\n";
                                                    $text .= "⏱️ Uptime: $uptime\n\n";
                                                    $text .= "📊 *Traffic Usage:*\n";
                                                    $text .= "⬇️ Download: $downloadMB MB\n";
                                                    $text .= "⬆️ Upload: $uploadMB MB\n";
                                                    $text .= "📦 Total: $totalMB MB\n";
                                                    $text .= "============================\n";
                                                }

                                                $text .= "\n✅ Total session aktif: ".count($activeSession);
                                            } else {
                                                $text = "ℹ️ *User tidak sedang online*\n\n";
                                                $text .= "👤 Username: $username\n";
                                                $text .= "📊 Status: Offline\n\n";
                                                $text .= 'User ditemukan di database tapi tidak ada session aktif di router.';
                                            }

                                            $response = ['text' => $text];
                                        } else {
                                            $text = "❌ *ERROR: Router Not Connected*\n\n";
                                            $text .= "Tidak dapat terhubung ke router.\n";
                                            $text .= 'Hubungi Administrator.';
                                            $response = ['text' => $text];
                                        }
                                    } else {
                                        $text = "❌ *ERROR: Username tidak ditemukan!*\n\n";
                                        $text .= "Username: *$username*\n";
                                        $text .= "Mode: Hotspot\n\n";
                                        $text .= 'Pastikan username sudah terdaftar di database.';
                                        $response = ['text' => $text];
                                    }
                                } else {
                                    $text = "❌ *ERROR: Format tidak sesuai!*\n\n";
                                    $text .= "📝 *Format:*\n";
                                    $text .= "/cekhs username [username]\n\n";
                                    $text .= "💡 *Contoh:*\n";
                                    $text .= '/cekhs username user01';
                                    $response = ['text' => $text];
                                }
                            } else {
                                $text = '⛔ Command ini hanya untuk Admin/Developer.';
                                $response = ['text' => $text];
                            }
                            break;

                        case '/cekppp':
                            // Cek PPPoE Active - traffic & uptime
                            if ($level == 'admin' || $level == 'developer') {
                                $username = '';

                                for ($i = 1; $i < count($params); $i++) {
                                    if ($params[$i] === 'username') {
                                        $username = isset($params[$i + 1]) ? $params[$i + 1] : '';
                                        break;
                                    }
                                }

                                if ($username !== '') {
                                    // Cek username di database
                                    $db = new WebhookDbCompat;
                                    $builder = $db->table('orders');
                                    $builder->where('pppoe_user', $username);
                                    $builder->where('mode', 'pppoe');
                                    $query = $builder->get();
                                    $cekdata = $query->getResult();

                                    if (! empty($cekdata)) {
                                        $idrouter = '';
                                        foreach ($cekdata as $dataorder) {
                                            $idrouter = $dataorder->id_router;
                                        }

                                        $cekrouter = $this->rosModel->getDatabyID($idrouter);
                                        foreach ($cekrouter->getResultArray() as $data) {
                                            $hostserver = $data['ip'];
                                            $usernameserver = $data['username'];
                                            $passwordserver = legacy_decrypt($data['password']);
                                        }

                                        if ($this->ros->connect($hostserver, $usernameserver, $passwordserver)) {
                                            // Query PPP active
                                            $activeSession = $this->ros->comm('/ppp/active/print', [
                                                '?name' => $username,
                                            ]);

                                            if (! empty($activeSession)) {
                                                $text = "🔐 *PPPoE USER ACTIVE*\n";
                                                $text .= "============================\n\n";

                                                foreach ($activeSession as $session) {
                                                    $name = $session['name'] ?? 'N/A';
                                                    $address = $session['address'] ?? 'N/A';
                                                    $uptime = $session['uptime'] ?? '0s';
                                                    $service = $session['service'] ?? 'N/A';
                                                    $callerID = $session['caller-id'] ?? 'N/A';

                                                    // Get bytes-in and bytes-out (di PPP mungkin tidak ada, atau ada di format berbeda)
                                                    $bytesIn = 0;
                                                    $bytesOut = 0;

                                                    // Coba ambil dari interface stats jika ada
                                                    if (isset($session['.id'])) {
                                                        $stats = $this->ros->comm('/ppp/active/print', [
                                                            '.proplist' => '.id,name,uptime,address,bytes-in,bytes-out',
                                                            '?.id' => $session['.id'],
                                                        ]);

                                                        if (! empty($stats) && isset($stats[0])) {
                                                            $bytesIn = isset($stats[0]['bytes-in']) ? $stats[0]['bytes-in'] : 0;
                                                            $bytesOut = isset($stats[0]['bytes-out']) ? $stats[0]['bytes-out'] : 0;
                                                        }
                                                    }

                                                    // Convert bytes to readable format
                                                    $downloadMB = round($bytesIn / 1048576, 2);
                                                    $uploadMB = round($bytesOut / 1048576, 2);
                                                    $totalMB = round(($bytesIn + $bytesOut) / 1048576, 2);

                                                    $text .= "👤 Username: *$name*\n";
                                                    $text .= "📡 IP Address: $address\n";
                                                    $text .= "🔌 Service: $service\n";
                                                    $text .= "📞 Caller ID: $callerID\n";
                                                    $text .= "⏱️ Uptime: $uptime\n\n";
                                                    $text .= "📊 *Traffic Usage:*\n";
                                                    $text .= "⬇️ Download: $downloadMB MB\n";
                                                    $text .= "⬆️ Upload: $uploadMB MB\n";
                                                    $text .= "📦 Total: $totalMB MB\n";
                                                    $text .= "============================\n";
                                                }

                                                $text .= "\n✅ Total session aktif: ".count($activeSession);
                                            } else {
                                                $text = "ℹ️ *User tidak sedang online*\n\n";
                                                $text .= "👤 Username: $username\n";
                                                $text .= "📊 Status: Offline\n\n";
                                                $text .= 'User ditemukan di database tapi tidak ada session aktif di router.';
                                            }

                                            $response = ['text' => $text];
                                        } else {
                                            $text = "❌ *ERROR: Router Not Connected*\n\n";
                                            $text .= "Tidak dapat terhubung ke router.\n";
                                            $text .= 'Hubungi Administrator.';
                                            $response = ['text' => $text];
                                        }
                                    } else {
                                        $text = "❌ *ERROR: Username tidak ditemukan!*\n\n";
                                        $text .= "Username: *$username*\n";
                                        $text .= "Mode: PPPoE\n\n";
                                        $text .= 'Pastikan username sudah terdaftar di database.';
                                        $response = ['text' => $text];
                                    }
                                } else {
                                    $text = "❌ *ERROR: Format tidak sesuai!*\n\n";
                                    $text .= "📝 *Format:*\n";
                                    $text .= "/cekppp username [username]\n\n";
                                    $text .= "💡 *Contoh:*\n";
                                    $text .= '/cekppp username pppuser01';
                                    $response = ['text' => $text];
                                }
                            } else {
                                $text = '⛔ Command ini hanya untuk Admin/Developer.';
                                $response = ['text' => $text];
                            }
                            break;

                        case '/cekredaman':
                            // Cek redaman ONT berdasarkan tag
                            if ($level == 'admin' || $level == 'developer') {
                                if ($debugMode) {
                                    file_put_contents(storage_path('logs/whatsapp.txt'), '[DEBUG /cekredaman] Command received at '.date('Y-m-d H:i:s')."\n", FILE_APPEND);
                                    file_put_contents(storage_path('logs/whatsapp.txt'), '[DEBUG /cekredaman] Params: '.json_encode($params)."\n", FILE_APPEND);
                                }

                                // Parse tag dari params
                                $tag = '';
                                for ($i = 1; $i < count($params); $i++) {
                                    if ($params[$i] === 'tag') {
                                        $tag = isset($params[$i + 1]) ? $params[$i + 1] : '';
                                        break;
                                    }
                                }

                                if ($debugMode) {
                                    file_put_contents(storage_path('logs/whatsapp.txt'), '[DEBUG /cekredaman] Tag parsed: '.$tag."\n", FILE_APPEND);
                                }

                                if ($tag !== '') {
                                    // Ambil host ACS dari tabel `acs` (fitur redaman butuh server ACS aktif).
                                    // Sebelumnya me-require app/Http/Controllers/service.php + memanggil
                                    // \ACSRequest::getDevicesByTags() yang tidak ada — keduanya diganti dengan
                                    // library ACSRequest yang sebenarnya (App\Libraries\ACSRequest).
                                    $acsRow = DB::table('acs')->whereNotNull('url')->first();
                                    $acsHost = $acsRow->url ?? null;

                                    if ($debugMode) {
                                        file_put_contents(storage_path('logs/whatsapp.txt'), '[DEBUG /cekredaman] ACS host: '.($acsHost ?? 'NULL')."\n", FILE_APPEND);
                                    }

                                    if (empty($acsHost)) {
                                        $text = '❌ Server ACS belum dikonfigurasi.';
                                        $response = ['text' => $text];
                                        break;
                                    }

                                    try {
                                        // Set timeout untuk keseluruhan proses
                                        set_time_limit(45);

                                        if ($debugMode) {
                                            file_put_contents(storage_path('logs/whatsapp.txt'), '[DEBUG /cekredaman] Calling ACS for tag: '.$tag."\n", FILE_APPEND);
                                            file_put_contents(storage_path('logs/whatsapp.txt'), '[DEBUG /cekredaman] Timestamp before API call: '.date('H:i:s')."\n", FILE_APPEND);
                                        }

                                        // Query device berdasarkan tag GenieACS (_tags).
                                        $acsRequest = new ACSRequest($acsHost);
                                        $devices = json_decode($acsRequest->curl('/devices', ['_tags' => $tag]), true);
                                        $searchType = 'tag';
                                        $searchValue = $tag;

                                        if ($debugMode) {
                                            file_put_contents(storage_path('logs/whatsapp.txt'), '[DEBUG /cekredaman] Timestamp after API call: '.date('H:i:s')."\n", FILE_APPEND);
                                            file_put_contents(storage_path('logs/whatsapp.txt'), '[DEBUG /cekredaman] API returned: '.gettype($devices)."\n", FILE_APPEND);
                                            if (is_array($devices)) {
                                                file_put_contents(storage_path('logs/whatsapp.txt'), '[DEBUG /cekredaman] Device count: '.count($devices)."\n", FILE_APPEND);
                                            }
                                        }

                                        if (is_array($devices) && ! empty($devices)) {
                                            if ($debugMode) {
                                                file_put_contents(storage_path('logs/whatsapp.txt'), '[DEBUG /cekredaman] Processing devices... Device count: '.count($devices)."\n", FILE_APPEND);
                                            }

                                            // Parameter paths untuk mengambil data (diambil dari index.php)
                                            $parameterPaths = [
                                                'pppUsername' => [
                                                    'VirtualParameters.pppoeUsername',
                                                    'VirtualParameters.pppUsername',
                                                    'InternetGatewayDevice.WANDevice.1.WANConnectionDevice.1.WANPPPConnection.1.Username',
                                                ],
                                                'rxPower' => [
                                                    'VirtualParameters.RXPower',
                                                    'VirtualParameters.redaman',
                                                    'InternetGatewayDevice.WANDevice.1.WANPONInterfaceConfig.RXPower',
                                                ],
                                                'ssid' => [
                                                    'InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.SSID',
                                                ],
                                                'ontSN' => [
                                                    'VirtualParameters.ontSN',
                                                    'VirtualParameters.getSerialNumber',
                                                    'InternetGatewayDevice.DeviceInfo.X_HW_SerialNumber',
                                                    'InternetGatewayDevice.X_BROADCOM_COM_GPON.ONU.SerialNumber',
                                                    'Device.X_BROADCOM_COM_GPON.ONU.SerialNumber',
                                                    'InternetGatewayDevice.WANDevice.1.WANPONInterfaceConfig.SerialNumber',
                                                ],
                                            ];

                                            $alarmedDevices = [];
                                            $threshold = 1800; // 30 menit
                                            $deviceIndex = 0;

                                            foreach ($devices as $device) {
                                                $deviceIndex++;
                                                if ($debugMode && $deviceIndex <= 3) {
                                                    file_put_contents(storage_path('logs/whatsapp.txt'), '[DEBUG /cekredaman] Processing device #'.$deviceIndex.' - ID: '.(isset($device['_id']) ? $device['_id'] : 'NO ID')."\n", FILE_APPEND);
                                                }

                                                // Helper function untuk extract value dari path
                                                $getValueFromPath = function ($device, $paths) {
                                                    // Try direct path first
                                                    foreach ($paths as $path) {
                                                        $pathParts = explode('.', $path);
                                                        $tempValue = $device;
                                                        $validPath = true;

                                                        foreach ($pathParts as $part) {
                                                            if (! isset($tempValue[$part])) {
                                                                $validPath = false;
                                                                break;
                                                            }
                                                            $tempValue = $tempValue[$part];
                                                        }

                                                        if ($validPath && isset($tempValue['_value'])) {
                                                            return $tempValue['_value'];
                                                        }
                                                    }

                                                    return null;
                                                };

                                                // Extract RX Power (lebih robust dengan parameterPaths)
                                                $rxpower = null;
                                                $hasRxPower = false;

                                                // Coba direct access dulu
                                                if (isset($device['VirtualParameters']['RXPower']['_value'])) {
                                                    $rxpower = $device['VirtualParameters']['RXPower']['_value'];
                                                    $hasRxPower = true;
                                                } else {
                                                    // Coba dari parameterPaths
                                                    $rxpower = $getValueFromPath($device, $parameterPaths['rxPower']);
                                                    if ($rxpower !== null) {
                                                        $hasRxPower = true;
                                                    }
                                                }

                                                // Clean RX Power value
                                                if ($hasRxPower && $rxpower !== null) {
                                                    // Convert to string first untuk cleaning
                                                    $rxpower = (string) $rxpower;
                                                    // Hapus 'dBm' jika ada
                                                    if (strpos($rxpower, 'dBm') !== false) {
                                                        $rxpower = str_replace([' dBm', 'dBm', ' '], '', $rxpower);
                                                    }
                                                    $rxpower = floatval($rxpower);

                                                    if ($debugMode && $deviceIndex <= 3) {
                                                        file_put_contents(storage_path('logs/whatsapp.txt'), '[DEBUG /cekredaman] Device #'.$deviceIndex.' - RX Power: '.$rxpower.' dBm'."\n", FILE_APPEND);
                                                    }

                                                    // Hanya tampilkan jika redaman tinggi (≤ -8 dBm)
                                                    if ($rxpower <= -8) {
                                                        // Extract PPPoE Username
                                                        $pppoeuser = 'N/A';
                                                        if (isset($device['VirtualParameters']['pppoeUsername']['_value'])) {
                                                            $pppoeuser = $device['VirtualParameters']['pppoeUsername']['_value'];
                                                        } else {
                                                            $pppoeUserVal = $getValueFromPath($device, $parameterPaths['pppUsername']);
                                                            if ($pppoeUserVal !== null) {
                                                                $pppoeuser = $pppoeUserVal;
                                                            }
                                                        }

                                                        // Extract SSID
                                                        $ssid = 'N/A';
                                                        if (isset($device['VirtualParameters']['SSID']['_value'])) {
                                                            $ssid = $device['VirtualParameters']['SSID']['_value'];
                                                        } else {
                                                            $ssidVal = $getValueFromPath($device, $parameterPaths['ssid']);
                                                            if ($ssidVal !== null) {
                                                                $ssid = $ssidVal;
                                                            }
                                                        }

                                                        // Extract ONT Serial Number
                                                        $ontSN = 'N/A';
                                                        if (isset($device['VirtualParameters']['ontSN']['_value'])) {
                                                            $ontSN = $device['VirtualParameters']['ontSN']['_value'];
                                                        } else {
                                                            $ontSNVal = $getValueFromPath($device, $parameterPaths['ontSN']);
                                                            if ($ontSNVal !== null) {
                                                                $ontSN = $ontSNVal;
                                                            }
                                                        }

                                                        // Extract Device ID
                                                        $deviceID = 'N/A';
                                                        if (isset($device['_deviceId']['_ID'])) {
                                                            $deviceID = $device['_deviceId']['_ID'];
                                                        } elseif (isset($device['_id'])) {
                                                            $deviceID = $device['_id'];
                                                        }

                                                        // Get Tags
                                                        $tagsName = 'N/A';
                                                        if (isset($device['_tags']) && is_array($device['_tags'])) {
                                                            $tagsName = implode(', ', $device['_tags']);
                                                        }

                                                        // Cek status online/offline
                                                        $isOffline = false;
                                                        $lastActive = 'N/A';
                                                        if (isset($device['_lastInform'])) {
                                                            try {
                                                                $lastInform = new \DateTime($device['_lastInform'], new \DateTimeZone('UTC'));
                                                                $lastInform->setTimezone(new \DateTimeZone('Asia/Jakarta'));
                                                                $currentTime = new \DateTime('now', new \DateTimeZone('Asia/Jakarta'));
                                                                $isOffline = ($currentTime->getTimestamp() - $lastInform->getTimestamp()) > $threshold;
                                                                $lastActive = $lastInform->format('d M Y, H:i');
                                                            } catch (\Exception $e) {
                                                                if ($debugMode) {
                                                                    file_put_contents(storage_path('logs/whatsapp.txt'), '[DEBUG /cekredaman] DateTime error for device #'.$deviceIndex.': '.$e->getMessage()."\n", FILE_APPEND);
                                                                }
                                                            }
                                                        }

                                                        $alarmedDevices[] = [
                                                            'rxpower' => $rxpower,
                                                            'pppoeuser' => $pppoeuser,
                                                            'ssid' => $ssid,
                                                            'ontsn' => $ontSN,
                                                            'deviceid' => $deviceID,
                                                            'tagsname' => $tagsName,
                                                            'lastactive' => $lastActive,
                                                            'isoffline' => $isOffline,
                                                        ];

                                                        if ($debugMode && $deviceIndex <= 3) {
                                                            file_put_contents(storage_path('logs/whatsapp.txt'), '[DEBUG /cekredaman] Device #'.$deviceIndex.' - Added to alarmed list (RX: '.$rxpower.' dBm)'."\n", FILE_APPEND);
                                                        }
                                                    }
                                                } else {
                                                    if ($debugMode && $deviceIndex <= 3) {
                                                        file_put_contents(storage_path('logs/whatsapp.txt'), '[DEBUG /cekredaman] Device #'.$deviceIndex.' - No RX Power data found'."\n", FILE_APPEND);
                                                    }
                                                }
                                            }

                                            if ($debugMode) {
                                                file_put_contents(storage_path('logs/whatsapp.txt'), '[DEBUG /cekredaman] Finished processing all '.$deviceIndex.' devices. Alarmed devices: '.count($alarmedDevices)."\n", FILE_APPEND);
                                            }

                                            // Format response
                                            if ($debugMode) {
                                                file_put_contents(storage_path('logs/whatsapp.txt'), '[DEBUG /cekredaman] Formatting response...'."\n", FILE_APPEND);
                                            }

                                            if (! empty($alarmedDevices)) {
                                                $text = '🔍 *CEK REDAMAN ONT ('.$searchType.": $searchValue)*\n";
                                                $text .= "============================\n\n";

                                                $count = 0;
                                                foreach ($alarmedDevices as $dev) {
                                                    $count++;

                                                    $text .= "📍 *Device #$count*\n";
                                                    $text .= "━━━━━━━━━━━━━━━━━\n";
                                                    $text .= '🏷️ Tag: '.(isset($dev['tagsname']) ? $dev['tagsname'] : 'N/A')."\n";
                                                    $text .= "⚠️ RX Power: *{$dev['rxpower']} dBm*\n";
                                                    $text .= "📡 SSID: {$dev['ssid']}\n";
                                                    $text .= "👤 PPPoE: {$dev['pppoeuser']}\n";
                                                    $text .= "🔢 ONT SN: {$dev['ontsn']}\n";
                                                    $text .= "🆔 Device ID: {$dev['deviceid']}\n";
                                                    $text .= "📅 Last Active: {$dev['lastactive']}\n";

                                                    if ($dev['isoffline']) {
                                                        $text .= "🔴 Status: *OFFLINE* (>30 menit)\n";
                                                    } else {
                                                        $text .= "🟢 Status: *ONLINE*\n";
                                                    }

                                                    $text .= "\n";

                                                    // Limit to 10 devices per message to avoid timeout
                                                    if ($count >= 10) {
                                                        $remaining = count($alarmedDevices) - 10;
                                                        if ($remaining > 0) {
                                                            $text .= "...dan *$remaining perangkat* lainnya.\n";
                                                            $text .= "_(Terbatas 10 perangkat per pesan)_\n";
                                                        }
                                                        break;
                                                    }
                                                }

                                            } else {
                                                $text = '✅ *CEK REDAMAN ONT ('.$searchType.": $searchValue)*\n";
                                                $text .= "============================\n\n";
                                                $text .= "🎉 Semua perangkat dalam kondisi baik!\n";
                                                $text .= "Tidak ada perangkat dengan RX Power ≤ -8 dBm.\n\n";
                                                $text .= 'Total perangkat dicek: *'.count($devices).' perangkat*';
                                            }

                                            if ($debugMode) {
                                                file_put_contents(storage_path('logs/whatsapp.txt'), '[DEBUG /cekredaman] Response formatted. Text length: '.strlen($text)." chars\n", FILE_APPEND);
                                            }

                                        } else {
                                            if ($debugMode) {
                                                file_put_contents(storage_path('logs/whatsapp.txt'), '[DEBUG /cekredaman] No devices found or API returned non-array'."\n", FILE_APPEND);
                                            }
                                            $text = '📭 *Tidak ada perangkat* ditemukan dengan '.$searchType.": *$searchValue*\n\n";
                                            $text .= 'Pastikan '.strtolower($searchType).' yang Anda masukkan sudah benar.';
                                        }

                                        $response = ['text' => $text];

                                        if ($debugMode) {
                                            file_put_contents(storage_path('logs/whatsapp.txt'), '[DEBUG /cekredaman] ✅ Response prepared successfully!'."\n", FILE_APPEND);
                                        }

                                    } catch (\Throwable $e) {
                                        if ($debugMode) {
                                            file_put_contents(storage_path('logs/whatsapp.txt'), '[DEBUG /cekredaman] API EXCEPTION: '.$e->getMessage()."\n", FILE_APPEND);
                                            file_put_contents(storage_path('logs/whatsapp.txt'), '[DEBUG /cekredaman] Stack trace: '.$e->getTraceAsString()."\n", FILE_APPEND);
                                        }

                                        $text = "❌ *ERROR saat mengakses ACS Server*\n\n";
                                        $text .= 'Detail: '.$e->getMessage()."\n\n";
                                        $text .= 'Silakan coba lagi atau hubungi administrator.';
                                        $response = ['text' => $text];
                                    }

                                } else {
                                    $text = "❌ *ERROR: Format tidak sesuai!*\n\n";
                                    $text .= "📝 *Format:*\n";
                                    $text .= "/cekredaman tag [nama_tag]\n\n";
                                    $text .= "💡 *Contoh:*\n";
                                    $text .= "/cekredaman tag waldi\n";
                                    $text .= '/cekredaman tag jakarta';
                                    $response = ['text' => $text];
                                }

                            } else {
                                $text = '⛔ Command ini hanya untuk Admin/Developer.';
                                $response = ['text' => $text];
                            }
                            break;

                        case '/cekdata':
                            // Cek data PPPoE atau Hotspot langsung dari MikroTik
                            if ($level == 'admin' || $level == 'developer') {
                                $mode = isset($params[1]) ? strtolower(trim($params[1])) : '';

                                if ($mode === 'ppp') {
                                    // Cek data PPPoE langsung dari MikroTik
                                    $username = '';
                                    $routerId = '';

                                    for ($i = 2; $i < count($params); $i++) {
                                        if ($params[$i] === 'username') {
                                            $username = isset($params[$i + 1]) ? $params[$i + 1] : '';
                                        } elseif ($params[$i] === 'router') {
                                            $routerId = isset($params[$i + 1]) ? $params[$i + 1] : '';
                                        }
                                    }

                                    if ($username !== '' && $routerId !== '') {
                                        // Get router info
                                        $cekrouter = $this->rosModel->getDatabyID($routerId);
                                        $routerData = $cekrouter->getResultArray();

                                        if (! empty($routerData)) {
                                            $hostserver = $routerData[0]['ip'];
                                            $usernameserver = $routerData[0]['username'];
                                            $passwordserver = legacy_decrypt($routerData[0]['password']);
                                            $routerName = $routerData[0]['nama'];

                                            $text = "🔐 *CEK DATA PPPoE USER*\n";
                                            $text .= "============================\n\n";
                                            $text .= '🖥️ Router: '.$routerName."\n";
                                            $text .= '📡 IP: '.$hostserver."\n\n";
                                            $text .= "🔍 *CHECKING MIKROTIK...*\n\n";

                                            // Connect to MikroTik
                                            if ($this->ros->connect($hostserver, $usernameserver, $passwordserver)) {
                                                // Check PPP Secret
                                                $pppSecret = $this->ros->comm('/ppp/secret/print', [
                                                    '?name' => $username,
                                                ]);

                                                if (! empty($pppSecret)) {
                                                    $text .= "✅ *USER DI TEMUKAN DI MIKROTIK*\n\n";

                                                    foreach ($pppSecret as $secret) {
                                                        $text .= '👤 Username: *'.($secret['name'] ?? 'N/A')."*\n";
                                                        $text .= '🔑 Password: *'.($secret['password'] ?? 'N/A')."*\n";
                                                        $text .= '📦 Profile: '.($secret['profile'] ?? 'N/A')."\n";
                                                        $text .= '🌐 Service: '.($secret['service'] ?? 'any')."\n";
                                                        $text .= '📍 Local Address: '.($secret['local-address'] ?? 'N/A')."\n";
                                                        $text .= '📍 Remote Address: '.($secret['remote-address'] ?? 'N/A')."\n";
                                                        $text .= '🔓 Status: '.(isset($secret['disabled']) && $secret['disabled'] === 'true' ? '🔴 Disabled' : '🟢 Enabled')."\n";
                                                    }

                                                    // Check active session
                                                    $activeSession = $this->ros->comm('/ppp/active/print', [
                                                        '?name' => $username,
                                                    ]);

                                                    if (! empty($activeSession)) {
                                                        $text .= "\n🟢 *ACTIVE SESSION:*\n";
                                                        foreach ($activeSession as $session) {
                                                            $text .= '📍 IP Address: '.($session['address'] ?? 'N/A')."\n";
                                                            $text .= '📞 Caller ID: '.($session['caller-id'] ?? 'N/A')."\n";
                                                            $text .= '⏱️ Uptime: '.($session['uptime'] ?? '0s')."\n";

                                                            // Get encoding (speed)
                                                            if (isset($session['encoding'])) {
                                                                $text .= '⚡ Speed: '.$session['encoding']."\n";
                                                            }
                                                        }
                                                    } else {
                                                        $text .= "\n⚪ *Status: OFFLINE* (Tidak ada sesi aktif)";
                                                    }

                                                } else {
                                                    $text .= "❌ *USER TIDAK DI TEMUKAN MIKROTIK*\n";
                                                    $text .= "Username *$username* tidak ditemukan di router.";
                                                }

                                                $this->ros->disconnect();
                                            } else {
                                                $text .= "❌ *GAGAL KONEKSI KE MIKROTIK*\n";
                                                $text .= 'Tidak dapat terhubung ke router.';
                                            }

                                            $response = ['text' => $text];
                                        } else {
                                            $text = "❌ Router ID *$routerId* tidak ditemukan.";
                                            $response = ['text' => $text];
                                        }
                                    } else {
                                        $text = "❌ *ERROR: Format tidak sesuai!*\n\n";
                                        $text .= "📝 *Format:*\n";
                                        $text .= "/cekdata ppp username [username] router [ID]\n\n";
                                        $text .= "💡 *Contoh:*\n";
                                        $text .= "/cekdata ppp username pppuser01 router 1\n\n";
                                        $text .= 'Gunakan /cekrouter untuk melihat daftar router.';
                                        $response = ['text' => $text];
                                    }

                                } elseif ($mode === 'hs') {
                                    // Cek data Hotspot langsung dari MikroTik
                                    $username = '';
                                    $routerId = '';

                                    for ($i = 2; $i < count($params); $i++) {
                                        if ($params[$i] === 'username') {
                                            $username = isset($params[$i + 1]) ? $params[$i + 1] : '';
                                        } elseif ($params[$i] === 'router') {
                                            $routerId = isset($params[$i + 1]) ? $params[$i + 1] : '';
                                        }
                                    }

                                    if ($username !== '' && $routerId !== '') {
                                        // Get router info
                                        $cekrouter = $this->rosModel->getDatabyID($routerId);
                                        $routerData = $cekrouter->getResultArray();

                                        if (! empty($routerData)) {
                                            $hostserver = $routerData[0]['ip'];
                                            $usernameserver = $routerData[0]['username'];
                                            $passwordserver = legacy_decrypt($routerData[0]['password']);
                                            $routerName = $routerData[0]['nama'];

                                            $text = "🔐 *CEK DATA HOTSPOT USER*\n";
                                            $text .= "============================\n\n";
                                            $text .= '🖥️ Router: '.$routerName."\n";
                                            $text .= '📡 IP: '.$hostserver."\n\n";
                                            $text .= "🔍 *CHECKING MIKROTIK...*\n\n";

                                            // Connect to MikroTik
                                            if ($this->ros->connect($hostserver, $usernameserver, $passwordserver)) {
                                                // Check if user exists in MikroTik
                                                $hotspotUser = $this->ros->comm('/ip/hotspot/user/print', [
                                                    '?name' => $username,
                                                ]);

                                                if (! empty($hotspotUser)) {
                                                    $text .= "✅ *USER DI TEMUKAN DI MIKROTIK*\n\n";

                                                    foreach ($hotspotUser as $user) {
                                                        $text .= '👤 Username: *'.($user['name'] ?? 'N/A')."*\n";
                                                        $text .= '🔑 Password: *'.($user['password'] ?? 'N/A')."*\n";
                                                        $text .= '📦 Profile: '.($user['profile'] ?? 'N/A')."\n";
                                                        $text .= '⏰ Uptime: '.($user['uptime'] ?? '0s')."\n";
                                                        $text .= '💾 Limit: '.($user['limit-uptime'] ?? 'Unlimited')."\n";
                                                        $text .= '🔓 Status: '.(isset($user['disabled']) && $user['disabled'] === 'true' ? '🔴 Disabled' : '🟢 Enabled')."\n";
                                                    }

                                                    // Check active session
                                                    $activeSession = $this->ros->comm('/ip/hotspot/active/print', [
                                                        '?user' => $username,
                                                    ]);

                                                    if (! empty($activeSession)) {
                                                        $text .= "\n🟢 *ACTIVE SESSION:*\n";
                                                        foreach ($activeSession as $session) {
                                                            $text .= '📍 IP Address: '.($session['address'] ?? 'N/A')."\n";
                                                            $text .= '🔗 MAC: '.($session['mac-address'] ?? 'N/A')."\n";
                                                            $text .= '⏱️ Uptime: '.($session['uptime'] ?? '0s')."\n";

                                                            // Get bytes in/out if available
                                                            $bytesIn = isset($session['bytes-in']) ? $session['bytes-in'] : 0;
                                                            $bytesOut = isset($session['bytes-out']) ? $session['bytes-out'] : 0;

                                                            $downloadMB = round($bytesIn / 1048576, 2);
                                                            $uploadMB = round($bytesOut / 1048576, 2);

                                                            $text .= '📥 Download: '.$downloadMB." MB\n";
                                                            $text .= '📤 Upload: '.$uploadMB." MB\n";
                                                        }
                                                    } else {
                                                        $text .= "\n⚪ *Status: OFFLINE* (Tidak ada sesi aktif)";
                                                    }

                                                } else {
                                                    $text .= "❌ *USER NOT FOUND IN MIKROTIK*\n";
                                                    $text .= 'User belum dibuat atau sudah dihapus dari MikroTik.';
                                                }

                                                $this->ros->disconnect();
                                            } else {
                                                $text .= "❌ *GAGAL KONEKSI KE MIKROTIK*\n";
                                                $text .= 'Tidak dapat terhubung ke router.';
                                            }

                                            $response = ['text' => $text];
                                        } else {
                                            $text = "❌ Router ID *$routerId* tidak ditemukan.";
                                            $response = ['text' => $text];
                                        }
                                    } else {
                                        $text = "❌ *ERROR: Format tidak sesuai!*\n\n";
                                        $text .= "📝 *Format:*\n";
                                        $text .= "/cekdata hs username [username] router [ID]\n\n";
                                        $text .= "💡 *Contoh:*\n";
                                        $text .= "/cekdata hs username hotspotuser01 router 1\n\n";
                                        $text .= 'Gunakan /cekrouter untuk melihat daftar router.';
                                        $response = ['text' => $text];
                                    }

                                } else {
                                    $text = "❌ *ERROR: Mode tidak valid!*\n\n";
                                    $text .= "📝 *Format:*\n";
                                    $text .= "/cekdata ppp username [user] router [ID]\n";
                                    $text .= "/cekdata hs username [user] router [ID]\n\n";
                                    $text .= "💡 *Contoh:*\n";
                                    $text .= "/cekdata ppp username pppuser01 router 1\n";
                                    $text .= "/cekdata hs username hsuser01 router 1\n\n";
                                    $text .= 'Gunakan /cekrouter untuk melihat daftar router.';
                                    $response = ['text' => $text];
                                }
                            } else {
                                $text = '⛔ Command ini hanya untuk Admin/Developer.';
                                $response = ['text' => $text];
                            }
                            break;

                        default:
                            // Text-sensitive: hanya respon jika ada kata kunci
                            $messageLower = strtolower($pesan);
                            $keywords = ['help', 'tolong', 'bantuan', 'bantu', 'gimana', 'cara', 'apa', 'command'];
                            $shouldRespond = false;

                            foreach ($keywords as $keyword) {
                                if (strpos($messageLower, $keyword) !== false) {
                                    $shouldRespond = true;
                                    break;
                                }
                            }

                            if ($shouldRespond) {
                                $text = "👋 Halo! Butuh bantuan?\n\n";
                                $text .= "Ketik **/help** untuk melihat daftar command yang tersedia.\n\n";
                                $text .= 'Atau hubungi Admin jika ada kendala.';
                                $response = ['text' => $text];
                            }
                            // Jika tidak ada keyword, tidak ada response (silent)
                            break;
                    }
                } else {
                    $text = 'Mohon maaf status webhook saat ini off !';
                    $response = [
                        'text' => $text,
                    ];
                }
            }
        }

        // Hanya kirim response jika ada $response yang valid
        if (isset($response) && $response !== null) {
            return response()->json($response);
        }
    }

    public function whatsappMetaVerify()
    {
        $mode = request()->query('hub_mode') ?? request()->query('hub.mode');
        $token = request()->query('hub_verify_token') ?? request()->query('hub.verify_token');
        $challenge = request()->query('hub_challenge') ?? request()->query('hub.challenge');

        if ($mode === 'subscribe' && hash_equals(WhatsAppGatewayResolver::verifyToken(), (string) $token)) {
            return response((string) $challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    /**
     * Verifikasi header X-Hub-Signature-256 dari Meta (HMAC-SHA256 raw body + App
     * Secret). Hanya dipanggil bila App Secret dikonfigurasi.
     */
    private function verifyMetaSignature(string $appSecret): bool
    {
        $header = (string) request()->header('X-Hub-Signature-256', '');
        if (! str_starts_with($header, 'sha256=')) {
            return false;
        }

        $expected = 'sha256='.hash_hmac('sha256', request()->getContent(), $appSecret);

        return hash_equals($expected, $header);
    }

    public function whatsappMeta()
    {
        // App Secret diambil dari setting gateway Meta aktif (blob DB, bukan .env).
        // Bila diisi, tolak payload yang signature-nya tidak cocok (cegah webhook
        // palsu). Fail-open kalau secret belum dikonfigurasi.
        $activeGateway = WhatsAppGatewayResolver::active();
        $appSecret = ($activeGateway && WhatsAppGatewayResolver::isMeta($activeGateway))
            ? WhatsAppGatewayResolver::metaAppSecret($activeGateway)
            : '';
        if ($appSecret !== '' && ! $this->verifyMetaSignature($appSecret)) {
            \Log::warning('WhatsApp Meta webhook: signature X-Hub-Signature-256 tidak valid, payload ditolak');

            return response()->json(['status' => 'invalid signature'], 403);
        }

        $payload = request()->all();
        \Log::info('WhatsApp Meta webhook payload', $payload);

        $webhook = DB::table('webhook')->first();
        if ($webhook && ($webhook->status ?? 'off') !== 'on') {
            return response()->json(['status' => 'ignored', 'message' => 'Webhook off']);
        }

        $messages = data_get($payload, 'entry.0.changes.0.value.messages', []);
        if (! is_array($messages) || $messages === []) {
            return response()->json(['status' => 'ok']);
        }

        $gateway = WhatsAppGatewayResolver::active();
        if (! $gateway || ! WhatsAppGatewayResolver::isMeta($gateway)) {
            return response()->json(['status' => 'ignored', 'message' => 'Meta gateway inactive']);
        }

        $api = WhatsAppGatewayResolver::make($gateway);
        $contacts = data_get($payload, 'entry.0.changes.0.value.contacts', []);

        foreach ($messages as $message) {
            $from = data_get($message, 'from');
            $text = $this->metaMessageText($message);
            $contact = collect($contacts)->firstWhere('wa_id', $from);
            $fromName = data_get($contact, 'profile.name');

            // Meta melakukan retry delivery webhook — pesan dengan id sama tidak boleh diproses dua kali
            $metaMessageId = data_get($message, 'id');
            if ($metaMessageId && WaInboxMessage::where('meta_message_id', $metaMessageId)->exists()) {
                continue;
            }

            if ($from && $text !== '') {
                WaInboxMessage::create([
                    'from_number' => $from,
                    'from_name' => $fromName,
                    'direction' => 'in',
                    'body' => $text,
                    'message_type' => (string) data_get($message, 'type', 'text'),
                    'meta_message_id' => data_get($message, 'id'),
                    'status' => 'received',
                    'created_at' => now(),
                ]);

                // Serap otomatis jadi laporan gangguan bila terindikasi keluhan.
                GangguanReport::capture($from, $fromName, $text, 'meta');

                $reply = $this->handleMetaTextCommand($text);
                if ($reply !== null) {
                    $api->sendMessage(WhatsAppGatewayResolver::sender($gateway), $from, $reply);
                    WaInboxMessage::create([
                        'from_number' => $from,
                        'from_name' => $fromName,
                        'direction' => 'out',
                        'body' => $reply,
                        'message_type' => 'text',
                        'status' => 'sent',
                        'created_at' => now(),
                    ]);
                }
            }
        }

        return response()->json(['status' => 'ok']);
    }

    private function handleMetaTextCommand(string $text): ?string
    {
        $command = strtolower(trim(strtok($text, " \n\t")));

        return match ($command) {
            '/help', 'help' => "*LandakNet Bot*\n\nPerintah tersedia:\n/help - bantuan\n/cek - info format cek tagihan\n\nUntuk command admin lengkap tetap gunakan webhook gateway lama sampai bot Meta disesuaikan.",
            '/cek', 'cek' => 'Cek tagihan melalui '.url('cek').' atau kirim ID pelanggan ke admin.',
            default => null,
        };
    }

    private function metaMessageText(array $message): string
    {
        $type = (string) data_get($message, 'type', 'text');

        return trim((string) match ($type) {
            'text' => data_get($message, 'text.body'),
            'button' => data_get($message, 'button.text'),
            'interactive' => data_get($message, 'interactive.button_reply.title')
                ?? data_get($message, 'interactive.list_reply.title'),
            'image', 'document', 'audio', 'video', 'sticker' => '['.ucfirst($type).'] '
                .data_get($message, $type.'.caption', data_get($message, $type.'.filename', '')),
            'location' => '[Location] '
                .data_get($message, 'location.latitude', '').', '
                .data_get($message, 'location.longitude', ''),
            default => '',
        });
    }
}
