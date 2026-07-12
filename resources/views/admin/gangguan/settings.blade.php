@extends('admin.layout')

@section('content')
<div class="page-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <h4 class="mb-0">Pengaturan Laporan Gangguan</h4>
                    <a href="{{ url('admin/gangguan') }}" class="btn btn-light"><i class="mdi mdi-arrow-left me-1"></i> Kembali</a>
                </div>
            </div>
        </div>

        @if (session('auth_errors'))
            <div class="alert alert-danger">@foreach (session('auth_errors') as $err)<p class="mb-0">{{ $err }}</p>@endforeach</div>
        @endif
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="mdi mdi-check-circle me-2"></i>@foreach (session('success') as $suc){{ $suc }}@endforeach
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <form method="post" action="{{ url('admin/gangguan/pengaturan') }}">
            @csrf
            <div class="row">
                <div class="col-lg-7">
                    {{-- Balasan otomatis --}}
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-3"><i class="mdi mdi-message-text me-1"></i> Balasan Otomatis</h5>

                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" role="switch" name="auto_reply_enabled" id="autoReply" value="1" {{ old('auto_reply_enabled', $setting->auto_reply_enabled) ? 'checked' : '' }}>
                                <label class="form-check-label" for="autoReply">Kirim balasan otomatis saat laporan gangguan masuk</label>
                            </div>

                            <div class="mb-2">
                                <label class="form-label">Isi balasan otomatis</label>
                                <textarea name="auto_reply_text" class="form-control" rows="5" maxlength="1000" placeholder="Halo{nama}, laporan Anda kami terima...">{{ old('auto_reply_text', $setting->auto_reply_text) }}</textarea>
                                <small class="text-muted">Variabel: <code>{nama}</code> (nama pelanggan, otomatis diberi spasi di depan), <code>{kategori}</code> (jenis gangguan terdeteksi).</small>
                            </div>
                        </div>
                    </div>

                    {{-- Gangguan massal --}}
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-3"><i class="mdi mdi-alert me-1"></i> Deteksi Gangguan Massal</h5>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Minimal laporan per ODP</label>
                                    <input type="number" name="massal_threshold" class="form-control" min="2" max="50" value="{{ old('massal_threshold', $setting->massal_threshold) }}" required>
                                    <small class="text-muted">Jumlah laporan dari ODP sama sebelum ditandai gangguan massal.</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Rentang waktu (jam)</label>
                                    <input type="number" name="massal_window_hours" class="form-control" min="1" max="72" value="{{ old('massal_window_hours', $setting->massal_window_hours) }}" required>
                                    <small class="text-muted">Laporan dihitung bila masuk dalam rentang jam terakhir ini.</small>
                                </div>
                            </div>

                            <div class="mb-2">
                                <label class="form-label">Isi pesan broadcast ke pelanggan terdampak</label>
                                <textarea name="massal_broadcast_text" class="form-control" rows="5" maxlength="1000" placeholder="Pemberitahuan gangguan...">{{ old('massal_broadcast_text', $setting->massal_broadcast_text) }}</textarea>
                                <small class="text-muted">Variabel: <code>{odp}</code> (nama ODP), <code>{nama}</code> (nama pelanggan). Dikirim manual dari tombol pada banner gangguan massal.</small>
                            </div>
                        </div>
                    </div>

                    {{-- SLA --}}
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-3"><i class="mdi mdi-clock-outline me-1"></i> SLA Penanganan</h5>
                            <div class="mb-2" style="max-width: 320px;">
                                <label class="form-label">Batas waktu respons (jam)</label>
                                <input type="number" name="sla_response_hours" class="form-control" min="1" max="168" value="{{ old('sla_response_hours', $setting->sla_response_hours) }}" required>
                                <small class="text-muted">Laporan berstatus "baru" yang melewati batas ini ditandai <span class="badge bg-danger">telat</span>.</small>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary mb-4"><i class="mdi mdi-content-save me-1"></i> Simpan Pengaturan</button>
                </div>

                <div class="col-lg-5">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Cara Kerja</h5>
                            <ul class="text-muted mb-0">
                                <li><strong>Balasan otomatis</strong> — begitu pelanggan mengirim keluhan via WhatsApp dan terdeteksi sebagai gangguan, sistem langsung membalas "laporan diterima" (via gateway aktif: Meta/lama). Pelanggan merasa direspons tanpa menunggu admin.</li>
                                <li><strong>Deteksi gangguan massal</strong> — jika banyak laporan datang dari ODP yang sama dalam waktu singkat, kemungkinan besar gangguan area (bukan per pelanggan). Banner peringatan muncul di halaman Laporan Gangguan lengkap dengan tombol kirim info ke seluruh pelanggan ODP itu.</li>
                                <li><strong>SLA</strong> — rata-rata waktu respons &amp; penyelesaian dihitung otomatis. Laporan yang belum ditangani melewati batas jam akan ditandai agar tidak terlupakan.</li>
                                <li>Pastikan setiap ODP memiliki titik koordinat pada menu <a href="{{ url('admin/coverage/odp') }}">Coverage ODP</a> agar lokasi gangguan massal bisa dilihat di peta.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
