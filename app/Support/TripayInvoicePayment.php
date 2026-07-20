<?php

namespace App\Support;

use App\Libraries\TripayPayment;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Models\PaymentMethod;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;

class TripayInvoicePayment
{
    /**
     * Buat transaksi Tripay untuk invoice pelanggan dan simpan reference/VA/QR
     * secara aman. Return ['ok' => true, 'view' => [...]] atau ['ok' => false, 'message' => ...].
     */
    public static function create(Invoice $invoice, PaymentMethod $paymentMethod, PaymentGateway $gateway, ?Order $order, string $category, ?string $coupon = null): array
    {
        if ($gateway->name !== 'tripay') {
            return ['ok' => false, 'message' => 'Payment gateway tidak didukung'];
        }

        if ($paymentMethod->provider !== $gateway->name) {
            return ['ok' => false, 'message' => 'Metode pembayaran tidak cocok dengan payment gateway default'];
        }

        $invoiceCode = (string) $invoice->code;
        if (! Invoice::acquirePaymentLock($invoiceCode)) {
            return ['ok' => false, 'message' => 'Invoice sedang diproses, coba lagi beberapa saat'];
        }

        try {
            $invoice = Invoice::whereKey($invoice->id)->first();
            if (! $invoice) {
                return ['ok' => false, 'message' => 'Invoice tidak ditemukan'];
            }

            if ($invoice->status === 'Paid') {
                return ['ok' => false, 'message' => 'Invoice #'.$invoice->code.' sudah terbayar'];
            }

            if ($invoice->status !== 'Unpaid') {
                return ['ok' => false, 'message' => 'Invoice #'.$invoice->code.' tidak dapat diproses karena status '.$invoice->status];
            }

            if ($invoice->hasActiveGatewayTransaction()) {
                return ['ok' => false, 'message' => 'Transaksi pembayaran invoice #'.$invoice->code.' masih aktif. Tunggu sampai expired sebelum membuat transaksi baru.'];
            }

            $package = $invoice->package;
            $price = (int) $invoice->price;
            $total = $price + rand(1, 999);
            $deadline = CarbonImmutable::now(config('app.timezone', 'Asia/Jakarta'))->addDay();
            $expired = $deadline->format('d-m-Y H:i:s');
            $name = $invoice->nama ?: ($order->nama ?? 'Customer');
            $email = $order->email ?? '';
            $phone = $order->nomor ?? '';

            $tripay = new TripayPayment($gateway->api_url.$gateway->url, $gateway->code_merchant, $gateway->api_key, $gateway->private_key, $gateway->callback);
            $signature = hash_hmac('sha256', $gateway->code_merchant.$invoiceCode.$total, $gateway->private_key);
            $tripay->set_params([
                'method' => $paymentMethod->provider_code,
                'merchant_ref' => $invoiceCode,
                'amount' => $total,
                'customer_name' => $name,
                'customer_email' => $email,
                'customer_phone' => $phone,
                'order_items' => [
                    [
                        'name' => $package,
                        'price' => $total,
                        'quantity' => 1,
                    ],
                ],
                'expired_time' => $deadline->getTimestamp(),
                'signature' => $signature,
            ]);

            try {
                $result = $tripay->createTransaction();
            } catch (\Throwable $e) {
                Log::warning("Gagal membuat transaksi Tripay invoice #{$invoiceCode}: {$e->getMessage()}");

                return ['ok' => false, 'message' => 'Gagal menghubungi payment gateway'];
            }

            $response = json_decode($result);
            if (! $response) {
                Log::warning("Respons Tripay tidak valid untuk invoice #{$invoiceCode}: ".$result);

                return ['ok' => false, 'message' => 'Sistem pembayaran sedang maintenance, coba lagi nanti'];
            }

            if (($response->success ?? false) !== true) {
                return ['ok' => false, 'message' => $response->message ?? 'Gagal membuat transaksi pembayaran'];
            }

            if (empty($response->data?->reference) || empty($response->data?->amount) || empty($response->data?->payment_method)) {
                return ['ok' => false, 'message' => 'Response payment gateway tidak lengkap'];
            }

            $note = $paymentMethod->note;
            $paymentUrl = null;
            $qrUrl = null;
            if (! empty($response->data->pay_code)) {
                $note = $response->data->pay_code;
            } elseif (! empty($response->data->pay_url)) {
                $paymentUrl = $response->data->pay_url;
            } elseif (! empty($response->data->qr_url)) {
                $qrUrl = $response->data->qr_url;
            }

            $paymentInfo = json_decode($tripay->getChannels($response->data->payment_method)) ?: (object) [];

            $updated = Invoice::whereKey($invoice->id)
                ->where('status', 'Unpaid')
                ->update([
                    'category' => $category,
                    'service' => $paymentMethod->service,
                    'method' => $paymentMethod->name,
                    'penerima' => $note,
                    'random_price' => $response->data->amount,
                    'received' => $total,
                    'reference' => $response->data->reference,
                    'exppay' => $expired,
                    'payment_url' => $paymentUrl,
                    'qr_url' => $qrUrl,
                    'code_coupon' => $coupon ?? '',
                    'provider' => 'tripay',
                ]);

            if (! $updated) {
                return ['ok' => false, 'message' => 'Invoice sudah berubah status, transaksi pembayaran dibatalkan'];
            }

            return [
                'ok' => true,
                'view' => [
                    'data' => $package,
                    'tripay' => $response,
                    'payment' => $paymentInfo,
                ],
            ];
        } finally {
            try {
                Invoice::releasePaymentLock($invoiceCode);
            } catch (\Throwable $e) {
                Log::warning("Gagal melepas Tripay payment lock #{$invoiceCode}: {$e->getMessage()}");
            }
        }
    }
}
