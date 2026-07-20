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

    public function home(Request $request)
    {
        $daysInMonth = (int) date('t');
        $tablesReady = $this->voucherTablesReady();

        if (! $tablesReady) {
            $this->missingTablesWarning();
        }

        $summary = [
            'month' => 0, 'vcrmonth' => 0,
            'today' => 0, 'vcrtoday' => 0,
            'yesterday' => 0, 'vcrystrdy' => 0,
            'reportByDay' => collect(),
        ];

        if ($tablesReady) {
            $todayStr = date('Y-m-d');
            $yesterdayStr = date('Y-m-d', strtotime('-1 day'));
            $monthStart = date('Y-m-01');
            $monthEnd = date('Y-m-t');

            // Optimasi: hitung semua summary voucher dalam satu query agregat.
            $rawSums = DB::table('logs_voucher')
                ->selectRaw('
                    SUM(CASE WHEN DATE(date) = ? THEN harga ELSE 0 END) as sum_today,
                    COUNT(CASE WHEN DATE(date) = ? THEN 1 END) as count_today,
                    SUM(CASE WHEN DATE(date) = ? THEN harga ELSE 0 END) as sum_yesterday,
                    COUNT(CASE WHEN DATE(date) = ? THEN 1 END) as count_yesterday,
                    SUM(CASE WHEN DATE(date) BETWEEN ? AND ? THEN harga ELSE 0 END) as sum_month,
                    COUNT(CASE WHEN DATE(date) BETWEEN ? AND ? THEN 1 END) as count_month
                ', [
                    $todayStr, $todayStr,
                    $yesterdayStr, $yesterdayStr,
                    $monthStart, $monthEnd,
                    $monthStart, $monthEnd,
                ])
                ->first();

            $summary = [
                'month' => $rawSums->sum_month ?? 0,
                'vcrmonth' => $rawSums->count_month ?? 0,
                'today' => $rawSums->sum_today ?? 0,
                'vcrtoday' => $rawSums->count_today ?? 0,
                'yesterday' => $rawSums->sum_yesterday ?? 0,
                'vcrystrdy' => $rawSums->count_yesterday ?? 0,
                'reportByDay' => $this->reportByDayThisMonth(),
            ];
        }

        return view('admin.voucher.home', [
            'title' => 'Dashboard Voucher',
            'month' => $summary['month'],
            'vcrmonth' => $summary['vcrmonth'],
            'today' => $summary['today'],
            'vcrtoday' => $summary['vcrtoday'],
            'yesterday' => $summary['yesterday'],
            'vcrystrdy' => $summary['vcrystrdy'],
            'reportByDay' => $summary['reportByDay'],
            'daysInMonth' => $daysInMonth,
        ] + $this->websiteData());
    }

    public function report(Request $request)
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

    public function reportOrders(Request $request)
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

    public function users(Request $request)
    {

        return view('admin.voucher.users', [
            'title' => 'Users Manager',
            'account' => DB::table('users')->where('level', 'member')->orWhere('level', 'reseller')->get(),
            'password' => Str::random(5),
        ] + $this->websiteData());
    }

    public function templateMessage(Request $request)
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
