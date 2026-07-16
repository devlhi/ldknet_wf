<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Libraries\RouterosAPI;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Report;
use App\Models\Router;
use App\Models\Website;
use Illuminate\Http\Request;
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

    public function index(Request $request)
    {
        $showData = $request->boolean('show_data');

        if (! $showData) {
            return view('admin.home', [
                'title' => 'Dashboard',
                'credit' => 0,
                'debit' => 0,
                'totalpsb' => 0,
                'tunggakanJumlah' => 0,
                'tunggakanRupiah' => 0,
                'totalIsolir' => 0,
                'chartData' => [
                    'labels' => collect(range(1, 12))->map(fn ($month) => bulan($month))->values(),
                    'income' => collect(array_fill(0, 12, 0)),
                    'expense' => collect(array_fill(0, 12, 0)),
                    'newCustomers' => collect(array_fill(0, 12, 0)),
                    'invoiceStatusLabels' => ['Paid', 'Unpaid', 'Pending', 'Success', 'Error'],
                    'invoiceStatus' => collect(array_fill(0, 5, 0)),
                ],
                'routers' => collect(),
                'showData' => false,
            ] + $this->websiteData());
        }

        $credit = Invoice::whereMonth('expdate', now()->month)
            ->whereYear('expdate', now()->year)
            ->where('status', 'Paid')
            ->where('account', 'user')
            ->sum('received');

        $debit = Report::whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->where('jenis_kategori', 'Pengeluaran')
            ->sum('balance');

        // Pelanggan baru = tabel orders (tabel psb legacy tidak terisi), konsisten
        // dgn Finance report getFilteredDataNewCustomers().
        $totalpsb = Order::whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->count();

        // Ringkasan penagihan: total tunggakan (invoice Unpaid pelanggan) & jumlah
        // pelanggan yang sedang terisolir — surface data yang sudah ada agar admin
        // langsung lihat prioritas penagihan di dashboard.
        $tunggakan = Invoice::where('status', 'Unpaid')
            ->where('account', 'user')
            ->selectRaw('COUNT(*) as jml, COALESCE(SUM(price), 0) as total')
            ->first();
        $tunggakanJumlah = (int) ($tunggakan->jml ?? 0);
        $tunggakanRupiah = (float) ($tunggakan->total ?? 0);
        $totalIsolir = Order::where('status', 'Isolir')->count();

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

        $newCustomersByMonth = Order::selectRaw('MONTH(date) as month, COUNT(*) as total')
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
            'tunggakanJumlah' => $tunggakanJumlah,
            'tunggakanRupiah' => $tunggakanRupiah,
            'totalIsolir' => $totalIsolir,
            'chartData' => $chartData,
            'routers' => Router::all(['id', 'nama', 'ip']),
            'showData' => true,
        ] + $this->websiteData());
    }

    public function transactions(Request $request)
    {
        if (! $request->boolean('show_data')) {
            return response()->json(['data' => []]);
        }

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

    /**
     * Statistik ringkas tiap Mikrotik untuk dashboard.
     * Dipanggil async (AJAX) supaya koneksi RouterOS yang lambat tidak
     * memblokir load halaman dashboard.
     */
    public function mikrotikStats(Request $request)
    {
        if (! $request->boolean('show_data')) {
            return response()->json([
                'data' => [],
                'summary' => ['online' => 0, 'total' => 0, 'offline' => 0, 'pppActive' => 0, 'hotspotActive' => 0],
            ]);
        }

        $routers = Router::all();

        $stats = $routers->map(function ($router) {
            [$host, $port] = $this->routerEndpoint((string) $router->ip);

            $base = [
                'id' => $router->id,
                'nama' => $router->nama,
                'ip' => $router->ip,
                'host' => $host,
                'port' => $port,
                'online' => false,
                'pppActive' => 0,
                'pppTotal' => 0,
                'hotspotActive' => 0,
            ];

            $ros = new RouterosAPI;
            // Fail cepat: 1 percobaan, timeout 3 detik. Jangan sampai 1 router
            // mati bikin request menggantung berpuluh detik.
            $ros->attempts = 1;
            $ros->timeout = 3;
            $ros->delay = 0;
            $ros->port = $port;

            try {
                if (! $ros->connect($host, $router->username, legacy_decrypt($router->password))) {
                    return $base;
                }

                $resource = $this->rosFirstRow($ros->comm('/system/resource/print'));
                $routerboard = $this->rosFirstRow($ros->comm('/system/routerboard/print'));

                $pppSecret = $this->rosRows($ros->comm('/ppp/secret/print', ['.proplist' => '.id']));
                $pppActive = $this->rosRows($ros->comm('/ppp/active/print', ['.proplist' => '.id']));
                $hotspotActive = $this->rosRows($ros->comm('/ip/hotspot/active/print', ['.proplist' => '.id']));

                $totalMem = (int) ($resource['total-memory'] ?? 0);
                $freeMem = (int) ($resource['free-memory'] ?? 0);
                $memUsedPercent = $totalMem > 0
                    ? round((($totalMem - $freeMem) / $totalMem) * 100)
                    : 0;

                $ros->disconnect();

                return array_merge($base, [
                    'online' => true,
                    'cpu' => (int) ($resource['cpu-load'] ?? 0),
                    'memUsedPercent' => $memUsedPercent,
                    'memUsed' => $this->formatBytes($totalMem - $freeMem),
                    'memTotal' => $this->formatBytes($totalMem),
                    'uptime' => $resource['uptime'] ?? '-',
                    'version' => $resource['version'] ?? '-',
                    'model' => $routerboard['model'] ?? ($resource['board-name'] ?? '-'),
                    'pppActive' => count($pppActive),
                    'pppTotal' => count($pppSecret),
                    'hotspotActive' => count($hotspotActive),
                ]);
            } catch (\Throwable $e) {
                if ($ros->connected) {
                    $ros->disconnect();
                }

                return $base;
            }
        });

        return response()->json([
            'summary' => [
                'total' => $stats->count(),
                'online' => $stats->where('online', true)->count(),
                'offline' => $stats->where('online', false)->count(),
                'pppActive' => $stats->sum(fn ($s) => $s['pppActive'] ?? 0),
                'hotspotActive' => $stats->sum(fn ($s) => $s['hotspotActive'] ?? 0),
            ],
            'data' => $stats->values(),
        ]);
    }

    private function rosRows(mixed $response): array
    {
        if (! is_array($response) || isset($response['!trap'])) {
            return [];
        }

        if (! array_is_list($response)) {
            return [$response];
        }

        return array_values(array_filter($response, 'is_array'));
    }

    private function rosFirstRow(mixed $response): array
    {
        return $this->rosRows($response)[0] ?? [];
    }

    /**
     * Pisah "host:port" jadi [host, port]. Kolom ip di DB bisa berisi
     * "160.187.139.19" atau "160.187.139.19:1169". Default port API 8728.
     * IPv6 diabaikan (skema ini hanya menyimpan IPv4).
     */
    private function routerEndpoint(string $ip): array
    {
        $ip = trim($ip);

        if (str_contains($ip, ':')) {
            [$host, $port] = explode(':', $ip, 2);
            $port = (int) $port;

            return [trim($host), $port > 0 ? $port : 8728];
        }

        return [$ip, 8728];
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);

        return round($bytes / (1024 ** $power), 1).' '.$units[$power];
    }
}
