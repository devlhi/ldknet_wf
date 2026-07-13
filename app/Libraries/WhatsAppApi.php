<?php

namespace App\Libraries;

class WhatsAppApi
{
    protected $apiUrl;

    protected $apiKey;

    public function __construct($apiUrl, $apiKey)
    {
        $this->apiUrl = $apiUrl;
        $this->apiKey = $apiKey;
    }

    public function sendMessage($sender, $number, $message)
    {
        $data = [
            'api_key' => $this->apiKey,
            'sender' => $sender,
            'number' => $number,
            'message' => $message,
        ];

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $this->apiUrl.'/send-message',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            // Timeout wajib (bukan 0/tak-terbatas): dipanggil sinkron termasuk dari
            // webhook (balasan otomatis). Gateway mati tidak boleh menggantung proses.
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 25,
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

    public function sendMessageMedia($sender, $number, $mediatype, $caption, $url)
    {
        $data = [
            'api_key' => $this->apiKey,
            'sender' => $sender,
            'number' => $number,
            'media_type' => $mediatype,
            'caption' => $caption,
            'url' => $url,
        ];

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $this->apiUrl.'/send-media',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            // Timeout wajib (bukan 0/tak-terbatas): dipanggil sinkron termasuk dari
            // webhook (balasan otomatis). Gateway mati tidak boleh menggantung proses.
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 25,
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
