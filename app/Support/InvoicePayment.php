<?php

namespace App\Support;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Models\PaymentMethod;

/**
 * Dispatcher pembayaran invoice. Memilih implementasi gateway berdasarkan
 * nama gateway default (tripay/duitku) supaya controller tidak perlu tahu
 * detail masing-masing provider.
 *
 * Return sama seperti *InvoicePayment::create():
 *   ['ok' => true, 'view' => [...], 'view_name' => 'customer.invoice.xxx']
 *   ['ok' => false, 'message' => '...']
 */
class InvoicePayment
{
    public static function create(Invoice $invoice, PaymentMethod $paymentMethod, PaymentGateway $gateway, ?Order $order, string $category, ?string $coupon = null): array
    {
        switch ($gateway->name) {
            case 'tripay':
                $result = TripayInvoicePayment::create($invoice, $paymentMethod, $gateway, $order, $category, $coupon);
                if ($result['ok']) {
                    $result['view_name'] = 'customer.invoice.tripay';
                }

                return $result;

            case 'duitku':
                $result = DuitkuInvoicePayment::create($invoice, $paymentMethod, $gateway, $order, $category, $coupon);
                if ($result['ok']) {
                    $result['view_name'] = 'customer.invoice.duitku';
                }

                return $result;

            default:
                return ['ok' => false, 'message' => 'Payment gateway tidak didukung'];
        }
    }
}
