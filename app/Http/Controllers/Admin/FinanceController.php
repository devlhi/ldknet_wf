<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Libraries\RouterosAPI;
use App\Libraries\TripayPayment;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Katkas;
use App\Models\Notification;
use App\Models\Order;
use App\Models\PaymentCat;
use App\Models\PaymentGateway;
use App\Models\PaymentMethod;
use App\Models\Report;
use App\Models\Router;
use App\Models\Service;
use App\Models\SmtpSetting;
use App\Models\TemplateMessage;
use App\Models\Website;
use App\Support\InvoicePayment;
use App\Support\WhatsAppNotifier;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Configuration;
use Brevo\Client\Model\SendSmtpEmail;
use DateTime;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Migrasi modul Finance area admin dari CI4:
 * - app/Controllers/base/admin/AdminController.php (method finance)
 * - app/Controllers/base/admin/FinanceController.php (dependency)
 * Logika perhitungan diport 1:1 — JANGAN diubah.
 */
class FinanceController extends Controller
{
    private function websiteData(): array
    {
        $website = Website::first();

        return [
            'titletext' => $website->title ?? 'LandakNet',
            'logo' => $website->logo ?? '',
        ];
    }

    // =========================================================
    // Cash Flows Category
    // =========================================================

    public function cashflowsCategory()
    {
        return view('admin.finance.cashflows.category', [
            'title' => 'Kategori Kas',
            'getdatacategory' => Katkas::all(),
        ] + $this->websiteData());
    }

    public function cashflowsCategoryDelete($id = null)
    {
        $getid = Katkas::where('id', $id)->get();

        if ($getid->count() == 0) {
            return redirect('admin/finance/cash-flows/category')->with('auth_errors', ['kategori tidak ditemukan']);
        }

        Katkas::where('id', $id)->delete();

        return redirect('admin/finance/cash-flows/category')->with('success', ['Berhasil menghapus kategori']);
    }

    public function cashflowsCategoryAdd(Request $request)
    {
        $nama = $request->post('namakat');
        $category = $request->post('category');
        $keterangan = $request->post('keterangan');

        if (empty($nama) || empty($category)) {
            return redirect('admin/finance/cash-flows/category')->with('auth_errors', ['Mohon mengisi semua input']);
        }

        Katkas::insert([
            'nama' => $nama,
            'jenis' => $category,
            'keterangan' => $keterangan,
        ]);

        return redirect('admin/finance/cash-flows/category')->with('success', ['Data berhasil di input']);
    }

    // =========================================================
    // Cash Flows
    // =========================================================

    public function cashflows()
    {
        return view('admin.finance.cashflows.index', [
            'title' => 'Cash Flows',
            'getDataJenis' => Katkas::all(),
            'getDataReport' => $this->getDataReport(),
            'getDataPemasukan' => $this->getDataPemasukan(),
            'getDataPengeluaran' => $this->getDataPengeluaran(),
            'tahun' => $this->getTahunMasuk(),
        ] + $this->websiteData());
    }

    public function cashFlowsAdd(Request $request)
    {
        $account = auth()->user()->nama;
        $category = $request->post('category');
        $jenis = $request->post('jenis');
        $jumlah = $request->post('jumlah');
        $asal = $request->post('asal');
        $date = $request->post('date');

        if (empty($jenis) || empty($jumlah) || empty($asal)) {
            return redirect('admin/finance/cash-flows')->with('auth_errors', ['Mohon mengisi semua input']);
        }

        Report::insert([
            'category' => $category,
            'jenis_kategori' => $jenis,
            'balance' => $jumlah,
            'asal' => $asal,
            'date' => $date,
            'account' => $account,
            // Kolom NOT NULL tanpa default di DB (share CI4). CI4 lolos karena
            // strict mode OFF; Laravel strict=true, jadi wajib diisi eksplisit.
            'image' => '',
        ]);

        return redirect('admin/finance/cash-flows')->with('success', ['Data berhasil di input']);
    }

    // =========================================================
    // Kas
    // =========================================================

    public function kas()
    {
        return view('admin.finance.kas.index', [
            'title' => 'Data Kas',
            'getDataReport' => $this->getDataReport(),
            'getDataPemasukan' => $this->getDataPemasukan(),
            'getDataPengeluaran' => $this->getDataPengeluaran(),
            'tahun' => $this->getTahunMasuk(),
        ] + $this->websiteData());
    }

    public function kasAdd(Request $request)
    {
        $category = $request->post('category');
        $jumlah = $request->post('jumlah');
        $asal = $request->post('asal');
        $date = $request->post('date');

        if (empty($category) || empty($jumlah) || empty($asal)) {
            return redirect('admin/finance/kas')->with('auth_errors', ['Mohon mengisi semua input']);
        }

        Report::insert([
            'category' => $category,
            'balance' => $jumlah,
            'asal' => $asal,
            'date' => $date,
            // Kolom NOT NULL tanpa default (share CI4, strict mode ON di Laravel).
            // CI4 non-strict mengisi enum pertama ('Pemasukan') + string kosong.
            'jenis_kategori' => 'Pemasukan',
            'account' => '',
            'image' => '',
        ]);

        return redirect('admin/finance/kas')->with('success', ['Data berhasil di input']);
    }

    // =========================================================
    // Report
    // =========================================================

    public function report()
    {
        return view('admin.finance.report.home', [
            'title' => 'Laporan',
            'getservice' => Service::where('status', 'Tersedia')->orderBy('id', 'ASC')->get(),
            'tahun' => $this->getTahunMasuk(),
        ] + $this->websiteData());
    }

    public function reportFilter(Request $request)
    {
        $paket = $request->post('paket');
        $status = $request->post('status');
        $penerima = $request->post('penerima');
        $bulan = $request->post('bulan');
        $tahun = $request->post('tahun');

        $filteredData = $this->getFilteredData($paket, $status, $penerima, $bulan, $tahun);

        if (! empty($filteredData)) {
            return response()->json(['data' => $filteredData]);
        }

        return response()->json(['data' => 'No data found']);
    }

    public function exportExcelReport(Request $request)
    {
        return $this->exportExcel($request->post('dataToExport'), 'laporan_tagihan.xlsx');
    }

    public function reportCashFlows()
    {
        return view('admin.finance.report.cash-flows', [
            'title' => 'Laporan',
            'katkas' => Katkas::all(),
            'tahun' => Report::selectRaw('YEAR(date) AS tahun')
                ->groupByRaw('YEAR(date)')
                ->orderByRaw('YEAR(date) ASC')
                ->get(),
        ] + $this->websiteData());
    }

    public function reportFilterCashFlows(Request $request)
    {
        $bulan = $request->post('bulan');
        $tahun = $request->post('tahun');
        $kategori = $request->post('kategori');
        $jenis = $request->post('jenis');

        $filteredData = $this->getFilteredDataCashFlows($bulan, $tahun, $kategori, $jenis);

        if (! empty($filteredData)) {
            return response()->json(['data' => $filteredData]);
        }

        return response()->json(['data' => 'No data found']);
    }

    public function exportExcelCashFlows(Request $request)
    {
        return $this->exportExcel($request->post('dataToExport'), 'laporan_arus_kas.xlsx');
    }

    public function reportCustomers()
    {
        return view('admin.finance.report.customers', [
            'title' => 'Laporan',
            'getservice' => Service::where('status', 'Tersedia')->orderBy('id', 'ASC')->get(),
        ] + $this->websiteData());
    }

    public function reportFilterCustomers(Request $request)
    {
        $paket = $request->post('paket');
        $status = $request->post('status');

        $filteredData = $this->getFilteredDataCustomers($paket, $status);

        if (! empty($filteredData)) {
            return response()->json(['data' => $filteredData]);
        }

        return response()->json(['data' => 'No data found']);
    }

    public function exportExcelCustomers(Request $request)
    {
        return $this->exportExcel($request->post('dataToExport'), 'data_pelanggan.xlsx');
    }

    public function reportNewCustomers()
    {
        return view('admin.finance.report.new_customers', [
            'title' => 'Laporan',
            // Tabel psb legacy praktis tidak terisi; pelanggan baru yang benar
            // berada di orders.date (tanggal pelanggan dibuat/aktif).
            'tahun' => Order::selectRaw('YEAR(date) AS tahun')
                ->whereRaw('YEAR(date) > 0')
                ->groupByRaw('YEAR(date)')
                ->orderByRaw('YEAR(date) ASC')
                ->get(),
        ] + $this->websiteData());
    }

    public function reportFilterNewCustomers(Request $request)
    {
        $bulan = $request->post('bulan');
        $tahun = $request->post('tahun');

        $filteredData = $this->getFilteredDataNewCustomers($bulan, $tahun);

        if (! empty($filteredData)) {
            return response()->json(['data' => $filteredData]);
        }

        return response()->json(['data' => 'No data found']);
    }

    public function exportExcelNewCustomers(Request $request)
    {
        return $this->exportExcel($request->post('dataToExport'), 'data_pelanggan_baru.xlsx');
    }

    // =========================================================
    // Invoice
    // =========================================================

    public function invoice()
    {
        return view('admin.finance.invoice.index', [
            'title' => 'Invoice',
            'getDataInvoice' => Invoice::where('account', 'user')->get(),
            'getInvoicePaid' => Invoice::where('status', 'Paid')->where('account', 'user')->count(),
            'getInvoiceUnpaid' => Invoice::where('status', 'Unpaid')->where('account', 'user')->count(),
            'tahun' => $this->getTahunMasuk(),
        ] + $this->websiteData());
    }

    public function editInvoice($id)
    {
        $cekInvoice = Invoice::where('code', $id)->get();

        if ($cekInvoice->count() == 0) {
            return redirect('admin/finance/invoice')->with('auth_errors', ['Data tidak ditemukan']);
        }

        return view('admin.finance.invoice.edit', [
            'title' => 'Invoice #'.$id,
            'payment' => $cekInvoice,
        ] + $this->websiteData());
    }

    public function invoiceUpdate(Request $request)
    {
        $website = $this->websiteData();
        $logo = $website['logo'];
        $titletext = $website['titletext'];

        $validator = Validator::make($request->all(), [
            'target' => 'required|integer',
            'code' => 'required|string',
            'idpel' => 'required|string',
            'status' => 'required|in:Paid',
            'category' => 'required|string|max:100',
            'metode' => 'required|string|max:150',
            // Admin boleh konfirmasi tanpa upload bukti: bukti wajib hanya bila
            // memilih "ya" (default form). Nilai lain -> bukti opsional.
            'upload_bukti' => 'nullable|in:ya,tidak',
            'image' => 'required_if:upload_bukti,ya|nullable|mimes:png,jpg,jpeg|image|max:2048',
        ], [
            'image.required_if' => 'Masukan Bukti Pembayaran terlebih dahulu',
            'image.mimes' => 'Extension tidak di perbolehkan',
            'image.image' => 'File yang diperbolehkan hanya gambar',
            'image.max' => 'Ukuran bukti pembayaran maksimal 2MB',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withInput()->with('auth_errors', $validator->errors()->all());
        }

        $code = $request->post('code');
        $idpel = $request->post('idpel');
        $status = $request->post('status');
        $target = $request->post('target');
        $category = $request->post('category');
        $metode = $request->post('metode');

        $invoice = Invoice::where('id', $target)
            ->where('code', $code)
            ->where('idpel', $idpel)
            ->where('account', 'user')
            ->first();

        if (! $invoice) {
            return redirect('admin/finance/invoice')->with('auth_errors', ['Invoice tidak ditemukan atau data tidak cocok']);
        }

        if ($invoice->status === 'Paid') {
            return redirect('admin/finance/invoice')->with('auth_errors', ['Invoice #'.$code.' sudah terbayar']);
        }

        $order = Order::where('idpel', $idpel)->first();

        if (! $order) {
            return redirect('admin/finance/invoice')->with('auth_errors', ['Pelanggan invoice tidak ditemukan']);
        }

        $service = Service::where('paket', $invoice->package)->first();

        if (! $service) {
            return redirect('admin/finance/invoice')->with('auth_errors', ['Paket pelanggan tidak ditemukan']);
        }

        $user = $invoice->nama;
        $package = $invoice->package;
        $price = $invoice->price;
        $expdate = $order->expdate ?: $invoice->expdate;
        $ppprofile = $service->ppp_profile;
        $emailnya = $order->email;
        $nomornya = $order->nomor;
        $statusorder = $order->status;
        $users = $order->pppoe_user;
        $idrouter = $order->id_router;
        $mode = $order->mode;

        // Bukti pembayaran opsional. Kalau tidak diupload, simpan string kosong
        // (di laporan tampil sebagai "Tidak ada bukti").
        $filename = '';
        if ($request->hasFile('image')) {
            $gambar = $request->file('image');
            $filename = 'bukti-pembayaran-'.$code.'-'.date('ymd').'-'.Str::random(12).'.'.$gambar->getClientOriginalExtension();
            $gambar->move(public_path('data/bukti'), $filename);
        }

        if ($status == 'Paid') {
            $date = date('Y-m-d');
            $tgl2 = date('Y-m-d', strtotime('+1 month', strtotime((string) $expdate)));

            $router = Router::where('id', $idrouter)->first();

            if ($statusorder == 'Isolir' && ! $router) {
                return redirect('admin/finance/invoice')->with('auth_errors', ['Router pelanggan tidak ditemukan']);
            }

            $ip = $router?->ip;
            $uname = $router?->username;
            $pass = $router ? legacy_decrypt($router->password) : null;

            $ros = new RouterosAPI;

            if ($statusorder == 'Isolir') {
                if ($mode == 'pppoe') {
                    if ($ros->connect($ip, $uname, $pass)) {
                        $all = $ros->comm(
                            '/ppp/secret/getall',
                            [
                                '.proplist' => '.id',
                                '?name' => $users,
                            ]
                        );

                        $ros->comm(
                            '/ppp/secret/set',
                            [
                                '.id' => $all[0]['.id'],
                                'profile' => $ppprofile,
                            ]
                        );
                        $active = $ros->comm('/ppp/active/getall', [
                            '.proplist' => '.id',
                            '?name' => $users,
                        ]);

                        if ($active == true) {
                            $ros->comm(
                                '/ppp/active/remove',
                                [
                                    '.id' => $active[0]['.id'],
                                ]
                            );
                        }

                        $update = [
                            'status' => $status,
                            'category' => $category,
                            'service' => $metode,
                            'method' => $metode,
                            'received' => $price,
                            'last_update' => $date,
                            'update_by' => auth()->user()->nama,
                            'bukti_pembayaran' => $filename,
                        ];

                        if (! $this->commitInvoicePayment($target, $update, $idpel, $tgl2, $price, $user, $date)) {
                            return redirect('admin/finance/invoice')->with('auth_errors', ['Invoice #'.$code.' sudah terbayar']);
                        }

                        [$message, $pesanemail] = $this->preparePaidNotification($idpel, $code, $nomornya, $statusorder === 'Isolir');

                        $apiInstance = $this->brevoApiInstance($key, $name, $email);

                        $sendSmtpEmail = $this->buildBrevoEmail(
                            'Tagihan Internet '.$titletext.' Telah Terbayar - #'.$code.' ',
                            $pesanemail,
                            $logo,
                            $titletext,
                            $name,
                            $email,
                            $emailnya
                        );

                        try {
                            $apiInstance->sendTransacEmail($sendSmtpEmail);
                        } catch (\Throwable $e) {
                            \Log::warning("Gagal kirim email terbayar invoice #{$code} ({$idpel}): {$e->getMessage()}");
                        }

                        return redirect('admin/finance/invoice')->with('success', ['Berhasil mengupdate invoice #'.$code]);
                    } else {
                        return redirect('admin/finance/invoice')->with('auth_errors', ['Gagal mengupdate invoice, Router Not Connected']);
                    }
                } elseif ($mode == 'hotspot') {
                    if ($ros->connect($ip, $uname, $pass)) {
                        $ros->comm('/ip/hotspot/user/set', [
                            'numbers' => $users,
                            'profile' => $ppprofile,
                        ]);

                        // Find and remove active sessions by username
                        $active = $ros->comm('/ip/hotspot/active/print', [
                            '?user' => $users,
                        ]);

                        if (! empty($active)) {
                            foreach ($active as $act) {
                                $ros->comm(
                                    '/ip/hotspot/active/remove',
                                    [
                                        '.id' => $act['.id'],
                                    ]
                                );
                            }
                        }

                        $update = [
                            'status' => $status,
                            'category' => $category,
                            'service' => $metode,
                            'method' => $metode,
                            'received' => $price,
                            'last_update' => $date,
                            'update_by' => auth()->user()->nama,
                            'bukti_pembayaran' => $filename,
                        ];

                        if (! $this->commitInvoicePayment($target, $update, $idpel, $tgl2, $price, $user, $date)) {
                            return redirect('admin/finance/invoice')->with('auth_errors', ['Invoice #'.$code.' sudah terbayar']);
                        }

                        [$message, $pesanemail] = $this->preparePaidNotification($idpel, $code, $nomornya, $statusorder === 'Isolir');

                        $apiInstance = $this->brevoApiInstance($key, $name, $email);

                        $sendSmtpEmail = $this->buildBrevoEmail(
                            'Tagihan Internet '.$titletext.' Telah Terbayar - #'.$code.' ',
                            $pesanemail,
                            $logo,
                            $titletext,
                            $name,
                            $email,
                            $emailnya
                        );

                        try {
                            $apiInstance->sendTransacEmail($sendSmtpEmail);
                        } catch (\Throwable $e) {
                            \Log::warning("Gagal kirim email terbayar invoice #{$code} ({$idpel}): {$e->getMessage()}");
                        }

                        return redirect('admin/finance/invoice')->with('success', ['Berhasil mengupdate invoice #'.$code]);
                    } else {
                        return redirect('admin/finance/invoice')->with('auth_errors', ['Gagal mengupdate invoice, Router Not Connected']);
                    }
                }
            } else {
                $update = [
                    'status' => $status,
                    'category' => $category,
                    'service' => $metode,
                    'method' => $metode,
                    'received' => $price,
                    'last_update' => $date,
                    'update_by' => auth()->user()->nama,
                    'bukti_pembayaran' => $filename,
                ];

                if (! $this->commitInvoicePayment($target, $update, $idpel, $tgl2, $price, $user, $date)) {
                    return redirect('admin/finance/invoice')->with('auth_errors', ['Invoice #'.$code.' sudah terbayar']);
                }

                [$message, $pesanemail] = $this->preparePaidNotification($idpel, $code, $nomornya, $statusorder === 'Isolir');

                $apiInstance = $this->brevoApiInstance($key, $name, $email);

                $sendSmtpEmail = $this->buildBrevoEmail(
                    'Tagihan Internet '.$titletext.' Telah Terbayar - #'.$code.' ',
                    $pesanemail,
                    $logo,
                    $titletext,
                    $name,
                    $email,
                    $emailnya
                );

                try {
                    $apiInstance->sendTransacEmail($sendSmtpEmail);
                } catch (\Throwable $e) {
                    \Log::warning("Gagal kirim email terbayar invoice #{$code} ({$idpel}): {$e->getMessage()}");
                }

                return redirect('admin/finance/invoice')->with('success', ['Berhasil mengupdate invoice #'.$code]);
            }
        }

        // Status selain 'Paid' (mis. Unpaid/Pending) atau pengiriman email gagal:
        // beri respons redirect eksplisit agar tidak mengembalikan halaman kosong.
        return redirect('admin/finance/invoice')->with('auth_errors', ['Invoice tidak diupdate — status bukan "Paid" atau proses gagal.']);
    }

    /**
     * Commit pembayaran invoice secara atomik dengan OPTIMISTIC LOCK.
     *
     * Update invoice memakai syarat `status != 'Paid'` di WHERE, sehingga bila
     * dua request paralel (double-submit / retry karena router lambat) lolos guard
     * status di awal invoiceUpdate(), hanya SATU yang benar-benar meng-update baris.
     * Yang kalah race → affected rows = 0 → transaksi dibatalkan (Order &
     * Report Pemasukan TIDAK ikut ditulis) → cegah pemasukan tercatat 2x.
     *
     * @return bool true bila pembayaran ter-commit; false bila baris sudah Paid (race kalah).
     */
    private function commitInvoicePayment($target, array $update, $idpel, $tgl2, $price, $user, $date): bool
    {
        return DB::transaction(function () use ($target, $update, $idpel, $tgl2, $price, $user, $date) {
            $affected = Invoice::where('id', $target)
                ->where('status', '!=', 'Paid')
                ->update($update);

            // Race kalah: request lain sudah menandai Paid lebih dulu. Batalkan tanpa
            // menulis Order/Report agar pemasukan tidak dobel.
            if ($affected === 0) {
                return false;
            }

            Order::where('idpel', $idpel)->update([
                'status' => 'Active',
                'expdate' => $tgl2,
            ]);

            Report::insert([
                'category' => 'Pemasukan',
                'jenis_kategori' => 'Pemasukan',
                'balance' => $price,
                'asal' => 'Pembayaran Tunai dari '.$user.', ID Pelanggan '.$idpel,
                'date' => $date,
                // Kolom NOT NULL tanpa default (share CI4, strict mode ON).
                // Tanpa ini insert gagal → transaksi rollback → pembayaran hilang.
                'account' => '',
                'image' => '',
            ]);

            return true;
        });
    }

    public function detailInvoice($id)
    {
        $cekInvoice = Invoice::where('code', $id)->get();

        if ($cekInvoice->count() == 0) {
            return redirect('admin/finance/invoice')->with('auth_errors', ['Data tidak ditemukan']);
        }

        return view('admin.finance.invoice.detail', [
            'title' => 'Invoice #'.$id,
            'payment' => $cekInvoice,
        ] + $this->websiteData());
    }

    public function invoicePrint($id)
    {
        $invoice = Invoice::where('code', $id)->get();

        if ($invoice->isEmpty()) {
            return redirect('admin/finance/invoice')->with('auth_errors', ['Data tidak ditemukan']);
        }

        // Render template invoice bergaya (port dari CI4 base/guest/invoice/detail).
        // View standalone (bukan layout admin) — meloop $content, $website, $company.
        return view('guest.invoice.detail', [
            'title' => 'Cetak Invoice #'.$id,
            'content' => $invoice,
            'website' => Website::all(),
            'company' => Company::all(),
        ]);
    }

    public function deleteInvoice($code)
    {
        $invoice = Invoice::where('code', $code)->first();

        if (! $invoice) {
            return redirect('admin/finance/invoice')->with('auth_errors', ['Data tidak ditemukan']);
        }

        Invoice::where('code', $code)->delete();

        return redirect('admin/finance/invoice')->with('success', ['Berhasil menghapus invoice #'.$code]);
    }

    public function bulkDeleteInvoice(Request $request)
    {
        $codes = $request->post('codes') ?? $request->post('invoice') ?? [];

        if (empty($codes)) {
            return redirect('admin/finance/invoice')->with('auth_errors', ['Tidak ada invoice yang dipilih']);
        }

        if (! is_array($codes)) {
            $codes = [$codes];
        }

        Invoice::whereIn('code', $codes)->delete();

        return redirect('admin/finance/invoice')->with('success', ['Berhasil menghapus '.count($codes).' invoice']);
    }

    public function generateInvoice()
    {
        $currentYear = (int) date('Y');
        $currentMonth = (int) date('n');
        $years = range($currentYear, 2020);

        return view('admin.finance.invoice.generate', [
            'title' => 'Generate Invoice',
            'customers' => Order::all(),
            'paket' => Service::where('status', 'Tersedia')->orderBy('id', 'ASC')->get(),
            'years' => $years,
            'currentYear' => $currentYear,
            'currentMonth' => $currentMonth,
        ] + $this->websiteData());
    }

    public function prosesGenerateInvoice(Request $request)
    {
        $website = $this->websiteData();
        $logo = $website['logo'];
        $titletext = $website['titletext'];
        $currentYear = (int) date('Y');

        $validator = Validator::make($request->all(), [
            'bulan' => 'required|integer|between:1,12',
            'tahun' => 'required|integer|between:2020,'.$currentYear,
            'idpel' => 'required|string',
        ], [
            'bulan.between' => 'Bulan tagihan tidak valid',
            'tahun.between' => 'Tahun tagihan tidak valid',
            'idpel.required' => 'Pelanggan wajib dipilih',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withInput()->with('auth_errors', $validator->errors()->all());
        }

        $bulan = (int) $request->post('bulan');
        $tahun = (int) $request->post('tahun');
        $idpel = $request->post('idpel');

        $order = Order::where('idpel', $idpel)->first();

        if (! $order) {
            return redirect('admin/finance/invoice/generate')->withInput()->with('auth_errors', ['Pelanggan tidak ditemukan']);
        }

        $service = Service::where('paket', $order->paket)->first();

        if (! $service) {
            return redirect('admin/finance/invoice/generate')->withInput()->with('auth_errors', ['Paket pelanggan tidak ditemukan']);
        }

        $user = $order->nama;
        $package = $order->paket;
        $nomornya = $order->nomor;
        $emailnya = $order->email;
        $expdate = $order->expdate;
        $tanggal = min((int) date('d'), (int) date('t', strtotime(sprintf('%04d-%02d-01', $tahun, $bulan))));
        $date = sprintf('%04d-%02d-%02d', $tahun, $bulan, $tanggal);
        $price = $service->harga + $service->harga * $service->ppn / 100;

        $kodebaru = null;
        $invoiceData = [
            'idpel' => $idpel,
            'nama' => $user,
            'package' => $package,
            'price' => $price,
            'status' => 'Unpaid',
            'date' => $date,
            'expdate' => $expdate,
            'account' => 'user',
        ];

        $created = DB::transaction(function () use ($idpel, $bulan, $tahun, $invoiceData, &$kodebaru) {
            $datainvoice = Invoice::where('idpel', $idpel)
                ->whereMonth('date', $bulan)
                ->whereYear('date', $tahun)
                ->lockForUpdate()
                ->count();

            if ($datainvoice > 0) {
                return false;
            }

            do {
                $kodebaru = $this->generateInvoiceCode().randinv(5);
            } while (Invoice::where('code', $kodebaru)->lockForUpdate()->exists());

            Invoice::insert($invoiceData + ['code' => $kodebaru]);

            return true;
        });

        if (! $created) {
            return redirect('admin/finance/invoice/generate')->with('auth_errors', ['Gagal Membuat Invoice, periode tersebut sudah ada']);
        }

        $notifTagihan = Notification::all()->last()?->notif_tagihan ?? 'off';

        if ($notifTagihan == 'on') {
            $templateMessage = TemplateMessage::all()->last();
            $message = $templateMessage->notif_tagihan ?? '';
            $pesanemail = $templateMessage->notif_tagihan_email ?? '';

            $smtp = SmtpSetting::all()->last();
            $key = $smtp->key ?? '';
            $name = $smtp->nama ?? '';
            $email = $smtp->email ?? '';
            $tglindo = tanggal_indo(date('Y-m-d', strtotime($expdate)));
            $base_url = url('/').'/';

            $message = str_replace('{nama_customer}', $user, $message);
            $message = str_replace('{id_pelanggan}', $idpel, $message);
            $message = str_replace('{expdate}', $tglindo, $message);
            $message = str_replace('{link_web}', $base_url, $message);
            $message = str_replace('{nomor_invoice}', $kodebaru, $message);
            $message = str_replace(['{link_bayar}', '{link_invoice}'], [url('tagihan/'.$kodebaru), url('invoice/'.$kodebaru)], $message);

            $pesanemail = str_replace('{nama_customer}', $user, $pesanemail);
            $pesanemail = str_replace('{id_pelanggan}', $idpel, $pesanemail);
            $pesanemail = str_replace('{expdate}', $tglindo, $pesanemail);
            $pesanemail = str_replace('{link_web}', $base_url, $pesanemail);
            $pesanemail = str_replace('{nomor_invoice}', $kodebaru, $pesanemail);

            try {
                WhatsAppNotifier::sendNotification(WhatsAppNotifier::EVENT_TAGIHAN, $nomornya, $message, [$user, $idpel, $tglindo, url('tagihan/'.$kodebaru), $kodebaru, $package, url('invoice/'.$kodebaru)], $kodebaru);
            } catch (\Throwable $e) {
                Log::warning("Gagal kirim WhatsApp tagihan invoice #{$kodebaru} ({$idpel}): {$e->getMessage()}");
            }

            try {
                $apiInstance = $this->brevoApiInstance($key, $name, $email);
                $sendSmtpEmail = $this->buildBrevoEmail(
                    'Tagihan Internet '.$titletext.' Telah Terbit - #'.$kodebaru.' ',
                    $pesanemail,
                    $logo,
                    $titletext,
                    $name,
                    $email,
                    $emailnya
                );
                $apiInstance->sendTransacEmail($sendSmtpEmail);
            } catch (\Throwable $e) {
                Log::warning("Gagal kirim email tagihan invoice #{$kodebaru} ({$idpel}): {$e->getMessage()}");
            }
        }

        return redirect('admin/finance/invoice/generate')->with('success', ['Berhasil membuat invoice #'.$kodebaru]);
    }

    public function bayar($id)
    {
        $cek = Invoice::where('code', $id)->get();
        if ($cek->count() == 0) {
            return redirect('admin/finance/invoice');
        }

        return view('admin.finance.invoice.bayar', [
            'title' => 'Payment Invoice #'.$id,
            'category' => PaymentCat::where('status', '1')->get(),
            'history' => $cek,
        ] + $this->websiteData());
    }

    public function pay(Request $request)
    {
        $invoiceCode = trim((string) $request->post('invoice'));
        $codecoupon = $request->post('codecoupon');
        $postService = $request->post('service');
        $postCategory = $request->post('category');

        if ($invoiceCode === '') {
            return redirect('admin/finance/invoice')->with('auth_errors', ['Invoice tidak ditemukan']);
        }

        if (empty($postService) || empty($postCategory) || $postService === '0') {
            return redirect('admin/finance/invoice/bayar/'.$invoiceCode)->with('auth_errors', ['Pilih metode pembayaran terlebih dahulu']);
        }

        $paymentMethod = PaymentMethod::where('id', $postService)
            ->where('status', '1')
            ->first();

        if (! $paymentMethod || $paymentMethod->category !== $postCategory) {
            return redirect('admin/finance/invoice/bayar/'.$invoiceCode)->with('auth_errors', ['Metode pembayaran tidak valid']);
        }

        $paymentGateway = PaymentGateway::where('payment_default', '1')->first();

        if (! $paymentGateway) {
            return redirect('admin/finance/invoice/bayar/'.$invoiceCode)->with('auth_errors', ['Payment gateway default belum disetel']);
        }

        // Gateway selain tripay (mis. duitku) memakai dispatcher terpusat yang
        // sudah menangani lock, validasi status invoice, dan pembuatan transaksi.
        if ($paymentGateway->name !== 'tripay') {
            $invoice = Invoice::where('code', $invoiceCode)->where('account', 'user')->first();

            if (! $invoice) {
                return redirect('admin/finance/invoice')->with('auth_errors', ['Invoice tidak ditemukan']);
            }

            $order = Order::where('idpel', $invoice->idpel)->first();

            $result = InvoicePayment::create($invoice, $paymentMethod, $paymentGateway, $order, $paymentMethod->category, $codecoupon);

            if (! $result['ok']) {
                return redirect('admin/finance/invoice/bayar/'.$invoiceCode)->with('auth_errors', [$result['message']]);
            }

            return view($result['view_name'], $result['view'] + ['backUrl' => url('admin/finance/invoice')]);
        }

        $lockName = 'pay_invoice_'.$invoiceCode;
        $lock = DB::selectOne('SELECT GET_LOCK(?, 10) AS acquired', [$lockName]);

        if (! $lock || (int) $lock->acquired !== 1) {
            return redirect('admin/finance/invoice/bayar/'.$invoiceCode)->with('auth_errors', ['Invoice sedang diproses, coba lagi beberapa saat']);
        }

        try {
            $invoice = Invoice::where('code', $invoiceCode)->where('account', 'user')->first();

            if (! $invoice) {
                return redirect('admin/finance/invoice')->with('auth_errors', ['Invoice tidak ditemukan']);
            }

            if ($invoice->status === 'Paid') {
                return redirect('admin/finance/invoice')->with('auth_errors', ['Invoice #'.$invoiceCode.' sudah terbayar']);
            }

            if ($invoice->status !== 'Unpaid') {
                return redirect('admin/finance/invoice')->with('auth_errors', ['Invoice #'.$invoiceCode.' tidak dapat diproses karena status '.$invoice->status]);
            }

            // Bila transaksi Tripay lama masih aktif, jangan buat VA/reference baru
            // untuk invoice yang sama agar customer tidak memegang beberapa kode bayar.
            $paymentStillActive = ! empty($invoice->reference);
            if ($paymentStillActive && ! empty($invoice->exppay)) {
                $expiredAt = DateTime::createFromFormat('d-m-Y H:i:s', $invoice->exppay);
                $paymentStillActive = $expiredAt ? $expiredAt->getTimestamp() > time() : true;
            }

            if ($paymentStillActive) {
                return redirect('admin/finance/invoice/bayar/'.$invoiceCode)->with('auth_errors', ['Transaksi pembayaran invoice #'.$invoiceCode.' masih aktif. Tunggu sampai expired sebelum membuat transaksi baru.']);
            }

            $order = Order::where('idpel', $invoice->idpel)->first();
            $nama = $invoice->nama;
            $email = $order->email ?? '';
            $nomor = $order->nomor ?? '';
            $package = $invoice->package;
            $price = (int) $invoice->price; // Ambil nominal dari DB, jangan percaya hidden form.

            date_default_timezone_set('Asia/Jakarta');
            $randangka = rand(1, 999);
            $total = $price + $randangka;
            $satuhari = mktime(0, 0, 0, date('n'), date('j') + 1, date('Y'));
            $expired = date('d-m-Y', $satuhari).' '.date('H:i:s');

            $tripay = new TripayPayment($paymentGateway->api_url.$paymentGateway->url, $paymentGateway->code_merchant, $paymentGateway->api_key, $paymentGateway->private_key, $paymentGateway->callback);
            $signature = hash_hmac('sha256', $paymentGateway->code_merchant.$invoiceCode.$total, $paymentGateway->private_key);
            $tripay->set_params([
                'method' => $paymentMethod->provider_code,
                'merchant_ref' => $invoiceCode,
                'amount' => $total,
                'customer_name' => $nama,
                'customer_email' => $email,
                'customer_phone' => $nomor,
                'order_items' => [
                    [
                        'name' => $package,
                        'price' => $total,
                        'quantity' => 1,
                    ],
                ],
                'expired_time' => (time() + (24 * 60 * 60)), // 24 jam
                'signature' => $signature,
            ]);

            try {
                $result = $tripay->createTransaction();
            } catch (\Throwable $e) {
                Log::warning("Gagal membuat transaksi Tripay invoice #{$invoiceCode}: {$e->getMessage()}");

                return redirect('admin/finance/invoice/bayar/'.$invoiceCode)->with('auth_errors', ['Gagal menghubungi payment gateway']);
            }

            $response = json_decode($result);

            if (! $response) {
                Log::warning("Respons Tripay tidak valid untuk invoice #{$invoiceCode}: ".$result);

                return redirect('admin/finance/invoice/bayar/'.$invoiceCode)->with('auth_errors', ['Sistem pembayaran sedang maintenance, coba lagi nanti']);
            }

            if (($response->success ?? false) !== true) {
                return redirect('admin/finance/invoice')->with('auth_errors', [$response->message ?? 'Gagal membuat transaksi pembayaran']);
            }

            if (empty($response->data?->reference) || empty($response->data?->amount) || empty($response->data?->payment_method)) {
                return redirect('admin/finance/invoice')->with('auth_errors', ['Response payment gateway tidak lengkap']);
            }

            $note = $paymentMethod->note;
            $paymentUrl = null;
            $qr_url = null;
            if (! empty($response->data->pay_code)) {
                $note = $response->data->pay_code;
            } elseif (! empty($response->data->pay_url)) {
                $paymentUrl = $response->data->pay_url;
            } elseif (! empty($response->data->qr_url)) {
                $qr_url = $response->data->qr_url;
            }

            $getdata = $tripay->getChannels($response->data->payment_method);
            $ress = json_decode($getdata) ?: (object) [];
            $mix['data'] = $package;
            $mix['tripay'] = $response;
            $mix['payment'] = $ress;
            $random_price = $response->data->amount;
            $data = [
                'category' => $paymentMethod->category,
                'service' => $paymentMethod->service,
                'method' => $paymentMethod->name,
                'penerima' => $note,
                'random_price' => $random_price,
                'received' => $total,
                'reference' => $response->data->reference,
                'exppay' => $expired,
                'payment_url' => $paymentUrl,
                'qr_url' => $qr_url,
                'code_coupon' => $codecoupon,
                'provider' => 'tripay',
            ];

            $update = Invoice::where('code', $invoiceCode)
                ->where('status', 'Unpaid')
                ->update($data);

            if (! $update) {
                return redirect('admin/finance/invoice')->with('auth_errors', ['Invoice sudah berubah status, transaksi pembayaran dibatalkan']);
            }

            return view('admin.finance.invoice.pay.tripay', $mix);
        } finally {
            DB::selectOne('SELECT RELEASE_LOCK(?) AS released', [$lockName]);
        }
    }

    // =========================================================
    // AJAX endpoints (path finance/* — sama seperti CI4 global routes)
    // =========================================================

    public function getDataFilterCashFlows(Request $request)
    {
        $bulan = $request->post('bulan');
        $tahun = $request->post('tahun');

        if (! empty($bulan) && ! empty($tahun)) {
            // Jika filter bulan dan tahun sudah dipilih, ambil data dengan filter
            $data = Report::whereMonth('date', $bulan)->whereYear('date', $tahun)->get();
        } else {
            // Jika belum ada filter bulan dan tahun, ambil data tanpa filter
            $data = $this->getDataReport();
        }

        return response()->json($data);
    }

    public function ambilDataCashFlows($bulan = null, $tahun = null)
    {
        if ($bulan && $tahun) {
            $data = [
                'getDataPemasukan' => Report::where('jenis_kategori', 'Pemasukan')
                    ->whereMonth('date', $bulan)->whereYear('date', $tahun)->sum('balance'),
                'getDataPengeluaran' => Report::where('jenis_kategori', 'Pengeluaran')
                    ->whereMonth('date', $bulan)->whereYear('date', $tahun)->sum('balance'),
            ];
        } else {
            $data = [
                'getDataPemasukan' => $this->getDataPemasukan(),
                'getDataPengeluaran' => $this->getDataPengeluaran(),
            ];
        }

        return response()->json($data);
    }

    public function getDataFilter(Request $request)
    {
        $bulan = $request->post('bulan');
        $tahun = $request->post('tahun');

        if (! empty($bulan) && ! empty($tahun)) {
            $data = Invoice::whereMonth('expdate', $bulan)
                ->whereYear('expdate', $tahun)
                ->where('status', 'Paid')
                ->orderBy('last_update', 'ASC')
                ->get();
        } else {
            $data = Invoice::where('status', 'Paid')->orderBy('id', 'DESC')->get();
        }

        return response()->json($data);
    }

    public function ambilDataStatistik($bulan = null, $tahun = null)
    {
        return $this->ambilDataInvoice($bulan, $tahun);
    }

    public function getDataFilterInvoice(Request $request)
    {
        $bulan = $request->post('bulan');
        $tahun = $request->post('tahun');

        if (! empty($bulan) && ! empty($tahun)) {
            $data = Invoice::whereMonth('date', $bulan)
                ->whereYear('date', $tahun)
                ->where('account', 'user')
                ->get();
        } else {
            $data = Invoice::where('account', 'user')->get();
        }

        return response()->json($data);
    }

    public function ambilDataInvoice($bulan = null, $tahun = null)
    {
        if ($bulan && $tahun) {
            $data = [
                'getAllCredit' => Invoice::whereMonth('expdate', $bulan)
                    ->whereYear('expdate', $tahun)
                    ->where('status', 'Paid')
                    ->sum('received'),
                'getInvoicePaid' => Invoice::whereMonth('date', $bulan)->whereYear('date', $tahun)
                    ->where('status', 'Paid')->where('account', 'user')->count(),
                'getInvoiceUnpaid' => Invoice::whereMonth('date', $bulan)->whereYear('date', $tahun)
                    ->where('status', 'Unpaid')->where('account', 'user')->count(),
            ];
        } else {
            $data = [
                'getAllCredit' => Invoice::where('status', 'Paid')->sum('received'),
                'getInvoicePaid' => Invoice::where('status', 'Paid')->where('account', 'user')->count(),
                'getInvoiceUnpaid' => Invoice::where('status', 'Unpaid')->where('account', 'user')->count(),
            ];
        }

        return response()->json($data);
    }

    /**
     * Port dari CI4 AjaxController::getCategory — dipakai halaman bayar invoice
     * untuk mengisi dropdown metode pembayaran per kategori.
     */
    public function getCategoryAjax(Request $request)
    {
        if ($request->has('category')) {
            $getPaymentDefault = PaymentGateway::where('payment_default', '1')->get();

            if ($getPaymentDefault->isEmpty()) {
                $getPaymentDefault = PaymentGateway::where('payment_default', '1')->where('status', 'enable')->get();
            }

            $PaymentName = null;
            foreach ($getPaymentDefault as $dataPaymentDefault) {
                $PaymentName = $dataPaymentDefault->name;
            }

            $category = $request->post('category');
            $data = PaymentMethod::where('category', $category)
                ->where('status', '1')
                ->where('provider', $PaymentName)
                ->get();

            $html = '<option value="0">Pilih salah satu ...</option>';
            foreach ($data as $row) {
                $html .= '<option value="'.$row->id.'">'.$row->service.'</option>';
            }

            return response($html);
        }

        return response('<option value="0">Error..</option>');
    }

    // =========================================================
    // Helper query (port dari FinanceModel CI4 — logika sama persis)
    // =========================================================

    private function getDataReport()
    {
        return Report::whereMonth('date', date('m'))
            ->whereYear('date', date('Y'))
            ->get();
    }

    private function getDataPemasukan()
    {
        return Report::where('jenis_kategori', 'Pemasukan')
            ->whereMonth('date', date('m'))
            ->whereYear('date', date('Y'))
            ->sum('balance');
    }

    private function getDataPengeluaran()
    {
        return Report::where('jenis_kategori', 'Pengeluaran')
            ->whereMonth('date', date('m'))
            ->whereYear('date', date('Y'))
            ->sum('balance');
    }

    private function getTahunMasuk()
    {
        // Saring tahun 0 (invoice legacy dengan date '0000-00-00' -> YEAR()=0)
        // supaya tidak muncul opsi "Tahun 0" di dropdown filter.
        return Invoice::selectRaw('YEAR(date) AS tahun')
            ->whereRaw('YEAR(date) > 0')
            ->groupByRaw('YEAR(date)')
            ->orderByRaw('YEAR(date) ASC')
            ->get();
    }

    private function generateInvoiceCode()
    {
        // Kode = 'INV' + nomor urut + 5 char random (mis. INV10001AB3XZ).
        // Ambil nomor urut TERBESAR secara NUMERIK, bukan MAX(code) string:
        // string sort menaruh 'INV9999...' > 'INV10000...' sehingga counter beku
        // di 10000 setelah tembus 4 digit. SUBSTRING antara 'INV' (3) dan 5 char
        // random terakhir lalu CAST ke angka.
        $nourut = (int) Invoice::whereRaw("code REGEXP '^INV[0-9]+[A-Za-z0-9]{5}$'")
            ->selectRaw('MAX(CAST(SUBSTRING(code, 4, CHAR_LENGTH(code) - 8) AS UNSIGNED)) AS max_no')
            ->value('max_no');

        $nourut++;
        $char = 'INV';

        // %04d = minimal 4 digit (zero-pad), tapi tetap tumbuh ke 5+ digit bila perlu.
        return $char.sprintf('%04d', $nourut);
    }

    private function getFilteredData($paket, $status, $penerima, $bulan, $tahun)
    {
        $paket = $paket ?: 'Pilih Paket';
        $status = $status ?: 'Tampilkan Semua';
        $penerima = $penerima ?: 'Tampilkan Semua';

        $builder = Invoice::query()->where('account', 'user');

        if ($paket !== 'Pilih Paket') {
            $builder->where('package', $paket);
        }

        if ($status !== 'Tampilkan Semua') {
            $builder->where('status', $status);
        }

        if ($penerima === 'Pembayaran Tunai') {
            $builder->where('update_by', '!=', 'System Payment Gateway Tripay')
                ->where('status', 'Paid');
        } elseif ($penerima === 'Pembayaran Non Tunai') {
            $builder->where('update_by', 'System Payment Gateway Tripay')
                ->where('status', 'Paid');
        }

        if ($bulan !== '0' && $tahun !== '0') {
            $builder->whereMonth('date', $bulan);
            $builder->whereYear('date', $tahun);
        }

        return $builder->orderBy('date', 'DESC')->orderBy('id', 'DESC')->get()->toArray();
    }

    private function getFilteredDataCashFlows($bulan, $tahun, $kategori, $jenis)
    {
        $builder = Report::query();

        // Filter berdasarkan bulan dan tahun
        if ($bulan !== '0' && $tahun !== '0') {
            $builder->whereMonth('date', $bulan);
            $builder->whereYear('date', $tahun);
        }

        if ($kategori !== 'Tampilkan Semua') {
            $builder->where('category', $kategori);
        }

        if ($jenis !== 'Tampilkan Semua') {
            $builder->where('jenis_kategori', $jenis);
        }

        return $builder->get()->toArray();
    }

    private function getFilteredDataCustomers($paket, $status)
    {
        // Data pelanggan berada di tabel `orders` (tidak ada tabel `customer`
        // pada skema ini — join lama menyebabkan SQL error "table not found").
        $builder = Order::query()->select('orders.*');

        // Filter berdasarkan paket internet
        if ($paket !== 'Tampilkan Semua') {
            $builder->where('paket', $paket);
        }

        // Filter berdasarkan status
        if ($status !== 'Tampilkan Semua') {
            $builder->where('status', $status);
        }

        return $builder->get()->toArray();
    }

    private function getFilteredDataNewCustomers($bulan, $tahun)
    {
        // Sumber pelanggan baru = tabel orders (psb legacy tidak terisi).
        // Alias paket->package & pilih idpel/date agar cocok dgn field view.
        $builder = Order::query()
            ->selectRaw('idpel, paket AS package, date');

        // Filter berdasarkan bulan dan tahun
        if ($bulan !== '0' && $tahun !== '0') {
            $builder->whereMonth('date', $bulan);
            $builder->whereYear('date', $tahun);
        }

        return $builder->orderBy('date', 'ASC')->get()->toArray();
    }

    // =========================================================
    // Helper export excel (port dari exportExcel* CI4 — struktur sama)
    // =========================================================

    private function exportExcel($dataToExport, string $fileName)
    {
        // dataToExport bisa dikirim sebagai array (form) atau JSON string.
        if (is_string($dataToExport)) {
            $dataToExport = json_decode($dataToExport, true) ?: [];
        }

        if (! is_array($dataToExport)) {
            $dataToExport = [];
        }

        // Menginisialisasi objek PhpSpreadsheet
        $spreadsheet = new Spreadsheet;
        $worksheet = $spreadsheet->getActiveSheet();

        // Menyusun data ke dalam worksheet
        $rowIndex = 1;
        foreach ($dataToExport as $rowData) {
            if (! is_array($rowData)) {
                continue;
            }

            $colIndex = 'A';
            foreach ($rowData as $cellData) {
                // setCellValueExplicit sebagai STRING: cegah formula injection
                // (nilai diawali '=', '+', '-', '@' tidak dieksekusi sebagai rumus).
                $worksheet->setCellValueExplicit(
                    $colIndex.$rowIndex,
                    (string) $cellData,
                    DataType::TYPE_STRING
                );
                $colIndex++;
            }
            $rowIndex++;
        }

        // Mengatur lebar kolom berdasarkan panjang data dalam kolom
        foreach (range('A', $spreadsheet->getActiveSheet()->getHighestDataColumn()) as $col) {
            $spreadsheet->getActiveSheet()
                ->getColumnDimension($col)
                ->setAutoSize(true);
        }

        // Menambahkan border pada sel-sel worksheet
        $highestRow = $worksheet->getHighestRow();
        $highestColumn = $worksheet->getHighestColumn();
        $cellRange = 'A1:'.$highestColumn.$highestRow;
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ];
        $worksheet->getStyle($cellRange)->applyFromArray($styleArray);

        $writer = new Xlsx($spreadsheet);

        // Stream langsung lewat route terproteksi auth. TIDAK menulis ke
        // public/laporan (file publik bernama tetap bisa diunduh tanpa login &
        // saling menimpa antar-admin).
        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    // =========================================================
    // Helper notifikasi (WA + template) — dipakai invoiceUpdate.
    // Mengembalikan [$message, $pesanemail]; mengeset $key/$name/$email
    // via referensi variabel pemanggil tidak dilakukan — smtp diambil
    // ulang di brevoApiInstance().
    // =========================================================

    private function preparePaidNotification($idpel, $code, $nomornya, bool $wasIsolir = false): array
    {
        $templateMessage = TemplateMessage::all()->last();
        $message = $templateMessage->notif_tagihan_terbayar ?? '';
        $pesanemail = $templateMessage->notif_tagihan_terbayar_email ?? '';

        $base_url = url('/').'/';
        $order = Order::where('idpel', $idpel)->first();
        $package = $order?->paket ?? '-';

        $message = str_replace('{id_pelanggan}', $idpel, $message);
        $message = str_replace('{nomor_invoice}', $code, $message);
        $message = str_replace('{link_web}', $base_url, $message);
        $message = str_replace(['{link_bayar}', '{link_invoice}'], [url('tagihan/'.$code), url('invoice/'.$code)], $message);

        $pesanemail = str_replace('{id_pelanggan}', $idpel, $pesanemail);
        $pesanemail = str_replace('{link_web}', $base_url, $pesanemail);
        $pesanemail = str_replace('{nomor_invoice}', $code, $pesanemail);

        if ((string) $nomornya !== '') {
            try {
                WhatsAppNotifier::sendNotification(WhatsAppNotifier::EVENT_TERBAYAR, $nomornya, $message, [$idpel, $code, url('invoice/'.$code), $package]);
            } catch (\Throwable $e) {
                Log::warning("Gagal kirim WhatsApp terbayar invoice #{$code} ({$idpel}): {$e->getMessage()}");
            }

            // Kirim notifikasi buka isolir bila pembayaran ini mengaktifkan kembali
            // pelanggan yang sebelumnya Isolir.
            if ($wasIsolir) {
                try {
                    $nama = $order?->nama ?? '';
                    $bukaMessage = "Layanan Internet Anda Telah Aktif Kembali\n\nNama: {$nama}\nID Pelanggan: {$idpel}\nPaket: {$package}\nTerima kasih atas pembayaran Anda.\nLink: {$base_url}\n\nSalam Hangat\n\nANNORTY NET";

                    // Template Meta notif_buka_isolir: [nama, id_pelanggan, paket, link]
                    WhatsAppNotifier::sendNotification(WhatsAppNotifier::EVENT_BUKA_ISOLIR, $nomornya, $bukaMessage, [
                        $nama,
                        $idpel,
                        $package,
                        $base_url,
                    ]);
                } catch (\Throwable $e) {
                    Log::warning("Gagal kirim WhatsApp buka isolir invoice #{$code} ({$idpel}): {$e->getMessage()}");
                }
            }
        }

        return [$message, $pesanemail];
    }

    private function brevoApiInstance(&$key = null, &$name = null, &$email = null)
    {
        $smtp = SmtpSetting::all();

        foreach ($smtp as $datanya) {
            $key = $datanya->key;
            $name = $datanya->nama;
            $email = $datanya->email;
        }

        $config = Configuration::getDefaultConfiguration()->setApiKey('api-key', $key);

        return new TransactionalEmailsApi(
            new Client,
            $config
        );
    }

    /**
     * Template HTML email Brevo — identik dengan blok HTML pada CI4
     * (invoiceUpdate pppoe/hotspot/non-isolir & prosesGenerate memakai
     * template yang sama persis, hanya subject & isi $pesanemail berbeda).
     */
    private function buildBrevoEmail($subject, $pesanemail, $logo, $titletext, $senderName, $senderEmail, $toEmail)
    {
        $base_url = url('/').'/';

        $sendSmtpEmail = new SendSmtpEmail;
        $sendSmtpEmail['params'] = ['subject' => $subject];
        $sendSmtpEmail['subject'] = '{{params.subject}}';

        $sendSmtpEmail['htmlContent'] = '
        <html>
        <body>
        <table cellpadding="0" cellspacing="0" border="0" width="100%">
        <tbody>
        <tr>
        <td bgcolor="#f2f2f2" style="font-size:0px">&nbsp;</td>
        <td bgcolor="#ffffff" width="660" align="center">
        <table cellpadding="0" cellspacing="0" border="0" width="100%">
        <tbody>
        <tr>
        <td align="center" width="600" valign="top">
        <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tbody>
        <tr>
        <td bgcolor="#f2f2f2" style="padding-top:10px"></td>
        </tr>
        <tr>
        <td bgcolor="#f2f2f2" style="padding-top:10px"></td>
        </tr>
        <tr>
        <td align="center" valign="top" bgcolor="#ffffff">
        <table border="0" cellpadding="0" cellspacing="0" style="padding-bottom:10px;padding-top:20px" width="100%">
        <tbody>
        <tr valign="bottom">
        <td width="20" align="center" valign="top">&nbsp;</td>
        <td>
        <span>
        <center>
        <p><img src="'.$base_url.'assets/logo/'.$logo.'" height="50" alt="logo"></p>
        </center>
        </span>
        </td>
        <td width="20" align="center" valign="top">&nbsp;</td>
        </tr>
        </tbody>
        </table>
        <table border="0" cellpadding="0" cellspacing="0" style="padding-bottom:10px;padding-top:10px;margin-bottom:10px" width="100%">
        <tbody>
        <tr valign="bottom">
        <td width="20" align="center" valign="top">&nbsp;</td>
        <td valign="top" style="font-family:Calibri,Trebuchet,Arial,sans serif;font-size:15px;line-height:22px;color:#333333">
        <p>'.$pesanemail.' </p>
        <p><br><br>Best Regards,<br/><strong>'.$titletext.' </strong><br>
        </td>
        <td width="20" align="center" valign="top">&nbsp;</td>
        </tr>
        </tbody>
        </table>
        </td>
        </tr>
        </tbody>
        </table>
        </td>
        </tr>
        </tbody>
        </table>
        <table cellpadding="0" cellspacing="0" border="0" width="100%">
        <tbody>
        <tr>
        <td align="center" width="600" valign="top">
        <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tbody>
        <tr>
        <td bgcolor="#f2f2f2" style="padding-top:20px"></td>
        </tr>
        <tr>
        <td align="center" valign="top" bgcolor="#f2f2f2">
        <table border="0" cellpadding="0" cellspacing="0" width="100%">
        <tbody>
        <tr valign="bottom">
        <td width="20" align="center" valign="top">&nbsp;</td>
        <td>
        <table align="left" border="0" cellpadding="0" cellspacing="0">
        <tbody>
        <tr>
        <td style="font-family:Calibri,Trebuchet,Arial,sans serif;font-size:13px;color:#666;font-weight:bold">
        <span id="m_-6118667421211539915bottomLinks">
        <div style="margin:5px 0;padding:0">
        <span style="display:inline">
        <span>
        <a href="'.$base_url.'" style="text-decoration:none" target="_blank">
        Bantuan&nbsp;
        </a>
        </span>
        <span style="color:#ccc"><span> | </span></span>
        <span>
        <a href="'.$base_url.'" style="text-decoration:none" target="_blank" >
        Website&nbsp;
        </a>
        </span>
        </span>
        </div>
        </span>
        </td>
        </tr>
        </tbody>
        </table>
        </td>
        <td width="20" align="center" valign="top">&nbsp;</td>
        </tr>
        </tbody>
        </table>
        <table border="0" cellpadding="0" cellspacing="0" width="100%">
        <tbody>
        <tr valign="bottom">
        <td width="20" align="center" valign="top">&nbsp;</td>
        <td>
        <p> Jangan balas ke email ini. Untuk menghubungi kami, klik
        <strong><a href="" style="text-decoration:none" target="_blank" >Bantuan dan Hubungi</a></strong>.
        </p>
        </td>
        <td width="20" align="center" valign="top">&nbsp;</td>
        </tr>
        </tbody>
        </table>
        <table border="0" cellpadding="0" cellspacing="0" width="100%">
        <tbody>
        <tr valign="bottom">
        <td width="20" align="center" valign="top">&nbsp;</td>
        <td>
        <span>
        <table border="0" cellpadding="0" cellspacing="0" id="m_-6118667421211539915emailFooter" style="padding-top:10px;font:12px Arial,Verdana,Helvetica,sans-serif;color:#292929" width="100%">
        <tbody>
        <tr>
        <td>
        <p>Hak Cipta © 2024 '.$titletext.'</p>
        </td>
        </tr>
        </tbody>
        </table>
        </span>
        </td>
        <td width="20" align="center" valign="top">&nbsp;</td>
        </tr>
        </tbody>
        </table>

        </td>
        </tr>
        </tbody>
        </table>
        </td>

        </tr>
        </tbody>
        </table>
        </td>
        <td bgcolor="#f2f2f2" style="font-size:0px">&nbsp;</td>
        </tr>
        </tbody>
        </table>
        </body>
        </html>';

        $sendSmtpEmail['sender'] = ['name' => $senderName, 'email' => $senderEmail];
        $sendSmtpEmail['to'] = [
            ['email' => $toEmail],
        ];
        $sendSmtpEmail['replyTo'] = ['email' => $senderEmail, 'name' => $senderName];

        return $sendSmtpEmail;
    }
}
