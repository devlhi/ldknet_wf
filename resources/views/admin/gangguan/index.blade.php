@extends('admin.layout')

@php use App\Models\GangguanReport; @endphp

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box">
                    <h4 class="mb-0">Laporan Gangguan</h4>
                    <p class="text-muted mb-0">Laporan otomatis terserap dari chat WhatsApp pelanggan (Meta &amp; gateway lama).</p>
                </div>
            </div>
        </div>

        @if (session('auth_errors'))
            <div class="alert alert-danger">@foreach (session('auth_errors') as $e)<p class="mb-0">{{ $e }}</p>@endforeach</div>
        @endif
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="mdi mdi-check-circle me-2"></i>@foreach (session('success') as $s){{ $s }}@endforeach
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- Rekap bulan --}}
        <div class="row">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body text-center py-3">
                        <h3 class="mb-0">{{ number_format($totalBulan) }}</h3>
                        <p class="text-muted mb-0">Total laporan bulan ini</p>
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
                <h5 class="card-title mb-3"><i class="uil uil-chart-pie me-1"></i> Gangguan Paling Sering Dilaporkan
                    <small class="text-muted">— {{ \Carbon\Carbon::parse($bulan.'-01')->translatedFormat('F Y') }}</small>
                </h5>
                @if ($rekapKategori->isEmpty())
                    <p class="text-muted mb-0">Belum ada laporan pada bulan ini.</p>
                @else
                    @foreach ($rekapKategori as $rk)
                        @php $pct = $totalBulan > 0 ? round($rk->total / $totalBulan * 100) : 0; @endphp
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

        {{-- Filter + Daftar --}}
        <div class="card">
            <div class="card-body">
                <form method="GET" class="row g-2 mb-3">
                    <div class="col-md-3">
                        <label class="form-label">Bulan</label>
                        <input type="month" name="bulan" class="form-control" value="{{ $bulan }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">Semua</option>
                            @foreach (GangguanReport::STATUSES as $st)
                                <option value="{{ $st }}" {{ $statusFilter === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Kategori</label>
                        <select name="kategori" class="form-select">
                            <option value="">Semua</option>
                            @foreach (['internet_mati', 'internet_lambat', 'tidak_bisa_akses', 'wifi', 'pembayaran', 'lainnya'] as $kat)
                                <option value="{{ $kat }}" {{ $kategoriFilter === $kat ? 'selected' : '' }}>{{ GangguanReport::kategoriLabel($kat) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button class="btn btn-primary me-1"><i class="uil uil-filter"></i> Filter</button>
                        <a href="{{ url('admin/gangguan') }}" class="btn btn-light">Reset</a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light">
                            <tr>
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
                                <tr>
                                    <td class="text-nowrap"><small>{{ $r->created_at->timezone('Asia/Jakarta')->format('d/m H:i') }}</small></td>
                                    <td>
                                        <div class="fw-semibold">{{ $r->from_name ?: '-' }}</div>
                                        <small class="text-muted">{{ $r->idpel ? 'ID: '.$r->idpel.' · ' : '' }}{{ $r->from_number }}</small>
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
                                            <a href="{{ url('admin/whatsapp/inbox?number='.$r->from_number) }}" class="btn btn-sm btn-success" title="Balas via Inbox (dengan signature)"><i class="uil uil-whatsapp"></i> Balas</a>
                                        @endif
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#status-{{ $r->id }}"><i class="uil uil-edit"></i> Status</button>

                                        <div class="modal fade" id="status-{{ $r->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <form method="POST" action="{{ url('admin/gangguan/status/'.$r->id) }}">
                                                        @csrf
                                                        <input type="hidden" name="bulan" value="{{ $bulan }}">
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
                                                            <button type="submit" class="btn btn-success"><i class="uil uil-save"></i> Simpan</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-3">Tidak ada laporan pada filter ini.</td></tr>
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
