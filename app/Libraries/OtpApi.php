<?php

namespace App\Libraries;

class OtpApi
{
    protected $apiUrl;

    protected $apiKey;

    public function __construct($apiUrl, $apiKey)
    {
        $this->apiUrl = $apiUrl;
        $this->apiKey = $apiKey;
    }

    public function sendMessage($type, $number, $message)
    {
        $data = [
            'apikey' => $this->apiKey,
            'mtype' => $type,
            'receiver' => $number,
            'text' => $message,
        ];

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $this->apiUrl.'/api/send-message',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
            ],
            CURLOPT_SSL_VERIFYPEER => false, // Matikan verifikasi sertifikat SSL

        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }
}
