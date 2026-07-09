<?php

namespace App\Libraries;

class HsgqAPI
{
    private function extractXToken($responseHeader)
    {
        $headers = explode("\r\n", $responseHeader);
        foreach ($headers as $header) {
            if (strpos($header, 'X-Token:') !== false) {
                $xToken = trim(str_replace('X-Token:', '', $header));

                return $xToken;
            }
        }

        return null; // Jika tidak ada X-Token ditemukan
    }

    public function login($url, $username, $password)
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

        $url = rtrim($url).'/'.ltrim('/userlogin?form=login');

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

    public function logout($host, $cookies, $username)
    {
        $url = $host.'/userlogin?form=logout';

        $data = [
            'method' => 'set',
            'param' => [
                'name' => $username,
            ],
        ];

        $jsonData = json_encode($data);

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-token: '.$cookies,
        ]);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;
    }

    public function getBoardInfo($host, $cookies)
    {
        $url = $host.'/board?info=pon';

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'X-token: '.$cookies,
            ],
        ];

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    public function changeName($host, $port, $onu, $name, $cookies)
    {
        $url = $host.'/onumgmt?form=config';

        $data = [
            'method' => 'set',
            'param' => [
                'fec_mode' => '1',
                'flags' => '8',
                'onu_desc' => '',
                'onu_id' => $onu,
                'onu_name' => $name,
                'port_id' => $port,
            ],
        ];

        $jsonData = json_encode($data);

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-token: '.$cookies,
        ]);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;
    }

    public function rebootOnu($host, $port, $onu, $cookies)
    {
        $url = $host.'/onumgmt?form=config';

        $data = [
            'method' => 'set',
            'param' => [
                'fec_mode' => '1',
                'flags' => '1',
                'onu_desc' => '',
                'onu_id' => $onu,
                'onu_name' => '',
                'port_id' => $port,
            ],
        ];

        $jsonData = json_encode($data);

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-token: '.$cookies,
        ]);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;
    }

    public function getOnuAllowList($host, $id, $cookies)
    {
        $url = $host.'/onu_allow_list?port_id='.$id;

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'X-token: '.$cookies,
            ],
        ];

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    public function getOnuData($host, $port, $onu, $cookies)
    {
        $urlOptical = $host.'/onumgmt?form=optical-diagnose&port_id='.$port.'&onu_id='.$onu;
        $urlBase = $host.'/onumgmt?form=base-info&port_id='.$port.'&onu_id='.$onu;

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'X-token: '.$cookies,
            ],
        ];

        $ch = curl_init();

        // Get Optical Data
        curl_setopt($ch, CURLOPT_URL, $urlOptical);
        curl_setopt_array($ch, $options);
        $opticalResult = curl_exec($ch);
        $dataOptical = json_decode($opticalResult)->data;

        // Get Base Info Data
        curl_setopt($ch, CURLOPT_URL, $urlBase);
        curl_setopt_array($ch, $options);
        $baseResult = curl_exec($ch);
        $dataBase = json_decode($baseResult)->data;

        curl_close($ch);

        return [
            'optical' => $dataOptical,
            'base' => $dataBase,
        ];
    }

    public function getOnuTable($host, $cookies)
    {
        $url = $host.'/onutable';

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'X-token: '.$cookies,
            ],
        ];

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    public function deleteOnuList($host, $port, $onu, $macaddr, $cookies)
    {
        $url = $host.'/onu_allow_list?form=onucfg';

        $data = [
            'method' => 'delete',
            'param' => [
                'port_id' => $port,
                'onu_id' => $onu,
                'macaddr' => $macaddr,
            ],
        ];

        $jsonData = json_encode($data);

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-token: '.$cookies,
        ]);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;
    }

    public function getSystemInfo($host, $cookies)
    {
        $url = $host.'/board?info=system';

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'X-token: '.$cookies,
            ],
        ];

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    public function getInfoTime($host, $cookies)
    {
        $url = $host.'/time?form=info';

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'X-token: '.$cookies,
            ],
        ];

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    public function rebootSystem($host, $cookies)
    {
        $url = $host.'/system_reboot';

        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'X-token: '.$cookies,
            ],
        ];

        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }
}
