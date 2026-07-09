<?php

namespace App\Http\Controllers\Server;

use App\Http\Controllers\Controller;
use App\Models\Website;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class VoucherController extends Controller
{
    private function websiteData(): array
    {
        $website = Website::first();

        return [
            'titletext' => $website->title ?? 'LandakNet',
            'logo' => $website->logo ?? '',
        ];
    }

    /**
     * Tabel voucher (logs_voucher, orders_voucher, template_message_voucher)
     * tidak ada di semua instalasi DB legacy — CI4 pun error di halaman ini
     * kalau tabelnya belum dibuat. Skema shared tidak boleh diubah dari
     * Laravel, jadi halaman ditampilkan kosong + warning.
     */
    private function voucherTablesReady(): bool
    {
        static $ready = null;

        return $ready ??= Schema::hasTable('logs_voucher');
    }

    private function missingTablesWarning(): void
    {
        // now() (bukan flash) — pesan hanya untuk request ini, supaya tidak
        // "bocor" ke halaman berikutnya yang juga merender auth_errors.
        session()->now('auth_errors', ['Tabel data voucher belum ada di database ini — hubungi developer untuk mengimpor tabel logs_voucher/orders_voucher/template_message_voucher dari instalasi lama.']);
    }

    public function home()
    {
        $daysInMonth = (int) date('t');

        if (! $this->voucherTablesReady()) {
            $this->missingTablesWarning();

            return view('admin.voucher.home', [
                'title' => 'Dashboard Voucher',
                'month' => 0, 'vcrmonth' => 0, 'today' => 0, 'vcrtoday' => 0,
                'yesterday' => 0, 'vcrystrdy' => 0,
                'reportByDay' => collect(), 'daysInMonth' => $daysInMonth,
            ] + $this->websiteData());
        }

        return view('admin.voucher.home', [
            'title' => 'Dashboard Voucher',
            'month' => $this->reportMonth(),
            'vcrmonth' => $this->vcrMonth(),
            'today' => $this->reportToday(),
            'vcrtoday' => $this->vcrToday(),
            'yesterday' => $this->reportYesterday(),
            'vcrystrdy' => $this->vcrYesterday(),
            'reportByDay' => $this->reportByDayThisMonth(),
            'daysInMonth' => $daysInMonth,
        ] + $this->websiteData());
    }

    public function report()
    {
        if (! $this->voucherTablesReady()) {
            $this->missingTablesWarning();
        }

        return view('admin.voucher.report', [
            'title' => 'Laporan',
            'tahun' => $this->voucherTablesReady() ? $this->getTahunMasuk() : collect(),
            'orders' => false,
            'filterUrl' => url('server/voucher/report/filter'),
        ] + $this->websiteData());
    }

    public function reportFilter(Request $request)
    {
        $bulan = (int) $request->post('bulan');
        $tahun = (int) $request->post('tahun');
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);
        $filteredData = $this->getFilteredData($bulan, $tahun);
        $chartData = $this->filterReportByMonthAndYear($bulan, $tahun);

        if (! empty($filteredData)) {
            return response()->json([
                'data' => $filteredData,
                'chartData' => $chartData,
                'daysInMonth' => $daysInMonth,
                'month' => $bulan,
                'year' => $tahun,
            ]);
        }

        return response()->json([
            'data' => 'No data found',
            'daysInMonth' => $daysInMonth,
            'month' => $bulan,
            'year' => $tahun,
        ]);
    }

    public function reportOrders()
    {
        $hasTable = Schema::hasTable('orders_voucher');
        if (! $hasTable) {
            $this->missingTablesWarning();
        }

        return view('admin.voucher.report', [
            'title' => 'Laporan',
            'tahun' => $hasTable ? $this->getTahunMasukVoc() : collect(),
            'orders' => true,
            'filterUrl' => url('server/voucher/report/orders/filter'),
        ] + $this->websiteData());
    }

    public function reportOrdersFilter(Request $request)
    {
        $bulan = (int) $request->post('bulan');
        $tahun = (int) $request->post('tahun');
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);
        $filteredData = $this->getFilteredDataOrders($bulan, $tahun);
        $chartData = $this->filterReportOrdersByMonthAndYear($bulan, $tahun);

        if (! empty($filteredData)) {
            return response()->json([
                'data' => $filteredData,
                'chartData' => $chartData,
                'daysInMonth' => $daysInMonth,
                'month' => $bulan,
                'year' => $tahun,
            ]);
        }

        return response()->json([
            'data' => 'No data found',
            'daysInMonth' => $daysInMonth,
            'month' => $bulan,
            'year' => $tahun,
        ]);
    }

    public function users()
    {
        return view('admin.voucher.users', [
            'title' => 'Users Manager',
            'account' => DB::table('users')->where('level', 'member')->orWhere('level', 'reseller')->get(),
            'password' => Str::random(5),
        ] + $this->websiteData());
    }

    public function templateMessage()
    {
        $hasTable = Schema::hasTable('template_message_voucher');
        if (! $hasTable) {
            $this->missingTablesWarning();
        }

        return view('admin.voucher.template', [
            'title' => 'Template Message',
            'content' => $hasTable ? DB::table('template_message_voucher')->get() : collect(),
        ] + $this->websiteData());
    }

    public function updateTemplateMessage(Request $request)
    {
        DB::table('template_message_voucher')->where('id', $request->post('id'))->update([
            'notif_pembelian' => $request->post('notif_pembelian'),
            'notif_pembayaran' => $request->post('notif_pembayaran'),
        ]);

        return redirect(url('server/voucher/template/message'))->with('success', ['Data berhasil diupdate']);
    }

    private function reportMonth()
    {
        return DB::table('logs_voucher')
            ->whereMonth('date', date('m'))
            ->whereYear('date', date('Y'))
            ->sum('harga');
    }

    private function reportToday()
    {
        return DB::table('logs_voucher')->whereDate('date', date('Y-m-d'))->sum('harga');
    }

    private function reportYesterday()
    {
        return DB::table('logs_voucher')->whereDate('date', date('Y-m-d', strtotime('-1 day')))->sum('harga');
    }

    private function vcrToday()
    {
        return DB::table('logs_voucher')->whereDate('date', date('Y-m-d'))->count();
    }

    private function vcrYesterday()
    {
        return DB::table('logs_voucher')->whereDate('date', date('Y-m-d', strtotime('-1 day')))->count();
    }

    private function vcrMonth()
    {
        return DB::table('logs_voucher')
            ->whereMonth('date', date('m'))
            ->whereYear('date', date('Y'))
            ->count();
    }

    private function reportByDayThisMonth()
    {
        return DB::table('logs_voucher')
            ->selectRaw('DATE(logs_voucher.date) as date, SUM(harga) as total_harga, COUNT(*) as total_voucher')
            ->whereMonth('logs_voucher.date', date('m'))
            ->whereYear('logs_voucher.date', date('Y'))
            ->groupByRaw('DATE(logs_voucher.date)')
            ->orderByRaw('DATE(logs_voucher.date) ASC')
            ->get();
    }

    private function getTahunMasuk()
    {
        return DB::table('logs_voucher')
            ->selectRaw('YEAR(date) AS tahun')
            ->groupByRaw('YEAR(date)')
            ->orderByRaw('YEAR(date) ASC')
            ->get();
    }

    private function getTahunMasukVoc()
    {
        return DB::table('orders_voucher')
            ->selectRaw('YEAR(date) AS tahun')
            ->groupByRaw('YEAR(date)')
            ->orderByRaw('YEAR(date) ASC')
            ->get();
    }

    private function getFilteredData($bulan, $tahun)
    {
        return DB::table('logs_voucher')
            ->when($bulan !== 0 && $tahun !== 0, function ($query) use ($bulan, $tahun) {
                $query->whereMonth('logs_voucher.date', $bulan)
                    ->whereYear('logs_voucher.date', $tahun);
            })
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    private function getFilteredDataOrders($bulan, $tahun)
    {
        return DB::table('orders_voucher')
            ->when($bulan !== 0 && $tahun !== 0, function ($query) use ($bulan, $tahun) {
                $query->whereMonth('orders_voucher.date', $bulan)
                    ->whereYear('orders_voucher.date', $tahun)
                    ->where('status', 'Paid');
            })
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    private function filterReportByMonthAndYear($bulan, $tahun)
    {
        return DB::table('logs_voucher')
            ->selectRaw('DATE(logs_voucher.date) as date, SUM(harga) as total_harga, COUNT(*) as total_voucher')
            ->whereMonth('logs_voucher.date', $bulan)
            ->whereYear('logs_voucher.date', $tahun)
            ->groupByRaw('DATE(logs_voucher.date)')
            ->orderByRaw('DATE(logs_voucher.date) ASC')
            ->get();
    }

    private function filterReportOrdersByMonthAndYear($bulan, $tahun)
    {
        return DB::table('orders_voucher')
            ->selectRaw('DATE(orders_voucher.date) as date, SUM(harga) as total_harga, COUNT(*) as total_voucher')
            ->whereMonth('orders_voucher.date', $bulan)
            ->whereYear('orders_voucher.date', $tahun)
            ->where('status', 'Paid')
            ->groupByRaw('DATE(orders_voucher.date)')
            ->orderByRaw('DATE(orders_voucher.date) ASC')
            ->get();
    }
}
