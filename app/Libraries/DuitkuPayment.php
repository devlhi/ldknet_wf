<?php

namespace App\Libraries;

class DuitkuPayment
{
    protected $merchantCode;

    protected $apiKey;

    protected $baseApiUrl = 'https://sandbox.duitku.com/webapi';

    protected $params = [];

    public $lastError = null;

    private $callback;

    public function __construct($baseApiUrl = null, $merchantCode = null, $apiKey = null, $callback = null)
    {
        $this->baseApiUrl = $baseApiUrl;
        $this->merchantCode = $merchantCode;
        $this->apiKey = $apiKey;
        $this->callback = $callback;

        return $this;
    }

    public function _set($name, $value, $force = false)
    {
        if (isset($this->$name) && ! $force) {
            throw new \Exception('Failed to set `'.$name.'`. Property already exists!. Please set `force` (3rd parameter) to `true` to overwrite the property');
        }

        $this->$name = $value;

        return $this;
    }

    protected function makeRequest($endpoint, $method = 'POST', array $payload = [], array $headers = [])
    {
        $ch = curl_init();

        $url = rtrim($this->baseApiUrl, '/').'/'.ltrim($endpoint, '/');

        $headers = array_merge(['Content-Type: application/json'], $headers);

        if ($method == 'GET') {
            $url = $url.(count($payload) ? '?'.http_build_query($payload) : '');
        } else {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_FRESH_CONNECT => true,
            CURLOPT_FAILONERROR => false,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
        ]);

        $result = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($errno) {
            $result = json_encode([
                'statusCode' => '99',
                'statusMessage' => $error,
            ]);
        }

        return $result;
    }

    public function set_param($name, $value)
    {
        $this->params[$name] = $value;

        return $this;
    }

    public function set_params(array $value)
    {
        $this->params = $value;

        return $this;
    }

    private function reset_param()
    {
        $this->params = [];

        return $this;
    }

    /**
     * Buat signature untuk request inquiry (create transaction):
     * md5(merchantCode + merchantOrderId + paymentAmount + apiKey)
     */
    public function signature($merchantOrderId, $paymentAmount)
    {
        return md5($this->merchantCode.$merchantOrderId.$paymentAmount.$this->apiKey);
    }

    public function createTransaction()
    {
        return $this->makeRequest('/api/merchant/v2/inquiry', 'POST', $this->params);
    }

    /**
     * Cek status transaksi. Signature: md5(merchantCode + merchantOrderId + apiKey)
     */
    public function getTransaction($merchantOrderId)
    {
        $signature = md5($this->merchantCode.$merchantOrderId.$this->apiKey);

        return $this->makeRequest('/api/merchant/transactionStatus', 'POST', [
            'merchantCode' => $this->merchantCode,
            'merchantOrderId' => $merchantOrderId,
            'signature' => $signature,
        ]);
    }

    /**
     * Ambil daftar metode pembayaran yang aktif di merchant Duitku.
     * Signature: sha256(merchantCode + paymentAmount + datetime + apiKey)
     */
    public function getPaymentMethod($paymentAmount, $datetime)
    {
        $signature = hash('sha256', $this->merchantCode.$paymentAmount.$datetime.$this->apiKey);

        return $this->makeRequest('/api/merchant/paymentmethod/getpaymentmethod', 'POST', [
            'merchantcode' => $this->merchantCode,
            'amount' => $paymentAmount,
            'datetime' => $datetime,
            'signature' => $signature,
        ]);
    }

    /**
     * Verifikasi callback Duitku.
     * Signature callback: md5(merchantCode + amount + merchantOrderId + apiKey)
     * Perhatikan: urutan berbeda dari inquiry (amount & orderId ditukar).
     */
    public function verifyCallback($verify = 0)
    {
        $verify = (int) $verify;

        $merchantCode = $_POST['merchantCode'] ?? '';
        $amount = $_POST['amount'] ?? '';
        $merchantOrderId = $_POST['merchantOrderId'] ?? '';
        $signature = $_POST['signature'] ?? '';

        if (empty($merchantCode) || empty($amount) || empty($merchantOrderId) || empty($signature)) {
            $this->lastError = 'Invalid callback data';

            return false;
        }

        $calcSignature = md5($merchantCode.$amount.$merchantOrderId.$this->apiKey);

        if (! hash_equals($calcSignature, (string) $signature)) {
            $this->lastError = 'Invalid signature. See https://docs.duitku.com/api';

            return false;
        }

        if ($verify == 1) {
            $cek = $this->getTransaction($merchantOrderId);
            $cek = json_decode($cek);

            // Cek ulang ke server Duitku: order harus cocok DAN statusnya sukses
            // (statusCode '00' = paid). '01' = pending, '02' = gagal/batal.
            if (
                isset($cek->merchantOrderId, $cek->statusCode) &&
                $cek->merchantOrderId == $merchantOrderId &&
                $cek->statusCode === '00'
            ) {
                return true;
            }

            $this->lastError = $cek->statusMessage ?? 'Invalid callback data';

            return false;
        }

        return true;
    }
}
