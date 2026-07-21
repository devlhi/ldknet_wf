@extends('admin.layout')

@php use App\Models\GangguanReport; @endphp

@section('content')
<div class="page-content">
    <div class="container-fluid">
        @php
            $exportUrl = url('admin/gangguan/cetak').'?'.http_build_query(array_filter([
                'periode' => $periode, 'tanggal' => $tanggal, 'status' => $statusFilter, 'kategori' => $kategoriFilter,
            ], fn ($v) => $v !== null && $v !== ''));
        @endphp
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between flex-wrap">
                    <div>
                        <h4 class="mb-0">Laporan Gangguan</h4>
                        <p class="text-muted mb-0">Laporan otomatis terserap dari chat WhatsApp pelanggan (Meta &amp; gateway lama).</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ $exportUrl }}" target="_blank" rel="noopener" class="btn btn-danger">Export PDF</a>
                        <a href="{{ url('admin/gangguan/pengaturan') }}" class="btn btn-light">Pengaturan</a>
                    </div>
                </div>
            </div>
        </div>

        @if (session('auth_errors'))
            <div class="alert alert-danger">@foreach (session('auth_errors') as $e)<p class="mb-0">{{ $e }}</p>@endforeach</div>
        @endif
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                @foreach (session('success') as $s){{ $s }}@endforeach
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Filter periode --}}
        <div class="card">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label mb-1">Periode</label>
                        <select name="periode" class="form-select">
                            @foreach (['harian' => 'Harian', 'mingguan' => 'Mingguan', 'bulanan' => 'Bulanan', 'tahunan' => 'Tahunan'] as $val => $lbl)
                                <option value="{{ $val }}" {{ $periode === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label mb-1">Tanggal acuan</label>
                        <input type="date" name="tanggal" class="form-control" value="{{ $tanggal }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label mb-1">Status</label>
                        <select name="status" class="form-select">
                            <option value="">Semua</option>
                            @foreach (GangguanReport::STATUSES as $st)
                                <option value="{{ $st }}" {{ $statusFilter === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label mb-1">Kategori</label>
                        <select name="kategori" class="form-select">
                            <option value="">Semua</option>
                            @foreach (['internet_mati', 'internet_lambat', 'tidak_bisa_akses', 'wifi', 'pembayaran', 'lainnya'] as $kat)
                                <option value="{{ $kat }}" {{ $kategoriFilter === $kat ? 'selected' : '' }}>{{ GangguanReport::kategoriLabel($kat) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-1">
                        <button class="btn btn-primary w-100">Terapkan Filter</button>
                        <a href="{{ url('admin/gangguan') }}" class="btn btn-light">Reset</a>
                    </div>
                    <div class="col-12"><small class="text-muted">Untuk periode Bulanan/Tahunan, sistem mengambil bulan/tahun dari <em>Tanggal acuan</em>. Sedang menampilkan: <strong>{{ $periodeLabel }}</strong>.</small></div>
                </form>
            </div>
        </div>

        {{-- Peringatan gangguan massal per ODP --}}
        @if ($massal->isNotEmpty())
            <div class="alert alert-danger border-0 shadow-sm">
                <h5 class="alert-heading mb-2">Kemungkinan Gangguan Massal Terdeteksi</h5>
                <p class="mb-2 text-muted">Beberapa ODP menerima banyak laporan dalam waktu berdekatan — kemungkinan gangguan jaringan area, bukan masalah per pelanggan.</p>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>ODP</th><th class="text-center">Laporan</th><th class="text-center">Pelanggan aktif</th><th class="text-end">Aksi</th></tr></thead>
                        <tbody>
                            @foreach ($massal as $m)
                                <tr>
                                    <td class="fw-semibold">{{ $m->nama_odp }}</td>
                                    <td class="text-center"><span class="badge bg-danger">{{ $m->total }} laporan</span></td>
                                    <td class="text-center">{{ $m->pelanggan_aktif }}</td>
                                    <td class="text-end text-nowrap">
                                        @if ($m->latitude && $m->longitude)
                                            <a href="https://www.google.com/maps?q={{ $m->latitude }},{{ $m->longitude }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary" title="Lihat lokasi ODP">Google Maps</a>
                                        @endif
                                        <a href="{{ url('admin/coverage/odp') }}" class="btn btn-sm btn-outline-primary" title="Peta ODP">Peta</a>
                                        <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#bc-{{ $loop->index }}">Info ke pelanggan</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @foreach ($massal as $m)
                <div class="modal fade" id="bc-{{ $loop->index }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <form method="POST" action="{{ url('admin/gangguan/broadcast-odp') }}">
                                @csrf
                                <input type="hidden" name="odp_id" value="{{ $m->odp_id }}">
                                <div class="modal-header">
                                    <h6 class="modal-title">Kirim info gangguan — {{ $m->nama_odp }}</h6>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body text-start">
                                    <p class="mb-2">Pesan pemberitahuan gangguan akan dikirim via WhatsApp ke <strong>{{ $m->pelanggan_aktif }} pelanggan aktif</strong> pada ODP <strong>{{ $m->nama_odp }}</strong>.</p>
                                    <p class="text-muted mb-0"><small>Teks pesan dapat diubah di menu Pengaturan. Pastikan gangguan memang sedang ditangani sebelum mengirim.</small></p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-danger">Kirim ke {{ $m->pelanggan_aktif }} pelanggan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif

        {{-- Rekap periode --}}
        <div class="row">
            <div class="col-md-3">
                <div class="card {{ $overdue > 0 ? 'border border-danger' : '' }}">
                    <div class="card-body text-center py-3">
                        <h3 class="mb-0">{{ number_format($totalPeriode) }}</h3>
                        <p class="text-muted mb-0">Total laporan ({{ $periodeLabel }})</p>
                        @if ($overdue > 0)
                            <span class="badge bg-danger mt-1">{{ $overdue }} lewat {{ $slaHours }} jam belum ditangani</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card"><div class="card-body text-center py-3">
                    <h3 class="mb-0 text-danger">{{ number_format($rekapStatus['baru'] ?? 0) }}</h3>
                    <p class="text-muted mb-0">Baru (belum ditangani)</p>
                </div></div>
            </div>
            <div class="col-md-3">
                <div class="card"><div class="card-body text-center py-3">
                    <h3 class="mb-0 text-warning">{{ number_format($rekapStatus['diproses'] ?? 0) }}</h3>
                    <p class="text-muted mb-0">Diproses</p>
                </div></div>
            </div>
            <div class="col-md-3">
                <div class="card"><div class="card-body text-center py-3">
                    <h3 class="mb-0 text-success">{{ number_format($rekapStatus['selesai'] ?? 0) }}</h3>
                    <p class="text-muted mb-0">Selesai</p>
                </div></div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <div class="border rounded p-2 h-100">
                            <p class="text-muted mb-0"><small>Rata-rata waktu respons (periode ini)</small></p>
                            <h5 class="mb-0">{{ GangguanReport::humanDuration($avgRespon) }}</h5>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-2 h-100">
                            <p class="text-muted mb-0"><small>Rata-rata waktu penyelesaian (periode ini)</small></p>
                            <h5 class="mb-0">{{ GangguanReport::humanDuration($avgSelesai) }}</h5>
                        </div>
                    </div>
                </div>
                <h5 class="card-title mb-3">Gangguan Paling Sering Dilaporkan
                    <small class="text-muted">— {{ $periodeLabel }}</small>
                </h5>
                @if ($rekapKategori->isEmpty())
                    <p class="text-muted mb-0">Belum ada laporan pada periode ini.</p>
                @else
                    @foreach ($rekapKategori as $rk)
                        @php $pct = $totalPeriode > 0 ? round($rk->total / $totalPeriode * 100) : 0; @endphp
                        <div class="mb-2">
                            <div class="d-flex justify-content-between mb-1">
                                <span>{{ GangguanReport::kategoriLabel($rk->kategori) }}</span>
                                <span class="text-muted">{{ $rk->total }} laporan ({{ $pct }}%)</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-primary" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>

        {{-- Riwayat SLA per sub-periode --}}
        @if (!empty($breakdown))
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Riwayat SLA
                        <small class="text-muted">— per {{ $periode === 'harian' ? 'jam' : ($periode === 'tahunan' ? 'bulan' : 'hari') }}</small>
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ $periode === 'harian' ? 'Jam' : ($periode === 'tahunan' ? 'Bulan' : 'Tanggal') }}</th>
                                    <th class="text-center">Laporan</th>
                                    <th class="text-center">Selesai</th>
                                    <th class="text-center">Avg Respons</th>
                                    <th class="text-center">Avg Penyelesaian</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($breakdown as $b)
                                    <tr>
                                        <td>{{ $b['label'] }}</td>
                                        <td class="text-center">{{ $b['total'] }}</td>
                                        <td class="text-center">{{ $b['selesai'] }}</td>
                                        <td class="text-center">{{ GangguanReport::humanDuration($b['avg_respon']) }}</td>
                                        <td class="text-center">{{ GangguanReport::humanDuration($b['avg_selesai']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif

        {{-- Daftar / riwayat laporan --}}
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between flex-wrap mb-3 gap-2">
                    <h5 class="card-title mb-0">Riwayat Laporan <small class="text-muted">({{ $periodeLabel }})</small></h5>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" form="bulk-close-form" id="bulk-close-btn" class="btn btn-sm btn-success" disabled>Tutup Terpilih (<span id="bulk-close-count">0</span>)</button>
                        @if ($openFilteredCount > 0)
                            <button type="submit" form="bulk-close-all-form" class="btn btn-sm btn-outline-success">Tutup Semua Terbuka ({{ $openFilteredCount }})</button>
                        @endif
                    </div>
                </div>

                {{-- Input checkbox menggunakan atribut form agar tidak bersarang dengan form aksi per-baris. --}}
                <form method="POST" action="{{ url('admin/gangguan/bulk-close') }}" id="bulk-close-form" class="swal-confirm d-none"
                    data-text="Tutup semua laporan terpilih? Status akan menjadi Selesai."
                    data-confirm="Ya, Tutup" data-icon="question">
                    @csrf
                    <input type="hidden" name="periode" value="{{ $periode }}">
                    <input type="hidden" name="tanggal" value="{{ $tanggal }}">
                    <input type="hidden" name="f_status" value="{{ $statusFilter }}">
                    <input type="hidden" name="f_kategori" value="{{ $kategoriFilter }}">
                </form>

                @if ($openFilteredCount > 0)
                    <form method="POST" action="{{ url('admin/gangguan/bulk-close') }}" id="bulk-close-all-form" class="swal-confirm d-none"
                        data-text="Tutup semua {{ $openFilteredCount }} laporan terbuka dalam periode dan filter aktif?"
                        data-confirm="Ya, Tutup Semua" data-icon="warning">
                        @csrf
                        <input type="hidden" name="close_all" value="1">
                        <input type="hidden" name="periode" value="{{ $periode }}">
                        <input type="hidden" name="tanggal" value="{{ $tanggal }}">
                        <input type="hidden" name="f_status" value="{{ $statusFilter }}">
                        <input type="hidden" name="f_kategori" value="{{ $kategoriFilter }}">
                    </form>
                @endif

                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 1%;"><input type="checkbox" id="gg-check-all" title="Pilih semua di halaman ini"></th>
                                <th>Waktu</th>
                                <th>Pelanggan</th>
                                <th>Kategori</th>
                                <th>Pesan</th>
                                <th>Sumber</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($list as $r)
                                @php $isOverdue = $r->status === 'baru' && $r->responded_at === null && $r->created_at->lt(now()->subHours($slaHours)); @endphp
                                <tr @class(['table-danger' => $isOverdue])>
                                    <td class="text-center">
                                        @if (in_array($r->status, ['baru', 'diproses'], true))
                                            <input type="checkbox" name="ids[]" value="{{ $r->id }}" class="gg-row-check" form="bulk-close-form" aria-label="Pilih laporan {{ $r->id }}">
                                        @endif
                                    </td>
                                    <td class="text-nowrap">
                                        <small>{{ $r->created_at->timezone('Asia/Jakarta')->format('d/m H:i') }}</small>
                                        @if ($isOverdue)<br><span class="badge bg-danger">telat</span>@endif
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $r->from_name ?: '-' }}</div>
                                        <small class="text-muted">{{ $r->idpel ? 'ID: '.$r->idpel.' · ' : '' }}{{ $r->from_number }}</small>
                                        @if ($r->nama_odp)<br><small class="text-muted">{{ $r->nama_odp }}</small>@endif
                                    </td>
                                    <td><span class="badge bg-soft-primary text-primary">{{ GangguanReport::kategoriLabel($r->kategori) }}</span></td>
                                    <td style="max-width: 280px;"><small>{{ \Illuminate\Support\Str::limit($r->pesan, 140) }}</small></td>
                                    <td><span class="badge bg-secondary text-uppercase">{{ $r->gateway }}</span></td>
                                    <td>
                                        <span class="badge bg-{{ $r->status === 'baru' ? 'danger' : ($r->status === 'diproses' ? 'warning' : 'success') }} text-capitalize">{{ $r->status }}</span>
                                        @if ($r->handler)
                                            <br><small class="text-muted">oleh {{ $r->handler->nama }}</small>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">
                                        @if ($r->gateway === 'meta')
                                            <a href="{{ url('admin/whatsapp/inbox?number='.$r->from_number) }}" class="btn btn-sm btn-success" title="Balas via Inbox (dengan signature)">Balas</a>
                                        @endif
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#status-{{ $r->id }}">Status</button>

                                        {{-- Tutup cepat (tandai selesai) / buka lagi bila sudah ditutup --}}
                                        <form method="POST" action="{{ url('admin/gangguan/status/'.$r->id) }}" id="status-quick-{{ $r->id }}" class="swal-confirm d-none"
                                            data-text="{{ $r->status === 'selesai' ? 'Buka kembali laporan ini? Status akan menjadi Diproses.' : 'Tutup laporan ini? Status akan menjadi Selesai.' }}"
                                            data-confirm="{{ $r->status === 'selesai' ? 'Ya, Buka' : 'Ya, Tutup' }}"
                                            data-icon="{{ $r->status === 'selesai' ? 'warning' : 'question' }}">
                                            @csrf
                                            <input type="hidden" name="status" value="{{ $r->status === 'selesai' ? 'diproses' : 'selesai' }}">
                                            <input type="hidden" name="periode" value="{{ $periode }}">
                                            <input type="hidden" name="tanggal" value="{{ $tanggal }}">
                                            <input type="hidden" name="f_status" value="{{ $statusFilter }}">
                                            <input type="hidden" name="f_kategori" value="{{ $kategoriFilter }}">
                                        </form>
                                        @if ($r->status === 'selesai')
                                            <button type="submit" form="status-quick-{{ $r->id }}" class="btn btn-sm btn-outline-secondary" title="Buka kembali laporan">Buka</button>
                                        @else
                                            <button type="submit" form="status-quick-{{ $r->id }}" class="btn btn-sm btn-outline-success" title="Tutup laporan (tandai selesai)">Tutup</button>
                                        @endif

                                        <div class="modal fade" id="status-{{ $r->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <form method="POST" action="{{ url('admin/gangguan/status/'.$r->id) }}">
                                                        @csrf
                                                        <input type="hidden" name="periode" value="{{ $periode }}">
                                                        <input type="hidden" name="tanggal" value="{{ $tanggal }}">
                                                        <input type="hidden" name="f_status" value="{{ $statusFilter }}">
                                                        <input type="hidden" name="f_kategori" value="{{ $kategoriFilter }}">
                                                        <div class="modal-header">
                                                            <h6 class="modal-title text-start">Laporan {{ $r->from_name ?: $r->from_number }}</h6>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body text-start">
                                                            <p class="mb-2"><span class="badge bg-soft-primary text-primary">{{ GangguanReport::kategoriLabel($r->kategori) }}</span></p>
                                                            <div class="border rounded p-2 bg-light mb-3" style="white-space: pre-wrap;">{{ $r->pesan }}</div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Status</label>
                                                                <select name="status" class="form-select">
                                                                    @foreach (GangguanReport::STATUSES as $st)
                                                                        <option value="{{ $st }}" {{ $r->status === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="mb-2">
                                                                <label class="form-label">Catatan penanganan (opsional)</label>
                                                                <textarea name="catatan" class="form-control" rows="2" maxlength="500">{{ $r->catatan }}</textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                            <button type="submit" class="btn btn-success">Simpan</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted py-3">Tidak ada laporan pada periode/filter ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{ $list->onEachSide(1)->links('pagination::bootstrap-5') }}
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
(function () {
    var checkAll = document.getElementById('gg-check-all');
    var bulkButton = document.getElementById('bulk-close-btn');
    var bulkCount = document.getElementById('bulk-close-count');
    var bulkForm = document.getElementById('bulk-close-form');
    var rowChecks = Array.prototype.slice.call(document.querySelectorAll('.gg-row-check'));

    function updateBulkState() {
        var selected = rowChecks.filter(function (checkbox) { return checkbox.checked; }).length;

        bulkButton.disabled = selected === 0;
        bulkCount.textContent = selected;
        checkAll.disabled = rowChecks.length === 0;
        checkAll.checked = rowChecks.length > 0 && selected === rowChecks.length;
        checkAll.indeterminate = selected > 0 && selected < rowChecks.length;
        bulkForm.dataset.text = 'Tutup ' + selected + ' laporan terpilih? Status akan menjadi Selesai.';
    }

    checkAll.addEventListener('change', function () {
        rowChecks.forEach(function (checkbox) {
            checkbox.checked = checkAll.checked;
        });
        updateBulkState();
    });

    rowChecks.forEach(function (checkbox) {
        checkbox.addEventListener('change', updateBulkState);
    });

    updateBulkState();
})();

// Konfirmasi via SweetAlert2 (bukan window.confirm bawaan browser) untuk aksi status.
document.addEventListener('submit', function (e) {
    var form = e.target.closest('form.swal-confirm');
    if (!form || form.dataset.confirmed === '1') return;
    e.preventDefault();
    Swal.fire({
        title: 'Konfirmasi',
        text: form.dataset.text || 'Lanjutkan?',
        icon: form.dataset.icon || 'question',
        showCancelButton: true,
        confirmButtonText: form.dataset.confirm || 'Ya',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#34c38f',
        cancelButtonColor: '#74788d'
    }).then(function (result) {
        if (result.isConfirmed) {
            form.dataset.confirmed = '1';
            form.submit();
        }
    });
});
</script>
@endsection
