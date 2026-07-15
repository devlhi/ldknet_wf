<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Libraries\RouterosAPI;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\PaymentCat;
use App\Models\PaymentGateway;
use App\Models\PaymentMethod;
use App\Models\Router;
use App\Models\User;
use App\Models\Website;
use App\Support\InvoicePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    private function websiteData(): array
    {
        $website = Website::first();

        return [
            'titletext' => $website->title ?? 'LandakNet',
            'logo' => $website->logo ?? '',
        ];
    }

    private function idpel(): ?string
    {
        return auth()->user()->idpel ?? session('idpel');
    }

    public function index()
    {
        $idpel = $this->idpel();

        $order = Order::where('idpel', $idpel)->orderByDesc('id')->first();
        $invoice = Invoice::where('idpel', $idpel)
            ->where('status', 'Unpaid')
            ->orderByDesc('id')
            ->first();

        // Cek status koneksi ke router (online/offline). Kegagalan router
        // ditelan agar dashboard tetap tampil walau router tak terjangkau.
        $online = $order ? $this->checkConnection($order) : false;

        return view('user.home', [
            'title' => 'Dashboard',
            'order' => $order,
            'invoice' => $invoice,
            'online' => $online,
            'mode' => $order->mode ?? null,
            'traffics' => $order->pppoe_user ?? null,
        ] + $this->websiteData());
    }

    /**
     * Cek apakah sesi PPPoE/hotspot pelanggan sedang aktif di router.
     * Mengembalikan false bila router tak terjangkau atau sesi tidak ada.
     */
    private function checkConnection(Order $order): bool
    {
        if (empty($order->id_router) || empty($order->pppoe_user)) {
            return false;
        }

        try {
            $router = Router::find($order->id_router);
            if (! $router) {
                return false;
            }

            $ros = new RouterosAPI;
            $ros->timeout = 3;
            $ros->attempts = 1;

            if (! $ros->connect($router->ip, $router->username, legacy_decrypt($router->password))) {
                return false;
            }

            $users = $order->pppoe_user;

            if ($order->mode === 'hotspot') {
                $active = $ros->comm('/ip/hotspot/active/print', ['?user' => $users]);
            } else {
                $active = $ros->comm('/ppp/active/getall', [
                    '.proplist' => '.id',
                    '?name' => $users,
                ]);
            }

            $ros->disconnect();

            return ! empty($active[0]['.id']) || ! empty($active[0]);
        } catch (\Throwable $e) {
            Log::warning("Cek status koneksi gagal idpel {$order->idpel}: {$e->getMessage()}");

            return false;
        }
    }

    public function service(Request $request)
    {
        $showData = (string) $request->query('show_data') === '1';

        return view('user.service', [
            'title' => 'Layanan',
            'content' => $showData
                ? Order::where('idpel', $this->idpel())->orderByDesc('id')->get()
                : collect(),
            'showData' => $showData,
        ] + $this->websiteData());
    }

    public function invoice(Request $request)
    {
        $showData = (string) $request->query('show_data') === '1';

        return view('user.invoice.index', [
            'title' => 'Data Invoice',
            'invoice' => $showData
                ? Invoice::where('idpel', $this->idpel())->orderByDesc('id')->get()
                : collect(),
            'showData' => $showData,
        ] + $this->websiteData());
    }

    public function invoiceDetail($id)
    {
        $history = Invoice::where('code', $id)->where('idpel', $this->idpel())->get();

        if ($history->isEmpty()) {
            return redirect('user/invoice');
        }

        return view('user.invoice.detail', [
            'title' => 'Detail Invoice #'.$id,
            'history' => $history,
            'date' => date('d-m-Y H:i:s'),
        ] + $this->websiteData());
    }

    public function invoicePayment($id)
    {
        $history = Invoice::where('code', $id)->where('idpel', $this->idpel())->get();

        if ($history->isEmpty()) {
            return redirect('user/invoice');
        }

        $gateway = PaymentGateway::where('payment_default', '1')->first();

        return view('user.invoice.payment', [
            'title' => 'Payment Invoice #'.$id,
            'category' => PaymentCat::where('status', '1')->get(),
            'method' => PaymentMethod::where('status', '1')
                ->when($gateway, fn ($query) => $query->where('provider', $gateway->name))
                ->get(),
            'history' => $history,
        ] + $this->websiteData());
    }

    public function invoicePembayaran(Request $request)
    {
        $invoice = trim((string) $request->post('invoice'));
        $postService = $request->post('service');
        $postCategory = $request->post('category');

        if (empty($postService) || empty($postCategory)) {
            return redirect('user/invoice/payment/'.$invoice)->with('auth_errors', ['Pilih metode pembayaran terlebih dahulu']);
        }

        $dataInvoice = Invoice::where('code', $invoice)->where('idpel', $this->idpel())->first();

        if (! $dataInvoice) {
            return redirect('user/invoice')->with('auth_errors', ['Invoice tidak ditemukan']);
        }

        $payment = PaymentMethod::where('id', $postService)->where('status', '1')->first();

        if (! $payment || $payment->category != $postCategory) {
            return redirect('user/invoice/payment/'.$invoice)->with('auth_errors', ['Metode pembayaran tidak valid']);
        }

        $gateway = PaymentGateway::where('payment_default', '1')->first();

        if (! $gateway) {
            return redirect('user/invoice/payment/'.$invoice)->with('auth_errors', ['Payment gateway default belum disetel']);
        }

        $order = Order::where('idpel', $dataInvoice->idpel)->first();

        $result = InvoicePayment::create($dataInvoice, $payment, $gateway, $order, $postCategory, $request->post('codecoupon'));

        if (! $result['ok']) {
            return redirect('user/invoice/payment/'.$invoice)->with('auth_errors', [$result['message']]);
        }

        return view($result['view_name'], $result['view'] + ['backUrl' => url('user/invoice')]);
    }

    public function invoiceCek($id)
    {
        return $this->invoiceDetail($id);
    }

    public function invoiceCekBank($id)
    {
        return $this->invoiceDetail($id);
    }

    public function invoiceHistory(Request $request)
    {
        $showData = (string) $request->query('show_data') === '1';

        return view('user.invoice.index', [
            'title' => 'Riwayat Invoice',
            'invoice' => $showData
                ? Invoice::where('idpel', $this->idpel())->where('status', 'Paid')->orderByDesc('id')->get()
                : collect(),
            'showData' => $showData,
        ] + $this->websiteData());
    }

    public function invoicePrint($id)
    {
        return $this->invoiceDetail($id);
    }

    public function account()
    {
        return view('admin.account.index', [
            'title' => 'Pengaturan Akun',
            'user' => auth()->user(),
        ] + $this->websiteData());
    }

    public function changepassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8',
            'confirm_password' => 'required|same:new_password',
        ]);

        $user = auth()->user();

        if (! Hash::check((string) $request->input('current_password'), $user->password)) {
            return redirect()->back()->with('auth_errors', ['Password saat ini salah']);
        }

        User::where('email', $user->email)->update([
            'password' => Hash::make((string) $request->input('new_password')),
        ]);

        return redirect('user/account')->with('success', ['Password berhasil diganti']);
    }
}
