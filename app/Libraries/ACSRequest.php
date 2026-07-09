<?php

namespace App\Libraries;

class ACSRequest
{
    public function __construct(private string $url) {}

    public function curl($endpoint = '/', $query = '', $body = '', $type = 'GET', $mimeType = '', $rawQuery = null)
    {
        if (is_array($query)) {
            $query = json_encode($query);
        }

        $query = $query ? '?query='.urlencode($query) : '';
        if ($rawQuery) {
            $query = '?'.http_build_query($rawQuery);
        }

        $curl = curl_init();
        $settings = [
            CURLOPT_URL => $this->url.$endpoint.$query,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $type,
        ];

        if ($body) {
            $settings[CURLOPT_POSTFIELDS] = is_array($body) ? json_encode($body) : $body;
        }

        if ($mimeType) {
            $settings[CURLOPT_HTTPHEADER] = ['Content-Type: '.$mimeType];
        }

        curl_setopt_array($curl, $settings);
        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }

    public function getAllDevices()
    {
        return json_decode($this->curl('/devices'), true);
    }

    public function getDeviceById($id)
    {
        return json_decode($this->curl('/devices', json_encode(['_id' => $id])), true);
    }

    public function setParameterValues($parameters, $deviceId)
    {
        $body = [
            'device' => $deviceId,
            'name' => 'setParameterValues',
            'parameterValues' => $parameters,
        ];

        return json_decode($this->curl('/devices/'.urlencode($deviceId).'/tasks', ['timeout' => '3000', 'connection_request'], $body, 'POST', 'application/json'), true);
    }

    public function refreshAllObjects($deviceId)
    {
        $body = [
            'device' => $deviceId,
            'name' => 'refreshObject',
            'objectName' => '',
        ];

        return json_decode($this->curl('/devices/'.urlencode($deviceId).'/tasks', ['timeout' => '3000', 'connection_request'], $body, 'POST', 'application/json'), true);
    }
}
