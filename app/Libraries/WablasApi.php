<?php

namespace App\Libraries;

class WablasApi
{
    protected $apiUrl;

    protected $apiKey;

    public function __construct($apiUrl, $apiKey)
    {
        $this->apiUrl = $apiUrl;
        $this->apiKey = $apiKey;
    }

    public function sendMessage($message, $number)
    {
        $data = [
            'phone' => $number,
            'message' => $message,
        ];

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_HTTPHEADER => [
                "Authorization: $this->apiKey",
            ],
            CURLOPT_URL => $this->apiUrl.'/api/send-message',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_SSL_VERIFYPEER => false, // Matikan verifikasi sertifikat SSL

        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }
}
