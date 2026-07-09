<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Invoice;
use App\Models\PaymentGateway;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class AjaxController extends Controller
{
    public function getCategory(Request $request)
    {
        if (! $request->has('category')) {
            return response('<option value="0">Error..</option>');
        }

        // Skema payment_gateway: kolom penanda default = payment_default,
        // status enum enable/disable (bukan is_default/default/Active).
        $paymentName = PaymentGateway::where('payment_default', 1)->value('name')
            ?? PaymentGateway::where('status', 'enable')->value('name');

        $query = PaymentMethod::where('category', $request->post('category'));
        if ($paymentName) {
            $query->where('provider', $paymentName);
        }

        $options = '<option value="0">Pilih salah satu ...</option>';
        foreach ($query->get() as $row) {
            $options .= '<option value="'.$row->id.'">'.$row->service.'</option>';
        }

        return response($options);
    }

    public function cekcoupon(Request $request)
    {
        $code = $request->post('code');
        $idpel = $request->post('idpel');
        $tagihan = (float) $request->post('tagihan');
        $coupon = Coupon::where('code', $code)->orWhere('name', $code)->first();

        if (! $coupon) {
            return response()->json(['disc' => 0, 'remark' => '<span style="color: red;">Kode kupon tidak ditemukan !  </span>']);
        }

        if ($coupon->status != 'Active') {
            return response()->json(['disc' => 0, 'remark' => '<span style="color: red;">Kode kupon sudah tidak aktif !  </span>']);
        }

        if (($coupon->otp ?? null) == 'ya') {
            $invoice = Invoice::where('code_coupon', $code)->where('idpel', $idpel)->first();
            if ($invoice) {
                return response()->json(['disc' => 0, 'remark' => '<span style="color: red;">Kode kupon sudah anda gunakan ditagihan sebelumnya ! </span>']);
            }
        }

        $disc = $tagihan * (((float) $coupon->rate) / 100);

        return response()->json([
            'disc' => round($disc),
            'remark' => '<span style="color: green;">Anda mendapatkan diskon sebesar Rp '.number_format($disc).'  </span>',
        ]);
    }
}
