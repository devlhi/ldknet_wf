<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Gangguan &amp; SLA — {{ $periodeLabel }}</title>
    @php use App\Models\GangguanReport; @endphp
    <style>
        * { box-sizing: border-box; }
        body { font-family: "Segoe UI", Arial, sans-serif; color: #222; margin: 0; padding: 24px; font-size: 12px; }
        .sheet { max-width: 900px; margin: 0 auto; }
        .toolbar { position: sticky; top: 0; background: #fff; padding: 10px 0; margin-bottom: 16px; border-bottom: 1px solid #eee; display: flex; gap: 8px; justify-content: flex-end; }
        .btn { border: 0; border-radius: 5px; padding: 8px 16px; font-size: 13px; cursor: pointer; text-decoration: none; color: #fff; }
        .btn-print { background: #dc3545; }
        .btn-back { background: #6c757d; }
        .letterhead { text-align: center; border-bottom: 2px solid #333; padding-bottom: 12px; margin-bottom: 16px; }
        .letterhead .company { font-size: 20px; font-weight: 700; letter-spacing: .5px; }
        .letterhead .doc-title { font-size: 15px; margin-top: 4px; }
        .letterhead .period { color: #555; margin-top: 2px; }
        .meta-line { display: flex; justify-content: space-between; color: #666; font-size: 11px; margin-bottom: 16px; }
        .cards { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 18px; }
        .card { flex: 1 1 120px; border: 1px solid #e3e3e3; border-radius: 6px; padding: 10px; text-align: center; }
        .card .num { font-size: 20px; font-weight: 700; }
        .card .lbl { color: #666; font-size: 11px; }
        .num.baru { color: #dc3545; } .num.proses { color: #f0a020; } .num.selesai { color: #22a06b; }
        h3.section { font-size: 13px; margin: 18px 0 8px; border-left: 4px solid #556ee6; padding-left: 8px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        th, td { border: 1px solid #ccc; padding: 5px 7px; text-align: left; vertical-align: top; }
        th { background: #f4f5f7; font-size: 11px; text-transform: uppercase; letter-spacing: .3px; }
        td.c, th.c { text-align: center; }
        .badge { display: inline-block; padding: 1px 6px; border-radius: 4px; font-size: 10px; color: #fff; }
        .b-baru { background: #dc3545; } .b-diproses { background: #f0a020; } .b-selesai { background: #22a06b; }
        .muted { color: #777; }
        .foot { margin-top: 24px; font-size: 10px; color: #888; text-align: center; border-top: 1px solid #eee; padding-top: 8px; }
        @media print {
            body { padding: 0; }
            .toolbar { display: none; }
            .card, th, td { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="sheet">
        <div class="toolbar">
            <button class="btn btn-print" onclick="window.print()">🖨 Cetak / Simpan PDF</button>
            <a class="btn btn-back" href="{{ url('admin/gangguan') }}">Kembali</a>
        </div>

        <div class="letterhead">
            <div class="company">{{ $namaPerusahaan }}</div>
            <div class="doc-title">Laporan Gangguan &amp; SLA Penanganan</div>
            <div class="period">Periode: <strong>{{ $periodeLabel }}</strong></div>
        </div>

        <div class="meta-line">
            <span>
                Rentang: {{ $start->translatedFormat('d M Y H:i') }} – {{ $end->translatedFormat('d M Y H:i') }}
                @if ($statusFilter) · Status: {{ ucfirst($statusFilter) }} @endif
                @if ($kategoriFilter) · Kategori: {{ GangguanReport::kategoriLabel($kategoriFilter) }} @endif
            </span>
            <span>Dicetak: {{ now()->timezone('Asia/Jakarta')->translatedFormat('d M Y H:i') }} WIB</span>
        </div>

        {{-- Ringkasan --}}
        <div class="cards">
            <div class="card"><div class="num">{{ number_format($totalPeriode) }}</div><div class="lbl">Total Laporan</div></div>
            <div class="card"><div class="num baru">{{ number_format($rekapStatus['baru'] ?? 0) }}</div><div class="lbl">Baru</div></div>
            <div class="card"><div class="num proses">{{ number_format($rekapStatus['diproses'] ?? 0) }}</div><div class="lbl">Diproses</div></div>
            <div class="card"><div class="num selesai">{{ number_format($rekapStatus['selesai'] ?? 0) }}</div><div class="lbl">Selesai</div></div>
            <div class="card"><div class="num">{{ GangguanReport::humanDuration($avgRespon) }}</div><div class="lbl">Avg Respons</div></div>
            <div class="card"><div class="num">{{ GangguanReport::humanDuration($avgSelesai) }}</div><div class="lbl">Avg Penyelesaian</div></div>
        </div>

        {{-- Rekap kategori --}}
        <h3 class="section">Rekap per Kategori Gangguan</h3>
        <table>
            <thead><tr><th>Kategori</th><th class="c">Jumlah</th><th class="c">Persentase</th></tr></thead>
            <tbody>
                @forelse ($rekapKategori as $rk)
                    <tr>
                        <td>{{ GangguanReport::kategoriLabel($rk->kategori) }}</td>
                        <td class="c">{{ $rk->total }}</td>
                        <td class="c">{{ $totalPeriode > 0 ? round($rk->total / $totalPeriode * 100) : 0 }}%</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="muted">Tidak ada laporan pada periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>

        {{-- Riwayat SLA per sub-periode --}}
        @if (!empty($breakdown))
            <h3 class="section">Riwayat SLA per {{ $periode === 'harian' ? 'Jam' : ($periode === 'tahunan' ? 'Bulan' : 'Hari') }}</h3>
            <table>
                <thead><tr>
                    <th>{{ $periode === 'harian' ? 'Jam' : ($periode === 'tahunan' ? 'Bulan' : 'Tanggal') }}</th>
                    <th class="c">Laporan</th><th class="c">Selesai</th><th class="c">Avg Respons</th><th class="c">Avg Penyelesaian</th>
                </tr></thead>
                <tbody>
                    @foreach ($breakdown as $b)
                        <tr>
                            <td>{{ $b['label'] }}</td>
                            <td class="c">{{ $b['total'] }}</td>
                            <td class="c">{{ $b['selesai'] }}</td>
                            <td class="c">{{ GangguanReport::humanDuration($b['avg_respon']) }}</td>
                            <td class="c">{{ GangguanReport::humanDuration($b['avg_selesai']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        {{-- Riwayat laporan detail --}}
        <h3 class="section">Riwayat Laporan
            @if ($totalList > $maxRows)<span class="muted" style="font-weight:400">(menampilkan {{ number_format($maxRows) }} dari {{ number_format($totalList) }} laporan terbaru)</span>@endif
        </h3>
        <table>
            <thead><tr>
                <th style="width:110px">Waktu</th><th>Pelanggan</th><th>ODP</th><th>Kategori</th><th>Pesan</th><th class="c">Status</th><th>Respons</th>
            </tr></thead>
            <tbody>
                @forelse ($list as $r)
                    <tr>
                        <td>{{ $r->created_at->timezone('Asia/Jakarta')->format('d/m/y H:i') }}</td>
                        <td>{{ $r->from_name ?: '-' }}<br><span class="muted">{{ $r->idpel ? $r->idpel.' · ' : '' }}{{ $r->from_number }}</span></td>
                        <td>{{ $r->nama_odp ?: '-' }}</td>
                        <td>{{ GangguanReport::kategoriLabel($r->kategori) }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($r->pesan, 90) }}</td>
                        <td class="c"><span class="badge b-{{ $r->status }}">{{ $r->status }}</span></td>
                        <td>
                            @if ($r->responded_at)
                                {{ GangguanReport::humanDuration($r->created_at->diffInMinutes($r->responded_at)) }}
                                @if ($r->resolved_at)<br><span class="muted">selesai {{ GangguanReport::humanDuration($r->created_at->diffInMinutes($r->resolved_at)) }}</span>@endif
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted">Tidak ada laporan pada periode/filter ini.</td></tr>
                @endforelse
            </tbody>
        </table>

        <div class="foot">Dokumen ini dihasilkan otomatis oleh sistem {{ $namaPerusahaan }} · Laporan Gangguan &amp; SLA</div>
    </div>
</body>
</html>
