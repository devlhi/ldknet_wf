<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\PaymentCat;
use App\Models\PaymentGateway;
use App\Models\PaymentMethod;
use App\Models\Website;
use App\Support\InvoicePayment;
use Illuminate\Http\Request;

class GuestController extends Controller
{
    private function websiteData(): array
    {
        $website = Website::first();

        return [
            'titletext' => $website->title ?? 'LandakNet',
            'logo' => $website->logo ?? '',
        ];
    }

    public function cek()
    {
        return view('guest.cek', [
            'title' => 'Cek Tagihan',
            'errorsJson' => json_encode(session('auth_errors')),
        ] + $this->websiteData());
    }

    public function tagihanHome()
    {
        return $this->cek();
    }

    public function cekProses(Request $request)
    {
        $idpel = $request->post('idpel');
        $bulan = $request->post('bulan');
        $tahun = $request->post('tahun');

        $checkidpel = Order::where('idpel', $idpel)->count();
        if ($checkidpel == 0) {
            return redirect('cek')->with('auth_errors', ['ID Pelanggan tidak ditemukan']);
        }

        $checkinvoice = Invoice::where('idpel', $idpel)
            ->whereMonth('expdate', $bulan)
            ->whereYear('expdate', $tahun)
            ->get();

        if ($checkinvoice->isEmpty()) {
            return redirect('cek')->with('auth_errors', ['Tidak ada invoice di periode tersebut ']);
        }

        $datainvoice = $checkinvoice->first();
        if ($datainvoice->status == 'Paid') {
            return redirect('invoice/'.$datainvoice->code);
        }

        return redirect('tagihan/'.$datainvoice->code);
    }

    public function tagihanCheck(Request $request)
    {
        $noinvoice = $request->post('invoice');
        $datainvoice = Invoice::where('code', $noinvoice)->first();

        if (! $datainvoice) {
            return redirect('invoice')->with('auth_errors', ['Nomor Invoice tidak ditemukan !']);
        }

        return redirect('invoice/'.$noinvoice);
    }

    public function tagihanDetail($code)
    {
        $datainvoice = Invoice::where('code', $code)->first();

        // Robust utk tombol WA (mis. isolir) yang isian {{1}}-nya = ID Pelanggan:
        // kalau {code} bukan nomor invoice, coba sebagai ID Pelanggan -> tagihan
        // Unpaid terbaru pelanggan tsb.
        if (! $datainvoice) {
            $byIdpel = Invoice::where('idpel', $code)
                ->where('status', 'Unpaid')
                ->orderByDesc('id')
                ->first();
            if ($byIdpel) {
                return redirect('tagihan/'.$byIdpel->code);
            }
        }

        if (! $datainvoice) {
            return redirect('tagihan')->with('auth_errors', ['Tagihan tidak ditemukan. Silakan cek dengan ID Pelanggan Anda.']);
        }

        $gateway = PaymentGateway::where('payment_default', '1')->first();

        return view('guest.tagihan-detail', [
            'title' => 'Detail Tagihan',
            'invoice' => $datainvoice,
            'category' => PaymentCat::where('status', '1')->get(),
            'method' => PaymentMethod::where('status', '1')
                ->when($gateway, fn ($query) => $query->where('provider', $gateway->name))
                ->get(),
        ] + $this->websiteData());
    }

    public function invoiceView($code)
    {
        $datainvoice = Invoice::where('code', $code)->get();

        if ($datainvoice->isEmpty()) {
            abort(404);
        }

        return view('guest.invoice-detail', [
            'title' => 'Invoice # '.$code,
            'content' => $datainvoice,
        ] + $this->websiteData());
    }

    public function tagihanProcess(Request $request)
    {
        $invoice = trim((string) $request->post('invoice'));
        $postService = $request->post('service');
        $postCategory = $request->post('category');

        if (empty($postService) || empty($postCategory)) {
            return redirect('tagihan/'.$invoice)->with('auth_errors', ['Pilih metode pembayaran terlebih dahulu']);
        }

        $dataInvoice = Invoice::where('code', $invoice)->first();

        if (! $dataInvoice) {
            return redirect('tagihan')->with('auth_errors', ['Invoice tidak ditemukan']);
        }

        $payment = PaymentMethod::where('id', $postService)->where('status', '1')->first();

        if (! $payment || $payment->category != $postCategory) {
            return redirect('tagihan/'.$invoice)->with('auth_errors', ['Metode pembayaran tidak valid']);
        }

        $gateway = PaymentGateway::where('payment_default', '1')->first();

        if (! $gateway) {
            return redirect('tagihan/'.$invoice)->with('auth_errors', ['Payment gateway default belum disetel']);
        }

        $order = Order::where('idpel', $dataInvoice->idpel)->first();

        $result = InvoicePayment::create($dataInvoice, $payment, $gateway, $order, $postCategory);

        if (! $result['ok']) {
            return redirect('tagihan/'.$invoice)->with('auth_errors', [$result['message']]);
        }

        return view($result['view_name'], $result['view'] + ['backUrl' => url('tagihan/'.$invoice)]);
    }
}
