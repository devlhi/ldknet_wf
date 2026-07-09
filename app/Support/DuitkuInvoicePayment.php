<?php

namespace App\Support;

use App\Libraries\DuitkuPayment;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DuitkuInvoicePayment
{
    /**
     * Buat transaksi Duitku (inquiry) untuk invoice pelanggan dan simpan
     * reference/payment URL secara aman.
     * Return ['ok' => true, 'view' => [...]] atau ['ok' => false, 'message' => ...].
     */
    public static function create(Invoice $invoice, PaymentMethod $paymentMethod, PaymentGateway $gateway, ?Order $order, string $category, ?string $coupon = null): array
    {
        if ($gateway->name !== 'duitku') {
            return ['ok' => false, 'message' => 'Payment gateway tidak didukung'];
        }

        if ($paymentMethod->provider !== $gateway->name) {
            return ['ok' => false, 'message' => 'Metode pembayaran tidak cocok dengan payment gateway default'];
        }

        $lockName = 'pay_invoice_'.$invoice->code;
        $lock = DB::selectOne('SELECT GET_LOCK(?, 10) AS acquired', [$lockName]);

        if (! $lock || (int) $lock->acquired !== 1) {
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

            if (self::paymentStillActive($invoice)) {
                return ['ok' => false, 'message' => 'Transaksi pembayaran invoice #'.$invoice->code.' masih aktif. Tunggu sampai expired sebelum membuat transaksi baru.'];
            }

            date_default_timezone_set('Asia/Jakarta');

            $invoiceCode = $invoice->code;
            $package = $invoice->package;
            $price = (int) $invoice->price;
            $total = $price + rand(1, 999);
            $expired = date('d-m-Y H:i:s', mktime(0, 0, 0, date('n'), date('j') + 1, date('Y')));
            $name = $invoice->nama ?: ($order->nama ?? 'Customer');
            $email = $order->email ?? '';
            $phone = $order->nomor ?? '';

            $duitku = new DuitkuPayment($gateway->api_url.$gateway->url, $gateway->code_merchant, $gateway->api_key, $gateway->callback);
            $signature = $duitku->signature($invoiceCode, $total);

            $duitku->set_params([
                'merchantCode' => $gateway->code_merchant,
                'paymentAmount' => $total,
                'paymentMethod' => $paymentMethod->provider_code,
                'merchantOrderId' => $invoiceCode,
                'productDetails' => $package,
                'customerVaName' => $name,
                'email' => $email,
                'phoneNumber' => $phone,
                'callbackUrl' => url('callback/duitku'),
                'returnUrl' => url('invoice/'.$invoiceCode),
                'signature' => $signature,
                'expiryPeriod' => 1440,
            ]);

            try {
                $result = $duitku->createTransaction();
            } catch (\Throwable $e) {
                Log::warning("Gagal membuat transaksi Duitku invoice #{$invoiceCode}: {$e->getMessage()}");

                return ['ok' => false, 'message' => 'Gagal menghubungi payment gateway'];
            }

            $response = json_decode($result);
            if (! $response) {
                Log::warning("Respons Duitku tidak valid untuk invoice #{$invoiceCode}: ".$result);

                return ['ok' => false, 'message' => 'Sistem pembayaran sedang maintenance, coba lagi nanti'];
            }

            if (($response->statusCode ?? null) !== '00') {
                return ['ok' => false, 'message' => $response->statusMessage ?? 'Gagal membuat transaksi pembayaran'];
            }

            if (empty($response->reference) || empty($response->paymentUrl)) {
                return ['ok' => false, 'message' => 'Response payment gateway tidak lengkap'];
            }

            $note = $response->vaNumber ?? $paymentMethod->note;
            $paymentUrl = $response->paymentUrl;
            $qrUrl = $response->qrString ?? null;

            $updated = Invoice::whereKey($invoice->id)
                ->where('status', 'Unpaid')
                ->update([
                    'category' => $category,
                    'service' => $paymentMethod->service,
                    'method' => $paymentMethod->name,
                    'penerima' => $note,
                    'random_price' => $total,
                    'received' => $total,
                    'reference' => $response->reference,
                    'exppay' => $expired,
                    'payment_url' => $paymentUrl,
                    'qr_url' => $qrUrl,
                    'code_coupon' => $coupon ?? '',
                    'provider' => 'duitku',
                ]);

            if (! $updated) {
                return ['ok' => false, 'message' => 'Invoice sudah berubah status, transaksi pembayaran dibatalkan'];
            }

            return [
                'ok' => true,
                'view' => [
                    'data' => $package,
                    'invoiceCode' => $invoiceCode,
                    'paymentName' => $paymentMethod->name,
                    'amount' => $total,
                    'paymentUrl' => $paymentUrl,
                    'vaNumber' => $response->vaNumber ?? null,
                    'qrString' => $qrUrl,
                    'expired' => strtotime($expired),
                ],
            ];
        } finally {
            DB::selectOne('SELECT RELEASE_LOCK(?) AS released', [$lockName]);
        }
    }

    private static function paymentStillActive(Invoice $invoice): bool
    {
        if (empty($invoice->reference)) {
            return false;
        }

        if (empty($invoice->exppay)) {
            return true;
        }

        $expiredAt = \DateTime::createFromFormat('d-m-Y H:i:s', $invoice->exppay);

        return $expiredAt ? $expiredAt->getTimestamp() > time() : true;
    }
}
