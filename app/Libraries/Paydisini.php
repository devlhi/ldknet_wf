<?php

namespace App\Libraries;

class Paydisini
{
    public function transaction($params = [])
    {
        return Paydisini::Request($params['url'], [
            'key' => $params['apikey'],
            'request' => 'new',
            'unique_code' => $params['unique_code'],
            'service' => $params['service'],
            'amount' => $params['amount'],
            'note' => $params['note'],
            'valid_time' => 10800,
            'type_fee' => $params['type_fee'],
            'signature' => Paydisini::signature($params['apikey'], $params['unique_code'].$params['service'].$params['amount']. 10800 .'NewTransaction'),
        ]);
    }

    public function statusTransaction($params = [])
    {
        return Paydisini::Request($params['url'], [
            'key' => $params['apikey'],
            'request' => 'status',
            'unique_code' => $params['unique_code'],
            'signature' => Paydisini::signature($params['apikey'], $params['unique_code'].'StatusTransaction'),
        ]);
    }

    public function cancelTransaction($params = [])
    {
        return Paydisini::Request($params['url'], [
            'key' => $params['apikey'],
            'request' => 'cancel',
            'unique_code' => $params['unique_code'],
            'signature' => Paydisini::signature($params['apikey'], $params['unique_code'].'CancelTransaction'),
        ]);
    }

    public function chanel($params = [])
    {
        return Paydisini::Request($params['url'], [
            'key' => $params['apikey'],
            'request' => 'payment_channel',
            'signature' => Paydisini::signature($params['apikey'], 'PaymentChannel'),
        ]);
    }

    public function panduanPembayaran($params = [])
    {
        return Paydisini::Request($params['url'], [
            'key' => $params['apikey'],
            'request' => 'payment_guide',
            'service' => $params['service'],
            'signature' => Paydisini::signature($params['apikey'], $params['service'].'PaymentGuide'),
        ]);
    }

    public function callback($params = [])
    {
        return ['key' => $params['apikey'], 'signature' => Paydisini::signature($params['apikey'], $params['unique_code'].$params['status'].'CallbackStatus')];
    }

    public function signature($apikey, $signature)
    {
        return md5($apikey.$signature);
    }

    public static function Request($url, $body)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // Skip SSL Verification
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($body));
        curl_setopt($curl, CURLOPT_ENCODING, 'gzip, deflate');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        if (curl_errno($curl)) {
            throw new \Exception('[ERROR] Cannot get server response!');
        } else {
            $response = curl_exec($curl);
            curl_close($curl);
            $response = json_decode($response, true);

            return $response;
        }
    }
}
