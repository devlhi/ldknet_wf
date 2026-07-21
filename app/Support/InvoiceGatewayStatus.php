<?php

namespace App\Support;

use App\Libraries\DuitkuPayment;
use App\Libraries\TripayPayment;
use App\Models\Invoice;
use App\Models\PaymentGateway;
use Carbon\CarbonImmutable;

class InvoiceGatewayStatus
{
    /** @var callable|null */
    private $requester;

    public function __construct(?callable $requester = null)
    {
        $this->requester = $requester;
    }

    /** @return array<string, mixed> */
    public function localSummary(Invoice $invoice): array
    {
        $reference = trim((string) $invoice->reference);
        if ($reference === '') {
            return [
                'state' => 'none',
                'label' => 'Tidak ada transaksi online',
                'active' => false,
            ];
        }

        if ($invoice->status === 'Paid') {
            $state = 'paid';
            $label = 'Invoice sudah terbayar';
            $active = false;
        } elseif ($invoice->hasActiveGatewayTransaction()) {
            $state = 'pending';
            $label = 'Aktif (berdasarkan masa berlaku lokal)';
            $active = true;
        } else {
            $state = 'expired';
            $label = 'Kedaluwarsa (berdasarkan masa berlaku lokal)';
            $active = false;
        }

        return [
            'state' => $state,
            'label' => $label,
            'active' => $active,
            'provider' => strtolower(trim((string) $invoice->provider)),
            'reference' => $reference,
            'expires_at' => trim((string) $invoice->exppay),
        ];
    }

    /** @return array<string, mixed> */
    public function fetch(Invoice $invoice): array
    {
        $providerName = strtolower(trim((string) $invoice->provider));
        $reference = trim((string) $invoice->reference);

        if ($reference === '' || ! in_array($providerName, ['tripay', 'duitku'], true)) {
            return $this->unknown($providerName, 'Referensi atau provider transaksi online tidak lengkap.');
        }

        $gateway = PaymentGateway::whereRaw('LOWER(name) = ?', [$providerName])->first();
        if (! $gateway) {
            return $this->unknown($providerName, 'Konfigurasi payment gateway tidak ditemukan.');
        }

        try {
            return $providerName === 'tripay'
                ? $this->fetchTripay($invoice, $gateway)
                : $this->fetchDuitku($invoice, $gateway);
        } catch (\Throwable) {
            return $this->unknown($providerName, 'Payment gateway tidak dapat dihubungi. Coba cek status lagi.');
        }
    }

    /** @return array<string, mixed> */
    private function fetchTripay(Invoice $invoice, PaymentGateway $gateway): array
    {
        $rawResponse = $this->requester
            ? ($this->requester)('tripay', $invoice, $gateway)
            : (new TripayPayment(
                $gateway->api_url.$gateway->url,
                $gateway->code_merchant,
                $gateway->api_key,
                $gateway->private_key,
                $gateway->callback
            ))->getTransaction((string) $invoice->reference);
        $response = json_decode((string) $rawResponse, true);

        if (! is_array($response) || ($response['success'] ?? false) !== true || ! is_array($response['data'] ?? null)) {
            return $this->unknown('tripay', (string) ($response['message'] ?? 'Respons status Tripay tidak valid.'));
        }

        $data = $response['data'];
        if (! hash_equals((string) $invoice->reference, (string) ($data['reference'] ?? ''))
            || ((string) ($data['merchant_ref'] ?? '') !== '' && ! hash_equals((string) $invoice->code, (string) $data['merchant_ref']))) {
            return $this->unknown('tripay', 'Referensi transaksi dari Tripay tidak cocok dengan invoice.');
        }

        $providerStatus = strtoupper(trim((string) ($data['status'] ?? '')));
        $state = match ($providerStatus) {
            'PAID' => 'paid',
            'UNPAID', 'PENDING' => 'pending',
            'EXPIRED' => 'expired',
            'FAILED', 'CANCELED', 'CANCELLED', 'REFUND', 'REFUNDED' => 'failed',
            default => 'unknown',
        };

        return $this->result(
            'tripay',
            $state,
            $providerStatus !== '' ? $providerStatus : '-',
            isset($data['amount']) ? (int) $data['amount'] : null
        );
    }

    /** @return array<string, mixed> */
    private function fetchDuitku(Invoice $invoice, PaymentGateway $gateway): array
    {
        $rawResponse = $this->requester
            ? ($this->requester)('duitku', $invoice, $gateway)
            : (new DuitkuPayment(
                $gateway->api_url.$gateway->url,
                $gateway->code_merchant,
                $gateway->api_key,
                $gateway->callback
            ))->getTransaction((string) $invoice->code);
        $response = json_decode((string) $rawResponse, true);

        if (! is_array($response)) {
            return $this->unknown('duitku', 'Respons status Duitku tidak valid.');
        }

        if ((string) ($response['merchantOrderId'] ?? '') !== ''
            && ! hash_equals((string) $invoice->code, (string) $response['merchantOrderId'])) {
            return $this->unknown('duitku', 'Nomor transaksi dari Duitku tidak cocok dengan invoice.');
        }

        if ((string) ($response['reference'] ?? '') !== ''
            && ! hash_equals((string) $invoice->reference, (string) $response['reference'])) {
            return $this->unknown('duitku', 'Referensi transaksi dari Duitku tidak cocok dengan invoice.');
        }

        $providerStatus = trim((string) ($response['statusCode'] ?? ''));
        $state = match ($providerStatus) {
            '00' => 'paid',
            '01' => 'pending',
            '02' => 'failed',
            default => 'unknown',
        };
        $message = trim((string) ($response['statusMessage'] ?? ''));

        return $this->result(
            'duitku',
            $state,
            $providerStatus !== '' ? $providerStatus : '-',
            isset($response['amount']) ? (int) $response['amount'] : null,
            $message
        );
    }

    /** @return array<string, mixed> */
    private function result(string $provider, string $state, string $providerStatus, ?int $amount, string $message = ''): array
    {
        $labels = [
            'paid' => 'Sudah dibayar, menunggu/selesai diproses callback',
            'pending' => 'Menunggu pembayaran',
            'expired' => 'Kedaluwarsa',
            'failed' => 'Gagal atau dibatalkan provider',
            'unknown' => 'Status tidak diketahui',
        ];

        return [
            'state' => $state,
            'label' => $labels[$state] ?? $labels['unknown'],
            'provider' => $provider,
            'provider_status' => $providerStatus,
            'amount' => $amount,
            'message' => $message,
            'checked_at' => CarbonImmutable::now(config('app.timezone', 'Asia/Jakarta'))->format('d-m-Y H:i:s'),
        ];
    }

    /** @return array<string, mixed> */
    private function unknown(string $provider, string $message): array
    {
        return $this->result($provider, 'unknown', '-', null, $message);
    }
}
