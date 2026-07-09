<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Psb;
use App\Models\Report;
use App\Models\Website;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    private function websiteData(): array
    {
        $website = Website::first();

        return [
            'titletext' => $website->title ?? 'LandakNet',
            'logo' => $website->logo ?? '',
        ];
    }

    public function index()
    {
        $credit = Invoice::whereMonth('expdate', now()->month)
            ->whereYear('expdate', now()->year)
            ->where('status', 'Paid')
            ->where('account', 'user')
            ->sum('received');

        $debit = Report::whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->where('jenis_kategori', 'Pengeluaran')
            ->sum('balance');

        $totalpsb = Psb::whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->count();

        $months = collect(range(1, 12));
        $monthLabels = $months->map(fn ($month) => bulan($month))->values();

        $incomeByMonth = Invoice::selectRaw('MONTH(expdate) as month, SUM(received) as total')
            ->whereYear('expdate', now()->year)
            ->where('status', 'Paid')
            ->where('account', 'user')
            ->groupBy(DB::raw('MONTH(expdate)'))
            ->pluck('total', 'month');

        $expenseByMonth = Report::selectRaw('MONTH(date) as month, SUM(balance) as total')
            ->whereYear('date', now()->year)
            ->where('jenis_kategori', 'Pengeluaran')
            ->groupBy(DB::raw('MONTH(date)'))
            ->pluck('total', 'month');

        $newCustomersByMonth = Psb::selectRaw('MONTH(date) as month, COUNT(*) as total')
            ->whereYear('date', now()->year)
            ->groupBy(DB::raw('MONTH(date)'))
            ->pluck('total', 'month');

        $invoiceStatus = Invoice::selectRaw('status, COUNT(*) as total')
            ->whereMonth('expdate', now()->month)
            ->whereYear('expdate', now()->year)
            ->where('account', 'user')
            ->groupBy('status')
            ->pluck('total', 'status');

        $chartData = [
            'labels' => $monthLabels,
            'income' => $months->map(fn ($month) => (int) ($incomeByMonth[$month] ?? 0))->values(),
            'expense' => $months->map(fn ($month) => (int) ($expenseByMonth[$month] ?? 0))->values(),
            'newCustomers' => $months->map(fn ($month) => (int) ($newCustomersByMonth[$month] ?? 0))->values(),
            'invoiceStatusLabels' => ['Paid', 'Unpaid', 'Pending', 'Success', 'Error'],
            'invoiceStatus' => collect(['Paid', 'Unpaid', 'Pending', 'Success', 'Error'])
                ->map(fn ($status) => (int) ($invoiceStatus[$status] ?? 0))
                ->values(),
        ];

        return view('admin.home', [
            'title' => 'Dashboard',
            'credit' => $credit,
            'debit' => $debit,
            'totalpsb' => $totalpsb,
            'chartData' => $chartData,
        ] + $this->websiteData());
    }

    public function transactions()
    {
        $gettransaction = Invoice::where('status', 'Paid')
            ->where('account', 'user')
            ->orderByDesc('last_update')
            ->limit(10)
            ->get();

        return response()->json([
            'data' => $gettransaction->map(fn ($row, $i) => [
                'no' => $i + 1,
                'code' => $row->code,
                'idpel' => $row->idpel,
                'nama' => $row->nama,
                'last_update' => tanggal($row->last_update),
                'received' => 'Rp '.number_format($row->received),
                'method' => $row->method,
                'update_by' => $row->update_by,
            ]),
        ]);
    }
}
