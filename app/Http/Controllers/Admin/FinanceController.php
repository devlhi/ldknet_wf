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
use Carbon\CarbonImmutable;
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
            'getDataPemasukan' => 0,
            'getDataPengeluaran' => 0,
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
        $years = Report::selectRaw('YEAR(date) AS tahun')
            ->groupByRaw('YEAR(date)')
            ->orderByRaw('YEAR(date) ASC')
            ->get();

        if (! $years->contains(fn ($row) => (int) $row->tahun === 2026)) {
            $years->push((object) ['tahun' => 2026]);
        }

        return view('admin.finance.report.cash-flows', [
            'title' => 'Laporan',
            'katkas' => Katkas::all(),
            'tahun' => $years->sortBy('tahun')->values(),
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
        // Tabel psb legacy praktis tidak terisi; pelanggan baru yang benar
        // berada di orders.date (tanggal pelanggan dibuat/aktif).
        $years = Order::selectRaw('YEAR(date) AS tahun')
            ->whereRaw('YEAR(date) > 0')
            ->groupByRaw('YEAR(date)')
            ->orderByRaw('YEAR(date) ASC')
            ->get();

        if (! $years->contains(fn ($row) => (int) $row->tahun === 2026)) {
            $years->push((object) ['tahun' => 2026]);
        }

        return view('admin.finance.report.new_customers', [
            'title' => 'Laporan',
            'tahun' => $years->sortBy('tahun')->values(),
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
            'getInvoicePaid' => 0,
            'getInvoiceUnpaid' => 0,
            'tahun' => $this->getTahunMasuk(),
        ] + $this->websiteData());
    }

    public function editInvoice($id)
    {
        $invoice = Invoice::where('code', $id)
            ->where('account', 'user')
            ->where('status', 'Unpaid')
            ->first();

        if (! $invoice) {
            return redirect('admin/finance/invoice')->with('auth_errors', ['Invoice tidak ditemukan atau sudah tidak dapat dikonfirmasi']);
        }

        return view('admin.finance.invoice.edit', [
            'title' => 'Invoice #'.$id,
            'payment' => collect([$invoice]),
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
            'confirmation_period' => 'nullable|in:current,next',
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

        $code = trim((string) $request->post('code'));
        $idpel = trim((string) $request->post('idpel'));
        $target = (int) $request->post('target');
        $category = (string) $request->post('category');
        $metode = (string) $request->post('metode');
        $advanceMonth = $request->post('confirmation_period', 'current') === 'next';

        $invoice = Invoice::where('id', $target)
            ->where('code', $code)
            ->where('idpel', $idpel)
            ->where('account', 'user')
            ->first();

        if (! $invoice) {
            return redirect('admin/finance/invoice')->with('auth_errors', ['Invoice tidak ditemukan atau data tidak cocok']);
        }

        if ($invoice->status !== 'Unpaid') {
            $message = $invoice->status === 'Paid'
                ? 'Invoice #'.$code.' sudah terbayar'
                : 'Invoice #'.$code.' tidak dapat dikonfirmasi karena statusnya '.($invoice->status === 'Error' ? 'Cancel' : $invoice->status);

            return redirect('admin/finance/invoice')->with('auth_errors', [$message]);
        }

        $order = Order::where('idpel', $idpel)->first();
        if (! $order) {
            return redirect('admin/finance/invoice')->with('auth_errors', ['Pelanggan invoice tidak ditemukan']);
        }

        $service = Service::where('paket', $invoice->package)->first();
        if (! $service) {
            return redirect('admin/finance/invoice')->with('auth_errors', ['Paket pelanggan tidak ditemukan']);
        }

        if ($this->invoiceHasActiveGatewayTransaction($invoice)) {
            return redirect('admin/finance/invoice')->withInput()->with('auth_errors', [
                'Invoice #'.$invoice->code.' masih memiliki transaksi pembayaran online aktif. Tunggu transaksi expired atau selesaikan rekonsiliasi terlebih dahulu.',
            ]);
        }

        if ($advanceMonth && ($advanceError = $this->advancePaymentPreflightError($invoice))) {
            return redirect('admin/finance/invoice')->withInput()->with('auth_errors', [$advanceError]);
        }

        $lockNames = $this->manualPaymentLockNames($invoice, $advanceMonth);
        $acquiredLockNames = [];
        $filename = '';
        $paymentCommitted = false;
        $paymentResult = null;

        try {
            foreach ($lockNames as $lockName) {
                if (! Invoice::acquireNamedLock($lockName)) {
                    return redirect('admin/finance/invoice')->with('auth_errors', ['Invoice sedang diproses, coba lagi beberapa saat']);
                }

                $acquiredLockNames[] = $lockName;
            }

            // File baru dipindahkan setelah advisory lock didapat. Jika transaksi DB
            // ditolak/exception, finally menghapus file agar tidak menjadi orphan.
            if ($request->hasFile('image')) {
                $gambar = $request->file('image');
                // Ekstensi berdasarkan isi file, bukan nama dari client.
                $ext = strtolower((string) $gambar->guessExtension());
                $ext = in_array($ext, ['jpg', 'jpeg', 'png'], true) ? $ext : 'jpg';
                $filename = 'bukti-pembayaran-'.$code.'-'.date('ymd').'-'.Str::random(12).'.'.$ext;
                $gambar->move(public_path('data/bukti'), $filename);
            }

            $paidAt = CarbonImmutable::now('Asia/Jakarta')->startOfDay();
            $date = $paidAt->toDateString();
            $update = [
                'status' => 'Paid',
                'category' => $category,
                'service' => $metode,
                'method' => $metode,
                'received' => (int) $invoice->price,
                'last_update' => $date,
                'update_by' => (string) auth()->user()?->nama,
            ];
            if ($filename !== '') {
                // Jangan hapus proof lama milik invoice tujuan jika admin tidak
                // mengunggah proof baru saat memakai destination yang sudah ada.
                $update['bukti_pembayaran'] = $filename;
            }

            $paymentResult = $this->commitInvoicePayment(
                $target,
                $update,
                $idpel,
                (int) $invoice->price,
                (string) $invoice->nama,
                $date,
                $advanceMonth
            );

            if (! $paymentResult['ok']) {
                return redirect('admin/finance/invoice')->with('auth_errors', [$paymentResult['message']]);
            }

            $paymentCommitted = true;
        } catch (\Throwable $e) {
            Log::error("Konfirmasi manual invoice #{$code} gagal sebelum commit: {$e->getMessage()}", ['exception' => $e]);

            return redirect('admin/finance/invoice')->with('auth_errors', ['Gagal mengonfirmasi invoice. Silakan coba lagi.']);
        } finally {
            if (! $paymentCommitted && $filename !== '') {
                $proofPath = public_path('data/bukti/'.$filename);
                if (is_file($proofPath) && ! @unlink($proofPath)) {
                    Log::warning("Gagal membersihkan bukti pembayaran orphan: {$proofPath}");
                }
            }

            foreach (array_reverse($acquiredLockNames) as $acquiredLockName) {
                try {
                    Invoice::releaseNamedLock($acquiredLockName);
                } catch (\Throwable $e) {
                    Log::warning("Gagal melepas advisory lock konfirmasi invoice #{$code}: {$e->getMessage()}");
                }
            }
        }

        // Mulai titik ini pembayaran sudah committed. Kegagalan RouterOS atau
        // notifikasi tidak boleh dilaporkan sebagai kegagalan pembayaran.
        $paidCode = $paymentResult['code'];
        $wasIsolir = $paymentResult['was_isolir'];
        $routerRestored = true;

        try {
            if ($wasIsolir) {
                $routerRestored = $this->restoreCustomerAccessAfterPayment(
                    $idpel,
                    (string) $service->ppp_profile,
                    $paymentResult['expdate']
                );
            }
        } catch (\Throwable $e) {
            $routerRestored = false;
            Log::warning("Post-commit buka isolir invoice #{$paidCode} ({$idpel}) gagal: {$e->getMessage()}");
            $this->queueCustomerAccessRestore($idpel, $paymentResult['expdate']);
        }

        try {
            [, $pesanemail] = $this->preparePaidNotification(
                $idpel,
                $paidCode,
                (string) $order->nomor,
                $wasIsolir && $routerRestored
            );
            $this->sendPaidEmail($paidCode, $idpel, $pesanemail, $logo, $titletext, (string) $order->email);
        } catch (\Throwable $e) {
            Log::warning("Post-commit notifikasi invoice #{$paidCode} ({$idpel}) gagal: {$e->getMessage()}");
        }

        $successMessage = $advanceMonth
            ? 'Invoice #'.$code.' dibatalkan dan pembayaran dikonfirmasi ke invoice #'.$paidCode
            : 'Berhasil mengupdate invoice #'.$paidCode;

        if ($wasIsolir && ! $routerRestored) {
            $successMessage .= '. Pembayaran sudah tercatat, tetapi Router belum dapat diaktifkan; sistem akan mencoba lagi pada jadwal berikutnya.';
        }

        return redirect('admin/finance/invoice')->with('success', [$successMessage]);
    }

    /**
     * Validasi awal sebelum RouterOS disentuh. Validasi yang sama diulang dengan
     * row lock saat commit untuk mencegah perubahan status di request paralel.
     */
    private function advancePaymentPreflightError(Invoice $source): ?string
    {
        if ($this->invoiceHasActiveGatewayTransaction($source)) {
            return 'Invoice #'.$source->code.' masih memiliki transaksi pembayaran online. Tunggu transaksi expired atau selesaikan rekonsiliasi terlebih dahulu.';
        }

        try {
            $targetPeriod = CarbonImmutable::parse((string) $source->date, 'Asia/Jakarta')->addMonthNoOverflow();
        } catch (\Throwable) {
            return 'Periode invoice #'.$source->code.' tidak valid.';
        }

        $targets = Invoice::where('idpel', $source->idpel)
            ->where('account', 'user')
            ->whereMonth('date', $targetPeriod->month)
            ->whereYear('date', $targetPeriod->year)
            ->get();

        if ($targets->count() > 1) {
            return 'Ditemukan lebih dari satu invoice pada periode tujuan. Selesaikan data duplikat terlebih dahulu.';
        }

        $target = $targets->first();
        if ($target && $target->status !== 'Unpaid') {
            return 'Invoice periode '.bulan_indo($targetPeriod->toDateString()).' sudah berstatus '.$target->status.'.';
        }

        if ($target && $this->invoiceHasActiveGatewayTransaction($target)) {
            return 'Invoice periode '.bulan_indo($targetPeriod->toDateString()).' masih memiliki transaksi pembayaran online. Tunggu transaksi expired atau selesaikan rekonsiliasi terlebih dahulu.';
        }

        if ($target && ((int) $target->price !== (int) $source->price || $target->package !== $source->package)) {
            return 'Nominal atau paket invoice periode tujuan berbeda. Konfirmasi invoice tujuan secara langsung.';
        }

        return null;
    }

    private function invoiceHasActiveGatewayTransaction(Invoice $invoice): bool
    {
        return $invoice->hasActiveGatewayTransaction();
    }

    /**
     * Kunci source, target invoice yang sudah ada, dan predicate target period
     * dalam urutan stabil. Period lock wajib ada walau destination belum dibuat;
     * row lock saja tidak dapat mengunci baris yang belum ada.
     *
     * @return list<string>
     */
    private function manualPaymentLockNames(Invoice $source, bool $advanceMonth): array
    {
        $names = [Invoice::paymentLockName((string) $source->code)];

        if ($advanceMonth) {
            try {
                $targetPeriod = CarbonImmutable::parse((string) $source->date, 'Asia/Jakarta')->addMonthNoOverflow();
                $period = $targetPeriod->format('Y-m');
                $names[] = Invoice::periodLockName((string) $source->idpel, $period);

                $targetCodes = Invoice::where('idpel', $source->idpel)
                    ->where('account', 'user')
                    ->whereMonth('date', $targetPeriod->month)
                    ->whereYear('date', $targetPeriod->year)
                    ->pluck('code')
                    ->map(static fn ($code): string => (string) $code)
                    ->all();
                foreach ($targetCodes as $targetCode) {
                    $names[] = Invoice::paymentLockName($targetCode);
                }
            } catch (\Throwable) {
                // Preflight/transaction menghasilkan pesan validasi periode.
            }
        }

        $names = array_values(array_unique(array_filter($names, static fn ($name): bool => $name !== '')));
        sort($names, SORT_STRING);

        return $names;
    }

    /**
     * Commit pembayaran manual secara atomik.
     *
     * Untuk mode maju satu bulan, invoice sumber diubah ke status enum legacy
     * `Error` (ditampilkan sebagai Cancel), lalu invoice periode berikut dibuat
     * atau digunakan dan ditandai Paid. Order dan pemasukan ikut dalam transaksi.
     *
     * @return array{ok: bool, code: string, message: string, expdate?: string, was_isolir?: bool}
     */
    private function commitInvoicePayment($target, array $update, $idpel, $price, $user, $date, bool $advanceMonth = false): array
    {
        return DB::transaction(function () use ($target, $update, $idpel, $price, $user, $date, $advanceMonth) {
            $sourceSnapshot = Invoice::where('id', $target)->first();
            if (! $sourceSnapshot || $sourceSnapshot->idpel !== $idpel || $sourceSnapshot->account !== 'user') {
                return ['ok' => false, 'code' => '', 'message' => 'Invoice tidak dapat dikonfirmasi.'];
            }

            $targetPeriod = null;
            $invoiceIds = [(int) $sourceSnapshot->id];

            if ($advanceMonth) {
                try {
                    $targetPeriod = CarbonImmutable::parse((string) $sourceSnapshot->date, 'Asia/Jakarta')->addMonthNoOverflow();
                } catch (\Throwable) {
                    return ['ok' => false, 'code' => '', 'message' => 'Periode invoice sumber tidak valid.'];
                }

                // Query ulang destination setelah period advisory lock diperoleh.
                // Ini menutup race ketika destination belum ada saat preflight.
                $targetIds = Invoice::where('idpel', $idpel)
                    ->where('account', 'user')
                    ->whereMonth('date', $targetPeriod->month)
                    ->whereYear('date', $targetPeriod->year)
                    ->pluck('id')
                    ->map(static fn ($id): int => (int) $id)
                    ->all();
                $invoiceIds = array_merge($invoiceIds, $targetIds);
            }

            // Seluruh invoice dikunci lebih dahulu dalam urutan PK konsisten,
            // kemudian row order. Hindari deadlock source -> order -> target.
            $lockedInvoices = Invoice::whereIn('id', array_values(array_unique($invoiceIds)))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $source = $lockedInvoices->get((int) $target);

            if (! $source || $source->idpel !== $idpel || $source->account !== 'user' || $source->status !== 'Unpaid') {
                return [
                    'ok' => false,
                    'code' => (string) ($source?->code ?? ''),
                    'message' => 'Invoice sudah berubah status atau tidak dapat dikonfirmasi.',
                ];
            }

            if ($this->invoiceHasActiveGatewayTransaction($source)) {
                return ['ok' => false, 'code' => '', 'message' => 'Invoice masih memiliki transaksi pembayaran online aktif.'];
            }

            $paidInvoice = $source;
            if ($advanceMonth) {
                $targets = $lockedInvoices
                    ->filter(fn (Invoice $candidate): bool => $candidate->idpel === $idpel
                        && $candidate->account === 'user'
                        && (int) CarbonImmutable::parse((string) $candidate->date, 'Asia/Jakarta')->month === $targetPeriod->month
                        && (int) CarbonImmutable::parse((string) $candidate->date, 'Asia/Jakarta')->year === $targetPeriod->year)
                    ->values();

                if ($targets->count() > 1) {
                    return ['ok' => false, 'code' => '', 'message' => 'Invoice periode tujuan duplikat. Pembayaran dibatalkan.'];
                }

                $paidInvoice = $targets->first();
                if ($paidInvoice && $paidInvoice->status !== 'Unpaid') {
                    return ['ok' => false, 'code' => '', 'message' => 'Invoice periode tujuan sudah berstatus '.$paidInvoice->status.'.'];
                }

                if ($paidInvoice && $this->invoiceHasActiveGatewayTransaction($paidInvoice)) {
                    return ['ok' => false, 'code' => '', 'message' => 'Invoice periode tujuan masih memiliki transaksi pembayaran online aktif.'];
                }

                if ($paidInvoice && ((int) $paidInvoice->price !== (int) $price || $paidInvoice->package !== $source->package)) {
                    return ['ok' => false, 'code' => '', 'message' => 'Nominal atau paket invoice periode tujuan berbeda.'];
                }
            }

            $order = Order::where('idpel', $idpel)->lockForUpdate()->first();
            if (! $order) {
                return ['ok' => false, 'code' => '', 'message' => 'Pelanggan invoice tidak ditemukan.'];
            }

            $wasIsolir = $order->status === 'Isolir';
            $expdate = $advanceMonth
                ? CarbonImmutable::parse($date, 'Asia/Jakarta')->addMonthNoOverflow()->toDateString()
                // Pertahankan semantik legacy PHP: 31 Januari + 1 bulan = 3 Maret.
                : date('Y-m-d', strtotime('+1 month', strtotime((string) ($order->expdate ?: $source->expdate))));

            if ($advanceMonth && ! $paidInvoice) {
                $paidInvoice = $source->replicate();
                $paidInvoice->code = $this->generateUniqueInvoiceCode();
                $paidInvoice->date = $targetPeriod->toDateString();
                $paidInvoice->expdate = $expdate;
                $paidInvoice->status = 'Unpaid';
                $paidInvoice->category = '';
                $paidInvoice->service = '';
                $paidInvoice->method = '';
                $paidInvoice->penerima = '';
                $paidInvoice->metode_pembayaran = '';
                $paidInvoice->random_price = 0;
                $paidInvoice->received = 0;
                $paidInvoice->reference = '';
                $paidInvoice->exppay = '';
                $paidInvoice->payment_url = null;
                $paidInvoice->qr_url = null;
                $paidInvoice->last_update = $date;
                $paidInvoice->update_by = '';
                $paidInvoice->bukti_pembayaran = '';
                $paidInvoice->data_invoice = '';
                $paidInvoice->code_coupon = '';
                $paidInvoice->otp = '';
                $paidInvoice->provider = '';
                $paidInvoice->save();
            }

            if ($advanceMonth) {
                $source->update([
                    'status' => 'Error',
                    'last_update' => $date,
                    'update_by' => auth()->user()?->nama.' (Cancel - konfirmasi maju 1 bulan)',
                    // Reference hanya bisa sampai di sini jika sudah expired.
                    'reference' => '',
                    'exppay' => '',
                    'payment_url' => null,
                    'qr_url' => null,
                    'random_price' => 0,
                    'provider' => '',
                ]);
            }

            // Manual confirmation mengambil alih transaksi expired: hapus metadata
            // provider supaya callback/tautan lama tidak terlihat sebagai transaksi aktif.
            $paidInvoice->fill($update);
            $paidInvoice->expdate = $expdate;
            $paidInvoice->reference = '';
            $paidInvoice->exppay = '';
            $paidInvoice->payment_url = null;
            $paidInvoice->qr_url = null;
            $paidInvoice->random_price = 0;
            $paidInvoice->provider = '';
            $paidInvoice->save();

            $order->update([
                'status' => 'Active',
                'expdate' => $expdate,
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

            return [
                'ok' => true,
                'code' => (string) $paidInvoice->code,
                'message' => 'Pembayaran berhasil dikonfirmasi.',
                'expdate' => $expdate,
                'was_isolir' => $wasIsolir,
            ];
        });
    }

    /**
     * Koordinasikan perubahan RouterOS per pelanggan dan reload order setelah
     * lock didapat agar cron tidak dapat menerapkan snapshot Isolir yang stale.
     */
    private function restoreCustomerAccessAfterPayment(string $idpel, string $profile, string $expectedExpdate): bool
    {
        $lockName = Invoice::customerAccessLockName($idpel);
        if (! Invoice::acquireNamedLock($lockName)) {
            $this->queueCustomerAccessRestore($idpel, $expectedExpdate);

            return false;
        }

        try {
            $order = Order::where('idpel', $idpel)->first();
            if (! $order || $order->status !== 'Active' || (string) $order->expdate !== $expectedExpdate) {
                Log::warning("Buka isolir manual ditunda karena state pelanggan berubah ({$idpel}).");

                return false;
            }

            $restored = $this->restoreCustomerRouterAccess($order, $profile);
            if (! $restored) {
                $this->queueCustomerAccessRestore($idpel, $expectedExpdate);
            }

            return $restored;
        } finally {
            try {
                Invoice::releaseNamedLock($lockName);
            } catch (\Throwable $e) {
                Log::warning("Gagal melepas customer access lock ({$idpel}): {$e->getMessage()}");
            }
        }
    }

    private function queueCustomerAccessRestore(string $idpel, string $expectedExpdate): void
    {
        // Status Isolir + expdate masa depan menjadi penanda retry aman untuk
        // AutoController::isolir(); pembayaran tetap sah/committed.
        Order::where('idpel', $idpel)
            ->where('status', 'Active')
            ->where('expdate', $expectedExpdate)
            ->update(['status' => 'Isolir']);
    }

    /**
     * Buka isolir hanya setelah transaksi pembayaran berhasil commit.
     */
    protected function restoreCustomerRouterAccess(Order $order, string $profile): bool
    {
        $router = Router::find($order->id_router);
        if (! $router) {
            Log::warning("Buka isolir manual ditunda, router tidak ditemukan ({$order->idpel}, router {$order->id_router})");

            return false;
        }

        $ros = $this->makeRouteros();

        try {
            if (! $ros->connect($router->ip, $router->username, legacy_decrypt($router->password))) {
                Log::warning("Buka isolir manual ditunda, Mikrotik tidak terhubung ({$order->idpel}, router {$router->id})");

                return false;
            }

            if ($order->mode === 'pppoe') {
                $secrets = $ros->comm('/ppp/secret/getall', [
                    '.proplist' => '.id',
                    '?name' => $order->pppoe_user,
                ]);
                $secretId = is_array($secrets) ? ($secrets[0]['.id'] ?? null) : null;
                if (! $secretId) {
                    throw new \RuntimeException('PPPoE secret tidak ditemukan.');
                }

                $this->ensureRouterCommandSucceeded($ros->comm('/ppp/secret/set', [
                    '.id' => $secretId,
                    'profile' => $profile,
                ]));

                $active = $ros->comm('/ppp/active/getall', [
                    '.proplist' => '.id',
                    '?name' => $order->pppoe_user,
                ]);
                foreach (is_array($active) ? $active : [] as $session) {
                    if (! empty($session['.id'])) {
                        $this->ensureRouterCommandSucceeded($ros->comm('/ppp/active/remove', ['.id' => $session['.id']]));
                    }
                }
            } elseif ($order->mode === 'hotspot') {
                $this->ensureRouterCommandSucceeded($ros->comm('/ip/hotspot/user/set', [
                    'numbers' => $order->pppoe_user,
                    'profile' => $profile,
                ]));

                $active = $ros->comm('/ip/hotspot/active/print', ['?user' => $order->pppoe_user]);
                foreach (is_array($active) ? $active : [] as $session) {
                    if (! empty($session['.id'])) {
                        $this->ensureRouterCommandSucceeded($ros->comm('/ip/hotspot/active/remove', ['.id' => $session['.id']]));
                    }
                }
            } else {
                throw new \RuntimeException("Mode pelanggan tidak didukung: {$order->mode}");
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning("Buka isolir manual gagal ({$order->idpel}, router {$router->id}): {$e->getMessage()}");

            return false;
        } finally {
            $ros->disconnect();
        }
    }

    protected function makeRouteros(): RouterosAPI
    {
        return new RouterosAPI;
    }

    private function ensureRouterCommandSucceeded(mixed $response): void
    {
        if (is_array($response) && (isset($response['!trap']) || isset($response['!fatal']))) {
            $message = $response['!trap'][0]['message'] ?? $response['!fatal'][0]['message'] ?? 'Perintah RouterOS gagal.';

            throw new \RuntimeException($message);
        }
    }

    private function generateUniqueInvoiceCode(): string
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            do {
                $sequence = (int) Invoice::where('code', 'like', 'INV%')
                    ->pluck('code')
                    ->map(function ($code) {
                        return preg_match('/^INV(\d+)[A-Za-z0-9]{5}$/', (string) $code, $matches)
                            ? (int) $matches[1]
                            : 0;
                    })
                    ->max() + 1;
                $code = 'INV'.sprintf('%04d', $sequence).randinv(5);
            } while (Invoice::where('code', $code)->exists());

            return $code;
        }

        do {
            $code = $this->generateInvoiceCode().randinv(5);
        } while (Invoice::where('code', $code)->exists());

        return $code;
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
            'customers' => Order::get(['idpel', 'nama', 'paket']),
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
        $invoice = Invoice::where('code', $id)
            ->where('account', 'user')
            ->where('status', 'Unpaid')
            ->first();
        if (! $invoice) {
            return redirect('admin/finance/invoice')->with('auth_errors', ['Invoice tidak ditemukan atau tidak dapat dibayar']);
        }

        return view('admin.finance.invoice.bayar', [
            'title' => 'Payment Invoice #'.$id,
            'category' => PaymentCat::where('status', '1')->get(),
            'history' => collect([$invoice]),
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

        if (! Invoice::acquirePaymentLock($invoiceCode)) {
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
            if ($invoice->hasActiveGatewayTransaction()) {
                return redirect('admin/finance/invoice/bayar/'.$invoiceCode)->with('auth_errors', ['Transaksi pembayaran invoice #'.$invoiceCode.' masih aktif. Tunggu sampai expired sebelum membuat transaksi baru.']);
            }

            $order = Order::where('idpel', $invoice->idpel)->first();
            $nama = $invoice->nama;
            $email = $order->email ?? '';
            $nomor = $order->nomor ?? '';
            $package = $invoice->package;
            $price = (int) $invoice->price; // Ambil nominal dari DB, jangan percaya hidden form.

            $randangka = rand(1, 999);
            $total = $price + $randangka;
            $deadline = CarbonImmutable::now(config('app.timezone', 'Asia/Jakarta'))->addDay();
            $expired = $deadline->format('d-m-Y H:i:s');

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
                'expired_time' => $deadline->getTimestamp(), // deadline sama dengan exppay lokal
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
            try {
                Invoice::releasePaymentLock($invoiceCode);
            } catch (\Throwable $e) {
                Log::warning("Gagal melepas admin Tripay payment lock #{$invoiceCode}: {$e->getMessage()}");
            }
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

    public function ambilDataCashFlows(Request $request, $bulan = null, $tahun = null)
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

    public function ambilDataStatistik(Request $request, $bulan = null, $tahun = null)
    {
        return $this->ambilDataInvoice($request, $bulan, $tahun);
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

    public function ambilDataInvoice(Request $request, $bulan = null, $tahun = null)
    {
        if (! $request->boolean('show_data')) {
            return response()->json([
                'getAllCredit' => 0,
                'getInvoicePaid' => 0,
                'getInvoiceUnpaid' => 0,
            ]);
        }

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
        $yearExpression = DB::connection()->getDriverName() === 'sqlite'
            ? "CAST(strftime('%Y', date) AS INTEGER)"
            : 'YEAR(date)';

        $years = Invoice::selectRaw($yearExpression.' AS tahun')
            ->whereRaw($yearExpression.' > 0')
            ->groupByRaw($yearExpression)
            ->orderByRaw($yearExpression.' ASC')
            ->get();

        if (! $years->contains(fn ($row) => (int) $row->tahun === 2026)) {
            $years->push((object) ['tahun' => 2026]);
        }

        return $years->sortBy('tahun')->values();
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

    private function sendPaidEmail($code, $idpel, $pesanemail, $logo, $titletext, $toEmail): void
    {
        if (trim((string) $toEmail) === '') {
            return;
        }

        try {
            $apiInstance = $this->brevoApiInstance($key, $name, $email);
            if (trim((string) $key) === '' || trim((string) $email) === '') {
                Log::warning("Email terbayar invoice #{$code} ({$idpel}) tidak dikirim: konfigurasi SMTP belum lengkap.");

                return;
            }

            $sendSmtpEmail = $this->buildBrevoEmail(
                'Tagihan Internet '.$titletext.' Telah Terbayar - #'.$code.' ',
                $pesanemail,
                $logo,
                $titletext,
                $name,
                $email,
                $toEmail
            );
            $apiInstance->sendTransacEmail($sendSmtpEmail);
        } catch (\Throwable $e) {
            Log::warning("Gagal kirim email terbayar invoice #{$code} ({$idpel}): {$e->getMessage()}");
        }
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
